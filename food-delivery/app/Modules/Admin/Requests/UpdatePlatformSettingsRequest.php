<?php

namespace App\Modules\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlatformSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'registration_enabled' => ['required', 'boolean'],
            'restaurants_enabled' => ['required', 'boolean'],
            'orders_enabled' => ['required', 'boolean'],
            'platform_open' => ['required', 'boolean'],
        ];
    }
}
