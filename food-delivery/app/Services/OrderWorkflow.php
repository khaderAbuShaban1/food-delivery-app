<?php

namespace App\Services;

/**
 * Central rules for verified-payment-first order fulfilment.
 * All status changes must validate against these transitions (backend only).
 */
final class OrderWorkflow
{
    public const PENDING_PAYMENT_VERIFICATION = 'pending_payment_verification';

    public const PAYMENT_VERIFIED = 'payment_verified';

    public const PAYMENT_REJECTED = 'payment_rejected';

    public const ACCEPTED_BY_RESTAURANT = 'accepted_by_restaurant';

    public const PREPARING = 'preparing';

    public const ON_THE_WAY = 'on_the_way';

    public const DELIVERED = 'delivered';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_RESTAURANT = 'restaurant';

    public const ROLE_DRIVER = 'driver';

    /** @return string[] */
    public static function allStatuses(): array
    {
        return [
            self::PENDING_PAYMENT_VERIFICATION,
            self::PAYMENT_VERIFIED,
            self::PAYMENT_REJECTED,
            self::ACCEPTED_BY_RESTAURANT,
            self::PREPARING,
            self::ON_THE_WAY,
            self::DELIVERED,
        ];
    }

    /** Visible to authenticated restaurant dashboard / API once admin verified payment */
    /** @return string[] */
    public static function restaurantVisibleStatuses(): array
    {
        return [
            self::PAYMENT_VERIFIED,
            self::ACCEPTED_BY_RESTAURANT,
            self::PREPARING,
            self::ON_THE_WAY,
            self::DELIVERED,
        ];
    }

    /** @return array<string, string[]> */
    public static function allowedTransitionsByRole(): array
    {
        return [
            self::ROLE_ADMIN => [
                self::PENDING_PAYMENT_VERIFICATION => [self::PAYMENT_VERIFIED, self::PAYMENT_REJECTED],
            ],
            self::ROLE_RESTAURANT => [
                self::PAYMENT_VERIFIED => [self::ACCEPTED_BY_RESTAURANT],
                self::ACCEPTED_BY_RESTAURANT => [self::PREPARING],
            ],
            self::ROLE_DRIVER => [
                self::PREPARING => [self::ON_THE_WAY],
                self::ON_THE_WAY => [self::DELIVERED],
            ],
        ];
    }

    /** Driver pulls from pool at preparing (unassigned). */
    public static function preparingPoolStatuses(): array
    {
        return [self::PREPARING];
    }

    /** @return array<string, string[]> */
    public static function arabicLabels(): array
    {
        return [
            self::PENDING_PAYMENT_VERIFICATION => 'بانتظار تحقق الدفع',
            self::PAYMENT_VERIFIED => 'تم التحقق من الدفع',
            self::PAYMENT_REJECTED => 'رفض الدفع',
            self::ACCEPTED_BY_RESTAURANT => 'مقبول من المطعم',
            self::PREPARING => 'قيد التحضير',
            self::ON_THE_WAY => 'في الطريق',
            self::DELIVERED => 'تم التسليم',
        ];
    }

    /** @return array<string, string> status => hex colour */
    public static function statusColors(): array
    {
        return [
            self::PENDING_PAYMENT_VERIFICATION => '#6B6B6B',
            self::PAYMENT_VERIFIED => '#2563EB',
            self::PAYMENT_REJECTED => '#DC2626',
            self::ACCEPTED_BY_RESTAURANT => '#0891B2',
            self::PREPARING => '#FF7A30',
            self::ON_THE_WAY => '#9B59B6',
            self::DELIVERED => '#2ECC71',
        ];
    }

    public static function label(string $status): string
    {
        return self::arabicLabels()[$status] ?? $status;
    }

    public static function colour(string $status): string
    {
        return self::statusColors()[$status] ?? '#6B6B6B';
    }

    public static function canRoleTransition(string $role, string $from, string $to): bool
    {
        if ($from === $to) {
            return false;
        }

        $map = self::allowedTransitionsByRole()[$role] ?? [];

        return in_array($to, $map[$from] ?? [], true);
    }

    public static function isTerminal(?string $status): bool
    {
        return in_array($status, [self::PAYMENT_REJECTED, self::DELIVERED], true);
    }

    /** @param array<string, int> $countsByStatus */
    /** @return array<string, array{label:string,value:int,width_percent:float}> */
    public static function adminDashboardProgressRows(array $countsByStatus, int $totalOrders): array
    {
        $total = max($totalOrders, 1);

        $rows = [
            'verification_queue' => [
                'label' => 'بانتظار تحقق الدفع',
                'value' => (int) ($countsByStatus[self::PENDING_PAYMENT_VERIFICATION] ?? 0),
            ],
            'verified_ready' => [
                'label' => 'معتمد — جاهز للمطعم',
                'value' => (int) ($countsByStatus[self::PAYMENT_VERIFIED] ?? 0),
            ],
            'kitchen' => [
                'label' => 'المطبخ والقبول',
                'value' => (int) (($countsByStatus[self::ACCEPTED_BY_RESTAURANT] ?? 0)
                    + ($countsByStatus[self::PREPARING] ?? 0)),
            ],
            'delivery' => [
                'label' => 'بالطريق',
                'value' => (int) ($countsByStatus[self::ON_THE_WAY] ?? 0),
            ],
            'delivered_done' => [
                'label' => 'تم التسليم',
                'value' => (int) ($countsByStatus[self::DELIVERED] ?? 0),
            ],
            'rejected' => [
                'label' => 'رفض الدفع',
                'value' => (int) ($countsByStatus[self::PAYMENT_REJECTED] ?? 0),
            ],
        ];

        foreach ($rows as $key => $row) {
            $rows[$key]['width_percent'] = ($row['value'] / $total) * 100.0;
        }

        return $rows;
    }
}
