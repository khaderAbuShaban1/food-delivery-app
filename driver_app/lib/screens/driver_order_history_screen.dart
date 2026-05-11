import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/config/app_config.dart';
import '../core/theme/app_colors.dart';
import '../models/order_model.dart';
import '../providers/auth_provider.dart';
import '../widgets/order_empty_state.dart';
import '../widgets/status_badge.dart';

class DriverOrderHistoryScreen extends StatefulWidget {
  const DriverOrderHistoryScreen({super.key});

  @override
  State<DriverOrderHistoryScreen> createState() => _DriverOrderHistoryScreenState();
}

class _DriverOrderHistoryScreenState extends State<DriverOrderHistoryScreen> {
  bool _loading = true;
  String? _error;
  List<OrderModel> _orders = const [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    final api = context.read<AuthProvider>().apiClient;
    final res = await api.get(DriverApiPaths.driverOrdersHistory);

    if (!mounted) return;

    if (res['success'] == true && res['data'] is List) {
      final list = res['data'] as List<dynamic>;
      final parsed = <OrderModel>[];
      for (final e in list) {
        if (e is Map) {
          parsed.add(OrderModel.fromLaravelApi(Map<String, dynamic>.from(e as Map)));
        }
      }
      setState(() {
        _orders = parsed;
        _loading = false;
      });
      return;
    }

    setState(() {
      _error = res['message']?.toString() ?? 'تعذر تحميل سجل الطلبات';
      _loading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        title: const Text('سجل الطلبات'),
        actions: [
          IconButton(
            tooltip: 'تحديث',
            onPressed: _loading ? null : _load,
            icon: const Icon(Icons.refresh_rounded),
          ),
        ],
      ),
      body: SafeArea(
        child: RefreshIndicator.adaptive(
          color: Theme.of(context).colorScheme.primary,
          onRefresh: _load,
          child: _loading
              ? const Center(
                  child: Padding(
                    padding: EdgeInsets.all(24),
                    child: CircularProgressIndicator(color: AppColors.accent),
                  ),
                )
              : _error != null
                  ? ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
                      children: [
                        _ErrorCard(message: _error!, onRetry: _load),
                      ],
                    )
                  : _orders.isEmpty
                      ? const OrderEmptyState(
                          title: 'لا توجد طلبات مكتملة',
                          subtitle: 'ستظهر هنا الطلبات التي تم تسليمها بعد اكتمالها.',
                          icon: Icons.history_rounded,
                        )
                      : ListView.separated(
                          physics: const AlwaysScrollableScrollPhysics(),
                          padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
                          itemCount: _orders.length,
                          separatorBuilder: (_, __) => const SizedBox(height: 14),
                          itemBuilder: (_, i) => _HistoryOrderCard(order: _orders[i]),
                        ),
        ),
      ),
    );
  }
}

class _HistoryOrderCard extends StatelessWidget {
  final OrderModel order;

  const _HistoryOrderCard({required this.order});

  String _dateText() {
    final dt = order.updatedAt ?? order.createdAt;
    if (dt == null) return '—';
    final y = dt.year.toString().padLeft(4, '0');
    final m = dt.month.toString().padLeft(2, '0');
    final d = dt.day.toString().padLeft(2, '0');
    final hh = dt.hour.toString().padLeft(2, '0');
    final mm = dt.minute.toString().padLeft(2, '0');
    return '$y-$m-$d  $hh:$mm';
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    final statusLabel = StatusBadge.labelAr(StatusBadge.normalize(order.status));

    return DecoratedBox(
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: theme.dividerColor),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 16,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(14),
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
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w900),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        _dateText(),
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: theme.colorScheme.onSurfaceVariant,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 10),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(
                      '₪ ${order.totalPrice.toStringAsFixed(2)}',
                      style: theme.textTheme.titleSmall?.copyWith(
                        fontWeight: FontWeight.w900,
                        color: AppColors.accentDark,
                      ),
                    ),
                    const SizedBox(height: 8),
                    StatusBadge(status: order.status, labelOverride: statusLabel),
                  ],
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _ErrorCard extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;

  const _ErrorCard({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Theme.of(context).dividerColor),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(
              message,
              style: Theme.of(context).textTheme.bodyMedium,
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 12),
            SizedBox(
              height: 48,
              child: FilledButton.icon(
                onPressed: onRetry,
                style: FilledButton.styleFrom(backgroundColor: AppColors.accent),
                icon: const Icon(Icons.refresh_rounded),
                label: const Text('إعادة المحاولة'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

