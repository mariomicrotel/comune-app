import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../core/constants/app_colors.dart';
import '../../core/constants/app_sizes.dart';

// Modello notifica locale (solo visualizzazione storico)
class _Notifica {
  final String titolo;
  final String corpo;
  final DateTime data;
  final bool letta;
  final String tipo; // 'avviso' | 'segnalazione' | 'info'

  const _Notifica({
    required this.titolo,
    required this.corpo,
    required this.data,
    this.letta = false,
    this.tipo = 'info',
  });
}

// Fixture notifiche per demo
final _notificheFixture = [
  _Notifica(
    titolo: 'Interruzione acqua',
    corpo: 'Interruzione idrica programmata nel centro storico dalle 8:00 alle 14:00.',
    data: DateTime.now().subtract(const Duration(hours: 2)),
    tipo: 'avviso',
  ),
  _Notifica(
    titolo: 'Segnalazione aggiornata',
    corpo: 'La tua segnalazione #ACR-0042 è ora "In lavorazione".',
    data: DateTime.now().subtract(const Duration(hours: 5)),
    letta: true,
    tipo: 'segnalazione',
  ),
  _Notifica(
    titolo: 'Mercato settimanale',
    corpo: 'Domani, giovedì, il mercato si terrà regolarmente in Piazza Roma.',
    data: DateTime.now().subtract(const Duration(days: 1)),
    letta: true,
    tipo: 'info',
  ),
  _Notifica(
    titolo: 'Raccolta straordinaria',
    corpo: 'Sabato 10 maggio raccolta straordinaria di ingombranti. Prenota il ritiro.',
    data: DateTime.now().subtract(const Duration(days: 2)),
    letta: true,
    tipo: 'avviso',
  ),
  _Notifica(
    titolo: 'Nuovi documenti disponibili',
    corpo: 'Sono stati pubblicati i verbali del Consiglio Comunale del 28 aprile.',
    data: DateTime.now().subtract(const Duration(days: 4)),
    letta: true,
    tipo: 'info',
  ),
];

class NotificheScreen extends ConsumerWidget {
  const NotificheScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final colors = isDark ? AppColors.dark : AppColors.light[AppPalette.bluCivico]!;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifiche'),
        actions: [
          TextButton(
            onPressed: () {},
            child: Text('Segna tutte lette',
                style: TextStyle(fontSize: 13, color: colors.primary)),
          ),
        ],
      ),
      body: _notificheFixture.isEmpty
          ? Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.notifications_none_rounded,
                      size: 56, color: colors.textFaint),
                  const SizedBox(height: 12),
                  Text('Nessuna notifica',
                      style: theme.textTheme.bodyMedium
                          ?.copyWith(color: colors.textMuted)),
                ],
              ),
            )
          : ListView.separated(
              padding: const EdgeInsets.fromLTRB(
                  AppSizes.padX, 12, AppSizes.padX, 100),
              itemCount: _notificheFixture.length,
              separatorBuilder: (_, __) =>
                  Divider(color: colors.border, height: 1),
              itemBuilder: (_, i) => _NotificaRow(
                n: _notificheFixture[i],
                colors: colors,
              ),
            ),
    );
  }
}

class _NotificaRow extends StatelessWidget {
  final _Notifica n;
  final AppColorTokens colors;

  const _NotificaRow({required this.n, required this.colors});

  IconData get _icon {
    switch (n.tipo) {
      case 'avviso':
        return Icons.campaign_rounded;
      case 'segnalazione':
        return Icons.report_problem_outlined;
      default:
        return Icons.info_outline_rounded;
    }
  }

  Color _iconColor(AppColorTokens c) {
    switch (n.tipo) {
      case 'avviso':
        return c.danger;
      case 'segnalazione':
        return c.warn;
      default:
        return c.primary;
    }
  }

  Color _iconBg(AppColorTokens c) {
    switch (n.tipo) {
      case 'avviso':
        return c.dangerSoft;
      case 'segnalazione':
        return c.warnSoft;
      default:
        return c.primarySoft;
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final df = DateFormat('d MMM · HH:mm', 'it_IT');

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 14),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: _iconBg(colors),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(_icon, color: _iconColor(colors), size: 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        n.titolo,
                        style: theme.textTheme.bodyMedium?.copyWith(
                          fontWeight:
                              n.letta ? FontWeight.w500 : FontWeight.w700,
                        ),
                      ),
                    ),
                    if (!n.letta)
                      Container(
                        width: 8,
                        height: 8,
                        margin: const EdgeInsets.only(left: 6, top: 4),
                        decoration: BoxDecoration(
                          color: colors.primary,
                          shape: BoxShape.circle,
                        ),
                      ),
                  ],
                ),
                const SizedBox(height: 3),
                Text(
                  n.corpo,
                  style: theme.textTheme.bodySmall,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 4),
                Text(
                  df.format(n.data),
                  style: TextStyle(fontSize: 11, color: colors.textFaint),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
