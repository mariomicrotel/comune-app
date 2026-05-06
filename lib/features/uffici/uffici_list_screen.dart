import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/constants/app_colors.dart';
import '../../core/constants/app_sizes.dart';
import '../../core/providers/core_providers.dart';
import '../../models/ufficio.dart';
import '../../widgets/loading_state.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/error_state.dart';
import 'uffici_notifier.dart';

class UfficiListScreen extends ConsumerWidget {
  const UfficiListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final colors = AppColors.resolve(theme.brightness, ref.watch(activePaletteProvider));
    final async = ref.watch(ufficiNotifierProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Uffici comunali')),
      body: async.when(
        loading: () => const LoadingState(),
        error: (e, _) => ErrorState(
          error: e,
          onRetry: () => ref.read(ufficiNotifierProvider.notifier).refresh(),
        ),
        data: (uffici) {
          if (uffici.isEmpty) {
            return const EmptyState(
              message: 'Nessun ufficio trovato.',
              icon: Icons.business_outlined,
            );
          }
          return ListView.separated(
            padding: const EdgeInsets.fromLTRB(AppSizes.padX, 16, AppSizes.padX, 100),
            itemCount: uffici.length,
            separatorBuilder: (_, __) => const SizedBox(height: 10),
            itemBuilder: (_, i) => _UfficioCard(u: uffici[i], colors: colors),
          );
        },
      ),
    );
  }
}

class _UfficioCard extends StatelessWidget {
  final Ufficio u;
  final AppColorTokens colors;

  const _UfficioCard({required this.u, required this.colors});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.all(AppSizes.cardPad),
      decoration: BoxDecoration(
        color: colors.bgElev,
        borderRadius: BorderRadius.circular(AppSizes.radiusMd),
        border: Border.all(color: colors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: AppSizes.iconXl,
                height: AppSizes.iconXl,
                decoration: BoxDecoration(
                  color: colors.primarySoft,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(Icons.business_outlined, color: colors.primary, size: 22),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(u.nome, style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
                    if (u.responsabile != null)
                      Text(u.responsabile!, style: theme.textTheme.bodySmall),
                  ],
                ),
              ),
            ],
          ),
          if (u.orari.isNotEmpty) ...[
            const SizedBox(height: 12),
            ...u.orari.entries.take(3).map((e) => Padding(
              padding: const EdgeInsets.only(bottom: 2),
              child: Text(
                '${_capitalize(e.key)}: ${e.value}',
                style: theme.textTheme.bodySmall,
              ),
            )),
          ],
          const SizedBox(height: 12),
          Row(
            children: [
              if (u.tel != null)
                Expanded(
                  child: _ActionButton(
                    label: 'Chiama',
                    icon: Icons.phone_outlined,
                    onTap: () => launchUrl(Uri.parse('tel:${u.tel}')),
                    outlined: true,
                    colors: colors,
                  ),
                ),
              if (u.tel != null && u.email != null) const SizedBox(width: 10),
              if (u.email != null)
                Expanded(
                  child: _ActionButton(
                    label: 'Email',
                    icon: Icons.email_outlined,
                    onTap: () => launchUrl(Uri.parse('mailto:${u.email}')),
                    outlined: false,
                    colors: colors,
                  ),
                ),
            ],
          ),
        ],
      ),
    );
  }

  String _capitalize(String s) => s.isEmpty ? s : '${s[0].toUpperCase()}${s.substring(1)}';
}

class _ActionButton extends StatelessWidget {
  final String label;
  final IconData icon;
  final VoidCallback onTap;
  final bool outlined;
  final AppColorTokens colors;

  const _ActionButton({
    required this.label,
    required this.icon,
    required this.onTap,
    required this.outlined,
    required this.colors,
  });

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 40,
      child: outlined
          ? OutlinedButton.icon(
              onPressed: onTap,
              icon: Icon(icon, size: 16),
              label: Text(label),
              style: OutlinedButton.styleFrom(
                minimumSize: Size.zero,
                padding: const EdgeInsets.symmetric(horizontal: 12),
              ),
            )
          : TextButton.icon(
              onPressed: onTap,
              icon: Icon(icon, size: 16),
              label: Text(label),
              style: TextButton.styleFrom(
                minimumSize: Size.zero,
                padding: const EdgeInsets.symmetric(horizontal: 12),
              ),
            ),
    );
  }
}
