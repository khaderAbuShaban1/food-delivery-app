<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\ApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected ApiService $api
    ) {}

    public function index(): View|RedirectResponse
    {
        $user = session()->get('user');
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        $restaurantsResponse = $this->api->get('/restaurants');
        $restaurants = $restaurantsResponse['data'] ?? [];
        
        $myRestaurants = array_filter($restaurants, function($r) use ($user) {
            return $r['user_id'] == ($user['id'] ?? null);
        });
        
        $restaurant = reset($myRestaurants) ?: null;
        
        if (!$restaurant) {
            return view('web.dashboard', [
                'restaurant' => ['id' => 0, 'name' => 'No Restaurant', 'is_open' => false],
                'menuItems' => [],
                'myOrders' => [],
                'ordersCount' => 0,
                'pendingOrders' => 0,
            ]);
        }
        
        $menuResponse = $this->api->get('/restaurants/' . $restaurant['id'] . '/menu');
        $menuItems = $menuResponse['data'] ?? [];
        
        $ordersResponse = $this->api->get('/orders');
        $orders = $ordersResponse['data'] ?? [];
        
        $myOrders = array_filter($orders, function($o) use ($restaurant) {
            return $o['restaurant_id'] == $restaurant['id'];
        });
        
        $ordersCount = count($myOrders);
        $pendingOrders = count(array_filter($myOrders, function($o) {
            return $o['status'] === 'pending';
        }));

        return view('web.dashboard', compact('restaurant', 'menuItems', 'myOrders', 'ordersCount', 'pendingOrders'));
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $response = $this->api->put('/restaurants/' . $id, [
            'is_open' => $request->is_open,
        ]);

        if ($response && ($response['success'] ?? false)) {
            return back()->with('success', 'Status updated');
        }

        return back()->with('error', $response['message'] ?? 'Update failed');
    }
}