<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MenuController extends Controller
{
    public function index(int $restaurantId): JsonResponse
    {
        $restaurant = Restaurant::find($restaurantId);

        if (!$restaurant) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant not found',
            ], 404);
        }

        $menuItems = $restaurant->menuItems;

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
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('menu-items', 'public');
        }

        $menuItem = $restaurant->menuItems()->create([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'description' => $validated['description'] ?? null,
            'image' => $imagePath,
        ]);

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
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $imagePath = $menuItem->image;
        if ($request->hasFile('image')) {
            if ($menuItem->image) {
                Storage::disk('public')->delete($menuItem->image);
            }
            $imagePath = $request->file('image')->store('menu-items', 'public');
        }

        $menuItem->update([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'description' => $validated['description'] ?? null,
            'image' => $imagePath,
        ]);

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

        if ($menuItem->image) {
            Storage::disk('public')->delete($menuItem->image);
        }

        $menuItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Menu item deleted successfully',
        ]);
    }
}