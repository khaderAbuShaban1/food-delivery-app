class OrderModel {
  final String id;
  final String? orderNumber;

  /// Public reference for UI (prefer `order_number` from DB when present).
  final String displayOrderRef;

  final String restaurantName;
  /// Pickup/contact line — e.g. restaurant phone when no street address exists in API.
  final String restaurantAddress;
  final String customerName;
  final String customerPhone;
  /// Customer delivery destination from Laravel (`users.address`).
  final String deliveryAddress;

  final String? customerLocation;
  final String? deliveryNotes;

  /// Laravel `STATUS_LABELS` string (Arabic); optional override for badge text.
  final String? statusLabel;

  final double totalPrice;
  final double? distanceKm;
  final String status;
  final int? driverId;
  final List<String> items;
  final DateTime? createdAt;
  final DateTime? updatedAt;
  final DateTime? assignedAt;

  const OrderModel({
    required this.id,
    required this.orderNumber,
    required this.displayOrderRef,
    required this.restaurantName,
    required this.restaurantAddress,
    required this.customerName,
    required this.customerPhone,
    required this.deliveryAddress,
    required this.customerLocation,
    required this.deliveryNotes,
    required this.statusLabel,
    required this.totalPrice,
    required this.distanceKm,
    required this.status,
    required this.driverId,
    required this.items,
    required this.createdAt,
    required this.updatedAt,
    required this.assignedAt,
  });

  /// Single line "Name — Phone" for compact cards.
  String get customerInfoLine {
    final parts =
        [customerName.trim(), customerPhone.trim()].where((s) => s.isNotEmpty).toList(growable: false);
    if (parts.isEmpty) return '';
    return parts.join(' — ');
  }

  bool get isPending => _normStatus(status) == 'pending';
  bool get isPreparing => _normStatus(status) == 'preparing';

  /// Driver-facing stage alias from backend workflow:
  /// preparing -> accepted, on_the_way -> delivering, delivered -> completed.
  String get driverStageStatus {
    switch (_normStatus(status)) {
      case 'preparing':
        return 'accepted';
      case 'on_the_way':
        return 'delivering';
      case 'delivered':
        return 'completed';
      default:
        return _normStatus(status);
    }
  }

  bool get isActive {
    switch (driverStageStatus) {
      case 'accepted':
      case 'delivering':
        return true;
      default:
        return false;
    }
  }

  static String _normStatus(String raw) =>
      raw.trim().toLowerCase().replaceAll(' ', '_').replaceAll('-', '_');

  static double _toDouble(dynamic value) {
    if (value is num) return value.toDouble();
    if (value is String) return double.tryParse(value.trim()) ?? 0;
    return 0;
  }

  static String _stringOrEmpty(dynamic value) {
    if (value == null) return '';
    final s = value.toString().trim();
    if (s.isEmpty || s == 'null') return '';
    return s;
  }

  static String _optionsLine(dynamic optionsRaw) {
    if (optionsRaw is! List) return '';
    final grouped = <String, List<String>>{};
    for (final e in optionsRaw) {
      if (e is! Map) continue;
      final m = Map<String, dynamic>.from(e as Map);
      final g = (m['group_name'] ?? '').toString().trim();
      final v = (m['value_name'] ?? '').toString().trim();
      if (g.isEmpty || v.isEmpty) continue;
      (grouped[g] ??= <String>[]).add(v);
    }
    if (grouped.isEmpty) return '';
    final parts = <String>[];
    grouped.forEach((g, vals) {
      final uniq = vals.toSet().toList();
      parts.add('$g: ${uniq.join('، ')}');
    });
    return parts.join('، ');
  }

  static String? _optionalString(dynamic value) {
    final s = _stringOrEmpty(value);
    return s.isEmpty ? null : s;
  }

  static DateTime? _optionalDate(dynamic value) {
    if (value == null) return null;
    if (value is DateTime) return value.toLocal();
    final raw = value.toString().trim();
    if (raw.isEmpty || raw == 'null') return null;
    final parsed = DateTime.tryParse(raw);
    return parsed?.toLocal();
  }

  /// Build from Laravel `OrderController::formatOrder` JSON (driver routes use the same formatter).
  factory OrderModel.fromLaravelApi(Map<String, dynamic> json) {
    final id = json['id']?.toString() ?? '';
    final orderNumberRaw = _optionalString(json['order_number']);
    final displayRef = orderNumberRaw ?? id;

    final restaurant = json['restaurant'] is Map
        ? Map<String, dynamic>.from(json['restaurant'] as Map)
        : const <String, dynamic>{};
    final customer = json['customer'] is Map
        ? Map<String, dynamic>.from(json['customer'] as Map)
        : const <String, dynamic>{};
    final driverMap = json['driver'] is Map ? Map<String, dynamic>.from(json['driver'] as Map) : null;

    final rawItems = json['items'];
    final itemsList = <String>[];
    if (rawItems is List) {
      for (final e in rawItems) {
        if (e is Map) {
          final m = Map<String, dynamic>.from(e);
          final name = (m['name'] ?? '').toString().trim();
          final qty = m['quantity'];
          final qtyStr =
              qty is num ? qty.toInt().toString() : (qty ?? '').toString().trim();
          final optionsText = _optionsLine(m['options']);
          final fullName = optionsText.isEmpty ? name : '$name ($optionsText)';
          if (name.isNotEmpty && qtyStr.isNotEmpty) {
            itemsList.add('$fullName × $qtyStr');
          } else if (name.isNotEmpty) {
            itemsList.add(fullName);
          }
        }
      }
    }

    int? parsedDriverId;
    final rawDriverId = json['driver_id'] ?? driverMap?['id'];
    if (rawDriverId is num) {
      parsedDriverId = rawDriverId.toInt();
    } else if (rawDriverId is String && rawDriverId.trim().isNotEmpty) {
      parsedDriverId = int.tryParse(rawDriverId.trim());
    }

    final custName = _stringOrEmpty(customer['name']);
    final custPhone = _stringOrEmpty(customer['phone']);
    final rootDelivery = _stringOrEmpty(json['delivery_address']);
    final nestedCustAddr = _stringOrEmpty(customer['address']);
    final deliveryAddr =
        rootDelivery.isNotEmpty ? rootDelivery : nestedCustAddr;

    final rName = _stringOrEmpty(restaurant['name']);
    final restaurantPhone = _stringOrEmpty(restaurant['phone']);

    final statusLabelRaw = _optionalString(json['status_label']);
    final createdAt = _optionalDate(json['created_at']);
    final updatedAt = _optionalDate(json['updated_at']);
    final assignedAt = _optionalDate(json['assigned_at']);

    return OrderModel(
      id: id,
      orderNumber: orderNumberRaw,
      displayOrderRef: displayRef,
      restaurantName: rName.isNotEmpty
          ? rName
          : (json['restaurant_id'] != null ? 'مطعم #${json['restaurant_id']}' : 'مطعم غير معروف'),
      restaurantAddress: restaurantPhone,
      customerName: custName,
      customerPhone: custPhone,
      deliveryAddress: deliveryAddr,
      customerLocation: null,
      deliveryNotes: null,
      statusLabel: statusLabelRaw,
      totalPrice: _toDouble(json['total_price']),
      distanceKm: null,
      status: (json['status'] ?? 'pending').toString(),
      driverId: parsedDriverId,
      items: itemsList,
      createdAt: createdAt,
      updatedAt: updatedAt,
      assignedAt: assignedAt,
    );
  }
}
