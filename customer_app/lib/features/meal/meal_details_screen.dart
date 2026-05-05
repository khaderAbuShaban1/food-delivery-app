import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/models/menu_item.dart';
import '../../core/services/cart_provider.dart';
import '../../core/theme/app_theme.dart';

class MealDetailsScreen extends StatefulWidget {
  final MenuItem menuItem;

  const MealDetailsScreen({super.key, required this.menuItem});

  @override
  State<MealDetailsScreen> createState() => _MealDetailsScreenState();
}

class _MealDetailsScreenState extends State<MealDetailsScreen> {
  int _quantity = 1;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final current = context.read<CartProvider>().getQuantity(widget.menuItem.id);
    if (_quantity == 1 && current > 0) {
      _quantity = current;
    }
  }

  void _inc() => setState(() => _quantity++);

  void _dec() {
    if (_quantity <= 1) return;
    setState(() => _quantity--);
  }

  void _addToCart() {
    final cart = context.read<CartProvider>();
    final existing = cart.getQuantity(widget.menuItem.id);
    if (existing == 0) {
      cart.addItem(widget.menuItem);
      if (_quantity > 1) cart.updateQuantity(widget.menuItem, _quantity);
    } else {
      cart.updateQuantity(widget.menuItem, _quantity);
    }

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('تمت إضافة ${widget.menuItem.name} للسلة'),
        backgroundColor: AppColors.primary,
        duration: const Duration(seconds: 1),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final item = widget.menuItem;

    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: AppColors.background,
        appBar: AppBar(
          title: const Text('تفاصيل الوجبة'),
          backgroundColor: AppColors.background,
        ),
        body: SafeArea(
          child: Column(
            children: [
              Expanded(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.all(AppSpacing.lg),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      ClipRRect(
                        borderRadius: BorderRadius.circular(AppRadius.xl),
                        child: AspectRatio(
                          aspectRatio: 16 / 10,
                          child: item.image != null && item.image!.isNotEmpty
                              ? Image.network(
                                  item.image!,
                                  fit: BoxFit.cover,
                                  errorBuilder: (context, error, stackTrace) =>
                                      _imagePlaceholder(),
                                )
                              : _imagePlaceholder(),
                        ),
                      ),
                      const SizedBox(height: AppSpacing.lg),
                      Text(
                        item.name,
                        style: const TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.w900,
                          color: AppColors.textPrimary,
                        ),
                        textAlign: TextAlign.right,
                      ),
                      if (item.description != null &&
                          item.description!.trim().isNotEmpty) ...[
                        const SizedBox(height: AppSpacing.sm),
                        Text(
                          item.description!,
                          style: const TextStyle(
                            fontSize: 14,
                            height: 1.5,
                            color: AppColors.textSecondary,
                          ),
                          textAlign: TextAlign.right,
                        ),
                      ],
                      const SizedBox(height: AppSpacing.lg),
                      Container(
                        padding: const EdgeInsets.all(AppSpacing.md),
                        decoration: BoxDecoration(
                          color: AppColors.surface,
                          borderRadius: BorderRadius.circular(AppRadius.xl),
                          border: Border.all(color: AppColors.border),
                        ),
                        child: Row(
                          children: [
                            _qtyButton(
                              icon: Icons.remove_rounded,
                              onTap: _dec,
                              enabled: _quantity > 1,
                            ),
                            const SizedBox(width: AppSpacing.sm),
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: AppSpacing.lg,
                                vertical: AppSpacing.sm,
                              ),
                              decoration: BoxDecoration(
                                color: AppColors.secondary,
                                borderRadius:
                                    BorderRadius.circular(AppRadius.pill),
                              ),
                              child: Text(
                                _quantity.toString(),
                                style: const TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w900,
                                  color: AppColors.textPrimary,
                                ),
                              ),
                            ),
                            const SizedBox(width: AppSpacing.sm),
                            _qtyButton(
                              icon: Icons.add_rounded,
                              onTap: _inc,
                              enabled: true,
                            ),
                            const Spacer(),
                            Directionality(
                              textDirection: TextDirection.ltr,
                              child: Text(
                                '₪${item.price.toStringAsFixed(2)}',
                                style: const TextStyle(
                                  fontSize: 20,
                                  fontWeight: FontWeight.w900,
                                  color: AppColors.primary,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.fromLTRB(
                  AppSpacing.lg,
                  AppSpacing.sm,
                  AppSpacing.lg,
                  AppSpacing.lg,
                ),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.06),
                      blurRadius: 18,
                      offset: const Offset(0, -10),
                    ),
                  ],
                ),
                child: SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _addToCart,
                    child: const Text('إضافة إلى السلة'),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _qtyButton({
    required IconData icon,
    required VoidCallback onTap,
    required bool enabled,
  }) {
    return Material(
      color: enabled ? AppColors.primary : AppColors.border,
      borderRadius: BorderRadius.circular(AppRadius.pill),
      child: InkWell(
        onTap: enabled ? onTap : null,
        borderRadius: BorderRadius.circular(AppRadius.pill),
        child: Padding(
          padding: const EdgeInsets.all(10),
          child: Icon(
            icon,
            size: 18,
            color: enabled ? Colors.white : AppColors.textHint,
          ),
        ),
      ),
    );
  }

  Widget _imagePlaceholder() {
    return Container(
      color: AppColors.secondary,
      child: const Center(
        child: Icon(
          Icons.fastfood_outlined,
          size: 42,
          color: AppColors.textHint,
        ),
      ),
    );
  }
}

