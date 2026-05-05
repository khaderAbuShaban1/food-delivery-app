<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItemOptionValue extends Model
{
    protected $fillable = [
        'option_group_id',
        'name',
        'extra_price',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'extra_price' => 'decimal:2',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(MenuItemOptionGroup::class, 'option_group_id');
    }
}

