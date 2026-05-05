import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/theme/app_colors.dart';
import '../models/order_model.dart';
import '../providers/auth_provider.dart';
import '../providers/order_provider.dart';
import '../widgets/driver_order_card.dart';
import '../widgets/order_empty_state.dart';
import '../widgets/order_list_skeleton.dart';
import 'order_details_screen.dart';

class HomeScreen extends StatelessWidget {
  /// Switch parent nav to Active tab when user taps from blocked state.
  final VoidCallback? onGoToActiveTab;

  const HomeScreen({super.key, this.onGoToActiveTab});

  Future<void> _openDetails(BuildContext context, OrderModel order) async {
    final accepted =
        await Navigator.of(context).push<bool>(MaterialPageRoute(builder: (_) => OrderDetailsScreen(order: order)));
    if (!context.mounted) return;
    if (accepted == true) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('تم قبول الطلب. انتقل لتبويب «نشط».'),
          behavior: SnackBarBehavior.floating,
        ),
      );
    }
  }

  String _driverFirstName(AuthProvider auth) {
    final n = auth.driver?.name.trim() ?? '';
    if (n.isEmpty) return 'سائق';
    final parts = n.split(RegExp(r'\s+'));
    return parts.first;
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<OrderProvider>();
    final auth = context.watch<AuthProvider>();
    final occupied = provider.hasActiveOrder;

    final appBar = AppBar(
      elevation: 0,
      scrolledUnderElevation: 0,
      titleSpacing: 20,
      title: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            _driverFirstName(auth),
            style: Theme.of(context).textTheme.bodySmall?.copyWith(color: AppColors.textMuted),
          ),
          const SizedBox(height: 2),
          Text(
            occupied ? 'متفرغ للتوصيل؟ أكمل نشطاً' : 'لوحة الطلبات',
            style: Theme.of(context).appBarTheme.titleTextStyle,
          ),
        ],
      ),
      actions: [
        Padding(
          padding: const EdgeInsetsDirectional.only(end: 16),
          child: IconButton.filledTonal(
            style: IconButton.styleFrom(backgroundColor: AppColors.surfaceMuted),
            onPressed: () => provider.refreshFromServer(),
            icon: const Icon(Icons.refresh_rounded, color: AppColors.accentDark),
            tooltip: 'تحديث',
          ),
        ),
      ],
    );

    if (!provider.hasSyncedAvailableOrders) {
      return Scaffold(backgroundColor: AppColors.background, appBar: appBar, body: const OrderListSkeleton());
    }

    if (occupied) {
      return Scaffold(
        backgroundColor: AppColors.background,
        appBar: appBar,
        body: SafeArea(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: Column(
              children: [
                const SizedBox(height: 12),
                Expanded(
                  child: Center(
                    child: ConstrainedBox(
                      constraints: const BoxConstraints(maxWidth: 400),
                      child: DecoratedBox(
                        decoration: BoxDecoration(
                          color: AppColors.surface,
                          borderRadius: BorderRadius.circular(24),
                          border: Border.all(color: AppColors.borderSubtle),
                          boxShadow: [
                            BoxShadow(
                              color: AppColors.accent.withValues(alpha: 0.08),
                              blurRadius: 32,
                              offset: const Offset(0, 12),
                            ),
                          ],
                        ),
                        child: Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 36),
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Container(
                                padding: const EdgeInsets.all(20),
                                decoration: BoxDecoration(
                                  color: AppColors.accent.withValues(alpha: 0.12),
                                  shape: BoxShape.circle,
                                ),
                                child: const Icon(Icons.delivery_dining_rounded, size: 52, color: AppColors.accentDark),
                              ),
                              const SizedBox(height: 24),
                              Text(
                                'تركيز على الطلب النشط',
                                textAlign: TextAlign.center,
                                style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800),
                              ),
                              const SizedBox(height: 12),
                              Text(
                                'عند قبول طلب واحد لا يمكن ظهور طلبات أخرى. أكمل المرحلة الحالية أو انتقل لمتابعتها.',
                                textAlign: TextAlign.center,
                                style: Theme.of(context).textTheme.bodyMedium,
                              ),
                              if (onGoToActiveTab != null) ...[
                                const SizedBox(height: 28),
                                SizedBox(
                                  width: double.infinity,
                                  height: 54,
                                  child: FilledButton.icon(
                                    onPressed: onGoToActiveTab,
                                    icon: const Icon(Icons.arrow_back_rounded),
                                    label: const Text('متابعة التوصيل'),
                                    style: FilledButton.styleFrom(
                                      backgroundColor: AppColors.accent,
                                      foregroundColor: Colors.white,
                                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                                    ),
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      );
    }

    final orders = provider.availableOrders;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: appBar,
      body: RefreshIndicator.adaptive(
        color: AppColors.accent,
        edgeOffset: 8,
        onRefresh: () => context.read<OrderProvider>().refreshFromServer(),
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverPadding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
              sliver: SliverToBoxAdapter(
                child: _TodaySummaryStrip(
                  readyCount: orders.length,
                  deliveredTodayCount: provider.deliveredTodayCount,
                ),
              ),
            ),
            if (orders.isEmpty)
              SliverFillRemaining(
                hasScrollBody: false,
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(16, 14, 16, 100),
                  child: const OrderEmptyState(
                    title: 'لا توجد طلبات جاهزة الآن',
                    subtitle:
                        'يُحدَّث القائمة تلقائياً كل بضع ثوانٍ عند توفر طلبات جديدة.',
                    icon: Icons.takeout_dining_rounded,
                  ),
                ),
              )
            else ...[
              SliverPadding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                sliver: SliverToBoxAdapter(
                  child: _SectionHeader(
                    icon: Icons.fiber_manual_record,
                    iconColor: AppColors.accent,
                    title: 'طلبات جديدة للقبول',
                    badge: '${orders.length}',
                  ),
                ),
              ),
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(16, 10, 16, 100),
                sliver: SliverList.separated(
                  itemCount: orders.length,
                  separatorBuilder: (context, index) => const SizedBox(height: 18),
                  itemBuilder: (_, i) {
                    final order = orders[i];
                    return DriverOrderCard(
                      order: order,
                      highlightAsNew: i == 0,
                      onTap: () => _openDetails(context, order),
                    );
                  },
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _TodaySummaryStrip extends StatelessWidget {
  final int readyCount;
  final int deliveredTodayCount;

  const _TodaySummaryStrip({
    required this.readyCount,
    required this.deliveredTodayCount,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return DecoratedBox(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            AppColors.surface,
            AppColors.surface.withValues(alpha: 0.96),
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppColors.borderSubtle),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 20,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.insights_rounded, size: 20, color: AppColors.accentDark.withValues(alpha: 0.9)),
                const SizedBox(width: 8),
                Text(
                  'ملخص اليوم',
                  style: theme.textTheme.labelLarge?.copyWith(
                    fontWeight: FontWeight.w800,
                    color: AppColors.textPrimary,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            Row(
              children: [
                Expanded(
                  child: _SummaryTile(
                    icon: Icons.restaurant_menu_rounded,
                    iconBg: AppColors.preparing.withValues(alpha: 0.12),
                    iconFg: AppColors.preparing,
                    label: 'جاهزة للقبول',
                    value: '$readyCount',
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _SummaryTile(
                    icon: Icons.task_alt_rounded,
                    iconBg: AppColors.completed.withValues(alpha: 0.12),
                    iconFg: AppColors.completed,
                    label: 'تم تسليم اليوم',
                    value: '$deliveredTodayCount',
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Text(
              'آخر التحديثات من السيرفر، سحب للتحديث.',
              style: theme.textTheme.bodySmall?.copyWith(color: AppColors.textMuted),
            ),
          ],
        ),
      ),
    );
  }
}

class _SummaryTile extends StatelessWidget {
  final IconData icon;
  final Color iconBg;
  final Color iconFg;
  final String label;
  final String value;

  const _SummaryTile({
    required this.icon,
    required this.iconBg,
    required this.iconFg,
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: AppColors.surfaceMuted,
        borderRadius: BorderRadius.circular(14),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: iconBg,
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, color: iconFg, size: 22),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  value,
                  style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w900),
                ),
                Text(label, style: theme.textTheme.bodySmall),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _SectionHeader extends StatelessWidget {
  final IconData icon;
  final Color iconColor;
  final String title;
  final String badge;

  const _SectionHeader({
    required this.icon,
    required this.iconColor,
    required this.title,
    required this.badge,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Padding(
      padding: const EdgeInsets.only(bottom: 4, top: 4),
      child: Row(
        children: [
          Icon(icon, size: 14, color: iconColor),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              title,
              style: theme.textTheme.titleSmall?.copyWith(
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: AppColors.accent.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(999),
            ),
            child: Text(
              badge,
              style: theme.textTheme.labelSmall?.copyWith(
                fontWeight: FontWeight.w900,
                color: AppColors.accentDark,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
