<?php

namespace App\Modules\Restaurant\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Restaurant\Services\ApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        protected ApiService $api
    ) {}

    public function index(): View|RedirectResponse
    {
        $user = session()->get('user');
        
        if (!$user) {
            return redirect()->route('restaurant.login');
        }
        
        $restaurantsResponse = $this->api->get('/restaurants');
        $restaurants = $restaurantsResponse['data'] ?? [];
        
        $myRestaurants = array_filter($restaurants, function($r) use ($user) {
            return $r['user_id'] == ($user['id'] ?? null);
        });
        
        $restaurant = reset($myRestaurants) ?: ['id' => 0, 'name' => 'No Restaurant'];

        $ordersResponse = $this->api->get('/orders');
        $orders = $ordersResponse['data'] ?? [];
        
        $myOrders = array_filter($orders, function($o) use ($restaurant) {
            return $o['restaurant_id'] == $restaurant['id'];
        });

        return view('restaurant::orders', compact('restaurant', 'myOrders'));
    }

    public function updateStatus(Request $request, int $orderId): RedirectResponse
    {
        $response = $this->api->put('/orders/' . $orderId . '/status', [
            'status' => $request->status,
        ]);

        if ($response && ($response['success'] ?? false)) {
            return back()->with('success', 'تم تحديث حالة الطلب بنجاح!');
        }

        return back()->with('error', $response['message'] ?? 'فشل في تحديث حالة الطلب');
    }
}