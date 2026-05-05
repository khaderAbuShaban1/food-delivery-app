<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'name',
        'price',
        'description',
        'image',
        'category',
        'is_available',
        'id_number',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_available' => 'boolean',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function optionGroups(): HasMany
    {
        return $this->hasMany(MenuItemOptionGroup::class)->orderBy('sort_order');
    }

    public function hasActiveOrders(): bool
    {
        return $this->orderItems()
            ->whereHas('order', fn($q) => $q->whereNotIn('status', ['delivered', 'payment_rejected']))
            ->exists();
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public static function generateIdNumber(): string
    {
        return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    }
}