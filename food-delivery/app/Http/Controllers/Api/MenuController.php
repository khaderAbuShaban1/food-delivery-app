<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class MenuController extends Controller
{
    private function getFullImageUrl($image): string
    {
        if (empty($image)) {
            return '';
        }
        
        if (str_starts_with($image, 'http')) {
            return $image;
        }

        $path = ltrim($image, '/');
        $baseUrl = request()->getSchemeAndHttpHost();
        if (str_starts_with($path, 'storage/')) {
            return $baseUrl . '/' . $path;
        }

        return $baseUrl . '/storage/' . $path;
    }

    public function index(int $restaurantId): JsonResponse
    {
        $restaurant = Restaurant::find($restaurantId);

        if (!$restaurant) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant not found',
            ], 404);
        }

        $menuItems = $restaurant->menuItems->map(function ($item) {
            return [
                'id' => $item->id,
                'restaurant_id' => $item->restaurant_id,
                'name' => $item->name,
                'description' => $item->description,
                'price' => (float) $item->price,
                'category' => $item->category ?? 'أخرى',
                'image' => $this->getFullImageUrl($item->image),
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $menuItems,
        ]);
    }

    public function publicIndex(int $restaurantId): JsonResponse
    {
        $restaurant = Restaurant::find($restaurantId);

        if (!$restaurant) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant not found',
            ], 404);
        }

        $query = $restaurant->menuItems();
        if (Schema::hasTable('menu_item_option_groups') && Schema::hasTable('menu_item_option_values')) {
            $query->with(['optionGroups.values']);
        }

        $menuItems = $query->get()->map(function ($item) {
            $optionGroups = [];
            if (Schema::hasTable('menu_item_option_groups') && Schema::hasTable('menu_item_option_values')) {
                $optionGroups = $item->optionGroups->map(function ($g) {
                    return [
                        'id' => $g->id,
                        'name' => $g->name,
                        'selection_type' => $g->selection_type,
                        'sort_order' => $g->sort_order,
                        'values' => $g->values->map(function ($v) {
                            return [
                                'id' => $v->id,
                                'name' => $v->name,
                                'extra_price' => (float) ($v->extra_price ?? 0),
                                'sort_order' => $v->sort_order,
                            ];
                        })->values()->all(),
                    ];
                })->values()->all();
            }
            return [
                'id' => $item->id,
                'restaurant_id' => $item->restaurant_id,
                'name' => $item->name,
                'description' => $item->description,
                'price' => (float) $item->price,
                'category' => $item->category ?? 'أخرى',
                'image' => $this->getFullImageUrl($item->image),
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'option_groups' => $optionGroups,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $menuItems,
        ]);
    }

    public function store(Request $request, int $restaurantId): JsonResponse
    {
        $restaurant = Restaurant::find($restaurantId);
        if (!$restaurant) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'category' => 'nullable|string',
            'image' => 'nullable|string',
        ]);

        $menuItem = $restaurant->menuItems()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Menu item created successfully',
            'data' => $menuItem,
        ], 201);
    }

    public function update(Request $request, int $restaurantId, int $menuItemId): JsonResponse
    {
        $menuItem = MenuItem::where('restaurant_id', $restaurantId)->find($menuItemId);

        if (!$menuItem) {
            return response()->json([
                'success' => false,
                'message' => 'Menu item not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'description' => 'nullable|string',
            'category' => 'nullable|string',
            'image' => 'nullable|string',
        ]);

        $menuItem->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Menu item updated successfully',
            'data' => $menuItem->fresh(),
        ]);
    }

    public function destroy(int $restaurantId, int $menuItemId): JsonResponse
    {
        $menuItem = MenuItem::where('restaurant_id', $restaurantId)->find($menuItemId);

        if (!$menuItem) {
            return response()->json([
                'success' => false,
                'message' => 'Menu item not found',
            ], 404);
        }

        $menuItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Menu item deleted successfully',
        ]);
    }
}