class Address {
  final int id;
  final String title;
  final String city;
  final String street;
  final String? details;
  final bool isDefault;

  Address({
    required this.id,
    required this.title,
    required this.city,
    required this.street,
    this.details,
    required this.isDefault,
  });

  String get fullAddress {
    final detailsValue = details?.trim();
    if (detailsValue != null && detailsValue.isNotEmpty) {
      return '$city - $street - $detailsValue';
    }
    return '$city - $street';
  }

  factory Address.fromJson(Map<String, dynamic> json) {
    final rawId = json['id'];
    final id = rawId is int
        ? rawId
        : rawId is num
            ? rawId.toInt()
            : int.tryParse(rawId?.toString() ?? '') ?? 0;
    return Address(
      id: id,
      title: (json['title'] ?? '').toString(),
      city: (json['city'] ?? '').toString(),
      street: (json['street'] ?? '').toString(),
      details: json['details']?.toString(),
      isDefault: json['is_default'] == true || json['is_default'] == 1,
    );
  }
}



