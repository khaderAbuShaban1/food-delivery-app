<?php

namespace App\Models;

use App\Enums\PaymentMethodType;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        'type',
        'bank_name',
        'wallet_provider',
        'account_holder_name',
        'account_number',
        'phone_number',
        'static_image',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => PaymentMethodType::class,
            'is_active' => 'boolean',
        ];
    }

    /** Human-readable subtitle for listings (Arabic-first). */
    public function subtypeLabel(): string
    {
        $v = match ($this->type) {
            PaymentMethodType::Bank => $this->bank_name,
            PaymentMethodType::Wallet => $this->wallet_provider,
        };

        return $v ?: '—';
    }
}
