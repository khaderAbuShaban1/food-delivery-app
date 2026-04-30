<?php

namespace Database\Seeders;

use App\Models\OrderItem;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class SyncOrderItemImagesSeeder extends Seeder
{
    public function run(): void
    {
        $orderItems = OrderItem::all();
        $syncCount = 0;

        foreach ($orderItems as $item) {
            $menuItem = MenuItem::find($item->menu_item_id);
            if ($menuItem && ($item->image !== $menuItem->image || $item->name !== $menuItem->name)) {
                $item->update([
                    'image' => $menuItem->image,
                    'name' => $menuItem->name,
                ]);
                $syncCount++;
            }
        }

        echo "Synced {$syncCount} order items with current menu item images\n";
    }
}