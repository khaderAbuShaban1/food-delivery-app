<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\SystemSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class OrderController extends Controller
{
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_PREPARING = 'preparing';
    const STATUS_DELIVERING = 'delivering';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const STATUS_FLOW = [
        self::STATUS_PENDING,
        self::STATUS_ACCEPTED,
        self::STATUS_PREPARING,
        self::STATUS_DELIVERING,
        self::STATUS_COMPLETED,
    ];

    const STATUS_LABELS = [
        'pending' => 'بانتظار التأكيد',
        'accepted' => 'تم التأكيد',
        'preparing' => 'قيد التحضير',
        'delivering' => 'في الطريق',
        'completed' => 'تم التسليم',
        'cancelled' => 'ملغى',
    ];

    const STATUS_COLORS = [
        'pending' => '#6B6B6B',
        'accepted' => '#3498DB',
        'preparing' => '#FF7A30',
        'delivering' => '#9B59B6',
        'completed' => '#2ECC71',
        'cancelled' => '#E74C3C',
    ];

    const ALLOWED_TRANSITIONS = [
        'pending' => ['accepted', 'cancelled'],
        'accepted' => ['preparing', 'cancelled'],
        'preparing' => ['delivering', 'cancelled'],
        'delivering' => ['completed'],
    ];

    private function customerColumn(): string
    {
        if (Schema::hasColumn('orders', 'customer_id')) {
            return 'customer_id';
        }

        return 'user_id';
    }

    public function getStatusLabel(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? $status;
    }

    public function getStatusColor(string $status): string
    {
        return self::STATUS_COLORS[$status] ?? '#6B6B6B';
    }

    public function canTransition(string $currentStatus, string $newStatus): bool
    {
        if (!isset(self::ALLOWED_TRANSITIONS[$currentStatus])) {
            return false;
        }

        return in_array($newStatus, self::ALLOWED_TRANSITIONS[$currentStatus]);
    }

    public function index(Request $request): JsonResponse
    {
        $customer = $request->user();
        $customerColumn = $this->customerColumn();

        $orders = Order::where($customerColumn, $customer->id)
            ->with(['restaurant', 'orderItems.menuItem'])
            ->latest()
            ->get()
            ->map(function ($order) {
                return $this->formatOrder($order);
            });

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var SystemSettingsService $settings */
        $settings = app(SystemSettingsService::class);
        if (!(bool) $settings->get('platform', 'platform_open', true)) {
            return response()->json([
                'success' => false,
                'message' => 'Platform is currently closed',
            ], 503);
        }

        if (!(bool) $settings->get('platform', 'orders_enabled', true)) {
            return response()->json([
                'success' => false,
                'message' => 'Orders are temporarily disabled for maintenance',
            ], 503);
        }

        if (!(bool) $settings->get('platform', 'restaurants_enabled', true)) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurants are temporarily unavailable',
            ], 503);
        }

        $validated = $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $restaurant = \App\Models\Restaurant::find($validated['restaurant_id']);
        if (!$restaurant->is_open) {
            return response()->json([
                'success' => false,
                'message' => 'المطعم مغلق حالياً',
            ], 400);
        }

        $totalPrice = 0;
        $orderItems = [];

        foreach ($validated['items'] as $item) {
            $menuItem = MenuItem::find($item['menu_item_id']);
            $totalPrice += $menuItem->price * $item['quantity'];
            $orderItems[] = [
                'menu_item_id' => $item['menu_item_id'],
                'quantity' => $item['quantity'],
                'price' => $menuItem->price,
            ];
        }

        $order = Order::create([
            $this->customerColumn() => $request->user()->id,
            'restaurant_id' => $validated['restaurant_id'],
            'total_price' => $totalPrice,
            'status' => self::STATUS_PENDING,
        ]);

        foreach ($orderItems as $orderItem) {
            OrderItem::create([
                'order_id' => $order->id,
                'menu_item_id' => $orderItem['menu_item_id'],
                'quantity' => $orderItem['quantity'],
                'price' => $orderItem['price'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الطلب بنجاح',
            'data' => $this->formatOrder($order->load(['orderItems.menuItem', 'restaurant'])),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $customerColumn = $this->customerColumn();
        $order = Order::with(['restaurant', 'driver', 'orderItems.menuItem'])
            ->where($customerColumn, $request->user()->id)
            ->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatOrder($order),
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود',
            ], 404);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,accepted,preparing,delivering,completed,cancelled',
        ]);

        $newStatus = $validated['status'];

        if (!$this->canTransition($order->status, $newStatus)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك تغيير حالة الطلب حالياً',
            ], 400);
        }

        $order->update(['status' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الطلب بنجاح',
            'data' => $this->formatOrder($order),
        ]);
    }

    public function restaurantOrders(Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id ?? $request->query('restaurant_id');

        if (!$restaurantId) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant ID required',
            ], 400);
        }

        $status = $request->query('status');

        $query = Order::where('restaurant_id', $restaurantId)
            ->with(['orderItems.menuItem', 'customer']);

        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query->latest()->get()->map(function ($order) {
            return $this->formatOrder($order);
        });

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    public function restaurantUpdateStatus(Request $request, int $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود',
            ], 404);
        }

        $validated = $request->validate([
            'status' => 'required|in:accepted,preparing,delivering,cancelled',
        ]);

        $newStatus = $validated['status'];

        $allowedForRestaurant = ['accepted', 'preparing', 'delivering', 'cancelled'];

        if (!in_array($newStatus, $allowedForRestaurant)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك تغيير إلى هذه الحالة',
            ], 400);
        }

        if (!$this->canTransition($order->status, $newStatus)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك تغيير حالة الطلب حالياً',
            ], 400);
        }

        $order->update(['status' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الطلب بنجاح',
            'data' => $this->formatOrder($order->load('orderItems.menuItem')),
        ]);
    }

    public function assignDriver(Request $request, int $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        $validated = $request->validate([
            'driver_id' => 'required|exists:users,id',
        ]);

        $driver = \App\Models\Driver::find($validated['driver_id']);

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver not found',
            ], 404);
        }

        $order->update(['driver_id' => $validated['driver_id']]);

        return response()->json([
            'success' => true,
            'message' => 'Driver assigned successfully',
            'data' => $this->formatOrder($order->load('driver')),
        ]);
    }

    private function formatOrder($order): array
    {
        return [
            'id' => $order->id,
            'restaurant_id' => $order->restaurant_id,
            'restaurant' => $order->restaurant ? [
                'id' => $order->restaurant->id,
                'name' => $order->restaurant->name,
                'image' => $order->restaurant->image,
            ] : null,
            'customer' => $order->customer ? [
                'id' => $order->customer->id,
                'name' => $order->customer->name,
                'phone' => $order->customer->phone ?? $order->customer->email,
            ] : null,
            'driver' => $order->driver ? [
                'id' => $order->driver->id,
                'name' => $order->driver->name,
            ] : null,
            'total_price' => (float) $order->total_price,
            'status' => $order->status,
            'status_label' => $this->getStatusLabel($order->status),
            'status_color' => $this->getStatusColor($order->status),
            'items' => $order->orderItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'menu_item_id' => $item->menu_item_id,
                    'name' => $item->menuItem?->name,
                    'price' => (float) $item->price,
                    'quantity' => $item->quantity,
                ];
            }),
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
        ];
    }
}