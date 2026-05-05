import 'package:flutter/material.dart';
import '../core/constants/app_colors.dart';
import '../core/constants/app_sizes.dart';

/// Full-screen error page (used outside Scaffold contexts like the router error builder).
class ErrorScreen extends StatelessWidget {
  final Object? error;
  final VoidCallback? onRetry;

  const ErrorScreen({super.key, this.error, this.onRetry});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final colors = isDark ? AppColors.dark : AppColors.light[AppPalette.bluCivico]!;

    return Scaffold(
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(40),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 80,
                height: 80,
                decoration: BoxDecoration(
                  color: colors.dangerSoft,
                  shape: BoxShape.circle,
                ),
                child: Icon(Icons.wifi_off_rounded,
                    size: 40, color: colors.danger),
              ),
              const SizedBox(height: 24),
              Text(
                'Qualcosa è andato storto',
                style: theme.textTheme.headlineSmall,
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 8),
              Text(
                error?.toString() ?? 'Errore sconosciuto',
                style: theme.textTheme.bodyMedium
                    ?.copyWith(color: colors.textMuted),
                textAlign: TextAlign.center,
                maxLines: 3,
                overflow: TextOverflow.ellipsis,
              ),
              if (onRetry != null) ...[
                const SizedBox(height: 28),
                SizedBox(
                  width: 200,
                  height: AppSizes.minTapTarget,
                  child: ElevatedButton.icon(
                    onPressed: onRetry,
                    icon: const Icon(Icons.refresh_rounded, size: 18),
                    label: const Text('Riprova'),
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
