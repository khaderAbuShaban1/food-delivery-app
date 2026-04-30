import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/theme/app_theme.dart';
import '../../core/models/restaurant.dart';
import '../../core/models/menu_item.dart';
import '../../core/api/api_client.dart';
import '../../core/services/cart_provider.dart';
import '../../core/widgets/widgets.dart';
import '../home/main_screen.dart';

class RestaurantScreen extends StatefulWidget {
  final Restaurant restaurant;

  const RestaurantScreen({super.key, required this.restaurant});

  @override
  State<RestaurantScreen> createState() => _RestaurantScreenState();
}

class _RestaurantScreenState extends State<RestaurantScreen> {
  List<MenuItem> _menuItems = [];
  bool _isLoading = true;
  String? _errorMessage;
  String _selectedCategory = 'الكل';

  @override
  void initState() {
    super.initState();
    _loadMenuItems();
  }

  Future<void> _loadMenuItems() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final response = await ApiClient.get(
        '/restaurants/${widget.restaurant.id}/menu',
      );

      if (response['success'] == true && response['data'] != null) {
        final List data = response['data'];
        setState(() {
          _menuItems = data.map((json) => MenuItem.fromJson(json)).toList();
          _isLoading = false;
        });
      } else {
        setState(() {
          _errorMessage = response['message'] ?? 'حدث خطأ';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _errorMessage = 'فشل في الاتصال بالخادم';
        _isLoading = false;
      });
    }
  }

  List<String> get _categories {
    final cats = _menuItems
        .map((item) => item.category ?? 'أخرى')
        .where((c) => c.isNotEmpty)
        .toSet()
        .toList();
    return ['الكل', ...cats];
  }

  List<MenuItem> get _filteredItems {
    if (_selectedCategory == 'الكل') return _menuItems;
    return _menuItems
        .where((item) => item.category == _selectedCategory)
        .toList();
  }

  void _onAddToCart(MenuItem item) {
    context.read<CartProvider>().addItem(item);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('تمت إضافة ${item.name} للسلة'),
        backgroundColor: AppColors.primary,
        duration: const Duration(seconds: 1),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: Stack(
        children: [
          CustomScrollView(
            slivers: [
              SliverAppBar(
                expandedHeight: 220,
                pinned: true,
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
                leading: Container(
                  margin: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Colors.black.withValues(alpha: 0.3),
                    shape: BoxShape.circle,
                  ),
                  child: IconButton(
                    icon: const Icon(Icons.arrow_forward),
                    onPressed: () => Navigator.pop(context),
                  ),
                ),
                flexibleSpace: FlexibleSpaceBar(
                  background: Stack(
                    fit: StackFit.expand,
                    children: [
                      widget.restaurant.image != null &&
                              widget.restaurant.image!.isNotEmpty
                          ? Image.network(
                              widget.restaurant.image!,
                              fit: BoxFit.cover,
                              errorBuilder: (_, _, _) =>
                                  _buildHeaderPlaceholder(),
                            )
                          : _buildHeaderPlaceholder(),
                      Container(
                        decoration: BoxDecoration(
                          gradient: LinearGradient(
                            begin: Alignment.topCenter,
                            end: Alignment.bottomCenter,
                            colors: [
                              Colors.transparent,
                              Colors.black.withValues(alpha: 0.7),
                            ],
                          ),
                        ),
                      ),
                      Positioned(
                        bottom: 16,
                        right: 16,
                        left: 16,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              widget.restaurant.name,
                              style: const TextStyle(
                                fontSize: 24,
                                fontWeight: FontWeight.bold,
                                color: Colors.white,
                              ),
                            ),
                            const SizedBox(height: 8),
                            Row(
                              children: [
                                Text(
                                  widget.restaurant.category,
                                  style: TextStyle(
                                    fontSize: 14,
                                    color: Colors.white.withValues(alpha: 0.9),
                                  ),
                                ),
                                const SizedBox(width: 16),
                                if (widget.restaurant.rating > 0) ...[
                                  const Icon(
                                    Icons.star,
                                    color: AppColors.warning,
                                    size: 16,
                                  ),
                                  const SizedBox(width: 4),
                                  Text(
                                    widget.restaurant.rating.toStringAsFixed(1),
                                    style: TextStyle(
                                      fontSize: 14,
                                      color: Colors.white.withValues(
                                        alpha: 0.9,
                                      ),
                                    ),
                                  ),
                                  const SizedBox(width: 16),
                                ],
                                StatusBadge(
                                  label: widget.restaurant.isOpen
                                      ? 'مفتوح'
                                      : 'مغلق',
                                  color: widget.restaurant.isOpen
                                      ? AppColors.open
                                      : AppColors.closed,
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              if (!_isLoading && _errorMessage == null)
                SliverToBoxAdapter(
                  child: Container(
                    height: 50,
                    margin: const EdgeInsets.symmetric(vertical: AppSpacing.md),
                    child: ListView.builder(
                      scrollDirection: Axis.horizontal,
                      padding: const EdgeInsets.symmetric(
                        horizontal: AppSpacing.lg,
                      ),
                      itemCount: _categories.length,
                      itemBuilder: (context, index) {
                        final category = _categories[index];
                        final isSelected = category == _selectedCategory;
                        return CategoryChip(
                          label: category,
                          isSelected: isSelected,
                          onTap: () =>
                              setState(() => _selectedCategory = category),
                        );
                      },
                    ),
                  ),
                ),
              if (_isLoading)
                SliverToBoxAdapter(child: _buildLoading())
              else if (_errorMessage != null)
                SliverToBoxAdapter(
                  child: ErrorState(
                    message: _errorMessage!,
                    onRetry: _loadMenuItems,
                  ),
                )
              else if (_filteredItems.isEmpty)
                const SliverToBoxAdapter(
                  child: EmptyState(
                    icon: Icons.restaurant_outlined,
                    title: 'لا توجد أصناف',
                  ),
                )
              else
                SliverList(
                  delegate: SliverChildBuilderDelegate((context, index) {
                    final item = _filteredItems[index];
                    return Consumer<CartProvider>(
                      builder: (context, cart, child) {
                        final inCart = cart.isInCart(item.id);
                        final quantity = cart.getQuantity(item.id);
                        return MenuItemCard(
                          title: item.name,
                          image: item.image,
                          description: item.description,
                          price: item.price,
                          category: item.category ?? 'أخرى',
                          quantity: quantity,
                          onAddToCart: inCart ? null : () => _onAddToCart(item),
                          onIncrease: () =>
                              cart.updateQuantity(item, quantity + 1),
                          onDecrease: () {
                            if (quantity > 1) {
                              cart.updateQuantity(item, quantity - 1);
                            } else {
                              cart.removeItem(item.id);
                            }
                          },
                        );
                      },
                    );
                  }, childCount: _filteredItems.length),
                ),
              const SliverToBoxAdapter(child: SizedBox(height: 100)),
            ],
          ),
          Consumer<CartProvider>(
            builder: (context, cart, _) {
              final visible = cart.itemCount > 0;
              return AnimatedPositioned(
                duration: const Duration(milliseconds: 250),
                curve: Curves.easeOut,
                left: AppSpacing.lg,
                right: AppSpacing.lg,
                bottom: visible ? AppSpacing.lg : -140,
                child: AnimatedOpacity(
                  duration: const Duration(milliseconds: 220),
                  opacity: visible ? 1 : 0,
                  child: IgnorePointer(
                    ignoring: !visible,
                    child: _buildGoToCartButton(cart),
                  ),
                ),
              );
            },
          ),
        ],
      ),
    );
  }

  Widget _buildHeaderPlaceholder() {
    return Container(
      color: AppColors.primary,
      child: const Center(
        child: Icon(Icons.restaurant_outlined, size: 60, color: Colors.white54),
      ),
    );
  }

  Widget _buildLoading() {
    return ListView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: 4,
      itemBuilder: (context, index) {
        return Container(
          margin: const EdgeInsets.symmetric(
            horizontal: AppSpacing.lg,
            vertical: AppSpacing.sm,
          ),
          padding: const EdgeInsets.all(AppSpacing.md),
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(AppRadius.lg),
          ),
          child: const LoadingSkeleton(height: 100, borderRadius: AppRadius.md),
        );
      },
    );
  }

  Widget _buildGoToCartButton(CartProvider cart) {
    return SafeArea(
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () {
            Navigator.pushAndRemoveUntil(
              context,
              MaterialPageRoute(
                builder: (_) => const MainScreen(initialIndex: 1),
              ),
              (route) => false,
            );
          },
          borderRadius: BorderRadius.circular(AppRadius.xl),
          child: Ink(
            decoration: BoxDecoration(
              color: AppColors.primary,
              borderRadius: BorderRadius.circular(AppRadius.xl),
              boxShadow: [
                BoxShadow(
                  color: AppColors.primary.withValues(alpha: 0.35),
                  blurRadius: 18,
                  offset: const Offset(0, 8),
                ),
              ],
            ),
            child: Padding(
              padding: const EdgeInsets.symmetric(
                horizontal: AppSpacing.lg,
                vertical: AppSpacing.md,
              ),
              child: Row(
                children: [
                  const Icon(Icons.arrow_back_rounded, color: Colors.white),
                  const SizedBox(width: AppSpacing.sm),
                  const Expanded(
                    child: Text(
                      'اذهب إلى السلة',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                    ),
                  ),
                  Text(
                    '${cart.itemCount} • ₪${cart.totalPrice.toStringAsFixed(2)}',
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      color: Colors.white,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
