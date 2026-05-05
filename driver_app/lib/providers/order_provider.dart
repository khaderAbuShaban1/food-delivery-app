import 'dart:async';

import 'package:flutter/foundation.dart';

import 'auth_provider.dart';
import '../models/driver_model.dart';
import '../models/order_model.dart';
import '../core/config/app_config.dart';
import '../services/api_client.dart';

/// All order data comes from **Laravel (MySQL)** via driver routes (`DriverApiPaths`). No Firestore.
/// Polling simulates near-real-time updates.
class OrderProvider extends ChangeNotifier {
  ApiClient? _api;
  DriverModel? _driver;
  Timer? _pollTimer;

  /// How often to re-fetch pool + active order while logged in.
  static const Duration pollInterval = Duration(seconds: 5);

  List<OrderModel> availableOrders = [];
  OrderModel? activeOrder;
  bool isBusy = false;
  String? error;

  bool hasSyncedAvailableOrders = false;
  bool hasSyncedActiveOrder = false;

  bool get hasActiveOrder => activeOrder != null;

  int _deliveredTodayCount = 0;

  int get deliveredTodayCount => _deliveredTodayCount;

  Future<void> refreshFromServer() async {
    await _refreshAvailableFromApi();
    await _refreshActiveFromApi();
    await _refreshTodayStatsFromApi();
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
          .map((e) => OrderModel.fromLaravelApi(Map<String, dynamic>.from(e as Map)))
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
        activeOrder = OrderModel.fromLaravelApi(Map<String, dynamic>.from(data));
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
    _stopPolling();
    availableOrders = [];
    activeOrder = null;
    hasSyncedAvailableOrders = false;
    hasSyncedActiveOrder = false;
    _deliveredTodayCount = 0;

    if (_driver != null) {
      unawaited(_refreshAvailableFromApi());
      unawaited(_refreshActiveFromApi());
      unawaited(_refreshTodayStatsFromApi());
      _startPolling();
    }
    notifyListeners();
  }

  void _startPolling() {
    _pollTimer?.cancel();
    _pollTimer = Timer.periodic(pollInterval, (_) {
      if (_driver == null || _api?.token == null) return;
      unawaited(_refreshAvailableFromApi());
      unawaited(_refreshActiveFromApi());
      unawaited(_refreshTodayStatsFromApi());
    });
  }

  void _stopPolling() {
    _pollTimer?.cancel();
    _pollTimer = null;
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
    final res = await api!.post(DriverApiPaths.driverAcceptOrder(oid), <String, dynamic>{});
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
        activeOrder = OrderModel.fromLaravelApi(Map<String, dynamic>.from(data));
      }
    }

    isBusy = false;
    await _refreshActiveFromApi();
    await _refreshAvailableFromApi();
    notifyListeners();
  }

  @override
  void dispose() {
    _stopPolling();
    super.dispose();
  }
}
