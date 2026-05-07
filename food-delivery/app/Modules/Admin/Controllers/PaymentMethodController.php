<?php

namespace App\Modules\Admin\Controllers;

use App\Enums\PaymentMethodType;
use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Modules\Admin\Requests\StorePaymentMethodRequest;
use App\Modules\Admin\Requests\UpdatePaymentMethodRequest;
use App\Support\PaymentMethods\PaymentMethodAssets;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentMethodController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total' => PaymentMethod::query()->count(),
            'active' => PaymentMethod::query()->where('is_active', true)->count(),
        ];

        $paymentMethods = PaymentMethod::query()->latest()->paginate(15);
        $typeLabels = collect(PaymentMethodType::cases())
            ->mapWithKeys(fn (PaymentMethodType $t) => [$t->value => $t->label()])
            ->all();
        $bankLabels = \App\Enums\PaymentBankName::labels();
        $walletLabels = \App\Enums\PaymentWalletProvider::labels();

        $paymentMethodsForJs = collect($paymentMethods->items())
            ->map(fn (PaymentMethod $p) => [
                'id' => $p->id,
                'type' => $p->type->value,
                'bank_name' => $p->bank_name,
                'wallet_provider' => $p->wallet_provider,
                'account_holder_name' => $p->account_holder_name,
                'account_number' => $p->account_number,
                'phone_number' => $p->phone_number,
                'is_active' => $p->is_active,
                'static_image' => $p->static_image,
            ])
            ->values();

        $previewUrls = collect(PaymentMethodAssets::previewMap())
            ->map(fn (string $path) => asset($path))
            ->all();

        return view('admin.payment-methods', compact(
            'paymentMethods',
            'typeLabels',
            'bankLabels',
            'walletLabels',
            'paymentMethodsForJs',
            'previewUrls',
            'stats',
        ));
    }

    public function store(StorePaymentMethodRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        PaymentMethod::query()->create([
            'type' => $validated['type'],
            'bank_name' => $validated['bank_name'] ?? null,
            'wallet_provider' => $validated['wallet_provider'] ?? null,
            'account_holder_name' => $validated['account_holder_name'],
            'account_number' => $validated['account_number'] ?? null,
            'phone_number' => $validated['phone_number'],
            'static_image' => PaymentMethodAssets::relativePath(
                $validated['type'],
                $validated['bank_name'] ?? null,
                $validated['wallet_provider'] ?? null,
            ),
            'is_active' => (bool) (int) $validated['is_active'],
        ]);

        return redirect()
            ->route('admin.payment-methods.index')
            ->with('success', 'تم إضافة طريقة الدفع بنجاح.');
    }

    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $validated = $request->validated();
        $paymentMethod->update([
            'type' => $validated['type'],
            'bank_name' => $validated['bank_name'] ?? null,
            'wallet_provider' => $validated['wallet_provider'] ?? null,
            'account_holder_name' => $validated['account_holder_name'],
            'account_number' => $validated['account_number'] ?? null,
            'phone_number' => $validated['phone_number'],
            'static_image' => PaymentMethodAssets::relativePath(
                $validated['type'],
                $validated['bank_name'] ?? null,
                $validated['wallet_provider'] ?? null,
            ),
            'is_active' => (bool) (int) $validated['is_active'],
        ]);

        return redirect()
            ->route('admin.payment-methods.index')
            ->with('success', 'تم تحديث طريقة الدفع.');
    }

    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        $paymentMethod->delete();

        return redirect()
            ->route('admin.payment-methods.index')
            ->with('success', 'تم حذف طريقة الدفع.');
    }

    public function toggle(PaymentMethod $paymentMethod): RedirectResponse
    {
        $paymentMethod->update(['is_active' => ! $paymentMethod->is_active]);

        return redirect()
            ->route('admin.payment-methods.index')
            ->with('success', 'تم تحديث حالة طريقة الدفع.');
    }
}
