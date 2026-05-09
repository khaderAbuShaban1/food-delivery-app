import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/foundation.dart';

import '../models/address.dart';
import '../models/restaurant.dart';
import 'auth_service.dart';

/// Listens to Firestore mirror collections for live UI updates.
///
/// Firestore is only a lightweight mirror; Laravel + MySQL remain the source of truth.
class RealtimeSyncService {
  static bool get _isReady => Firebase.apps.isNotEmpty;

  static FirebaseFirestore get _db => FirebaseFirestore.instance;

  static String? currentUserId() {
    final id = AuthService.currentUser?['id'];
    if (id == null) return null;
    final value = id.toString();
    return value.isEmpty ? null : value;
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

  static Stream<List<Map<String, dynamic>>> watchCustomerOrders(String userId) {
    if (!_isReady) return Stream.value(const <Map<String, dynamic>>[]);
    if (kDebugMode) {
      debugPrint('[RealtimeSync] watchCustomerOrders userId=$userId');
    }
    final parsedId = int.tryParse(userId);
    final query = parsedId == null
        ? _db.collection('orders').where('customer_id', isEqualTo: userId)
        : _db.collection('orders').where('customer_id', isEqualTo: parsedId);

    return query.snapshots().map((snapshot) {
      if (kDebugMode) {
        debugPrint(
          '[RealtimeSync] customer orders snapshot docs=${snapshot.docs.length}',
        );
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
    });
  }

  static Stream<Map<String, dynamic>?> watchOrder(String orderId) {
    if (!_isReady) return Stream.value(null);
    if (kDebugMode) {
      debugPrint('[RealtimeSync] watchOrder orderId=$orderId');
    }
    return _db.collection('orders').doc(orderId).snapshots().map((doc) {
      if (kDebugMode) {
        debugPrint(
          '[RealtimeSync] order document snapshot id=$orderId exists=${doc.exists}',
        );
      }
      if (!doc.exists || doc.data() == null) return null;
      final data = Map<String, dynamic>.from(doc.data()!);
      data['id'] ??= int.tryParse(doc.id) ?? doc.id;
      return data;
    });
  }

  static Stream<List<Address>> watchAddresses(String userId) {
    if (!_isReady) return Stream.value(const <Address>[]);
    if (kDebugMode) {
      debugPrint('[RealtimeSync] watchAddresses userId=$userId');
    }
    return _db
        .collection('addresses')
        .where('user_id', isEqualTo: userId)
        .snapshots()
        .map((snapshot) {
          final docs = [...snapshot.docs];
          docs.sort((a, b) {
            final aMillis = _toEpochMillis(a.data()['updated_at']);
            final bMillis = _toEpochMillis(b.data()['updated_at']);
            return bMillis.compareTo(aMillis);
          });

          return docs
              .map((doc) {
                final data = doc.data();
                return Address(
                  id: int.tryParse(doc.id) ?? (data['id'] as int? ?? 0),
                  title: (data['title'] ?? '').toString(),
                  city: (data['city'] ?? '').toString(),
                  street: (data['street'] ?? '').toString(),
                  details: data['details']?.toString(),
                  isDefault: data['is_default'] == true,
                );
              })
              .where((a) => a.id > 0)
              .toList();
        });
  }

  static Future<void> syncAddresses(
    String userId,
    List<Address> addresses,
  ) async {
    return;
  }

  static Future<void> upsertAddress(String userId, Address address) async {
    return;
  }

  static Future<void> removeAddress(int addressId) async {
    return;
  }

  static Stream<Restaurant?> watchRestaurant(String restaurantId) {
    if (!_isReady) return Stream.value(null);
    if (kDebugMode) {
      debugPrint('[RealtimeSync] watchRestaurant restaurantId=$restaurantId');
    }
    return _db.collection('restaurants').doc(restaurantId).snapshots().map((
      doc,
    ) {
      if (!doc.exists || doc.data() == null) return null;
      return Restaurant.fromJson(doc.data()!);
    });
  }

  static Stream<List<Restaurant>> watchRestaurants() {
    if (!_isReady) return Stream.value(const <Restaurant>[]);
    if (kDebugMode) {
      debugPrint('[RealtimeSync] watchRestaurants');
    }
    return _db.collection('restaurants').snapshots().map((snapshot) {
      if (kDebugMode) {
        debugPrint(
          '[RealtimeSync] restaurants snapshot docs=${snapshot.docs.length}',
        );
      }
      final docs = [...snapshot.docs];
      docs.sort((a, b) {
        final aData = a.data();
        final bData = b.data();
        final aMillis = _toEpochMillis(aData['updated_at']) > 0
            ? _toEpochMillis(aData['updated_at'])
            : _toEpochMillis(aData['created_at']);
        final bMillis = _toEpochMillis(bData['updated_at']) > 0
            ? _toEpochMillis(bData['updated_at'])
            : _toEpochMillis(bData['created_at']);
        return bMillis.compareTo(aMillis);
      });

      final restaurants = <Restaurant>[];
      for (final doc in docs) {
        final data = Map<String, dynamic>.from(doc.data());
        data['id'] ??= int.tryParse(doc.id) ?? doc.id;
        try {
          restaurants.add(Restaurant.fromJson(data));
        } catch (_) {
          // Ignore malformed docs and keep stream alive for valid updates.
        }
      }
      return restaurants;
    });
  }

  static Future<void> syncRestaurants(List<Restaurant> restaurants) async {
    return;
  }

  static Future<void> syncRestaurant(Restaurant restaurant) async {
    return;
  }
}
