<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Driver extends Model implements Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable, AuthenticatableTrait;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_available',
        'vehicle_type',
        'license_number',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'is_available' => 'boolean',
        ];
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'driver_id');
    }
}