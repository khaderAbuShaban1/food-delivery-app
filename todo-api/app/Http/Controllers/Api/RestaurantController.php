<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    public function index(): JsonResponse
    {
        $restaurants = Restaurant::with('owner')->get();

        return response()->json([
            'success' => true,
            'data' => $restaurants,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $restaurant = Restaurant::with('owner', 'menuItems')->find($id);

        if (!$restaurant) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $restaurant,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'image' => 'nullable|string',
            'is_open' => 'boolean',
        ]);

        $restaurant = $request->user()->restaurants()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Restaurant created successfully',
            'data' => $restaurant,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $restaurant = Restaurant::find($id);

        if (!$restaurant) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'category' => 'sometimes|string|max:255',
            'image' => 'nullable|string',
            'is_open' => 'boolean',
        ]);

        $restaurant->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Restaurant updated successfully',
            'data' => $restaurant,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $restaurant = Restaurant::find($id);

        if (!$restaurant) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant not found',
            ], 404);
        }

        $restaurant->delete();

        return response()->json([
            'success' => true,
            'message' => 'Restaurant deleted successfully',
        ]);
    }
}