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
        $restaurantUser = User::create([
            'name' => 'Test Restaurant',
            'email' => 'restaurant@test.com',
            'phone' => '+201234567890',
            'password' => Hash::make('password123'),
            'role' => 'restaurant',
        ]);

        $customer = User::create([
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'phone' => '+201234567891',
            'password' => Hash::make('password123'),
            'role' => 'customer',
        ]);

        $driver = User::create([
            'name' => 'Test Driver',
            'email' => 'driver@test.com',
            'phone' => '+201234567892',
            'password' => Hash::make('password123'),
            'role' => 'driver',
        ]);

        $restaurant = Restaurant::create([
            'user_id' => $restaurantUser->id,
            'name' => 'Test Restaurant',
            'category' => 'Fast Food',
            'is_open' => true,
        ]);

        MenuItem::create(['restaurant_id' => $restaurant->id, 'name' => 'Burger', 'price' => 5.99, 'description' => 'Delicious burger']);
        MenuItem::create(['restaurant_id' => $restaurant->id, 'name' => 'Pizza', 'price' => 8.99, 'description' => 'Cheese pizza']);
        MenuItem::create(['restaurant_id' => $restaurant->id, 'name' => 'Fries', 'price' => 2.99, 'description' => 'Crispy fries']);

        $order = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'driver_id' => $driver->id,
            'total_price' => 14.98,
            'status' => 'pending',
        ]);

        OrderItem::create(['order_id' => $order->id, 'menu_item_id' => 1, 'quantity' => 2, 'price' => 5.99]);
        OrderItem::create(['order_id' => $order->id, 'menu_item_id' => 3, 'quantity' => 1, 'price' => 2.99]);
    }
}