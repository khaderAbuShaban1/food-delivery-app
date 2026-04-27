<?php

namespace App\Modules\Restaurant\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $restaurant = session()->get('restaurant');
        
        if (!$restaurant) {
            return redirect()->route('restaurant.login');
        }
        
        if (!$restaurant || !$restaurant->id) {
            return view('restaurant::menu', [
                'restaurant' => null,
                'menuItems' => collect([]),
                'categories' => self::CATEGORIES
            ]);
        }

        $menuItems = MenuItem::where('restaurant_id', $restaurant->id)->get();

        return view('restaurant::menu', compact('restaurant', 'menuItems') + ['categories' => self::CATEGORIES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $restaurant = session()->get('restaurant');
        
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
        ]);

        return back()->with('success', 'تمت إضافة الصنف بنجاح!');
    }

    public function update(Request $request, int $restaurantId, int $menuItemId): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'category' => 'nullable|string|in:' . implode(',', array_keys(self::CATEGORIES)),
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
        ]);

        return back()->with('success', 'تم تحديث الصنف بنجاح!');
    }

    public function destroy(int $restaurantId, int $menuItemId): RedirectResponse
    {
        $menuItem = MenuItem::where('id', $menuItemId)->where('restaurant_id', $restaurantId)->first();

        if ($menuItem) {
            $menuItem->delete();
            return back()->with('success', 'تم حذف الصنف بنجاح!');
        }

        return back()->with('error', 'الصنف غير موجود');
    }
}
