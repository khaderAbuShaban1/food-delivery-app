<?php

namespace App\Enums;

enum PaymentBankName: string
{
    case BankOfPalestine = 'Bank of Palestine';

    public static function labels(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::BankOfPalestine => 'بنك فلسطين',
        };
    }
}
