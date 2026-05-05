import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:latlong2/latlong.dart';
import '../../core/constants/app_colors.dart';
import '../../core/constants/app_sizes.dart';
import '../../models/luogo.dart';
import '../../widgets/loading_state.dart';
import '../../widgets/error_state.dart';
import '../luoghi/luoghi_notifier.dart';

// Category → color mapping
Color _catColor(String cat) {
  switch (cat.toLowerCase()) {
    case 'salute':
      return const Color(0xFF1ED27A);
    case 'trasporti':
      return const Color(0xFF3F88E0);
    case 'cultura':
      return const Color(0xFF7C3AED);
    case 'sport':
      return const Color(0xFFE8A317);
    case 'istruzione':
      return const Color(0xFFD7263D);
    default:
      return const Color(0xFF0B5FFF);
  }
}

final _mapFilterProvider = StateProvider<String>((ref) => 'tutti');
final _mapSearchProvider = StateProvider<String>((ref) => '');

class LuoghiMapScreen extends ConsumerWidget {
  const LuoghiMapScreen({super.key});

  static const _center = LatLng(40.7519, 15.0308);

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final colors = isDark ? AppColors.dark : AppColors.light[AppPalette.bluCivico]!;
    final async = ref.watch(luoghiNotifierProvider);
    final filter = ref.watch(_mapFilterProvider);
    final search = ref.watch(_mapSearchProvider);

    return Scaffold(
      body: async.when(
        loading: () => const LoadingState(),
        error: (e, _) => ErrorState(
          error: e,
          onRetry: () => ref.read(luoghiNotifierProvider.notifier).refresh(),
        ),
        data: (all) {
          // Apply filter + search
          final filtered = all.where((l) {
            final matchCat = filter == 'tutti' ||
                l.categoria.toLowerCase() == filter.toLowerCase();
            final matchSearch = search.isEmpty ||
                l.nome.toLowerCase().contains(search.toLowerCase()) ||
                l.indirizzo.toLowerCase().contains(search.toLowerCase());
            return matchCat && matchSearch;
          }).toList();

          // Unique categories
          final cats = ['tutti', ...{...all.map((l) => l.categoria)}];

          return Column(
            children: [
              // ── Map (top half) ──────────────────────────────────
              SizedBox(
                height: MediaQuery.of(context).size.height * 0.40,
                child: Stack(
                  children: [
                    FlutterMap(
                      options: MapOptions(
                        initialCenter: _center,
                        initialZoom: 14.5,
                      ),
                      children: [
                        TileLayer(
                          urlTemplate:
                              'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                          userAgentPackageName: 'it.comune.app',
                        ),
                        MarkerLayer(
                          markers: filtered.map((l) {
                            final col = _catColor(l.categoria);
                            return Marker(
                              point: LatLng(l.lat, l.lng),
                              width: 36,
                              height: 44,
                              child: Semantics(
                                label: l.nome,
                                child: Column(
                                  children: [
                                    Container(
                                      width: 32,
                                      height: 32,
                                      decoration: BoxDecoration(
                                        color: col,
                                        shape: BoxShape.circle,
                                        boxShadow: [
                                          BoxShadow(
                                            color: col.withValues(alpha: 0.4),
                                            blurRadius: 6,
                                            offset: const Offset(0, 2),
                                          ),
                                        ],
                                      ),
                                      child: const Icon(
                                        Icons.place_rounded,
                                        color: Colors.white,
                                        size: 18,
                                      ),
                                    ),
                                    // Pin tail
                                    Container(
                                      width: 3,
                                      height: 8,
                                      decoration: BoxDecoration(
                                        color: col,
                                        borderRadius: BorderRadius.circular(2),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            );
                          }).toList(),
                        ),
                      ],
                    ),
                    // Search bar overlay
                    Positioned(
                      top: MediaQuery.of(context).padding.top + 8,
                      left: 12,
                      right: 12,
                      child: _SearchBar(
                        colors: colors,
                        onBack: () => Navigator.maybePop(context),
                        onChanged: (v) =>
                            ref.read(_mapSearchProvider.notifier).state = v,
                      ),
                    ),
                  ],
                ),
              ),

              // ── Filter chips ───────────────────────────────────
              Container(
                color: theme.scaffoldBackgroundColor,
                child: _FilterRow(
                  categories: cats,
                  active: filter,
                  colors: colors,
                  onSelect: (c) =>
                      ref.read(_mapFilterProvider.notifier).state = c,
                ),
              ),

              // ── List ───────────────────────────────────────────
              Expanded(
                child: filtered.isEmpty
                    ? Center(
                        child: Text('Nessun luogo trovato.',
                            style: theme.textTheme.bodyMedium
                                ?.copyWith(color: colors.textMuted)),
                      )
                    : ListView.separated(
                        padding: const EdgeInsets.fromLTRB(
                            AppSizes.padX, 8, AppSizes.padX, 100),
                        itemCount: filtered.length,
                        separatorBuilder: (_, __) => Divider(
                          color: colors.border,
                          height: 1,
                          indent: 60,
                        ),
                        itemBuilder: (_, i) => _LuogoRow(
                          luogo: filtered[i],
                          colors: colors,
                        ),
                      ),
              ),
            ],
          );
        },
      ),
    );
  }
}

// ── Search bar ────────────────────────────────────────────────────────────────

class _SearchBar extends StatelessWidget {
  final AppColorTokens colors;
  final VoidCallback onBack;
  final ValueChanged<String> onChanged;

  const _SearchBar({
    required this.colors,
    required this.onBack,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 48,
      decoration: BoxDecoration(
        color: colors.bgElev,
        borderRadius: BorderRadius.circular(AppSizes.radiusMd),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.12),
            blurRadius: 12,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          GestureDetector(
            onTap: onBack,
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 12),
              child: Icon(Icons.arrow_back_rounded,
                  size: 20, color: colors.textMuted),
            ),
          ),
          const SizedBox(width: 2),
          Icon(Icons.search_rounded, size: 18, color: colors.textFaint),
          const SizedBox(width: 8),
          Expanded(
            child: TextField(
              onChanged: onChanged,
              style: TextStyle(fontSize: 15, color: colors.text),
              decoration: InputDecoration(
                hintText: 'Cerca un luogo o servizio',
                hintStyle: TextStyle(color: colors.textFaint, fontSize: 15),
                border: InputBorder.none,
                enabledBorder: InputBorder.none,
                focusedBorder: InputBorder.none,
                contentPadding: EdgeInsets.zero,
                isDense: true,
                filled: false,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ── Filter row ────────────────────────────────────────────────────────────────

class _FilterRow extends StatelessWidget {
  final List<String> categories;
  final String active;
  final AppColorTokens colors;
  final ValueChanged<String> onSelect;

  const _FilterRow({
    required this.categories,
    required this.active,
    required this.colors,
    required this.onSelect,
  });

  String _label(String cat) {
    if (cat == 'tutti') return 'Tutti';
    return '${cat[0].toUpperCase()}${cat.substring(1)}';
  }

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 52,
      child: ListView(
        scrollDirection: Axis.horizontal,
        padding:
            const EdgeInsets.fromLTRB(AppSizes.padX, 8, AppSizes.padX, 8),
        children: categories.map((cat) {
          final isActive = active == cat;
          return Padding(
            padding: const EdgeInsets.only(right: 8),
            child: GestureDetector(
              onTap: () => onSelect(cat),
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 180),
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                decoration: BoxDecoration(
                  color: isActive ? colors.primary : colors.chip,
                  borderRadius: BorderRadius.circular(AppSizes.radiusFull),
                ),
                child: Text(
                  _label(cat),
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: isActive ? Colors.white : colors.text,
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

// ── Luogo row ─────────────────────────────────────────────────────────────────

class _LuogoRow extends StatelessWidget {
  final Luogo luogo;
  final AppColorTokens colors;

  const _LuogoRow({required this.luogo, required this.colors});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final col = _catColor(luogo.categoria);
    // Simulate distance (would use geolocator in production)
    final fakeDistance = _fakeDistance(luogo.nome);

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 12),
      child: Row(
        children: [
          // Colored icon
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: col.withValues(alpha: 0.13),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(Icons.place_rounded, color: col, size: 22),
          ),
          const SizedBox(width: 12),
          // Name + address
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  luogo.nome,
                  style: theme.textTheme.bodyLarge
                      ?.copyWith(fontWeight: FontWeight.w700),
                ),
                Text(
                  luogo.indirizzo,
                  style: theme.textTheme.bodySmall,
                ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          // Distance + category
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                fakeDistance,
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                  color: colors.primary,
                ),
              ),
              Text(
                luogo.categoria,
                style: theme.textTheme.bodySmall
                    ?.copyWith(color: colors.textMuted),
              ),
            ],
          ),
        ],
      ),
    );
  }

  String _fakeDistance(String name) {
    // Deterministic fake distances until real geolocation
    final hash = name.codeUnits.fold(0, (a, b) => a + b) % 8;
    final distances = ['80 m', '120 m', '210 m', '340 m', '410 m', '520 m', '650 m', '890 m'];
    return distances[hash];
  }
}
