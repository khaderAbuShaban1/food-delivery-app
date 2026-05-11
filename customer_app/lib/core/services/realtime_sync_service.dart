import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_core/firebase_core.dart';

import '../models/address.dart';
import '../models/restaurant.dart';
import 'auth_service.dart';

/// Mirrors **addresses** and **restaurants** to Firestore for local listeners (live UI polish).
///
/// Order data **must never** live in Firestore; use [ApiClient] + Laravel `/orders` endpoints only.
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

  static Stream<List<Address>> watchAddresses(String userId) {
    if (!_isReady) return Stream.value(<Address>[]);
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

  static Future<void> syncAddresses(String userId, List<Address> addresses) async {
    if (!_isReady) return;
    final batch = _db.batch();
    for (final address in addresses) {
      final ref = _db.collection('addresses').doc(address.id.toString());
      batch.set(ref, {
        'id': address.id,
        'user_id': userId,
        'title': address.title,
        'city': address.city,
        'street': address.street,
        'details': address.details,
        'is_default': address.isDefault,
        'updated_at': FieldValue.serverTimestamp(),
      }, SetOptions(merge: true));
    }
    await batch.commit();
  }

  static Future<void> upsertAddress(String userId, Address address) async {
    if (!_isReady) return;
    await _db.collection('addresses').doc(address.id.toString()).set({
      'id': address.id,
      'user_id': userId,
      'title': address.title,
      'city': address.city,
      'street': address.street,
      'details': address.details,
      'is_default': address.isDefault,
      'updated_at': FieldValue.serverTimestamp(),
    }, SetOptions(merge: true));
  }

  static Future<void> removeAddress(int addressId) async {
    if (!_isReady) return;
    await _db.collection('addresses').doc(addressId.toString()).delete();
  }

  static Stream<Restaurant?> watchRestaurant(String restaurantId) {
    if (!_isReady) return Stream.value(null);
    return _db.collection('restaurants').doc(restaurantId).snapshots().map((
      doc,
    ) {
      if (!doc.exists || doc.data() == null) return null;
      return Restaurant.fromJson(doc.data()!);
    });
  }

  static Stream<List<Restaurant>> watchRestaurants() {
    if (!_isReady) return Stream.value(<Restaurant>[]);
    return _db
        .collection('restaurants')
        .snapshots()
        .map((snapshot) {
          final docs = [...snapshot.docs];
          docs.sort((a, b) {
            final aData = a.data();
            final bData = b.data();
            final aMillis =
                _toEpochMillis(aData['updated_at']) > 0
                    ? _toEpochMillis(aData['updated_at'])
                    : _toEpochMillis(aData['created_at']);
            final bMillis =
                _toEpochMillis(bData['updated_at']) > 0
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
    if (!_isReady) return;
    final batch = _db.batch();
    for (final restaurant in restaurants) {
      final ref = _db.collection('restaurants').doc(restaurant.id.toString());
      batch.set(ref, {
        'id': restaurant.id,
        'name': restaurant.name,
        'image': restaurant.image,
        'category': restaurant.category,
        'is_open': restaurant.isOpen,
        'email': restaurant.email,
        'phone': restaurant.phone,
        'rating': restaurant.rating,
        'ratings_count': restaurant.ratingsCount,
        'my_rating': restaurant.myRating,
        'updated_at': FieldValue.serverTimestamp(),
      }, SetOptions(merge: true));
    }
    await batch.commit();
  }

  static Future<void> syncRestaurant(Restaurant restaurant) async {
    if (!_isReady) return;
    await _db.collection('restaurants').doc(restaurant.id.toString()).set({
      'id': restaurant.id,
      'name': restaurant.name,
      'image': restaurant.image,
      'category': restaurant.category,
      'is_open': restaurant.isOpen,
      'email': restaurant.email,
      'phone': restaurant.phone,
      'rating': restaurant.rating,
      'ratings_count': restaurant.ratingsCount,
      'my_rating': restaurant.myRating,
      'updated_at': FieldValue.serverTimestamp(),
    }, SetOptions(merge: true));
  }
}



