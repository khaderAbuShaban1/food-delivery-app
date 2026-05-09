import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:provider/provider.dart';
import 'features/auth/screens/login_screen.dart';
import 'features/auth/screens/register_screen.dart';
import 'features/home/main_screen.dart';
import 'features/profile/my_addresses_screen.dart';
import 'core/theme/app_theme.dart';
import 'core/api/api_client.dart';
import 'core/services/cart_provider.dart';
import 'firebase_options.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    ApiClient.setBaseUrl('10.0.0.11');

    return MultiProvider(
      providers: [ChangeNotifierProvider(create: (_) => CartProvider())],
      child: MaterialApp(
        title: 'توصيل الطعام',
        debugShowCheckedModeBanner: false,
        locale: const Locale('ar', 'SA'),
        supportedLocales: const [Locale('ar', 'SA')],
        localizationsDelegates: const [
          GlobalMaterialLocalizations.delegate,
          GlobalWidgetsLocalizations.delegate,
          GlobalCupertinoLocalizations.delegate,
        ],
        builder: (context, child) {
          return Directionality(
            textDirection: TextDirection.rtl,
            child: child ?? const SizedBox.shrink(),
          );
        },
        theme: AppTheme.lightTheme,
        home: const LoginScreen(),
        routes: {
          '/register': (context) => const RegisterScreen(),
          '/main': (context) => const MainScreen(),
          '/addresses': (context) => const MyAddressesScreen(),
        },
      ),
    );
  }
}
