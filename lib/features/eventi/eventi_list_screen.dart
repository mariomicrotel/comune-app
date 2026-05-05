import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/constants/app_colors.dart';
import '../../core/constants/app_sizes.dart';
import '../../core/utils/date_formatter.dart';
import '../../models/evento.dart';
import '../../widgets/loading_state.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/error_state.dart';
import 'eventi_notifier.dart';

class EventiListScreen extends ConsumerWidget {
  const EventiListScreen({super.key});

  // Assign a header color per categoria
  static Color _catColor(String cat) {
    final map = {
      'cultura': const Color(0xFF6366F1),
      'sport': const Color(0xFF0891B2),
      'ambiente': const Color(0xFF059669),
      'sociale': const Color(0xFFD97706),
      'istituzionale': const Color(0xFF7C3AED),
    };
    return map[cat.toLowerCase()] ?? const Color(0xFF0B5FFF);
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final colors = isDark ? AppColors.dark : AppColors.light[AppPalette.bluCivico]!;
    final async = ref.watch(eventiNotifierProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Eventi')),
      body: async.when(
        loading: () => const LoadingState(),
        error: (e, _) => ErrorState(
          error: e,
          onRetry: () => ref.read(eventiNotifierProvider.notifier).refresh(),
        ),
        data: (eventi) {
          if (eventi.isEmpty) {
            return const EmptyState(
              message: 'Nessun evento in programma.',
              icon: Icons.event_outlined,
            );
          }
          return ListView.separated(
            padding: const EdgeInsets.fromLTRB(AppSizes.padX, 16, AppSizes.padX, 100),
            itemCount: eventi.length,
            separatorBuilder: (_, __) => const SizedBox(height: 14),
            itemBuilder: (ctx, i) {
              final e = eventi[i];
              return _EventoCard(
                evento: e,
                headerColor: _catColor(e.categoria),
                colors: colors,
                onTap: () => ctx.push('/eventi/${e.id}'),
              );
            },
          );
        },
      ),
    );
  }
}

class _EventoCard extends StatelessWidget {
  final Evento evento;
  final Color headerColor;
  final AppColorTokens colors;
  final VoidCallback onTap;

  const _EventoCard({
    required this.evento,
    required this.headerColor,
    required this.colors,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Semantics(
      button: true,
      label: evento.titolo,
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          decoration: BoxDecoration(
            color: colors.bgElev,
            borderRadius: BorderRadius.circular(AppSizes.radiusMd),
            border: Border.all(color: colors.border),
          ),
          clipBehavior: Clip.hardEdge,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Colored header
              Container(
                height: AppSizes.eventCardHeaderHeight,
                color: headerColor,
                child: Stack(
                  children: [
                    if (evento.immagineUrl != null)
                      Positioned.fill(
                        child: Image.network(
                          evento.immagineUrl!,
                          fit: BoxFit.cover,
                          color: headerColor.withValues(alpha: 0.4),
                          colorBlendMode: BlendMode.multiply,
                          errorBuilder: (_, __, ___) => const SizedBox(),
                        ),
                      ),
                    // Date badge
                    Positioned(
                      top: 12,
                      left: 12,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Text(
                          DateFormatter.dayMonthShort(evento.dataInizio),
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w700,
                            color: headerColor,
                          ),
                        ),
                      ),
                    ),
                    // Categoria badge
                    Positioned(
                      top: 12,
                      right: 12,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.22),
                          borderRadius: BorderRadius.circular(AppSizes.radiusFull),
                        ),
                        child: Text(
                          evento.categoria,
                          style: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w600,
                            color: Colors.white,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              // Content footer
              Padding(
                padding: const EdgeInsets.all(AppSizes.cardPad),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      evento.titolo,
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Icon(Icons.schedule_outlined, size: 14, color: colors.textMuted),
                        const SizedBox(width: 4),
                        Text(
                          DateFormatter.eventRange(evento.dataInizio, evento.dataFine),
                          style: theme.textTheme.bodySmall,
                        ),
                        const SizedBox(width: 12),
                        Icon(Icons.location_on_outlined, size: 14, color: colors.textMuted),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            evento.luogo,
                            style: theme.textTheme.bodySmall,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
