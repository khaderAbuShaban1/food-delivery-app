import 'dart:async';

import 'package:flutter/material.dart';

import '../../core/api/api_client.dart';
import '../../core/models/restaurant.dart';
import '../../core/theme/app_theme.dart';
import '../../core/services/auth_service.dart';
import '../../core/widgets/widgets.dart';
import '../restaurant/restaurant_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

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
  final _searchController = TextEditingController();
  String? _profileImage;
  int _imageCacheKey = 0;

  final List<Map<String, String>> _quickFilters = const [
    {'icon': '⚡', 'label': 'سريع'},
    {'icon': '🔥', 'label': 'الأكثر طلباً'},
    {'icon': '💚', 'label': 'صحي'},
  ];

  @override
  void initState() {
    super.initState();
    _loadRestaurants();
    _loadUserProfile();
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

  @override
  void dispose() {
    _searchDebounce?.cancel();
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

  Future<void> _loadRestaurants() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final response = await ApiClient.get('/restaurants');

      if (response['success'] == true && response['data'] != null) {
        final List data = response['data'];
        setState(() {
          _restaurants = data.map((json) => Restaurant.fromJson(json)).toList();
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
    _searchDebounce = Timer(const Duration(milliseconds: 180), () {
      if (!mounted) return;
      setState(() => _searchQuery = value);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: AppColors.background,
        body: SafeArea(
          child: RefreshIndicator(
            onRefresh: _loadRestaurants,
            color: AppColors.primary,
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
                  const SliverToBoxAdapter(child: LoadingShimmer(itemCount: 5))
                else if (_errorMessage != null)
                  SliverToBoxAdapter(
                    child: ErrorState(
                      message: _errorMessage!,
                      onRetry: _loadRestaurants,
                    ),
                  )
                else if (_filteredRestaurants.isEmpty)
                  const SliverToBoxAdapter(
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
                const SliverToBoxAdapter(
                  child: SizedBox(height: AppSpacing.xxl),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildHeader() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(
        AppSpacing.lg,
        AppSpacing.lg,
        AppSpacing.lg,
        AppSpacing.md,
      ),
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
                color: AppColors.surface,
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
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: _profileImage == null || _profileImage!.isEmpty
                  ? const Icon(Icons.person_rounded, color: AppColors.textHint)
                  : null,
            ),
          ),
          const Spacer(),
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: AppColors.surface,
              borderRadius: BorderRadius.circular(AppRadius.md),
              boxShadow: [
                BoxShadow(
                  color: AppColors.shadow,
                  blurRadius: 10,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: const Icon(Icons.tune_rounded, color: AppColors.textPrimary),
          ),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: const [
                Text(
                  'التوصيل إلى',
                  style: TextStyle(
                    fontSize: 12,
                    color: AppColors.textSecondary,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                SizedBox(height: 2),
                Text(
                  'HH#21, ST#22, ISB',
                  textAlign: TextAlign.right,
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.bold,
                    color: AppColors.textPrimary,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSearch() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
      child: Row(
        children: [
          Container(
            width: 50,
            height: 50,
            decoration: BoxDecoration(
              color: AppColors.surface,
              borderRadius: BorderRadius.circular(AppRadius.xl),
              boxShadow: [
                BoxShadow(
                  color: AppColors.shadow,
                  blurRadius: 10,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: IconButton(
              onPressed: _showFiltersSheet,
              icon: const Icon(Icons.tune_rounded, color: AppColors.primary),
            ),
          ),
          const SizedBox(width: AppSpacing.sm),
          Expanded(
            child: Container(
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(AppRadius.xl),
                boxShadow: [
                  BoxShadow(
                    color: AppColors.shadow,
                    blurRadius: 10,
                    offset: const Offset(0, 4),
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
                  hintStyle: const TextStyle(color: AppColors.textHint),
                  suffixIcon: const Icon(
                    Icons.search_rounded,
                    color: AppColors.textHint,
                  ),
                  border: InputBorder.none,
                  enabledBorder: InputBorder.none,
                  focusedBorder: InputBorder.none,
                  contentPadding: const EdgeInsets.symmetric(
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
    return Padding(
      padding: const EdgeInsets.fromLTRB(
        AppSpacing.lg,
        AppSpacing.lg,
        AppSpacing.lg,
        AppSpacing.md,
      ),
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(AppRadius.xl),
          gradient: const LinearGradient(
            colors: [Color(0xFF1B1D27), Color(0xFF2D3142)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
        ),
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.lg),
          child: Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: const [
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
                decoration: const BoxDecoration(
                  color: Colors.white,
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.arrow_forward_rounded,
                  color: AppColors.textPrimary,
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
        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
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
        duration: const Duration(milliseconds: 200),
        margin: const EdgeInsetsDirectional.only(
          start: AppSpacing.sm,
          top: AppSpacing.xs,
          bottom: AppSpacing.xs,
        ),
        padding: const EdgeInsets.symmetric(
          horizontal: AppSpacing.md,
          vertical: AppSpacing.sm,
        ),
        decoration: BoxDecoration(
          color: isSelected ? AppColors.primary : AppColors.surface,
          borderRadius: BorderRadius.circular(AppRadius.xl),
          boxShadow: [
            BoxShadow(
              color: AppColors.shadow,
              blurRadius: 8,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(icon, style: const TextStyle(fontSize: 24)),
            const SizedBox(height: 4),
            Text(
              label,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w700,
                color: isSelected ? Colors.white : AppColors.textPrimary,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildQuickFilters() {
    return SizedBox(
      height: 44,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
        itemCount: _quickFilters.length,
        itemBuilder: (context, index) {
          final filter = _quickFilters[index];
          return Container(
            margin: const EdgeInsetsDirectional.only(start: AppSpacing.sm),
            padding: const EdgeInsets.symmetric(
              horizontal: AppSpacing.md,
              vertical: AppSpacing.sm,
            ),
            decoration: BoxDecoration(
              color: AppColors.secondary,
              borderRadius: BorderRadius.circular(AppRadius.pill),
            ),
            child: Row(
              children: [
                Text(filter['icon']!, style: const TextStyle(fontSize: 14)),
                const SizedBox(width: AppSpacing.xs),
                Text(
                  filter['label']!,
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: AppColors.textSecondary,
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildSectionHeader() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(
        AppSpacing.lg,
        AppSpacing.md,
        AppSpacing.lg,
        AppSpacing.sm,
      ),
      child: Row(
        children: [
          const Text(
            'أفضل المطاعم',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: AppColors.textPrimary,
            ),
          ),
          const Spacer(),
          const Text(
            'عرض الكل',
            style: TextStyle(
              fontSize: 13,
              color: AppColors.primary,
              fontWeight: FontWeight.w700,
            ),
          ),
          Text(
            '${_filteredRestaurants.length} مطعم',
            style: const TextStyle(
              fontSize: 14,
              color: AppColors.textSecondary,
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
              padding: const EdgeInsets.all(AppSpacing.lg),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    'قيّم ${restaurant.name}',
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(height: AppSpacing.md),
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
                              : AppColors.textHint,
                          size: 34,
                        ),
                      );
                    }),
                  ),
                  const SizedBox(height: AppSpacing.md),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: () => Navigator.pop(context, selected),
                      child: const Text('حفظ التقييم'),
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
      setState(() {
        _restaurants = _restaurants
            .map((r) => r.id == updated.id ? updated : r)
            .toList();
      });
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
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
      backgroundColor: Colors.white,
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
              padding: const EdgeInsets.all(AppSpacing.lg),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  const Text(
                    'الفلاتر',
                    style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800),
                  ),
                  const SizedBox(height: AppSpacing.md),
                  SwitchListTile(
                    value: onlyOpen,
                    onChanged: (value) => setSheetState(() => onlyOpen = value),
                    title: const Text('المطاعم المفتوحة فقط'),
                    activeThumbColor: AppColors.primary,
                    contentPadding: EdgeInsets.zero,
                  ),
                  const SizedBox(height: AppSpacing.sm),
                  const Text(
                    'الحد الأدنى للتقييم',
                    style: TextStyle(fontWeight: FontWeight.w700),
                  ),
                  const SizedBox(height: AppSpacing.xs),
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
                  const SizedBox(height: AppSpacing.md),
                  const Text(
                    'مستوى السعر',
                    style: TextStyle(fontWeight: FontWeight.w700),
                  ),
                  const SizedBox(height: AppSpacing.xs),
                  Wrap(
                    spacing: AppSpacing.sm,
                    children: [
                      priceChip('all', 'الكل'),
                      priceChip('budget', 'اقتصادي'),
                      priceChip('mid', 'متوسط'),
                      priceChip('premium', 'فاخر'),
                    ],
                  ),
                  const SizedBox(height: AppSpacing.lg),
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton(
                          onPressed: () => Navigator.pop(context, false),
                          child: const Text('إلغاء'),
                        ),
                      ),
                      const SizedBox(width: AppSpacing.sm),
                      Expanded(
                        child: ElevatedButton(
                          onPressed: () => Navigator.pop(context, true),
                          child: const Text('تطبيق'),
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
