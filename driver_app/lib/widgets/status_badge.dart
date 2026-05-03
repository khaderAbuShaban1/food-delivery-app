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
    return s.replaceAll(' ', '_');
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
        return 'قيد التوصيل';
      case 'completed':
        return 'مكتمل';
      default:
        return normalized;
    }
  }

  @override
  Widget build(BuildContext context) {
    final normalized = normalize(status);
    final bg = AppColors.statusSurface(normalized);
    final fg = AppColors.statusForeground(normalized);

    final o = labelOverride?.trim();
    final labelText = (o != null && o.isNotEmpty) ? o : labelAr(normalized);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(999),
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
