import 'menu_item_option.dart';

class MenuItem {
  final int id;
  final int restaurantId;
  final String name;
  final double price;
  final String? description;
  final String? image;
  final String? category;
  final List<MenuItemOptionGroup> optionGroups;

  MenuItem({
    required this.id,
    required this.restaurantId,
    required this.name,
    required this.price,
    this.description,
    this.image,
    this.category,
    this.optionGroups = const [],
  });

  static int _asInt(dynamic v) {
    if (v == null) return 0;
    if (v is int) return v;
    if (v is num) return v.toInt();
    return int.tryParse(v.toString().trim()) ?? 0;
  }

  factory MenuItem.fromJson(Map<String, dynamic> json) {
    final groups = <MenuItemOptionGroup>[];
    final rawGroups = json['option_groups'];
    if (rawGroups is List) {
      for (final e in rawGroups) {
        if (e is Map) {
          groups.add(MenuItemOptionGroup.fromJson(Map<String, dynamic>.from(e as Map)));
        }
      }
    }
    groups.sort((a, b) => a.sortOrder.compareTo(b.sortOrder));

    return MenuItem(
      id: _asInt(json['id']),
      restaurantId: _asInt(json['restaurant_id']),
      name: (json['name'] ?? '').toString(),
      price: double.tryParse(json['price'].toString()) ?? 0.0,
      description: json['description'],
      image: json['image'],
      category: json['category'],
      optionGroups: groups,
    );
  }
}


