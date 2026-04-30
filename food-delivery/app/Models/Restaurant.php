<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Restaurant extends Model implements Authenticatable
{
    use HasFactory, Notifiable, AuthenticatableTrait, HasApiTokens;

    protected $table = 'restaurants';

    protected $fillable = [
        'name',
        'description',
        'email',
        'password',
        'phone',
        'category',
        'image',
        'is_open',
        'minimum_order_amount',
        'delivery_available',
        'working_hours',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_open' => 'boolean',
            'is_active' => 'boolean',
            'delivery_available' => 'boolean',
            'minimum_order_amount' => 'decimal:2',
            'working_hours' => 'array',
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
        ];
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}