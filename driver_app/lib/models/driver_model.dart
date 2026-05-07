class DriverModel {
  final int id;
  final String name;
  final String email;
  final String? phone;
  final bool isAvailable;
  final String approvalStatus;
  final String? nationalId;
  final String? vehicleType;
  final String? vehiclePlateNumber;
  final String? city;

  DriverModel({
    required this.id,
    required this.name,
    required this.email,
    required this.phone,
    required this.isAvailable,
    required this.approvalStatus,
    required this.nationalId,
    required this.vehicleType,
    required this.vehiclePlateNumber,
    required this.city,
  });

  factory DriverModel.fromJson(Map<String, dynamic> json) {
    return DriverModel(
      id: json['id'] as int,
      name: (json['name'] ?? '').toString(),
      email: (json['email'] ?? '').toString(),
      phone: json['phone']?.toString(),
      isAvailable: json['is_available'] == true || json['is_available'] == 1,
      approvalStatus: (json['approval_status'] ?? 'approved').toString(),
      nationalId: json['national_id']?.toString(),
      vehicleType: json['vehicle_type']?.toString(),
      vehiclePlateNumber: json['vehicle_plate_number']?.toString(),
      city: json['city']?.toString(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'email': email,
      'phone': phone,
      'is_available': isAvailable,
      'approval_status': approvalStatus,
      'national_id': nationalId,
      'vehicle_type': vehicleType,
      'vehicle_plate_number': vehiclePlateNumber,
      'city': city,
    };
  }
}
