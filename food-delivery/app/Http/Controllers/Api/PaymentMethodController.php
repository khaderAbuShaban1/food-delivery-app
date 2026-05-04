<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;

class PaymentMethodController extends Controller
{
    /** Active manual payment accounts for checkout (customers). */
    public function active(): JsonResponse
    {
        $methods = PaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('id')
            ->get();

        $data = $methods->map(function (PaymentMethod $p) {
            $static = $p->static_image ? trim((string) $p->static_image, '/') : null;

            return [
                'id' => $p->id,
                'type' => $p->type->value,
                'type_label' => $p->type->label(),
                'bank_name' => $p->bank_name,
                'wallet_provider' => $p->wallet_provider,
                'subtype_name' => $p->subtypeLabel(),
                'account_holder_name' => $p->account_holder_name,
                'account_number' => $p->account_number,
                'phone_number' => $p->phone_number,
                'static_image' => $static,
                'static_image_url' => $static ? asset($static) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
