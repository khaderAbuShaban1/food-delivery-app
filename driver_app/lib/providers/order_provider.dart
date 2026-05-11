import 'dart:async';

import 'package:flutter/foundation.dart';

import 'auth_provider.dart';
import '../models/driver_model.dart';
import '../models/order_model.dart';
import '../core/config/app_config.dart';
import '../services/api_client.dart';
import '../services/driver_realtime_sync_service.dart';

/// Full order data comes from Laravel/MySQL. Firestore only wakes the provider
/// when lightweight mirror documents change, then Laravel is refreshed once.
class OrderProvider extends ChangeNotifier {
  ApiClient? _api;
  DriverModel? _driver;
  StreamSubscription<List<Map<String, dynamic>>>? _availableOrdersSub;
  StreamSubscription<List<Map<String, dynamic>>>? _driverOrdersSub;
  Timer? _availableRefreshDebounce;
  Timer? _activeRefreshDebounce;
  DateTime? _lastFallbackRefreshAt;
  String? _availableMirrorSignature;
  String? _driverMirrorSignature;

  static const Duration _fallbackRefreshInterval = Duration(seconds: 60);
  static const Duration _firestoreRefreshDebounce = Duration(milliseconds: 700);

  List<OrderModel> availableOrders = [];
  OrderModel? activeOrder;
  bool isBusy = false;
  String? error;

  bool hasSyncedAvailableOrders = false;
  bool hasSyncedActiveOrder = false;

  bool get hasActiveOrder => activeOrder != null;

  int _deliveredTodayCount = 0;

  int get deliveredTodayCount => _deliveredTodayCount;

  Future<void> refreshFromServer({bool force = false}) async {
    if (!force && !_canRunFallbackRefresh()) return;
    _lastFallbackRefreshAt = DateTime.now();
    await _refreshAvailableFromApi();
    await _refreshActiveFromApi();
    await _refreshTodayStatsFromApi();
  }

  bool _canRunFallbackRefresh() {
    final last = _lastFallbackRefreshAt;
    if (last == null) return true;
    return DateTime.now().difference(last) >= _fallbackRefreshInterval;
  }

  void syncFromAuth(AuthProvider auth) {
    _api = auth.apiClient;
    setDriver(auth.driver);
  }

  Future<void> _refreshAvailableFromApi() async {
    final api = _api;
    if (api?.token == null) return;

    final res = await api!.get(DriverApiPaths.driverOrdersAvailablePool);
    if (res['success'] == true && res['data'] is List) {
      final list = res['data'] as List<dynamic>;
      availableOrders = list
          .map(
            (e) =>
                OrderModel.fromLaravelApi(Map<String, dynamic>.from(e as Map)),
          )
          .toList();
    } else if (res['success'] != true) {
      if (kDebugMode) {
        debugPrint('[OrderProvider] available-pool API: ${res['message']}');
      }
    }
    hasSyncedAvailableOrders = true;
    notifyListeners();
  }

  Future<void> _refreshActiveFromApi() async {
    final api = _api;
    if (api?.token == null) return;

    final res = await api!.get(DriverApiPaths.driverOrdersActive);
    if (res['success'] == true) {
      final data = res['data'];
      if (data == null) {
        activeOrder = null;
      } else if (data is Map) {
        activeOrder = OrderModel.fromLaravelApi(
          Map<String, dynamic>.from(data),
        );
      } else {
        activeOrder = null;
      }
    } else if (kDebugMode) {
      debugPrint('[OrderProvider] active API: ${res['message']}');
    }
    hasSyncedActiveOrder = true;
    notifyListeners();
  }

  Future<void> _refreshTodayStatsFromApi() async {
    final api = _api;
    if (api?.token == null) return;

    final res = await api!.get(DriverApiPaths.driverTodayStats);
    if (res['success'] == true && res['data'] is Map) {
      final data = Map<String, dynamic>.from(res['data'] as Map);
      final raw = data['delivered_today'];
      final v = raw is num ? raw.toInt() : int.tryParse(raw?.toString() ?? '');
      if (v != null) {
        _deliveredTodayCount = v;
        notifyListeners();
      }
    } else if (kDebugMode) {
      debugPrint('[OrderProvider] today stats API: ${res['message']}');
    }
  }

  void setDriver(DriverModel? driver) {
    if (_driver?.id == driver?.id) return;
    _driver = driver;
    _stopRealtimeListeners();
    availableOrders = [];
    activeOrder = null;
    hasSyncedAvailableOrders = false;
    hasSyncedActiveOrder = false;
    _deliveredTodayCount = 0;
    _availableMirrorSignature = null;
    _driverMirrorSignature = null;
    _lastFallbackRefreshAt = null;
    _availableRefreshDebounce?.cancel();
    _activeRefreshDebounce?.cancel();

    if (_driver != null) {
      unawaited(refreshFromServer(force: true));
      _startRealtimeListeners();
    }
    notifyListeners();
  }

  void _startRealtimeListeners() {
    final driver = _driver;
    if (driver == null) return;

    _availableOrdersSub?.cancel();
    _availableOrdersSub = DriverRealtimeSyncService.watchAvailableOrders().listen(
      (mirrors) {
        final signature = _mirrorSignature(mirrors);
        if (signature == _availableMirrorSignature) return;
        _availableMirrorSignature = signature;
        if (kDebugMode) {
          debugPrint(
            '[OrderProvider] available Firestore event count=${mirrors.length}',
          );
        }
        if (_driver == null || _api?.token == null) return;
        _scheduleAvailableRefresh();
      },
      onError: (error) {
        if (kDebugMode) {
          debugPrint(
            '[OrderProvider] available Firestore listener error: $error',
          );
        }
      },
    );

    _driverOrdersSub?.cancel();
    _driverOrdersSub = DriverRealtimeSyncService.watchDriverOrders(driver.id)
        .listen(
          (mirrors) {
            final signature = _mirrorSignature(mirrors);
            if (signature == _driverMirrorSignature) return;
            _driverMirrorSignature = signature;
            if (kDebugMode) {
              debugPrint(
                '[OrderProvider] driver Firestore event count=${mirrors.length}',
              );
            }
            if (_driver == null || _api?.token == null) return;
            _syncTodayStatsFromMirrors(mirrors);
            _syncActiveEmptyStateFromMirrors(mirrors);
            _scheduleActiveRefresh();
          },
          onError: (error) {
            if (kDebugMode) {
              debugPrint(
                '[OrderProvider] driver Firestore listener error: $error',
              );
            }
          },
        );
  }

  void _stopRealtimeListeners() {
    _availableOrdersSub?.cancel();
    _availableOrdersSub = null;
    _driverOrdersSub?.cancel();
    _driverOrdersSub = null;
    _availableRefreshDebounce?.cancel();
    _availableRefreshDebounce = null;
    _activeRefreshDebounce?.cancel();
    _activeRefreshDebounce = null;
  }

  void _scheduleAvailableRefresh() {
    _availableRefreshDebounce?.cancel();
    _availableRefreshDebounce = Timer(_firestoreRefreshDebounce, () {
      if (_driver == null || _api?.token == null) return;
      unawaited(_refreshAvailableFromApi());
    });
  }

  void _scheduleActiveRefresh() {
    _activeRefreshDebounce?.cancel();
    _activeRefreshDebounce = Timer(_firestoreRefreshDebounce, () {
      if (_driver == null || _api?.token == null) return;
      unawaited(_refreshActiveFromApi());
    });
  }

  String _mirrorSignature(List<Map<String, dynamic>> mirrors) {
    final parts = mirrors.map((mirror) {
      final id = mirror['id']?.toString() ?? '';
      final status = mirror['status']?.toString() ?? '';
      final driverId = mirror['driver_id']?.toString() ?? '';
      return '$id:$status:$driverId';
    }).toList()..sort();
    return parts.join('|');
  }

  bool _isDriverActiveStatus(String status) {
    final normalized = status.trim().toLowerCase().replaceAll('-', '_');
    return normalized == 'preparing' || normalized == 'on_the_way';
  }

  void _syncActiveEmptyStateFromMirrors(List<Map<String, dynamic>> mirrors) {
    final hasActiveMirror = mirrors.any(
      (mirror) => _isDriverActiveStatus(mirror['status']?.toString() ?? ''),
    );
    if (hasActiveMirror) return;
    if (activeOrder == null && hasSyncedActiveOrder) return;
    activeOrder = null;
    hasSyncedActiveOrder = true;
    notifyListeners();
  }

  void _syncTodayStatsFromMirrors(List<Map<String, dynamic>> mirrors) {
    final now = DateTime.now();
    final startOfDay = DateTime(now.year, now.month, now.day);
    final nextDay = startOfDay.add(const Duration(days: 1));
    final count = mirrors.where((mirror) {
      final status = mirror['status']
          ?.toString()
          .trim()
          .toLowerCase()
          .replaceAll('-', '_');
      if (status != 'delivered' && status != 'completed') return false;
      final updatedAt = DriverRealtimeSyncService.toDateTime(
        mirror['updated_at'],
      );
      if (updatedAt == null) return false;
      final local = updatedAt.toLocal();
      return !local.isBefore(startOfDay) && local.isBefore(nextDay);
    }).length;

    if (_deliveredTodayCount == count) return;
    _deliveredTodayCount = count;
    notifyListeners();
  }

  Future<void> acceptOrder(OrderModel order) async {
    if (_driver == null) return;

    if (activeOrder != null) {
      error = 'لديك طلب نشط. أكمل الطلب الحالي قبل قبول طلب آخر.';
      notifyListeners();
      return;
    }

    final api = _api;
    if (api?.token == null) {
      error = 'انتهت الجلسة. سجّل الدخول مجدداً.';
      notifyListeners();
      return;
    }

    isBusy = true;
    error = null;
    notifyListeners();

    final oid = int.tryParse(order.id.trim());
    if (oid == null) {
      error = 'معرّف الطلب غير صالح.';
      isBusy = false;
      notifyListeners();
      return;
    }
    final res = await api!.post(
      DriverApiPaths.driverAcceptOrder(oid),
      <String, dynamic>{},
    );
    if (res['success'] != true) {
      error = res['message']?.toString() ?? 'تعذّر قبول الطلب';
      isBusy = false;
      notifyListeners();
      return;
    }

    isBusy = false;
    await _refreshAvailableFromApi();
    await _refreshActiveFromApi();
    notifyListeners();
  }

  Future<void> updateStatus(String status) async {
    if (_driver == null || activeOrder == null) return;

    final api = _api;
    if (api?.token == null) {
      error = 'انتهت الجلسة. سجّل الدخول مجدداً.';
      notifyListeners();
      return;
    }

    isBusy = true;
    error = null;
    notifyListeners();

    final oid = int.tryParse(activeOrder!.id.trim());
    if (oid == null) {
      error = 'معرّف الطلب غير صالح.';
      isBusy = false;
      notifyListeners();
      return;
    }
    final res = await api!.put(
      DriverApiPaths.driverOrderStatus(oid),
      <String, dynamic>{'status': status},
    );
    if (res['success'] != true) {
      error = res['message']?.toString() ?? 'تعذّر تحديث الحالة';
    } else {
      final data = res['data'];
      if (data is Map) {
        activeOrder = OrderModel.fromLaravelApi(
          Map<String, dynamic>.from(data),
        );
      }
    }

    isBusy = false;
    await _refreshActiveFromApi();
    await _refreshAvailableFromApi();
    notifyListeners();
  }

  @override
  void dispose() {
    _stopRealtimeListeners();
    super.dispose();
  }
}
