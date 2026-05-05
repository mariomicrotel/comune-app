import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/constants/app_colors.dart';
import '../../core/constants/app_sizes.dart';
import '../../core/utils/date_formatter.dart';
import '../../models/documento.dart';
import '../../widgets/loading_state.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/error_state.dart';
import 'documenti_notifier.dart';

class DocumentiScreen extends ConsumerWidget {
  const DocumentiScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final colors = isDark ? AppColors.dark : AppColors.light[AppPalette.bluCivico]!;
    final async = ref.watch(documentiNotifierProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Documenti')),
      body: async.when(
        loading: () => const LoadingState(),
        error: (e, _) => ErrorState(
          error: e,
          onRetry: () => ref.read(documentiNotifierProvider.notifier).refresh(),
        ),
        data: (documenti) {
          if (documenti.isEmpty) {
            return const EmptyState(
              message: 'Nessun documento disponibile.',
              icon: Icons.folder_outlined,
            );
          }
          // Group by categoria
          final grouped = <String, List<Documento>>{};
          for (final d in documenti) {
            grouped.putIfAbsent(d.categoria, () => []).add(d);
          }

          return ListView(
            padding: const EdgeInsets.fromLTRB(AppSizes.padX, 16, AppSizes.padX, 100),
            children: grouped.entries.map((entry) {
              return Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Padding(
                    padding: const EdgeInsets.only(bottom: 8, top: 4),
                    child: Text(
                      entry.key,
                      style: theme.textTheme.labelLarge?.copyWith(
                        color: colors.textMuted,
                        letterSpacing: 0.5,
                      ),
                    ),
                  ),
                  Container(
                    decoration: BoxDecoration(
                      color: colors.bgElev,
                      borderRadius: BorderRadius.circular(AppSizes.radiusMd),
                      border: Border.all(color: colors.border),
                    ),
                    child: Column(
                      children: entry.value.asMap().entries.map((e) {
                        final isLast = e.key == entry.value.length - 1;
                        return Column(
                          children: [
                            _DocumentoRow(d: e.value, colors: colors),
                            if (!isLast) Divider(color: colors.border, height: 1, indent: 60),
                          ],
                        );
                      }).toList(),
                    ),
                  ),
                  const SizedBox(height: 16),
                ],
              );
            }).toList(),
          );
        },
      ),
    );
  }
}

class _DocumentoRow extends StatelessWidget {
  final Documento d;
  final AppColorTokens colors;

  const _DocumentoRow({required this.d, required this.colors});

  Future<void> _open() async {
    final uri = Uri.tryParse(d.url);
    if (uri != null) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return InkWell(
      onTap: _open,
      borderRadius: BorderRadius.circular(AppSizes.radiusMd),
      child: Padding(
        padding: const EdgeInsets.symmetric(
            horizontal: AppSizes.cardPad, vertical: 12),
        child: Row(
          children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: colors.dangerSoft,
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(Icons.picture_as_pdf_outlined,
                  color: colors.danger, size: 20),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(d.titolo,
                      style: theme.textTheme.bodyMedium
                          ?.copyWith(fontWeight: FontWeight.w600),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis),
                  const SizedBox(height: 2),
                  Text(
                    '${d.dimensioneLabel}  ·  ${DateFormatter.dayMonthYear(d.data)}',
                    style: theme.textTheme.bodySmall,
                  ),
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
