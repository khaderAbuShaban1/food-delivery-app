import 'package:flutter/material.dart';
import '../models/menu_item.dart';

class CartItem {
  final MenuItem menuItem;
  int quantity;

  CartItem({required this.menuItem, this.quantity = 1});

  double get totalPrice => menuItem.price * quantity;
}

class CartProvider extends ChangeNotifier {
  List<CartItem> _items = [];
  int? _deliveryAddressId;

  /// Saved-address row to send as `address_id` when placing an order (MySQL).
  int? get deliveryAddressId => _deliveryAddressId;

  List<CartItem> get items => _items;
  
  int get itemCount => _items.fold(0, (sum, item) => sum + item.quantity);
  
  double get totalPrice => _items.fold(0, (sum, item) => sum + item.totalPrice);

  void addItem(MenuItem menuItem) {
    final existingIndex = _items.indexWhere((item) => item.menuItem.id == menuItem.id);
    
    if (existingIndex >= 0) {
      _items[existingIndex].quantity++;
    } else {
      _items.add(CartItem(menuItem: menuItem));
    }
    
    notifyListeners();
  }

  void removeItem(int menuItemId) {
    _items.removeWhere((item) => item.menuItem.id == menuItemId);
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

  int getQuantity(int menuItemId) {
    final item = _items.where((item) => item.menuItem.id == menuItemId).firstOrNull;
    return item?.quantity ?? 0;
  }
}