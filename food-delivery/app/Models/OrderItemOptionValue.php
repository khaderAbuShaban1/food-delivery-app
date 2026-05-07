<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemOptionValue extends Model
{
    protected $fillable = [
        'order_item_id',
        'option_group_id',
        'option_value_id',
        'group_name',
        'value_name',
        'extra_price',
    ];

    protected function casts(): array
    {
        return [
            'extra_price' => 'decimal:2',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}

