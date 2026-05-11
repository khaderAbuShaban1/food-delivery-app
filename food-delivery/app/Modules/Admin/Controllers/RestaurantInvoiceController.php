<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\RestaurantInvoice;
use App\Services\OrderWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class RestaurantInvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $query = RestaurantInvoice::query()->with('restaurant:id,name');

        if ($request->filled('restaurant_id')) {
            $query->where('restaurant_id', (int) $request->restaurant_id);
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('start_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('end_date', '<=', $request->date_to);
        }

        $invoices = $query->latest('id')->paginate(15)->withQueryString();

        $summary = [
            'total_invoices' => (int) RestaurantInvoice::count(),
            'total_sales' => (float) RestaurantInvoice::sum('total_sales'),
            'total_commission' => (float) RestaurantInvoice::sum('commission_amount'),
            'total_restaurant_amount' => (float) RestaurantInvoice::sum('final_amount'),
            'pending_count' => (int) RestaurantInvoice::where('status', 'pending')->count(),
            'paid_count' => (int) RestaurantInvoice::where('status', 'paid')->count(),
            'unpaid_count' => (int) RestaurantInvoice::where('status', 'unpaid')->count(),
        ];

        $restaurants = Restaurant::query()->orderBy('name')->get(['id', 'name']);

        return view('admin::restaurant-invoices.index', compact('invoices', 'summary', 'restaurants'));
    }

    public function show(RestaurantInvoice $invoice): View
    {
        $invoice->load([
            'restaurant:id,name,email,phone',
            'orders' => fn ($query) => $query->with('customer:id,name')->orderBy('orders.created_at'),
        ]);

        return view('admin::restaurant-invoices.show', compact('invoice'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'restaurant_id' => ['required', 'exists:restaurants,id'],
            'period_type' => ['required', 'in:weekly,monthly,custom'],
            'commission_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        [$startDate, $endDate] = $this->resolvePeriodRange(
            $validated['period_type'],
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
        );

        $restaurantId = (int) $validated['restaurant_id'];
        $commissionPercentage = (float) $validated['commission_percentage'];

        $orders = Order::query()
            ->where('restaurant_id', $restaurantId)
            ->where('status', OrderWorkflow::DELIVERED)
            ->whereDate('created_at', '>=', $startDate->toDateString())
            ->whereDate('created_at', '<=', $endDate->toDateString())
            ->whereDoesntHave('invoices')
            ->get(['id', 'total_price']);

        if ($orders->isEmpty()) {
            return back()->with('error', 'لا توجد طلبات مكتملة غير مفوترة ضمن الفترة المحددة.');
        }

        $totalSales = (float) $orders->sum('total_price');
        $commissionAmount = round($totalSales * ($commissionPercentage / 100), 2);
        $finalAmount = round($totalSales - $commissionAmount, 2);

        DB::transaction(function () use ($restaurantId, $commissionPercentage, $startDate, $endDate, $validated, $orders, $totalSales, $commissionAmount, $finalAmount): void {
            $invoice = RestaurantInvoice::create([
                'restaurant_id' => $restaurantId,
                'invoice_number' => $this->generateInvoiceNumber(),
                'total_orders' => $orders->count(),
                'total_sales' => $totalSales,
                'commission_percentage' => $commissionPercentage,
                'commission_amount' => $commissionAmount,
                'final_amount' => $finalAmount,
                'status' => 'pending',
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $invoice->orders()->attach($orders->pluck('id')->all());
        });

        return back()->with('success', 'تم إنشاء الفاتورة بنجاح.');
    }

    public function updateStatus(Request $request, RestaurantInvoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,paid,unpaid'],
        ]);

        $invoice->update([
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'تم تحديث حالة الفاتورة بنجاح.');
    }

    public function update(Request $request, RestaurantInvoice $invoice): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,paid,unpaid'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'commission_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $commissionPercentage = (float) ($validated['commission_percentage'] ?? $invoice->commission_percentage);
        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->endOfDay();

        $orders = Order::query()
            ->where('restaurant_id', $invoice->restaurant_id)
            ->where('status', OrderWorkflow::DELIVERED)
            ->whereDate('created_at', '>=', $startDate->toDateString())
            ->whereDate('created_at', '<=', $endDate->toDateString())
            ->where(function ($query) use ($invoice) {
                $query->whereDoesntHave('invoices')
                    ->orWhereHas('invoices', function ($q) use ($invoice) {
                        $q->where('restaurant_invoices.id', $invoice->id);
                    });
            })
            ->get(['id', 'total_price']);

        if ($orders->isEmpty()) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'لا توجد طلبات ضمن الفترة المحددة.'], 422)
                : back()->with('error', 'لا توجد طلبات ضمن الفترة المحددة.');
        }

        $totalSales = (float) $orders->sum('total_price');
        $commissionAmount = round($totalSales * ($commissionPercentage / 100), 2);
        $finalAmount = round($totalSales - $commissionAmount, 2);

        DB::transaction(function () use ($validated, $invoice, $orders, $startDate, $endDate, $commissionPercentage, $totalSales, $commissionAmount, $finalAmount): void {
            $invoice->update([
                'status' => $validated['status'],
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'commission_percentage' => $commissionPercentage,
                'total_orders' => $orders->count(),
                'total_sales' => $totalSales,
                'commission_amount' => $commissionAmount,
                'final_amount' => $finalAmount,
                'notes' => $validated['notes'] ?? null,
            ]);

            $invoice->orders()->sync($orders->pluck('id')->all());
        });

        $invoice->refresh();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حفظ التعديلات بنجاح.',
                'invoice' => $this->invoicePayload($invoice),
            ]);
        }

        return back()->with('success', 'تم حفظ التعديلات بنجاح.');
    }

    public function updatePaymentProof(Request $request, RestaurantInvoice $invoice): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'payment_proof_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'payment_paid_at' => ['nullable', 'date'],
        ]);

        if ($invoice->payment_proof_image || $invoice->payment_proof) {
            Storage::disk('public')->delete($invoice->payment_proof_image ?: $invoice->payment_proof);
        }

        $stored = $request->file('payment_proof_image')->store('invoice-proofs', 'public');
        $invoice->update([
            'payment_proof_image' => $stored,
            'payment_proof' => $stored,
            'payment_paid_at' => !empty($validated['payment_paid_at']) ? Carbon::parse($validated['payment_paid_at']) : null,
        ]);

        $invoice->refresh();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم رفع إثبات الدفع بنجاح.',
                'invoice' => $this->invoicePayload($invoice),
            ]);
        }

        return back()->with('success', 'تم رفع إثبات الدفع بنجاح.');
    }

    public function destroy(Request $request, RestaurantInvoice $invoice): RedirectResponse|JsonResponse
    {
        if ($invoice->payment_proof_image || $invoice->payment_proof) {
            Storage::disk('public')->delete($invoice->payment_proof_image ?: $invoice->payment_proof);
        }

        $invoice->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'تم حذف الفاتورة بنجاح.']);
        }

        return redirect()->route('admin.restaurant-invoices.index')->with('success', 'تم حذف الفاتورة بنجاح.');
    }

    private function resolvePeriodRange(string $periodType, ?string $startDate, ?string $endDate): array
    {
        $today = now();

        if ($periodType === 'weekly') {
            return [
                $today->copy()->startOfWeek(Carbon::SATURDAY),
                $today->copy()->endOfWeek(Carbon::FRIDAY),
            ];
        }

        if ($periodType === 'monthly') {
            return [
                $today->copy()->startOfMonth(),
                $today->copy()->endOfMonth(),
            ];
        }

        if (! $startDate || ! $endDate) {
            abort(422, 'الفترة المخصصة تحتاج تاريخ بداية وتاريخ نهاية.');
        }

        return [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay(),
        ];
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . now()->format('Ymd');
        $latest = RestaurantInvoice::query()
            ->where('invoice_number', 'like', $prefix . '-%')
            ->latest('id')
            ->value('invoice_number');

        $next = 1;
        if ($latest) {
            $parts = explode('-', $latest);
            $lastChunk = end($parts);
            if (is_numeric($lastChunk)) {
                $next = ((int) $lastChunk) + 1;
            }
        }

        return sprintf('%s-%04d', $prefix, $next);
    }

    private function invoicePayload(RestaurantInvoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'start_date' => optional($invoice->start_date)->format('Y-m-d'),
            'end_date' => optional($invoice->end_date)->format('Y-m-d'),
            'total_orders' => (int) $invoice->total_orders,
            'total_sales' => (float) $invoice->total_sales,
            'commission_percentage' => (float) $invoice->commission_percentage,
            'commission_amount' => (float) $invoice->commission_amount,
            'final_amount' => (float) $invoice->final_amount,
            'status' => $invoice->status,
            'notes' => $invoice->notes,
            'payment_paid_at' => optional($invoice->payment_paid_at)->format('Y-m-d'),
            'payment_proof_url' => ($invoice->payment_proof_image || $invoice->payment_proof)
                ? asset('storage/' . ($invoice->payment_proof_image ?: $invoice->payment_proof))
                : null,
        ];
    }
}
