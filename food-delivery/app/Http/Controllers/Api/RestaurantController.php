<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\RestaurantRating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class RestaurantController extends Controller
{
    private function ratingsTableReady(): bool
    {
        return Schema::hasTable('restaurant_ratings');
    }

    private function restaurantQueryWithStats(?int $customerId = null)
    {
        $query = Restaurant::query()
            ->withAvg('menuItems', 'price');

        if (!$this->ratingsTableReady()) {
            return $query;
        }

        return $query
            ->withCount('ratings')
            ->withAvg('ratings', 'rating')
            ->with(['ratings' => function ($q) use ($customerId) {
                if ($customerId !== null) {
                    $q->where('customer_id', $customerId);
                }
            }]);
    }

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

    private function toRestaurantPayload(Restaurant $restaurant, ?int $currentCustomerId = null): array
    {
        $avgRating = $restaurant->ratings_avg_rating !== null
            ? round((float) $restaurant->ratings_avg_rating, 1)
            : 0.0;

        $myRating = null;
        if ($currentCustomerId !== null) {
            $myRating = $restaurant->relationLoaded('ratings')
                ? $restaurant->ratings->firstWhere('customer_id', $currentCustomerId)?->rating
                : null;
        }

        return [
            'id' => $restaurant->id,
            'name' => $restaurant->name,
            'email' => $restaurant->email,
            'phone' => $restaurant->phone,
            'category' => $restaurant->category,
            'image' => $this->getFullImageUrl($restaurant->image),
            'is_open' => (bool) $restaurant->is_open,
            'rating' => $avgRating,
            'ratings_count' => (int) ($restaurant->ratings_count ?? 0),
            'my_rating' => $myRating,
            'avg_price' => $restaurant->menu_items_avg_price !== null
                ? (float) $restaurant->menu_items_avg_price
                : null,
            'created_at' => $restaurant->created_at,
            'updated_at' => $restaurant->updated_at,
        ];
    }

    public function index(): JsonResponse
    {
        try {
            $customerId = request()->user()?->id;
            $restaurants = $this->restaurantQueryWithStats($customerId)
                ->get()
                ->map(fn (Restaurant $restaurant) => $this->toRestaurantPayload($restaurant, $customerId));

            return response()->json([
                'success' => true,
                'data' => $restaurants,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        $customerId = request()->user()?->id;
        $restaurant = $this->restaurantQueryWithStats($customerId)->find($id);

        if (!$restaurant) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->toRestaurantPayload($restaurant, $customerId),
        ]);
    }

    public function rate(Request $request, int $id): JsonResponse
    {
        $customer = $request->user();
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $restaurant = Restaurant::find($id);
        if (!$restaurant) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant not found',
            ], 404);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
        ]);

        if (!$this->ratingsTableReady()) {
            return response()->json([
                'success' => false,
                'message' => 'Ratings are not ready yet. Please run migrations.',
            ], 503);
        }

        RestaurantRating::updateOrCreate(
            [
                'restaurant_id' => $restaurant->id,
                'customer_id' => $customer->id,
            ],
            [
                'rating' => $validated['rating'],
            ]
        );

        $restaurant = $this->restaurantQueryWithStats($customer->id)->find($id);

        return response()->json([
            'success' => true,
            'message' => 'Rating saved successfully',
            'data' => $this->toRestaurantPayload($restaurant, $customer->id),
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