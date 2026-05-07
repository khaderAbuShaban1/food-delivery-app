class AppConfig {
  // Android emulator uses 10.0.2.2, but real devices must use your PC LAN IP.
  // Update this value when your network IP changes.
  static const String defaultApiHost = '10.0.0.11';
  static String apiBaseUrl = 'http://$defaultApiHost:8000/api';

  static void setApiHost(String host) {
    apiBaseUrl = 'http://$host:8000/api';
  }
}

/// Laravel API paths (single source of truth: MySQL via these endpoints).
///
/// Customers use [ordersIndex] (`GET /api/orders` with a user token).
/// The driver app logs in via `POST /api/driver/login` and uses [driverOrdersAvailablePool] /
/// [driverOrdersActive]; responses use `OrderController::formatOrder` (same MySQL data as phpMyAdmin).
class DriverApiPaths {
  DriverApiPaths._();

  /// Customer-scoped list — not used by the driver app (Sanctum `User`, not `Driver`).
  static const String ordersIndex = '/orders';

  static const String driverOrdersAvailablePool = '/driver/orders/available-pool';
  static const String driverOrdersActive = '/driver/orders/active';
  static const String driverOrdersHistory = '/driver/orders/history';
  static const String driverTodayStats = '/driver/stats/today';

  static String driverAcceptOrder(int id) => '/driver/orders/$id/accept';
  static String driverOrderStatus(int id) => '/driver/orders/$id/status';
}

