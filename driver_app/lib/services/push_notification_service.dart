import 'package:device_info_plus/device_info_plus.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

import 'api_client.dart';

class PushNotificationService {
  PushNotificationService(this._apiClient);

  static const String _androidChannelId = 'food_delivery_driver_orders_loud_v2';

  static final Int64List _vibrationPattern = Int64List.fromList(<int>[
    0,
    350,
    140,
    350,
  ]);

  static final AndroidNotificationChannel _androidChannel =
      AndroidNotificationChannel(
        _androidChannelId,
        'Driver order updates',
        description: 'Real-time order assignment and delivery notifications',
        importance: Importance.max,
        playSound: true,
        enableVibration: true,
        vibrationPattern: _vibrationPattern,
        audioAttributesUsage: AudioAttributesUsage.notification,
      );

  static final FlutterLocalNotificationsPlugin _localNotifications =
      FlutterLocalNotificationsPlugin();
  static bool _initialized = false;
  static bool _foregroundListenerAttached = false;

  final ApiClient _apiClient;
  final FirebaseMessaging _messaging = FirebaseMessaging.instance;
  bool _refreshListenerAttached = false;

  static Future<void> initialize() async {
    if (_initialized) return;
    _initialized = true;

    const androidSettings = AndroidInitializationSettings(
      '@mipmap/ic_launcher',
    );
    const initializationSettings = InitializationSettings(
      android: androidSettings,
    );

    await _localNotifications.initialize(settings: initializationSettings);

    final androidNotifications = _localNotifications
        .resolvePlatformSpecificImplementation<
          AndroidFlutterLocalNotificationsPlugin
        >();
    await androidNotifications?.createNotificationChannel(_androidChannel);
    if (kDebugMode) {
      debugPrint(
        '[PushNotificationService] loud Android channel ready: ${_androidChannel.id}',
      );
    }

    await FirebaseMessaging.instance
        .setForegroundNotificationPresentationOptions(
          alert: true,
          badge: true,
          sound: true,
        );

    if (!_foregroundListenerAttached) {
      _foregroundListenerAttached = true;
      FirebaseMessaging.onMessage.listen(_showForegroundNotification);
    }
  }

  Future<void> registerDeviceToken() async {
    if (_apiClient.token == null) return;

    try {
      await initialize();
      await _requestPermissionIfNeeded();

      final token = await _resolveFcmTokenWithRetry();
      if (token == null || token.isEmpty) {
        if (kDebugMode) {
          debugPrint('[DriverPush] FCM token is empty');
        }
        return;
      }

      await _sendToken(token);

      if (!_refreshListenerAttached) {
        _refreshListenerAttached = true;
        _messaging.onTokenRefresh.listen((newToken) {
          if (_apiClient.token != null) {
            _sendToken(newToken);
          }
        });
      }
    } catch (e) {
      if (kDebugMode) {
        debugPrint('[DriverPush] registerDeviceToken failed: $e');
      }
      // Push registration must not block driver login or order actions.
    }
  }

  Future<String?> _resolveFcmTokenWithRetry() async {
    for (var i = 0; i < 5; i++) {
      final token = await _messaging.getToken();
      if (token != null && token.isNotEmpty) {
        return token;
      }
      await Future<void>.delayed(const Duration(seconds: 2));
    }
    return null;
  }

  Future<void> unregisterDeviceToken() async {
    if (_apiClient.token == null) return;

    try {
      final token = await _messaging.getToken();
      if (token == null || token.isEmpty) return;

      await _apiClient.delete('/driver/fcm-token', {'token': token});
    } catch (_) {
      // Logout should continue even if token cleanup fails.
    }
  }

  Future<void> _sendToken(String token) async {
    Map<String, dynamic> response = <String, dynamic>{};
    for (var i = 0; i < 3; i++) {
      response = await _apiClient.post('/driver/fcm-token', {
        'token': token,
        'platform': _platform,
      });
      if (response['success'] == true || response['status'] == true) {
        if (kDebugMode) {
          debugPrint('[DriverPush] token registered successfully');
        }
        return;
      }
      await Future<void>.delayed(const Duration(seconds: 1));
    }

    final message =
        response['message']?.toString() ?? 'Unknown FCM register error';
    throw Exception(message);
  }

  Future<void> _requestPermissionIfNeeded() async {
    if (kIsWeb || defaultTargetPlatform != TargetPlatform.android) {
      await _messaging.requestPermission(alert: true, badge: true, sound: true);
      return;
    }

    final androidInfo = await DeviceInfoPlugin().androidInfo;
    if (androidInfo.version.sdkInt < 33) {
      return;
    }

    final settings = await _messaging.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );

    if (settings.authorizationStatus == AuthorizationStatus.denied) {
      return;
    }

    await _localNotifications
        .resolvePlatformSpecificImplementation<
          AndroidFlutterLocalNotificationsPlugin
        >()
        ?.requestNotificationsPermission();
  }

  static Future<void> _showForegroundNotification(RemoteMessage message) async {
    final notification = message.notification;
    final android = notification?.android;

    if (kDebugMode) {
      debugPrint(
        '[PushNotificationService] foreground FCM received event=${message.data['event']} order_id=${message.data['order_id']} title=${notification?.title}',
      );
    }

    if (notification == null ||
        defaultTargetPlatform != TargetPlatform.android) {
      return;
    }

    await _localNotifications.show(
      id: notification.hashCode,
      title: notification.title,
      body: notification.body,
      notificationDetails: NotificationDetails(
        android: AndroidNotificationDetails(
          _androidChannel.id,
          _androidChannel.name,
          channelDescription: _androidChannel.description,
          importance: Importance.max,
          priority: Priority.max,
          playSound: true,
          enableVibration: true,
          vibrationPattern: _vibrationPattern,
          audioAttributesUsage: AudioAttributesUsage.notification,
          icon: android?.smallIcon,
        ),
      ),
      payload: message.data['order_id']?.toString(),
    );
    if (kDebugMode) {
      debugPrint(
        '[PushNotificationService] foreground local notification shown with sound channel=${_androidChannel.id}',
      );
    }
  }

  String get _platform {
    return switch (defaultTargetPlatform) {
      TargetPlatform.android => 'android',
      TargetPlatform.iOS => 'ios',
      TargetPlatform.macOS => 'macos',
      TargetPlatform.windows => 'windows',
      _ => 'web',
    };
  }
}
