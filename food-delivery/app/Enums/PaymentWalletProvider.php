<?php

namespace App\Enums;

enum PaymentWalletProvider: string
{
    case PalPay = 'PalPay';
    case JawwalPay = 'Jawwal Pay';

    public static function labels(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::PalPay => 'بال باي PalPay',
            self::JawwalPay => 'جوال باي Jawwal Pay',
        };
    }
}
