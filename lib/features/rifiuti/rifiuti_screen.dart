import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../core/constants/app_colors.dart';
import '../../core/constants/app_sizes.dart';
import '../../core/providers/core_providers.dart';
import '../../models/zona_rifiuti.dart';
import '../../widgets/loading_state.dart';
import '../../widgets/empty_state.dart';
import 'rifiuti_notifier.dart';

// Reminder toggle — stored locally
final _reminderProvider = StateNotifierProvider<_ReminderNotifier, bool>(
  (ref) => _ReminderNotifier(ref.watch(sharedPreferencesProvider)),
);

class _ReminderNotifier extends StateNotifier<bool> {
  final SharedPreferences _prefs;
  static const _key = 'rifiuti_reminder';

  _ReminderNotifier(this._prefs) : super(_prefs.getBool(_key) ?? false);

  Future<void> toggle(bool v) async {
    state = v;
    await _prefs.setBool(_key, v);
  }
}

class RifiutiScreen extends ConsumerWidget {
  const RifiutiScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final colors = isDark ? AppColors.dark : AppColors.light[AppPalette.bluCivico]!;

    final zoneAsync = ref.watch(zoneRifiutiProvider);
    final selectedId = ref.watch(selectedZonaIdProvider);
    final calAsync = ref.watch(calendarioProvider);
    final prossima = ref.watch(prossimaRaccoltaProvider);
    final reminder = ref.watch(_reminderProvider);

    // Selected zone name
    final zonaName = zoneAsync.maybeWhen(
      data: (zone) =>
          zone.where((z) => z.id == selectedId).firstOrNull?.nome ??
          'Seleziona zona',
      orElse: () => 'Seleziona zona',
    );

    return Scaffold(
      backgroundColor: theme.scaffoldBackgroundColor,
      body: zoneAsync.when(
        loading: () => const LoadingState(),
        error: (_, __) => const EmptyState(message: 'Impossibile caricare le zone.'),
        data: (zone) => CustomScrollView(
          slivers: [
            // ── Custom header ────────────────────────────────────
            SliverToBoxAdapter(
              child: SafeArea(
                bottom: false,
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(
                      AppSizes.padX, 8, AppSizes.padX, 0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Back button
                      GestureDetector(
                        onTap: () => Navigator.maybePop(context),
                        child: Container(
                          width: 36,
                          height: 36,
                          decoration: BoxDecoration(
                            color: colors.chip,
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Icon(Icons.arrow_back_ios_new_rounded,
                              size: 16, color: colors.text),
                        ),
                      ),
                      const SizedBox(height: 12),
                      Text('Raccolta rifiuti',
                          style: theme.textTheme.displaySmall),
                      const SizedBox(height: 2),
                      Text(
                        selectedId != null ? zonaName : 'Seleziona la tua zona',
                        style: theme.textTheme.bodyMedium
                            ?.copyWith(color: colors.textMuted),
                      ),
                      const SizedBox(height: 20),
                    ],
                  ),
                ),
              ),
            ),

            SliverPadding(
              padding: const EdgeInsets.fromLTRB(
                  AppSizes.padX, 0, AppSizes.padX, 100),
              sliver: SliverList(
                delegate: SliverChildListDelegate([
                  // ── Today hero ───────────────────────────────
                  if (prossima != null)
                    _TodayHero(giorno: prossima, colors: colors),
                  if (prossima != null) const SizedBox(height: 16),

                  // ── Zone selector ────────────────────────────
                  _ZoneSelector(
                    zone: zone,
                    selectedId: selectedId,
                    zonaName: zonaName,
                    colors: colors,
                    onSelect: (id) {
                      ref.read(selectedZonaIdProvider.notifier).state = id;
                      ref.read(preferencesServiceProvider).setZonaRifiutiId(id);
                    },
                  ),
                  const SizedBox(height: 24),

                  // ── Calendario settimanale ───────────────────
                  Text('Calendario settimanale',
                      style: theme.textTheme.titleLarge),
                  const SizedBox(height: 12),
                  calAsync.when(
                    loading: () => const LoadingState(),
                    error: (_, __) => const EmptyState(
                        message: 'Impossibile caricare il calendario.'),
                    data: (giorni) =>
                        _WeeklyCalendar(giorni: giorni, colors: colors),
                  ),
                  const SizedBox(height: 24),

                  // ── Legenda ──────────────────────────────────
                  Text('Legenda', style: theme.textTheme.titleLarge),
                  const SizedBox(height: 12),
                  _Legend(colors: colors),
                  const SizedBox(height: 24),

                  // ── Promemoria ───────────────────────────────
                  Text('Promemoria', style: theme.textTheme.titleLarge),
                  const SizedBox(height: 12),
                  _PromemoriaCard(
                    value: reminder,
                    colors: colors,
                    onChanged: (v) =>
                        ref.read(_reminderProvider.notifier).toggle(v),
                  ),
                ]),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Today hero ────────────────────────────────────────────────────────────────

class _TodayHero extends StatelessWidget {
  final RaccoltaGiorno giorno;
  final AppColorTokens colors;

  const _TodayHero({required this.giorno, required this.colors});

  @override
  Widget build(BuildContext context) {
    final tipo = giorno.tipi.isNotEmpty ? giorno.tipi.first : '';
    final col = AppColors.wasteColor(tipo);
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final isToday = giorno.data.isAtSameMomentAs(today);
    final topLabel = isToday
        ? 'OGGI · ESPOSIZIONE 19:00–22:00'
        : 'DOMANI · ESPOSIZIONE 19:00–22:00';
    final subtext = isToday
        ? 'Ricordati di esporre il bidone entro le 22:00 di oggi.'
        : 'Prepara il bidone per domani sera.';

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: col,
        borderRadius: BorderRadius.circular(AppSizes.radiusMd),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            topLabel,
            style: const TextStyle(
              color: Colors.white70,
              fontSize: 11,
              fontWeight: FontWeight.w600,
              letterSpacing: 0.5,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            _capitalizeFirst(tipo),
            style: const TextStyle(
              color: Colors.white,
              fontSize: 28,
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            subtext,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 14,
            ),
          ),
        ],
      ),
    );
  }

  String _capitalizeFirst(String s) =>
      s.isEmpty ? s : '${s[0].toUpperCase()}${s.substring(1)}';
}

// ── Zone selector ─────────────────────────────────────────────────────────────

class _ZoneSelector extends StatelessWidget {
  final List<ZonaRifiuti> zone;
  final int? selectedId;
  final String zonaName;
  final AppColorTokens colors;
  final ValueChanged<int> onSelect;

  const _ZoneSelector({
    required this.zone,
    required this.selectedId,
    required this.zonaName,
    required this.colors,
    required this.onSelect,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return GestureDetector(
      onTap: () => showModalBottomSheet(
        context: context,
        shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
        ),
        builder: (_) => _ZoneSheet(
            zone: zone, selectedId: selectedId, onSelect: onSelect),
      ),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        decoration: BoxDecoration(
          color: colors.bgElev,
          borderRadius: BorderRadius.circular(AppSizes.radiusMd),
          border: Border.all(color: colors.border),
        ),
        child: Row(
          children: [
            Icon(Icons.place_outlined, color: colors.primary, size: 18),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Zona di raccolta',
                    style: theme.textTheme.bodySmall
                        ?.copyWith(color: colors.textMuted),
                  ),
                  const SizedBox(height: 1),
                  Text(
                    zonaName,
                    style: theme.textTheme.bodyLarge
                        ?.copyWith(fontWeight: FontWeight.w600),
                  ),
                ],
              ),
            ),
            Icon(Icons.keyboard_arrow_down_rounded,
                color: colors.textMuted, size: 22),
          ],
        ),
      ),
    );
  }
}

class _ZoneSheet extends StatelessWidget {
  final List<ZonaRifiuti> zone;
  final int? selectedId;
  final ValueChanged<int> onSelect;

  const _ZoneSheet(
      {required this.zone,
      required this.selectedId,
      required this.onSelect});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        const SizedBox(height: 12),
        Container(
          width: 40,
          height: 4,
          decoration: BoxDecoration(
            color: Colors.grey[300],
            borderRadius: BorderRadius.circular(2),
          ),
        ),
        const SizedBox(height: 12),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 4),
          child: Text('Seleziona zona',
              style: theme.textTheme.titleMedium),
        ),
        ...zone.map(
          (z) => ListTile(
            title: Text(z.nome),
            trailing: z.id == selectedId
                ? const Icon(Icons.check_rounded)
                : null,
            onTap: () {
              onSelect(z.id);
              Navigator.pop(context);
            },
          ),
        ),
        const SizedBox(height: 16),
      ],
    );
  }
}

// ── Weekly calendar ───────────────────────────────────────────────────────────

class _WeeklyCalendar extends StatelessWidget {
  final List<RaccoltaGiorno> giorni;
  final AppColorTokens colors;

  const _WeeklyCalendar({required this.giorni, required this.colors});

  static const _dayNames = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];

  @override
  Widget build(BuildContext context) {
    final now = DateTime.now();
    final weekStart = now.subtract(Duration(days: now.weekday - 1));

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
      decoration: BoxDecoration(
        color: colors.bgElev,
        borderRadius: BorderRadius.circular(AppSizes.radiusMd),
        border: Border.all(color: colors.border),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: List.generate(7, (i) {
          final day = weekStart.add(Duration(days: i));
          final isToday = day.year == now.year &&
              day.month == now.month &&
              day.day == now.day;
          final giorno = giorni
              .where((g) =>
                  g.data.year == day.year &&
                  g.data.month == day.month &&
                  g.data.day == day.day)
              .firstOrNull;
          final tipo = giorno?.tipi.firstOrNull;
          final col = tipo != null ? AppColors.wasteColor(tipo) : null;
          final shortType = _shortLabel(tipo);

          return Expanded(
            child: Column(
              children: [
                // Day name
                Text(
                  _dayNames[i],
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight:
                        isToday ? FontWeight.w700 : FontWeight.w500,
                    color: isToday ? colors.primary : colors.textMuted,
                  ),
                ),
                const SizedBox(height: 6),
                // Colored square
                Container(
                  width: 32,
                  height: 32,
                  decoration: BoxDecoration(
                    color: col ?? colors.chip,
                    borderRadius: BorderRadius.circular(8),
                    border: isToday
                        ? Border.all(color: colors.primary, width: 2)
                        : null,
                  ),
                ),
                const SizedBox(height: 5),
                // Type label
                Text(
                  shortType,
                  style: TextStyle(
                    fontSize: 9,
                    color: colors.textMuted,
                    fontWeight: FontWeight.w500,
                  ),
                  textAlign: TextAlign.center,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          );
        }),
      ),
    );
  }

  String _shortLabel(String? tipo) {
    if (tipo == null) return '—';
    const map = {
      'organico': 'Umido',
      'umido': 'Umido',
      'plastica': 'Plastica',
      'plastica e metalli': 'Plastica',
      'carta': 'Carta',
      'vetro': 'Vetro',
      'indifferenziata': 'Indifferenziata',
    };
    return map[tipo.toLowerCase()] ?? tipo;
  }
}

// ── Legenda ───────────────────────────────────────────────────────────────────

class _Legend extends StatelessWidget {
  final AppColorTokens colors;

  const _Legend({required this.colors});

  static const _items = [
    ('Umido', AppColors.wasteOrganico),
    ('Plastica e metalli', AppColors.wastePlastica),
    ('Carta', AppColors.wasteCarta),
    ('Vetro', AppColors.wasteVetro),
    ('Indifferenziata', AppColors.wasteIndifferenziata),
  ];

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.symmetric(
          horizontal: AppSizes.cardPad, vertical: 12),
      decoration: BoxDecoration(
        color: colors.bgElev,
        borderRadius: BorderRadius.circular(AppSizes.radiusMd),
        border: Border.all(color: colors.border),
      ),
      child: Column(
        children: _items.asMap().entries.map((e) {
          final isLast = e.key == _items.length - 1;
          return Column(
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 8),
                child: Row(
                  children: [
                    Container(
                      width: 24,
                      height: 24,
                      decoration: BoxDecoration(
                        color: e.value.$2,
                        borderRadius: BorderRadius.circular(6),
                      ),
                    ),
                    const SizedBox(width: 14),
                    Text(e.value.$1,
                        style: theme.textTheme.bodyMedium
                            ?.copyWith(fontWeight: FontWeight.w500)),
                  ],
                ),
              ),
              if (!isLast)
                Divider(color: colors.border, height: 1),
            ],
          );
        }).toList(),
      ),
    );
  }
}

// ── Promemoria card ───────────────────────────────────────────────────────────

class _PromemoriaCard extends StatelessWidget {
  final bool value;
  final AppColorTokens colors;
  final ValueChanged<bool> onChanged;

  const _PromemoriaCard({
    required this.value,
    required this.colors,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.symmetric(
          horizontal: AppSizes.padX, vertical: 14),
      decoration: BoxDecoration(
        color: colors.bgElev,
        borderRadius: BorderRadius.circular(AppSizes.radiusMd),
        border: Border.all(color: colors.border),
      ),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: colors.primarySoft,
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(Icons.notifications_outlined,
                color: colors.primary, size: 20),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Avvisami la sera prima',
                  style: theme.textTheme.bodyLarge
                      ?.copyWith(fontWeight: FontWeight.w600),
                ),
                const SizedBox(height: 2),
                Text(
                  'Notifica alle 19:00 con il rifiuto del giorno dopo',
                  style: theme.textTheme.bodySmall,
                ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          Switch(
            value: value,
            onChanged: onChanged,
            activeThumbColor: colors.accent,
          ),
        ],
      ),
    );
  }
}
