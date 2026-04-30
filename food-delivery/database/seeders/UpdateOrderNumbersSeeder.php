<?php

namespace Database\Seeders;

use App\Models\Order;
use Illuminate\Database\Seeder;

class UpdateOrderNumbersSeeder extends Seeder
{
    public function run(): void
    {
        $orders = Order::all();
        foreach ($orders as $order) {
            if (!$order->order_number) {
                $order->update(['order_number' => Order::generateOrderNumber()]);
            }
        }
        echo "Updated " . $orders->count() . " orders with order numbers\n";
    }
}