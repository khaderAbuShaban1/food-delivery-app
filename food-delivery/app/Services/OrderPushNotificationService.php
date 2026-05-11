<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\FcmToken;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderPushNotificationService
{
    private const DRIVER_ANDROID_CHANNEL = 'food_delivery_driver_orders_loud_v2';

    private const CUSTOMER_ANDROID_CHANNEL = 'food_delivery_orders_silent';

    public function __construct(private readonly FirebaseCloudMessagingService $fcm)
    {
    }

    public function notifyOrderCreatedAfterCommit(Order $order): void
    {
        $this->afterCommit(fn () => $this->notifyDriversAboutNewOrder($order->fresh() ?? $order));
    }

    public function notifyOrderUpdatedAfterCommit(Order $order, array $changes): void
    {
        $this->afterCommit(function () use ($order, $changes) {
            $fresh = $order->fresh(['customerUser', 'legacyUser', 'driver']) ?? $order;

            if (array_key_exists('driver_id', $changes) && $fresh->driver_id !== null) {
                $this->notifyCustomerDriverAssigned($fresh);
                $this->notifyAssignedDriver($fresh);
            }

            if (array_key_exists('status', $changes)) {
                $this->notifyStatusChanged($fresh);
            }
        });
    }

    private function notifyDriversAboutNewOrder(Order $order): void
    {
        $drivers = Driver::query()
            ->where('approval_status', 'approved')
            ->where('is_available', true)
            ->pluck('id')
            ->all();

        $this->sendToTokenables(
            Driver::class,
            $drivers,
            'طلب جديد',
            'يوجد طلب جديد بانتظار المتابعة.',
            $this->orderData($order, 'order_created'),
        );
    }

    private function notifyCustomerDriverAssigned(Order $order): void
    {
        $customerId = $this->customerId($order);
        if ($customerId === null) {
            return;
        }

        $driverName = $order->driver?->name ?? 'السائق';

        $this->sendToTokenables(
            User::class,
            [$customerId],
            'تم قبول طلبك',
            "{$driverName} قبل توصيل طلبك.",
            $this->orderData($order, 'driver_assigned'),
        );
    }

    private function notifyAssignedDriver(Order $order): void
    {
        if ($order->driver_id === null) {
            return;
        }

        $this->sendToTokenables(
            Driver::class,
            [(int) $order->driver_id],
            'تم تعيين الطلب لك',
            'افتح التطبيق لمتابعة تفاصيل الطلب.',
            $this->orderData($order, 'driver_assigned'),
        );
    }

    private function notifyStatusChanged(Order $order): void
    {
        if ($order->status === OrderWorkflow::PREPARING && $order->driver_id === null) {
            $this->notifyDriversOrderReady($order);
        }

        $customerId = $this->customerId($order);
        if ($customerId !== null) {
            $this->sendToTokenables(
                User::class,
                [$customerId],
                'تحديث حالة الطلب',
                'حالة طلبك الآن: '.OrderWorkflow::label((string) $order->status),
                $this->orderData($order, 'order_status_changed'),
            );
        }

        if ($order->driver_id !== null) {
            $this->sendToTokenables(
                Driver::class,
                [(int) $order->driver_id],
                'تحديث حالة الطلب',
                'حالة الطلب الآن: '.OrderWorkflow::label((string) $order->status),
                $this->orderData($order, 'order_status_changed'),
            );
        }
    }

    private function notifyDriversOrderReady(Order $order): void
    {
        $drivers = Driver::query()
            ->where('approval_status', 'approved')
            ->where('is_available', true)
            ->pluck('id')
            ->all();

        $this->sendToTokenables(
            Driver::class,
            $drivers,
            'طلب جاهز للسائقين',
            'يوجد طلب جاهز للاستلام من المطعم.',
            $this->orderData($order, 'order_ready_for_driver'),
        );
    }

    /**
     * @param class-string $tokenableType
     * @param array<int, int|string> $tokenableIds
     * @param array<string, string|int|float|null> $data
     */
    private function sendToTokenables(string $tokenableType, array $tokenableIds, string $title, string $body, array $data): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $tokenableIds))));
        if ($ids === []) {
            return;
        }

        $tokens = FcmToken::query()
            ->where('tokenable_type', $tokenableType)
            ->whereIn('tokenable_id', $ids)
            ->pluck('token')
            ->all();

        Log::info('Order push notification queued for FCM send.', [
            'tokenable_type' => $tokenableType,
            'recipient_count' => count($ids),
            'token_count' => count($tokens),
            'event' => $data['event'] ?? null,
            'order_id' => $data['order_id'] ?? null,
        ]);

        $this->fcm->sendToTokens($tokens, $title, $body, $data, $this->androidNotificationOptions($tokenableType));
    }

    /** @return array<string, string|int|float|null> */
    private function orderData(Order $order, string $event): array
    {
        return [
            'event' => $event,
            'order_id' => (int) $order->id,
            'status' => (string) $order->status,
            'customer_id' => $this->customerId($order),
            'driver_id' => $order->driver_id !== null ? (int) $order->driver_id : null,
            'restaurant_id' => $order->restaurant_id !== null ? (int) $order->restaurant_id : null,
            'price' => (float) $order->total_price,
        ];
    }

    private function customerId(Order $order): ?int
    {
        if ($order->customer_id !== null) {
            return (int) $order->customer_id;
        }

        return $order->user_id !== null ? (int) $order->user_id : null;
    }

    /**
     * @param class-string $tokenableType
     * @return array<string, mixed>
     */
    private function androidNotificationOptions(string $tokenableType): array
    {
        if ($tokenableType === Driver::class) {
            return [
                'channel_id' => self::DRIVER_ANDROID_CHANNEL,
                'sound' => 'default',
                'default_sound' => true,
                'default_vibrate_timings' => true,
                'notification_priority' => 'PRIORITY_MAX',
            ];
        }

        return [
            'channel_id' => self::CUSTOMER_ANDROID_CHANNEL,
            'sound' => null,
            'default_sound' => false,
            'default_vibrate_timings' => false,
            'notification_priority' => 'PRIORITY_DEFAULT',
        ];
    }

    private function afterCommit(callable $callback): void
    {
        if (DB::connection()->transactionLevel() > 0) {
            DB::afterCommit($callback);
            return;
        }

        $callback();
    }
}
