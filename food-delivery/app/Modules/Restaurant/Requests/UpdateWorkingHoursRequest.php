<?php

namespace App\Modules\Restaurant\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkingHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('restaurant')->check();
    }

    public function rules(): array
    {
        return [
            'working_hours' => ['required', 'array'],
            'working_hours.*.enabled' => ['nullable', 'boolean'],
            'working_hours.*.open' => ['required', 'date_format:H:i'],
            'working_hours.*.close' => ['required', 'date_format:H:i'],
        ];
    }
}
