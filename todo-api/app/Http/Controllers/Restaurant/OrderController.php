<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $restaurant = Restaurant::where('user_id', $user->id)->firstOrFail();
        $orders = Order::where('restaurant_id', $restaurant->id)
            ->with('orderItems.menuItem', 'user')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('restaurant.orders', compact('restaurant', 'orders'));
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $user = auth()->user();
        $restaurant = Restaurant::where('user_id', $user->id)->firstOrFail();

        $order = Order::where('id', $id)->where('restaurant_id', $restaurant->id)->firstOrFail();

        $request->validate([
            'status' => 'required|in:pending,accepted,preparing,delivering,completed',
        ]);

        $order->update(['status' => $request->status]);

        return redirect()->route('restaurant.orders')->with('success', 'Order status updated!');
    }
}