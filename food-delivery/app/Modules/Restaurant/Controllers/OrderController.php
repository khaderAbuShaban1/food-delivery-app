<?php

namespace App\Modules\Restaurant\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrderController extends Controller
{
    private function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'قيد الانتظار',
            'accepted' => 'مقبول',
            'preparing' => 'جاري التحضير',
            'delivering' => 'قيد التوصيل',
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

        $myOrders = Order::where('restaurant_id', $restaurant->id)
            ->with(['orderItems.menuItem:id,name', 'customerUser:id,name', 'legacyUser:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('restaurant::orders', compact('restaurant', 'myOrders'));
    }

    public function realtime(Request $request)
    {
        $restaurant = Auth::guard('restaurant')->user();
        if (!$restaurant || !$restaurant->id) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح',
            ], 401);
        }

        $orders = Order::where('restaurant_id', $restaurant->id)
            ->with(['orderItems.menuItem:id,name', 'customerUser:id,name', 'legacyUser:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();

        $serialized = $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number ?: $order->id,
                'customer_name' => $order->customerUser?->name ?? $order->legacyUser?->name ?? 'عميل غير محدد',
                'items' => $order->orderItems->map(function ($item) {
                    return [
                        'quantity' => (int) ($item->quantity ?? 1),
                        'name' => $item->name ?? $item->menuItem?->name ?? 'صنف',
                    ];
                })->values(),
                'total_price' => (float) $order->total_price,
                'status' => $order->status,
                'status_label' => $this->statusLabel($order->status),
                'created_date' => optional($order->created_at)->format('Y-m-d'),
                'created_time' => optional($order->created_at)->format('H:i'),
                'status_update_url' => route('restaurant.orders.status', $order->id),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'orders' => $serialized,
                'summary' => [
                    'total' => $orders->count(),
                    'pending' => $orders->where('status', 'pending')->count(),
                    'in_progress' => $orders->whereIn('status', ['accepted', 'preparing', 'delivering'])->count(),
                ],
            ],
        ]);
    }

    public function updateStatus(Request $request, int $orderId): RedirectResponse
    {
        $restaurant = Auth::guard('restaurant')->user();
        if (!$restaurant) {
            return back()->with('error', 'غير مصرح');
        }

        $request->validate([
            'status' => 'required|in:pending,accepted,preparing,delivering,completed,cancelled',
        ]);

        $order = Order::where('id', $orderId)
            ->where('restaurant_id', $restaurant->id)
            ->first();

        if (!$order) {
            return back()->with('error', 'الطلب غير موجود');
        }

        $transitions = [
            'pending' => ['accepted', 'cancelled'],
            'accepted' => ['preparing', 'cancelled'],
            'preparing' => ['delivering', 'cancelled'],
            'delivering' => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
        ];

        $nextStatus = $request->status;
        $allowed = $transitions[$order->status] ?? [];
        if (!in_array($nextStatus, $allowed, true) && $nextStatus !== $order->status) {
            return back()->with('error', 'لا يمكن تحديث الحالة بهذا الشكل');
        }

        $order->update([
            'status' => $nextStatus,
        ]);

        return back()->with('success', 'تم تحديث حالة الطلب!');
    }
}