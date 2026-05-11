import 'dart:math' as math;

import 'package:flutter/material.dart';

/// Shimmer-style skeleton placeholders for order cards / detail rows.
class OrderListSkeleton extends StatefulWidget {
  final int rowCount;

  const OrderListSkeleton({super.key, this.rowCount = 4});

  @override
  State<OrderListSkeleton> createState() => _OrderListSkeletonState();
}

class _OrderListSkeletonState extends State<OrderListSkeleton> with SingleTickerProviderStateMixin {
  late AnimationController _ctrl;

  @override
  void initState() {
    super.initState();
    _ctrl = AnimationController(vsync: this, duration: const Duration(milliseconds: 1300))
      ..repeat();
  }

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _ctrl,
      builder: (context, _) {
        final t = (math.sin(_ctrl.value * math.pi * 2) + 1) / 2;
        final fade = Color.lerp(
          Theme.of(context).dividerColor,
          Theme.of(context).colorScheme.surfaceContainerHighest,
          0.45 + t * 0.35,
        )!;
        return ListView.builder(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 96),
          itemCount: widget.rowCount,
          itemBuilder: (_, i) => Padding(
            padding: const EdgeInsets.only(bottom: 14),
            child: _SkeletonCard(fill: fade, index: i),
          ),
        );
      },
    );
  }
}

class _SkeletonCard extends StatelessWidget {
  final Color fill;
  final int index;

  const _SkeletonCard({required this.fill, required this.index});

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 16,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  height: 20,
                  width: 140 + (index % 3) * 18,
                  decoration: BoxDecoration(color: fill, borderRadius: BorderRadius.circular(8)),
                ),
                const Spacer(),
                Container(
                  height: 28,
                  width: 92,
                  decoration: BoxDecoration(color: fill, borderRadius: BorderRadius.circular(999)),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Container(
              height: 14,
              width: double.infinity,
              decoration: BoxDecoration(color: fill, borderRadius: BorderRadius.circular(6)),
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                Expanded(
                  child: Container(
                    height: 14,
                    decoration: BoxDecoration(color: fill, borderRadius: BorderRadius.circular(6)),
                  ),
                ),
                const SizedBox(width: 14),
                Container(
                  width: 96,
                  height: 34,
                  decoration: BoxDecoration(color: fill, borderRadius: BorderRadius.circular(12)),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

/// Spinner fallback for constrained spaces.
class InlineLoadingPulse extends StatelessWidget {
  final String message;

  const InlineLoadingPulse({super.key, this.message = ''});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          SizedBox(
            width: 36,
            height: 36,
            child: CircularProgressIndicator(
              strokeWidth: 3,
              color: Theme.of(context).colorScheme.primary,
            ),
          ),
          if (message.isNotEmpty) ...[
            const SizedBox(height: 16),
            Text(
              message,
              style: Theme.of(context).textTheme.bodyMedium,
              textAlign: TextAlign.center,
            ),
          ],
        ],
      ),
    );
  }
}
