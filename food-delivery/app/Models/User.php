<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'profile_image',
        'password',
        'email_verification_code_hash',
        'email_verification_expires_at',
        'email_verification_last_sent_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_code_hash',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_expires_at' => 'datetime',
            'email_verification_last_sent_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function restaurants(): HasMany
    {
        return $this->hasMany(Restaurant::class, 'user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function getTotalSpentAttribute(): float
    {
        return $this->orders()->where('status', 'delivered')->sum('total_price') ?? 0;
    }
}
