<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $restaurant = Restaurant::where('user_id', $user->id)->first();

        if (!$restaurant) {
            $restaurant = Restaurant::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'category' => 'General',
                'is_open' => false,
            ]);
        }

        $ordersCount = Order::where('restaurant_id', $restaurant->id)->count();
        $pendingOrders = Order::where('restaurant_id', $restaurant->id)
            ->where('status', 'pending')
            ->count();

        return view('restaurant.dashboard', compact('restaurant', 'ordersCount', 'pendingOrders'));
    }

    public function updateStatus(Request $request, Restaurant $restaurant): RedirectResponse
    {
        $user = auth()->user();
        $dbRestaurant = Restaurant::where('user_id', $user->id)->where('id', $restaurant->id)->firstOrFail();

        $dbRestaurant->update(['is_open' => $request->is_open == 1]);
        $message = $request->is_open == 1 ? 'Restaurant is now open!' : 'Restaurant is now closed!';

        return redirect()->route('restaurant.dashboard')->with('success', $message);
    }
}