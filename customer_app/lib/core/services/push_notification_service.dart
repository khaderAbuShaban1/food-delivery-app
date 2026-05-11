import 'package:flutter/foundation.dart';
import 'package:device_info_plus/device_info_plus.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

import '../api/api_client.dart';

class PushNotificationService {
  PushNotificationService._();

  static const AndroidNotificationChannel _androidChannel =
      AndroidNotificationChannel(
        'food_delivery_orders_silent',
        'Order updates (silent)',
        description: 'Visual order and delivery notifications without sound',
        importance: Importance.low,
        playSound: false,
        enableVibration: false,
      );

  static final FirebaseMessaging _messaging = FirebaseMessaging.instance;
  static final FlutterLocalNotificationsPlugin _localNotifications =
      FlutterLocalNotificationsPlugin();

  static bool _initialized = false;
  static bool _refreshListenerAttached = false;
  static bool _foregroundListenerAttached = false;

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

    await _messaging.setForegroundNotificationPresentationOptions(
      alert: true,
      badge: true,
      sound: false,
    );

    if (!_foregroundListenerAttached) {
      _foregroundListenerAttached = true;
      FirebaseMessaging.onMessage.listen(_showForegroundNotification);
    }
  }

  static Future<void> registerDeviceToken() async {
    if (ApiClient.token == null) return;

    try {
      await initialize();
      await _requestPermissionIfNeeded();

      final token = await _messaging.getToken();
      if (token == null || token.isEmpty) return;

      await _sendToken(token);

      if (!_refreshListenerAttached) {
        _refreshListenerAttached = true;
        _messaging.onTokenRefresh.listen((newToken) {
          if (ApiClient.token != null) {
            _sendToken(newToken);
          }
        });
      }
    } catch (_) {
      // Push registration must never block login or checkout.
    }
  }

  static Future<void> unregisterDeviceToken() async {
    if (ApiClient.token == null) return;

    try {
      final token = await _messaging.getToken();
      if (token == null || token.isEmpty) return;

      await ApiClient.delete('/fcm-token', data: {'token': token});
    } catch (_) {
      // Logout should continue even if the token cannot be removed.
    }
  }

  static Future<void> _sendToken(String token) async {
    await ApiClient.post('/fcm-token', {'token': token, 'platform': _platform});
  }

  static Future<void> _requestPermissionIfNeeded() async {
    if (kIsWeb || defaultTargetPlatform != TargetPlatform.android) {
      await _messaging.requestPermission(
        alert: true,
        badge: true,
        sound: false,
      );
      return;
    }

    final androidInfo = await DeviceInfoPlugin().androidInfo;
    if (androidInfo.version.sdkInt < 33) {
      return;
    }

    final settings = await _messaging.requestPermission(
      alert: true,
      badge: true,
      sound: false,
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
          importance: Importance.low,
          priority: Priority.low,
          playSound: false,
          enableVibration: false,
          silent: true,
          icon: android?.smallIcon,
        ),
      ),
      payload: message.data['order_id']?.toString(),
    );
  }

  static String get _platform {
    return switch (defaultTargetPlatform) {
      TargetPlatform.android => 'android',
      TargetPlatform.iOS => 'ios',
      TargetPlatform.macOS => 'macos',
      TargetPlatform.windows => 'windows',
      _ => 'web',
    };
  }
}
