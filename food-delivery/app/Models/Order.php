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
        'user_id',
        'restaurant_id',
        'driver_id',
        'total_price',
        'delivery_address',
        'status',
        'payment_proof',
        'payment_reference',
        'payment_verified_at',
        'verified_by_admin_id',
        'payment_method_id',
        'assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:2',
            'payment_verified_at' => 'datetime',
            'assigned_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function verifiedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'verified_by_admin_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
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