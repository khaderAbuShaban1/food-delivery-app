<?php

use Illuminate\Foundation\Inspiring;
use App\Models\Order;
use App\Services\FirestoreSyncService;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('firestore:doctor', function (FirestoreSyncService $firestore) {
    $diagnostics = $firestore->diagnostics();

    foreach ($diagnostics as $key => $value) {
        $this->line($key.': '.json_encode($value, JSON_UNESCAPED_SLASHES));
    }

    if (! $diagnostics['ready']) {
        $this->warn('Firestore sync is not ready. Set FIRESTORE_SYNC_ENABLED=true and configure a Firebase service account.');
        return Command::FAILURE;
    }

    $this->info('Firestore sync is ready.');
    return Command::SUCCESS;
})->purpose('Show Firestore sync configuration diagnostics');

Artisan::command('firestore:sync-orders {--id=} {--dry-run}', function (FirestoreSyncService $firestore) {
    $diagnostics = $firestore->diagnostics();
    if (! $diagnostics['ready'] && ! $this->option('dry-run')) {
        $this->warn('Firestore sync is not ready; no writes were attempted.');
        $this->line(json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return Command::FAILURE;
    }

    if (! $diagnostics['ready']) {
        $this->warn('Firestore sync is not ready; running dry-run payload inspection only.');
        $this->line(json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    $query = Order::query()->orderBy('id');
    if ($this->option('id')) {
        $query->whereKey((int) $this->option('id'));
    }

    $total = 0;
    $ok = 0;
    $failed = 0;

    $query->chunkById(100, function ($orders) use ($firestore, &$total, &$ok, &$failed) {
        foreach ($orders as $order) {
            $total++;
            $payload = $firestore->payloadForModel($order);
            $this->line('orders/'.$order->id.' '.json_encode($payload, JSON_UNESCAPED_SLASHES));

            if ($this->option('dry-run')) {
                continue;
            }

            if ($firestore->sync(Order::class, $payload)) {
                $ok++;
            } else {
                $failed++;
            }
        }
    });

    $this->info("Orders inspected: {$total}; synced: {$ok}; failed: {$failed}.");

    return $failed === 0 ? Command::SUCCESS : Command::FAILURE;
})->purpose('Backfill real MySQL orders into the Firestore realtime mirror');

Artisan::command('firestore:show-order {id}', function (FirestoreSyncService $firestore, int $id) {
    $document = $firestore->fetchDocument(Order::class, $id);

    if ($document === null) {
        $this->warn("Firestore order {$id} was not found or could not be fetched.");
        return Command::FAILURE;
    }

    $this->line(json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return Command::SUCCESS;
})->purpose('Fetch a mirrored Firestore order document by Laravel order id');
