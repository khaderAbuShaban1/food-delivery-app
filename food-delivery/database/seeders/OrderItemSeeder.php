<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Seeder;

class OrderItemSeeder extends Seeder
{
    public function run(): void
    {
        $orders = Order::all();
        $totalNew = 0;

        foreach ($orders as $order) {
            $existingItems = $order->orderItems()->pluck('menu_item_id')->toArray();

            if (count($existingItems) == 0) {
                $menuItems = MenuItem::where('restaurant_id', $order->restaurant_id)->get();

                if ($menuItems->count() > 0) {
                    $itemsToAdd = $menuItems->random(min(rand(2, 4), $menuItems->count()));

                    foreach ($itemsToAdd as $item) {
                        OrderItem::create([
                            'order_id' => $order->id,
                            'menu_item_id' => $item->id,
                            'quantity' => rand(1, 3),
                            'price' => $item->price
                        ]);
                    }
                    $totalNew++;
                }
            }
        }

        echo "Updated " . $totalNew . " orders with menu items\n";
        echo "Total order items: " . OrderItem::count() . "\n";
    }
}