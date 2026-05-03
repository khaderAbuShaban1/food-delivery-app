<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Order;
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

        return $user instanceof Driver ? $user : null;
    }

    private function activeStatuses(): array
    {
        return ['accepted', 'preparing', 'delivering'];
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
            ->where('status', OrderController::STATUS_PREPARING)
            ->whereNull('driver_id')
            ->with(['restaurant', 'orderItems.menuItem', 'customer'])
            ->orderByDesc('updated_at')
            ->get();

        $formatter = app(OrderController::class);

        return response()->json([
            'success' => true,
            'data' => $orders->map(static fn (Order $order) => $formatter->formatOrder($order))->values()->all(),
        ]);
    }

    /**
     * Current active assignment for this driver (MySQL).
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
            ->whereIn('status', $this->activeStatuses())
            ->with(['restaurant', 'orderItems.menuItem', 'driver', 'customer'])
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
            ->whereIn('status', $this->activeStatuses())
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

        if ($order->status !== OrderController::STATUS_PREPARING) {
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
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم قبول الطلب',
            'data' => app(OrderController::class)->formatOrder($order->fresh(['restaurant', 'orderItems.menuItem', 'driver', 'customer'])),
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
            'status' => 'required|in:delivering,completed',
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

        $newStatus = $validated['status'];
        $orderController = app(OrderController::class);

        if (!$orderController->canTransition($order->status, $newStatus)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تغيير الحالة من «'.$order->status.'» إلى «'.$newStatus.'».',
            ], 400);
        }

        $order->update(['status' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الطلب',
            'data' => app(OrderController::class)->formatOrder($order->fresh(['restaurant', 'orderItems.menuItem', 'driver', 'customer'])),
        ]);
    }
}
