import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import '../../models/avviso.dart';
import '../../widgets/priority_badge.dart';
import '../../widgets/loading_state.dart';
import '../../widgets/error_state.dart';
import '../../widgets/empty_state.dart';
import '../../core/constants/app_colors.dart';
import '../../core/constants/app_sizes.dart';
import 'avvisi_notifier.dart';

class AvvisiListScreen extends ConsumerWidget {
  const AvvisiListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final colors = isDark ? AppColors.dark : AppColors.light[AppPalette.bluCivico]!;
    final allAvvisi = ref.watch(avvisiNotifierProvider);
    final filtered = ref.watch(avvisiFiltrati);
    final activeFilter = ref.watch(avvisiFilterProvider);

    // Count per filter
    final counts = allAvvisi.maybeWhen(
      data: (list) => {
        'tutti': list.length,
        'urgenti': list.where((a) =>
            a.priorita == PrioritaAvviso.urgente ||
            a.priorita == PrioritaAvviso.alta).length,
        'importanti': list.where((a) => a.priorita == PrioritaAvviso.media).length,
        'info': list.where((a) => a.priorita == PrioritaAvviso.bassa).length,
      },
      orElse: () => <String, int>{},
    );

    return Scaffold(
      backgroundColor: theme.scaffoldBackgroundColor,
      body: CustomScrollView(
        slivers: [
          // App bar
          SliverAppBar(
            pinned: true,
            backgroundColor: theme.scaffoldBackgroundColor,
            surfaceTintColor: Colors.transparent,
            title: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Avvisi', style: theme.textTheme.headlineLarge),
                if (counts['tutti'] != null)
                  Text(
                    '${counts['tutti']} comunicazioni recenti',
                    style: theme.textTheme.bodySmall,
                  ),
              ],
            ),
            titleSpacing: AppSizes.padX,
            actions: [
              IconButton(
                icon: const Icon(Icons.search_rounded),
                onPressed: () {},
                tooltip: 'Cerca avvisi',
              ),
              const SizedBox(width: 8),
            ],
            bottom: PreferredSize(
              preferredSize: const Size.fromHeight(52),
              child: _FilterChips(
                active: activeFilter,
                counts: counts,
                colors: colors,
                onSelected: (f) =>
                    ref.read(avvisiFilterProvider.notifier).state = f,
              ),
            ),
          ),

          // List
          filtered.when(
            loading: () => const SliverFillRemaining(child: LoadingState()),
            error: (e, _) => SliverFillRemaining(
              child: ErrorState(
                error: e,
                onRetry: () => ref.read(avvisiNotifierProvider.notifier).refresh(),
              ),
            ),
            data: (list) {
              if (list.isEmpty) {
                return const SliverFillRemaining(
                  child: EmptyState(
                    message: 'Nessun avviso al momento.',
                    icon: Icons.campaign_outlined,
                  ),
                );
              }
              return SliverPadding(
                padding: const EdgeInsets.fromLTRB(
                    AppSizes.padX, 8, AppSizes.padX, 100),
                sliver: SliverList(
                  delegate: SliverChildBuilderDelegate(
                    (ctx, i) => Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: _AvvisoCard(
                        avviso: list[i],
                        colors: colors,
                        onTap: () => ctx.push('/avvisi/${list[i].id}'),
                      ),
                    ),
                    childCount: list.length,
                  ),
                ),
              );
            },
          ),
        ],
      ),
    );
  }
}

// ── Filter chips ─────────────────────────────────────────────────────────────

class _FilterChips extends StatelessWidget {
  final String active;
  final Map<String, int> counts;
  final AppColorTokens colors;
  final ValueChanged<String> onSelected;

  const _FilterChips({
    required this.active,
    required this.counts,
    required this.colors,
    required this.onSelected,
  });

  static const _filters = [
    ('tutti', 'Tutti'),
    ('urgenti', 'Urgenti'),
    ('importanti', 'Importanti'),
    ('info', 'Info'),
  ];

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 52,
      child: ListView(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.fromLTRB(AppSizes.padX, 8, AppSizes.padX, 8),
        children: _filters.map((f) {
          final isActive = active == f.$1;
          final count = counts[f.$1];
          final label = count != null ? '${f.$2} $count' : f.$2;
          return Padding(
            padding: const EdgeInsets.only(right: 8),
            child: Semantics(
              selected: isActive,
              label: label,
              child: GestureDetector(
                onTap: () => onSelected(f.$1),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 180),
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                  decoration: BoxDecoration(
                    color: isActive ? colors.primary : colors.chip,
                    borderRadius: BorderRadius.circular(AppSizes.radiusFull),
                  ),
                  child: Text(
                    label,
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: isActive ? Colors.white : colors.text,
                    ),
                  ),
                ),
              ),
            ),
          );
        }).toList(),
      ),
    );
  }
}

// ── Avviso card ───────────────────────────────────────────────────────────────

class _AvvisoCard extends StatelessWidget {
  final Avviso avviso;
  final AppColorTokens colors;
  final VoidCallback onTap;

  const _AvvisoCard({
    required this.avviso,
    required this.colors,
    required this.onTap,
  });

  Color get _borderColor {
    switch (avviso.priorita) {
      case PrioritaAvviso.urgente:
      case PrioritaAvviso.alta:
        return colors.danger;
      case PrioritaAvviso.media:
        return colors.warn;
      case PrioritaAvviso.bassa:
        return colors.border;
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final df = DateFormat('d MMMM yyyy', 'it_IT');

    return Semantics(
      button: true,
      label: '${avviso.priorita.label}: ${avviso.titolo}',
      child: Material(
        color: colors.bgElev,
        borderRadius: BorderRadius.circular(AppSizes.radiusMd),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(AppSizes.radiusMd),
          child: Container(
            decoration: BoxDecoration(
              border: Border.all(color: colors.border),
              borderRadius: BorderRadius.circular(AppSizes.radiusMd),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Left priority border
                Container(
                  width: 4,
                  decoration: BoxDecoration(
                    color: _borderColor,
                    borderRadius: const BorderRadius.only(
                      topLeft: Radius.circular(AppSizes.radiusMd),
                      bottomLeft: Radius.circular(AppSizes.radiusMd),
                    ),
                  ),
                ),
                // Content
                Expanded(
                  child: Padding(
                    padding: const EdgeInsets.all(AppSizes.cardPad),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Badge row
                        Row(
                          children: [
                            PriorityBadge(priorita: avviso.priorita, colors: colors),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                '${avviso.categoria} · ${df.format(avviso.data)}',
                                style: theme.textTheme.bodySmall,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        // Title
                        Text(
                          avviso.titolo,
                          style: theme.textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 4),
                        // Summary
                        Text(
                          avviso.contenuto,
                          style: theme.textTheme.bodyMedium?.copyWith(
                            color: colors.textMuted,
                          ),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
