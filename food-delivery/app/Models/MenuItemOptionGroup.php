<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItemOptionGroup extends Model
{
    protected $fillable = [
        'menu_item_id',
        'name',
        'selection_type',
        'sort_order',
    ];

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(MenuItemOptionValue::class, 'option_group_id')->orderBy('sort_order');
    }
}

