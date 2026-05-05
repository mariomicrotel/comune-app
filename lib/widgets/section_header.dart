import 'package:flutter/material.dart';
import '../core/constants/app_sizes.dart';

class SectionHeader extends StatelessWidget {
  final String title;
  final String? actionLabel;
  final VoidCallback? onAction;

  const SectionHeader({
    super.key,
    required this.title,
    this.actionLabel,
    this.onAction,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.fromLTRB(AppSizes.padX, AppSizes.spacingLg, AppSizes.padX, AppSizes.spacingSm),
      child: Row(
        children: [
          Expanded(
            child: Text(title, style: theme.textTheme.titleLarge),
          ),
          if (actionLabel != null && onAction != null)
            TextButton(
              onPressed: onAction,
              child: Text(actionLabel!),
            ),
        ],
      ),
    );
  }
}
