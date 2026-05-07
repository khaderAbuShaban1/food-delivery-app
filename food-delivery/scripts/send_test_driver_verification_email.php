<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$to = $argv[1] ?? null;
if (!is_string($to) || trim($to) === '') {
    fwrite(STDERR, "Usage: php scripts/send_test_driver_verification_email.php you@example.com\n");
    exit(2);
}

$to = trim($to);
$expiresAt = Illuminate\Support\Carbon::now()->addMinutes(10);
$mail = new App\Mail\DriverEmailVerificationCodeMail('123456', $expiresAt, 'Test Driver');

try {
    Illuminate\Support\Facades\Mail::to($to)->send($mail);
    fwrite(STDOUT, "SENT\n");
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "FAILED: " . $e->getMessage() . "\n");
    exit(1);
}

