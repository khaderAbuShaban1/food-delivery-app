import '../api/api_client.dart';

class AuthService {
  static Map<String, dynamic>? _currentUser;

  static bool _isSuccess(Map<String, dynamic> response) {
    return response['status'] == true || response['success'] == true;
  }

  static String? _extractToken(Map<String, dynamic> response) {
    final directToken = response['token']?.toString();
    if (directToken != null && directToken.isNotEmpty) {
      return directToken;
    }

    final accessToken = response['access_token']?.toString();
    if (accessToken != null && accessToken.isNotEmpty) {
      return accessToken;
    }

    if (response['data'] is Map<String, dynamic>) {
      final data = response['data'] as Map<String, dynamic>;
      final nestedToken = data['token']?.toString() ?? data['access_token']?.toString();
      if (nestedToken != null && nestedToken.isNotEmpty) {
        return nestedToken;
      }
    }

    return null;
  }

  static Map<String, dynamic>? _extractUser(Map<String, dynamic> response) {
    if (response['data'] is Map<String, dynamic>) {
      return response['data'] as Map<String, dynamic>;
    }
    if (response['user'] is Map<String, dynamic>) {
      return response['user'] as Map<String, dynamic>;
    }
    return null;
  }

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

      if (_isSuccess(response)) {
        final token = _extractToken(response);
        if (token == null || token.isEmpty) {
          return response['message']?.toString() ?? 'تعذر قراءة رمز الدخول';
        }
        ApiClient.token = token;
        _currentUser = _extractUser(response);
        return 'success';
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

      if (_isSuccess(response)) {
        final token = _extractToken(response);
        if (token == null || token.isEmpty) {
          return response['message']?.toString() ?? 'تعذر قراءة رمز الدخول';
        }
        ApiClient.token = token;
        _currentUser = _extractUser(response);
        return 'success';
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
    if (_isSuccess(response) && response['data'] is Map<String, dynamic>) {
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

      if (_isSuccess(response)) {
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

      if (_isSuccess(response)) {
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

      if (_isSuccess(response)) {
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
