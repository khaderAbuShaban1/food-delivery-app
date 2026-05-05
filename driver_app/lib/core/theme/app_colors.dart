import 'package:flutter/material.dart';

/// Driver app palette — modern, delivery-app style (readable in RTL/LTR).
class AppColors {
  AppColors._();

  static const Color background = Color(0xFFF7F8FA);
  static const Color surface = Color(0xFFFFFFFF);
  static const Color surfaceMuted = Color(0xFFEEF2F6);
  static const Color borderSubtle = Color(0xFFE4E8EC);
  /// Incoming order emphasis (accent strip / chip).
  static const Color incomingGlow = Color(0xFFFFF3E8);

  /// Primary brand orange — buttons, tabs, workflow highlights.
  static const Color accent = Color(0xFFFF6D00);
  static const Color accentDark = Color(0xFFE65100);

  /// Text
  static const Color textPrimary = Color(0xFF1A1D21);
  static const Color textSecondary = Color(0xFF5C6570);
  static const Color textMuted = Color(0xFF8B95A3);

  static const Color pending = Color(0xFFF57C00);
  /// Restaurant / kitchen preparing (available pool)
  static const Color preparing = Color(0xFFEF6C00);
  static const Color accepted = Color(0xFF1565C0);
  static const Color pickedUp = Color(0xFF00838F);
  static const Color delivering = Color(0xFF7B1FA2);
  static const Color completed = Color(0xFF2E7D32);

  /// Status chip background tint
  static Color statusSurface(String normalized) {
    switch (normalized) {
      case 'pending':
        return const Color(0xFFFFF3E0);
      case 'preparing':
        return const Color(0xFFFFF8E1);
      case 'accepted':
        return const Color(0xFFE3F2FD);
      case 'picked_up':
        return const Color(0xFFE0F7FA);
      case 'delivering':
      case 'on_the_way':
        return const Color(0xFFFFF3E0);
      case 'completed':
      case 'delivered':
        return const Color(0xFFE8F5E9);
      default:
        return const Color(0xFFF1F3F5);
    }
  }

  static Color statusForeground(String normalized) {
    switch (normalized) {
      case 'pending':
        return pending;
      case 'preparing':
        return preparing;
      case 'accepted':
        return accepted;
      case 'picked_up':
        return pickedUp;
      case 'delivering':
      case 'on_the_way':
        return delivering;
      case 'completed':
      case 'delivered':
        return completed;
      default:
        return textSecondary;
    }
  }
}
