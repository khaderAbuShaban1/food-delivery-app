<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RestaurantInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'invoice_number',
        'total_orders',
        'total_sales',
        'commission_percentage',
        'commission_amount',
        'final_amount',
        'status',
        'start_date',
        'end_date',
        'notes',
        'payment_proof',
        'payment_proof_image',
        'payment_paid_at',
    ];

    protected function casts(): array
    {
        return [
            'total_sales' => 'decimal:2',
            'commission_percentage' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'final_amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'payment_paid_at' => 'datetime',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'restaurant_invoice_orders', 'restaurant_invoice_id', 'order_id')
            ->withTimestamps();
    }
}
