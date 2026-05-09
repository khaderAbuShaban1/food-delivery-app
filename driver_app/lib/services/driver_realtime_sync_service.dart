import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/foundation.dart';

class DriverRealtimeSyncService {
  static bool get _isReady => Firebase.apps.isNotEmpty;

  static FirebaseFirestore get _db => FirebaseFirestore.instance;

  static Stream<List<Map<String, dynamic>>> watchAvailableOrders() {
    if (!_isReady) return Stream.value(const <Map<String, dynamic>>[]);
    if (kDebugMode) {
      debugPrint('[DriverRealtimeSync] watchAvailableOrders');
    }
    return _db
        .collection('orders')
        .where('status', isEqualTo: 'preparing')
        .where('driver_id', isEqualTo: null)
        .snapshots()
        .map(_snapshotToList);
  }

  static Stream<List<Map<String, dynamic>>> watchDriverOrders(int driverId) {
    if (!_isReady) return Stream.value(const <Map<String, dynamic>>[]);
    if (kDebugMode) {
      debugPrint('[DriverRealtimeSync] watchDriverOrders driverId=$driverId');
    }
    return _db
        .collection('orders')
        .where('driver_id', isEqualTo: driverId)
        .snapshots()
        .map(_snapshotToList);
  }

  static List<Map<String, dynamic>> _snapshotToList(
    QuerySnapshot<Map<String, dynamic>> snapshot,
  ) {
    if (kDebugMode) {
      debugPrint('[DriverRealtimeSync] snapshot docs=${snapshot.docs.length}');
    }
    final docs = [...snapshot.docs];
    docs.sort((a, b) {
      final aMillis = _toEpochMillis(a.data()['updated_at']);
      final bMillis = _toEpochMillis(b.data()['updated_at']);
      return bMillis.compareTo(aMillis);
    });

    return docs.map((doc) {
      final data = Map<String, dynamic>.from(doc.data());
      data['id'] ??= int.tryParse(doc.id) ?? doc.id;
      return data;
    }).toList();
  }

  static int _toEpochMillis(dynamic value) {
    if (value == null) return 0;
    if (value is Timestamp) return value.toDate().millisecondsSinceEpoch;
    if (value is DateTime) return value.millisecondsSinceEpoch;
    if (value is String) {
      final parsed = DateTime.tryParse(value);
      if (parsed != null) return parsed.millisecondsSinceEpoch;
    }
    if (value is int) return value;
    return 0;
  }
}
