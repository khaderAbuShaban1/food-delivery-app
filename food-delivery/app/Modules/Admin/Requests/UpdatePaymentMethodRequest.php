<?php

namespace App\Modules\Admin\Requests;

use App\Modules\Admin\Requests\Concerns\ValidatesPaymentMethodFields;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentMethodRequest extends FormRequest
{
    use ValidatesPaymentMethodFields;

    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->paymentMethodFieldRules(true);
    }
}
