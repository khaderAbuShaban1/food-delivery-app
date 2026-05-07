import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;

import '../core/config/app_config.dart';

class ApiClient {
  String? token;

  Map<String, String> get _headers => {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    if (token != null) 'Authorization': 'Bearer $token',
  };

  Future<Map<String, dynamic>> get(String endpoint) async {
    try {
      final response = await http
          .get(Uri.parse('${AppConfig.apiBaseUrl}$endpoint'), headers: _headers)
          .timeout(const Duration(seconds: 15));
      return _handleResponse(response);
    } on TimeoutException {
      return {'success': false, 'message': 'Connection timed out'};
    } on SocketException {
      return {
        'success': false,
        'message':
            'Cannot reach server. Check Laravel is running and API host IP is correct: ${AppConfig.apiBaseUrl}',
      };
    } on HttpException {
      return {'success': false, 'message': 'HTTP connection failed'};
    } on FormatException {
      return {'success': false, 'message': 'Invalid server response'};
    } catch (e) {
      return {'success': false, 'message': 'Unexpected error: $e'};
    }
  }

  Future<Map<String, dynamic>> post(
    String endpoint,
    Map<String, dynamic> data,
  ) async {
    try {
      final response = await http
          .post(
            Uri.parse('${AppConfig.apiBaseUrl}$endpoint'),
            headers: _headers,
            body: jsonEncode(data),
          )
          .timeout(const Duration(seconds: 15));
      return _handleResponse(response);
    } on TimeoutException {
      return {'success': false, 'message': 'Connection timed out'};
    } on SocketException {
      return {
        'success': false,
        'message':
            'Cannot reach server. Check Laravel is running and API host IP is correct: ${AppConfig.apiBaseUrl}',
      };
    } on HttpException {
      return {'success': false, 'message': 'HTTP connection failed'};
    } on FormatException {
      return {'success': false, 'message': 'Invalid server response'};
    } catch (e) {
      return {'success': false, 'message': 'Unexpected error: $e'};
    }
  }

  Future<Map<String, dynamic>> put(
    String endpoint,
    Map<String, dynamic> data,
  ) async {
    try {
      final response = await http
          .put(
            Uri.parse('${AppConfig.apiBaseUrl}$endpoint'),
            headers: _headers,
            body: jsonEncode(data),
          )
          .timeout(const Duration(seconds: 15));
      return _handleResponse(response);
    } on TimeoutException {
      return {'success': false, 'message': 'Connection timed out'};
    } on SocketException {
      return {
        'success': false,
        'message':
            'Cannot reach server. Check Laravel is running and API host IP is correct: ${AppConfig.apiBaseUrl}',
      };
    } on HttpException {
      return {'success': false, 'message': 'HTTP connection failed'};
    } on FormatException {
      return {'success': false, 'message': 'Invalid server response'};
    } catch (e) {
      return {'success': false, 'message': 'Unexpected error: $e'};
    }
  }

  Future<Map<String, dynamic>> postMultipart(
    String endpoint, {
    required Map<String, String> fields,
    List<http.MultipartFile> files = const [],
  }) async {
    try {
      final request = http.MultipartRequest(
        'POST',
        Uri.parse('${AppConfig.apiBaseUrl}$endpoint'),
      );
      request.headers.addAll({
        'Accept': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      });
      request.fields.addAll(fields);
      request.files.addAll(files);

      final streamed = await request.send().timeout(
        const Duration(seconds: 30),
      );
      final response = await http.Response.fromStream(streamed);
      return _handleResponse(response);
    } on TimeoutException {
      return {'success': false, 'message': 'Connection timed out'};
    } on SocketException {
      return {
        'success': false,
        'message':
            'Cannot reach server. Check Laravel is running and API host IP is correct: ${AppConfig.apiBaseUrl}',
      };
    } on HttpException {
      return {'success': false, 'message': 'HTTP connection failed'};
    } on FormatException {
      return {'success': false, 'message': 'Invalid server response'};
    } catch (e) {
      return {'success': false, 'message': 'Unexpected error: $e'};
    }
  }

  Map<String, dynamic> _handleResponse(http.Response response) {
    Map<String, dynamic> body;
    try {
      final raw = jsonDecode(response.body);
      body = raw is Map<String, dynamic> ? raw : <String, dynamic>{};
    } catch (_) {
      body = <String, dynamic>{};
    }
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return body;
    }

    return {
      'success': false,
      'message': body['message']?.toString() ?? 'Request failed',
      if (body['errors'] != null) 'errors': body['errors'],
    };
  }
}
