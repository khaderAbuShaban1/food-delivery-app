import 'package:flutter/material.dart';

import '../core/theme/app_colors.dart';
import '../models/order_model.dart';
import 'order_info_row.dart';
import 'status_badge.dart';

String shortDeliveryAddress(String full, {int maxChars = 56}) {
  final t = full.trim();
  if (t.isEmpty) return 'لم يتم تحديد العنوان';
  if (t.length <= maxChars) return t;
  return '${t.substring(0, maxChars).trim()}…';
}

/// Pool card — opens details on tap (no inline accept).
class DriverOrderCard extends StatelessWidget {
  final OrderModel order;
  final VoidCallback? onTap;
  /// Highlights the card as most recent incoming (accent strip + chip).
  final bool highlightAsNew;

  const DriverOrderCard({
    super.key,
    required this.order,
    this.onTap,
    this.highlightAsNew = false,
  });

  int get itemCount => order.items.length;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final address = shortDeliveryAddress(order.deliveryAddress);
    final distanceText =
        order.distanceKm == null ? null : 'تقريباً ${order.distanceKm!.toStringAsFixed(1)} كم';
    final custRaw = order.customerInfoLine.trim();
    final customerTitle = custRaw.isEmpty
        ? 'غير متوفر'
        : (custRaw.length <= 48 ? custRaw : '${custRaw.substring(0, 48).trim()}…');

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        splashColor: AppColors.accent.withValues(alpha: 0.08),
        highlightColor: AppColors.accent.withValues(alpha: 0.04),
        child: Ink(
          decoration: BoxDecoration(
            color: highlightAsNew ? AppColors.incomingGlow : AppColors.surface,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(
              color: highlightAsNew ? AppColors.accent.withValues(alpha: 0.35) : AppColors.borderSubtle,
              width: highlightAsNew ? 1.5 : 1,
            ),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: highlightAsNew ? 0.08 : 0.04),
                blurRadius: highlightAsNew ? 22 : 18,
                offset: const Offset(0, 8),
              ),
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.02),
                blurRadius: 2,
                offset: const Offset(0, 1),
              ),
            ],
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(12),
            child: IntrinsicHeight(
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  if (highlightAsNew)
                    Container(
                      width: 5,
                      decoration: const BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.topCenter,
                          end: Alignment.bottomCenter,
                          colors: [AppColors.accent, AppColors.accentDark],
                        ),
                      ),
                    ),
                  Expanded(
                    child: Padding(
                      padding: const EdgeInsets.fromLTRB(18, 18, 18, 18),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          if (highlightAsNew) ...[
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                              decoration: BoxDecoration(
                                color: AppColors.accent.withValues(alpha: 0.15),
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: Text(
                                'جديد',
                                style: theme.textTheme.labelSmall?.copyWith(
                                  color: AppColors.accentDark,
                                  fontWeight: FontWeight.w800,
                                  letterSpacing: 0.3,
                                ),
                              ),
                            ),
                            const SizedBox(height: 8),
                          ],
                          Text(
                            order.restaurantName,
                            style: theme.textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.w900,
                              height: 1.15,
                            ),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                          const SizedBox(height: 6),
                          Text(
                            order.displayOrderRef,
                            style: theme.textTheme.bodySmall?.copyWith(
                              fontWeight: FontWeight.w600,
                              color: AppColors.textSecondary,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 10),
                    StatusBadge(status: order.status, labelOverride: order.statusLabel),
                  ],
                ),
                const SizedBox(height: 16),
                DecoratedBox(
                  decoration: BoxDecoration(
                    color: AppColors.surfaceMuted,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: AppColors.borderSubtle.withValues(alpha: 0.9)),
                  ),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
                    child: Column(
                      children: [
                        OrderInfoRow(
                          icon: Icons.payments_rounded,
                          iconColor: AppColors.accentDark,
                          title: order.totalPrice.toStringAsFixed(2),
                          subtitle: 'إجمالي الطلب',
                          emphasizeTitle: true,
                        ),
                        const Divider(height: 22, color: AppColors.borderSubtle),
                        OrderInfoRow(
                          icon: Icons.person_outline_rounded,
                          iconColor: AppColors.pending,
                          title: customerTitle,
                          subtitle: 'معلومات العميل',
                          maxLines: 2,
                        ),
                        const Divider(height: 22, color: AppColors.borderSubtle),
                        OrderInfoRow(
                          icon: Icons.location_on_rounded,
                          iconColor: AppColors.delivering,
                          title: address,
                          subtitle: 'وجهة التسليم',
                          maxLines: 2,
                        ),
                        const Divider(height: 22, color: AppColors.borderSubtle),
                        OrderInfoRow(
                          icon: Icons.shopping_bag_outlined,
                          iconColor: AppColors.accepted,
                          title: '$itemCount',
                          subtitle: itemCount == 0
                              ? 'لم تُحمّل تفاصيل الأصناف'
                              : (itemCount == 1 ? 'صنف' : 'أصناف'),
                        ),
                      ],
                    ),
                  ),
                ),
                if (distanceText != null) ...[
                  const SizedBox(height: 10),
                  Text(
                    distanceText,
                    style: theme.textTheme.bodySmall?.copyWith(color: AppColors.textMuted),
                  ),
                ],
                const SizedBox(height: 14),
                SizedBox(
                  width: double.infinity,
                  height: 44,
                  child: FilledButton.icon(
                    onPressed: onTap,
                    style: FilledButton.styleFrom(
                      backgroundColor: AppColors.accent,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      padding: const EdgeInsets.symmetric(horizontal: 14),
                      textStyle: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    icon: const Icon(Icons.check_circle_outline_rounded, size: 20),
                    label: const Text('قبول الطلب'),
                  ),
                ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
