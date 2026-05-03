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
    notifyListeners();

    final (token, loggedDriver, message) = await _authService.login(email, password);
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

  Future<void> logout() async {
    await _authService.logout();
    apiClient.token = null;
    driver = null;
    notifyListeners();
  }
}
