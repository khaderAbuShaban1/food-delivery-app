import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../models/driver_model.dart';
import 'api_client.dart';

class AuthService {
  static const _tokenKey = 'driver_token';
  static const _driverKey = 'driver_data';

  final ApiClient _apiClient;
  final FlutterSecureStorage _secureStorage;

  AuthService({
    ApiClient? apiClient,
    FlutterSecureStorage? secureStorage,
  })  : _apiClient = apiClient ?? ApiClient(),
        _secureStorage = secureStorage ?? const FlutterSecureStorage();

  Future<(String?, DriverModel?, String)> login(String email, String password) async {
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
    await _secureStorage.write(key: _driverKey, value: jsonEncode(driver.toJson()));

    return (token, driver, 'success');
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
