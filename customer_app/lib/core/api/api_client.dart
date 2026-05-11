import 'package:http/http.dart' as http;
import 'dart:async';
import 'dart:convert';
import 'dart:io';

class ApiClient {
  // IMPORTANT: Change this to your computer's local IP
  // To find your IP: ipconfig (Windows) or ifconfig (Mac/Linux)
  // Look for IPv4 Address like 192.168.x.x
  // 
  // For Android Emulator: use 10.0.2.2 (special IP for host machine)
  // For iOS Emulator: use 127.0.0.1
  // For Real Device: use your actual network IP (e.g., 192.168.1.100)
  // 
  // UPDATE THIS IP to match your network!
  static String baseUrl = 'http://YOUR_COMPUTER_IP_HERE:8000/api';
  
  static String? token;

  static Map<String, String> get headers => {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    if (token != null) 'Authorization': 'Bearer $token',
  };

  static Future<Map<String, dynamic>> get(String endpoint) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl$endpoint'),
        headers: headers,
      ).timeout(Duration(seconds: 15));
      return _handleResponse(response);
    } on TimeoutException {
      return {
        'success': false,
        'message':
            'انتهت مهلة الاتصال بالخادم. تأكد من تشغيل Laravel وأن الهاتف على نفس الشبكة.',
      };
    } on SocketException {
      return {
        'success': false,
        'message':
            'تعذر الوصول إلى الخادم. تحقق من عنوان IP في التطبيق ومن اتصال الشبكة.',
      };
    } catch (e) {
      return {'success': false, 'message': 'حدث خطأ في الاتصال: $e'};
    }
  }

  static Future<Map<String, dynamic>> post(String endpoint, Map<String, dynamic> data) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl$endpoint'),
        headers: headers,
        body: jsonEncode(data),
      ).timeout(Duration(seconds: 15));
      return _handleResponse(response);
    } on TimeoutException {
      return {
        'success': false,
        'message':
            'انتهت مهلة الاتصال بالخادم. تأكد من تشغيل Laravel وأن الهاتف على نفس الشبكة.',
      };
    } on SocketException {
      return {
        'success': false,
        'message':
            'تعذر الوصول إلى الخادم. تحقق من عنوان IP في التطبيق ومن اتصال الشبكة.',
      };
    } catch (e) {
      return {'success': false, 'message': 'حدث خطأ في الاتصال: $e'};
    }
  }

  static Future<Map<String, dynamic>> put(String endpoint, Map<String, dynamic> data) async {
    try {
      final response = await http.put(
        Uri.parse('$baseUrl$endpoint'),
        headers: headers,
        body: jsonEncode(data),
      ).timeout(Duration(seconds: 15));
      return _handleResponse(response);
    } on TimeoutException {
      return {
        'success': false,
        'message':
            'انتهت مهلة الاتصال بالخادم. تأكد من تشغيل Laravel وأن الهاتف على نفس الشبكة.',
      };
    } on SocketException {
      return {
        'success': false,
        'message':
            'تعذر الوصول إلى الخادم. تحقق من عنوان IP في التطبيق ومن اتصال الشبكة.',
      };
    } catch (e) {
      return {'success': false, 'message': 'حدث خطأ في الاتصال: $e'};
    }
  }

  static Future<Map<String, dynamic>> delete(String endpoint) async {
    try {
      final response = await http.delete(
        Uri.parse('$baseUrl$endpoint'),
        headers: headers,
      ).timeout(Duration(seconds: 15));
      return _handleResponse(response);
    } on TimeoutException {
      return {
        'success': false,
        'message':
            'انتهت مهلة الاتصال بالخادم. تأكد من تشغيل Laravel وأن الهاتف على نفس الشبكة.',
      };
    } on SocketException {
      return {
        'success': false,
        'message':
            'تعذر الوصول إلى الخادم. تحقق من عنوان IP في التطبيق ومن اتصال الشبكة.',
      };
    } catch (e) {
      return {'success': false, 'message': 'حدث خطأ في الاتصال: $e'};
    }
  }

  /// Order creation with `items[]` fields and optional `payment_proof` file — do not send JSON Content-Type.
  static Future<Map<String, dynamic>> postMultipart(
    String endpoint, {
    required Map<String, String> fields,
    List<http.MultipartFile> files = const [],
  }) async {
    try {
      final uri = Uri.parse('$baseUrl$endpoint');
      final request = http.MultipartRequest('POST', uri);
      request.headers.addAll({
        'Accept': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      });
      request.fields.addAll(fields);
      request.files.addAll(files);

      final streamedResponse = await request.send().timeout(Duration(seconds: 45));
      final response = await http.Response.fromStream(streamedResponse);
      return _handleResponse(response);
    } on TimeoutException {
      return {
        'success': false,
        'message':
            'انتهت مهلة الاتصال بالخادم. تأكد من تشغيل Laravel وأن الهاتف على نفس الشبكة.',
      };
    } on SocketException {
      return {
        'success': false,
        'message':
            'تعذر الوصول إلى الخادم. تحقق من عنوان IP في التطبيق ومن اتصال الشبكة.',
      };
    } catch (e) {
      return {'success': false, 'message': 'حدث خطأ في الاتصال: $e'};
    }
  }

  static Future<Map<String, dynamic>> uploadFile(String endpoint, String filePath, String fieldName) async {
    try {
      final uri = Uri.parse('$baseUrl$endpoint');
      final request = http.MultipartRequest('POST', uri);
      
      request.headers.addAll(headers);
      request.headers.remove('Content-Type');
      
      final file = await http.MultipartFile.fromPath(fieldName, filePath);
      request.files.add(file);
      
      final streamedResponse = await request.send();
      final response = await http.Response.fromStream(streamedResponse);
      
      return _handleResponse(response);
    } on TimeoutException {
      return {
        'success': false,
        'message':
            'انتهت مهلة الاتصال بالخادم. تأكد من تشغيل Laravel وأن الهاتف على نفس الشبكة.',
      };
    } on SocketException {
      return {
        'success': false,
        'message':
            'تعذر الوصول إلى الخادم. تحقق من عنوان IP في التطبيق ومن اتصال الشبكة.',
      };
    } catch (e) {
      return {'success': false, 'message': 'حدث خطأ في الاتصال: $e'};
    }
  }

  static Map<String, dynamic> _handleResponse(http.Response response) {
    Map<String, dynamic> body;
    try {
      final decoded = jsonDecode(response.body);
      body = decoded is Map<String, dynamic> ? decoded : <String, dynamic>{};
    } catch (_) {
      body = <String, dynamic>{};
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return body;
    }

    final backendMessage = body['message']?.toString();
    return {
      'success': false,
      'message': backendMessage ?? 'فشل الطلب: ${response.statusCode}',
      'http_status': response.statusCode,
      if (body['errors'] != null) 'errors': body['errors'],
      if (body['status'] != null) 'status': body['status'],
    };
  }

  /// Origin without `/api` (for Laravel `asset()` URLs returned by the API).
  static String get publicOrigin {
    var u = baseUrl.trim();
    if (u.endsWith('/api')) {
      u = u.substring(0, u.length - 4);
    } else if (u.endsWith('/api/')) {
      u = u.substring(0, u.length - 5);
    }
    while (u.endsWith('/')) {
      u = u.substring(0, u.length - 1);
    }
    return u;
  }

  // Call this once at app start to set correct IP
  static void setBaseUrl(String ip) {
    baseUrl = 'http://$ip:8000/api';
  }
}


