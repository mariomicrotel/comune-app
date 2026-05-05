import 'package:flutter/material.dart';
import '../core/constants/app_sizes.dart';

class AppCard extends StatelessWidget {
  final Widget child;
  final VoidCallback? onTap;
  final EdgeInsetsGeometry? padding;
  final double? borderRadius;

  const AppCard({
    super.key,
    required this.child,
    this.onTap,
    this.padding,
    this.borderRadius,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final radius = borderRadius ?? AppSizes.radiusMd;

    return Material(
      color: theme.colorScheme.surface,
      borderRadius: BorderRadius.circular(radius),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(radius),
        child: Container(
          decoration: BoxDecoration(
            border: Border.all(color: theme.colorScheme.outline),
            borderRadius: BorderRadius.circular(radius),
          ),
          padding: padding ?? const EdgeInsets.all(AppSizes.cardPad),
          child: child,
        ),
      ),
    );
  }
}
