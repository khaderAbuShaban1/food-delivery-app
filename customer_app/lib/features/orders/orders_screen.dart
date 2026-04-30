import 'package:flutter/material.dart';
import '../../core/api/api_client.dart';
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

  static const List<String> _statusFlow = [
    'pending',
    'accepted',
    'preparing',
    'delivering',
    'completed',
  ];

  static const Map<String, String> _statusLabels = {
    'pending': 'بانتظار التأكيد',
    'accepted': 'تم التأكيد',
    'preparing': 'قيد التحضير',
    'delivering': 'في الطريق',
    'completed': 'تم التسليم',
    'cancelled': 'ملغى',
  };

  static const Map<String, Color> _statusColors = {
    'pending': AppColors.textSecondary,
    'accepted': Color(0xFF3498DB),
    'preparing': AppColors.primary,
    'delivering': Color(0xFF9B59B6),
    'completed': AppColors.success,
    'cancelled': AppColors.error,
  };

  @override
  void initState() {
    super.initState();
    _loadOrders();
  }

  Future<void> _loadOrders() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final response = await ApiClient.get('/orders');
    if (!mounted) return;

    if (response['success'] == true && response['data'] is List) {
      final List data = response['data'] as List;
      setState(() {
        _orders = data.map((e) => Map<String, dynamic>.from(e)).toList();
        _isLoading = false;
      });
      return;
    }

    setState(() {
      _errorMessage = response['message']?.toString() ?? 'تعذر تحميل الطلبات';
      _isLoading = false;
    });
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
      body: RefreshIndicator(
        onRefresh: _loadOrders,
        color: AppColors.primary,
        child: _isLoading
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
                                  errorBuilder: (_, __, ___) => const Icon(
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
    final currentIndex = _statusFlow.indexOf(currentStatus);
    final adjustedIndex = currentStatus == 'cancelled' ? -1 : currentIndex;

    return Container(
      padding: const EdgeInsets.all(AppSpacing.lg),
      decoration: BoxDecoration(
        color: AppColors.secondary.withValues(alpha: 0.3),
        borderRadius: const BorderRadius.vertical(
          bottom: Radius.circular(AppRadius.xl),
        ),
      ),
      child: Row(
        children: [
          for (int i = 0; i < _statusFlow.length; i++) ...[
            if (i > 0)
              Expanded(
                child: Container(
                  height: 3,
                  margin: const EdgeInsets.only(bottom: AppSpacing.xl),
                  decoration: BoxDecoration(
                    color: i <= adjustedIndex
                        ? _statusColors[_statusFlow[i]] ?? AppColors.primary
                        : AppColors.divider,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
            Column(
              children: [
                Container(
                  width: 24,
                  height: 24,
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
                          size: 14,
                        )
                      : null,
                ),
                const SizedBox(height: AppSpacing.xs),
                Text(
                  _statusLabels[_statusFlow[i]] ?? '',
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight:
                        i == adjustedIndex ? FontWeight.bold : FontWeight.normal,
                    color: i <= adjustedIndex
                        ? AppColors.textPrimary
                        : AppColors.textHint,
                  ),
                  textAlign: TextAlign.center,
                ),
              ],
            ),
          ],
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