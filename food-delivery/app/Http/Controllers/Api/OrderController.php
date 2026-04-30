<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\SystemSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isRestaurant()) {
            $orders = Order::where('restaurant_id', $user->restaurants->pluck('id'))
                ->with(['user', 'restaurant', 'orderItems.menuItem'])
                ->get();
        } elseif ($user->isDriver()) {
            $orders = Order::where('driver_id', $user->id)
                ->orWhereNull('driver_id')
                ->with(['user', 'restaurant', 'orderItems.menuItem'])
                ->get();
        } else {
            $orders = $user->orders()
                ->with(['restaurant', 'orderItems.menuItem'])
                ->get();
        }

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
                'message' => 'Restaurant is currently closed',
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
            'user_id' => $request->user()->id,
            'restaurant_id' => $validated['restaurant_id'],
            'total_price' => $totalPrice,
            'status' => 'pending',
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
            'message' => 'Order created successfully',
            'data' => $order->load(['orderItems.menuItem', 'restaurant']),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $order = Order::with(['user', 'restaurant', 'driver', 'orderItems.menuItem'])->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order,
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
            'status' => 'required|in:pending,accepted,preparing,delivering,completed',
        ]);

        $statusFlow = ['pending', 'accepted', 'preparing', 'delivering', 'completed'];
        $currentIndex = array_search($order->status, $statusFlow);
        $newIndex = array_search($validated['status'], $statusFlow);

        if ($newIndex < $currentIndex) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك التراجع عن حالة الطلب',
            ], 400);
        }

        $order->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الطلب بنجاح',
            'data' => $order,
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
            'data' => $order->load('driver'),
        ]);
    }
}