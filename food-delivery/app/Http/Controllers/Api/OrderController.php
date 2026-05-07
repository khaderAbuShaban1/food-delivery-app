<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\MenuItem;
use App\Models\MenuItemOptionGroup;
use App\Models\MenuItemOptionValue;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemOptionValue;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\OrderWorkflow;
use App\Services\SystemSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class OrderController extends Controller
{
    private function customerColumn(): string
    {
        if (Schema::hasColumn('orders', 'customer_id')) {
            return 'customer_id';
        }

        return 'user_id';
    }

    /**
     * Resolve delivery text for a new order: explicit address row, then default saved address, then legacy users.address.
     */
    private function resolveDeliverySnapshotForCustomer(User $customer, ?int $addressId): ?string
    {
        if ($addressId !== null) {
            $row = Address::query()
                ->where('user_id', $customer->id)
                ->where('id', $addressId)
                ->first();

            return $row ? $row->formattedDeliveryLine() : null;
        }

        $default = Address::query()
            ->where('user_id', $customer->id)
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->first();

        if ($default) {
            return $default->formattedDeliveryLine();
        }

        $legacy = trim((string) ($customer->address ?? ''));

        return $legacy !== '' ? $legacy : null;
    }

    /** Display line for API (stored snapshot + fallbacks for older rows). */
    private function deliveryAddressLineForResponse(Order $order): ?string
    {
        $stored = trim((string) ($order->delivery_address ?? ''));
        if ($stored !== '') {
            return $stored;
        }

        $buyer = $order->relationLoaded('customer') ? $order->customer : $order->customer()->first();
        if (! $buyer instanceof User) {
            return null;
        }

        $legacy = trim((string) ($buyer->address ?? ''));
        if ($legacy !== '') {
            return $legacy;
        }

        $fallback = Address::query()
            ->where('user_id', $buyer->id)
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->first();

        return $fallback ? $fallback->formattedDeliveryLine() : null;
    }

    public function getStatusLabel(string $status): string
    {
        return OrderWorkflow::label($status);
    }

    public function getStatusColor(string $status): string
    {
        return OrderWorkflow::colour($status);
    }

    /** @deprecated Prefer OrderWorkflow::canRoleTransition for role-bound updates */
    public function canTransition(string $currentStatus, string $newStatus): bool
    {
        return collect(OrderWorkflow::allowedTransitionsByRole())
            ->flatten(1)
            ->contains(fn ($targets, $from) => in_array($newStatus, $targets, true));

        /*
         * Above is ambiguous; DriverOrderController used this for delivering→completed only.
         * Keep explicit map for backwards compat within driver-only transitions:
         */
    }

    public function index(Request $request): JsonResponse
    {
        $customer = $request->user();
        $customerColumn = $this->customerColumn();

        $orders = Order::where($customerColumn, $customer->id)
            ->with(['restaurant', 'orderItems.menuItem', 'orderItems.optionValues', 'paymentMethod'])
            ->latest()
            ->get()
            ->map(function ($order) {
                return $this->formatOrder($order);
            });

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var SystemSettingsService $settings */
        $settings = app(SystemSettingsService::class);
        if (!(bool) $settings->get('platform', 'platform_open', true)) {
            return response()->json([
                'success' => false,
                'message' => 'Platform is currently closed',
            ], 503);
        }

        if (!(bool) $settings->get('platform', 'orders_enabled', true)) {
            return response()->json([
                'success' => false,
                'message' => 'Orders are temporarily disabled for maintenance',
            ], 503);
        }

        if (!(bool) $settings->get('platform', 'restaurants_enabled', true)) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurants are temporarily unavailable',
            ], 503);
        }

        $customer = $request->user();
        if (! $customer instanceof User) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تسجيل الدخول كعميل لإنشاء طلب',
            ], 403);
        }

        $validated = Validator::make($request->all(), [
            'restaurant_id' => 'required|integer|exists:restaurants,id',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|integer|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.options' => 'nullable|array',
            'address_id' => [
                'required',
                'integer',
                Rule::exists('addresses', 'id')->where(fn ($q) => $q->where('user_id', $customer->id)),
            ],
            'payment_method_id' => [
                'required',
                'integer',
                Rule::exists('payment_methods', 'id')->where(fn ($q) => $q->where('is_active', true)),
            ],
            'payment_proof' => 'required|file|image|max:8192',
        ], [
            'address_id.required' => 'يجب اختيار عنوان التوصيل.',
            'payment_method_id.required' => 'يجب اختيار طريقة الدفع.',
            'payment_proof.required' => 'يجب رفع صورة إثبات الدفع.',
            'payment_proof.image' => 'ملف إثبات الدفع يجب أن يكون صورة.',
        ])->validate();

        $restaurant = Restaurant::find($validated['restaurant_id']);
        if (! $restaurant) {
            return response()->json([
                'success' => false,
                'message' => 'المطعم غير موجود',
            ], 404);
        }

        if (! $restaurant->is_open) {
            return response()->json([
                'success' => false,
                'message' => 'المطعم مغلق حالياً',
            ], 400);
        }

        $itemIds = array_map(static fn (array $row) => (int) $row['menu_item_id'], $validated['items']);
        $uniqueIds = array_values(array_unique($itemIds));

        $menuItems = MenuItem::query()
            ->where('restaurant_id', (int) $validated['restaurant_id'])
            ->whereIn('id', $uniqueIds)
            ->get()
            ->keyBy('id');

        if ($menuItems->count() !== count($uniqueIds)) {
            return response()->json([
                'success' => false,
                'message' => 'أحد الأصناف غير صالح أو لا يتبع هذا المطعم',
                'errors' => [
                    'items' => ['تأكد أن جميع الأصناف من قائمة المطعم الحالي'],
                ],
            ], 422);
        }

        $addressId = (int) $validated['address_id'];

        $deliverySnapshot = $this->resolveDeliverySnapshotForCustomer($customer, $addressId);
        if ($deliverySnapshot === null || trim($deliverySnapshot) === '') {
            return response()->json([
                'success' => false,
                'message' => 'أضف عنوان توصيلاً قبل تأكيد الطلب',
                'errors' => [
                    'address_id' => ['يرجى اختيار أو إضافة عنوان توصيل من الملف الشخصي'],
                ],
            ], 422);
        }

        $paymentProofPath = $request->file('payment_proof')->store('payment-proofs', 'public');
        $paymentMethodId = (int) $validated['payment_method_id'];

        try {
            $order = DB::transaction(function () use ($validated, $menuItems, $customer, $deliverySnapshot, $paymentProofPath, $paymentMethodId) {
                $totalPrice = 0.0;
                $orderItems = [];
                $orderItemOptions = [];

                foreach ($validated['items'] as $item) {
                    $menuItemId = (int) $item['menu_item_id'];
                    $menuItem = $menuItems->get($menuItemId);
                    if (! $menuItem) {
                        throw new \RuntimeException('Menu item missing after validation');
                    }
                    $qty = (int) $item['quantity'];

                    $optionsByGroup = $item['options'] ?? [];
                    $selectedValueIds = [];
                    $selectedByGroup = [];
                    if (is_array($optionsByGroup)) {
                        foreach ($optionsByGroup as $gid => $valueIds) {
                            $groupId = (int) $gid;
                            $ids = [];
                            if (is_array($valueIds)) {
                                foreach ($valueIds as $raw) {
                                    $vid = (int) $raw;
                                    if ($vid > 0) {
                                        $ids[] = $vid;
                                    }
                                }
                            }
                            $ids = array_values(array_unique($ids));
                            sort($ids);
                            if (!empty($ids)) {
                                $selectedByGroup[$groupId] = $ids;
                                $selectedValueIds = array_merge($selectedValueIds, $ids);
                            }
                        }
                    }
                    $selectedValueIds = array_values(array_unique($selectedValueIds));

                    $extraPerUnit = 0.0;
                    $optionRows = [];

                    if (!empty($selectedValueIds) && Schema::hasTable('menu_item_option_groups') && Schema::hasTable('menu_item_option_values')) {
                        /** @var \Illuminate\Support\Collection<int, MenuItemOptionGroup> $groups */
                        $groups = MenuItemOptionGroup::query()
                            ->where('menu_item_id', $menuItemId)
                            ->get()
                            ->keyBy('id');

                        /** @var \Illuminate\Support\Collection<int, MenuItemOptionValue> $values */
                        $values = MenuItemOptionValue::query()
                            ->whereIn('id', $selectedValueIds)
                            ->with('group')
                            ->get()
                            ->keyBy('id');

                        // Validate: all requested groups belong to this menu item and satisfy single/multiple rules.
                        foreach ($selectedByGroup as $groupId => $ids) {
                            $group = $groups->get($groupId);
                            if (!$group) {
                                throw new \RuntimeException('Invalid option group for menu item');
                            }
                            if ($group->selection_type === 'single' && count($ids) > 1) {
                                throw new \RuntimeException('Single-select group has multiple values');
                            }
                            foreach ($ids as $vid) {
                                $val = $values->get((int) $vid);
                                if (!$val || (int) $val->option_group_id !== (int) $groupId) {
                                    throw new \RuntimeException('Invalid option value for option group');
                                }
                                $extra = (float) ($val->extra_price ?? 0);
                                $extraPerUnit += $extra;
                                $optionRows[] = [
                                    'option_group_id' => (int) $groupId,
                                    'option_value_id' => (int) $val->id,
                                    'group_name' => $group->name,
                                    'value_name' => $val->name,
                                    'extra_price' => $extra,
                                ];
                            }
                        }
                    }

                    $unitBase = (float) $menuItem->price;
                    $lineTotal = ($unitBase + $extraPerUnit) * $qty;
                    $totalPrice += $lineTotal;
                    $orderItems[] = [
                        'menu_item_id' => $menuItemId,
                        'quantity' => $qty,
                        'price' => $menuItem->price, // base unit price snapshot
                    ];
                    $orderItemOptions[] = $optionRows;
                }

                $orderNumber = Order::generateOrderNumber();
                if (Schema::hasColumn('orders', 'order_number')) {
                    while (Order::query()->where('order_number', $orderNumber)->exists()) {
                        $orderNumber = Order::generateOrderNumber();
                    }
                }

                $createData = [
                    $this->customerColumn() => $customer->id,
                    'restaurant_id' => (int) $validated['restaurant_id'],
                    'total_price' => $totalPrice,
                    'status' => OrderWorkflow::PENDING_PAYMENT_VERIFICATION,
                ];
                if (Schema::hasColumn('orders', 'order_number')) {
                    $createData['order_number'] = $orderNumber;
                }
                if (Schema::hasColumn('orders', 'delivery_address')) {
                    $createData['delivery_address'] = $deliverySnapshot;
                }
                $createData['payment_proof'] = $paymentProofPath;
                if (Schema::hasColumn('orders', 'payment_method_id')) {
                    $createData['payment_method_id'] = $paymentMethodId;
                }

                $order = Order::create($createData);

                foreach ($orderItems as $idx => $orderItem) {
                    $oi = OrderItem::create([
                        'order_id' => $order->id,
                        'menu_item_id' => $orderItem['menu_item_id'],
                        'quantity' => $orderItem['quantity'],
                        'price' => $orderItem['price'],
                    ]);

                    $optionRows = $orderItemOptions[$idx] ?? [];
                    if (!empty($optionRows)) {
                        foreach ($optionRows as $row) {
                            $row['order_item_id'] = $oi->id;
                            OrderItemOptionValue::create($row);
                        }
                    }
                }

                return $order->load(['orderItems.menuItem', 'orderItems.optionValues', 'restaurant', 'customer', 'paymentMethod']);
            });
        } catch (Throwable $e) {
            // Option validation errors are client-side problems.
            $msg = $e->getMessage();
            if (str_contains($msg, 'Invalid option') || str_contains($msg, 'Single-select group')) {
                return response()->json([
                    'success' => false,
                    'message' => 'خيارات الطلب غير صالحة. حدّث الصفحة وأعد الاختيار.',
                ], 422);
            }

            report($e);

            return response()->json([
                'success' => false,
                'message' => config('app.debug')
                    ? 'Order save failed: '.$e->getMessage()
                    : 'تعذر حفظ الطلب في الخادم. حاول لاحقاً.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الطلب بنجاح وبانتظار تحقق الدفع من الإدارة',
            'data' => $this->formatOrder($order),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $customerColumn = $this->customerColumn();
        $order = Order::with(['restaurant', 'driver', 'orderItems.menuItem', 'orderItems.optionValues', 'paymentMethod'])
            ->where($customerColumn, $request->user()->id)
            ->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatOrder($order),
        ]);
    }

    /**
     * Restaurant token (Flutter) — verified-payment orders only.
     */
    public function restaurantOrders(Request $request): JsonResponse
    {
        $restaurantId = $request->user()?->restaurant_id ?? $request->query('restaurant_id');

        if (!$restaurantId) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant ID required',
            ], 400);
        }

        $status = $request->query('status');

        $query = Order::where('restaurant_id', $restaurantId)
            ->whereIn('status', OrderWorkflow::restaurantVisibleStatuses())
            ->with(['orderItems.menuItem', 'orderItems.optionValues', 'customer']);

        if ($status) {
            if (! in_array($status, OrderWorkflow::restaurantVisibleStatuses(), true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status filter for restaurant',
                ], 422);
            }
            $query->where('status', $status);
        }

        $orders = $query->latest()->get()->map(function ($order) {
            return $this->formatOrder($order);
        });

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    public function restaurantUpdateStatus(Request $request, int $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود',
            ], 404);
        }

        $restaurantId = $request->user()?->restaurant_id;
        if ((int) $order->restaurant_id !== (int) $restaurantId) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح',
            ], 403);
        }

        if (! in_array($order->status, OrderWorkflow::restaurantVisibleStatuses(), true)) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير متاح لهذا المطعم بعد',
            ], 403);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in([
                OrderWorkflow::ACCEPTED_BY_RESTAURANT,
                OrderWorkflow::PREPARING,
            ])],
        ]);

        $newStatus = $validated['status'];

        if (! OrderWorkflow::canRoleTransition(OrderWorkflow::ROLE_RESTAURANT, $order->status, $newStatus)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تغيير الحالة إلا بالترتيب المحدد (قبول ثم تحضير).',
            ], 400);
        }

        $order->update(['status' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الطلب بنجاح',
            'data' => $this->formatOrder($order->load(['orderItems.menuItem', 'orderItems.optionValues'])),
        ]);
    }

    public function assignDriver(Request $request, int $id): JsonResponse
    {
        $order = Order::find($id);

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        $validated = $request->validate([
            'driver_id' => 'required|exists:drivers,id',
        ]);

        $driver = \App\Models\Driver::find($validated['driver_id']);

        if (! $driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver not found',
            ], 404);
        }

        $order->update(['driver_id' => $validated['driver_id']]);

        return response()->json([
            'success' => true,
            'message' => 'Driver assigned successfully',
            'data' => $this->formatOrder($order->load('driver')),
        ]);
    }

    public function formatOrder($order): array
    {
        $buyer = $order->customer;
        $deliveryLine = $this->deliveryAddressLineForResponse($order);

        $proofUrl = null;
        if ($order->payment_proof) {
            $proofUrl = Storage::disk('public')->url($order->payment_proof);
        }

        $pm = $order->paymentMethod ?? null;

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'restaurant_id' => $order->restaurant_id,
            'payment_method_id' => $order->payment_method_id,
            'payment_method' => $pm ? [
                'id' => $pm->id,
                'type' => $pm->type->value,
                'type_label' => $pm->type->label(),
                'subtype_name' => $pm->subtypeLabel(),
                'account_holder_name' => $pm->account_holder_name,
                'account_number' => $pm->account_number,
                'phone_number' => $pm->phone_number,
            ] : null,
            'delivery_address' => $deliveryLine,
            'payment_proof_url' => $proofUrl,
            'payment_proof' => $order->payment_proof,
            'payment_reference' => $order->payment_reference,
            'payment_verified_at' => $order->payment_verified_at,
            'verified_by_admin_id' => $order->verified_by_admin_id,
            'restaurant' => $order->restaurant ? [
                'id' => $order->restaurant->id,
                'name' => $order->restaurant->name,
                'image' => $order->restaurant->image,
                'phone' => $order->restaurant->phone ?? null,
            ] : null,
            'customer' => $buyer ? [
                'id' => $buyer->id,
                'name' => $buyer->name,
                'phone' => $buyer->phone ?? $buyer->email,
                'address' => $deliveryLine,
            ] : null,
            'driver_id' => $order->driver_id,
            'assigned_at' => $order->assigned_at,
            'driver' => $order->driver ? [
                'id' => $order->driver->id,
                'name' => $order->driver->name,
            ] : null,
            'total_price' => (float) $order->total_price,
            'status' => $order->status,
            'status_label' => $this->getStatusLabel($order->status),
            'status_color' => $this->getStatusColor($order->status),
            'items' => $order->orderItems->map(function ($item) {
                $options = $item->relationLoaded('optionValues') ? $item->optionValues : $item->optionValues()->get();
                $optionsTotal = (float) $options->sum(fn ($o) => (float) ($o->extra_price ?? 0));
                $baseUnit = (float) $item->price;
                $unit = $baseUnit + $optionsTotal;
                return [
                    'id' => $item->id,
                    'menu_item_id' => $item->menu_item_id,
                    'name' => $item->menuItem?->name,
                    'price' => $unit,
                    'base_price' => $baseUnit,
                    'options_total' => $optionsTotal,
                    'quantity' => $item->quantity,
                    'options' => $options->map(static fn ($o) => [
                        'option_group_id' => (int) $o->option_group_id,
                        'option_value_id' => (int) $o->option_value_id,
                        'group_name' => $o->group_name,
                        'value_name' => $o->value_name,
                        'extra_price' => (float) ($o->extra_price ?? 0),
                    ])->values()->all(),
                ];
            }),
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
        ];
    }
}
