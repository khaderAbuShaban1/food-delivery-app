<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\SystemSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Throwable;

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

    /**
     * Resolve delivery text for a new order: explicit address row, then default saved address, then legacy users.address.
     */
    private function resolveDeliverySnapshotForCustomer(User $customer, ?int $addressId): ?string
    {
        if ($addressId !== null) {
            $row = Address::query()
                ->where('user_id', $customer->id)
                ->where('id', $addressId)
                ->first();

            return $row ? $row->formattedDeliveryLine() : null;
        }

        $default = Address::query()
            ->where('user_id', $customer->id)
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->first();

        if ($default) {
            return $default->formattedDeliveryLine();
        }

        $legacy = trim((string) ($customer->address ?? ''));

        return $legacy !== '' ? $legacy : null;
    }

    /** Display line for API (stored snapshot + fallbacks for older rows). */
    private function deliveryAddressLineForResponse(Order $order): ?string
    {
        $stored = trim((string) ($order->delivery_address ?? ''));
        if ($stored !== '') {
            return $stored;
        }

        $buyer = $order->relationLoaded('customer') ? $order->customer : $order->customer()->first();
        if (! $buyer instanceof User) {
            return null;
        }

        $legacy = trim((string) ($buyer->address ?? ''));
        if ($legacy !== '') {
            return $legacy;
        }

        $fallback = Address::query()
            ->where('user_id', $buyer->id)
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->first();

        return $fallback ? $fallback->formattedDeliveryLine() : null;
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

        $customer = $request->user();
        if (! $customer instanceof User) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تسجيل الدخول كعميل لإنشاء طلب',
            ], 403);
        }

        $validated = $request->validate([
            'restaurant_id' => 'required|integer|exists:restaurants,id',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|integer|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'address_id' => [
                'nullable',
                'integer',
                Rule::exists('addresses', 'id')->where(fn ($q) => $q->where('user_id', $customer->id)),
            ],
        ]);

        $restaurant = Restaurant::find($validated['restaurant_id']);
        if (! $restaurant) {
            return response()->json([
                'success' => false,
                'message' => 'المطعم غير موجود',
            ], 404);
        }

        if (! $restaurant->is_open) {
            return response()->json([
                'success' => false,
                'message' => 'المطعم مغلق حالياً',
            ], 400);
        }

        $itemIds = array_map(static fn (array $row) => (int) $row['menu_item_id'], $validated['items']);
        $uniqueIds = array_values(array_unique($itemIds));

        $menuItems = MenuItem::query()
            ->where('restaurant_id', (int) $validated['restaurant_id'])
            ->whereIn('id', $uniqueIds)
            ->get()
            ->keyBy('id');

        if ($menuItems->count() !== count($uniqueIds)) {
            return response()->json([
                'success' => false,
                'message' => 'أحد الأصناف غير صالح أو لا يتبع هذا المطعم',
                'errors' => [
                    'items' => ['تأكد أن جميع الأصناف من قائمة المطعم الحالي'],
                ],
            ], 422);
        }

        $requestedAddrId = $validated['address_id'] ?? null;
        $addressId = $requestedAddrId !== null ? (int) $requestedAddrId : null;

        $deliverySnapshot = $this->resolveDeliverySnapshotForCustomer($customer, $addressId);
        if ($deliverySnapshot === null || trim($deliverySnapshot) === '') {
            return response()->json([
                'success' => false,
                'message' => 'أضف عنوان توصيلاً قبل تأكيد الطلب',
                'errors' => [
                    'address_id' => ['يرجى اختيار أو إضافة عنوان توصيل من الملف الشخصي'],
                ],
            ], 422);
        }

        try {
            $order = DB::transaction(function () use ($validated, $menuItems, $customer, $deliverySnapshot) {
                $totalPrice = 0.0;
                $orderItems = [];

                foreach ($validated['items'] as $item) {
                    $menuItemId = (int) $item['menu_item_id'];
                    $menuItem = $menuItems->get($menuItemId);
                    if (! $menuItem) {
                        throw new \RuntimeException('Menu item missing after validation');
                    }
                    $qty = (int) $item['quantity'];
                    $lineTotal = (float) $menuItem->price * $qty;
                    $totalPrice += $lineTotal;
                    $orderItems[] = [
                        'menu_item_id' => $menuItemId,
                        'quantity' => $qty,
                        'price' => $menuItem->price,
                    ];
                }

                $orderNumber = Order::generateOrderNumber();
                if (Schema::hasColumn('orders', 'order_number')) {
                    while (Order::query()->where('order_number', $orderNumber)->exists()) {
                        $orderNumber = Order::generateOrderNumber();
                    }
                }

                $createData = [
                    $this->customerColumn() => $customer->id,
                    'restaurant_id' => (int) $validated['restaurant_id'],
                    'total_price' => $totalPrice,
                    'status' => self::STATUS_PENDING,
                ];
                if (Schema::hasColumn('orders', 'order_number')) {
                    $createData['order_number'] = $orderNumber;
                }
                if (Schema::hasColumn('orders', 'delivery_address')) {
                    $createData['delivery_address'] = $deliverySnapshot;
                }

                $order = Order::create($createData);

                foreach ($orderItems as $orderItem) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'menu_item_id' => $orderItem['menu_item_id'],
                        'quantity' => $orderItem['quantity'],
                        'price' => $orderItem['price'],
                    ]);
                }

                return $order->load(['orderItems.menuItem', 'restaurant', 'customer']);
            });
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => config('app.debug')
                    ? 'Order save failed: '.$e->getMessage()
                    : 'تعذر حفظ الطلب في الخادم. حاول لاحقاً.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الطلب بنجاح',
            'data' => $this->formatOrder($order),
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

        $driver = \App\Models\User::where('id', $validated['driver_id'])
            ->where('role', 'driver')
            ->first();

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

    public function formatOrder($order): array
    {
        $buyer = $order->customer;
        $deliveryLine = $this->deliveryAddressLineForResponse($order);

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'restaurant_id' => $order->restaurant_id,
            'delivery_address' => $deliveryLine,
            'restaurant' => $order->restaurant ? [
                'id' => $order->restaurant->id,
                'name' => $order->restaurant->name,
                'image' => $order->restaurant->image,
                'phone' => $order->restaurant->phone ?? null,
            ] : null,
            'customer' => $buyer ? [
                'id' => $buyer->id,
                'name' => $buyer->name,
                'phone' => $buyer->phone ?? $buyer->email,
                'address' => $deliveryLine,
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