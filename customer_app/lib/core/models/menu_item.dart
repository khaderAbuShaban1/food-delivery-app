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

  factory MenuItem.fromJson(Map<String, dynamic> json) {
    return MenuItem(
      id: json['id'],
      restaurantId: json['restaurant_id'],
      name: json['name'],
      price: double.parse(json['price'].toString()),
      description: json['description'],
      image: json['image'],
      category: json['category'],
    );
  }
}