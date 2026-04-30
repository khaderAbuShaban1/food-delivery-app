<?php

namespace App\Modules\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSecuritySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'password_min_length' => ['required', 'integer', 'min:6', 'max:32'],
            'password_require_complexity' => ['required', 'boolean'],
            'session_timeout_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'login_attempt_limit' => ['required', 'integer', 'min:3', 'max:20'],
        ];
    }
}
