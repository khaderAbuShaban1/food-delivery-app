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
        $restaurant = session()->get('restaurant');
        
        if (!$restaurant || !$restaurant->id) {
            return redirect()->route('restaurant.login');
        }
        
        $menuItems = MenuItem::where('restaurant_id', $restaurant->id)->get();
        
        $myOrders = Order::where('restaurant_id', $restaurant->id)->with('orderItems.menuItem')->get();
        
        $ordersCount = $myOrders->count();
        $pendingOrders = $myOrders->where('status', 'pending')->count();

        return view('restaurant::dashboard', compact('restaurant', 'menuItems', 'myOrders', 'ordersCount', 'pendingOrders'));
    }

    public function updateStatus(Request $request, int $restaurantId): RedirectResponse
    {
        $restaurant = Restaurant::find($restaurantId);
        
        if (!$restaurant) {
            return back()->with('error', 'المطعم غير موجود');
        }

        $restaurant->update([
            'is_open' => $request->is_open == 1,
        ]);

        return back()->with('success', 'تم تحديث الحالة!');
    }
}