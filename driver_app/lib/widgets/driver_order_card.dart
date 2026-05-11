import 'package:flutter/material.dart';

import '../core/theme/app_colors.dart';
import '../models/order_model.dart';
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
    final isDark = theme.brightness == Brightness.dark;
    final address = shortDeliveryAddress(order.deliveryAddress);
    final distanceText =
        order.distanceKm == null ? null : 'تقريباً ${order.distanceKm!.toStringAsFixed(1)} كم';

    final priceText = order.totalPrice.toStringAsFixed(2);
    final restaurantAddress = order.restaurantAddress.trim();
    final canShowStatus = (order.statusLabel ?? '').trim().isNotEmpty || order.status.trim().isNotEmpty;

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        splashColor: Theme.of(context).colorScheme.primary.withValues(alpha: 0.08),
        highlightColor: Theme.of(context).colorScheme.primary.withValues(alpha: 0.04),
        child: Ink(
          decoration: BoxDecoration(
            color: highlightAsNew
                ? (isDark
                    ? Theme.of(context).colorScheme.surfaceContainerHighest
                    : AppColors.incomingGlow)
                : Theme.of(context).colorScheme.surface,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: highlightAsNew
                  ? Theme.of(context).colorScheme.primary.withValues(alpha: 0.35)
                  : Theme.of(context).dividerColor,
              width: highlightAsNew ? 1.5 : 1,
            ),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: highlightAsNew ? 0.08 : 0.04),
                blurRadius: highlightAsNew ? 20 : 16,
                offset: const Offset(0, 10),
              ),
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.02),
                blurRadius: 2,
                offset: const Offset(0, 1),
              ),
            ],
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(16),
            child: IntrinsicHeight(
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  if (highlightAsNew)
                    Container(
                      width: 5,
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.topCenter,
                          end: Alignment.bottomCenter,
                          colors: [
                            Theme.of(context).colorScheme.primary,
                            Theme.of(context).colorScheme.secondary,
                          ],
                        ),
                      ),
                    ),
                  Expanded(
                    child: Padding(
                      padding: const EdgeInsets.fromLTRB(16, 16, 16, 16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                        // TOP ROW: Restaurant + Price
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  if (highlightAsNew) ...[
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                      decoration: BoxDecoration(
                                        color: AppColors.accent.withValues(alpha: 0.14),
                                        borderRadius: BorderRadius.circular(999),
                                      ),
                                      child: Text(
                                        'جديد',
                                        style: theme.textTheme.labelSmall?.copyWith(
                                          color: Theme.of(context).colorScheme.secondary,
                                          fontWeight: FontWeight.w900,
                                          letterSpacing: 0.2,
                                        ),
                                      ),
                                    ),
                                    const SizedBox(height: 10),
                                  ],
                                  Text(
                                    order.restaurantName,
                                    style: theme.textTheme.titleMedium?.copyWith(
                                      fontWeight: FontWeight.w900,
                                      height: 1.15,
                                    ),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                  if (restaurantAddress.isNotEmpty) ...[
                                    const SizedBox(height: 4),
                                    Text(
                                      restaurantAddress,
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: theme.textTheme.bodySmall?.copyWith(
                                        color: Theme.of(context).colorScheme.onSurfaceVariant,
                                        fontWeight: FontWeight.w700,
                                        height: 1.1,
                                      ),
                                    ),
                                  ],
                                ],
                              ),
                            ),
                            const SizedBox(width: 12),
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.end,
                              children: [
                                Text(
                                  '₪ $priceText',
                                  style: theme.textTheme.titleMedium?.copyWith(
                                    fontWeight: FontWeight.w900,
                                    color: Theme.of(context).colorScheme.secondary,
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),

                        // OPTIONAL: small status badge
                        if (canShowStatus) ...[
                          Align(
                            alignment: AlignmentDirectional.centerStart,
                            child: StatusBadge(
                              status: order.status,
                              labelOverride: order.statusLabel,
                            ),
                          ),
                          const SizedBox(height: 10),
                        ],

                        // MIDDLE: Location + distance/time
                        _CompactInfoRow(
                          icon: Icons.location_on_rounded,
                          iconColor: AppColors.delivering,
                          text: address,
                        ),
                        if (distanceText != null) ...[
                          const SizedBox(height: 8),
                          _CompactInfoRow(
                            icon: Icons.schedule_rounded,
                            iconColor: Theme.of(context).colorScheme.onSurfaceVariant,
                            text: distanceText,
                          ),
                        ],

                        const SizedBox(height: 14),
                        // BOTTOM: decision buttons
                        SizedBox(
                          width: double.infinity,
                          height: 48,
                          child: FilledButton.icon(
                            onPressed: onTap,
                            style: FilledButton.styleFrom(
                              backgroundColor: Theme.of(context).colorScheme.primary,
                              foregroundColor: Colors.white,
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                              padding: const EdgeInsets.symmetric(horizontal: 14),
                              textStyle: const TextStyle(fontSize: 14.5, fontWeight: FontWeight.w900),
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

class _CompactInfoRow extends StatelessWidget {
  final IconData icon;
  final Color iconColor;
  final String text;

  const _CompactInfoRow({
    required this.icon,
    required this.iconColor,
    required this.text,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 34,
          height: 34,
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.surfaceContainerHighest,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: Theme.of(context).dividerColor.withValues(alpha: 0.9)),
          ),
          child: Icon(icon, size: 18, color: iconColor),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Text(
            text,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: theme.textTheme.bodyMedium?.copyWith(
              color: Theme.of(context).colorScheme.onSurface,
              fontWeight: FontWeight.w700,
              height: 1.2,
            ),
          ),
        ),
      ],
    );
  }
}
