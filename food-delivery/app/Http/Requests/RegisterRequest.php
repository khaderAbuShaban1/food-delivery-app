<?php

namespace App\Http\Requests;

use App\Services\SystemSettingsService;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var SystemSettingsService $settings */
        $settings = app(SystemSettingsService::class);
        $minLength = (int) $settings->get('security', 'password_min_length', 8);
        $requireComplexity = (bool) $settings->get('security', 'password_require_complexity', false);

        $passwordRules = ['required', 'string', 'min:' . max(6, $minLength)];
        if ($requireComplexity) {
            $passwordRules[] = 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/';
        }

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => $passwordRules,
            'role' => 'sometimes|in:customer,driver,restaurant,admin',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required',
            'email.required' => 'Email is required',
            'email.email' => 'Please provide a valid email',
            'email.unique' => 'Email already registered',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 6 characters',
            'password.regex' => 'Password must include uppercase, lowercase, and a number',
            'role.in' => 'Role must be one of: customer, driver, restaurant, admin',
        ];
    }
}