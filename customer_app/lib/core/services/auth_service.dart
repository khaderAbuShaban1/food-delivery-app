import '../api/api_client.dart';

class AuthService {
  static Map<String, dynamic>? _currentUser;

  static Map<String, dynamic>? get currentUser => _currentUser;
  static String get currentUserName =>
      _currentUser?['name']?.toString() ?? 'ضيف';
  static String get currentUserEmail =>
      _currentUser?['email']?.toString() ?? '';
  static String? get currentUserImage => _currentUser?['profile_image']?.toString();

  static Future<String> login(String email, String password) async {
    try {
      final response = await ApiClient.post('/user/login', {
        'email': email,
        'password': password,
      });

      final status = response['status'] ?? false;
      if (status == true) {
        final token = response['token']?.toString();
        if (token != null && token.isNotEmpty) {
          ApiClient.token = token;
          _currentUser = response['data'] is Map<String, dynamic>
              ? response['data'] as Map<String, dynamic>
              : null;
          return 'success';
        }
      }

      final message = response['message']?.toString() ?? 'حدث خطأ';
      return message;
    } catch (e) {
      return 'حدث خطأ في الاتصال';
    }
  }

  static Future<String> register(
    String name,
    String email,
    String password,
    String phone,
  ) async {
    try {
      final response = await ApiClient.post('/user/register', {
        'name': name,
        'email': email,
        'password': password,
        'password_confirmation': password,
        'phone': phone,
      });

      final status = response['status'] ?? false;
      if (status == true) {
        final token = response['token']?.toString();
        if (token != null && token.isNotEmpty) {
          ApiClient.token = token;
          _currentUser = response['data'] is Map<String, dynamic>
              ? response['data'] as Map<String, dynamic>
              : null;
          return 'success';
        }
      }

      final message = response['message']?.toString() ?? 'حدث خطأ';

      // Handle validation errors
      if (response['errors'] != null) {
        final errors = response['errors'];
        final firstError = errors.keys.first;
        return errors[firstError][0]?.toString() ?? message;
      }

      return message;
    } catch (e) {
      return 'حدث خطأ في الاتصال';
    }
  }

  static Future<void> logout() async {
    try {
      await ApiClient.post('/logout', {});
    } catch (e) {
      // ignore
    }
    ApiClient.token = null;
    _currentUser = null;
  }

  static bool isLoggedIn() {
    return ApiClient.token != null;
  }

  static Future<Map<String, dynamic>?> fetchCurrentUser() async {
    if (!isLoggedIn()) return null;
    final response = await ApiClient.get('/me');
    final status = response['status'] ?? response['success'] ?? false;
    if (status == true && response['data'] is Map<String, dynamic>) {
      _currentUser = response['data'] as Map<String, dynamic>;
      return _currentUser;
    }
    return _currentUser;
  }

  static Future<String> updateProfile({
    required String name,
    String? phone,
  }) async {
    try {
      final response = await ApiClient.put('/profile', {
        'name': name,
        if (phone != null) 'phone': phone,
      });

      final status = response['status'] ?? response['success'] ?? false;
      if (status == true) {
        if (_currentUser != null) {
          _currentUser!['name'] = name;
          if (phone != null) _currentUser!['phone'] = phone;
        }
        return 'success';
      }

      final message = response['message']?.toString() ?? 'تعذر تحديث البيانات';
      return message;
    } catch (e) {
      return 'حدث خطأ في الاتصال';
    }
  }

  static Future<String> changePassword({
    required String currentPassword,
    required String newPassword,
  }) async {
    try {
      final response = await ApiClient.put('/change-password', {
        'current_password': currentPassword,
        'new_password': newPassword,
        'new_password_confirmation': newPassword,
      });

      final status = response['status'] ?? response['success'] ?? false;
      if (status == true) {
        return 'success';
      }

      final message = response['message']?.toString() ?? 'تعذر تغيير كلمة المرور';
      return message;
    } catch (e) {
      return 'حدث خطأ في الاتصال';
    }
  }

  static Future<String> uploadProfileImage(String imagePath) async {
    try {
      final response = await ApiClient.uploadFile('/profile/image', imagePath, 'image');

      final status = response['status'] ?? response['success'] ?? false;
      if (status == true) {
        await fetchCurrentUser();
        return 'success';
      }

      final message = response['message']?.toString() ?? 'تعذر رفع الصورة';
      return message;
    } catch (e) {
      return 'حدث خطأ في الاتصال';
    }
  }
}
