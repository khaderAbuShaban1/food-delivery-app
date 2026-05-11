import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/theme/app_theme.dart';
import '../../core/services/cart_provider.dart';
import '../home/home_screen.dart';
import '../cart/cart_screen.dart';
import '../orders/orders_screen.dart';
import '../profile/profile_screen.dart';

class MainScreen extends StatefulWidget {
  final int initialIndex;
  final int? highlightedOrderId;

  MainScreen({super.key, this.initialIndex = 0, this.highlightedOrderId});

  @override
  State<MainScreen> createState() => _MainScreenState();
}

class _MainScreenState extends State<MainScreen> {
  late int _currentIndex;

  late final List<Widget> _screens;
  static double _navBarHeight = 74;

  @override
  void initState() {
    super.initState();
    _currentIndex = widget.initialIndex;
    _screens = [
      HomeScreen(),
      CartScreen(),
      OrdersScreen(highlightedOrderId: widget.highlightedOrderId),
      _ProfileScreenWrapper(),
    ];
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: IndexedStack(index: _currentIndex, children: _screens),
      bottomNavigationBar: Consumer<CartProvider>(
        builder: (context, cart, child) {
          return SafeArea(
            top: false,
            child: Container(
              height: _navBarHeight,
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.surface,
                borderRadius: BorderRadius.vertical(
                  top: Radius.circular(28),
                ),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.08),
                    blurRadius: 18,
                    offset: Offset(0, -6),
                  ),
                ],
              ),
              child: Padding(
                padding: EdgeInsets.symmetric(
                  horizontal: AppSpacing.lg,
                  vertical: 10,
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    _buildNavItem(
                      0,
                      icon: Icons.home_outlined,
                      activeIcon: Icons.home_rounded,
                      label: 'الرئيسية',
                    ),
                    _buildNavItem(
                      1,
                      icon: Icons.shopping_cart_outlined,
                      activeIcon: Icons.shopping_cart_rounded,
                      label: 'السلة',
                      showBadge: true,
                      badgeCountOverride: cart.itemCount,
                    ),
                    _buildNavItem(
                      2,
                      icon: Icons.receipt_long_outlined,
                      activeIcon: Icons.receipt_long_rounded,
                      label: 'الطلبات',
                    ),
                    _buildNavItem(
                      3,
                      icon: Icons.person_outline_rounded,
                      activeIcon: Icons.person_rounded,
                      label: 'حسابي',
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildNavItem(
    int index, {
    required IconData icon,
    required IconData activeIcon,
    required String label,
    bool showBadge = false,
    int? badgeCountOverride,
  }) {
    final isSelected = _currentIndex == index;
    final badgeCount =
        showBadge ? (badgeCountOverride ?? context.watch<CartProvider>().itemCount) : 0;

    return SizedBox(
      width: 72,
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () => setState(() => _currentIndex = index),
          borderRadius: BorderRadius.circular(16),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Stack(
                clipBehavior: Clip.none,
                children: [
                  Icon(
                    isSelected ? activeIcon : icon,
                    color: isSelected ? AppColors.primary : Theme.of(context).colorScheme.onSurfaceVariant,
                    size: 26,
                  ),
                  if (badgeCount > 0)
                    PositionedDirectional(
                      end: -10,
                      top: -8,
                      child: Container(
                        padding: EdgeInsets.symmetric(
                          horizontal: 6,
                          vertical: 2,
                        ),
                        decoration: BoxDecoration(
                          color: AppColors.primary,
                          borderRadius: BorderRadius.circular(AppRadius.pill),
                        ),
                        constraints: BoxConstraints(minWidth: 18),
                        child: Text(
                          badgeCount > 99 ? '99+' : badgeCount.toString(),
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 10,
                            fontWeight: FontWeight.w900,
                            height: 1.0,
                          ),
                          textAlign: TextAlign.center,
                        ),
                      ),
                    ),
                ],
              ),
              SizedBox(height: 4),
              AnimatedContainer(
                duration: Duration(milliseconds: 180),
                height: 3,
                width: isSelected ? 18 : 0,
                decoration: BoxDecoration(
                  color: AppColors.primary,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              SizedBox(height: 4),
              Text(
                label,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
                  color: isSelected ? AppColors.primary : Theme.of(context).colorScheme.onSurfaceVariant,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ProfileScreenWrapper extends StatefulWidget {
  @override
  State<_ProfileScreenWrapper> createState() => _ProfileScreenWrapperState();
}

class _ProfileScreenWrapperState extends State<_ProfileScreenWrapper> {
  Key _profileKey = UniqueKey();

  @override
  Widget build(BuildContext context) {
    return ProfileScreen(
      key: _profileKey,
      onImageUpdated: () {
        setState(() {
          _profileKey = UniqueKey();
        });
      },
    );
  }
}
