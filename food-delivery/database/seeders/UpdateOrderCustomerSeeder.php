<?php

namespace Database\Seeders;

use App\Models\Order;
use Illuminate\Database\Seeder;

class UpdateOrderCustomerSeeder extends Seeder
{
    public function run(): void
    {
        $orders = Order::all();
        foreach ($orders as $order) {
            if (!$order->customer_id) {
                $order->update(['customer_id' => rand(1, 3)]);
            }
        }
        echo "Updated " . $orders->count() . " orders with customer_id\n";
    }
}