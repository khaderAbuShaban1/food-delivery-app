class MenuItem {
  final int id;
  final int restaurantId;
  final String name;
  final double price;
  final String? description;
  final String? image;
  final String? category;

  MenuItem({
    required this.id,
    required this.restaurantId,
    required this.name,
    required this.price,
    this.description,
    this.image,
    this.category,
  });

  static int _asInt(dynamic v) {
    if (v == null) return 0;
    if (v is int) return v;
    if (v is num) return v.toInt();
    return int.tryParse(v.toString().trim()) ?? 0;
  }

  factory MenuItem.fromJson(Map<String, dynamic> json) {
    return MenuItem(
      id: _asInt(json['id']),
      restaurantId: _asInt(json['restaurant_id']),
      name: (json['name'] ?? '').toString(),
      price: double.tryParse(json['price'].toString()) ?? 0.0,
      description: json['description'],
      image: json['image'],
      category: json['category'],
    );
  }
}