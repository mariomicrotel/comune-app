import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/constants/app_colors.dart';
import '../../core/constants/app_sizes.dart';
import '../../models/sondaggio.dart';
import '../../widgets/loading_state.dart';
import '../../widgets/error_state.dart';
import '../../widgets/primary_button.dart';
import '../../core/providers/core_providers.dart';
import 'sondaggi_notifier.dart';

class SondaggioDetailScreen extends ConsumerStatefulWidget {
  final int id;

  const SondaggioDetailScreen({super.key, required this.id});

  @override
  ConsumerState<SondaggioDetailScreen> createState() => _State();
}

class _State extends ConsumerState<SondaggioDetailScreen> {
  bool _submitted = false;
  bool _submitting = false;

  Future<void> _submit(Sondaggio s) async {
    setState(() => _submitting = true);
    try {
      final risposte = ref.read(risposteProvider);
      await ref.read(sondaggiServiceProvider).submitRisposte(s.id, risposte);
      ref.read(risposteProvider.notifier).reset();
      setState(() => _submitted = true);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(e.toString())));
      }
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colors = AppColors.resolve(theme.brightness, ref.watch(activePaletteProvider));
    final async = ref.watch(sondaggioDetailProvider(widget.id));

    return Scaffold(
      appBar: AppBar(title: const Text('Sondaggio')),
      body: async.when(
        loading: () => const LoadingState(),
        error: (e, _) => ErrorState(
          error: e,
          onRetry: () => ref.invalidate(sondaggioDetailProvider(widget.id)),
        ),
        data: (s) {
          if (_submitted) return _Thanks(colors: colors);
          return _Form(
            s: s,
            colors: colors,
            submitting: _submitting,
            onSubmit: () => _submit(s),
          );
        },
      ),
    );
  }
}

class _Form extends ConsumerWidget {
  final Sondaggio s;
  final AppColorTokens colors;
  final bool submitting;
  final VoidCallback onSubmit;

  const _Form({
    required this.s,
    required this.colors,
    required this.submitting,
    required this.onSubmit,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final risposte = ref.watch(risposteProvider);
    final notifier = ref.read(risposteProvider.notifier);
    final disabled = !s.aperto;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(AppSizes.padX),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (disabled)
            Container(
              margin: const EdgeInsets.only(bottom: 16),
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: colors.chip,
                borderRadius: BorderRadius.circular(AppSizes.radiusMd),
              ),
              child: Row(
                children: [
                  Icon(Icons.lock_outline, size: 16, color: colors.textMuted),
                  const SizedBox(width: 8),
                  Text('Sondaggio concluso', style: TextStyle(color: colors.textMuted)),
                ],
              ),
            ),
          Text(s.titolo, style: theme.textTheme.headlineMedium),
          const SizedBox(height: 8),
          Text(s.descrizione, style: theme.textTheme.bodyMedium?.copyWith(color: colors.textMuted)),
          const SizedBox(height: 24),
          ...s.domande.asMap().entries.map((e) => Padding(
            padding: const EdgeInsets.only(bottom: 20),
            child: _QuestionWidget(
              domanda: e.value,
              index: e.key,
              risposta: risposte[e.value.id],
              disabled: disabled,
              colors: colors,
              onChanged: (v) => notifier.set(e.value.id, v),
              onToggle: (o) => notifier.toggleMultiple(e.value.id, o),
            ),
          )),
          if (!disabled)
            PrimaryButton(
              label: 'Invia risposta',
              onPressed: onSubmit,
              loading: submitting,
            ),
          const SizedBox(height: 80),
        ],
      ),
    );
  }
}

class _QuestionWidget extends StatelessWidget {
  final Domanda domanda;
  final int index;
  final dynamic risposta;
  final bool disabled;
  final AppColorTokens colors;
  final ValueChanged<dynamic> onChanged;
  final ValueChanged<String> onToggle;

  const _QuestionWidget({
    required this.domanda,
    required this.index,
    required this.risposta,
    required this.disabled,
    required this.colors,
    required this.onChanged,
    required this.onToggle,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          '${index + 1}. ${domanda.testo}',
          style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 10),
        switch (domanda.tipo) {
          TipoDomanda.sceltaSingola => Column(
              children: domanda.opzioni.map((op) {
                return InkWell(
                  onTap: disabled ? null : () => onChanged(op),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(vertical: 6),
                    child: Row(
                      children: [
                        Radio<String>(
                          value: op,
                          groupValue: risposta as String?,
                          onChanged: disabled ? null : (v) => onChanged(v),
                          visualDensity: VisualDensity.compact,
                          materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                        ),
                        const SizedBox(width: 8),
                        Expanded(child: Text(op, style: theme.textTheme.bodyMedium)),
                      ],
                    ),
                  ),
                );
              }).toList(),
            ),
          TipoDomanda.sceltaMultipla => Column(
              children: domanda.opzioni.map((op) {
                final selected = (risposta as List<String>?)?.contains(op) ?? false;
                return CheckboxListTile(
                  value: selected,
                  onChanged: disabled ? null : (_) => onToggle(op),
                  title: Text(op, style: theme.textTheme.bodyMedium),
                  contentPadding: EdgeInsets.zero,
                  visualDensity: VisualDensity.compact,
                );
              }).toList(),
            ),
          TipoDomanda.testo => TextField(
              enabled: !disabled,
              onChanged: onChanged,
              maxLines: 3,
              decoration: const InputDecoration(hintText: 'Scrivi la tua risposta…'),
            ),
        },
      ],
    );
  }
}

class _Thanks extends StatelessWidget {
  final AppColorTokens colors;

  const _Thanks({required this.colors});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(40),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 80,
              height: 80,
              decoration: BoxDecoration(color: colors.accentSoft, shape: BoxShape.circle),
              child: Icon(Icons.check_rounded, size: 44, color: colors.accent),
            ),
            const SizedBox(height: 20),
            Text('Grazie per aver partecipato!', style: theme.textTheme.headlineSmall),
            const SizedBox(height: 8),
            Text('La tua risposta è stata registrata.',
                textAlign: TextAlign.center,
                style: theme.textTheme.bodyMedium?.copyWith(color: colors.textMuted)),
            const SizedBox(height: 32),
            PrimaryButton(
              label: 'Torna ai sondaggi',
              onPressed: () => context.pop(),
            ),
          ],
        ),
      ),
    );
  }
}
