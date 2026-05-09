<?php

namespace App\Observers;

use App\Services\FirestoreSyncService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class FirestoreMirrorObserver
{
    /** @var array<class-string, string[]> */
    private array $watched = [
        \App\Models\Order::class => ['status', 'customer_id', 'user_id', 'driver_id', 'restaurant_id', 'total_price'],
        \App\Models\Restaurant::class => ['name', 'is_open', 'is_active'],
        \App\Models\Address::class => ['details', 'city', 'street', 'user_id'],
        \App\Models\RestaurantRating::class => ['rating', 'order_id'],
        \App\Models\User::class => ['name', 'banned_at', 'is_active'],
        \App\Models\Driver::class => ['name', 'approval_status', 'is_available'],
    ];

    public function saved(Model $model): void
    {
        if (! $this->shouldSync($model)) {
            Log::debug('Firestore observer skipped model save.', [
                'model' => $model::class,
                'id' => $model->getKey(),
                'changes' => array_keys($model->getChanges()),
            ]);
            return;
        }

        Log::info('Firestore observer detected model save.', [
            'model' => $model::class,
            'id' => $model->getKey(),
            'created' => $model->wasRecentlyCreated,
            'changes' => array_keys($model->getChanges()),
        ]);

        app(FirestoreSyncService::class)->syncAfterCommit($model::class, $model->fresh() ?? $model);
    }

    public function deleted(Model $model): void
    {
        Log::info('Firestore observer detected model delete.', [
            'model' => $model::class,
            'id' => $model->getKey(),
        ]);

        app(FirestoreSyncService::class)->deleteAfterCommit($model::class, $model->getKey());
    }

    private function shouldSync(Model $model): bool
    {
        if ($model->wasRecentlyCreated) {
            return true;
        }

        $watched = $this->watched[$model::class] ?? [];
        if ($watched === []) {
            return false;
        }

        $changes = array_keys($model->getChanges());

        return count(array_intersect($changes, $watched)) > 0;
    }
}
