<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = Address::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $addresses,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:120',
            'city' => 'required|string|max:120',
            'street' => 'required|string|max:255',
            'details' => 'nullable|string|max:1000',
            'is_default' => 'sometimes|boolean',
        ], [
            'title.required' => 'عنوان المكان مطلوب',
            'city.required' => 'المدينة مطلوبة',
            'street.required' => 'الشارع مطلوب',
        ]);

        $userId = $request->user()->id;
        $setAsDefault = (bool) ($validated['is_default'] ?? false);

        if ($setAsDefault) {
            Address::query()
                ->where('user_id', $userId)
                ->update(['is_default' => false]);
        } else {
            $hasDefault = Address::query()
                ->where('user_id', $userId)
                ->where('is_default', true)
                ->exists();
            $setAsDefault = !$hasDefault;
        }

        $address = Address::create([
            'user_id' => $userId,
            'title' => $validated['title'],
            'city' => $validated['city'],
            'street' => $validated['street'],
            'details' => $validated['details'] ?? null,
            'is_default' => $setAsDefault,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تمت إضافة العنوان بنجاح',
            'data' => $address,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $address = Address::query()
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'العنوان غير موجود',
            ], 404);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:120',
            'city' => 'required|string|max:120',
            'street' => 'required|string|max:255',
            'details' => 'nullable|string|max:1000',
            'is_default' => 'sometimes|boolean',
        ], [
            'title.required' => 'عنوان المكان مطلوب',
            'city.required' => 'المدينة مطلوبة',
            'street.required' => 'الشارع مطلوب',
        ]);

        $setAsDefault = (bool) ($validated['is_default'] ?? $address->is_default);
        if ($setAsDefault) {
            Address::query()
                ->where('user_id', $request->user()->id)
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }

        $address->update([
            'title' => $validated['title'],
            'city' => $validated['city'],
            'street' => $validated['street'],
            'details' => $validated['details'] ?? null,
            'is_default' => $setAsDefault,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث العنوان بنجاح',
            'data' => $address->fresh(),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $address = Address::query()
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'العنوان غير موجود',
            ], 404);
        }

        $wasDefault = (bool) $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $next = Address::query()
                ->where('user_id', $request->user()->id)
                ->latest('updated_at')
                ->first();
            if ($next) {
                $next->update(['is_default' => true]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حذف العنوان بنجاح',
        ]);
    }
}
