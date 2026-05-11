<?php

namespace App\Modules\Restaurant\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RestaurantInvoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $restaurant = Auth::guard('restaurant')->user();

        if (!$restaurant || !$restaurant->id) {
            return redirect()->route('restaurant.login');
        }

        $query = RestaurantInvoice::query()
            ->where('restaurant_id', (int) $restaurant->id);

        $status = trim((string) $request->query('status', ''));
        if (in_array($status, ['pending', 'paid', 'unpaid'], true)) {
            $query->where('status', $status);
        }

        $dateFrom = $request->query('date_from');
        if (!empty($dateFrom)) {
            $query->whereDate('start_date', '>=', $dateFrom);
        }

        $dateTo = $request->query('date_to');
        if (!empty($dateTo)) {
            $query->whereDate('end_date', '<=', $dateTo);
        }

        $invoices = $query->latest('id')->paginate(15)->withQueryString();

        $summary = [
            'total_invoices' => (int) RestaurantInvoice::where('restaurant_id', $restaurant->id)->count(),
            'total_sales' => (float) RestaurantInvoice::where('restaurant_id', $restaurant->id)->sum('total_sales'),
            'total_commission' => (float) RestaurantInvoice::where('restaurant_id', $restaurant->id)->sum('commission_amount'),
            'total_final_amount' => (float) RestaurantInvoice::where('restaurant_id', $restaurant->id)->sum('final_amount'),
        ];

        $filters = [
            'status' => $status,
            'date_from' => (string) ($dateFrom ?? ''),
            'date_to' => (string) ($dateTo ?? ''),
        ];

        return view('restaurant::invoices', compact('restaurant', 'invoices', 'summary', 'filters'));
    }

    public function show(int $invoiceId): View|RedirectResponse
    {
        $restaurant = Auth::guard('restaurant')->user();

        if (!$restaurant || !$restaurant->id) {
            return redirect()->route('restaurant.login');
        }

        $invoice = RestaurantInvoice::query()
            ->where('restaurant_id', (int) $restaurant->id)
            ->with([
                'restaurant:id,name,email,phone',
                'orders' => fn ($q) => $q
                    ->orderBy('orders.created_at')
                    ->select('orders.id', 'orders.order_number', 'orders.status', 'orders.total_price', 'orders.created_at'),
            ])
            ->findOrFail($invoiceId);

        return view('restaurant::invoice-details', compact('restaurant', 'invoice'));
    }
}
