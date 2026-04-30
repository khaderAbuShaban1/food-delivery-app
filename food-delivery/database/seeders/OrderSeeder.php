<?php

namespace Database\Seeders;

use App\Models\Order;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $orders = [
            ['status' => 'pending', 'total_price' => 30, 'restaurant_id' => 1],
            ['status' => 'pending', 'total_price' => 75, 'restaurant_id' => 1],
            ['status' => 'accepted', 'total_price' => 120, 'restaurant_id' => 2],
            ['status' => 'preparing', 'total_price' => 45, 'restaurant_id' => 1],
            ['status' => 'delivering', 'total_price' => 90, 'restaurant_id' => 2],
            ['status' => 'completed', 'total_price' => 148, 'restaurant_id' => 3],
            ['status' => 'completed', 'total_price' => 50, 'restaurant_id' => 3],
            ['status' => 'pending', 'total_price' => 55, 'restaurant_id' => 1],
            ['status' => 'pending', 'total_price' => 200, 'restaurant_id' => 2],
            ['status' => 'pending', 'total_price' => 35, 'restaurant_id' => 1],
        ];

        foreach ($orders as $order) {
            Order::create($order);
        }

        echo "Created " . count($orders) . " orders\n";
    }
}