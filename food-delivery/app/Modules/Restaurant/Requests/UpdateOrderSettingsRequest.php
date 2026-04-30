<?php

namespace App\Modules\Restaurant\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('restaurant')->check();
    }

    public function rules(): array
    {
        return [
            'is_open' => ['required', 'boolean'],
            'minimum_order_amount' => ['nullable', 'numeric', 'min:0'],
            'delivery_available' => ['required', 'boolean'],
        ];
    }
}
