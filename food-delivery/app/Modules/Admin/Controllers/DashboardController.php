<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Restaurant;
use App\Models\Order;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = now()->startOfDay();
        
        $stats = [
            'activeRestaurants' => Restaurant::where('is_open', true)->count(),
            'activeCustomers' => User::where('role', 'customer')->count(),
            'todayRevenue' => Order::where('status', 'completed')
                ->where('created_at', '>=', $today)
                ->sum('total_price'),
            'todayOrders' => Order::where('created_at', '>=', $today)->count(),
        ];

        $orderStats = [
            'preparing' => Order::where('status', 'preparing')->count(),
            'delivering' => Order::where('status', 'delivering')->count(),
            'completed' => Order::where('status', 'completed')->where('created_at', '>=', $today)->count(),
        ];

        $totalOrders = Order::count();
        $progressPreparing = $totalOrders > 0 ? ($orderStats['preparing'] / $totalOrders) * 100 : 0;
        $progressDelivering = $totalOrders > 0 ? ($orderStats['delivering'] / $totalOrders) * 100 : 0;
        $progressCompleted = $totalOrders > 0 ? ($orderStats['completed'] / $totalOrders) * 100 : 0;

        return view('admin::dashboard', compact('stats', 'orderStats', 'progressPreparing', 'progressDelivering', 'progressCompleted'));
    }

    public function users(Request $request): View
    {
        $query = User::query();

        if ($request->role && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        $users = $query->latest()->paginate(20);
        $roles = ['customer' => 'عميل', 'driver' => 'سائق', 'restaurant' => 'مطعم', 'admin' => 'مدير'];

        return view('admin::users', compact('users', 'roles'));
    }

    public function restaurants(Request $request): View
    {
        $query = Restaurant::query();

        if ($request->has('is_open')) {
            $query->where('is_open', $request->is_open);
        }

        $restaurants = $query->latest()->get();
        $categories = ['مشروبات' => 'مشروبات', 'حلويات' => 'حلويات', 'شاورما' => 'شاورما'];

        return view('admin::restaurants', compact('restaurants', 'categories'));
    }

    public function storeRestaurant(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|min:6',
            'phone' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active' => 'boolean',
            'is_open' => 'boolean',
        ]);

        $data = $request->only(['name', 'category', 'email', 'phone', 'is_active', 'is_open']);
        $data['password'] = bcrypt($request->password);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('restaurants', 'public');
        }

        Restaurant::create($data);

        return back()->with('success', 'تمت إضافة المطعم بنجاح');
    }

    public function updateRestaurant(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'email' => 'required|email',
            'password' => 'nullable|min:6',
            'phone' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active' => 'boolean',
            'is_open' => 'boolean',
        ]);

        $restaurant = Restaurant::findOrFail($id);
        $data = $request->only(['name', 'category', 'email', 'phone', 'is_active', 'is_open']);

        if ($request->password) {
            $data['password'] = bcrypt($request->password);
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('restaurants', 'public');
        }

        $restaurant->update(array_filter($data));

        return back()->with('success', 'تم تحديث المطعم بنجاح');
    }

    public function deleteRestaurant(int $id): RedirectResponse
    {
        Restaurant::findOrFail($id)->delete();
        return back()->with('success', 'تم حذف المطعم بنجاح');
    }

    public function menu(): View
    {
        $menuItems = MenuItem::with('restaurant')
            ->latest()
            ->paginate(20);

        return view('admin::menu', compact('menuItems'));
    }

    public function orders(Request $request): View
    {
        $query = Order::with(['restaurant']);

        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate(20);
        $statuses = ['pending' => 'قيد الانتظار', 'accepted' => 'مقبول', 'preparing' => 'قيد التجهيز', 'delivering' => 'في الطريق', 'completed' => 'مكتمل'];

        return view('admin::orders', compact('orders', 'statuses'));
    }

    public function offers(): View
    {
        return view('admin::offers');
    }

    public function toggleRestaurant(int $id): RedirectResponse
    {
        $restaurant = Restaurant::findOrFail($id);
        $restaurant->update(['is_open' => !$restaurant->is_open]);

        return back()->with('success', 'تم تحديث حالة المطعم');
    }

    public function toggleActive(int $id): RedirectResponse
    {
        $restaurant = Restaurant::findOrFail($id);
        $restaurant->update(['is_active' => !$restaurant->is_active]);

        return back()->with('success', 'تم تحديث الحالة');
    }

    public function toggleOpenStatus(int $id): RedirectResponse
    {
        $restaurant = Restaurant::findOrFail($id);
        $restaurant->update(['is_open' => !$restaurant->is_open]);

        return back()->with('success', 'تم تحديث حالة الفتح');
    }
}