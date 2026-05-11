<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FcmToken;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class FcmTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof Model) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $validated = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['nullable', 'string', Rule::in(['android', 'ios', 'web', 'macos', 'windows'])],
            'device_id' => ['nullable', 'string', 'max:255'],
        ]);

        $token = FcmToken::query()->updateOrCreate(
            ['token' => $validated['token']],
            [
                'tokenable_type' => $actor::class,
                'tokenable_id' => $actor->getKey(),
                'platform' => $validated['platform'] ?? null,
                'device_id' => $validated['device_id'] ?? null,
                'last_seen_at' => now(),
            ],
        );

        if (Schema::hasColumn($actor->getTable(), 'fcm_token')) {
            $actor->forceFill(['fcm_token' => $validated['token']])->saveQuietly();
        }

        return response()->json([
            'success' => true,
            'message' => 'FCM token registered',
            'data' => [
                'id' => $token->id,
                'platform' => $token->platform,
                'device_id' => $token->device_id,
                'last_seen_at' => $token->last_seen_at,
            ],
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $actor = $request->user();
        if (! $actor instanceof Model) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $validated = $request->validate([
            'token' => ['required', 'string', 'max:512'],
        ]);

        FcmToken::query()
            ->where('token', $validated['token'])
            ->where('tokenable_type', $actor::class)
            ->where('tokenable_id', $actor->getKey())
            ->delete();

        if (Schema::hasColumn($actor->getTable(), 'fcm_token') && ($actor->fcm_token ?? null) === $validated['token']) {
            $actor->forceFill(['fcm_token' => null])->saveQuietly();
        }

        return response()->json([
            'success' => true,
            'message' => 'FCM token removed',
        ]);
    }
}
