<?php

namespace App\Observers;

use App\Models\OrderItem;
use App\Models\MenuItem;

class OrderItemObserver
{
    public function creating(OrderItem $orderItem): void
    {
        $menuItem = MenuItem::find($orderItem->menu_item_id);
        if ($menuItem) {
            $orderItem->image = $menuItem->image;
            $orderItem->name = $menuItem->name;
        }
    }

    public function updating(OrderItem $orderItem): void
    {
        $menuItem = MenuItem::find($orderItem->menu_item_id);
        if ($menuItem) {
            $orderItem->image = $menuItem->image;
            $orderItem->name = $menuItem->name;
        }
    }
}
