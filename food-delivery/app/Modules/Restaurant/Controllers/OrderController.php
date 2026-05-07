<?php

namespace App\Modules\Restaurant\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class OrderController extends Controller
{
    private function statusLabel(string $status): string
    {
        return OrderWorkflow::label($status);
    }

    public function index(Request $request): View|RedirectResponse
    {
        $restaurant = Auth::guard('restaurant')->user();

        if (!$restaurant || !$restaurant->id) {
            return redirect()->route('restaurant.login');
        }

        $myOrders = $this->buildOrdersQuery((int) $restaurant->id, $request)
            ->with(['orderItems.menuItem:id,name', 'orderItems.optionValues', 'customerUser:id,name', 'legacyUser:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filters = [
            'status' => (string) $request->query('status', ''),
            'search' => trim((string) $request->query('search', '')),
        ];

        return view('restaurant::orders', compact('restaurant', 'myOrders', 'filters'));
    }

    public function realtime(Request $request)
    {
        $restaurant = Auth::guard('restaurant')->user();
        if (!$restaurant || !$restaurant->id) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح',
            ], 401);
        }

        $orders = $this->buildOrdersQuery((int) $restaurant->id, $request)
            ->with(['orderItems.menuItem:id,name', 'orderItems.optionValues', 'customerUser:id,name', 'legacyUser:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();

        $serialized = $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number ?: $order->id,
                'customer_name' => $order->customerUser?->name ?? $order->legacyUser?->name ?? 'عميل غير محدد',
                'items' => $order->orderItems->map(function ($item) {
                    $opts = $item->optionValues ?? collect();

                    // Serialize selected options as:
                    // [ { group_name: 'Size', values: [ { name: 'Large', extra_price: 3.00 }, ... ] }, ... ]
                    $options = [];
                    if ($opts->count() > 0) {
                        $grouped = $opts->groupBy(fn ($o) => trim((string) ($o->group_name ?? '')));
                        foreach ($grouped as $g => $rows) {
                            $g = trim((string) $g);
                            if ($g === '') {
                                continue;
                            }

                            $values = [];
                            $seen = [];
                            foreach ($rows as $row) {
                                $valueName = trim((string) ($row->value_name ?? ''));
                                if ($valueName === '' || isset($seen[$valueName])) {
                                    continue;
                                }
                                $seen[$valueName] = true;

                                $values[] = [
                                    'name' => $valueName,
                                    'extra_price' => (float) ($row->extra_price ?? 0),
                                ];
                            }

                            if (!empty($values)) {
                                $options[] = [
                                    'group_name' => $g,
                                    'values' => $values,
                                ];
                            }
                        }
                    } else {
                        // Fallback: some older schemas may store options as JSON on the order_items row.
                        $rawOptions = $item->options ?? null;
                        $decoded = null;
                        if (is_string($rawOptions)) {
                            $decoded = json_decode($rawOptions, true);
                        } elseif (is_array($rawOptions)) {
                            $decoded = $rawOptions;
                        }

                        if (is_array($decoded) && !empty($decoded)) {
                            $first = $decoded[0] ?? null;
                            $isRowShape = is_array($first)
                                && (array_key_exists('value_name', $first) || array_key_exists('value', $first) || array_key_exists('name', $first));

                            if ($isRowShape) {
                                $rows = collect($decoded);
                                $grouped = $rows->groupBy(fn ($r) => trim((string) ($r['group_name'] ?? '')));
                                foreach ($grouped as $g => $rowsGroup) {
                                    $g = trim((string) $g);
                                    if ($g === '') {
                                        continue;
                                    }

                                    $values = [];
                                    $seen = [];
                                    foreach ($rowsGroup as $r) {
                                        if (!is_array($r)) continue;
                                        $valueName = trim((string) ($r['value_name'] ?? $r['name'] ?? $r['value'] ?? ''));
                                        if ($valueName === '' || isset($seen[$valueName])) {
                                            continue;
                                        }
                                        $seen[$valueName] = true;

                                        $values[] = [
                                            'name' => $valueName,
                                            'extra_price' => (float) ($r['extra_price'] ?? 0),
                                        ];
                                    }

                                    if (!empty($values)) {
                                        $options[] = [
                                            'group_name' => $g,
                                            'values' => $values,
                                        ];
                                    }
                                }
                            } else {
                                // Group shape: [ { group_name: 'Size', values: [ { name: 'Large', extra_price: 3 } ] } ]
                                foreach ($decoded as $groupObj) {
                                    if (!is_array($groupObj)) continue;
                                    $g = trim((string) ($groupObj['group_name'] ?? $groupObj['name'] ?? ''));
                                    if ($g === '') continue;

                                    $groupValues = $groupObj['values'] ?? [];
                                    if (!is_array($groupValues)) continue;

                                    $values = [];
                                    $seen = [];
                                    foreach ($groupValues as $v) {
                                        if (!is_array($v)) continue;
                                        $valueName = trim((string) ($v['value_name'] ?? $v['name'] ?? $v['value'] ?? ''));
                                        if ($valueName === '' || isset($seen[$valueName])) {
                                            continue;
                                        }
                                        $seen[$valueName] = true;

                                        $values[] = [
                                            'name' => $valueName,
                                            'extra_price' => (float) ($v['extra_price'] ?? 0),
                                        ];
                                    }

                                    if (!empty($values)) {
                                        $options[] = [
                                            'group_name' => $g,
                                            'values' => $values,
                                        ];
                                    }
                                }
                            }
                        }
                    }

                    return [
                        'quantity' => (int) ($item->quantity ?? 1),
                        'name' => $item->name ?? $item->menuItem?->name ?? 'صنف',
                        'base_price' => (float) ($item->price ?? 0),
                        'options' => $options,
                    ];
                })->values(),
                'total_price' => (float) $order->total_price,
                'status' => $order->status,
                'status_label' => $this->statusLabel($order->status),
                'created_date' => optional($order->created_at)->format('Y-m-d'),
                'created_time' => optional($order->created_at)->format('H:i'),
                'status_update_url' => route('restaurant.orders.status', $order->id),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'orders' => $serialized,
                'summary' => [
                    'total' => $orders->count(),
                    'awaiting_accept' => $orders->where('status', OrderWorkflow::PAYMENT_VERIFIED)->count(),
                    'in_progress' => $orders->whereIn('status', [
                        OrderWorkflow::ACCEPTED_BY_RESTAURANT,
                        OrderWorkflow::PREPARING,
                        OrderWorkflow::ON_THE_WAY,
                    ])->count(),
                ],
            ],
        ]);
    }

    public function updateStatus(Request $request, int $orderId): RedirectResponse
    {
        $restaurant = Auth::guard('restaurant')->user();
        if (!$restaurant) {
            return back()->with('error', 'غير مصرح');
        }

        $request->validate([
            'status' => [
                'required',
                'in:'.OrderWorkflow::ACCEPTED_BY_RESTAURANT.','.OrderWorkflow::PREPARING,
            ],
        ]);

        $order = Order::where('id', $orderId)
            ->where('restaurant_id', $restaurant->id)
            ->first();

        if (!$order) {
            return back()->with('error', 'الطلب غير موجود');
        }

        if (! in_array($order->status, OrderWorkflow::restaurantVisibleStatuses(), true)) {
            return back()->with('error', 'الطلب غير متاح');
        }

        $nextStatus = $request->status;

        if (! OrderWorkflow::canRoleTransition(OrderWorkflow::ROLE_RESTAURANT, $order->status, $nextStatus)) {
            return back()->with('error', 'لا يمكن تحديث الحالة إلا بالترتيب: قبول الطلب ثم بدء التحضير.');
        }

        $order->update([
            'status' => $nextStatus,
        ]);

        return back()->with('success', 'تم تحديث حالة الطلب!');
    }

    private function buildOrdersQuery(int $restaurantId, Request $request): Builder
    {
        $query = Order::query()
            ->where('restaurant_id', $restaurantId)
            ->whereIn('status', OrderWorkflow::restaurantVisibleStatuses());

        $status = trim((string) $request->query('status', ''));
        if ($status !== '' && in_array($status, OrderWorkflow::restaurantVisibleStatuses(), true)) {
            $query->where('status', $status);
        }

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('order_number', 'like', '%' . $search . '%')
                    ->orWhereHas('customerUser', function (Builder $cq) use ($search): void {
                        $cq->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('legacyUser', function (Builder $lq) use ($search): void {
                        $lq->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        return $query;
    }
}
