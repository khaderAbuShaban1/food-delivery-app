<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
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
            'role.in' => 'Role must be one of: customer, driver, restaurant, admin',
        ];
    }
}