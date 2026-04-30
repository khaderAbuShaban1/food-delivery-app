<?php

namespace App\Modules\Restaurant\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MenuController extends Controller
{
    public const CATEGORIES = [
        'Pizza' => 'بيتزا',
        'Burgers' => 'برجر',
        'Drinks' => 'مشروبات',
        'Desserts' => 'حلويات',
        'Fast Food' => 'وجبات سريعة',
        'Sandwiches' => 'ساندويتشات',
        'Salad' => 'سلطة',
        'Seafood' => 'مأكولات بحرية',
        'Breakfast' => 'فطور',
        'Other' => 'أخرى',
    ];

    public function index(): View|RedirectResponse
    {
        $restaurant = Auth::guard('restaurant')->user();

        if (!$restaurant) {
            return redirect()->route('restaurant.login');
        }

        $menuItems = MenuItem::where('restaurant_id', $restaurant->id)
            ->latest()
            ->get();

        return view('restaurant::menu', compact('restaurant', 'menuItems') + ['categories' => self::CATEGORIES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $restaurant = Auth::guard('restaurant')->user();
        
        if (!$restaurant || !$restaurant->id) {
            return back()->with('error', 'لم يتم العثور على مطعم');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'category' => 'nullable|string|in:' . implode(',', array_keys(self::CATEGORIES)),
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('menu-images', 'public');
        }
        
        MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => $request->name,
            'price' => $request->price,
            'description' => $request->description ?? '',
            'image' => $imagePath,
            'category' => $request->category ?? null,
            'is_available' => true,
        ]);

        return back()->with('success', 'تمت إضافة الصنف بنجاح!');
    }

    public function update(Request $request, int $restaurantId, int $menuItemId): RedirectResponse
    {
        $restaurant = Auth::guard('restaurant')->user();
        if (!$restaurant || (int) $restaurant->id !== $restaurantId) {
            return back()->with('error', 'غير مصرح بتعديل هذا الصنف');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'category' => 'nullable|string|in:' . implode(',', array_keys(self::CATEGORIES)),
            'is_available' => 'nullable|boolean',
        ]);

        $menuItem = MenuItem::where('id', $menuItemId)->where('restaurant_id', $restaurantId)->first();
        
        if (!$menuItem) {
            return back()->with('error', 'الصنف غير موجود');
        }

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('menu-images', 'public');
            $menuItem->update(['image' => $imagePath]);
        }
        
        $menuItem->update([
            'name' => $request->name,
            'price' => $request->price,
            'description' => $request->description ?? '',
            'category' => $request->category ?? null,
            'is_available' => $request->boolean('is_available'),
        ]);

        return back()->with('success', 'تم تحديث الصنف بنجاح!');
    }

    public function destroy(int $restaurantId, int $menuItemId): RedirectResponse
    {
        $restaurant = Auth::guard('restaurant')->user();
        if (!$restaurant || (int) $restaurant->id !== $restaurantId) {
            return back()->with('error', 'غير مصرح بحذف هذا الصنف');
        }

        $menuItem = MenuItem::where('id', $menuItemId)->where('restaurant_id', $restaurantId)->first();

        if ($menuItem) {
            if ($menuItem->hasActiveOrders()) {
                $menuItem->update(['is_available' => false]);
                return back()->with('warning', 'هذا الصنف مرتبط بطلبات نشطة لذا تم تعطيله بدلاً من حذفه');
            }
            
            $menuItem->delete();
            return back()->with('success', 'تم حذف الصنف بنجاح!');
        }

        return back()->with('error', 'الصنف غير موجود');
    }

    public function toggleAvailability(Request $request, int $menuItemId): JsonResponse
    {
        $restaurant = Auth::guard('restaurant')->user();
        if (!$restaurant) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $menuItem = MenuItem::where('id', $menuItemId)
            ->where('restaurant_id', $restaurant->id)
            ->first();

        if (!$menuItem) {
            return response()->json(['success' => false, 'message' => 'الصنف غير موجود'], 404);
        }

        $menuItem->is_available = !$menuItem->is_available;
        $menuItem->save();

        return response()->json([
            'success' => true,
            'message' => $menuItem->is_available ? 'تم تفعيل الصنف' : 'تم إيقاف الصنف',
            'is_available' => (bool) $menuItem->is_available,
        ]);
    }
}
