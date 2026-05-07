<?php

namespace App\Modules\Restaurant\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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

    public function index(Request $request): View|RedirectResponse|JsonResponse
    {
        $restaurant = Auth::guard('restaurant')->user();

        if (!$restaurant) {
            return redirect()->route('restaurant.login');
        }

        $menuItemsQuery = MenuItem::where('restaurant_id', $restaurant->id);

        $category = trim((string) $request->query('category', ''));
        $status = trim((string) $request->query('status', 'all'));
        $search = trim((string) $request->query('search', ''));

        if ($category !== '') {
            $menuItemsQuery->where('category', $category);
        }

        if ($status === 'available') {
            $menuItemsQuery->where('is_available', true);
        } elseif ($status === 'unavailable') {
            $menuItemsQuery->where('is_available', false);
        }

        if ($search !== '') {
            $menuItemsQuery->where('name', 'like', '%' . $search . '%');
        }

        if (Schema::hasTable('menu_item_option_groups') && Schema::hasTable('menu_item_option_values')) {
            $menuItemsQuery->with('optionGroups.values');
        }

        $menuItems = $menuItemsQuery->latest()->get();
        $menuOptionsMap = $this->buildOptionsMap($menuItems);
        $filters = [
            'category' => $category,
            'status' => in_array($status, ['all', 'available', 'unavailable'], true) ? $status : 'all',
            'search' => $search,
        ];

        if ($request->ajax() || Str::contains((string) $request->header('Accept'), 'application/json')) {
            $html = view('restaurant::partials.menu-items-grid', [
                'menuItems' => $menuItems,
                'restaurant' => $restaurant,
                'categories' => self::CATEGORIES,
            ])->render();

            return response()->json([
                'html' => $html,
                'count' => $menuItems->count(),
                'menu_options_map' => $menuOptionsMap,
            ]);
        }

        return view('restaurant::menu', compact('restaurant', 'menuItems', 'menuOptionsMap', 'filters') + ['categories' => self::CATEGORIES]);
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
            'options' => 'nullable|array',
            'options.*.name' => 'nullable|string|max:100',
            'options.*.selection_type' => 'nullable|in:single,multiple',
            'options.*.values' => 'nullable|array',
            'options.*.values.*.name' => 'nullable|string|max:100',
            'options.*.values.*.extra_price' => 'nullable|numeric|min:0',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('menu-images', 'public');
        }
        
        DB::transaction(function () use ($request, $restaurant, $imagePath): void {
            $menuItem = MenuItem::create([
                'restaurant_id' => $restaurant->id,
                'name' => $request->name,
                'price' => $request->price,
                'description' => $request->description ?? '',
                'image' => $imagePath,
                'category' => $request->category ?? null,
                'is_available' => true,
            ]);

            if (Schema::hasTable('menu_item_option_groups') && Schema::hasTable('menu_item_option_values')) {
                $this->syncMenuItemOptions($menuItem, $request->input('options', []));
            }
        });

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
            'options' => 'nullable|array',
            'options.*.name' => 'nullable|string|max:100',
            'options.*.selection_type' => 'nullable|in:single,multiple',
            'options.*.values' => 'nullable|array',
            'options.*.values.*.name' => 'nullable|string|max:100',
            'options.*.values.*.extra_price' => 'nullable|numeric|min:0',
        ]);

        $menuItem = MenuItem::where('id', $menuItemId)->where('restaurant_id', $restaurantId)->first();
        
        if (!$menuItem) {
            return back()->with('error', 'الصنف غير موجود');
        }

        DB::transaction(function () use ($request, $menuItem): void {
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

            if (Schema::hasTable('menu_item_option_groups') && Schema::hasTable('menu_item_option_values')) {
                $this->syncMenuItemOptions($menuItem, $request->input('options', []));
            }
        });

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

    private function syncMenuItemOptions(MenuItem $menuItem, array $rawOptions): void
    {
        $menuItem->optionGroups()->delete();

        foreach (array_values($rawOptions) as $groupIndex => $group) {
            $groupName = trim((string) ($group['name'] ?? ''));
            if ($groupName === '') {
                continue;
            }

            $selectionType = in_array(($group['selection_type'] ?? 'single'), ['single', 'multiple'], true)
                ? $group['selection_type']
                : 'single';

            $values = is_array($group['values'] ?? null) ? $group['values'] : [];
            $normalizedValues = [];
            foreach (array_values($values) as $valueIndex => $value) {
                $valueName = trim((string) ($value['name'] ?? ''));
                if ($valueName === '') {
                    continue;
                }

                $normalizedValues[] = [
                    'name' => $valueName,
                    'extra_price' => max(0, (float) ($value['extra_price'] ?? 0)),
                    'sort_order' => $valueIndex,
                ];
            }

            if (count($normalizedValues) === 0) {
                continue;
            }

            $optionGroup = $menuItem->optionGroups()->create([
                'name' => $groupName,
                'selection_type' => $selectionType,
                'sort_order' => $groupIndex,
            ]);

            $optionGroup->values()->createMany($normalizedValues);
        }
    }

    private function buildOptionsMap($menuItems): array
    {
        return collect($menuItems)->mapWithKeys(function ($item) {
            return [
                $item->id => collect($item->optionGroups ?? [])->map(function ($group) {
                    return [
                        'name' => $group->name,
                        'selection_type' => $group->selection_type,
                        'values' => collect($group->values ?? [])->map(function ($value) {
                            return [
                                'name' => $value->name,
                                'extra_price' => (float) $value->extra_price,
                            ];
                        })->values()->all(),
                    ];
                })->values()->all(),
            ];
        })->all();
    }
}
