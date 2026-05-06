import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../core/constants/app_colors.dart';
import '../../core/constants/app_sizes.dart';
import '../../core/providers/core_providers.dart';
import '../../models/avviso.dart';
import '../../widgets/loading_state.dart';
import '../../widgets/error_state.dart';
import '../../widgets/priority_badge.dart';

final _avvisoDetailProvider = FutureProvider.family<Avviso, int>(
  (ref, id) => ref.read(avvisiServiceProvider).getAvviso(id),
);

class AvvisoDetailScreen extends ConsumerWidget {
  final int id;

  const AvvisoDetailScreen({super.key, required this.id});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(_avvisoDetailProvider(id));
    final theme = Theme.of(context);
    final colors = AppColors.resolve(theme.brightness, ref.watch(activePaletteProvider));
    final df = DateFormat("d MMMM yyyy", 'it_IT');

    return Scaffold(
      appBar: AppBar(
        title: const Text('Avviso'),
      ),
      body: async.when(
        loading: () => const LoadingState(),
        error: (e, _) => ErrorState(
          error: e,
          onRetry: () => ref.invalidate(_avvisoDetailProvider(id)),
        ),
        data: (avviso) => SingleChildScrollView(
          padding: const EdgeInsets.all(AppSizes.padX),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              PriorityBadge(priorita: avviso.priorita, colors: colors),
              const SizedBox(height: 12),
              Text(avviso.titolo, style: theme.textTheme.headlineMedium),
              const SizedBox(height: 8),
              Text(
                '${avviso.categoria} · ${df.format(avviso.data)}',
                style: theme.textTheme.bodySmall,
              ),
              const Divider(height: 32),
              Text(avviso.contenuto, style: theme.textTheme.bodyLarge),
              const SizedBox(height: 60),
            ],
          ),
        ),
      ),
    );
  }
}
