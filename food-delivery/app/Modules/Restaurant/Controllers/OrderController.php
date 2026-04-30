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