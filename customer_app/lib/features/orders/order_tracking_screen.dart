import 'dart:async';
import 'package:flutter/material.dart';
import '../../core/services/realtime_sync_service.dart';
import '../../core/theme/app_theme.dart';

class OrderTrackingScreen extends StatefulWidget {
  final Map<String, dynamic> order;

  const OrderTrackingScreen({super.key, required this.order});

  @override
  State<OrderTrackingScreen> createState() => _OrderTrackingScreenState();
}

class _OrderTrackingScreenState extends State<OrderTrackingScreen> {
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

  static const Map<String, IconData> _statusIcons = {
    'pending': Icons.receipt_outlined,
    'accepted': Icons.check_circle_outline,
    'preparing': Icons.restaurant_outlined,
    'delivering': Icons.delivery_dining,
    'completed': Icons.done_all,
  };

  late Map<String, dynamic> _order;
  StreamSubscription<Map<String, dynamic>?>? _orderSub;

  @override
  void initState() {
    super.initState();
    _order = Map<String, dynamic>.from(widget.order);
    _syncInitialOrder();
    _startRealtimeListener();
  }

  Future<void> _syncInitialOrder() async {
    final userId = RealtimeSyncService.currentUserId();
    if (userId == null) return;
    await RealtimeSyncService.upsertOrder(userId, _order);
  }

  void _startRealtimeListener() {
    final orderId = _order['id']?.toString();
    if (orderId == null || orderId.isEmpty) return;
    _orderSub?.cancel();
    _orderSub = RealtimeSyncService.watchOrderById(orderId).listen((order) {
      if (!mounted || order == null) return;
      setState(() {
        _order = Map<String, dynamic>.from(order);
      });
    });
  }

  @override
  void dispose() {
    _orderSub?.cancel();
    super.dispose();
  }

  String _getTimeAgo(dynamic dateTime) {
    if (dateTime == null) return '';

    DateTime date;
    if (dateTime is String) {
      date = DateTime.tryParse(dateTime) ?? DateTime.now();
    } else {
      date = DateTime.now();
    }

    final now = DateTime.now();
    final diff = now.difference(date);

    if (diff.inMinutes < 1) {
      return 'الآن';
    } else if (diff.inMinutes < 60) {
      return 'منذ ${diff.inMinutes} دقيقة';
    } else if (diff.inHours < 24) {
      return 'منذ ${diff.inHours} ساعة';
    } else {
      return 'منذ ${diff.inDays} يوم';
    }
  }

  @override
  Widget build(BuildContext context) {
    final status = _order['status']?.toString() ?? 'pending';
    final statusLabel = _statusLabels[status] ?? status;
    final statusColor = _statusColors[status] ?? AppColors.textSecondary;
    final orderId = _order['id'];
    final totalPrice = double.tryParse(_order['total_price']?.toString() ?? '0') ?? 0;
    final restaurant = Map<String, dynamic>.from(_order['restaurant'] as Map? ?? {});
    final items = (_order['items'] as List?)?.map((e) => Map<String, dynamic>.from(e as Map)).toList() ?? [];
    final createdAt = _order['created_at'];

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: Text('طلب #$orderId'),
        backgroundColor: AppColors.background,
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildHeader(orderId, totalPrice, createdAt, status, statusLabel, statusColor),
            const SizedBox(height: AppSpacing.xl),
            _buildTimelineSection(status),
            const SizedBox(height: AppSpacing.xl),
            _buildRestaurantSection(restaurant),
            const SizedBox(height: AppSpacing.xl),
            _buildItemsSection(items),
            const SizedBox(height: AppSpacing.xl),
            _buildOrderSummary(totalPrice),
            const SizedBox(height: AppSpacing.huge),
          ],
        ),
      ),
    );
  }

  Widget _buildHeader(int? orderId, double totalPrice, dynamic createdAt, String status, String statusLabel, Color statusColor) {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.lg),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppRadius.xl),
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
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'طلب #${orderId ?? ''}',
                    style: const TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                      color: AppColors.textPrimary,
                    ),
                  ),
                  if (createdAt != null)
                    Text(
                      _getTimeAgo(createdAt),
                      style: const TextStyle(
                        fontSize: 14,
                        color: AppColors.textSecondary,
                      ),
                    ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: AppSpacing.lg,
                  vertical: AppSpacing.sm,
                ),
                decoration: BoxDecoration(
                  color: statusColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(AppRadius.lg),
                ),
                child: Row(
                  children: [
                    Icon(
                      _statusIcons[status] ?? Icons.receipt,
                      color: statusColor,
                      size: 20,
                    ),
                    const SizedBox(width: AppSpacing.sm),
                    Text(
                      statusLabel,
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.bold,
                        color: statusColor,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.lg),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'المبلغ الإجمالي',
                style: TextStyle(
                  fontSize: 16,
                  color: AppColors.textSecondary,
                ),
              ),
              Text(
                '₪${totalPrice.toStringAsFixed(2)}',
                style: const TextStyle(
                  fontSize: 28,
                  fontWeight: FontWeight.bold,
                  color: AppColors.primary,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildTimelineSection(String currentStatus) {
    final currentIndex = _statusFlow.indexOf(currentStatus);
    final adjustedIndex = currentStatus == 'cancelled' ? -1 : currentIndex;

    return Container(
      padding: const EdgeInsets.all(AppSpacing.lg),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppRadius.xl),
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
          const Row(
            children: [
              Icon(Icons.timeline, color: AppColors.primary, size: 24),
              SizedBox(width: AppSpacing.sm),
              Text(
                'تتبع الطلب',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: AppColors.textPrimary,
                ),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.xl),
          ...List.generate(_statusFlow.length, (index) {
            final stepStatus = _statusFlow[index];
            final isCompleted = index < adjustedIndex;
            final isCurrent = index == adjustedIndex;
            final isPending = index > adjustedIndex;

            return _buildTimelineStep(
              stepStatus,
              isCompleted: isCompleted,
              isCurrent: isCurrent,
              isPending: isPending,
              isLast: index == _statusFlow.length - 1,
            );
          }),
        ],
      ),
    );
  }

  Widget _buildTimelineStep(
    String stepStatus, {
    required bool isCompleted,
    required bool isCurrent,
    required bool isPending,
    required bool isLast,
  }) {
    final color = isCompleted || isCurrent
        ? _statusColors[stepStatus] ?? AppColors.primary
        : AppColors.divider;
    final label = _statusLabels[stepStatus] ?? stepStatus;
    final icon = _statusIcons[stepStatus] ?? Icons.check;

    return IntrinsicHeight(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Column(
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: color.withValues(alpha: isPending ? 0.1 : 0.15),
                  shape: BoxShape.circle,
                  border: isCurrent
                      ? Border.all(color: color, width: 2)
                      : null,
                ),
                child: Center(
                  child: isPending
                      ? Icon(icon, color: AppColors.textHint, size: 20)
                      : Icon(
                          isCompleted ? Icons.check : icon,
                          color: Colors.white,
                          size: 20,
                        ),
                ),
              ),
              if (!isLast)
                Expanded(
                  child: Container(
                    width: 2,
                    margin: const EdgeInsets.symmetric(vertical: 4),
                    decoration: BoxDecoration(
                      color: isCompleted ? color : AppColors.divider,
                      borderRadius: BorderRadius.circular(1),
                    ),
                  ),
                ),
            ],
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Padding(
              padding: EdgeInsets.only(bottom: isLast ? 0 : AppSpacing.lg),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    label,
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: isCurrent ? FontWeight.bold : FontWeight.w600,
                      color: isPending ? AppColors.textHint : AppColors.textPrimary,
                    ),
                  ),
                  if (isCurrent)
                    Text(
                      'الجاري الآن',
                      style: TextStyle(
                        fontSize: 13,
                        color: color,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildRestaurantSection(Map<String, dynamic> restaurant) {
    final image = restaurant['image'] as String?;
    final name = restaurant['name']?.toString() ?? 'مطعم غير معروف';

    return Container(
      padding: const EdgeInsets.all(AppSpacing.lg),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppRadius.xl),
        boxShadow: [
          BoxShadow(
            color: AppColors.shadow,
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 60,
            height: 60,
            decoration: BoxDecoration(
              color: AppColors.secondary,
              borderRadius: BorderRadius.circular(AppRadius.lg),
            ),
            child: (image)?.isNotEmpty == true
                ? ClipRRect(
                    borderRadius: BorderRadius.circular(AppRadius.lg),
                    child: Image.network(
                      image!,
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
                const Text(
                  'المطعم',
                  style: TextStyle(
                    fontSize: 12,
                    color: AppColors.textSecondary,
                  ),
                ),
                Text(
                  name,
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: AppColors.textPrimary,
                  ),
                ),
              ],
            ),
          ),
          const Icon(
            Icons.storefront_outlined,
            color: AppColors.textHint,
          ),
        ],
      ),
    );
  }

  Widget _buildItemsSection(List items) {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.lg),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppRadius.xl),
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
          const Row(
            children: [
              Icon(Icons.receipt_long_outlined, color: AppColors.primary, size: 24),
              SizedBox(width: AppSpacing.sm),
              Text(
                'تفاصيل الطلب',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: AppColors.textPrimary,
                ),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.lg),
          ...items.map((item) {
            final name = item['name']?.toString() ?? 'عنصر';
            final quantity = item['quantity'] ?? 1;
            final price = double.tryParse(item['price']?.toString() ?? '0') ?? 0;

            return Padding(
              padding: const EdgeInsets.only(bottom: AppSpacing.md),
              child: Row(
                children: [
                  Container(
                    width: 32,
                    height: 32,
                    decoration: BoxDecoration(
                      color: AppColors.secondary,
                      borderRadius: BorderRadius.circular(AppRadius.md),
                    ),
                    child: Center(
                      child: Text(
                        '$quantity',
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.bold,
                          color: AppColors.primary,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(
                    child: Text(
                      name,
                      style: const TextStyle(
                        fontSize: 15,
                        color: AppColors.textPrimary,
                      ),
                    ),
                  ),
                  Text(
                    '₪${price.toStringAsFixed(2)}',
                    style: const TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w600,
                      color: AppColors.textPrimary,
                    ),
                  ),
                ],
              ),
            );
          }),
        ],
      ),
    );
  }

  Widget _buildOrderSummary(double totalPrice) {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.lg),
      decoration: BoxDecoration(
        color: AppColors.primary.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(AppRadius.xl),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          const Text(
            'الإجمالي',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: AppColors.textPrimary,
            ),
          ),
          Text(
            '₪${totalPrice.toStringAsFixed(2)}',
            style: const TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.bold,
              color: AppColors.primary,
            ),
          ),
        ],
      ),
    );
  }
}