class ActivePaymentMethod {
  final int id;
  final String type;
  final String typeLabel;
  final String subtypeName;
  final String accountHolderName;
  final String? accountNumber;
  final String phoneNumber;
  final String? staticImageUrl;

  ActivePaymentMethod({
    required this.id,
    required this.type,
    required this.typeLabel,
    required this.subtypeName,
    required this.accountHolderName,
    required this.accountNumber,
    required this.phoneNumber,
    required this.staticImageUrl,
  });

  factory ActivePaymentMethod.fromJson(Map<String, dynamic> json) {
    final rawId = json['id'];
    final id = rawId is int
        ? rawId
        : rawId is num
            ? rawId.toInt()
            : int.tryParse(rawId?.toString() ?? '') ?? 0;
    final acc = json['account_number'];

    return ActivePaymentMethod(
      id: id,
      type: (json['type'] ?? '').toString(),
      typeLabel: (json['type_label'] ?? '').toString(),
      subtypeName: (json['subtype_name'] ?? '').toString(),
      accountHolderName: (json['account_holder_name'] ?? '').toString(),
      accountNumber: acc == null || '$acc'.trim().isEmpty ? null : '$acc',
      phoneNumber: (json['phone_number'] ?? '').toString(),
      staticImageUrl: json['static_image_url']?.toString(),
    );
  }
}



