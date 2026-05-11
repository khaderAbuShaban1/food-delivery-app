<?php

namespace App\Services;

use App\Models\FcmToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FirebaseCloudMessagingService
{
    private const TOKEN_CACHE_KEY = 'fcm_access_token';

    /**
     * @param array<int, string> $tokens
     * @param array<string, string> $data
     * @param array<string, mixed> $androidNotification
     * @return array{attempted:int,sent:int,failed:int}
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = [], array $androidNotification = []): array
    {
        $tokens = array_values(array_unique(array_filter($tokens)));

        if ($tokens === []) {
            Log::debug('FCM notification skipped: no recipient tokens.');
            return ['attempted' => 0, 'sent' => 0, 'failed' => 0];
        }

        if (! $this->isReady()) {
            Log::warning('FCM notification skipped: service is not ready.', [
                'diagnostics' => $this->diagnostics(),
            ]);
            return ['attempted' => count($tokens), 'sent' => 0, 'failed' => count($tokens)];
        }

        $accessToken = $this->accessToken();
        if ($accessToken === null) {
            return ['attempted' => count($tokens), 'sent' => 0, 'failed' => count($tokens)];
        }

        $sent = 0;
        $failed = 0;

        foreach ($tokens as $token) {
            $ok = $this->sendSingle($accessToken, $token, $title, $body, $data, $androidNotification);
            $ok ? $sent++ : $failed++;
        }

        Log::info('FCM notification batch finished.', [
            'attempted' => count($tokens),
            'sent' => $sent,
            'failed' => $failed,
        ]);

        return ['attempted' => count($tokens), 'sent' => $sent, 'failed' => $failed];
    }

    /** @return array<string, mixed> */
    public function diagnostics(): array
    {
        $account = $this->serviceAccount();
        $path = $this->resolvedCredentialsPath();

        return [
            'enabled' => (bool) config('services.fcm.enabled'),
            'project_id' => config('services.fcm.project_id'),
            'credentials_path' => $path,
            'credentials_path_exists' => is_string($path) && $path !== '' && is_file($path),
            'has_client_email' => filled($account['client_email'] ?? null),
            'has_private_key' => filled($account['private_key'] ?? null),
            'ready' => $this->isReady(),
        ];
    }

    /**
     * @param array<string, string> $data
     * @param array<string, mixed> $androidNotification
     */
    private function sendSingle(string $accessToken, string $token, string $title, string $body, array $data, array $androidNotification): bool
    {
        $url = sprintf(
            'https://fcm.googleapis.com/v1/projects/%s/messages:send',
            rawurlencode((string) config('services.fcm.project_id')),
        );

        try {
            $message = [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $this->stringData($data),
                'android' => [
                    'priority' => 'HIGH',
                ],
                'apns' => [
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                ],
            ];

            $androidNotification = collect($androidNotification)
                ->reject(fn (mixed $value) => $value === null)
                ->all();

            if ($androidNotification !== []) {
                $message['android']['notification'] = $androidNotification;
            }

            Log::debug('FCM notification payload prepared.', [
                'token_hash' => sha1($token),
                'event' => $data['event'] ?? null,
                'order_id' => $data['order_id'] ?? null,
                'android_priority' => $message['android']['priority'],
                'android_notification' => $androidNotification,
            ]);

            $response = Http::withToken($accessToken)
                ->timeout((int) config('services.fcm.timeout', 10))
                ->post($url, [
                    'message' => $message,
                ]);

            if ($response->successful()) {
                Log::info('FCM notification sent.', [
                    'token_hash' => sha1($token),
                    'message' => $response->json('name'),
                ]);
                return true;
            }

            Log::warning('FCM notification failed.', [
                'token_hash' => sha1($token),
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if (in_array($response->status(), [400, 404], true)) {
                FcmToken::query()->where('token', $token)->delete();
            }

            return false;
        } catch (Throwable $e) {
            Log::error('FCM notification threw exception.', [
                'token_hash' => sha1($token),
                'message' => $e->getMessage(),
            ]);
            report($e);
            return false;
        }
    }

    private function isReady(): bool
    {
        return (bool) config('services.fcm.enabled')
            && filled(config('services.fcm.project_id'))
            && is_string($this->resolvedCredentialsPath())
            && $this->resolvedCredentialsPath() !== ''
            && filled($this->serviceAccount()['client_email'] ?? null)
            && filled($this->serviceAccount()['private_key'] ?? null);
    }

    /** @return array<string, mixed> */
    private function serviceAccount(): array
    {
        $path = $this->resolvedCredentialsPath();
        if (is_string($path) && $path !== '' && is_file($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    private function resolvedCredentialsPath(): ?string
    {
        $path = config('services.fcm.credentials_path') ?: config('services.firestore.credentials_path');
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $trimmed = trim($path);
        if (is_file($trimmed)) {
            return $trimmed;
        }

        $absolute = base_path($trimmed);

        return is_file($absolute) ? $absolute : $trimmed;
    }

    private function accessToken(): ?string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, now()->addMinutes(50), function () {
            $account = $this->serviceAccount();
            $now = time();
            $claims = [
                'iss' => $account['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ];

            $jwt = $this->jwt($claims, (string) $account['private_key']);
            if ($jwt === null) {
                return null;
            }

            $response = Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->failed()) {
                Log::warning('FCM token request failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            return $response->json('access_token');
        });
    }

    /** @param array<string, mixed> $claims */
    private function jwt(array $claims, string $privateKey): ?string
    {
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $segments = [
            $this->base64Url(json_encode($header, JSON_THROW_ON_ERROR)),
            $this->base64Url(json_encode($claims, JSON_THROW_ON_ERROR)),
        ];
        $signingInput = implode('.', $segments);

        $signature = '';
        if (! openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            Log::warning('FCM JWT signing failed.');
            return null;
        }

        $segments[] = $this->base64Url($signature);

        return implode('.', $segments);
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /** @param array<string, mixed> $data */
    private function stringData(array $data): array
    {
        return collect($data)
            ->mapWithKeys(fn (mixed $value, string $key) => [$key => (string) $value])
            ->all();
    }
}
