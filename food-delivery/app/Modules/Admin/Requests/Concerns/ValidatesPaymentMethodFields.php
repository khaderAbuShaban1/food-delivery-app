<?php

namespace App\Modules\Admin\Requests\Concerns;

use App\Enums\PaymentBankName;
use App\Enums\PaymentMethodType;
use App\Enums\PaymentWalletProvider;
use Illuminate\Validation\Rule;

trait ValidatesPaymentMethodFields
{
    /**
     * @return array<string, mixed>
     */
    protected function paymentMethodFieldRules(?bool $isActiveIncluded = false): array
    {
        $rules = [
            'type' => ['required', Rule::enum(PaymentMethodType::class)],
            'bank_name' => [
                Rule::excludeUnless(fn () => $this->input('type') === PaymentMethodType::Bank->value),
                'required',
                Rule::enum(PaymentBankName::class),
            ],
            'account_number' => [
                Rule::excludeUnless(fn () => $this->input('type') === PaymentMethodType::Bank->value),
                'required',
                'string',
                'max:100',
            ],
            'wallet_provider' => [
                Rule::excludeUnless(fn () => $this->input('type') === PaymentMethodType::Wallet->value),
                'required',
                Rule::enum(PaymentWalletProvider::class),
            ],
            'account_holder_name' => ['required', 'string', 'max:190'],
            'phone_number' => ['required', 'string', 'max:40'],
        ];

        if ($isActiveIncluded) {
            $rules['is_active'] = ['required', Rule::in(['0', '1'])];
        }

        return $rules;
    }
}
