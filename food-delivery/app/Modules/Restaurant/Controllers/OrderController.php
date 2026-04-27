<?php

namespace App\Modules\Restaurant\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $restaurant = session()->get('restaurant');
        
        if (!$restaurant || !$restaurant->id) {
            return redirect()->route('restaurant.login');
        }

        $myOrders = Order::where('restaurant_id', $restaurant->id)
            ->with('orderItems.menuItem')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('restaurant::orders', compact('restaurant', 'myOrders'));
    }

    public function updateStatus(Request $request, int $orderId): RedirectResponse
    {
        $order = Order::find($orderId);
        
        if (!$order) {
            return back()->with('error', 'الطلب غير موجود');
        }

        $order->update([
            'status' => $request->status,
        ]);

        return back()->with('success', 'تم تحديث حالة الطلب!');
    }
}