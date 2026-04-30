<?php

namespace App\Modules\Restaurant\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('restaurant')->check();
    }

    public function rules(): array
    {
        return [
            'current_password' => ['nullable', 'required_with:new_password'],
            'new_password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
}
