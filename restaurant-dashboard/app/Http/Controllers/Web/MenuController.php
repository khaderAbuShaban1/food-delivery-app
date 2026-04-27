<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\ApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

class MenuController extends Controller
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
        
        $restaurant = reset($myRestaurants) ?: ['id' => 0, 'name' => 'No Restaurant'];

        $menuResponse = $this->api->get('/restaurants/' . $restaurant['id'] . '/menu');
        $menuItems = $menuResponse['data'] ?? [];

        return view('web.menu', compact('restaurant', 'menuItems'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = session()->get('user');
        
        $restaurantsResponse = $this->api->get('/restaurants');
        $restaurants = $restaurantsResponse['data'] ?? [];
        
        $myRestaurants = array_filter($restaurants, function($r) use ($user) {
            return $r['user_id'] == ($user['id'] ?? null);
        });
        
        $restaurant = reset($myRestaurants);
        
        if (!$restaurant) {
            return back()->with('error', 'لم يتم العثور على مطعم');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $image = $request->file('image');
        
        $response = $this->api->postWithFile(
            '/restaurants/' . $restaurant['id'] . '/menu',
            [
                'name' => $request->name,
                'price' => $request->price,
                'description' => $request->description ?? '',
            ],
            $image
        );

        if ($response && ($response['success'] ?? false)) {
            return back()->with('success', 'تمت إضافة الصنف بنجاح!');
        }

        return back()->with('error', $response['message'] ?? 'فشل في إضافة الصنف');
    }

    public function update(Request $request, int $restaurantId, int $menuItemId): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $image = $request->file('image');
        
        $response = $this->api->putWithFile(
            '/restaurants/' . $restaurantId . '/menu/' . $menuItemId,
            [
                'name' => $request->name,
                'price' => $request->price,
                'description' => $request->description ?? '',
            ],
            $image
        );

        if ($response && ($response['success'] ?? false)) {
            return back()->with('success', 'تم تحديث الصنف بنجاح!');
        }

        return back()->with('error', $response['message'] ?? 'فشل في تحديث الصنف');
    }

    public function destroy(int $restaurantId, int $menuItemId): RedirectResponse
    {
        $response = $this->api->delete('/restaurants/' . $restaurantId . '/menu/' . $menuItemId);

        if ($response && ($response['success'] ?? false)) {
            return back()->with('success', 'تم حذف الصنف بنجاح!');
        }

        return back()->with('error', $response['message'] ?? 'فشل في حذف الصنف');
    }
}