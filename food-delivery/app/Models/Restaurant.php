<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Restaurant extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

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

    public function ratings()
    {
        return $this->hasMany(RestaurantRating::class);
    }
}
