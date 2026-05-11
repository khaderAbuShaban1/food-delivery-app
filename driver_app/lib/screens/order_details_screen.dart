import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/theme/app_colors.dart';
import '../models/order_model.dart';
import '../providers/order_provider.dart';
import '../widgets/order_info_row.dart';
import '../widgets/status_badge.dart';

/// Full-screen order preview before accepting (data from Laravel API).
class OrderDetailsScreen extends StatelessWidget {
  final OrderModel order;

  const OrderDetailsScreen({super.key, required this.order});

  int get itemCount => order.items.length;

  Future<void> _accept(BuildContext context) async {
    final provider = context.read<OrderProvider>();
    if (provider.activeOrder != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('لديك طلب نشط بالفعل. أكمله أولاً.'),
          behavior: SnackBarBehavior.floating,
        ),
      );
      return;
    }
    await provider.acceptOrder(order);
    if (!context.mounted) return;
    final err = provider.error;
    if (err != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(err), behavior: SnackBarBehavior.floating),
      );
      return;
    }
    if (context.mounted) Navigator.of(context).pop(true);
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final provider = context.watch<OrderProvider>();

    final blockedByActive = provider.hasActiveOrder;
    final accepting = provider.isBusy;

    return Scaffold(
      appBar: AppBar(
        title: const Text('تفاصيل الطلب'),
        actions: [
          Padding(
            padding: const EdgeInsetsDirectional.only(start: 12, end: 16),
            child: Center(child: StatusBadge(status: order.status, labelOverride: order.statusLabel)),
          ),
        ],
      ),
      body: Stack(
        children: [
          ListView(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 116),
            children: [
              Text(
                order.restaurantName,
                style: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w900),
              ),
              const SizedBox(height: 6),
              Text(order.displayOrderRef, style: theme.textTheme.bodySmall),
              const SizedBox(height: 16),
              DecoratedBox(
                decoration: BoxDecoration(
                  color: theme.colorScheme.surface,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: theme.dividerColor),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.04),
                      blurRadius: 18,
                      offset: const Offset(0, 8),
                    ),
                  ],
                ),
                child: Padding(
                  padding: const EdgeInsets.all(18),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      OrderInfoRow(
                        icon: Icons.storefront_rounded,
                        iconColor: AppColors.preparing,
                        title: order.restaurantAddress.trim().isEmpty ? 'لم يُذكر' : order.restaurantAddress,
                        subtitle: 'جهة الاستلام / المطعم',
                        maxLines: 4,
                      ),
                      Divider(height: 22, color: theme.dividerColor),
                      OrderInfoRow(
                        icon: Icons.person_outline_rounded,
                        iconColor: AppColors.pending,
                        title: order.customerName.trim().isEmpty ? '—' : order.customerName.trim(),
                        subtitle: 'اسم العميل',
                      ),
                      Divider(height: 22, color: theme.dividerColor),
                      OrderInfoRow(
                        icon: Icons.phone_outlined,
                        iconColor: AppColors.accepted,
                        title: order.customerPhone.trim().isEmpty ? '—' : order.customerPhone.trim(),
                        subtitle: 'هاتف العميل',
                      ),
                      Divider(height: 22, color: theme.dividerColor),
                      OrderInfoRow(
                        icon: Icons.location_on_rounded,
                        iconColor: AppColors.delivering,
                        title:
                            order.deliveryAddress.trim().isEmpty ? 'لم يتم تحديد العنوان' : order.deliveryAddress.trim(),
                        subtitle: 'عنوان التسليم',
                        maxLines: 4,
                      ),
                      Divider(height: 22, color: theme.dividerColor),
                      OrderInfoRow(
                        icon: Icons.payments_rounded,
                        iconColor: AppColors.accentDark,
                        title: order.totalPrice.toStringAsFixed(2),
                        subtitle: 'إجمالي الطلب',
                        emphasizeTitle: true,
                      ),
                      Divider(height: 22, color: theme.dividerColor),
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
              if (order.items.isNotEmpty) ...[
                const SizedBox(height: 14),
                Text('الأصناف', style: theme.textTheme.labelLarge),
                const SizedBox(height: 8),
                DecoratedBox(
                  decoration: BoxDecoration(
                    color: theme.colorScheme.surface,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: theme.dividerColor),
                  ),
                  child: Padding(
                    padding: const EdgeInsets.all(14),
                    child: Column(
                      children: [
                        for (final line in order.items)
                          Padding(
                            padding: const EdgeInsets.only(bottom: 10),
                            child: Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Padding(
                                  padding: const EdgeInsets.only(top: 2),
                                  child: Icon(Icons.fiber_manual_record, size: 8, color: theme.colorScheme.onSurfaceVariant),
                                ),
                                const SizedBox(width: 10),
                                Expanded(child: Text(line, style: theme.textTheme.bodyMedium)),
                              ],
                            ),
                          ),
                      ],
                    ),
                  ),
                ),
              ],
            ],
          ),
          Align(
            alignment: Alignment.bottomCenter,
            child: Material(
              elevation: 10,
              color: theme.colorScheme.surface,
              child: SafeArea(
                top: false,
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(16, 10, 16, 12),
                  child: SizedBox(
                    height: 54,
                    width: double.infinity,
                    child: FilledButton.icon(
                      onPressed: (blockedByActive || accepting) ? null : () => _accept(context),
                      style: FilledButton.styleFrom(backgroundColor: AppColors.accent),
                      icon: accepting
                          ? const SizedBox(
                              width: 22,
                              height: 22,
                              child: CircularProgressIndicator(strokeWidth: 2.2, color: Colors.white),
                            )
                          : const Icon(Icons.check_circle_outline_rounded, size: 22),
                      label: Text(blockedByActive ? 'يوجد طلب نشط حالياً' : 'قبول الطلب'),
                    ),
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
