import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../core/constants/app_colors.dart';
import '../../core/constants/app_sizes.dart';
import '../../core/providers/core_providers.dart';
import '../../models/segnalazione.dart';
import '../../widgets/loading_state.dart';
import '../../widgets/error_state.dart';

final _segnalazioneDetailProvider =
    FutureProviderFamily<Segnalazione, String>((ref, id) {
  return ref.read(segnalazioniServiceProvider).getSegnalazione(id);
});

class SegnalazioneDetailScreen extends ConsumerWidget {
  final String id;
  const SegnalazioneDetailScreen({super.key, required this.id});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final colors = isDark ? AppColors.dark : AppColors.light[AppPalette.bluCivico]!;
    final async = ref.watch(_segnalazioneDetailProvider(id));
    final df = DateFormat('d MMMM yyyy', 'it_IT');

    return Scaffold(
      appBar: AppBar(title: const Text('Dettaglio segnalazione')),
      body: async.when(
        loading: () => const LoadingState(),
        error: (e, _) => ErrorState(
          error: e,
          onRetry: () => ref.invalidate(_segnalazioneDetailProvider(id)),
        ),
        data: (s) => SingleChildScrollView(
          padding: const EdgeInsets.all(AppSizes.padX),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Status badge
              _StatoBadge(stato: s.stato, colors: colors),
              const SizedBox(height: 16),

              // Category + date
              Text(s.categoria,
                  style: theme.textTheme.headlineMedium
                      ?.copyWith(fontWeight: FontWeight.w800)),
              const SizedBox(height: 6),
              Text(
                df.format(s.data),
                style: theme.textTheme.bodySmall
                    ?.copyWith(color: colors.textMuted),
              ),
              if (s.publicCode != null) ...[
                const SizedBox(height: 4),
                Text(
                  'Pratica #${s.publicCode}',
                  style: theme.textTheme.bodySmall
                      ?.copyWith(color: colors.textFaint),
                ),
              ],
              const Divider(height: 32),

              // Description
              if (s.descrizione.isNotEmpty) ...[
                Text('Descrizione',
                    style: theme.textTheme.titleSmall
                        ?.copyWith(color: colors.textMuted)),
                const SizedBox(height: 8),
                Text(s.descrizione, style: theme.textTheme.bodyLarge),
                const SizedBox(height: 24),
              ],

              // Photos
              if (s.fotoUrls.isNotEmpty) ...[
                Text('Foto allegate',
                    style: theme.textTheme.titleSmall
                        ?.copyWith(color: colors.textMuted)),
                const SizedBox(height: 8),
                SizedBox(
                  height: 100,
                  child: ListView.separated(
                    scrollDirection: Axis.horizontal,
                    itemCount: s.fotoUrls.length,
                    separatorBuilder: (_, __) => const SizedBox(width: 8),
                    itemBuilder: (_, i) => ClipRRect(
                      borderRadius: BorderRadius.circular(10),
                      child: Image.network(
                        s.fotoUrls[i],
                        width: 100,
                        height: 100,
                        fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) => Container(
                          width: 100,
                          height: 100,
                          color: colors.chip,
                          child: Icon(Icons.broken_image_outlined,
                              color: colors.textFaint),
                        ),
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 24),
              ],

              // Location
              if (s.lat != null && s.lng != null) ...[
                Text('Posizione',
                    style: theme.textTheme.titleSmall
                        ?.copyWith(color: colors.textMuted)),
                const SizedBox(height: 8),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                  decoration: BoxDecoration(
                    color: colors.bgElev,
                    borderRadius: BorderRadius.circular(AppSizes.radiusMd),
                    border: Border.all(color: colors.border),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.location_on_outlined,
                          color: colors.primary, size: 20),
                      const SizedBox(width: 10),
                      Text(
                        '${s.lat!.toStringAsFixed(5)}, ${s.lng!.toStringAsFixed(5)}',
                        style: theme.textTheme.bodyMedium,
                      ),
                    ],
                  ),
                ),
              ],
              const SizedBox(height: 60),
            ],
          ),
        ),
      ),
    );
  }
}

class _StatoBadge extends StatelessWidget {
  final StatoSegnalazione stato;
  final AppColorTokens colors;

  const _StatoBadge({required this.stato, required this.colors});

  (Color bg, Color fg) get _palette {
    switch (stato) {
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
    final (bg, fg) = _palette;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(AppSizes.radiusFull),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 7,
            height: 7,
            decoration: BoxDecoration(color: fg, shape: BoxShape.circle),
          ),
          const SizedBox(width: 6),
          Text(
            stato.label,
            style: TextStyle(
                fontSize: 13, fontWeight: FontWeight.w700, color: fg),
          ),
        ],
      ),
    );
  }
}
