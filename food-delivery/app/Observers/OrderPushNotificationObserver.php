<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\OrderPushNotificationService;
use Illuminate\Support\Facades\Log;

class OrderPushNotificationObserver
{
    public function created(Order $order): void
    {
        Log::info('Order push observer detected order creation.', [
            'order_id' => $order->id,
        ]);

        app(OrderPushNotificationService::class)->notifyOrderCreatedAfterCommit($order);
    }

    public function updated(Order $order): void
    {
        $changes = $order->getChanges();

        if (! array_key_exists('status', $changes) && ! array_key_exists('driver_id', $changes)) {
            return;
        }

        Log::info('Order push observer detected order update.', [
            'order_id' => $order->id,
            'changes' => array_keys($changes),
        ]);

        app(OrderPushNotificationService::class)->notifyOrderUpdatedAfterCommit($order, $changes);
    }
}
