import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/models/menu_item.dart';
import '../../core/models/menu_item_option.dart';
import '../../core/services/cart_provider.dart';
import '../../core/theme/app_theme.dart';

class MealDetailsScreen extends StatefulWidget {
  final MenuItem menuItem;

  MealDetailsScreen({super.key, required this.menuItem});

  @override
  State<MealDetailsScreen> createState() => _MealDetailsScreenState();
}

class _MealDetailsScreenState extends State<MealDetailsScreen> {
  int _quantity = 1;
  bool _initializedFromCart = false;
  final Map<int, Set<int>> _selectedValueIdsByGroup = <int, Set<int>>{};

  List<MenuItemOptionGroup> get _groupsWithValues =>
      widget.menuItem.optionGroups.where((g) => g.values.isNotEmpty).toList(growable: false);

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_initializedFromCart) return;
    _initializedFromCart = true;

    final cart = context.read<CartProvider>();
    final current = cart.getQuantityForVariant(widget.menuItem.id, _normalizedSelections());
    if (current > 0) _quantity = current;
  }

  double _selectedExtrasPerUnit() {
    double total = 0;
    for (final group in _groupsWithValues) {
      final selected = _selectedValueIdsByGroup[group.id] ?? <int>{};
      for (final value in group.values) {
        if (selected.contains(value.id)) total += value.extraPrice;
      }
    }
    return total;
  }

  double get _unitPriceWithOptions => widget.menuItem.price + _selectedExtrasPerUnit();
  double get _totalPrice => _unitPriceWithOptions * _quantity;

  String _priceTag(double value) => '₪${value.toStringAsFixed(2)}';

  void _selectSingle(int groupId, int valueId) {
    setState(() {
      _selectedValueIdsByGroup[groupId] = <int>{valueId};
    });
  }

  void _toggleMulti(int groupId, int valueId, bool selected) {
    setState(() {
      final set = _selectedValueIdsByGroup[groupId] ?? <int>{};
      if (selected) {
        set.add(valueId);
      } else {
        set.remove(valueId);
      }
      _selectedValueIdsByGroup[groupId] = set;
    });
  }

  Map<int, List<int>> _normalizedSelections() {
    final out = <int, List<int>>{};
    _selectedValueIdsByGroup.forEach((groupId, ids) {
      if (ids.isEmpty) return;
      out[groupId] = ids.toList()..sort();
    });
    return out;
  }

  void _inc() => setState(() => _quantity++);

  void _dec() {
    if (_quantity <= 1) return;
    setState(() => _quantity--);
  }

  void _addToCart() {
    final cart = context.read<CartProvider>();
    cart.addOrReplaceItemWithOptions(
      widget.menuItem,
      quantity: _quantity,
      selections: _normalizedSelections(),
    );

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('تمت إضافة ${widget.menuItem.name} للسلة'),
        backgroundColor: AppColors.primary,
        duration: Duration(seconds: 1),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final item = widget.menuItem;
    final scheme = Theme.of(context).colorScheme;
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        appBar: AppBar(
          title: Text('تفاصيل الوجبة'),
          backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        ),
        body: SafeArea(
          child: Column(
            children: [
              Expanded(
                child: SingleChildScrollView(
                  padding: EdgeInsets.all(AppSpacing.lg),
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
                      SizedBox(height: AppSpacing.lg),
                      Text(
                        item.name,
                        style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.w900,
                          color: Theme.of(context).colorScheme.onSurface,
                        ),
                        textAlign: TextAlign.right,
                      ),
                      if (item.description != null &&
                          item.description!.trim().isNotEmpty) ...[
                        SizedBox(height: AppSpacing.sm),
                        Text(
                          item.description!,
                          style: TextStyle(
                            fontSize: 14,
                            height: 1.5,
                            color: Theme.of(context).colorScheme.onSurfaceVariant,
                          ),
                          textAlign: TextAlign.right,
                        ),
                      ],
                      SizedBox(height: AppSpacing.lg),
                      if (_groupsWithValues.isNotEmpty) ...[
                        _OptionsSection(
                          groups: _groupsWithValues,
                          selectedValueIdsByGroup: _selectedValueIdsByGroup,
                          onSelectSingle: _selectSingle,
                          onToggleMulti: _toggleMulti,
                          priceTag: _priceTag,
                        ),
                        SizedBox(height: AppSpacing.lg),
                      ],
                      Container(
                        padding: EdgeInsets.all(AppSpacing.md),
                        decoration: BoxDecoration(
                          color: Theme.of(context).colorScheme.surface,
                          borderRadius: BorderRadius.circular(AppRadius.xl),
                          border: Border.all(color: Theme.of(context).dividerColor),
                        ),
                        child: Row(
                          children: [
                            _qtyButton(
                              icon: Icons.remove_rounded,
                              onTap: _dec,
                              enabled: _quantity > 1,
                            ),
                            SizedBox(width: AppSpacing.sm),
                            Container(
                              padding: EdgeInsets.symmetric(
                                horizontal: AppSpacing.lg,
                                vertical: AppSpacing.sm,
                              ),
                              decoration: BoxDecoration(
                                color: isDark
                                    ? scheme.surfaceContainerHighest
                                    : AppColors.secondary,
                                borderRadius:
                                    BorderRadius.circular(AppRadius.pill),
                              ),
                              child: Text(
                                _quantity.toString(),
                                style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w900,
                                  color: Theme.of(context).colorScheme.onSurface,
                                ),
                              ),
                            ),
                            SizedBox(width: AppSpacing.sm),
                            _qtyButton(
                              icon: Icons.add_rounded,
                              onTap: _inc,
                              enabled: true,
                            ),
                            Spacer(),
                            Directionality(
                              textDirection: TextDirection.ltr,
                              child: Text(
                                _priceTag(_totalPrice),
                                style: TextStyle(
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
                padding: EdgeInsets.fromLTRB(
                  AppSpacing.lg,
                  AppSpacing.sm,
                  AppSpacing.lg,
                  AppSpacing.lg,
                ),
                decoration: BoxDecoration(
                  color: Theme.of(context).colorScheme.surface,
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.06),
                      blurRadius: 18,
                      offset: Offset(0, -10),
                    ),
                  ],
                ),
                child: SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _addToCart,
                    child: Text('إضافة إلى السلة • ${_priceTag(_totalPrice)}'),
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
      color: enabled ? AppColors.primary : Theme.of(context).dividerColor,
      borderRadius: BorderRadius.circular(AppRadius.pill),
      child: InkWell(
        onTap: enabled ? onTap : null,
        borderRadius: BorderRadius.circular(AppRadius.pill),
        child: Padding(
          padding: EdgeInsets.all(10),
          child: Icon(
            icon,
            size: 18,
            color: enabled ? Colors.white : Theme.of(context).colorScheme.onSurfaceVariant,
          ),
        ),
      ),
    );
  }

  Widget _imagePlaceholder() {
    return Container(
      color: AppColors.secondary,
      child: Center(
        child: Icon(
          Icons.fastfood_outlined,
          size: 42,
          color: Theme.of(context).colorScheme.onSurfaceVariant,
        ),
      ),
    );
  }
}

class _OptionsSection extends StatelessWidget {
  final List<MenuItemOptionGroup> groups;
  final Map<int, Set<int>> selectedValueIdsByGroup;
  final void Function(int groupId, int valueId) onSelectSingle;
  final void Function(int groupId, int valueId, bool selected) onToggleMulti;
  final String Function(double) priceTag;

  _OptionsSection({
    required this.groups,
    required this.selectedValueIdsByGroup,
    required this.onSelectSingle,
    required this.onToggleMulti,
    required this.priceTag,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(AppRadius.xl),
        border: Border.all(color: Theme.of(context).dividerColor),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            'التخصيصات',
            textAlign: TextAlign.right,
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w900,
              color: Theme.of(context).colorScheme.onSurface,
            ),
          ),
          SizedBox(height: AppSpacing.md),
          ...groups.map((group) {
            final selected = selectedValueIdsByGroup[group.id] ?? <int>{};
            return Padding(
              padding: EdgeInsets.only(bottom: AppSpacing.md),
              child: _OptionGroupCard(
                group: group,
                selected: selected,
                onSelectSingle: onSelectSingle,
                onToggleMulti: onToggleMulti,
                priceTag: priceTag,
              ),
            );
          }),
        ],
      ),
    );
  }
}

class _OptionGroupCard extends StatelessWidget {
  final MenuItemOptionGroup group;
  final Set<int> selected;
  final void Function(int groupId, int valueId) onSelectSingle;
  final void Function(int groupId, int valueId, bool selected) onToggleMulti;
  final String Function(double) priceTag;

  _OptionGroupCard({
    required this.group,
    required this.selected,
    required this.onSelectSingle,
    required this.onToggleMulti,
    required this.priceTag,
  });

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Container(
      decoration: BoxDecoration(
        color: Theme.of(context).scaffoldBackgroundColor,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        border: Border.all(color: Theme.of(context).dividerColor),
      ),
      child: Padding(
        padding: EdgeInsets.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    group.name,
                    textAlign: TextAlign.right,
                    style: TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w800,
                      color: Theme.of(context).colorScheme.onSurface,
                    ),
                  ),
                ),
                Container(
                  padding: EdgeInsets.symmetric(
                    horizontal: AppSpacing.sm,
                    vertical: AppSpacing.xs,
                  ),
                  decoration: BoxDecoration(
                    color: isDark ? scheme.surfaceContainerHighest : AppColors.secondary,
                    borderRadius: BorderRadius.circular(AppRadius.pill),
                  ),
                  child: Text(
                    group.isMulti ? 'متعدد' : 'اختيار واحد',
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                    ),
                  ),
                ),
              ],
            ),
            SizedBox(height: AppSpacing.sm),
            ...group.values.map((value) {
              final isSelected = selected.contains(value.id);
              final priceSuffix = value.extraPrice > 0 ? ' +${priceTag(value.extraPrice)}' : '';
              return Padding(
                padding: EdgeInsets.only(bottom: AppSpacing.xs),
                child: Container(
                  decoration: BoxDecoration(
                    color: isSelected
                        ? (isDark ? scheme.surfaceContainerHighest : AppColors.secondary)
                        : (isDark ? scheme.surface : Colors.white),
                    borderRadius: BorderRadius.circular(AppRadius.md),
                    border: Border.all(
                      color: isSelected ? AppColors.primary : Theme.of(context).dividerColor,
                    ),
                  ),
                  child: group.isMulti
                      ? CheckboxListTile(
                          dense: true,
                          value: isSelected,
                          controlAffinity: ListTileControlAffinity.leading,
                          onChanged: (v) => onToggleMulti(group.id, value.id, v ?? false),
                          title: Text(
                            '${value.name}$priceSuffix',
                            textAlign: TextAlign.right,
                            style: TextStyle(
                              fontWeight: FontWeight.w600,
                              color: Theme.of(context).colorScheme.onSurface,
                            ),
                          ),
                        )
                      : RadioListTile<int>(
                          dense: true,
                          value: value.id,
                          groupValue: selected.isEmpty ? null : selected.first,
                          controlAffinity: ListTileControlAffinity.leading,
                          onChanged: (v) {
                            if (v == null) return;
                            onSelectSingle(group.id, v);
                          },
                          title: Text(
                            '${value.name}$priceSuffix',
                            textAlign: TextAlign.right,
                            style: TextStyle(
                              fontWeight: FontWeight.w600,
                              color: Theme.of(context).colorScheme.onSurface,
                            ),
                          ),
                        ),
                ),
              );
            }),
          ],
        ),
      ),
    );
  }
}




