class Restaurant {
  final int id;
  final String name;
  final String? image;
  final String category;
  final bool isOpen;
  final String? email;
  final String? phone;
  final double rating;
  final int ratingsCount;
  final int? myRating;
  final double? avgPrice;

  Restaurant({
    required this.id,
    required this.name,
    this.image,
    required this.category,
    required this.isOpen,
    this.email,
    this.phone,
    this.rating = 0,
    this.ratingsCount = 0,
    this.myRating,
    this.avgPrice,
  });

  factory Restaurant.fromJson(Map<String, dynamic> json) {
    final dynamic rawRating = json['rating'];
    final double parsedRating = rawRating == null
        ? 0
        : (rawRating is String
              ? double.tryParse(rawRating) ?? 0
              : (rawRating as num).toDouble());

    return Restaurant(
      id: json['id'],
      name: json['name'],
      image: json['image'],
      category: json['category'] ?? '',
      isOpen: json['is_open'] ?? true,
      email: json['email'],
      phone: json['phone'],
      rating: parsedRating,
      ratingsCount: json['ratings_count'] ?? 0,
      myRating: json['my_rating'],
      avgPrice: json['avg_price'] != null
          ? (json['avg_price'] is String
                ? double.tryParse(json['avg_price']) ?? 0
                : (json['avg_price'] as num).toDouble())
          : null,
    );
  }

  Restaurant copyWith({double? rating, int? ratingsCount, int? myRating}) {
    return Restaurant(
      id: id,
      name: name,
      image: image,
      category: category,
      isOpen: isOpen,
      email: email,
      phone: phone,
      rating: rating ?? this.rating,
      ratingsCount: ratingsCount ?? this.ratingsCount,
      myRating: myRating ?? this.myRating,
      avgPrice: avgPrice,
    );
  }
}



