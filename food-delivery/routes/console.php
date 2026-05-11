<?php

use Illuminate\Foundation\Inspiring;
use App\Models\Order;
use App\Services\FirebaseCloudMessagingService;
use App\Services\FirestoreSyncService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mail:doctor', function () {
    $password = (string) config('mail.mailers.smtp.password', '');
    $passwordCompact = preg_replace('/\s+/', '', $password) ?? '';
    $host = (string) config('mail.mailers.smtp.host', '');
    $port = (int) config('mail.mailers.smtp.port', 0);

    $diagnostics = [
        'mail_default' => config('mail.default'),
        'smtp_host' => $host,
        'smtp_port' => $port,
        'smtp_encryption' => config('mail.mailers.smtp.encryption'),
        'smtp_username_set' => filled(config('mail.mailers.smtp.username')),
        'smtp_password_set' => $password !== '',
        'smtp_password_length' => strlen($password),
        'smtp_password_compact_length' => strlen($passwordCompact),
        'smtp_password_has_spaces' => $password !== $passwordCompact,
        'smtp_password_app_password_shape' => strlen($passwordCompact) === 16,
        'from_address' => config('mail.from.address'),
    ];

    foreach ($diagnostics as $key => $value) {
        $this->line($key.': '.json_encode($value, JSON_UNESCAPED_SLASHES));
    }

    $connection = false;
    $errorNumber = 0;
    $errorMessage = '';
    if ($host !== '' && $port > 0) {
        $socket = @fsockopen($host, $port, $errorNumber, $errorMessage, 10);
        if (is_resource($socket)) {
            $connection = true;
            fclose($socket);
        }
    }

    $this->line('smtp_tcp_connection: '.json_encode($connection));
    if (! $connection && $errorMessage !== '') {
        $this->line('smtp_tcp_error: '.json_encode($errorMessage));
    }

    $ready = $diagnostics['mail_default'] === 'smtp'
        && $host === 'smtp.gmail.com'
        && $port === 587
        && $diagnostics['smtp_encryption'] === 'tls'
        && $diagnostics['smtp_username_set']
        && $diagnostics['smtp_password_set']
        && ! $diagnostics['smtp_password_has_spaces']
        && $connection;

    if (! $ready) {
        $this->warn('Mail is not ready. For Gmail SMTP, set MAIL_PASSWORD to a Gmail App Password, not the normal Gmail password.');
        return Command::FAILURE;
    }

    $this->info('Mail configuration is ready for a live send test.');
    return Command::SUCCESS;
})->purpose('Show SMTP mail configuration diagnostics without printing secrets');

Artisan::command('mail:test {to=khadeer1017@gmail.com}', function (string $to) {
    Log::info('mail_test_attempt', [
        'to' => $to,
        'mailer' => config('mail.default'),
        'host' => config('mail.mailers.smtp.host'),
        'port' => config('mail.mailers.smtp.port'),
        'encryption' => config('mail.mailers.smtp.encryption'),
        'username_set' => filled(config('mail.mailers.smtp.username')),
        'password_set' => filled(config('mail.mailers.smtp.password')),
        'from' => config('mail.from.address'),
    ]);

    try {
        Mail::raw('Laravel SMTP test email sent at '.now()->toDateTimeString(), function ($message) use ($to) {
            $message->to($to)->subject('Food Delivery SMTP test');
        });
    } catch (\Throwable $e) {
        Log::error('mail_test_failed', [
            'to' => $to,
            'exception' => get_class($e),
            'code' => $e->getCode(),
            'error' => $e->getMessage(),
        ]);

        $this->error('MAIL_FAILED: '.$e->getMessage());
        return Command::FAILURE;
    }

    Log::info('mail_test_sent', ['to' => $to]);
    $this->info('MAIL_SENT to '.$to);
    return Command::SUCCESS;
})->purpose('Send a live SMTP test email');

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

Artisan::command('fcm:doctor', function (FirebaseCloudMessagingService $fcm) {
    $diagnostics = $fcm->diagnostics();

    foreach ($diagnostics as $key => $value) {
        $this->line($key.': '.json_encode($value, JSON_UNESCAPED_SLASHES));
    }

    if (! $diagnostics['ready']) {
        $this->warn('FCM push is not ready. Set FCM_PUSH_ENABLED=true and configure the Firebase service account.');
        return Command::FAILURE;
    }

    $this->info('FCM push is ready.');
    return Command::SUCCESS;
})->purpose('Show Firebase Cloud Messaging configuration diagnostics');
