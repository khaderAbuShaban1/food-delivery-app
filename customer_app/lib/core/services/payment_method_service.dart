import '../api/api_client.dart';
import '../models/active_payment_method.dart';

class PaymentMethodService {
  static bool _ok(Map<String, dynamic> response) {
    return response['status'] == true || response['success'] == true;
  }

  static Future<List<ActivePaymentMethod>> getActivePaymentMethods() async {
    final response = await ApiClient.get('/payment-methods/active');
    if (!_ok(response)) {
      throw Exception(response['message']?.toString() ?? 'تعذر جلب وسائل الدفع');
    }

    final data = response['data'];
    if (data is List) {
      return data.whereType<Map<String, dynamic>>().map(ActivePaymentMethod.fromJson).toList();
    }
    return [];
  }
}



