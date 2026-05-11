import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api/api_client.dart';
import '../../core/models/address.dart';
import '../../core/models/restaurant.dart';
import '../../core/services/address_service.dart';
import '../../core/services/auth_service.dart';
import '../../core/services/cart_provider.dart';
import '../../core/services/realtime_sync_service.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/widgets.dart';
import '../restaurant/restaurant_screen.dart';

class HomeScreen extends StatefulWidget {
  HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  List<Restaurant> _restaurants = [];
  bool _isLoading = true;
  String? _errorMessage;
  String? _selectedCategory;
  String _searchQuery = '';
  bool _onlyOpen = false;
  double _minRating = 0;
  String _priceFilter = 'all';
  Timer? _searchDebounce;
  StreamSubscription<Map<String, dynamic>?>? _userSub;
  StreamSubscription<List<Restaurant>>? _restaurantsSub;
  final _searchController = TextEditingController();
  Timer? _realtimeBackfillTimer;
  String? _profileImage;
  int _imageCacheKey = 0;
  List<Address> _addresses = [];
  Address? _selectedAddress;
  bool _isAddressLoading = true;

  // Removed per design request.
  final List<String> _quickFilters = [];

  @override
  void initState() {
    super.initState();
    _loadRestaurants();
    _subscribeToUser();
    _loadUserProfile();
    _loadAddresses();
    _startRealtimeListeners();
    _startRealtimeBackfillLoop();
  }

  void _startRealtimeBackfillLoop() {
    _realtimeBackfillTimer?.cancel();
    _realtimeBackfillTimer = Timer.periodic(Duration(seconds: 10), (_) {
      _loadRestaurants(showLoading: false);
    });
  }

  void _subscribeToUser() {
    _userSub?.cancel();
    _userSub = AuthService.watchCurrentUser().listen((user) {
      if (!mounted || user == null) return;
      setState(() {
        _profileImage = user['profile_image']?.toString();
        _imageCacheKey = DateTime.now().millisecondsSinceEpoch;
      });
    });
  }

  Future<void> _startRealtimeListeners() async {
    await AuthService.fetchCurrentUser();

    _restaurantsSub?.cancel();
    _restaurantsSub = RealtimeSyncService.watchRestaurants().listen((items) {
      if (!mounted) return;
      if (items.isEmpty && _restaurants.isNotEmpty) return;
      setState(() {
        _restaurants = items;
        _isLoading = false;
        _errorMessage = null;
      });
    }, onError: (_) {
      if (!mounted) return;
      setState(() {
        _errorMessage = 'تعذر مزامنة المطاعم مباشرة';
        _isLoading = false;
      });
    });
  }

  Future<void> _loadUserProfile() async {
    await AuthService.fetchCurrentUser();
    if (!mounted) return;
    setState(() {
      _profileImage = AuthService.currentUserImage;
      _imageCacheKey = DateTime.now().millisecondsSinceEpoch;
    });
  }

  String? _getImageUrlWithCacheBuster(String? url) {
    if (url == null || url.isEmpty) return null;
    final separator = url.contains('?') ? '&' : '?';
    return '$url${separator}v=$_imageCacheKey';
  }

  Future<void> _loadAddresses({bool showLoading = true}) async {
    if (showLoading) {
      setState(() => _isAddressLoading = true);
    }
    try {
      final addresses = await AddressService.getAddresses();
      if (!mounted) return;
      final resolved = _resolveSelectedAddress(addresses);
      setState(() {
        _addresses = addresses;
        _selectedAddress = resolved;
        if (showLoading) _isAddressLoading = false;
      });
      context.read<CartProvider>().setDeliveryAddressId(resolved?.id);
    } catch (_) {
      if (!mounted) return;
      setState(() {
        if (showLoading) _isAddressLoading = false;
      });
    }
  }

  Address? _resolveSelectedAddress(List<Address> addresses) {
    if (addresses.isEmpty) return null;
    final currentId = _selectedAddress?.id;
    if (currentId != null) {
      for (final address in addresses) {
        if (address.id == currentId) return address;
      }
    }
    for (final address in addresses) {
      if (address.isDefault) return address;
    }
    return addresses.first;
  }

  Future<void> _onAddressTap() async {
    if (_isAddressLoading) return;

    if (_addresses.isEmpty) {
      await Navigator.pushNamed(context, '/addresses');
      if (!mounted) return;
      await _loadAddresses();
      return;
    }

    final selected = await showModalBottomSheet<Address>(
      context: context,
      showDragHandle: true,
      backgroundColor: Theme.of(context).colorScheme.surface,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(AppRadius.xl)),
      ),
      builder: (ctx) {
        return SafeArea(
          child: ListView.separated(
            shrinkWrap: true,
            padding: EdgeInsets.all(AppSpacing.lg),
            itemCount: _addresses.length + 1,
            separatorBuilder: (_, __) => SizedBox(height: AppSpacing.sm),
            itemBuilder: (context, index) {
              if (index == 0) {
                return Text(
                  'اختر عنوان التوصيل',
                  textAlign: TextAlign.right,
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w800,
                    color: Theme.of(context).colorScheme.onSurface,
                  ),
                );
              }

              final address = _addresses[index - 1];
              final isSelected = _selectedAddress?.id == address.id;
              return InkWell(
                borderRadius: BorderRadius.circular(AppRadius.lg),
                onTap: () => Navigator.pop(ctx, address),
                child: Container(
                  padding: EdgeInsets.all(AppSpacing.md),
                  decoration: BoxDecoration(
                    color: isSelected
                        ? AppColors.primary.withValues(alpha: 0.08)
                        : Theme.of(context).scaffoldBackgroundColor,
                    borderRadius: BorderRadius.circular(AppRadius.lg),
                    border: Border.all(
                      color: isSelected ? AppColors.primary : Theme.of(context).dividerColor,
                    ),
                  ),
                  child: Row(
                    children: [
                      if (address.isDefault)
                        Container(
                          padding: EdgeInsets.symmetric(
                            horizontal: AppSpacing.sm,
                            vertical: AppSpacing.xs,
                          ),
                          decoration: BoxDecoration(
                            color: AppColors.primary.withValues(alpha: 0.14),
                            borderRadius: BorderRadius.circular(AppRadius.pill),
                          ),
                          child: Text(
                            'الافتراضي',
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                              color: AppColors.primaryDark,
                            ),
                          ),
                        ),
                      SizedBox(width: AppSpacing.sm),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            Text(
                              address.title,
                              textAlign: TextAlign.right,
                              style: TextStyle(
                                fontSize: 15,
                                fontWeight: FontWeight.w700,
                                color: Theme.of(context).colorScheme.onSurface,
                              ),
                            ),
                            SizedBox(height: 2),
                            Text(
                              address.fullAddress,
                              textAlign: TextAlign.right,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                fontSize: 12,
                                color: Theme.of(context).colorScheme.onSurfaceVariant,
                              ),
                            ),
                          ],
                        ),
                      ),
                      Icon(
                        isSelected
                            ? Icons.radio_button_checked
                            : Icons.radio_button_off,
                        color: isSelected
                            ? AppColors.primary
                            : Theme.of(context).colorScheme.onSurfaceVariant,
                        size: 20,
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
        );
      },
    );

    if (selected == null || !mounted) return;

    // Reflect selection immediately in the header.
    setState(() => _selectedAddress = selected);
    context.read<CartProvider>().setDeliveryAddressId(selected.id);

    if (!selected.isDefault) {
      try {
        await AddressService.updateAddress(
          id: selected.id,
          title: selected.title,
          city: selected.city,
          street: selected.street,
          details: selected.details,
          isDefault: true,
        );
        if (!mounted) return;
        setState(() {
          _addresses = _addresses
              .map(
                (a) => Address(
                  id: a.id,
                  title: a.title,
                  city: a.city,
                  street: a.street,
                  details: a.details,
                  isDefault: a.id == selected.id,
                ),
              )
              .toList();
          _selectedAddress = _resolveSelectedAddress(_addresses);
        });
        context
            .read<CartProvider>()
            .setDeliveryAddressId(_selectedAddress?.id);
      } catch (e) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(e.toString().replaceFirst('Exception: ', '')),
            backgroundColor: AppColors.error,
          ),
        );
        await _loadAddresses();
      }
    }
  }

  @override
  void dispose() {
    _searchDebounce?.cancel();
    _realtimeBackfillTimer?.cancel();
    _userSub?.cancel();
    _restaurantsSub?.cancel();
    _searchController.dispose();
    super.dispose();
  }

  void _navigateToProfile() {
    AuthService.fetchCurrentUser();
    if (!mounted) return;
    setState(() {
      _profileImage = AuthService.currentUserImage;
      _imageCacheKey = DateTime.now().millisecondsSinceEpoch;
    });
  }

  Future<void> _loadRestaurants({bool showLoading = true}) async {
    if (showLoading) {
      setState(() {
        _isLoading = true;
        _errorMessage = null;
      });
    }

    try {
      final response = await ApiClient.get('/restaurants');

      if (response['success'] == true && response['data'] != null) {
        final List data = response['data'];
        final restaurants = data.map((json) => Restaurant.fromJson(json)).toList();
        try {
          await RealtimeSyncService.syncRestaurants(restaurants);
        } catch (_) {
          // Firestore sync must not block showing API restaurants.
        }
        setState(() {
          _restaurants = restaurants;
          if (showLoading) _isLoading = false;
          if (!showLoading && _errorMessage != null) _errorMessage = null;
        });
      } else {
        setState(() {
          _errorMessage = response['message'] ?? 'حدث خطأ';
          if (showLoading) _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _errorMessage = 'فشل في الاتصال بالخادم';
        if (showLoading) _isLoading = false;
      });
    }
  }

  List<Restaurant> get _filteredRestaurants {
    final query = _searchQuery.trim().toLowerCase();
    return _restaurants.where((restaurant) {
      final matchesCategory =
          _selectedCategory == null ||
          _selectedCategory == 'الكل' ||
          restaurant.category.toLowerCase() == _selectedCategory!.toLowerCase();
      final matchesOpen = !_onlyOpen || restaurant.isOpen;
      final matchesRating = restaurant.rating >= _minRating;
      final matchesPrice = _matchesPriceFilter(restaurant.avgPrice);
      final searchable = [
        restaurant.name,
        restaurant.category,
        restaurant.phone ?? '',
      ].join(' ').toLowerCase();
      final matchesSearch = query.isEmpty || searchable.contains(query);
      return matchesCategory &&
          matchesOpen &&
          matchesRating &&
          matchesPrice &&
          matchesSearch;
    }).toList();
  }

  List<String> get _availableCategories {
    final categories =
        _restaurants
            .map((r) => r.category.trim())
            .where((c) => c.isNotEmpty)
            .toSet()
            .toList()
          ..sort();
    return ['الكل', ...categories];
  }

  bool _matchesPriceFilter(double? avgPrice) {
    if (_priceFilter == 'all' || avgPrice == null) return true;
    if (_priceFilter == 'budget') return avgPrice <= 30;
    if (_priceFilter == 'mid') return avgPrice > 30 && avgPrice <= 60;
    return avgPrice > 60;
  }

  void _onSearchChanged(String value) {
    _searchDebounce?.cancel();
    _searchDebounce = Timer(Duration(milliseconds: 180), () {
      if (!mounted) return;
      setState(() => _searchQuery = value);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        body: SafeArea(
          child: CustomScrollView(
            slivers: [
              // Header
              SliverToBoxAdapter(child: _buildHeader()),
              // Search
              SliverToBoxAdapter(child: _buildSearch()),
              // Featured banner
              SliverToBoxAdapter(child: _buildFeaturedBanner()),
              // Categories
              SliverToBoxAdapter(child: _buildCategories()),
              // Quick filters
              SliverToBoxAdapter(child: _buildQuickFilters()),
              // Section title
              SliverToBoxAdapter(child: _buildSectionHeader()),
              // Content
              if (_isLoading)
                SliverToBoxAdapter(child: LoadingShimmer(itemCount: 5))
              else if (_errorMessage != null)
                SliverToBoxAdapter(
                  child: ErrorState(
                    message: _errorMessage!,
                    onRetry: _loadRestaurants,
                  ),
                )
              else if (_filteredRestaurants.isEmpty)
                SliverToBoxAdapter(
                  child: EmptyState(
                    icon: Icons.restaurant_outlined,
                    title: 'لا توجد مطاعم',
                    subtitle: 'جرب اختيار فئة أخرى',
                  ),
                )
              else
                SliverList(
                  delegate: SliverChildBuilderDelegate((context, index) {
                    final restaurant = _filteredRestaurants[index];
                    return RestaurantCard(
                      restaurant: restaurant,
                      onTap: () => _navigateToRestaurant(restaurant),
                      onRateTap: () => _rateRestaurant(restaurant),
                    );
                  }, childCount: _filteredRestaurants.length),
                ),
              // Bottom padding
              SliverToBoxAdapter(
                child: SizedBox(height: AppSpacing.xxl),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildHeader() {
    final scheme = Theme.of(context).colorScheme;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final addressTitle = _selectedAddress?.title ?? 'إضافة عنوان';
    final addressDetails = _selectedAddress?.fullAddress ?? 'اختر عنوان التوصيل';

    return Padding(
      padding: EdgeInsets.fromLTRB(
        AppSpacing.lg,
        AppSpacing.lg,
        AppSpacing.lg,
        AppSpacing.md,
      ),
      child: Directionality(
        textDirection: TextDirection.ltr,
        child: Row(
        children: [
          GestureDetector(
            onTap: () async {
              await AuthService.fetchCurrentUser();
              if (!mounted) return;
              setState(() {
                _profileImage = AuthService.currentUserImage;
                _imageCacheKey = DateTime.now().millisecondsSinceEpoch;
              });
            },
            child: Container(
              width: 46,
              height: 46,
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.surface,
                shape: BoxShape.circle,
                image: _profileImage != null && _profileImage!.isNotEmpty
                    ? DecorationImage(
                        image: NetworkImage(_getImageUrlWithCacheBuster(_profileImage)!),
                        fit: BoxFit.cover,
                        onError: (_, __) {},
                      )
                    : null,
                boxShadow: [
                  BoxShadow(
                    color: AppColors.shadow,
                    blurRadius: 10,
                    offset: Offset(0, 4),
                  ),
                ],
              ),
              child: _profileImage == null || _profileImage!.isEmpty
                  ? Icon(Icons.person_rounded, color: Theme.of(context).colorScheme.onSurfaceVariant)
                  : null,
            ),
          ),
          SizedBox(width: AppSpacing.md),
          Expanded(
            child: InkWell(
              onTap: _onAddressTap,
              borderRadius: BorderRadius.circular(AppRadius.lg),
              child: Container(
                padding: EdgeInsets.symmetric(
                  horizontal: AppSpacing.md,
                  vertical: AppSpacing.sm,
                ),
                decoration: BoxDecoration(
                  color: Theme.of(context).colorScheme.surface,
                  borderRadius: BorderRadius.circular(AppRadius.lg),
                  boxShadow: [
                    BoxShadow(
                      color: AppColors.shadow,
                      blurRadius: 10,
                      offset: Offset(0, 4),
                    ),
                  ],
                ),
                child: Row(
                  children: [
                    Container(
                      width: 34,
                      height: 34,
                      decoration: BoxDecoration(
                        color: isDark ? scheme.surfaceContainerHighest : AppColors.secondary,
                        borderRadius: BorderRadius.circular(AppRadius.md),
                      ),
                      child: _isAddressLoading
                          ? Padding(
                              padding: EdgeInsets.all(8),
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: isDark ? scheme.primary : AppColors.primary,
                              ),
                            )
                          : Icon(
                              Icons.keyboard_arrow_down_rounded,
                              color: isDark ? scheme.primary : AppColors.primary,
                            ),
                    ),
                    SizedBox(width: AppSpacing.sm),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.end,
                            children: [
                              Text(
                                'التوصيل إلى',
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                              SizedBox(width: 4),
                              Icon(
                                Icons.location_on_outlined,
                                size: 14,
                                color: Theme.of(context).colorScheme.onSurfaceVariant,
                              ),
                            ],
                          ),
                          SizedBox(height: 2),
                          Text(
                            addressTitle,
                            textAlign: TextAlign.right,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.bold,
                              color: Theme.of(context).colorScheme.onSurface,
                            ),
                          ),
                          SizedBox(height: 1),
                          Text(
                            addressDetails,
                            textAlign: TextAlign.right,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              fontSize: 12,
                              color: Theme.of(context).colorScheme.onSurfaceVariant,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
      ),
    );
  }

  Widget _buildSearch() {
    final scheme = Theme.of(context).colorScheme;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Padding(
      padding: EdgeInsets.symmetric(horizontal: AppSpacing.lg),
      child: Row(
        children: [
          Container(
            width: 50,
            height: 50,
            decoration: BoxDecoration(
              color: isDark ? scheme.surfaceContainerHighest : scheme.surface,
              borderRadius: BorderRadius.circular(AppRadius.xl),
              boxShadow: [
                BoxShadow(
                  color: AppColors.shadow,
                  blurRadius: 10,
                  offset: Offset(0, 4),
                ),
              ],
            ),
            child: IconButton(
              onPressed: _showFiltersSheet,
              icon: Icon(Icons.tune_rounded, color: AppColors.primary),
            ),
          ),
          SizedBox(width: AppSpacing.sm),
          Expanded(
            child: Container(
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.surface,
                borderRadius: BorderRadius.circular(AppRadius.xl),
                boxShadow: [
                  BoxShadow(
                    color: AppColors.shadow,
                    blurRadius: 10,
                    offset: Offset(0, 4),
                  ),
                ],
              ),
              child: TextField(
                controller: _searchController,
                onChanged: _onSearchChanged,
                textAlign: TextAlign.right,
                textDirection: TextDirection.rtl,
                decoration: InputDecoration(
                  hintText: 'ابحث عن مطعم أو وجبة...',
                  hintStyle: TextStyle(color: Theme.of(context).colorScheme.onSurfaceVariant),
                  suffixIcon: Icon(
                    Icons.search_rounded,
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
                  border: InputBorder.none,
                  enabledBorder: InputBorder.none,
                  focusedBorder: InputBorder.none,
                  contentPadding: EdgeInsets.symmetric(
                    horizontal: AppSpacing.lg,
                    vertical: AppSpacing.md,
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFeaturedBanner() {
    final scheme = Theme.of(context).colorScheme;
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Padding(
      padding: EdgeInsets.fromLTRB(
        AppSpacing.lg,
        AppSpacing.lg,
        AppSpacing.lg,
        AppSpacing.md,
      ),
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(AppRadius.xl),
          gradient: LinearGradient(
            colors: [Color(0xFF1B1D27), Color(0xFF2D3142)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
        ),
        child: Padding(
          padding: EdgeInsets.all(AppSpacing.lg),
          child: Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'خصم حتى 30%',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 22,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    SizedBox(height: AppSpacing.xs),
                    Text(
                      'اكتشف أفضل المطاعم القريبة منك الآن',
                      style: TextStyle(color: Colors.white70, fontSize: 13),
                    ),
                  ],
                ),
              ),
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: isDark ? scheme.surfaceContainerHighest : Colors.white,
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  Icons.arrow_forward_rounded,
                  color: isDark ? scheme.primary : scheme.onSurface,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildCategories() {
    return SizedBox(
      height: 88,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: EdgeInsets.symmetric(horizontal: AppSpacing.lg),
        itemCount: _availableCategories.length,
        itemBuilder: (context, index) {
          final category = _availableCategories[index];
          final isSelected = (_selectedCategory ?? 'الكل') == category;
          return _buildCategoryChip('🍽️', category, isSelected, () {
            setState(
              () => _selectedCategory = category == 'الكل' ? null : category,
            );
          });
        },
      ),
    );
  }

  Widget _buildCategoryChip(
    String icon,
    String label,
    bool isSelected,
    VoidCallback onTap,
  ) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: Duration(milliseconds: 200),
        margin: EdgeInsetsDirectional.only(
          start: AppSpacing.sm,
          top: AppSpacing.xs,
          bottom: AppSpacing.xs,
        ),
        padding: EdgeInsets.symmetric(
          horizontal: AppSpacing.md,
          vertical: AppSpacing.sm,
        ),
        decoration: BoxDecoration(
          color: isSelected ? AppColors.primary : Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(AppRadius.xl),
          boxShadow: [
            BoxShadow(
              color: AppColors.shadow,
              blurRadius: 8,
              offset: Offset(0, 2),
            ),
          ],
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(icon, style: TextStyle(fontSize: 24)),
            SizedBox(height: 4),
            Text(
              label,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w700,
                color: isSelected ? Colors.white : Theme.of(context).colorScheme.onSurface,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildQuickFilters() {
    if (_quickFilters.isEmpty) return SizedBox.shrink();
    return SizedBox(
      height: 44,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: EdgeInsets.symmetric(horizontal: AppSpacing.lg),
        itemCount: _quickFilters.length,
        itemBuilder: (context, index) {
          final icon = _quickFilters[index];
          return Container(
            margin: EdgeInsetsDirectional.only(start: AppSpacing.sm),
            padding: EdgeInsets.symmetric(
              horizontal: AppSpacing.md,
              vertical: AppSpacing.sm,
            ),
            decoration: BoxDecoration(
              color: AppColors.secondary,
              borderRadius: BorderRadius.circular(AppRadius.pill),
            ),
            child: Text(icon, style: TextStyle(fontSize: 14)),
          );
        },
      ),
    );
  }

  Widget _buildSectionHeader() {
    return Padding(
      padding: EdgeInsets.fromLTRB(
        AppSpacing.lg,
        AppSpacing.md,
        AppSpacing.lg,
        AppSpacing.sm,
      ),
      child: Row(
        children: [
          Text(
            'أفضل المطاعم',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Theme.of(context).colorScheme.onSurface,
            ),
          ),
          Spacer(),
          Text(
            'عرض الكل',
            style: TextStyle(
              fontSize: 13,
              color: AppColors.primary,
              fontWeight: FontWeight.w700,
            ),
          ),
          Text(
            '${_filteredRestaurants.length} مطعم',
            style: TextStyle(
              fontSize: 14,
              color: Theme.of(context).colorScheme.onSurfaceVariant,
            ),
          ),
        ],
      ),
    );
  }

  void _navigateToRestaurant(Restaurant restaurant) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => RestaurantScreen(restaurant: restaurant),
      ),
    );
  }

  Future<void> _rateRestaurant(Restaurant restaurant) async {
    int selected = restaurant.myRating ?? 5;
    final rating = await showModalBottomSheet<int>(
      context: context,
      showDragHandle: true,
      backgroundColor: Colors.white,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setBottomState) {
            return Padding(
              padding: EdgeInsets.all(AppSpacing.lg),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    'قيّم ${restaurant.name}',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w800,
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
                          color: active
                              ? AppColors.warning
                              : Theme.of(context).colorScheme.onSurfaceVariant,
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

    final response = await ApiClient.post(
      '/restaurants/${restaurant.id}/rate',
      {'rating': rating},
    );

    if (!mounted) return;
    if (response['success'] == true && response['data'] != null) {
      final updated = Restaurant.fromJson(response['data']);
      await RealtimeSyncService.syncRestaurant(updated);
      setState(() {
        _restaurants = _restaurants
            .map((r) => r.id == updated.id ? updated : r)
            .toList();
      });
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
          response['message']?.toString() ??
              'تعذر حفظ التقييم، تأكد من تسجيل الدخول',
        ),
        backgroundColor: AppColors.error,
      ),
    );
  }

  Future<void> _showFiltersSheet() async {
    bool onlyOpen = _onlyOpen;
    double minRating = _minRating;
    String priceFilter = _priceFilter;

    final apply = await showModalBottomSheet<bool>(
      context: context,
      showDragHandle: true,
      isScrollControlled: true,
      backgroundColor: Theme.of(context).colorScheme.surface,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setSheetState) {
            Widget priceChip(String key, String label) {
              return ChoiceChip(
                label: Text(label),
                selected: priceFilter == key,
                onSelected: (_) => setSheetState(() => priceFilter = key),
              );
            }

            return Padding(
              padding: EdgeInsets.all(AppSpacing.lg),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    'الفلاتر',
                    style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800),
                  ),
                  SizedBox(height: AppSpacing.md),
                  SwitchListTile(
                    value: onlyOpen,
                    onChanged: (value) => setSheetState(() => onlyOpen = value),
                    title: Text('المطاعم المفتوحة فقط'),
                    activeThumbColor: AppColors.primary,
                    contentPadding: EdgeInsets.zero,
                  ),
                  SizedBox(height: AppSpacing.sm),
                  Text(
                    'الحد الأدنى للتقييم',
                    style: TextStyle(fontWeight: FontWeight.w700),
                  ),
                  SizedBox(height: AppSpacing.xs),
                  Wrap(
                    spacing: AppSpacing.sm,
                    children: [0.0, 3.5, 4.0, 4.5].map((value) {
                      return ChoiceChip(
                        label: Text(value == 0 ? 'الكل' : '$value+'),
                        selected: minRating == value,
                        onSelected: (_) =>
                            setSheetState(() => minRating = value),
                      );
                    }).toList(),
                  ),
                  SizedBox(height: AppSpacing.md),
                  Text(
                    'مستوى السعر',
                    style: TextStyle(fontWeight: FontWeight.w700),
                  ),
                  SizedBox(height: AppSpacing.xs),
                  Wrap(
                    spacing: AppSpacing.sm,
                    children: [
                      priceChip('all', 'الكل'),
                      priceChip('budget', 'اقتصادي'),
                      priceChip('mid', 'متوسط'),
                      priceChip('premium', 'فاخر'),
                    ],
                  ),
                  SizedBox(height: AppSpacing.lg),
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton(
                          onPressed: () => Navigator.pop(context, false),
                          child: Text('إلغاء'),
                        ),
                      ),
                      SizedBox(width: AppSpacing.sm),
                      Expanded(
                        child: ElevatedButton(
                          onPressed: () => Navigator.pop(context, true),
                          child: Text('تطبيق'),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            );
          },
        );
      },
    );

    if (apply == true) {
      setState(() {
        _onlyOpen = onlyOpen;
        _minRating = minRating;
        _priceFilter = priceFilter;
      });
    }
  }
}




