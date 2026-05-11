import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/theme/app_colors.dart';
import '../providers/order_provider.dart';
import 'active_order_screen.dart';
import 'home_screen.dart';
import 'profile_screen.dart';

class MainNavScreen extends StatefulWidget {
  const MainNavScreen({super.key});

  @override
  State<MainNavScreen> createState() => _MainNavScreenState();
}

class _MainNavScreenState extends State<MainNavScreen> {
  int index = 0;

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<OrderProvider>();
    final hasActive = provider.hasActiveOrder;
    final poolCount = provider.availableOrders.length;

    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      if (hasActive && index == 0) {
        setState(() => index = 1);
      }
    });

    final pages = [
      HomeScreen(onGoToActiveTab: () => setState(() => index = 1)),
      const ActiveOrderScreen(),
      const ProfileScreen(),
    ];

    final homeBadge = poolCount > 0 && !hasActive;

    return Scaffold(
      body: pages[index],
      bottomNavigationBar: NavigationBarTheme(
        data: NavigationBarThemeData(
          height: 68,
          indicatorColor: Theme.of(context).colorScheme.primary.withValues(alpha: 0.12),
          labelTextStyle: WidgetStateProperty.resolveWith((states) {
            final selected = states.contains(WidgetState.selected);
            return TextStyle(
              fontSize: 12,
              fontWeight: selected ? FontWeight.w800 : FontWeight.w600,
              color: selected
                  ? Theme.of(context).colorScheme.secondary
                  : Theme.of(context).colorScheme.onSurfaceVariant,
            );
          }),
        ),
        child: DecoratedBox(
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.surface,
            border: Border(top: BorderSide(color: Theme.of(context).dividerColor)),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.04),
                blurRadius: 18,
                offset: const Offset(0, -8),
              ),
            ],
          ),
          child: NavigationBar(
            backgroundColor: Theme.of(context).colorScheme.surface,
            surfaceTintColor: Colors.transparent,
            elevation: 0,
            shadowColor: Colors.transparent,
            labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
            selectedIndex: index,
            onDestinationSelected: (v) {
              if (hasActive && v == 0) {
                return;
              }
              setState(() => index = v);
            },
            destinations: [
              NavigationDestination(
                icon: _NavPoolIcon(showCount: homeBadge, count: poolCount, dimmed: hasActive, selected: false),
                selectedIcon:
                    _NavPoolIcon(showCount: homeBadge, count: poolCount, dimmed: hasActive, selected: true),
                label: hasActive ? 'مؤجّلة' : 'المتاحة',
              ),
              NavigationDestination(
                icon: _NavActiveIcon(showDot: hasActive, selected: false),
                selectedIcon: _NavActiveIcon(showDot: hasActive, selected: true),
                label: 'نشط',
              ),
              NavigationDestination(
                icon: Icon(Icons.person_outline_rounded),
                selectedIcon: Icon(Icons.person_rounded, color: Theme.of(context).colorScheme.secondary),
                label: 'حسابي',
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _NavPoolIcon extends StatelessWidget {
  final bool showCount;
  final int count;
  final bool dimmed;
  final bool selected;

  const _NavPoolIcon({
    required this.showCount,
    required this.count,
    required this.dimmed,
    required this.selected,
  });

  @override
  Widget build(BuildContext context) {
    final icon = Icon(
      Icons.grid_view_rounded,
      color: dimmed
          ? Theme.of(context).colorScheme.onSurfaceVariant
          : (selected ? Theme.of(context).colorScheme.secondary : null),
    );

    if (!showCount || count <= 0) return icon;

    return Badge.count(
      count: count > 99 ? 99 : count,
      backgroundColor: Theme.of(context).colorScheme.primary,
      textColor: Colors.white,
      child: icon,
    );
  }
}

class _NavActiveIcon extends StatelessWidget {
  final bool showDot;
  final bool selected;

  const _NavActiveIcon({required this.showDot, required this.selected});

  @override
  Widget build(BuildContext context) {
    final icon = Icon(
      Icons.local_shipping_rounded,
      color: selected ? Theme.of(context).colorScheme.secondary : null,
    );

    if (!showDot) return icon;

    return Stack(
      clipBehavior: Clip.none,
      alignment: Alignment.center,
      children: [
        icon,
        PositionedDirectional(
          top: -2,
          end: -6,
          child: Container(
            width: 10,
            height: 10,
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.primary,
              shape: BoxShape.circle,
              border: Border.all(color: Theme.of(context).colorScheme.surface, width: 2),
            ),
          ),
        ),
      ],
    );
  }
}
