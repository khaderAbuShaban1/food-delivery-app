import 'package:flutter/material.dart';
import 'dart:async';
import 'package:provider/provider.dart';
import '../../core/theme/app_theme.dart';
import '../../core/models/restaurant.dart';
import '../../core/models/menu_item.dart';
import '../../core/api/api_client.dart';
import '../../core/services/auth_service.dart';
import '../../core/services/cart_provider.dart';
import '../../core/services/realtime_sync_service.dart';
import '../../core/widgets/widgets.dart';
import '../home/main_screen.dart';
import '../meal/meal_details_screen.dart';

class RestaurantScreen extends StatefulWidget {
  final Restaurant restaurant;

  RestaurantScreen({super.key, required this.restaurant});

  @override
  State<RestaurantScreen> createState() => _RestaurantScreenState();
}

class _RestaurantScreenState extends State<RestaurantScreen> {
  late Restaurant _restaurant;
  List<MenuItem> _menuItems = [];
  bool _isLoading = true;
  String? _errorMessage;
  String _selectedCategory = 'الكل';
  bool _isSubmittingRating = false;
  StreamSubscription<Restaurant?>? _restaurantSub;

  @override
  void initState() {
    super.initState();
    _restaurant = widget.restaurant;
    _loadMenuItems();
    _refreshRestaurantDetails();
    _startRealtimeListener();
  }

  void _startRealtimeListener() {
    _restaurantSub?.cancel();
    _restaurantSub = RealtimeSyncService.watchRestaurant(
      _restaurant.id.toString(),
    ).listen((restaurant) {
      if (!mounted || restaurant == null) return;
      setState(() {
        _restaurant = _restaurant.copyWith(
          rating: restaurant.rating,
          ratingsCount: restaurant.ratingsCount,
          myRating: restaurant.myRating,
        );
      });
    });
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

  void _openMealDetails(MenuItem item) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => MealDetailsScreen(menuItem: item),
      ),
    );
  }

  Future<void> _refreshRestaurantDetails() async {
    final response = await ApiClient.get('/restaurants/${_restaurant.id}');
    if (!mounted) return;
    if (response['success'] == true && response['data'] is Map<String, dynamic>) {
      setState(() {
        _restaurant = Restaurant.fromJson(response['data'] as Map<String, dynamic>);
      });
      await RealtimeSyncService.syncRestaurant(_restaurant);
    }
  }

  Future<void> _submitRating() async {
    if (!AuthService.isLoggedIn()) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('يرجى تسجيل الدخول لتقييم المطعم'),
          backgroundColor: AppColors.error,
        ),
      );
      return;
    }

    int selected = _restaurant.myRating ?? (_restaurant.rating > 0 ? _restaurant.rating.round() : 5);

    final rating = await showModalBottomSheet<int>(
      context: context,
      showDragHandle: true,
      backgroundColor: Theme.of(context).colorScheme.surface,
      builder: (context) {
        final scheme = Theme.of(context).colorScheme;
        return StatefulBuilder(
          builder: (context, setBottomState) {
            return Padding(
              padding: EdgeInsets.all(AppSpacing.lg),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    'قيّم ${_restaurant.name}',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w800,
                      color: scheme.onSurface,
                    ),
                  ),
                  SizedBox(height: AppSpacing.md),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: List.generate(5, (index) {
                      final star = index + 1;
                      final active = star <= selected;
                      return IconButton(
                        onPressed: () => setBottomState(() => selected = star),
                        icon: Icon(
                          active
                              ? Icons.star_rounded
                              : Icons.star_border_rounded,
                          color: active ? AppColors.warning : Theme.of(context).colorScheme.onSurfaceVariant,
                          size: 34,
                        ),
                      );
                    }),
                  ),
                  SizedBox(height: AppSpacing.md),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: () => Navigator.pop(context, selected),
                      child: Text('حفظ التقييم'),
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );

    if (rating == null) return;

    setState(() => _isSubmittingRating = true);
    final response = await ApiClient.post(
      '/restaurants/${_restaurant.id}/rate',
      {'rating': rating},
    );

    if (!mounted) return;
    setState(() => _isSubmittingRating = false);

    if (response['success'] == true && response['data'] != null) {
      setState(() {
        _restaurant = Restaurant.fromJson(response['data']);
      });
      await RealtimeSyncService.syncRestaurant(_restaurant);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('تم حفظ التقييم'),
          backgroundColor: AppColors.success,
        ),
      );
      return;
    }

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          response['message']?.toString() ?? 'تعذر حفظ التقييم',
        ),
        backgroundColor: AppColors.error,
      ),
    );
  }

  Widget _buildRatingSummary() {
    if (_restaurant.ratingsCount == 0) {
      return Text(
        'لا توجد تقييمات بعد',
        style: TextStyle(
          fontSize: 13,
          color: Colors.white70,
          fontWeight: FontWeight.w600,
        ),
      );
    }

    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          _restaurant.rating.toStringAsFixed(1),
          style: TextStyle(
            fontSize: 14,
            color: Colors.white,
            fontWeight: FontWeight.bold,
          ),
        ),
        SizedBox(width: 4),
        Icon(Icons.star_rounded, color: AppColors.warning, size: 16),
        SizedBox(width: 6),
        Text(
          '(${_restaurant.ratingsCount} تقييم)',
          style: TextStyle(
            fontSize: 13,
            color: Colors.white70,
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
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
                  margin: EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Colors.black.withValues(alpha: 0.3),
                    shape: BoxShape.circle,
                  ),
                  child: IconButton(
                    icon: Icon(Icons.arrow_forward),
                    onPressed: () => Navigator.pop(context),
                  ),
                ),
                flexibleSpace: FlexibleSpaceBar(
                  background: Stack(
                    fit: StackFit.expand,
                    children: [
                      _restaurant.image != null &&
                              _restaurant.image!.isNotEmpty
                          ? Image.network(
                              _restaurant.image!,
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
                              _restaurant.name,
                              style: TextStyle(
                                fontSize: 24,
                                fontWeight: FontWeight.bold,
                                color: Colors.white,
                              ),
                            ),
                            SizedBox(height: 8),
                            Row(
                              children: [
                                Text(
                                  _restaurant.category,
                                  style: TextStyle(
                                    fontSize: 14,
                                    color: Colors.white.withValues(alpha: 0.9),
                                  ),
                                ),
                                SizedBox(width: 16),
                                _buildRatingSummary(),
                                SizedBox(width: 12),
                                StatusBadge(
                                  label: _restaurant.isOpen
                                      ? 'مفتوح'
                                      : 'مغلق',
                                  color: _restaurant.isOpen
                                      ? AppColors.open
                                      : AppColors.closed,
                                ),
                              ],
                            ),
                            SizedBox(height: AppSpacing.sm),
                            SizedBox(
                              height: 34,
                              child: OutlinedButton.icon(
                                onPressed: _isSubmittingRating
                                    ? null
                                    : _submitRating,
                                icon: _isSubmittingRating
                                    ? SizedBox(
                                        width: 14,
                                        height: 14,
                                        child: CircularProgressIndicator(
                                          strokeWidth: 2,
                                        ),
                                      )
                                    : Icon(Icons.star_outline_rounded, size: 18),
                                label: Text(
                                  _restaurant.myRating != null
                                      ? 'تعديل تقييمي (${_restaurant.myRating})'
                                      : 'قيّم المطعم',
                                  style: TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                                style: OutlinedButton.styleFrom(
                                  foregroundColor: Colors.white,
                                  side: BorderSide(
                                    color: Colors.white.withValues(alpha: 0.65),
                                  ),
                                  padding: EdgeInsets.symmetric(
                                    horizontal: AppSpacing.md,
                                  ),
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
              if (!_isLoading && _errorMessage == null)
                SliverToBoxAdapter(
                  child: Container(
                    height: 50,
                    margin: EdgeInsets.symmetric(vertical: AppSpacing.md),
                    child: ListView.builder(
                      scrollDirection: Axis.horizontal,
                      padding: EdgeInsets.symmetric(
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
                SliverToBoxAdapter(
                  child: EmptyState(
                    icon: Icons.restaurant_outlined,
                    title: 'لا توجد أصناف',
                  ),
                )
              else
                SliverList(
                  delegate: SliverChildBuilderDelegate((context, index) {
                    final item = _filteredItems[index];
                    return Material(
                      color: Colors.transparent,
                      child: InkWell(
                        onTap: () => _openMealDetails(item),
                        child: MenuItemCard(
                          title: item.name,
                          image: item.image,
                          description: item.description,
                          price: item.price,
                          category: item.category ?? 'أخرى',
                          showCartControls: false,
                        ),
                      ),
                    );
                  }, childCount: _filteredItems.length),
                ),
              SliverToBoxAdapter(child: SizedBox(height: 100)),
            ],
          ),
          Consumer<CartProvider>(
            builder: (context, cart, _) {
              final visible = cart.itemCount > 0;
              return AnimatedPositioned(
                duration: Duration(milliseconds: 250),
                curve: Curves.easeOut,
                left: AppSpacing.lg,
                right: AppSpacing.lg,
                bottom: visible ? AppSpacing.lg : -140,
                child: AnimatedOpacity(
                  duration: Duration(milliseconds: 220),
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
      child: Center(
        child: Icon(Icons.restaurant_outlined, size: 60, color: Colors.white54),
      ),
    );
  }

  Widget _buildLoading() {
    return ListView.builder(
      shrinkWrap: true,
      physics: NeverScrollableScrollPhysics(),
      itemCount: 4,
      itemBuilder: (context, index) {
        return Container(
          margin: EdgeInsets.symmetric(
            horizontal: AppSpacing.lg,
            vertical: AppSpacing.sm,
          ),
          padding: EdgeInsets.all(AppSpacing.md),
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.surface,
            borderRadius: BorderRadius.circular(AppRadius.lg),
          ),
          child: LoadingSkeleton(height: 100, borderRadius: AppRadius.md),
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
                builder: (_) => MainScreen(initialIndex: 1),
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
                  offset: Offset(0, 8),
                ),
              ],
            ),
            child: Padding(
              padding: EdgeInsets.symmetric(
                horizontal: AppSpacing.lg,
                vertical: AppSpacing.md,
              ),
              child: Row(
                children: [
                  Icon(Icons.arrow_back_rounded, color: Colors.white),
                  SizedBox(width: AppSpacing.sm),
                  Expanded(
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
                    style: TextStyle(
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

  @override
  void dispose() {
    _restaurantSub?.cancel();
    super.dispose();
  }
}



