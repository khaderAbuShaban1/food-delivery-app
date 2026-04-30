<?php

namespace App\Modules\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'cash_enabled' => ['required', 'boolean'],
            'online_enabled' => ['required', 'boolean'],
            'transaction_fee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'auto_capture' => ['required', 'boolean'],
        ];
    }
}
