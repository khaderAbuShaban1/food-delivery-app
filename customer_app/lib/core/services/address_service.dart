import '../api/api_client.dart';
import '../models/address.dart';

class AddressService {
  static bool _isSuccess(Map<String, dynamic> response) {
    return response['status'] == true || response['success'] == true;
  }

  static Future<List<Address>> getAddresses() async {
    final response = await ApiClient.get('/addresses');
    if (!_isSuccess(response)) {
      throw Exception(response['message']?.toString() ?? 'تعذر جلب العناوين');
    }

    final data = response['data'];
    if (data is List) {
      return data
          .whereType<Map<String, dynamic>>()
          .map(Address.fromJson)
          .toList();
    }

    return [];
  }

  static Future<Address> createAddress({
    required String title,
    required String city,
    required String street,
    String? details,
    required bool isDefault,
  }) async {
    final response = await ApiClient.post('/addresses', {
      'title': title,
      'city': city,
      'street': street,
      'details': details,
      'is_default': isDefault,
    });

    if (!_isSuccess(response) || response['data'] is! Map<String, dynamic>) {
      throw Exception(response['message']?.toString() ?? 'تعذر إضافة العنوان');
    }

    final address = Address.fromJson(response['data'] as Map<String, dynamic>);
    return address;
  }

  static Future<Address> updateAddress({
    required int id,
    required String title,
    required String city,
    required String street,
    String? details,
    required bool isDefault,
  }) async {
    final response = await ApiClient.put('/addresses/$id', {
      'title': title,
      'city': city,
      'street': street,
      'details': details,
      'is_default': isDefault,
    });

    if (!_isSuccess(response) || response['data'] is! Map<String, dynamic>) {
      throw Exception(response['message']?.toString() ?? 'تعذر تحديث العنوان');
    }

    final address = Address.fromJson(response['data'] as Map<String, dynamic>);
    return address;
  }

  static Future<void> deleteAddress(int id) async {
    final response = await ApiClient.delete('/addresses/$id');
    if (!_isSuccess(response)) {
      throw Exception(response['message']?.toString() ?? 'تعذر حذف العنوان');
    }
  }
}



