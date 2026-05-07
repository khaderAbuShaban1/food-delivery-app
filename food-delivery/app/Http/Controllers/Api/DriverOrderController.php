<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Order;
use App\Services\OrderWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Driver-facing order actions. MySQL is the source of truth for the driver app.
 */
class DriverOrderController extends Controller
{
    private function ensureDriver(Request $request): ?Driver
    {
        $user = $request->user();

        return $user instanceof Driver && $user->isApproved() ? $user : null;
    }

    /**
     * Available pool: preparing + unassigned (MySQL).
     */
    public function availablePool(Request $request): JsonResponse
    {
        if (!$this->ensureDriver($request)) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح',
            ], 403);
        }

        $orders = Order::query()
            ->whereIn('status', OrderWorkflow::preparingPoolStatuses())
            ->whereNull('driver_id')
            ->with(['restaurant', 'orderItems.menuItem', 'orderItems.optionValues', 'customer'])
            ->orderByDesc('updated_at')
            ->get();

        $formatter = app(OrderController::class);

        return response()->json([
            'success' => true,
            'data' => $orders->map(static fn (Order $order) => $formatter->formatOrder($order))->values()->all(),
        ]);
    }

    /**
     * Current active assignment for this driver (MySQL):
     * assigned waiting pickup, on the way, or post-pickup until delivered.
     */
    public function activeOrder(Request $request): JsonResponse
    {
        $driver = $this->ensureDriver($request);
        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح',
            ], 403);
        }

        $order = Order::query()
            ->where('driver_id', $driver->id)
            ->whereNotIn('status', [
                OrderWorkflow::DELIVERED,
                OrderWorkflow::PAYMENT_REJECTED,
            ])
            ->with(['restaurant', 'orderItems.menuItem', 'orderItems.optionValues', 'driver', 'customer'])
            ->orderByDesc('updated_at')
            ->first();

        $formatter = app(OrderController::class);

        return response()->json([
            'success' => true,
            'data' => $order ? $formatter->formatOrder($order) : null,
        ]);
    }

    public function accept(Request $request, int $id): JsonResponse
    {
        $driver = $this->ensureDriver($request);
        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح',
            ], 403);
        }

        $hasActive = Order::query()
            ->where('driver_id', $driver->id)
            ->whereNotIn('status', [
                OrderWorkflow::DELIVERED,
                OrderWorkflow::PAYMENT_REJECTED,
            ])
            ->where('id', '!=', $id)
            ->exists();

        if ($hasActive) {
            return response()->json([
                'success' => false,
                'message' => 'لديك طلب نشط. أكمله قبل قبول طلب آخر.',
            ], 422);
        }

        $order = Order::find($id);
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود',
            ], 404);
        }

        if (! in_array($order->status, OrderWorkflow::preparingPoolStatuses(), true)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن قبول هذا الطلب (الحالة ليست قيد التحضير).',
            ], 400);
        }

        if ($order->driver_id !== null && (int) $order->driver_id !== 0) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب مُعيَّن لسائق آخر.',
            ], 409);
        }

        $order->update([
            'driver_id' => $driver->id,
            'assigned_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تعيين الطلب لك.',
            'data' => app(OrderController::class)->formatOrder($order->fresh(['restaurant', 'orderItems.menuItem', 'orderItems.optionValues', 'driver', 'customer'])),
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $driver = $this->ensureDriver($request);
        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح',
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:picked_up,on_the_way,delivered',
        ]);

        $order = Order::find($id);
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود',
            ], 404);
        }

        if ((int) $order->driver_id !== (int) $driver->id) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الطلب غير مخصص لك',
            ], 403);
        }

        $requestedStatus = (string) $validated['status'];
        $newStatus = $requestedStatus === 'picked_up'
            ? OrderWorkflow::ON_THE_WAY
            : $requestedStatus;

        if ($newStatus === OrderWorkflow::ON_THE_WAY && $order->status !== OrderWorkflow::PREPARING) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن بدء التوصيل إلا عندما تكون حالة الطلب «قيد التحضير».',
            ], 400);
        }

        if (! OrderWorkflow::canRoleTransition(OrderWorkflow::ROLE_DRIVER, $order->status, $newStatus)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تغيير الحالة من «'.$order->status.'» إلى «'.$newStatus.'».',
            ], 400);
        }

        $order->update(['status' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الطلب',
            'data' => app(OrderController::class)->formatOrder($order->fresh(['restaurant', 'orderItems.menuItem', 'orderItems.optionValues', 'driver', 'customer'])),
        ]);
    }

    /**
     * Driver "today" stats computed from MySQL (no local app state).
     * Counts orders delivered today based on server date (updated_at).
     */
    public function todayStats(Request $request): JsonResponse
    {
        $driver = $this->ensureDriver($request);
        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح',
            ], 403);
        }

        $todayStart = now()->startOfDay();

        $deliveredToday = (int) Order::query()
            ->where('driver_id', $driver->id)
            ->whereIn('status', [OrderWorkflow::DELIVERED, 'completed'])
            ->where('updated_at', '>=', $todayStart)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'delivered_today' => $deliveredToday,
            ],
        ]);
    }

    /**
     * Read-only history for the authenticated driver (MySQL).
     * Returns only completed/delivered orders using the same formatter payload.
     */
    public function history(Request $request): JsonResponse
    {
        $driver = $this->ensureDriver($request);
        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح',
            ], 403);
        }

        $orders = Order::query()
            ->where('driver_id', $driver->id)
            ->whereIn('status', [OrderWorkflow::DELIVERED, 'completed'])
            ->with(['restaurant', 'orderItems.menuItem', 'orderItems.optionValues', 'driver', 'customer'])
            ->orderByDesc('updated_at')
            ->limit(200)
            ->get();

        $formatter = app(OrderController::class);

        return response()->json([
            'success' => true,
            'data' => $orders->map(static fn (Order $order) => $formatter->formatOrder($order))->values()->all(),
        ]);
    }
}
