import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/constants/app_colors.dart';
import '../../core/constants/app_sizes.dart';
import '../../core/providers/core_providers.dart';
import '../../models/sondaggio.dart';
import '../../widgets/loading_state.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/error_state.dart';
import 'sondaggi_notifier.dart';

class SondaggiListScreen extends ConsumerWidget {
  const SondaggiListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final colors = AppColors.resolve(theme.brightness, ref.watch(activePaletteProvider));
    final async = ref.watch(sondaggiNotifierProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Sondaggi')),
      body: async.when(
        loading: () => const LoadingState(),
        error: (e, _) => ErrorState(
          error: e,
          onRetry: () => ref.read(sondaggiNotifierProvider.notifier).refresh(),
        ),
        data: (sondaggi) {
          if (sondaggi.isEmpty) {
            return const EmptyState(
              message: 'Nessun sondaggio disponibile.',
              icon: Icons.poll_outlined,
            );
          }
          return ListView.separated(
            padding: const EdgeInsets.fromLTRB(AppSizes.padX, 16, AppSizes.padX, 100),
            itemCount: sondaggi.length,
            separatorBuilder: (_, __) => const SizedBox(height: 10),
            itemBuilder: (ctx, i) => _SondaggioCard(
              s: sondaggi[i],
              colors: colors,
              onTap: () => ctx.push('/sondaggi/${sondaggi[i].id}'),
            ),
          );
        },
      ),
    );
  }
}

class _SondaggioCard extends StatelessWidget {
  final Sondaggio s;
  final AppColorTokens colors;
  final VoidCallback onTap;

  const _SondaggioCard({required this.s, required this.colors, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return GestureDetector(
      onTap: onTap,
      child: Container(
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
                _StatusChip(aperto: s.aperto, colors: colors),
                const Spacer(),
                Icon(Icons.chevron_right_rounded, color: colors.textFaint),
              ],
            ),
            const SizedBox(height: 8),
            Text(s.titolo,
                style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
            const SizedBox(height: 4),
            Text(
              '${s.partecipanti} partecipanti · ${s.domande.length} domande',
              style: theme.textTheme.bodySmall,
            ),
          ],
        ),
      ),
    );
  }
}

class _StatusChip extends StatelessWidget {
  final bool aperto;
  final AppColorTokens colors;

  const _StatusChip({required this.aperto, required this.colors});

  @override
  Widget build(BuildContext context) {
    final bg = aperto ? colors.accentSoft : colors.chip;
    final fg = aperto ? colors.accent : colors.textMuted;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(AppSizes.radiusFull),
      ),
      child: Text(
        aperto ? 'Attivo' : 'Concluso',
        style: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w600,
          color: fg,
        ),
      ),
    );
  }
}
