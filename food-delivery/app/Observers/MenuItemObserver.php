<?php

namespace App\Observers;

use App\Models\MenuItem;
use App\Models\OrderItem;

class MenuItemObserver
{
    public function updated(MenuItem $menuItem): void
    {
        if ($menuItem->isDirty(['image', 'name'])) {
            OrderItem::where('menu_item_id', $menuItem->id)->update([
                'image' => $menuItem->image,
                'name' => $menuItem->name,
            ]);
        }
    }
}