<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Driver;
use App\Models\Restaurant;
use App\Models\User;
use App\Models\Order;
use App\Models\MenuItem;
use App\Services\OrderWorkflow;
use App\Services\SystemSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private function statusLabel(string $status): string
    {
        return OrderWorkflow::label($status);
    }

    public function index(): View
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        $today = now()->startOfDay();

        $orderStatusCounts = Order::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $todayOrdersBase = Order::query()->where('created_at', '>=', $today);

        $stats = [
            'totalOrders' => (int) Order::count(),
            'todayOrders' => (int) (clone $todayOrdersBase)->count(),
            'todayRevenue' => (float) (clone $todayOrdersBase)->where('status', OrderWorkflow::DELIVERED)->sum('total_price'),
            'totalRevenue' => (float) Order::where('status', OrderWorkflow::DELIVERED)->sum('total_price'),
            'activeRestaurants' => (int) Restaurant::where('is_active', true)->count(),
            'openRestaurants' => (int) Restaurant::where('is_open', true)->count(),
            'totalRestaurants' => (int) Restaurant::count(),
            'totalCustomers' => (int) User::count(),
            'activeCustomers' => (int) User::where('is_active', true)->count(),
            'totalDrivers' => (int) Driver::count(),
            'activeDrivers' => (int) Driver::where('is_available', true)->count(),
            'totalAdmins' => (int) Admin::count(),
        ];

        $orderStats = collect(OrderWorkflow::allStatuses())
            ->mapWithKeys(fn (string $s) => [$s => (int) ($orderStatusCounts[$s] ?? 0)])
            ->all();

        $totalOrders = max($stats['totalOrders'], 1);
        $orderProgress = collect($orderStats)
            ->map(fn (int $c) => ($c / $totalOrders) * 100.0)
            ->all();

        $summaryAcceptedOrders = ($orderStats[OrderWorkflow::PAYMENT_VERIFIED] ?? 0)
            + ($orderStats[OrderWorkflow::ACCEPTED_BY_RESTAURANT] ?? 0)
            + ($orderStats[OrderWorkflow::PREPARING] ?? 0);

        $recentOrders = Order::query()
            ->with('restaurant:id,name')
            ->latest()
            ->limit(6)
            ->get();

        return view('admin::dashboard', compact('stats', 'orderStats', 'orderProgress', 'recentOrders', 'summaryAcceptedOrders'));
    }

    public function realtime(Request $request)
    {
        if (!Auth::guard('admin')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح',
            ], 401);
        }

        $today = now()->startOfDay();

        $orderStatusCounts = Order::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $todayOrdersBase = Order::query()->where('created_at', '>=', $today);

        $stats = [
            'totalOrders' => (int) Order::count(),
            'todayOrders' => (int) (clone $todayOrdersBase)->count(),
            'todayRevenue' => (float) (clone $todayOrdersBase)->where('status', OrderWorkflow::DELIVERED)->sum('total_price'),
            'totalRevenue' => (float) Order::where('status', OrderWorkflow::DELIVERED)->sum('total_price'),
            'activeRestaurants' => (int) Restaurant::where('is_active', true)->count(),
            'openRestaurants' => (int) Restaurant::where('is_open', true)->count(),
            'totalRestaurants' => (int) Restaurant::count(),
            'totalCustomers' => (int) User::count(),
            'activeCustomers' => (int) User::where('is_active', true)->count(),
            'totalDrivers' => (int) Driver::count(),
            'activeDrivers' => (int) Driver::where('is_available', true)->count(),
            'totalAdmins' => (int) Admin::count(),
        ];

        $orderStats = collect(OrderWorkflow::allStatuses())
            ->mapWithKeys(fn (string $s) => [$s => (int) ($orderStatusCounts[$s] ?? 0)])
            ->all();

        $summaryAcceptedOrders = ($orderStats[OrderWorkflow::PAYMENT_VERIFIED] ?? 0)
            + ($orderStats[OrderWorkflow::ACCEPTED_BY_RESTAURANT] ?? 0)
            + ($orderStats[OrderWorkflow::PREPARING] ?? 0);

        $recentOrders = Order::query()
            ->with('restaurant:id,name')
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number ?: $order->id,
                'restaurant_name' => $order->restaurant?->name ?? '-',
                'status' => $order->status,
                'status_label' => $this->statusLabel($order->status),
                'total_price' => (float) $order->total_price,
                'created_at' => optional($order->created_at)->format('Y-m-d H:i'),
            ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'orderStats' => $orderStats,
                'summaryAcceptedOrders' => $summaryAcceptedOrders,
                'recentOrders' => $recentOrders,
            ],
        ]);
    }

    public function users(Request $request): View
    {
        $customerQuery = DB::table('users')
            ->selectRaw("
                id,
                name,
                email,
                phone,
                profile_image as avatar,
                created_at,
                COALESCE(is_active, 1) as is_active,
                'customer' as account_type,
                (
                    select count(*)
                    from orders
                    where orders.customer_id = users.id
                ) as orders_count,
                (
                    select COALESCE(sum(total_price), 0)
                    from orders
                    where orders.customer_id = users.id
                    and orders.status = 'delivered'
                ) as total_spent
            ")
            ->whereNotNull('id');

        $driverQuery = DB::table('drivers')
            ->selectRaw("
                id,
                name,
                email,
                phone,
                null as avatar,
                created_at,
                COALESCE(is_available, 1) as is_active,
                'driver' as account_type,
                (
                    select count(*)
                    from orders
                    where orders.driver_id = drivers.id
                ) as orders_count,
                0 as total_spent
            ");

        $restaurantQuery = DB::table('restaurants')
            ->selectRaw("
                id,
                name,
                email,
                phone,
                image as avatar,
                created_at,
                COALESCE(is_active, 1) as is_active,
                'restaurant' as account_type,
                (
                    select count(*)
                    from orders
                    where orders.restaurant_id = restaurants.id
                ) as orders_count,
                0 as total_spent
            ");

        $adminQuery = DB::table('admins')
            ->selectRaw("
                id,
                name,
                email,
                phone,
                null as avatar,
                created_at,
                1 as is_active,
                'admin' as account_type,
                0 as orders_count,
                0 as total_spent
            ");

        $accountsQuery = $customerQuery
            ->unionAll($driverQuery)
            ->unionAll($restaurantQuery)
            ->unionAll($adminQuery);

        $query = DB::query()->fromSub($accountsQuery, 'accounts');

        if ($request->role && $request->role !== 'all') {
            $query->where('account_type', $request->role);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('id', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhere('name', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $users = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $roles = ['customer' => 'عميل', 'driver' => 'سائق', 'restaurant' => 'مطعم', 'admin' => 'مدير'];

        return view('admin::users', compact('users', 'roles'));
    }

    public function restaurants(Request $request): View
    {
        $query = Restaurant::query();

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->is_active);
        }

        if ($request->filled('is_open')) {
            $query->where('is_open', (bool) $request->is_open);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $restaurants = $query->latest()->paginate(15)->withQueryString();
        $categories = Restaurant::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $stats = [
            'total' => Restaurant::count(),
            'active' => Restaurant::where('is_active', true)->count(),
            'open' => Restaurant::where('is_open', true)->count(),
        ];

        return view('admin::restaurants', compact('restaurants', 'categories', 'stats'));
    }

    public function storeRestaurant(Request $request): RedirectResponse
    {
        /** @var SystemSettingsService $settings */
        $settings = app(SystemSettingsService::class);
        if (!(bool) $settings->get('platform', 'restaurants_enabled', true)) {
            return back()->with('error', 'تم تعطيل إدارة/تفعيل المطاعم حالياً من إعدادات المنصة');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'email' => 'required|email|unique:restaurants,email',
            'password' => 'required|min:6',
            'phone' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active' => 'boolean',
            'is_open' => 'boolean',
        ]);

        $data = $request->only(['name', 'category', 'email', 'phone']);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_open'] = $request->boolean('is_open');
        $data['password'] = bcrypt($request->password);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('restaurants', 'public');
        }

        Restaurant::create($data);

        return back()->with('success', 'تمت إضافة المطعم بنجاح');
    }

    public function updateRestaurant(Request $request, int $id): RedirectResponse
    {
        /** @var SystemSettingsService $settings */
        $settings = app(SystemSettingsService::class);
        if (!(bool) $settings->get('platform', 'restaurants_enabled', true)) {
            return back()->with('error', 'تم تعطيل إدارة/تفعيل المطاعم حالياً من إعدادات المنصة');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'email' => 'required|email|unique:restaurants,email,' . $id,
            'password' => 'nullable|min:6',
            'phone' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active' => 'boolean',
            'is_open' => 'boolean',
        ]);

        $restaurant = Restaurant::findOrFail($id);
        $data = $request->only(['name', 'category', 'email', 'phone']);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_open'] = $request->boolean('is_open');

        if ($request->password) {
            $data['password'] = bcrypt($request->password);
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('restaurants', 'public');
        }

        $restaurant->update($data);

        return back()->with('success', 'تم تحديث المطعم بنجاح');
    }

    public function deleteRestaurant(int $id): RedirectResponse
    {
        Restaurant::findOrFail($id)->delete();
        return back()->with('success', 'تم حذف المطعم بنجاح');
    }

    public function menu(Request $request): View
    {
        $query = MenuItem::with('restaurant');

        if ($request->status === 'available') {
            $query->where('is_available', true);
        } elseif ($request->status === 'unavailable') {
            $query->where('is_available', false);
        }

        if ($request->restaurant_id) {
            $query->where('restaurant_id', $request->restaurant_id);
        }

        if ($request->category) {
            $query->where('category', $request->category);
        }

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $menuItems = $query->latest()->paginate(20)->withQueryString();
        $restaurants = Restaurant::orderBy('name')->get();
        $categories = MenuItem::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('admin::menu', compact('menuItems', 'restaurants', 'categories'));
    }

    public function updateMenuItem(Request $request, int $id): RedirectResponse
    {
        $item = MenuItem::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'category' => 'required|string|max:255',
            'is_available' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data = [
            'name' => $validated['name'],
            'price' => $validated['price'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'],
            'is_available' => $request->boolean('is_available'),
        ];

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('menu-images', 'public');
        }

        $item->update($data);

        return back()->with('success', 'تم تحديث الصنف بنجاح');
    }

    public function deleteMenuItem(Request $request, int $id): RedirectResponse
    {
        $item = MenuItem::findOrFail($id);
        $item->delete();
        return back()->with('success', 'تم حذف الصنف بنجاح');
    }

    public function orders(Request $request): View
    {
        $query = Order::with(['restaurant', 'orderItems.menuItem']);

        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->restaurant_id) {
            $query->where('restaurant_id', $request->restaurant_id);
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->search) {
            $query->where('order_number', 'like', '%' . $request->search . '%');
        }

        $orders = $query->latest()->paginate(20);
        $statuses = OrderWorkflow::arabicLabels();

        return view('admin::orders', compact('orders', 'statuses'));
    }

    public function getOrders(Request $request)
    {
        $query = Order::with(['restaurant', 'orderItems.menuItem']);

        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->restaurant_id) {
            $query->where('restaurant_id', $request->restaurant_id);
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->search) {
            $query->where('order_number', 'like', '%' . $request->search . '%');
        }

        $orders = $query->latest()->paginate(20);
        
        return response()->json([
            'orders' => $orders->items(),
            'total' => $orders->total(),
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
        ]);
    }

    public function getOrderData(int $id)
    {
        $order = Order::with(['restaurant', 'customer', 'verifiedByAdmin', 'orderItems.menuItem'])->findOrFail($id);

        $payload = $order->toArray();
        $payload['payment_proof_url'] = $order->payment_proof
            ? Storage::disk('public')->url($order->payment_proof)
            : null;

        return response()->json($payload);
    }

    public function verifyOrderPayment(int $id): JsonResponse
    {
        /** @var SystemSettingsService $settings */
        $settings = app(SystemSettingsService::class);
        if (!(bool) $settings->get('platform', 'platform_open', true) || !(bool) $settings->get('platform', 'orders_enabled', true)) {
            return response()->json(['success' => false, 'message' => 'نظام الطلبات متوقف حالياً من إعدادات المنصة'], 503);
        }

        $admin = Auth::guard('admin')->user();
        if (!$admin instanceof Admin) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 401);
        }

        $order = Order::findOrFail($id);

        if (! OrderWorkflow::canRoleTransition(OrderWorkflow::ROLE_ADMIN, $order->status, OrderWorkflow::PAYMENT_VERIFIED)) {
            return response()->json(['success' => false, 'message' => 'لا يمكن التحقق من الدفع في هذه الحالة'], 400);
        }

        $order->update([
            'status' => OrderWorkflow::PAYMENT_VERIFIED,
            'payment_verified_at' => now(),
            'verified_by_admin_id' => $admin->id,
        ]);

        return response()->json(['success' => true, 'message' => 'تم التحقق من الدفع']);
    }

    public function rejectOrderPayment(int $id): JsonResponse
    {
        /** @var SystemSettingsService $settings */
        $settings = app(SystemSettingsService::class);
        if (!(bool) $settings->get('platform', 'platform_open', true) || !(bool) $settings->get('platform', 'orders_enabled', true)) {
            return response()->json(['success' => false, 'message' => 'نظام الطلبات متوقف حالياً من إعدادات المنصة'], 503);
        }

        $order = Order::findOrFail($id);

        if (! OrderWorkflow::canRoleTransition(OrderWorkflow::ROLE_ADMIN, $order->status, OrderWorkflow::PAYMENT_REJECTED)) {
            return response()->json(['success' => false, 'message' => 'لا يمكن الرفض في هذه الحالة'], 400);
        }

        $order->update([
            'status' => OrderWorkflow::PAYMENT_REJECTED,
        ]);

        return response()->json(['success' => true, 'message' => 'تم رفض الدفع']);
    }

    public function offers(): View
    {
        return view('admin::offers');
    }

    public function toggleRestaurant(int $id): RedirectResponse
    {
        /** @var SystemSettingsService $settings */
        $settings = app(SystemSettingsService::class);
        if (!(bool) $settings->get('platform', 'restaurants_enabled', true)) {
            return back()->with('error', 'تم تعطيل التحكم بحالة المطاعم من الإعدادات');
        }

        $restaurant = Restaurant::findOrFail($id);
        $restaurant->update(['is_open' => !$restaurant->is_open]);

        return back()->with('success', 'تم تحديث حالة المطعم');
    }

    public function toggleActive(int $id): RedirectResponse
    {
        /** @var SystemSettingsService $settings */
        $settings = app(SystemSettingsService::class);
        if (!(bool) $settings->get('platform', 'restaurants_enabled', true)) {
            return back()->with('error', 'تم تعطيل التحكم بحالة المطاعم من الإعدادات');
        }

        $restaurant = Restaurant::findOrFail($id);
        $restaurant->update(['is_active' => !$restaurant->is_active]);

        return back()->with('success', 'تم تحديث الحالة');
    }

    public function getUser(int $id)
    {
        $type = request()->query('type', 'customer');
        $account = $this->resolveAccountByType($id, $type);

        $orders = collect();
        if ($type === 'customer') {
            $orders = Order::with('orderItems')
                ->where('customer_id', $account->id)
                ->latest()
                ->limit(10)
                ->get()
                ->map(function ($order) {
                    return [
                        'order_number' => $order->order_number,
                        'status' => $order->status,
                        'created_at' => $order->created_at->format('Y-m-d H:i'),
                        'total_price' => number_format($order->total_price, 2),
                        'items_count' => $order->orderItems->count(),
                        'is_paid' => $order->is_paid ?? false,
                    ];
                });
        } elseif ($type === 'driver') {
            $orders = Order::with('orderItems')
                ->where('driver_id', $account->id)
                ->latest()
                ->limit(10)
                ->get()
                ->map(function ($order) {
                    return [
                        'order_number' => $order->order_number,
                        'status' => $order->status,
                        'created_at' => $order->created_at->format('Y-m-d H:i'),
                        'total_price' => number_format($order->total_price, 2),
                        'items_count' => $order->orderItems->count(),
                        'is_paid' => $order->is_paid ?? false,
                    ];
                });
        } elseif ($type === 'restaurant') {
            $orders = Order::with('orderItems')
                ->where('restaurant_id', $account->id)
                ->latest()
                ->limit(10)
                ->get()
                ->map(function ($order) {
                    return [
                        'order_number' => $order->order_number,
                        'status' => $order->status,
                        'created_at' => $order->created_at->format('Y-m-d H:i'),
                        'total_price' => number_format($order->total_price, 2),
                        'items_count' => $order->orderItems->count(),
                        'is_paid' => $order->is_paid ?? false,
                    ];
                });
        }

        return response()->json([
            'id' => $account->id,
            'name' => $account->name,
            'email' => $account->email,
            'created_at' => $account->created_at->format('Y-m-d'),
            'is_active' => $this->isAccountActive($account, $type),
            'account_type' => $type,
            'avatar' => $this->resolveAvatar($account, $type),
            'addresses' => [],
            'orders' => $orders,
        ]);
    }

    public function toggleUserStatus(int $id): RedirectResponse
    {
        $type = request()->input('type', request()->query('type', 'customer'));
        [$isActive] = $this->toggleAccountStatus($id, $type);

        return back()->with('success', $isActive ? 'تم تفعيل الحساب بنجاح' : 'تم حظر الحساب بنجاح');
    }

    public function toggleUserStatusApi(int $id)
    {
        $type = request()->input('type', request()->query('type', 'customer'));
        [$isActive, $message] = $this->toggleAccountStatus($id, $type);

        return response()->json([
            'success' => true,
            'message' => $message,
            'is_active' => $isActive,
        ]);
    }

    private function resolveAccountByType(int $id, string $type)
    {
        return match ($type) {
            'customer' => User::findOrFail($id),
            'driver' => Driver::findOrFail($id),
            'restaurant' => Restaurant::findOrFail($id),
            'admin' => Admin::findOrFail($id),
            default => throw new NotFoundHttpException('Unsupported account type'),
        };
    }

    private function toggleAccountStatus(int $id, string $type): array
    {
        if ($type === 'admin') {
            throw new NotFoundHttpException('Admin status cannot be toggled.');
        }

        $account = $this->resolveAccountByType($id, $type);

        if ($type === 'driver') {
            $wasActive = (bool) ($account->is_available ?? true);
            $account->is_available = !$wasActive;
            $account->save();
        } else {
            $wasActive = (bool) ($account->is_active ?? true);
            $account->is_active = !$wasActive;

            if ($type === 'customer') {
                $account->banned_at = $wasActive ? now() : null;
                $account->ban_reason = $wasActive ? 'تم الحظر بواسطة المدير' : null;
            }

            $account->save();
        }

        $isActive = !$wasActive;

        return [
            $isActive,
            $isActive ? 'تم تفعيل الحساب بنجاح' : 'تم حظر الحساب بنجاح',
        ];
    }

    private function isAccountActive(object $account, string $type): bool
    {
        return match ($type) {
            'driver' => (bool) ($account->is_available ?? true),
            'admin' => true,
            default => (bool) ($account->is_active ?? true),
        };
    }

    private function resolveAvatar(object $account, string $type): ?string
    {
        if ($type === 'restaurant' && !empty($account->image)) {
            return '/storage/' . $account->image;
        }

        if (!empty($account->profile_image)) {
            return '/storage/' . $account->profile_image;
        }

        if (!empty($account->avatar)) {
            return '/storage/' . $account->avatar;
        }

        return null;
    }

    public function toggleOpenStatus(int $id): RedirectResponse
    {
        /** @var SystemSettingsService $settings */
        $settings = app(SystemSettingsService::class);
        if (!(bool) $settings->get('platform', 'restaurants_enabled', true)) {
            return back()->with('error', 'تم تعطيل التحكم بحالة المطاعم من الإعدادات');
        }

        $restaurant = Restaurant::findOrFail($id);
        $restaurant->update(['is_open' => !$restaurant->is_open]);

        return back()->with('success', 'تم تحديث حالة الفتح');
    }
}