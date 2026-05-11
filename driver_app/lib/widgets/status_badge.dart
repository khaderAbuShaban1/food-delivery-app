import 'package:flutter/material.dart';

import '../core/theme/app_colors.dart';

/// Status label + colors aligned with ops (pending → completed).
class StatusBadge extends StatelessWidget {
  final String status;
  /// Laravel `status_label` (e.g. Arabic). When null, Arabic map from [status] is used.
  final String? labelOverride;

  const StatusBadge({super.key, required this.status, this.labelOverride});

  static String normalize(String raw) {
    final s = raw.trim().toLowerCase();
    if (s.isEmpty) return 'pending';
    final normalized = s.replaceAll(' ', '_');
    switch (normalized) {
      case 'preparing':
        return 'accepted';
      case 'on_the_way':
        return 'delivering';
      case 'delivered':
        return 'completed';
      default:
        return normalized;
    }
  }

  static String labelAr(String normalized) {
    switch (normalized) {
      case 'pending':
        return 'قيد الانتظار';
      case 'preparing':
        return 'قيد التحضير';
      case 'accepted':
        return 'مقبول';
      case 'picked_up':
        return 'تم الاستلام';
      case 'delivering':
      case 'on_the_way':
        return 'قيد التوصيل';
      case 'completed':
      case 'delivered':
        return 'مكتمل';
      default:
        return normalized;
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final normalized = normalize(status);
    final baseFg = AppColors.statusForeground(normalized);
    final bg = isDark
        ? baseFg.withValues(alpha: 0.16)
        : AppColors.statusSurface(normalized);
    final fg = isDark ? baseFg : AppColors.statusForeground(normalized);

    final o = labelOverride?.trim();
    final labelText = (o != null && o.isNotEmpty) ? o : labelAr(normalized);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(
          color: isDark ? baseFg.withValues(alpha: 0.42) : Colors.transparent,
        ),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 6,
            height: 6,
            decoration: BoxDecoration(
              color: fg,
              shape: BoxShape.circle,
            ),
          ),
          const SizedBox(width: 8),
          Text(
            labelText,
            style: TextStyle(
              color: fg,
              fontWeight: FontWeight.w800,
              fontSize: 12,
              letterSpacing: 0.2,
            ),
          ),
        ],
      ),
    );
  }
}
