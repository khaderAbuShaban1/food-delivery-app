<?php

namespace App\Enums;

enum PaymentMethodType: string
{
    case Bank = 'bank';
    case Wallet = 'wallet';

    public function label(): string
    {
        return match ($this) {
            self::Bank => 'حساب بنكي',
            self::Wallet => 'محفظة إلكترونية',
        };
    }
}
