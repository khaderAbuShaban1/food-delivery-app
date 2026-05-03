<?php

namespace App\Support\PaymentMethods;

use App\Enums\PaymentBankName;
use App\Enums\PaymentMethodType;
use App\Enums\PaymentWalletProvider;

final class PaymentMethodAssets
{
    public const BANK_OF_PALESTINE = 'images/payment-methods/bank-of-palestine.png';

    public const PALPAY = 'images/payment-methods/palpay.png';

    public const JAWWAL_PAY = 'images/payment-methods/jawwal-pay.png';

    public static function relativePath(?string $type, ?string $bankName, ?string $walletProvider): ?string
    {
        if ($type === PaymentMethodType::Bank->value) {
            return match ($bankName) {
                PaymentBankName::BankOfPalestine->value => self::BANK_OF_PALESTINE,
                default => null,
            };
        }

        if ($type === PaymentMethodType::Wallet->value) {
            return match ($walletProvider) {
                PaymentWalletProvider::PalPay->value => self::PALPAY,
                PaymentWalletProvider::JawwalPay->value => self::JAWWAL_PAY,
                default => null,
            };
        }

        return null;
    }

    /** @return array<string, string> value => asset path relative to public */
    public static function previewMap(): array
    {
        return [
            PaymentMethodType::Bank->value.'|'.PaymentBankName::BankOfPalestine->value => self::BANK_OF_PALESTINE,
            PaymentMethodType::Wallet->value.'|'.PaymentWalletProvider::PalPay->value => self::PALPAY,
            PaymentMethodType::Wallet->value.'|'.PaymentWalletProvider::JawwalPay->value => self::JAWWAL_PAY,
        ];
    }

    public static function previewUrlKey(string $type, string $selectionValue): ?string
    {
        return self::previewMap()[$type.'|'.$selectionValue] ?? null;
    }
}
