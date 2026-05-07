import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;

import '../models/driver_model.dart';
import 'api_client.dart';

class AuthService {
  static const _tokenKey = 'driver_token';
  static const _driverKey = 'driver_data';

  final ApiClient _apiClient;
  final FlutterSecureStorage _secureStorage;

  AuthService({ApiClient? apiClient, FlutterSecureStorage? secureStorage})
    : _apiClient = apiClient ?? ApiClient(),
      _secureStorage = secureStorage ?? const FlutterSecureStorage();

  Future<(String?, DriverModel?, String)> login(
    String email,
    String password,
  ) async {
    final response = await _apiClient.post('/driver/login', {
      'email': email,
      'password': password,
    });

    if (response['success'] != true) {
      return (null, null, response['message']?.toString() ?? 'Login failed');
    }

    final token = response['token']?.toString();
    final data = response['data'];
    if (token == null || data is! Map<String, dynamic>) {
      return (null, null, 'Invalid login response');
    }

    final driver = DriverModel.fromJson(data);
    await _secureStorage.write(key: _tokenKey, value: token);
    await _secureStorage.write(
      key: _driverKey,
      value: jsonEncode(driver.toJson()),
    );

    return (token, driver, 'success');
  }

  Future<(bool, String, Map<String, dynamic>?)> register({
    required String name,
    required String nationalId,
    required String phone,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String vehicleType,
    String? vehiclePlateNumber,
    String? city,
    String? emergencyContactNumber,
    required String profileImagePath,
    required String nationalIdImagePath,
    required String vehicleImagePath,
  }) async {
    final fields = <String, String>{
      'name': name,
      'national_id': nationalId,
      'phone': phone,
      'email': email,
      'password': password,
      'password_confirmation': passwordConfirmation,
      'vehicle_type': vehicleType,
      if (vehiclePlateNumber != null && vehiclePlateNumber.trim().isNotEmpty)
        'vehicle_plate_number': vehiclePlateNumber.trim(),
      if (city != null && city.trim().isNotEmpty) 'city': city.trim(),
      if (emergencyContactNumber != null &&
          emergencyContactNumber.trim().isNotEmpty)
        'emergency_contact_number': emergencyContactNumber.trim(),
    };

    final files = <http.MultipartFile>[
      await http.MultipartFile.fromPath('profile_image', profileImagePath),
      await http.MultipartFile.fromPath(
        'national_id_image',
        nationalIdImagePath,
      ),
      await http.MultipartFile.fromPath('vehicle_image', vehicleImagePath),
    ];

    final response = await _apiClient.postMultipart(
      '/driver/register',
      fields: fields,
      files: files,
    );
    final errors = response['errors'] is Map<String, dynamic>
        ? Map<String, dynamic>.from(response['errors'] as Map)
        : null;

    if (response['success'] == true) {
      return (
        true,
        response['message']?.toString() ??
            'تم إرسال طلبك للإدارة وبانتظار الموافقة',
        errors,
      );
    }

    return (
      false,
      response['message']?.toString() ?? 'تعذر إرسال طلب التسجيل',
      errors,
    );
  }

  Future<(bool, String)> verifyEmail({
    required String email,
    required String code,
  }) async {
    final response = await _apiClient.post('/driver/verify-email', {
      'email': email,
      'code': code,
    });

    if (response['success'] == true) {
      return (true, response['message']?.toString() ?? 'تم التحقق بنجاح');
    }

    return (false, response['message']?.toString() ?? 'تعذر التحقق من البريد');
  }

  Future<(bool, String)> resendVerificationCode({required String email}) async {
    final response = await _apiClient.post('/driver/resend-verification', {
      'email': email,
    });

    if (response['success'] == true) {
      return (
        true,
        response['message']?.toString() ??
            'تم إرسال رمز التحقق إلى بريدك الإلكتروني',
      );
    }

    return (false, response['message']?.toString() ?? 'تعذر إعادة إرسال الرمز');
  }

  Future<(String?, DriverModel?)> restoreSession() async {
    final token = await _secureStorage.read(key: _tokenKey);
    final rawDriver = await _secureStorage.read(key: _driverKey);

    if (token == null || rawDriver == null) {
      return (null, null);
    }

    final json = jsonDecode(rawDriver);
    if (json is! Map<String, dynamic>) {
      return (null, null);
    }

    return (token, DriverModel.fromJson(json));
  }

  Future<void> logout() async {
    await _secureStorage.delete(key: _tokenKey);
    await _secureStorage.delete(key: _driverKey);
  }
}
