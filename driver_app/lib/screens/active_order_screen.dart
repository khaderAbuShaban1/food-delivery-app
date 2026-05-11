import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/theme/app_colors.dart';
import '../models/order_model.dart';
import '../providers/order_provider.dart';
import '../widgets/order_empty_state.dart';
import '../widgets/order_info_row.dart';
import '../widgets/order_list_skeleton.dart';
import '../widgets/status_badge.dart';

class ActiveOrderScreen extends StatelessWidget {
  const ActiveOrderScreen({super.key});

  Future<void> _updateStatus(BuildContext context, String status) async {
    final provider = context.read<OrderProvider>();
    await provider.updateStatus(status);
    if (!context.mounted) return;
    final err = provider.error;
    if (err != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(err),
          behavior: SnackBarBehavior.floating,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<OrderProvider>();

    if (!provider.hasSyncedActiveOrder) {
      return Scaffold(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        appBar: AppBar(
          elevation: 0,
          scrolledUnderElevation: 0,
          backgroundColor: Theme.of(context).scaffoldBackgroundColor,
          title: const Text('الطلب النشط'),
        ),
        body: const Padding(
          padding: EdgeInsets.all(24),
          child: InlineLoadingPulse(message: 'جاري مزامنة الطلب…'),
        ),
      );
    }

    final order = provider.activeOrder;
    if (order == null) {
      return Scaffold(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        appBar: AppBar(
          elevation: 0,
          scrolledUnderElevation: 0,
          backgroundColor: Theme.of(context).scaffoldBackgroundColor,
          title: const Text('الطلب النشط'),
        ),
        body: const OrderEmptyState(
          title: 'لا يوجد طلب نشط',
          subtitle: 'بعد قبول طلب من «المتاحة» ستظهر مراحل التوصيل والإجراءات هنا.',
          icon: Icons.local_shipping_outlined,
        ),
      );
    }

    final normalized = order.driverStageStatus;

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        elevation: 0,
        scrolledUnderElevation: 0,
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        title: const Text('التوصيل'),
        actions: [
          Padding(
            padding: const EdgeInsetsDirectional.only(start: 8, end: 16),
            child: Center(child: StatusBadge(status: order.status, labelOverride: order.statusLabel)),
          ),
        ],
      ),
      body: Stack(
        children: [
          ListView(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 160),
            children: [
              _HeroOrderHeader(order: order),
              const SizedBox(height: 16),
              _HorizontalDeliveryTimeline(statusNormalized: normalized),
              const SizedBox(height: 20),
              _DetailCard(order: order),
              if (order.deliveryNotes != null && order.deliveryNotes!.trim().isNotEmpty) ...[
                const SizedBox(height: 14),
                _NotesCard(notes: order.deliveryNotes!.trim()),
              ],
            ],
          ),
          Align(
            alignment: Alignment.bottomCenter,
            child: _ActiveActionDock(
              order: order,
              busy: provider.isBusy,
              onAction: (s) => _updateStatus(context, s),
            ),
          ),
          if (provider.isBusy)
            Positioned.fill(
              child: AbsorbPointer(
                child: ColoredBox(
                  color: Color(0x33000000),
                  child: Center(
                    child: SizedBox(
                      width: 40,
                      height: 40,
                      child: CircularProgressIndicator(color: Theme.of(context).colorScheme.surface, strokeWidth: 3),
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

class _HeroOrderHeader extends StatelessWidget {
  final OrderModel order;

  const _HeroOrderHeader({required this.order});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(12),
        color: Theme.of(context).colorScheme.surface,
        border: Border.all(color: Theme.of(context).dividerColor),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 18,
            offset: const Offset(0, 8),
          ),
        ],
      ),
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
                    Text(
                      order.restaurantName,
                      style: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w900, height: 1.15),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 6),
                    Text(
                      order.displayOrderRef,
                      style: theme.textTheme.titleSmall?.copyWith(
                        color: AppColors.textSecondary,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                decoration: BoxDecoration(
                  color: Theme.of(context).colorScheme.surfaceContainerHighest,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Theme.of(context).dividerColor),
                ),
                child: Column(
                  children: [
                    Text(
                      order.totalPrice.toStringAsFixed(2),
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w900,
                        color: AppColors.accentDark,
                      ),
                    ),
                    Text(
                      '₪ الإجمالي',
                      style: theme.textTheme.labelSmall?.copyWith(color: AppColors.textMuted),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

/// Driver milestones after assignment: accepted -> picked_up -> delivered.
class _HorizontalDeliveryTimeline extends StatelessWidget {
  final String statusNormalized;

  const _HorizontalDeliveryTimeline({required this.statusNormalized});

  int get _step {
    switch (statusNormalized) {
      case 'completed':
        return 2;
      case 'delivering':
      case 'on_the_way':
      case 'picked_up':
        return 1;
      default:
        return 0;
    }
  }

  bool get _allDone => statusNormalized == 'completed' || statusNormalized == 'delivered';

  @override
  Widget build(BuildContext context) {
    const labels = ['تم القبول', 'تم الاستلام', 'مكتمل'];
    final step = _step;

    return DecoratedBox(
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Theme.of(context).dividerColor),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 8),
              child: Row(
                children: [
                  Icon(Icons.route_rounded, size: 18, color: AppColors.accentDark.withValues(alpha: 0.85)),
                  const SizedBox(width: 8),
                  Text(
                    'مسار التوصيل',
                    style: Theme.of(context).textTheme.labelLarge?.copyWith(fontWeight: FontWeight.w800),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                for (var i = 0; i < labels.length; i++) ...[
                  Expanded(
                    child: _TimelineNode(
                      label: labels[i],
                      index: i,
                      currentStep: step,
                      allStepsComplete: _allDone,
                    ),
                  ),
                  if (i < labels.length - 1)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 22),
                      child: _TimelineConnector(filled: _allDone || step > i),
                    ),
                ],
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _TimelineNode extends StatelessWidget {
  final String label;
  final int index;
  final int currentStep;
  final bool allStepsComplete;

  const _TimelineNode({
    required this.label,
    required this.index,
    required this.currentStep,
    required this.allStepsComplete,
  });

  @override
  Widget build(BuildContext context) {
    final done = allStepsComplete || index < currentStep;
    final active = !allStepsComplete && index == currentStep;

    return Column(
      children: [
        AnimatedContainer(
          duration: const Duration(milliseconds: 220),
          width: 36,
          height: 36,
          decoration: BoxDecoration(
            color: done
                ? AppColors.accent
                : Theme.of(context).colorScheme.surfaceContainerHighest,
            shape: BoxShape.circle,
            border: Border.all(
              color: done
                  ? AppColors.accent
                  : active
                      ? AppColors.accent
                      : Theme.of(context).dividerColor,
              width: active ? 2.4 : 1.6,
            ),
          ),
          child: Center(
            child: done
                ? const Icon(Icons.check_rounded, color: Colors.white, size: 18)
                : Text(
                    '${index + 1}',
                    style: TextStyle(
                      fontWeight: FontWeight.w900,
                      fontSize: 13,
                      color: active ? AppColors.accentDark : AppColors.textMuted,
                    ),
                  ),
          ),
        ),
        const SizedBox(height: 8),
        Text(
          label,
          textAlign: TextAlign.center,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: Theme.of(context).textTheme.labelSmall?.copyWith(
                fontWeight: active ? FontWeight.w900 : FontWeight.w600,
                color: active ? AppColors.textPrimary : AppColors.textMuted,
                fontSize: 11,
              ),
        ),
      ],
    );
  }
}

class _TimelineConnector extends StatelessWidget {
  final bool filled;

  const _TimelineConnector({required this.filled});

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 12,
      child: Center(
        child: Container(
          height: 3,
          margin: const EdgeInsets.only(bottom: 22),
          decoration: BoxDecoration(
            color: filled ? AppColors.accent : Theme.of(context).dividerColor,
            borderRadius: BorderRadius.circular(2),
          ),
        ),
      ),
    );
  }
}

class _DetailCard extends StatelessWidget {
  final OrderModel order;

  const _DetailCard({required this.order});

  int get itemCount => order.items.length;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Theme.of(context).dividerColor),
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
            _SectionTitle(icon: Icons.list_alt_rounded, text: 'تفاصيل التسليم'),
            const SizedBox(height: 14),
            OrderInfoRow(
              icon: Icons.storefront_rounded,
              iconColor: AppColors.preparing,
              title: order.restaurantAddress.trim().isEmpty ? 'لم يُذكر' : order.restaurantAddress.trim(),
              subtitle: 'المطعم / الاستلام',
              maxLines: 3,
            ),
            Divider(height: 22, color: Theme.of(context).dividerColor),
            OrderInfoRow(
              icon: Icons.person_outline_rounded,
              iconColor: AppColors.accepted,
              title: order.customerName.trim().isEmpty ? '—' : order.customerName.trim(),
              subtitle: 'العميل',
            ),
            Divider(height: 22, color: Theme.of(context).dividerColor),
            OrderInfoRow(
              icon: Icons.phone_outlined,
              iconColor: AppColors.accepted,
              title: order.customerPhone.trim().isEmpty ? '—' : order.customerPhone.trim(),
              subtitle: 'الهاتف',
            ),
            Divider(height: 22, color: Theme.of(context).dividerColor),
            OrderInfoRow(
              icon: Icons.location_on_rounded,
              iconColor: AppColors.delivering,
              title: order.deliveryAddress.trim().isEmpty ? 'لم يتم تحديد العنوان' : order.deliveryAddress.trim(),
              subtitle: 'عنوان التسليم',
              maxLines: 4,
            ),
            Divider(height: 22, color: Theme.of(context).dividerColor),
            OrderInfoRow(
              icon: Icons.shopping_bag_outlined,
              iconColor: AppColors.accepted,
              title: '$itemCount',
              subtitle: itemCount == 0
                  ? 'لم تُحمّل التفاصيل'
                  : (itemCount == 1 ? 'صنف' : 'أصناف'),
            ),
            if (order.items.isNotEmpty) ...[
              const SizedBox(height: 16),
              _SectionTitle(icon: Icons.receipt_long_rounded, text: 'الملخص'),
              const SizedBox(height: 10),
              ...order.items.map(
                (line) => Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Icon(Icons.fiber_manual_record, size: 7, color: AppColors.textMuted.withValues(alpha: 0.8)),
                      const SizedBox(width: 10),
                      Expanded(child: Text(line, style: Theme.of(context).textTheme.bodyMedium)),
                    ],
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  final IconData icon;
  final String text;

  const _SectionTitle({required this.icon, required this.text});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(icon, size: 20, color: AppColors.accentDark),
        const SizedBox(width: 8),
        Text(
          text,
          style: Theme.of(context).textTheme.labelLarge?.copyWith(fontWeight: FontWeight.w900),
        ),
      ],
    );
  }
}

class _NotesCard extends StatelessWidget {
  final String notes;

  const _NotesCard({required this.notes});

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: AppColors.pending.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.pending.withValues(alpha: 0.22)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.note_alt_outlined, size: 20, color: AppColors.pending),
                const SizedBox(width: 8),
                Text('ملاحظات', style: Theme.of(context).textTheme.labelLarge?.copyWith(fontWeight: FontWeight.w800)),
              ],
            ),
            const SizedBox(height: 10),
            Text(notes, style: Theme.of(context).textTheme.bodyMedium),
          ],
        ),
      ),
    );
  }
}

class _ActiveActionDock extends StatelessWidget {
  final OrderModel order;
  final bool busy;
  final Future<void> Function(String status) onAction;

  const _ActiveActionDock({
    required this.order,
    required this.busy,
    required this.onAction,
  });

  Widget _infoSheet(BuildContext context, String title, String text) {
    return Material(
      elevation: 16,
      color: Colors.transparent,
      shadowColor: Colors.black.withValues(alpha: 0.12),
      child: ClipRRect(
        child: DecoratedBox(
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.surface,
            border: Border(top: BorderSide(color: Theme.of(context).dividerColor)),
          ),
          child: SafeArea(
            top: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 18),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Row(
                    children: [
                      Icon(Icons.info_outline_rounded, color: AppColors.accentDark.withValues(alpha: 0.9)),
                      const SizedBox(width: 10),
                      Text(title, style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800)),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Text(text, style: Theme.of(context).textTheme.bodyMedium),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _primaryAction({
    required BuildContext context,
    required String label,
    required String subtitle,
    required IconData icon,
    required Color color,
    required VoidCallback? onPressed,
  }) {
    return Material(
      elevation: 12,
      color: Colors.transparent,
      shadowColor: Colors.black.withValues(alpha: 0.1),
      child: DecoratedBox(
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surface,
          border: Border(top: BorderSide(color: Theme.of(context).dividerColor)),
        ),
        child: SafeArea(
          top: false,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              mainAxisSize: MainAxisSize.min,
              children: [
                FilledButton.icon(
                  onPressed: onPressed,
                  icon: Icon(icon, size: 22),
                  label: Padding(
                    padding: const EdgeInsets.symmetric(vertical: 2),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(label, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
                        Text(
                          subtitle,
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w500,
                            color: Colors.white.withValues(alpha: 0.92),
                          ),
                        ),
                      ],
                    ),
                  ),
                  style: FilledButton.styleFrom(
                    backgroundColor: color,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final normalized = StatusBadge.normalize(order.status);

    if (normalized == 'accepted') {
      return _primaryAction(
        context: context,
        label: 'تم الاستلام',
        subtitle: 'بدء التوصيل (In Transit)',
        icon: Icons.inventory_2_outlined,
        color: Theme.of(context).colorScheme.primary,
        onPressed: busy ? null : () => onAction('picked_up'),
      );
    }

    if (normalized == 'delivering') {
      return _primaryAction(
        context: context,
        label: 'تأكيد التسليم',
        subtitle: 'بعد تسليم الطلب للعميل',
        icon: Icons.task_alt_rounded,
        color: Theme.of(context).colorScheme.primary,
        onPressed: busy ? null : () => onAction('delivered'),
      );
    }

    return _infoSheet(context, 'لا إجراء', 'لا يمكن تحديث الحالة لهذه المرحلة من هذا الشاشة.');
  }
}

