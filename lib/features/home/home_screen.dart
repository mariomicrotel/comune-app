import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/config/app_config.dart';
import '../../core/constants/app_colors.dart';
import '../../core/constants/app_sizes.dart';
import '../../core/providers/core_providers.dart';
import '../../core/utils/date_formatter.dart';
import '../../features/avvisi/avvisi_notifier.dart';
import '../../features/rifiuti/rifiuti_notifier.dart';
import '../../models/avviso.dart';
import '../../models/zona_rifiuti.dart';

class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final colors = isDark ? AppColors.dark : AppColors.light[AppPalette.bluCivico]!;
    final prefs = ref.watch(preferencesServiceProvider);
    final firstName = prefs.firstName ?? 'Cittadino';
    final urgentiCount = ref.watch(avvisiUrgentiCount);
    final prossima = ref.watch(prossimaRaccoltaProvider);
    final avvisiAsync = ref.watch(avvisiNotifierProvider);

    // Top urgent avviso banner
    final urgentAvviso = avvisiAsync.maybeWhen(
      data: (list) => list.where((a) =>
          a.priorita == PrioritaAvviso.urgente ||
          a.priorita == PrioritaAvviso.alta).firstOrNull,
      orElse: () => null,
    );

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          // Header
          SliverToBoxAdapter(
            child: _HomeHeader(
              firstName: firstName,
              colors: colors,
              urgentAvviso: urgentAvviso,
            ),
          ),
          // Grid
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(AppSizes.padX, 4, AppSizes.padX, 0),
            sliver: SliverGrid(
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2,
                crossAxisSpacing: 12,
                mainAxisSpacing: 12,
                childAspectRatio: 1.1,
              ),
              delegate: SliverChildListDelegate([
                _HomeTile(
                  icon: Icons.campaign_rounded,
                  label: 'Avvisi',
                  color: colors.danger,
                  badge: urgentiCount > 0 ? urgentiCount : null,
                  colors: colors,
                  onTap: () => context.go('/avvisi'),
                ),
                _HomeTile(
                  icon: Icons.report_problem_outlined,
                  label: 'Segnala',
                  color: colors.primary,
                  colors: colors,
                  onTap: () => context.push('/segnala/new'),
                ),
                _HomeTile(
                  icon: Icons.delete_outline_rounded,
                  label: 'Rifiuti',
                  color: AppColors.wasteOrganico,
                  colors: colors,
                  onTap: () => context.push('/rifiuti'),
                ),
                _HomeTile(
                  icon: Icons.event_outlined,
                  label: 'Eventi',
                  color: colors.accent,
                  colors: colors,
                  onTap: () => context.push('/eventi'),
                ),
                _HomeTile(
                  icon: Icons.business_outlined,
                  label: 'Uffici',
                  color: const Color(0xFF6366F1),
                  colors: colors,
                  onTap: () => context.push('/uffici'),
                ),
                _HomeTile(
                  icon: Icons.map_outlined,
                  label: 'Mappa',
                  color: const Color(0xFF0891B2),
                  colors: colors,
                  onTap: () => context.go('/luoghi/mappa'),
                ),
                _HomeTile(
                  icon: Icons.folder_outlined,
                  label: 'Documenti',
                  color: colors.warn,
                  colors: colors,
                  onTap: () => context.push('/documenti'),
                ),
                _HomeTile(
                  icon: Icons.poll_outlined,
                  label: 'Sondaggi',
                  color: const Color(0xFF7C3AED),
                  colors: colors,
                  onTap: () => context.push('/sondaggi'),
                ),
              ]),
            ),
          ),
          // Waste peek
          if (prossima != null)
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(AppSizes.padX, 14, AppSizes.padX, 0),
                child: _WastePeek(giorno: prossima, colors: colors),
              ),
            ),
          const SliverToBoxAdapter(child: SizedBox(height: 100)),
        ],
      ),
    );
  }
}

// ── Header ────────────────────────────────────────────────────────────────────

class _HomeHeader extends StatelessWidget {
  final String firstName;
  final AppColorTokens colors;
  final Avviso? urgentAvviso;

  const _HomeHeader({
    required this.firstName,
    required this.colors,
    this.urgentAvviso,
  });

  String _greeting() {
    final h = DateTime.now().hour;
    if (h < 12) return 'Buongiorno';
    if (h < 18) return 'Buon pomeriggio';
    return 'Buonasera';
  }

  @override
  Widget build(BuildContext context) {
    final now = DateTime.now();
    // "Martedì 5 maggio" — giorno della settimana + data
    final todayFull = DateFormatter.dayName(now).replaceFirst(
            DateFormatter.dayName(now)[0],
            DateFormatter.dayName(now)[0].toUpperCase()) +
        ' ${DateFormatter.dayMonth(now)}';

    return Container(
      decoration: BoxDecoration(
        color: colors.primary,
        image: const DecorationImage(
          image: AssetImage('assets/images/acerno_bg.jpg'),
          fit: BoxFit.cover,
          // Overlay primary blu ~75% opaco → foto in trasparenza
          colorFilter: ColorFilter.mode(
            Color(0xBF0B5FFF), // #0B5FFF con alpha 0xBF ≈ 75%
            BlendMode.srcOver,
          ),
          alignment: Alignment.topCenter,
        ),
      ),
      child: Stack(
        children: [
          SafeArea(
            bottom: false,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Padding(
                  padding: const EdgeInsets.fromLTRB(AppSizes.padX, 16, AppSizes.padX, 0),
                  child: Row(
                    children: [
                      // Stemma / coat of arms
                      Container(
                        width: 40,
                        height: 40,
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.18),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: ClipRRect(
                          borderRadius: BorderRadius.circular(10),
                          child: Image.asset(
                            'assets/images/stemma-acerno.png',
                            fit: BoxFit.contain,
                            errorBuilder: (_, __, ___) => const Icon(
                              Icons.location_city_rounded,
                              size: 22,
                              color: Colors.white,
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              AppConfig.comuneProvince.toUpperCase(),
                              style: const TextStyle(
                                color: Colors.white70,
                                fontSize: 10,
                                letterSpacing: 0.8,
                              ),
                            ),
                            Text(
                              AppConfig.comuneName,
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 15,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ],
                        ),
                      ),
                      // Notifications bell
                      Container(
                        width: 40,
                        height: 40,
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.18),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: const Icon(Icons.notifications_outlined,
                            color: Colors.white, size: 20),
                      ),
                    ],
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.fromLTRB(AppSizes.padX, 20, AppSizes.padX, 24),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        todayFull,
                        style: const TextStyle(color: Colors.white70, fontSize: 13),
                      ),
                      Text(
                        '${_greeting()}, $firstName',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 26,
                          fontWeight: FontWeight.w800,
                          height: 1.15,
                        ),
                      ),
                    ],
                  ),
                ),
                // Urgent banner
                if (urgentAvviso != null)
                  Padding(
                    padding: const EdgeInsets.fromLTRB(AppSizes.padX, 0, AppSizes.padX, 12),
                    child: _UrgentBanner(avviso: urgentAvviso!, colors: colors),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _UrgentBanner extends StatelessWidget {
  final Avviso avviso;
  final AppColorTokens colors;

  const _UrgentBanner({required this.avviso, required this.colors});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: colors.bgElev,
        borderRadius: BorderRadius.circular(AppSizes.radiusMd),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.12),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: colors.dangerSoft,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(Icons.warning_amber_rounded,
                color: colors.danger, size: 22),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(avviso.titolo,
                    style: theme.textTheme.bodyMedium
                        ?.copyWith(fontWeight: FontWeight.w700),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis),
                Text(avviso.contenuto,
                    style: theme.textTheme.bodySmall,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis),
              ],
            ),
          ),
          Icon(Icons.chevron_right_rounded, color: colors.textMuted, size: 20),
        ],
      ),
    );
  }
}

// ── Home tile ─────────────────────────────────────────────────────────────────

class _HomeTile extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color color;
  final int? badge;
  final AppColorTokens colors;
  final VoidCallback onTap;

  const _HomeTile({
    required this.icon,
    required this.label,
    required this.color,
    required this.colors,
    required this.onTap,
    this.badge,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Semantics(
      button: true,
      label: badge != null ? '$label, $badge elementi' : label,
      child: Material(
        color: colors.bgElev,
        borderRadius: BorderRadius.circular(AppSizes.radiusMd),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(AppSizes.radiusMd),
          child: Container(
            constraints: const BoxConstraints(minHeight: AppSizes.homeCardMinHeight),
            decoration: BoxDecoration(
              border: Border.all(color: colors.border),
              borderRadius: BorderRadius.circular(AppSizes.radiusMd),
            ),
            padding: const EdgeInsets.all(AppSizes.cardPad),
            child: Stack(
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Container(
                      width: AppSizes.iconXl,
                      height: AppSizes.iconXl,
                      decoration: BoxDecoration(
                        color: color.withValues(alpha: 0.13),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Icon(icon, size: 22, color: color),
                    ),
                    Text(
                      label,
                      style: theme.textTheme.titleMedium
                          ?.copyWith(fontWeight: FontWeight.w700),
                    ),
                  ],
                ),
                if (badge != null)
                  Positioned(
                    top: 0,
                    right: 0,
                    child: Container(
                      width: 22,
                      height: 22,
                      decoration: BoxDecoration(
                        color: colors.danger,
                        shape: BoxShape.circle,
                      ),
                      child: Center(
                        child: Text(
                          badge! > 9 ? '9+' : badge.toString(),
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
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

// ── Waste peek ────────────────────────────────────────────────────────────────

class _WastePeek extends StatelessWidget {
  final RaccoltaGiorno giorno;
  final AppColorTokens colors;

  const _WastePeek({required this.giorno, required this.colors});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final tipo = giorno.tipi.firstOrNull ?? '';
    final col = AppColors.wasteColor(tipo);
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final label = giorno.data.isAtSameMomentAs(today) ? 'Oggi' : 'Domani';

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
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
              color: col.withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(Icons.delete_outline_rounded, color: col, size: 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Prossima raccolta — $label',
                    style: theme.textTheme.bodySmall),
                Text(giorno.tipi.join(', '),
                    style: theme.textTheme.bodyMedium
                        ?.copyWith(fontWeight: FontWeight.w600)),
              ],
            ),
          ),
          Icon(Icons.chevron_right_rounded,
              color: colors.textFaint, size: 18),
        ],
      ),
    );
  }
}
