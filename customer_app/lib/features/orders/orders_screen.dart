import 'package:flutter/material.dart';
import 'dart:async';

import '../../core/api/api_client.dart';
import '../../core/services/auth_service.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/widgets.dart';
import 'order_tracking_screen.dart';

class OrdersScreen extends StatefulWidget {
  final int? highlightedOrderId;

  const OrdersScreen({super.key, this.highlightedOrderId});

  @override
  State<OrdersScreen> createState() => _OrdersScreenState();
}

class _OrdersScreenState extends State<OrdersScreen> {
  List<Map<String, dynamic>> _orders = [];
  bool _isLoading = true;
  String? _errorMessage;
  StreamSubscription<dynamic>? _userSub;
  Timer? _ordersPollTimer;

  static const List<String> _statusFlow = [
    'pending_payment_verification',
    'payment_verified',
    'accepted_by_restaurant',
    'preparing',
    'on_the_way',
    'delivered',
  ];

  static const Map<String, String> _statusLabels = {
    'pending_payment_verification': 'بانتظار تحقق الدفع',
    'payment_verified': 'تم التحقق من الدفع',
    'payment_rejected': 'رفض الدفع',
    'accepted_by_restaurant': 'مقبول من المطعم',
    'preparing': 'قيد التحضير',
    'on_the_way': 'في الطريق',
    'delivered': 'تم التسليم',
    'pending': 'بانتظار التأكيد',
    'accepted': 'تم التأكيد',
    'cancelled': 'ملغى',
  };

  static const Map<String, Color> _statusColors = {
    'pending_payment_verification': AppColors.textSecondary,
    'payment_verified': Color(0xFF3498DB),
    'payment_rejected': AppColors.error,
    'accepted_by_restaurant': Color(0xFF0891B2),
    'preparing': AppColors.primary,
    'on_the_way': AppColors.primaryDark,
    'delivered': AppColors.success,
    'pending': AppColors.textSecondary,
    'accepted': Color(0xFF3498DB),
    'cancelled': AppColors.error,
  };

  @override
  void initState() {
    super.initState();
    AuthService.fetchCurrentUser();
    _watchUserChanges();
    _startOrdersPolling();
    _loadOrders();
  }

  void _watchUserChanges() {
    _userSub?.cancel();
    _userSub = AuthService.watchCurrentUser().listen((_) {
      if (!mounted) return;
      _loadOrders(showLoading: false);
    });
  }

  void _startOrdersPolling() {
    _ordersPollTimer?.cancel();
    _ordersPollTimer = Timer.periodic(const Duration(seconds: 8), (_) {
      _loadOrders(showLoading: false);
    });
  }

  Future<void> _loadOrders({bool showLoading = true}) async {
    if (showLoading) {
      setState(() {
        _isLoading = true;
        _errorMessage = null;
      });
    }

    final response = await ApiClient.get('/orders');
    if (!mounted) return;

    if (response['success'] == true && response['data'] is List) {
      final List data = response['data'] as List;
      final orders = data.map((e) => Map<String, dynamic>.from(e)).toList();
      setState(() {
        _orders = orders;
        if (showLoading) _isLoading = false;
        if (!showLoading && _errorMessage != null) _errorMessage = null;
      });
      return;
    }

    setState(() {
      _errorMessage = response['message']?.toString() ?? 'تعذر تحميل الطلبات';
      if (showLoading) _isLoading = false;
    });
  }

  @override
  void dispose() {
    _userSub?.cancel();
    _ordersPollTimer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('طلباتي'),
        backgroundColor: AppColors.background,
        elevation: 0,
      ),
      body: _isLoading
          ? const LoadingShimmer(itemCount: 4)
          : _errorMessage != null
              ? ErrorState(message: _errorMessage!, onRetry: _loadOrders)
              : _orders.isEmpty
                  ? const EmptyState(
                      icon: Icons.receipt_long_outlined,
                      title: 'لا توجد طلبات بعد',
                      subtitle: 'عند إتمام أي طلب سيظهر هنا',
                    )
                  : ListView.builder(
                      padding: const EdgeInsets.all(AppSpacing.lg),
                      itemCount: _orders.length,
                      itemBuilder: (context, index) {
                        final order = _orders[index];
                        return _buildOrderCard(order);
                      },
                    ),
    );
  }

  Widget _buildOrderCard(Map<String, dynamic> order) {
    final status = order['status']?.toString() ?? 'pending';
    final statusLabel = _statusLabels[status] ?? status;
    final statusColor = _statusColors[status] ?? AppColors.textSecondary;
    final id = order['id'];
    final restaurant = (order['restaurant'] as Map?) ?? {};
    final items = (order['items'] as List?) ?? [];
    final isHighlighted =
        widget.highlightedOrderId != null && id == widget.highlightedOrderId;
    final totalPrice = double.tryParse(order['total_price']?.toString() ?? '0') ?? 0;

    return GestureDetector(
      onTap: () => _navigateToTracking(order),
      child: Container(
        margin: const EdgeInsets.only(bottom: AppSpacing.xl),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(AppRadius.xl),
          border: isHighlighted
              ? Border.all(color: AppColors.primary, width: 2)
              : null,
          boxShadow: [
            BoxShadow(
              color: AppColors.shadow,
              blurRadius: 12,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Padding(
              padding: const EdgeInsets.all(AppSpacing.lg),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: AppSpacing.md,
                          vertical: AppSpacing.xs,
                        ),
                        decoration: BoxDecoration(
                          color: statusColor.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(AppRadius.pill),
                        ),
                        child: Text(
                          statusLabel,
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.bold,
                            color: statusColor,
                          ),
                        ),
                      ),
                      Row(
                        children: [
                          Text(
                            'طلب #',
                            style: TextStyle(
                              fontSize: 14,
                              color: AppColors.textSecondary,
                            ),
                          ),
                          Text(
                            '$id',
                            style: const TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                              color: AppColors.textPrimary,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                  const SizedBox(height: AppSpacing.lg),
                  Row(
                    children: [
                      Container(
                        width: 56,
                        height: 56,
                        decoration: BoxDecoration(
                          color: AppColors.secondary,
                          borderRadius: BorderRadius.circular(AppRadius.lg),
                        ),
                        child: (restaurant['image'] as String?)?.isNotEmpty == true
                            ? ClipRRect(
                                borderRadius: BorderRadius.circular(AppRadius.lg),
                                child: Image.network(
                                  restaurant['image']!,
                                  fit: BoxFit.cover,
                                  errorBuilder: (_, _, _) => const Icon(
                                    Icons.restaurant_outlined,
                                    color: AppColors.textHint,
                                  ),
                                ),
                              )
                            : const Icon(
                                Icons.restaurant_outlined,
                                color: AppColors.textHint,
                              ),
                      ),
                      const SizedBox(width: AppSpacing.md),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              restaurant['name']?.toString() ?? 'مطعم',
                              style: const TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                                color: AppColors.textPrimary,
                              ),
                            ),
                            const SizedBox(height: AppSpacing.xs),
                            Text(
                              '${items.length} عنصر',
                              style: const TextStyle(
                                fontSize: 14,
                                color: AppColors.textSecondary,
                              ),
                            ),
                          ],
                        ),
                      ),
                      Text(
                        '₪${totalPrice.toStringAsFixed(2)}',
                        style: const TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                          color: AppColors.primary,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            _buildStatusTimeline(status),
          ],
        ),
      ),
    );
  }

  Widget _buildStatusTimeline(String currentStatus) {
    if (currentStatus == 'payment_rejected') {
      return Container(
        padding: const EdgeInsets.all(AppSpacing.lg),
        decoration: BoxDecoration(
          color: AppColors.error.withValues(alpha: 0.08),
          borderRadius: const BorderRadius.vertical(
            bottom: Radius.circular(AppRadius.xl),
          ),
        ),
        child: const Text(
          'تم رفض الدفع لهذا الطلب',
          style: TextStyle(fontSize: 13, color: AppColors.error, fontWeight: FontWeight.w600),
        ),
      );
    }

    final currentIndex = _statusFlow.indexOf(currentStatus);
    final adjustedIndex = currentStatus == 'cancelled'
        ? -1
        : (currentIndex >= 0 ? currentIndex : 0);

    return Container(
      padding: const EdgeInsets.fromLTRB(
        AppSpacing.sm,
        AppSpacing.lg,
        AppSpacing.sm,
        AppSpacing.md,
      ),
      decoration: BoxDecoration(
        color: AppColors.secondary.withValues(alpha: 0.3),
        borderRadius: const BorderRadius.vertical(
          bottom: Radius.circular(AppRadius.xl),
        ),
      ),
      child: Column(
        children: [
          Row(
            children: [
              for (int i = 0; i < _statusFlow.length; i++) ...[
                Expanded(
                  child: Center(
                    child: Container(
                      width: 22,
                      height: 22,
                      decoration: BoxDecoration(
                        color: i <= adjustedIndex
                            ? _statusColors[_statusFlow[i]] ?? AppColors.primary
                            : AppColors.divider,
                        shape: BoxShape.circle,
                      ),
                      child: i <= adjustedIndex
                          ? const Icon(
                              Icons.check,
                              color: Colors.white,
                              size: 13,
                            )
                          : null,
                    ),
                  ),
                ),
                if (i < _statusFlow.length - 1)
                  Expanded(
                    child: Container(
                      height: 3,
                      margin: const EdgeInsets.only(bottom: 1),
                      decoration: BoxDecoration(
                        color: (i + 1) <= adjustedIndex
                            ? _statusColors[_statusFlow[i + 1]] ?? AppColors.primary
                            : AppColors.divider,
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                  ),
              ],
            ],
          ),
          const SizedBox(height: AppSpacing.xs),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              for (int i = 0; i < _statusFlow.length; i++) ...[
                Expanded(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 2),
                    child: Text(
                      _statusLabels[_statusFlow[i]] ?? '',
                      style: TextStyle(
                        fontSize: 9,
                        fontWeight: i == adjustedIndex ? FontWeight.bold : FontWeight.normal,
                        color: i <= adjustedIndex ? AppColors.textPrimary : AppColors.textHint,
                        height: 1.2,
                      ),
                      textAlign: TextAlign.center,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ),
                if (i < _statusFlow.length - 1) const Expanded(child: SizedBox()),
              ],
            ],
          ),
        ],
      ),
    );
  }

  void _navigateToTracking(Map<String, dynamic> order) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => OrderTrackingScreen(order: order),
      ),
    );
  }
}
