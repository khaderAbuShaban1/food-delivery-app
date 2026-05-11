import 'package:flutter/material.dart';

class AppColors {
  static const Color primary = Color(0xFFFF6D00);
  static const Color primaryLight = Color(0xFFFF8A38);
  static const Color primaryDark = Color(0xFFE65100);
  static const Color secondary = Color(0xFFFFF3E8);
  static const Color background = Color(0xFFF7F8FA);
  static const Color surface = Color(0xFFFFFFFF);
  static const Color accent = Color(0xFF1A1A1A);
  static const Color textPrimary = Color(0xFF1A1A1A);
  static const Color textSecondary = Color(0xFF6B6B6B);
  static const Color textHint = Color(0xFFAAAAAA);
  static const Color success = Color(0xFF2ECC71);
  static const Color error = Color(0xFFE74C3C);
  static const Color warning = Color(0xFFF39C12);
  static const Color divider = Color(0xFFEEEEEE);
  static const Color border = Color(0xFFE5E5E5);
  static const Color shadow = Color(0x05000000);
  static const Color open = Color(0xFF2ECC71);
  static const Color closed = Color(0xFFE74C3C);
}

class AppTheme {
  static const Color _lightBackground = Color(0xFFF7F8FA);
  static const Color _lightSurface = Color(0xFFFFFFFF);
  static const Color _lightSecondary = Color(0xFFFFF3E8);
  static const Color _darkBackground = Color(0xFF121212);
  static const Color _darkSurface = Color(0xFF1E1E1E);
  static const Color _darkSecondary = Color(0xFF2A211A);

  static ThemeData get lightTheme => _buildTheme(
    brightness: Brightness.light,
    background: _lightBackground,
    surface: _lightSurface,
    secondary: _lightSecondary,
    onSurface: const Color(0xFF1A1A1A),
    onSurfaceVariant: const Color(0xFF6B6B6B),
    outline: const Color(0xFFE5E5E5),
  );

  static ThemeData get darkTheme => _buildTheme(
    brightness: Brightness.dark,
    background: _darkBackground,
    surface: _darkSurface,
    secondary: _darkSecondary,
    onSurface: const Color(0xFFEDEDED),
    onSurfaceVariant: const Color(0xFFB0B0B0),
    outline: const Color(0xFF3A3A3A),
  );

  static ThemeData _buildTheme({
    required Brightness brightness,
    required Color background,
    required Color surface,
    required Color secondary,
    required Color onSurface,
    required Color onSurfaceVariant,
    required Color outline,
  }) {
    final scheme = ColorScheme.fromSeed(
      seedColor: AppColors.primary,
      brightness: brightness,
      primary: AppColors.primary,
      surface: surface,
      secondary: secondary,
      error: AppColors.error,
    ).copyWith(
      onSurface: onSurface,
      onSurfaceVariant: onSurfaceVariant,
      outline: outline,
    );

    return ThemeData(
      useMaterial3: true,
      brightness: brightness,
      colorScheme: scheme,
      scaffoldBackgroundColor: background,
      fontFamily: 'Cairo',
      appBarTheme: AppBarTheme(
        elevation: 0,
        centerTitle: false,
        backgroundColor: background,
        foregroundColor: onSurface,
      ),
      cardTheme: CardThemeData(
        elevation: 0,
        color: surface,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      ),
      dividerTheme: DividerThemeData(color: outline),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: surface,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: outline),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: outline),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: scheme.primary, width: 2),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.primary,
          foregroundColor: Colors.white,
        ),
      ),
      bottomNavigationBarTheme: BottomNavigationBarThemeData(
        backgroundColor: surface,
        selectedItemColor: AppColors.primary,
        unselectedItemColor: onSurfaceVariant,
        type: BottomNavigationBarType.fixed,
      ),
    );
  }
}

class AppSpacing {
  static const double xs = 4;
  static const double sm = 8;
  static const double md = 16;
  static const double lg = 24;
  static const double xl = 32;
  static const double xxl = 48;
  static const double xxxl = 64;
  static const double huge = 96;
}

class AppRadius {
  static const double sm = 8;
  static const double md = 12;
  static const double lg = 16;
  static const double xl = 20;
  static const double xxl = 24;
  static const double pill = 100;
}



