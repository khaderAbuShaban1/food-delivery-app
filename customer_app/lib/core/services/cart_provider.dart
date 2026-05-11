import 'package:flutter/material.dart';
import '../models/menu_item.dart';

class CartItem {
  final MenuItem menuItem;
  int quantity;
  /// Selected option value ids grouped by option group id.
  /// Example: { 1: [10], 2: [21, 22] }
  Map<int, List<int>> selectedOptionValueIdsByGroup;

  CartItem({
    required this.menuItem,
    this.quantity = 1,
    Map<int, List<int>>? selectedOptionValueIdsByGroup,
  }) : selectedOptionValueIdsByGroup = selectedOptionValueIdsByGroup ?? <int, List<int>>{};

  double get selectedExtrasPerUnit {
    double total = 0;
    for (final group in menuItem.optionGroups) {
      final selectedIds = selectedOptionValueIdsByGroup[group.id] ?? <int>[];
      for (final value in group.values) {
        if (selectedIds.contains(value.id)) {
          total += value.extraPrice;
        }
      }
    }
    return total;
  }

  double get unitPriceWithOptions => menuItem.price + selectedExtrasPerUnit;
  double get totalPrice => unitPriceWithOptions * quantity;
}

class CartProvider extends ChangeNotifier {
  List<CartItem> _items = [];
  int? _deliveryAddressId;

  /// Saved-address row to send as `address_id` when placing an order (MySQL).
  int? get deliveryAddressId => _deliveryAddressId;

  List<CartItem> get items => _items;
  
  int get itemCount => _items.fold(0, (sum, item) => sum + item.quantity);
  
  double get totalPrice => _items.fold(0, (sum, item) => sum + item.totalPrice);

  Map<int, List<int>> normalizeSelections(Map<int, List<int>> selections) {
    final normalized = <int, List<int>>{};
    selections.forEach((gid, ids) {
      final uniq = ids.toSet().toList()..sort();
      if (uniq.isNotEmpty) {
        normalized[gid] = uniq;
      }
    });
    return normalized;
  }

  bool _sameSelections(Map<int, List<int>> a, Map<int, List<int>> b) {
    final na = normalizeSelections(a);
    final nb = normalizeSelections(b);
    if (na.length != nb.length) return false;
    for (final gid in na.keys) {
      final av = na[gid] ?? <int>[];
      final bv = nb[gid];
      if (bv == null || av.length != bv.length) return false;
      for (var i = 0; i < av.length; i++) {
        if (av[i] != bv[i]) return false;
      }
    }
    return true;
  }

  int _findVariantIndex(int menuItemId, Map<int, List<int>> selections) {
    final normalized = normalizeSelections(selections);
    return _items.indexWhere(
      (item) =>
          item.menuItem.id == menuItemId &&
          _sameSelections(item.selectedOptionValueIdsByGroup, normalized),
    );
  }

  void addItem(MenuItem menuItem) {
    addOrReplaceItemWithOptions(
      menuItem,
      quantity: 1,
      selections: <int, List<int>>{},
    );
  }

  /// Add/merge by variant (meal + exact same selected options).
  void addOrReplaceItemWithOptions(MenuItem menuItem, {required int quantity, required Map<int, List<int>> selections}) {
    final normalized = normalizeSelections(selections);
    final existingIndex = _findVariantIndex(menuItem.id, normalized);

    if (existingIndex >= 0) {
      _items[existingIndex].quantity += quantity;
      _items[existingIndex].selectedOptionValueIdsByGroup = normalized;
    } else {
      _items.add(
        CartItem(
          menuItem: menuItem,
          quantity: quantity,
          selectedOptionValueIdsByGroup: normalized,
        ),
      );
    }
    notifyListeners();
  }

  void removeCartItem(CartItem cartItem) {
    _items.remove(cartItem);
    notifyListeners();
  }

  void removeItem(int menuItemId) {
    _items.removeWhere((item) => item.menuItem.id == menuItemId);
    notifyListeners();
  }

  void updateCartItemQuantity(CartItem cartItem, int quantity) {
    final index = _items.indexOf(cartItem);
    if (index < 0) return;
    if (quantity <= 0) {
      _items.removeAt(index);
    } else {
      _items[index].quantity = quantity;
    }
    notifyListeners();
  }

  void updateQuantity(MenuItem menuItem, int quantity) {
    final index = _items.indexWhere((item) => item.menuItem.id == menuItem.id);
    
    if (index >= 0) {
      if (quantity <= 0) {
        _items.removeAt(index);
      } else {
        _items[index].quantity = quantity;
      }
    }
    
    notifyListeners();
  }

  void setDeliveryAddressId(int? id) {
    if (_deliveryAddressId == id) return;
    _deliveryAddressId = id;
    notifyListeners();
  }

  void clearCart() {
    _items.clear();
    _deliveryAddressId = null;
    notifyListeners();
  }

  bool isInCart(int menuItemId) {
    return _items.any((item) => item.menuItem.id == menuItemId);
  }

  bool isVariantInCart(int menuItemId, Map<int, List<int>> selections) {
    return _findVariantIndex(menuItemId, selections) >= 0;
  }

  int getQuantityForVariant(int menuItemId, Map<int, List<int>> selections) {
    final index = _findVariantIndex(menuItemId, selections);
    if (index < 0) return 0;
    return _items[index].quantity;
  }

  int getQuantity(int menuItemId) {
    final item = _items.where((item) => item.menuItem.id == menuItemId).firstOrNull;
    return item?.quantity ?? 0;
  }
}


