import 'package:flutter/material.dart';

import '../models/driver_model.dart';
import '../services/api_client.dart';
import '../services/auth_service.dart';

class AuthProvider extends ChangeNotifier {
  final AuthService _authService = AuthService();
  final ApiClient apiClient = ApiClient();

  DriverModel? driver;
  bool isLoading = false;
  bool isRestoring = true;
  String? error;
  String? successMessage;

  bool get isLoggedIn => driver != null && apiClient.token != null;

  Future<void> restoreSession() async {
    final (token, restoredDriver) = await _authService.restoreSession();
    apiClient.token = token;
    driver = restoredDriver;
    isRestoring = false;
    notifyListeners();
  }

  Future<bool> login(String email, String password) async {
    isLoading = true;
    error = null;
    successMessage = null;
    notifyListeners();

    final (token, loggedDriver, message) = await _authService.login(
      email,
      password,
    );
    if (token == null || loggedDriver == null) {
      error = message;
      isLoading = false;
      notifyListeners();
      return false;
    }

    apiClient.token = token;
    driver = loggedDriver;
    isLoading = false;
    notifyListeners();
    return true;
  }

  Future<bool> register({
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
    isLoading = true;
    error = null;
    successMessage = null;
    notifyListeners();

    final (ok, message, errors) = await _authService.register(
      name: name,
      nationalId: nationalId,
      phone: phone,
      email: email,
      password: password,
      passwordConfirmation: passwordConfirmation,
      vehicleType: vehicleType,
      vehiclePlateNumber: vehiclePlateNumber,
      city: city,
      emergencyContactNumber: emergencyContactNumber,
      profileImagePath: profileImagePath,
      nationalIdImagePath: nationalIdImagePath,
      vehicleImagePath: vehicleImagePath,
    );

    isLoading = false;
    if (ok) {
      successMessage = message;
    } else {
      error = _firstValidationMessage(errors) ?? message;
    }
    notifyListeners();
    return ok;
  }

  Future<bool> verifyEmail({
    required String email,
    required String code,
  }) async {
    isLoading = true;
    error = null;
    successMessage = null;
    notifyListeners();

    final (ok, message) = await _authService.verifyEmail(
      email: email,
      code: code,
    );
    isLoading = false;
    if (ok) {
      successMessage = message;
    } else {
      error = message;
    }
    notifyListeners();
    return ok;
  }

  Future<bool> resendVerificationCode({required String email}) async {
    isLoading = true;
    error = null;
    successMessage = null;
    notifyListeners();

    final (ok, message) = await _authService.resendVerificationCode(
      email: email,
    );
    isLoading = false;
    if (ok) {
      successMessage = message;
    } else {
      error = message;
    }
    notifyListeners();
    return ok;
  }

  String? _firstValidationMessage(Map<String, dynamic>? errors) {
    if (errors == null || errors.isEmpty) return null;
    final first = errors.values.first;
    if (first is List && first.isNotEmpty) return first.first.toString();
    return first?.toString();
  }

  Future<void> logout() async {
    await _authService.logout();
    apiClient.token = null;
    driver = null;
    successMessage = null;
    notifyListeners();
  }
}
