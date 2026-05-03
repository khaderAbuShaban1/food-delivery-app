class DriverModel {
  final int id;
  final String name;
  final String email;
  final String? phone;
  final bool isAvailable;

  DriverModel({
    required this.id,
    required this.name,
    required this.email,
    required this.phone,
    required this.isAvailable,
  });

  factory DriverModel.fromJson(Map<String, dynamic> json) {
    return DriverModel(
      id: json['id'] as int,
      name: (json['name'] ?? '').toString(),
      email: (json['email'] ?? '').toString(),
      phone: json['phone']?.toString(),
      isAvailable: json['is_available'] == true || json['is_available'] == 1,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'email': email,
      'phone': phone,
      'is_available': isAvailable,
    };
  }
}
