class MenuItemOptionValue {
  final int id;
  final String name;
  final double extraPrice;
  final int sortOrder;

  MenuItemOptionValue({
    required this.id,
    required this.name,
    required this.extraPrice,
    required this.sortOrder,
  });

  static int _asInt(dynamic v) {
    if (v == null) return 0;
    if (v is int) return v;
    if (v is num) return v.toInt();
    return int.tryParse(v.toString().trim()) ?? 0;
  }

  static double _asDouble(dynamic v) {
    if (v == null) return 0;
    if (v is double) return v;
    if (v is num) return v.toDouble();
    return double.tryParse(v.toString().trim()) ?? 0;
  }

  factory MenuItemOptionValue.fromJson(Map<String, dynamic> json) {
    return MenuItemOptionValue(
      id: _asInt(json['id']),
      name: (json['name'] ?? '').toString(),
      extraPrice: _asDouble(json['extra_price']),
      sortOrder: _asInt(json['sort_order']),
    );
  }
}

class MenuItemOptionGroup {
  final int id;
  final String name;
  /// Expected values from backend: `single` | `multiple` (fallback: treats unknown as single)
  final String selectionType;
  final int sortOrder;
  final List<MenuItemOptionValue> values;

  MenuItemOptionGroup({
    required this.id,
    required this.name,
    required this.selectionType,
    required this.sortOrder,
    required this.values,
  });

  bool get isMulti => selectionType.toLowerCase().trim() == 'multiple';

  static int _asInt(dynamic v) {
    if (v == null) return 0;
    if (v is int) return v;
    if (v is num) return v.toInt();
    return int.tryParse(v.toString().trim()) ?? 0;
  }

  factory MenuItemOptionGroup.fromJson(Map<String, dynamic> json) {
    final rawValues = json['values'];
    final values = <MenuItemOptionValue>[];
    if (rawValues is List) {
      for (final e in rawValues) {
        if (e is Map) {
          values.add(MenuItemOptionValue.fromJson(Map<String, dynamic>.from(e as Map)));
        }
      }
    }
    values.sort((a, b) => a.sortOrder.compareTo(b.sortOrder));

    return MenuItemOptionGroup(
      id: _asInt(json['id']),
      name: (json['name'] ?? '').toString(),
      selectionType: (json['selection_type'] ?? 'single').toString(),
      sortOrder: _asInt(json['sort_order']),
      values: values,
    );
  }
}




