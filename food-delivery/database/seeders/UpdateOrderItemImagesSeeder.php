<?php

namespace Database\Seeders;

use App\Models\OrderItem;
use Illuminate\Database\Seeder;

class UpdateOrderItemImagesSeeder extends Seeder
{
    public function run(): void
    {
        $orderItems = OrderItem::all();
        $updated = 0;

        foreach ($orderItems as $item) {
            $menuItem = $item->menuItem;
            if ($menuItem) {
                $item->update([
                    'image' => $menuItem->image,
                    'name' => $menuItem->name,
                ]);
                $updated++;
            }
        }

        echo "Updated {$updated} order items with images and names\n";
    }
}