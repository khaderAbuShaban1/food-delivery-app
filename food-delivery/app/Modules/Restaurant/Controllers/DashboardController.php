<?php

namespace App\Modules\Restaurant\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = session()->get('user');
        
        if (!$user) {
            return redirect()->route('restaurant.login');
        }
        
        $restaurant = Restaurant::where('user_id', $user['id'])->first();
        
        if (!$restaurant) {
            return view('restaurant::dashboard', [
                'restaurant' => null,
                'menuItems' => [],
                'myOrders' => [],
                'ordersCount' => 0,
                'pendingOrders' => 0,
            ]);
        }
        
        $menuItems = MenuItem::where('restaurant_id', $restaurant->id)->get();
        
        $myOrders = Order::where('restaurant_id', $restaurant->id)->with('orderItems.menuItem')->get();
        
        $ordersCount = $myOrders->count();
        $pendingOrders = $myOrders->where('status', 'pending')->count();

        return view('restaurant::dashboard', compact('restaurant', 'menuItems', 'myOrders', 'ordersCount', 'pendingOrders'));
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $restaurant = Restaurant::find($id);
        
        if ($restaurant) {
            $restaurant->update(['is_open' => $request->is_open == 1]);
            return back()->with('success', 'Status updated');
        }

        return back()->with('error', 'Restaurant not found');
    }
}
