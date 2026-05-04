<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(DriverSeeder::class);

        $restaurant = Restaurant::create([
            'name' => 'Test Restaurant',
            'category' => 'Fast Food',
            'email' => 'restaurant@test.com',
            'password' => Hash::make('password123'),
            'phone' => '+201234567890',
            'is_open' => true,
        ]);

        $burger = MenuItem::create(['restaurant_id' => $restaurant->id, 'name' => 'Burger', 'price' => 5.99, 'description' => 'Delicious burger']);
        $pizza = MenuItem::create(['restaurant_id' => $restaurant->id, 'name' => 'Pizza', 'price' => 8.99, 'description' => 'Cheese pizza']);
        $fries = MenuItem::create(['restaurant_id' => $restaurant->id, 'name' => 'Fries', 'price' => 2.99, 'description' => 'Crispy fries']);

        $order = Order::create([
            'restaurant_id' => $restaurant->id,
            'driver_id' => null,
            'total_price' => 14.98,
            'status' => 'pending_payment_verification',
            'payment_reference' => 'SEED-TEST-REF',
        ]);

        OrderItem::create(['order_id' => $order->id, 'menu_item_id' => $burger->id, 'quantity' => 2, 'price' => 5.99]);
        OrderItem::create(['order_id' => $order->id, 'menu_item_id' => $fries->id, 'quantity' => 1, 'price' => 2.99]);
    }
}