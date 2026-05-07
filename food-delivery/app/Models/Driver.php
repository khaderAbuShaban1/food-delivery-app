<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
        'national_id',
        'email',
        'phone',
        'password',
        'approval_status',
        'profile_image',
        'national_id_image',
        'vehicle_image',
        'is_available',
        'vehicle_type',
        'vehicle_plate_number',
        'city',
        'emergency_contact_number',
        'license_number',
        'approved_at',
        'rejected_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_code_hash',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'email_verification_expires_at' => 'datetime',
            'email_verification_last_sent_at' => 'datetime',
            'is_available' => 'boolean',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'driver_id');
    }
}
