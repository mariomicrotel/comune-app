import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import '../../core/constants/app_colors.dart';
import '../../core/constants/app_sizes.dart';
import '../../core/providers/core_providers.dart';
import '../../models/segnalazione.dart';
import '../../widgets/loading_state.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/error_state.dart';
import 'segnalazioni_notifier.dart';

class SegnalazioniListScreen extends ConsumerWidget {
  const SegnalazioniListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final colors = AppColors.resolve(theme.brightness, ref.watch(activePaletteProvider));
    final async = ref.watch(mySegnalazioniProvider);
    final df = DateFormat('d MMM yyyy', 'it_IT');

    return Scaffold(
      appBar: AppBar(
        title: const Text('Le mie segnalazioni'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: () => ref.read(mySegnalazioniProvider.notifier).refresh(),
          ),
        ],
      ),
      body: async.when(
        loading: () => const LoadingState(),
        error: (e, _) => ErrorState(
          error: e,
          onRetry: () => ref.read(mySegnalazioniProvider.notifier).refresh(),
        ),
        data: (list) {
          if (list.isEmpty) {
            return EmptyState(
              message: 'Nessuna segnalazione inviata.',
              icon: Icons.report_outlined,
              onRetry: () => context.push('/segnala/new'),
            );
          }
          return ListView.separated(
            padding: const EdgeInsets.fromLTRB(AppSizes.padX, 16, AppSizes.padX, 100),
            itemCount: list.length,
            separatorBuilder: (_, __) => const SizedBox(height: 10),
            itemBuilder: (_, i) {
              final s = list[i];
              return _SegnalazioneRow(s: s, colors: colors, df: df);
            },
          );
        },
      ),
    );
  }
}

class _SegnalazioneRow extends StatelessWidget {
  final Segnalazione s;
  final AppColorTokens colors;
  final DateFormat df;

  const _SegnalazioneRow({required this.s, required this.colors, required this.df});

  (Color bg, Color fg) _statePalette() {
    switch (s.stato) {
      case StatoSegnalazione.ricevuta:
        return (colors.primarySoft, colors.primary);
      case StatoSegnalazione.presaInCarico:
        return (colors.primarySoft, colors.primaryDeep);
      case StatoSegnalazione.inLavorazione:
        return (colors.warnSoft, colors.warn);
      case StatoSegnalazione.chiusa:
        return (colors.accentSoft, colors.accent);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final (bg, fg) = _statePalette();

    return Material(
      color: colors.bgElev,
      borderRadius: BorderRadius.circular(AppSizes.radiusMd),
      child: Container(
        padding: const EdgeInsets.all(AppSizes.cardPad),
        decoration: BoxDecoration(
          border: Border.all(color: colors.border),
          borderRadius: BorderRadius.circular(AppSizes.radiusMd),
        ),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: bg,
                          borderRadius: BorderRadius.circular(AppSizes.radiusFull),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Container(
                              width: 6, height: 6,
                              decoration: BoxDecoration(color: fg, shape: BoxShape.circle),
                            ),
                            const SizedBox(width: 5),
                            Text(
                              s.stato.label,
                              style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: fg),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Text(s.categoria, style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
                  const SizedBox(height: 2),
                  Text(df.format(s.data), style: theme.textTheme.bodySmall),
                  if (s.publicCode != null) ...[
                    const SizedBox(height: 4),
                    Text('#${s.publicCode}', style: theme.textTheme.bodySmall?.copyWith(color: colors.textFaint)),
                  ],
                ],
              ),
            ),
            Icon(Icons.chevron_right_rounded, color: colors.textFaint),
          ],
        ),
      ),
    );
  }
}
