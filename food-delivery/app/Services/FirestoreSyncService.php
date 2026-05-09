<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\RestaurantRating;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class FirestoreSyncService
{
    private const TOKEN_CACHE_KEY = 'firestore_sync_access_token';

    /** @return array<string, mixed> */
    public function diagnostics(): array
    {
        $account = $this->serviceAccount();
        $credentialsPath = $this->resolvedCredentialsPath();

        return [
            'enabled' => (bool) config('services.firestore.enabled'),
            'project_id' => config('services.firestore.project_id'),
            'database' => config('services.firestore.database', '(default)'),
            'credentials_path' => $credentialsPath,
            'credentials_path_exists' => is_string($credentialsPath) && $credentialsPath !== '' && is_file($credentialsPath),
            'has_client_email' => filled($account['client_email'] ?? null),
            'has_private_key' => filled($account['private_key'] ?? null),
            'credentials_project_id' => $account['project_id'] ?? null,
            'ready' => $this->isEnabled(),
        ];
    }

    /**
     * Queue a sync after the current MySQL transaction commits. Firestore is a mirror only;
     * failed mirror writes are logged and must never roll back source-of-truth data.
     */
    public function syncAfterCommit(string $modelType, Model|array $source): void
    {
        $payload = $source instanceof Model
            ? $this->payloadForModel($source)
            : $source;

        if ($payload === null) {
            Log::debug('Firestore sync skipped: no payload for model.', [
                'model_type' => $modelType,
            ]);
            return;
        }

        $callback = fn () => $this->sync($modelType, $payload);

        if (DB::connection()->transactionLevel() > 0) {
            Log::debug('Firestore sync queued until database commit.', [
                'model_type' => $modelType,
                'id' => $payload['id'] ?? null,
            ]);
            DB::afterCommit($callback);
            return;
        }

        $callback();
    }

    /**
     * Sync a minimal document to the matching Firestore mirror collection.
     *
     * @param array<string, mixed> $data
     */
    public function sync(string $modelType, array $data): bool
    {
        Log::info('Firestore sync attempt started.', [
            'model_type' => $modelType,
            'collection' => $this->collectionFor($modelType),
            'document' => $data['id'] ?? null,
            'enabled' => (bool) config('services.firestore.enabled'),
        ]);

        $diagnostics = $this->diagnostics();
        if (! $diagnostics['ready']) {
            Log::warning('Firestore sync skipped: service is not ready.', [
                'model_type' => $modelType,
                'document' => $data['id'] ?? null,
                'diagnostics' => $diagnostics,
            ]);
            return false;
        }

        $collection = $this->collectionFor($modelType);
        $id = (string) ($data['id'] ?? '');

        if ($collection === null || $id === '') {
            Log::warning('Firestore sync skipped: missing collection or id.', [
                'model_type' => $modelType,
                'id' => $id,
            ]);
            return false;
        }

        try {
            $token = $this->accessToken();
            if ($token === null) {
                return false;
            }

            $url = sprintf(
                'https://firestore.googleapis.com/v1/projects/%s/databases/%s/documents/%s/%s',
                rawurlencode((string) config('services.firestore.project_id')),
                rawurlencode((string) config('services.firestore.database', '(default)')),
                rawurlencode($collection),
                rawurlencode($id),
            );

            $response = Http::withToken($token)
                ->timeout((int) config('services.firestore.timeout', 5))
                ->patch($url, ['fields' => $this->encodeFields($data)]);

            if ($response->failed()) {
                Log::warning('Firestore sync failed.', [
                    'collection' => $collection,
                    'document' => $id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            Log::info('Firestore sync succeeded.', [
                'collection' => $collection,
                'document' => $id,
                'payload_keys' => array_keys($data),
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error('Firestore sync threw exception.', [
                'model_type' => $modelType,
                'document' => $data['id'] ?? null,
                'message' => $e->getMessage(),
            ]);
            report($e);
            return false;
        }
    }

    public function deleteAfterCommit(string $modelType, string|int $id): void
    {
        $callback = fn () => $this->delete($modelType, $id);

        if (DB::connection()->transactionLevel() > 0) {
            DB::afterCommit($callback);
            return;
        }

        $callback();
    }

    /** @return array<string, mixed>|null */
    public function fetchDocument(string $modelType, string|int $id): ?array
    {
        $diagnostics = $this->diagnostics();
        if (! $diagnostics['ready']) {
            Log::warning('Firestore fetch skipped: service is not ready.', [
                'model_type' => $modelType,
                'document' => $id,
                'diagnostics' => $diagnostics,
            ]);
            return null;
        }

        $collection = $this->collectionFor($modelType);
        if ($collection === null) {
            return null;
        }

        $token = $this->accessToken();
        if ($token === null) {
            return null;
        }

        $url = sprintf(
            'https://firestore.googleapis.com/v1/projects/%s/databases/%s/documents/%s/%s',
            rawurlencode((string) config('services.firestore.project_id')),
            rawurlencode((string) config('services.firestore.database', '(default)')),
            rawurlencode($collection),
            rawurlencode((string) $id),
        );

        try {
            $response = Http::withToken($token)
                ->timeout((int) config('services.firestore.timeout', 5))
                ->get($url);

            if ($response->failed()) {
                Log::warning('Firestore fetch failed.', [
                    'collection' => $collection,
                    'document' => $id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            return $response->json();
        } catch (Throwable $e) {
            Log::error('Firestore fetch threw exception.', [
                'model_type' => $modelType,
                'document' => $id,
                'message' => $e->getMessage(),
            ]);
            report($e);
            return null;
        }
    }

    public function delete(string $modelType, string|int $id): bool
    {
        $diagnostics = $this->diagnostics();
        if (! $diagnostics['ready']) {
            Log::warning('Firestore delete skipped: service is not ready.', [
                'model_type' => $modelType,
                'document' => $id,
                'diagnostics' => $diagnostics,
            ]);
            return false;
        }

        $collection = $this->collectionFor($modelType);
        if ($collection === null) {
            return false;
        }

        try {
            $token = $this->accessToken();
            if ($token === null) {
                return false;
            }

            $url = sprintf(
                'https://firestore.googleapis.com/v1/projects/%s/databases/%s/documents/%s/%s',
                rawurlencode((string) config('services.firestore.project_id')),
                rawurlencode((string) config('services.firestore.database', '(default)')),
                rawurlencode($collection),
                rawurlencode((string) $id),
            );

            $response = Http::withToken($token)
                ->timeout((int) config('services.firestore.timeout', 5))
                ->delete($url);

            if ($response->failed() && $response->status() !== 404) {
                Log::warning('Firestore delete failed.', [
                    'collection' => $collection,
                    'document' => $id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            Log::info('Firestore delete succeeded.', [
                'collection' => $collection,
                'document' => $id,
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error('Firestore delete threw exception.', [
                'model_type' => $modelType,
                'document' => $id,
                'message' => $e->getMessage(),
            ]);
            report($e);
            return false;
        }
    }

    public function payloadForModel(Model $model): ?array
    {
        return match (true) {
            $model instanceof Order => $this->orderPayload($model),
            $model instanceof User => $this->userPayload($model),
            $model instanceof Driver => $this->driverPayload($model),
            $model instanceof Restaurant => $this->restaurantPayload($model),
            $model instanceof Address => $this->addressPayload($model),
            $model instanceof RestaurantRating => $this->ratingPayload($model),
            default => null,
        };
    }

    private function collectionFor(string $modelType): ?string
    {
        $normalized = Str::of($modelType)->classBasename()->lower()->toString();

        return match ($normalized) {
            'order' => 'orders',
            'user', 'driver' => 'users',
            'restaurant' => 'restaurants',
            'address' => 'addresses',
            'restaurantrating', 'rating' => 'ratings',
            default => null,
        };
    }

    private function orderPayload(Order $order): array
    {
        return [
            'id' => (int) $order->id,
            'status' => (string) $order->status,
            'customer_id' => $order->customer_id !== null ? (int) $order->customer_id : ($order->user_id !== null ? (int) $order->user_id : null),
            'driver_id' => $order->driver_id !== null ? (int) $order->driver_id : null,
            'restaurant_id' => $order->restaurant_id !== null ? (int) $order->restaurant_id : null,
            'price' => (float) $order->total_price,
            'total_price' => (float) $order->total_price,
            'updated_at' => $order->updated_at,
        ];
    }

    private function userPayload(User $user): array
    {
        $isActive = ! method_exists($user, 'trashed') || ! $user->trashed();
        if (array_key_exists('banned_at', $user->getAttributes())) {
            $isActive = $user->banned_at === null;
        }

        return [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'role' => 'customer',
            'status' => $isActive ? 'active' : 'banned',
            'updated_at' => $user->updated_at,
        ];
    }

    private function driverPayload(Driver $driver): array
    {
        $status = $driver->approval_status === 'approved'
            ? ((bool) $driver->is_available ? 'available' : 'unavailable')
            : (string) $driver->approval_status;

        return [
            'id' => (int) $driver->id,
            'name' => (string) $driver->name,
            'role' => 'driver',
            'status' => $status,
            'updated_at' => $driver->updated_at,
        ];
    }

    private function restaurantPayload(Restaurant $restaurant): array
    {
        $status = ! (bool) $restaurant->is_active
            ? 'inactive'
            : ((bool) $restaurant->is_open ? 'open' : 'closed');

        return [
            'id' => (int) $restaurant->id,
            'name' => (string) $restaurant->name,
            'status' => $status,
            'is_open' => (bool) $restaurant->is_open,
            'updated_at' => $restaurant->updated_at,
        ];
    }

    private function addressPayload(Address $address): array
    {
        return [
            'id' => (int) $address->id,
            'user_id' => (string) $address->user_id,
            'details' => $address->formattedDeliveryLine(),
            'updated_at' => $address->updated_at,
        ];
    }

    private function ratingPayload(RestaurantRating $rating): array
    {
        return [
            'id' => (int) $rating->id,
            'value' => (int) $rating->rating,
            'order_id' => $rating->order_id !== null ? (int) $rating->order_id : null,
            'updated_at' => $rating->updated_at,
        ];
    }

    private function isEnabled(): bool
    {
        return (bool) config('services.firestore.enabled')
            && filled(config('services.firestore.project_id'))
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
        $path = config('services.firestore.credentials_path');
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
                'scope' => 'https://www.googleapis.com/auth/datastore',
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
                Log::warning('Firestore token request failed.', [
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
            Log::warning('Firestore JWT signing failed.');
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
    private function encodeFields(array $data): array
    {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[$key] = $this->encodeValue($value);
        }

        return $fields;
    }

    private function encodeValue(mixed $value): array
    {
        if ($value === null) {
            return ['nullValue' => null];
        }

        if ($value instanceof DateTimeInterface) {
            return ['timestampValue' => $value->format(DateTimeInterface::RFC3339_EXTENDED)];
        }

        if (is_bool($value)) {
            return ['booleanValue' => $value];
        }

        if (is_int($value)) {
            return ['integerValue' => (string) $value];
        }

        if (is_float($value)) {
            return ['doubleValue' => $value];
        }

        return ['stringValue' => (string) $value];
    }
}
