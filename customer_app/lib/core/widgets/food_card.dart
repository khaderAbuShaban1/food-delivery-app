import 'package:flutter/material.dart';
import 'package:intl/intl.dart' as intl;
import '../../core/theme/app_theme.dart';

class FoodCard extends StatelessWidget {
  final String title;
  final String? image;
  final String subtitle;
  final String price;
  final String? tag;
  final bool isOpen;
  final double? rating;
  final VoidCallback? onTap;
  final VoidCallback? onAddToCart;

  FoodCard({
    super.key,
    required this.title,
    this.image,
    required this.subtitle,
    required this.price,
    this.tag,
    required this.isOpen,
    this.rating,
    this.onTap,
    this.onAddToCart,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: EdgeInsets.symmetric(
          horizontal: AppSpacing.lg,
          vertical: AppSpacing.sm,
        ),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(AppRadius.lg),
          boxShadow: [
            BoxShadow(
              color: AppColors.shadow,
              blurRadius: 12,
              offset: Offset(0, 4),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Image
            Stack(
              children: [
                ClipRRect(
                  borderRadius: BorderRadius.vertical(
                    top: Radius.circular(AppRadius.lg),
                  ),
                  child: AspectRatio(
                    aspectRatio: 16 / 10,
                    child: image != null && image!.isNotEmpty
                        ? Image.network(
                            image!,
                            fit: BoxFit.cover,
                            errorBuilder: (_, _, _) => _buildPlaceholder(context),
                          )
                        : _buildPlaceholder(context),
                  ),
                ),
                // Tag
                if (tag != null)
                  Positioned(
                    top: AppSpacing.sm,
                    right: AppSpacing.sm,
                    child: Container(
                      padding: EdgeInsets.symmetric(
                        horizontal: AppSpacing.sm,
                        vertical: AppSpacing.xs,
                      ),
                      decoration: BoxDecoration(
                        color: AppColors.primary,
                        borderRadius: BorderRadius.circular(AppRadius.sm),
                      ),
                      child: Text(
                        tag!,
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ),
              ],
            ),
            // Content
            Padding(
              padding: EdgeInsets.all(AppSpacing.lg),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Info
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Title & Rating
                        if (rating != null)
                          Row(
                            children: [
                              Expanded(
                                child: Text(
                                  title,
                                  style: TextStyle(
                                    fontSize: 18,
                                    fontWeight: FontWeight.bold,
                                    color: Theme.of(context).colorScheme.onSurface,
                                  ),
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                              Row(
                                children: [
                                  Icon(
                                    Icons.star,
                                    color: AppColors.warning,
                                    size: 18,
                                  ),
                                  SizedBox(width: 2),
                                  Text(
                                    rating!.toStringAsFixed(1),
                                    style: TextStyle(
                                      fontSize: 14,
                                      fontWeight: FontWeight.bold,
                                      color: Theme.of(context).colorScheme.onSurface,
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          )
                        else
                          Text(
                            title,
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: Theme.of(context).colorScheme.onSurface,
                            ),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                        SizedBox(height: AppSpacing.xs),
                        // Subtitle
                        Text(
                          subtitle,
                          style: TextStyle(
                            fontSize: 14,
                            color: Theme.of(context).colorScheme.onSurfaceVariant,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        SizedBox(height: AppSpacing.sm),
                        // Price & Status
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              price,
                              style: TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                                color: AppColors.primary,
                              ),
                            ),
                            _buildStatusBadge(),
                          ],
                        ),
                      ],
                    ),
                  ),
                  // Add button (for menu items)
                  if (onAddToCart != null) ...[
                    SizedBox(width: AppSpacing.md),
                    _buildAddButton(),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPlaceholder(BuildContext context) {
    return Container(
      color: AppColors.secondary,
      child: Center(
        child: Icon(
          Icons.restaurant_outlined,
          size: 48,
          color: Theme.of(context).colorScheme.onSurfaceVariant,
        ),
      ),
    );
  }

  Widget _buildStatusBadge() {
    return Container(
      padding: EdgeInsets.symmetric(
        horizontal: AppSpacing.md,
        vertical: AppSpacing.xs,
      ),
      decoration: BoxDecoration(
        color: isOpen
            ? AppColors.open.withValues(alpha: 0.1)
            : AppColors.closed.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(AppRadius.pill),
      ),
      child: Text(
        isOpen ? 'مفتوح' : 'مغلق',
        style: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.bold,
          color: isOpen ? AppColors.open : AppColors.closed,
        ),
      ),
    );
  }

  Widget _buildAddButton() {
    return GestureDetector(
      onTap: onAddToCart,
      child: Container(
        padding: EdgeInsets.all(AppSpacing.md),
        decoration: BoxDecoration(
          color: AppColors.primary,
          borderRadius: BorderRadius.circular(AppRadius.md),
        ),
        child: Icon(Icons.add, color: Colors.white, size: 24),
      ),
    );
  }
}

// Simple menu item card without the full header
class MenuItemCard extends StatelessWidget {
  final String title;
  final String? image;
  final String? description;
  final double price;
  final String category;
  final VoidCallback? onAddToCart;
  final int quantity;
  final VoidCallback? onIncrease;
  final VoidCallback? onDecrease;
  final bool showCartControls;
  static final intl.NumberFormat _currencyFormatter = intl.NumberFormat.currency(
    locale: 'en_US',
    symbol: '₪ ',
    decimalDigits: 2,
  );

  MenuItemCard({
    super.key,
    required this.title,
    this.image,
    this.description,
    required this.price,
    this.category = 'أخرى',
    this.onAddToCart,
    this.quantity = 0,
    this.onIncrease,
    this.onDecrease,
    this.showCartControls = true,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: EdgeInsets.symmetric(
        horizontal: AppSpacing.lg,
        vertical: AppSpacing.sm,
      ),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(AppRadius.xl),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.06),
            blurRadius: 14,
            offset: Offset(0, 8),
          ),
        ],
      ),
      child: Row(
        children: [
          // Image
          ClipRRect(
            borderRadius: BorderRadius.horizontal(
              right: Radius.circular(AppRadius.xl),
            ),
            child: SizedBox(
              width: 105,
              height: 110,
              child: image != null && image!.isNotEmpty
                  ? Image.network(
                      image!,
                      fit: BoxFit.cover,
                      errorBuilder: (_, _, _) => _buildPlaceholder(context),
                    )
                  : _buildPlaceholder(context),
            ),
          ),
          SizedBox(width: AppSpacing.md),
          // Content
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                SizedBox(height: AppSpacing.sm),
                Text(
                  title,
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w800,
                    color: Theme.of(context).colorScheme.onSurface,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
                if (description != null && description!.isNotEmpty) ...[
                  SizedBox(height: AppSpacing.xs),
                  Text(
                    description!,
                    style: TextStyle(
                      fontSize: 13,
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
                SizedBox(height: AppSpacing.sm),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Directionality(
                      textDirection: TextDirection.ltr,
                      child: Text(
                        _currencyFormatter.format(price),
                        style: TextStyle(
                          fontSize: 19,
                          fontWeight: FontWeight.w800,
                          color: AppColors.primary,
                        ),
                      ),
                    ),
                    if (!showCartControls)
                      SizedBox(width: 72, height: 36)
                    else
                      quantity > 0 ? _buildQuantityControls(context) : _buildAddButton(),
                  ],
                ),
                SizedBox(height: AppSpacing.sm),
              ],
            ),
          ),
          SizedBox(width: AppSpacing.md),
        ],
      ),
    );
  }

  Widget _buildPlaceholder(BuildContext context) {
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

  Widget _buildAddButton() {
    return GestureDetector(
      onTap: onAddToCart,
      child: Container(
        padding: EdgeInsets.symmetric(
          horizontal: AppSpacing.md,
          vertical: AppSpacing.sm,
        ),
        decoration: BoxDecoration(
          color: AppColors.primary,
          borderRadius: BorderRadius.circular(AppRadius.pill),
        ),
        child: Row(
          children: [
            Icon(Icons.add_rounded, color: Colors.white, size: 18),
            SizedBox(width: 4),
            Text(
              'إضافة',
              style: TextStyle(
                color: Colors.white,
                fontSize: 12,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildQuantityControls(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.secondary,
        borderRadius: BorderRadius.circular(AppRadius.md),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          GestureDetector(
            onTap: onDecrease,
            child: Container(
              padding: EdgeInsets.all(AppSpacing.sm),
              child: Icon(
                quantity > 1 ? Icons.remove : Icons.delete_outline,
                color: AppColors.primary,
                size: 20,
              ),
            ),
          ),
          Container(
            padding: EdgeInsets.symmetric(horizontal: AppSpacing.sm),
            child: Text(
              quantity.toString(),
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
                color: Theme.of(context).colorScheme.onSurface,
              ),
            ),
          ),
          GestureDetector(
            onTap: onIncrease,
            child: Container(
              padding: EdgeInsets.all(AppSpacing.sm),
              child: Icon(Icons.add, color: AppColors.primary, size: 20),
            ),
          ),
        ],
      ),
    );
  }
}




