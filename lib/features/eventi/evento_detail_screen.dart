import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/constants/app_colors.dart';
import '../../core/constants/app_sizes.dart';
import '../../core/providers/core_providers.dart';
import '../../core/utils/date_formatter.dart';
import '../../models/evento.dart';
import '../../widgets/loading_state.dart';
import '../../widgets/error_state.dart';

final _eventoDetailProvider = FutureProviderFamily<Evento, int>((ref, id) {
  return ref.read(eventiServiceProvider).getEvento(id);
});

Color _catColor(String cat) {
  final map = {
    'cultura': const Color(0xFF6366F1),
    'sport': const Color(0xFF0891B2),
    'ambiente': const Color(0xFF059669),
    'sociale': const Color(0xFFD97706),
    'istituzionale': const Color(0xFF7C3AED),
  };
  return map[cat.toLowerCase()] ?? const Color(0xFF0B5FFF);
}

class EventoDetailScreen extends ConsumerWidget {
  final int id;
  const EventoDetailScreen({super.key, required this.id});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final colors = isDark ? AppColors.dark : AppColors.light[AppPalette.bluCivico]!;
    final async = ref.watch(_eventoDetailProvider(id));

    return Scaffold(
      body: async.when(
        loading: () => const LoadingState(),
        error: (e, _) => Scaffold(
          appBar: AppBar(),
          body: ErrorState(
            error: e,
            onRetry: () => ref.invalidate(_eventoDetailProvider(id)),
          ),
        ),
        data: (evento) {
          final headerColor = _catColor(evento.categoria);
          return CustomScrollView(
            slivers: [
              // Hero header
              SliverAppBar(
                expandedHeight: AppSizes.eventCardHeaderHeight * 1.5,
                pinned: true,
                backgroundColor: headerColor,
                iconTheme: const IconThemeData(color: Colors.white),
                flexibleSpace: FlexibleSpaceBar(
                  background: Stack(
                    fit: StackFit.expand,
                    children: [
                      if (evento.immagineUrl != null)
                        Image.network(
                          evento.immagineUrl!,
                          fit: BoxFit.cover,
                          color: headerColor.withValues(alpha: 0.5),
                          colorBlendMode: BlendMode.multiply,
                          errorBuilder: (_, __, ___) =>
                              Container(color: headerColor),
                        )
                      else
                        Container(color: headerColor),
                      // Categoria badge
                      Positioned(
                        bottom: 16,
                        left: AppSizes.padX,
                        child: Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 10, vertical: 5),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.22),
                            borderRadius: BorderRadius.circular(
                                AppSizes.radiusFull),
                          ),
                          child: Text(
                            evento.categoria,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 12,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),

              SliverPadding(
                padding: const EdgeInsets.all(AppSizes.padX),
                sliver: SliverList(
                  delegate: SliverChildListDelegate([
                    // Title
                    Text(evento.titolo,
                        style: theme.textTheme.headlineMedium
                            ?.copyWith(fontWeight: FontWeight.w800)),
                    const SizedBox(height: 16),

                    // Info row
                    _InfoRow(
                      icon: Icons.schedule_outlined,
                      text: DateFormatter.eventRange(
                          evento.dataInizio, evento.dataFine),
                      colors: colors,
                    ),
                    const SizedBox(height: 8),
                    _InfoRow(
                      icon: Icons.location_on_outlined,
                      text: evento.luogo,
                      colors: colors,
                      tappable: evento.lat != null,
                      onTap: evento.lat != null
                          ? () => launchUrl(Uri.parse(
                              'https://maps.google.com/?q=${evento.lat},${evento.lng}'))
                          : null,
                    ),

                    const Divider(height: 32),

                    // Description
                    Text('Descrizione',
                        style: theme.textTheme.titleSmall
                            ?.copyWith(color: colors.textMuted)),
                    const SizedBox(height: 8),
                    Text(evento.descrizione,
                        style: theme.textTheme.bodyLarge),

                    const SizedBox(height: 80),
                  ]),
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  final IconData icon;
  final String text;
  final AppColorTokens colors;
  final bool tappable;
  final VoidCallback? onTap;

  const _InfoRow({
    required this.icon,
    required this.text,
    required this.colors,
    this.tappable = false,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final row = Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 18, color: colors.primary),
        const SizedBox(width: 10),
        Expanded(
          child: Text(
            text,
            style: theme.textTheme.bodyMedium?.copyWith(
              color: tappable ? colors.primary : null,
              decoration: tappable ? TextDecoration.underline : null,
            ),
          ),
        ),
      ],
    );
    if (onTap != null) {
      return GestureDetector(onTap: onTap, child: row);
    }
    return row;
  }
}
