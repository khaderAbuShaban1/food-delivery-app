// File generated for the food delivery Firebase project.
// ignore_for_file: type=lint
import 'package:firebase_core/firebase_core.dart' show FirebaseOptions;
import 'package:flutter/foundation.dart'
    show TargetPlatform, defaultTargetPlatform, kIsWeb;

class DefaultFirebaseOptions {
  static FirebaseOptions get currentPlatform {
    if (kIsWeb) {
      throw UnsupportedError(
        'DefaultFirebaseOptions have not been configured for web.',
      );
    }

    switch (defaultTargetPlatform) {
      case TargetPlatform.android:
        return android;
      case TargetPlatform.iOS:
        return ios;
      case TargetPlatform.macOS:
      case TargetPlatform.windows:
      case TargetPlatform.linux:
        throw UnsupportedError(
          'DefaultFirebaseOptions have not been configured for this platform.',
        );
      default:
        throw UnsupportedError('Unsupported Firebase platform.');
    }
  }

  static const FirebaseOptions android = FirebaseOptions(
    apiKey: 'AIzaSyBgAqN4YO1qVnp6_YTMbjdPzk5-Hh6apg4',
    appId: '1:885023315229:android:61eb24ac6482af2cd77df4',
    messagingSenderId: '885023315229',
    projectId: 'food-delivery-app-7ee8d',
    storageBucket: 'food-delivery-app-7ee8d.firebasestorage.app',
  );

  static const FirebaseOptions ios = FirebaseOptions(
    apiKey: 'AIzaSyBLRLiR3KCo0yv0NzFZDZZC7EXq2-EW1eo',
    appId: '1:885023315229:ios:7f1a04b634f8ef3bd77df4',
    messagingSenderId: '885023315229',
    projectId: 'food-delivery-app-7ee8d',
    storageBucket: 'food-delivery-app-7ee8d.firebasestorage.app',
    iosBundleId: 'com.fooddelivery.driverApp',
  );
}
