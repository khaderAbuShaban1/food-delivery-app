<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'restaurant_id',
        'driver_id',
        'total_price',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function customerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function legacyUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public static function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        return "ORD-{$date}-{$random}";
    }

    public static function validateItemsIntegrity($restaurantId, array $menuItemIds): bool
    {
        $menuItems = MenuItem::whereIn('id', $menuItemIds)->get();
        return $menuItems->every(fn($item) => $item->restaurant_id == $restaurantId);
    }
}