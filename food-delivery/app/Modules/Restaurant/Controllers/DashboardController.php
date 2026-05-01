<?php

namespace App\Modules\Restaurant\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'قيد الانتظار',
            'accepted' => 'مقبول',
            'preparing' => 'قيد التجهيز',
            'delivering' => 'في الطريق',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            default => $status,
        };
    }

    public function index(): View|RedirectResponse
    {
        $restaurant = Auth::guard('restaurant')->user();

        if (!$restaurant || !$restaurant->id) {
            return redirect()->route('restaurant.login');
        }

        $orderStatusCounts = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $today = now()->startOfDay();
        $todayOrdersQuery = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('created_at', '>=', $today);

        $stats = [
            'orders_total' => (int) Order::where('restaurant_id', $restaurant->id)->count(),
            'orders_today' => (int) (clone $todayOrdersQuery)->count(),
            'pending_orders' => (int) ($orderStatusCounts['pending'] ?? 0),
            'menu_items_total' => (int) MenuItem::where('restaurant_id', $restaurant->id)->count(),
            'menu_items_available' => (int) MenuItem::where('restaurant_id', $restaurant->id)->where('is_available', true)->count(),
            'revenue_total' => (float) Order::where('restaurant_id', $restaurant->id)->where('status', 'completed')->sum('total_price'),
            'revenue_today' => (float) (clone $todayOrdersQuery)->where('status', 'completed')->sum('total_price'),
        ];

        $orderStats = [
            'pending' => (int) ($orderStatusCounts['pending'] ?? 0),
            'accepted' => (int) ($orderStatusCounts['accepted'] ?? 0),
            'preparing' => (int) ($orderStatusCounts['preparing'] ?? 0),
            'delivering' => (int) ($orderStatusCounts['delivering'] ?? 0),
            'completed' => (int) ($orderStatusCounts['completed'] ?? 0),
            'cancelled' => (int) ($orderStatusCounts['cancelled'] ?? 0),
        ];

        $recentOrders = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->latest()
            ->limit(6)
            ->get(['id', 'order_number', 'status', 'total_price', 'created_at']);

        return view('restaurant::dashboard', compact('restaurant', 'stats', 'orderStats', 'recentOrders'));
    }

    public function realtime(Request $request): JsonResponse
    {
        $restaurant = Auth::guard('restaurant')->user();
        if (!$restaurant || !$restaurant->id) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح',
            ], 401);
        }

        $orderStatusCounts = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $today = now()->startOfDay();
        $todayOrdersQuery = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('created_at', '>=', $today);

        $stats = [
            'orders_total' => (int) Order::where('restaurant_id', $restaurant->id)->count(),
            'orders_today' => (int) (clone $todayOrdersQuery)->count(),
            'pending_orders' => (int) ($orderStatusCounts['pending'] ?? 0),
            'menu_items_total' => (int) MenuItem::where('restaurant_id', $restaurant->id)->count(),
            'menu_items_available' => (int) MenuItem::where('restaurant_id', $restaurant->id)->where('is_available', true)->count(),
            'revenue_total' => (float) Order::where('restaurant_id', $restaurant->id)->where('status', 'completed')->sum('total_price'),
            'revenue_today' => (float) (clone $todayOrdersQuery)->where('status', 'completed')->sum('total_price'),
        ];

        $orderStats = [
            'pending' => (int) ($orderStatusCounts['pending'] ?? 0),
            'accepted' => (int) ($orderStatusCounts['accepted'] ?? 0),
            'preparing' => (int) ($orderStatusCounts['preparing'] ?? 0),
            'delivering' => (int) ($orderStatusCounts['delivering'] ?? 0),
            'completed' => (int) ($orderStatusCounts['completed'] ?? 0),
            'cancelled' => (int) ($orderStatusCounts['cancelled'] ?? 0),
        ];

        $recentOrders = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->latest()
            ->limit(6)
            ->get(['id', 'order_number', 'status', 'total_price', 'created_at'])
            ->map(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number ?: $order->id,
                'status' => $order->status,
                'status_label' => $this->statusLabel($order->status),
                'total_price' => (float) $order->total_price,
                'created_at' => optional($order->created_at)->format('Y-m-d H:i'),
            ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'orderStats' => $orderStats,
                'recentOrders' => $recentOrders,
            ],
        ]);
    }

    public function updateStatus(Request $request, int $restaurantId): JsonResponse|RedirectResponse
    {
        $authRestaurant = Auth::guard('restaurant')->user();
        if (!$authRestaurant || (int) $authRestaurant->id !== $restaurantId) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'غير مصرح بهذا الإجراء'], 403);
            }
            return back()->with('error', 'غير مصرح بهذا الإجراء');
        }

        $restaurant = Restaurant::find($restaurantId);
        if (!$restaurant) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'المطعم غير موجود'], 404);
            }
            return back()->with('error', 'المطعم غير موجود');
        }

        $request->validate([
            'is_open' => 'required|boolean',
        ]);

        $restaurant->update([
            'is_open' => $request->boolean('is_open'),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الحالة!',
                'is_open' => (bool) $restaurant->is_open,
                'status_label' => $restaurant->is_open ? 'مفتوح' : 'مغلق',
                'status_description' => $restaurant->is_open ? 'يقبل الطلبات حالياً' : 'متوقف عن استقبال الطلبات',
            ]);
        }

        return back()->with('success', 'تم تحديث الحالة!');
    }
}