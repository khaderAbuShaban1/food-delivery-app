import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/services/auth_service.dart';
import '../../core/theme/app_theme.dart';
import '../../core/services/cart_provider.dart';
import '../../core/widgets/widgets.dart';
import '../checkout/checkout_flow_screen.dart';

class CartScreen extends StatefulWidget {
  CartScreen({super.key});

  @override
  State<CartScreen> createState() => _CartScreenState();
}

class _CartScreenState extends State<CartScreen> {
  static double _deliveryFee = 15.0;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        title: Text('السلة'),
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        elevation: 0,
      ),
      body: Consumer<CartProvider>(
        builder: (context, cart, child) {
          if (cart.items.isEmpty) {
            return EmptyState(
              icon: Icons.shopping_cart_outlined,
              title: 'السلة فارغة',
              subtitle: 'أضف عناصر من المطاعم',
            );
          }

          return Column(
            children: [
              Expanded(
                child: ListView.builder(
                  padding: EdgeInsets.symmetric(
                    horizontal: AppSpacing.lg,
                    vertical: AppSpacing.md,
                  ),
                  itemCount: cart.items.length,
                  itemBuilder: (context, index) {
                    final item = cart.items[index];
                    return _buildCartItem(context, cart, item);
                  },
                ),
              ),
              _buildOrderSummary(context, cart),
            ],
          );
        },
      ),
    );
  }

  Widget _buildCartItem(
    BuildContext context,
    CartProvider cart,
    CartItem item,
  ) {
    final itemTotal = item.totalPrice;
    
    return TweenAnimationBuilder<double>(
      tween: Tween(begin: 0.95, end: 1.0),
      duration: Duration(milliseconds: 200),
      builder: (context, scale, child) {
        return Transform.scale(
          scale: scale,
          child: child,
        );
      },
      child: Container(
        margin: EdgeInsets.only(bottom: AppSpacing.md),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(AppRadius.lg),
          boxShadow: [
            BoxShadow(
              color: AppColors.shadow,
              blurRadius: 8,
              offset: Offset(0, 2),
            ),
          ],
        ),
        child: IntrinsicHeight(
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              ClipRRect(
                borderRadius: BorderRadius.horizontal(
                  right: Radius.circular(AppRadius.lg),
                  left: Radius.circular(0),
                ),
                child: SizedBox(
                  width: 85,
                  height: 85,
                  child: item.menuItem.image != null && item.menuItem.image!.isNotEmpty
                      ? Image.network(
                          item.menuItem.image!,
                          fit: BoxFit.cover,
                          errorBuilder: (_, _, _) => _buildPlaceholder(),
                        )
                      : _buildPlaceholder(),
                ),
              ),
              Expanded(
                child: Padding(
                  padding: EdgeInsets.all(AppSpacing.md),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        item.menuItem.name,
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: Theme.of(context).colorScheme.onSurface,
                          height: 1.3,
                        ),
                        textAlign: TextAlign.right,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                      SizedBox(height: 4),
                      Text(
                        '₪${item.unitPriceWithOptions.toStringAsFixed(2)}',
                        style: TextStyle(
                          fontSize: 12,
                          color: Theme.of(context).colorScheme.onSurfaceVariant,
                        ),
                      ),
                      if (item.selectedOptionValueIdsByGroup.isNotEmpty) ...[
                        SizedBox(height: 2),
                        Text(
                          _selectedOptionsLine(item),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            fontSize: 11,
                            color: Theme.of(context).colorScheme.onSurfaceVariant,
                            height: 1.35,
                          ),
                        ),
                      ],
                      Spacer(),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Flexible(
                            child: Text(
                              '₪${itemTotal.toStringAsFixed(2)}',
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                                color: AppColors.primary,
                              ),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          _buildQuantityControls(context, cart, item),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildPlaceholder() {
    return Container(
      color: AppColors.secondary,
      child: Center(
        child: Icon(
          Icons.fastfood_outlined,
          size: 32,
          color: Theme.of(context).colorScheme.onSurfaceVariant,
        ),
      ),
    );
  }

  Widget _buildQuantityControls(
    BuildContext context,
    CartProvider cart,
    CartItem item,
  ) {
    final scheme = Theme.of(context).colorScheme;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Container(
      decoration: BoxDecoration(
        color: isDark ? scheme.surfaceContainerHighest : AppColors.secondary,
        borderRadius: BorderRadius.circular(AppRadius.md),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          _buildQuantityButton(
            icon: item.quantity > 1 ? Icons.remove : Icons.delete_outline,
            onTap: () {
              if (item.quantity > 1) {
                cart.updateCartItemQuantity(item, item.quantity - 1);
              } else {
                cart.removeCartItem(item);
              }
            },
            isDelete: item.quantity == 1,
          ),
          Container(
            width: 32,
            alignment: Alignment.center,
            child: Text(
              item.quantity.toString(),
              style: TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.bold,
                color: isDark ? scheme.onSurface : Theme.of(context).colorScheme.onSurface,
              ),
            ),
          ),
          _buildQuantityButton(
            icon: Icons.add,
            onTap: () => cart.updateCartItemQuantity(item, item.quantity + 1),
          ),
        ],
      ),
    );
  }

  String _selectedOptionsLine(CartItem item) {
    final parts = <String>[];
    for (final group in item.menuItem.optionGroups) {
      final selectedIds = item.selectedOptionValueIdsByGroup[group.id] ?? <int>[];
      if (selectedIds.isEmpty) continue;
      final names = group.values
          .where((v) => selectedIds.contains(v.id))
          .map((v) => v.name)
          .where((n) => n.trim().isNotEmpty)
          .toList(growable: false);
      if (names.isEmpty) continue;
      parts.add('${group.name}: ${names.join('، ')}');
    }
    return parts.join(' • ');
  }

  Widget _buildQuantityButton({
    required IconData icon,
    required VoidCallback onTap,
    bool isDelete = false,
  }) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppRadius.sm),
        child: Container(
          padding: EdgeInsets.all(AppSpacing.sm),
          child: Icon(
            icon,
            color: isDelete ? AppColors.error : AppColors.primary,
            size: 18,
          ),
        ),
      ),
    );
  }

Widget _buildOrderSummary(BuildContext context, CartProvider cart) {
    final subtotal = cart.totalPrice;
    final total = subtotal + _deliveryFee;
    final totalStr = '₪${total.toStringAsFixed(2)}';

    return Container(
      padding: EdgeInsets.fromLTRB(
        AppSpacing.lg,
        AppSpacing.lg,
        AppSpacing.lg,
        AppSpacing.md,
      ),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.vertical(
          top: Radius.circular(AppRadius.xl),
        ),
        boxShadow: [
          BoxShadow(
            color: AppColors.shadow,
            blurRadius: 16,
            offset: Offset(0, -4),
          ),
        ],
      ),
      child: SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 36,
              height: 3,
              margin: EdgeInsets.only(bottom: AppSpacing.md),
              decoration: BoxDecoration(
                color: Theme.of(context).dividerColor,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            _buildSummaryRow('المجموع', '₪${subtotal.toStringAsFixed(2)}'),
            SizedBox(height: AppSpacing.sm),
            _buildSummaryRow('التوصيل', '₪${_deliveryFee.toStringAsFixed(2)}'),
            SizedBox(height: AppSpacing.sm),
            Divider(color: Theme.of(context).dividerColor, height: 1),
            SizedBox(height: AppSpacing.sm),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'الإجمالي',
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.bold,
                    color: Theme.of(context).colorScheme.onSurface,
                  ),
                ),
                Text(
                  totalStr,
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: AppColors.primary,
                  ),
                ),
              ],
            ),
            SizedBox(height: AppSpacing.md),
            SizedBox(
              width: double.infinity,
              height: 42,
              child: ElevatedButton(
                onPressed: () => _goToCheckout(cart),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  foregroundColor: Colors.white,
                  padding: EdgeInsets.zero,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(AppRadius.lg),
                  ),
                  elevation: 0,
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(Icons.payments_outlined, size: 18),
                    SizedBox(width: AppSpacing.sm),
                    Text(
                      'متابعة للدفع',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSummaryRow(String label, String value) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: TextStyle(
            fontSize: 13,
            color: Theme.of(context).colorScheme.onSurfaceVariant,
          ),
        ),
        Text(
          value,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w600,
            color: Theme.of(context).colorScheme.onSurface,
          ),
        ),
      ],
    );
  }

  void _goToCheckout(CartProvider cart) {
    if (cart.items.isEmpty) return;
    if (!AuthService.isLoggedIn()) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('يرجى تسجيل الدخول أولاً'),
          backgroundColor: AppColors.error,
        ),
      );
      return;
    }

    final restaurantIds = cart.items.map((item) => item.menuItem.restaurantId).toSet();
    if (restaurantIds.length != 1) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('لا يمكن الطلب من أكثر من مطعم'),
          backgroundColor: AppColors.error,
        ),
      );
      return;
    }

    final restaurantId = restaurantIds.first;
    if (restaurantId <= 0 || cart.items.any((e) => e.menuItem.id <= 0 || e.quantity < 1)) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('بيانات الطلب غير صالحة'),
          backgroundColor: AppColors.error,
        ),
      );
      return;
    }

    Navigator.of(context).push(
      MaterialPageRoute<void>(builder: (_) => CheckoutFlowScreen()),
    );
  }
}


