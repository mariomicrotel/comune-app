import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/config/app_config.dart';
import '../../core/constants/app_colors.dart';
import '../../core/constants/app_sizes.dart';
import '../../core/providers/core_providers.dart';
import '../../services/preferences_service.dart';
import '../../features/rifiuti/rifiuti_notifier.dart';
import '../../features/segnalazioni/segnalazioni_notifier.dart';

class ProfiloScreen extends ConsumerWidget {
  const ProfiloScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final colors = AppColors.resolve(theme.brightness, ref.watch(activePaletteProvider));
    final prefs = ref.watch(preferencesServiceProvider);

    // User info
    final firstName = prefs.firstName ?? 'Cittadino';
    final email = 'utente@email.it'; // placeholder
    final notificationsOn = prefs.notificationsConsent;
    final darkMode = prefs.themeMode == 'dark';

    // Zona rifiuti name
    final zoneAsync = ref.watch(zoneRifiutiProvider);
    final selectedId = ref.watch(selectedZonaIdProvider);
    final zonaName = zoneAsync.maybeWhen(
      data: (zone) => zone.where((z) => z.id == selectedId).firstOrNull?.nome,
      orElse: () => null,
    );

    // Activity counts
    final segnalazioniAsync = ref.watch(mySegnalazioniProvider);
    final segnalazioniCount = segnalazioniAsync.maybeWhen(
        data: (l) => l.length, orElse: () => 0);

    // Initials
    final parts = firstName.trim().split(' ');
    final initials = parts.length >= 2
        ? '${parts[0][0]}${parts[1][0]}'.toUpperCase()
        : firstName.substring(0, firstName.length.clamp(1, 2)).toUpperCase();

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            pinned: true,
            backgroundColor: theme.scaffoldBackgroundColor,
            surfaceTintColor: Colors.transparent,
            automaticallyImplyLeading: false,
            title: Padding(
              padding: const EdgeInsets.only(left: AppSizes.padX - 16),
              child: Text('Profilo', style: theme.textTheme.headlineLarge),
            ),
          ),
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(
                AppSizes.padX, 4, AppSizes.padX, 100),
            sliver: SliverList(
              delegate: SliverChildListDelegate([
                // ── Avatar card ───────────────────────────────────
                _AvatarCard(
                  initials: initials,
                  firstName: firstName,
                  email: email,
                  zonaName: zonaName,
                  colors: colors,
                ),
                const SizedBox(height: 28),

                // ── Le mie attività ───────────────────────────────
                _SectionLabel(label: 'Le mie attività', colors: colors),
                const SizedBox(height: 8),
                _NavGroup(
                  colors: colors,
                  items: [
                    _ActivityItem(
                      icon: Icons.report_outlined,
                      label: 'Le mie segnalazioni',
                      badge: segnalazioniCount > 0
                          ? '$segnalazioniCount'
                          : null,
                      onTap: () => context.push('/segnalazioni'),
                      colors: colors,
                    ),
                    _ActivityItem(
                      icon: Icons.notifications_outlined,
                      label: 'Notifiche ricevute',
                      onTap: () => context.push('/notifiche'),
                      colors: colors,
                    ),
                    _ActivityItem(
                      icon: Icons.poll_outlined,
                      label: 'I miei sondaggi',
                      badge: '3 votati',
                      onTap: () => context.push('/sondaggi'),
                      colors: colors,
                    ),
                  ],
                ),
                const SizedBox(height: 24),

                // ── Preferenze ────────────────────────────────────
                _SectionLabel(label: 'Preferenze', colors: colors),
                const SizedBox(height: 8),
                _PreferenceGroup(
                  colors: colors,
                  items: [
                    _PreferenceToggle(
                      icon: Icons.notifications_outlined,
                      label: 'Notifiche push',
                      value: notificationsOn,
                      onChanged: (v) async {
                        await prefs.setNotificationsConsent(v);
                        if (v) {
                          await ref
                              .read(notificationServiceProvider)
                              .requestPermissionAndRegister();
                        }
                      },
                      colors: colors,
                    ),
                    _PreferenceToggle(
                      icon: Icons.dark_mode_outlined,
                      label: 'Tema scuro',
                      value: darkMode,
                      onChanged: (v) =>
                          prefs.setThemeMode(v ? 'dark' : 'light'),
                      colors: colors,
                    ),
                  ],
                ),
                const SizedBox(height: 24),

                // ── Altro ─────────────────────────────────────────
                _SectionLabel(label: 'Altro', colors: colors),
                const SizedBox(height: 8),
                _NavGroup(
                  colors: colors,
                  items: [
                    _ActivityItem(
                      icon: Icons.privacy_tip_outlined,
                      label: 'Privacy Policy',
                      onTap: () => context.push('/onboarding'),
                      colors: colors,
                    ),
                    _ActivityItem(
                      icon: Icons.delete_forever_outlined,
                      label: 'Cancella i miei dati',
                      onTap: () => _confirmDelete(context, ref, colors, prefs),
                      colors: colors,
                      destructive: true,
                    ),
                  ],
                ),
                const SizedBox(height: 32),

                // ── Footer ────────────────────────────────────────
                Center(
                  child: Column(
                    children: [
                      Text(
                        'App Comune · v1.0.0',
                        style: TextStyle(
                            fontSize: 12, color: colors.textFaint),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        'Powered by ${AppConfig.comuneName}',
                        style: TextStyle(
                            fontSize: 12, color: colors.textFaint),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
              ]),
            ),
          ),
        ],
      ),
    );
  }

  void _confirmDelete(
    BuildContext ctx,
    WidgetRef ref,
    AppColorTokens colors,
    PreferencesService prefs,
  ) {
    showDialog(
      context: ctx,
      builder: (_) => AlertDialog(
        title: const Text('Cancella dati'),
        content: const Text(
            'Tutti i tuoi dati locali (preferenze, token, zona rifiuti) verranno eliminati.'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Annulla')),
          TextButton(
            onPressed: () async {
              await prefs.clearAll();
              if (ctx.mounted) {
                Navigator.pop(ctx);
                ctx.go('/onboarding');
              }
            },
            child:
                Text('Cancella', style: TextStyle(color: colors.danger)),
          ),
        ],
      ),
    );
  }
}

// ── Avatar card ───────────────────────────────────────────────────────────────

class _AvatarCard extends StatelessWidget {
  final String initials;
  final String firstName;
  final String email;
  final String? zonaName;
  final AppColorTokens colors;

  const _AvatarCard({
    required this.initials,
    required this.firstName,
    required this.email,
    required this.zonaName,
    required this.colors,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.all(AppSizes.cardPad),
      decoration: BoxDecoration(
        color: colors.bgElev,
        borderRadius: BorderRadius.circular(AppSizes.radiusMd),
        border: Border.all(color: colors.border),
      ),
      child: Row(
        children: [
          // Avatar circle with initials
          Container(
            width: 56,
            height: 56,
            decoration: BoxDecoration(
              color: colors.primary,
              shape: BoxShape.circle,
            ),
            child: Center(
              child: Text(
                initials,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 20,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(firstName,
                    style: theme.textTheme.titleLarge
                        ?.copyWith(fontWeight: FontWeight.w700)),
                const SizedBox(height: 2),
                Text(email, style: theme.textTheme.bodySmall),
                if (zonaName != null) ...[
                  const SizedBox(height: 2),
                  Text(zonaName!,
                      style: theme.textTheme.bodySmall
                          ?.copyWith(color: colors.textFaint)),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// ── Section label ─────────────────────────────────────────────────────────────

class _SectionLabel extends StatelessWidget {
  final String label;
  final AppColorTokens colors;

  const _SectionLabel({required this.label, required this.colors});

  @override
  Widget build(BuildContext context) {
    return Text(
      label,
      style: Theme.of(context).textTheme.titleLarge,
    );
  }
}

// ── Nav group ─────────────────────────────────────────────────────────────────

class _NavGroup extends StatelessWidget {
  final List<Widget> items;
  final AppColorTokens colors;

  const _NavGroup({required this.items, required this.colors});

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: colors.bgElev,
        borderRadius: BorderRadius.circular(AppSizes.radiusMd),
        border: Border.all(color: colors.border),
      ),
      child: Column(
        children: items.asMap().entries.map((e) {
          final isLast = e.key == items.length - 1;
          return Column(
            children: [
              e.value,
              if (!isLast)
                Divider(color: colors.border, height: 1, indent: 52),
            ],
          );
        }).toList(),
      ),
    );
  }
}

class _ActivityItem extends StatelessWidget {
  final IconData icon;
  final String label;
  final String? badge;
  final VoidCallback onTap;
  final AppColorTokens colors;
  final bool destructive;

  const _ActivityItem({
    required this.icon,
    required this.label,
    required this.onTap,
    required this.colors,
    this.badge,
    this.destructive = false,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final fg = destructive ? colors.danger : colors.text;

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(AppSizes.radiusMd),
      child: Padding(
        padding: const EdgeInsets.symmetric(
            horizontal: AppSizes.padX, vertical: 14),
        child: Row(
          children: [
            Container(
              width: 32,
              height: 32,
              decoration: BoxDecoration(
                color: destructive ? colors.dangerSoft : colors.primarySoft,
                borderRadius: BorderRadius.circular(9),
              ),
              child: Icon(icon,
                  size: 17,
                  color: destructive ? colors.danger : colors.primary),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Text(
                label,
                style: theme.textTheme.bodyLarge?.copyWith(color: fg),
              ),
            ),
            if (badge != null) ...[
              Text(
                badge!,
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: colors.textMuted,
                ),
              ),
              const SizedBox(width: 4),
            ],
            Icon(Icons.chevron_right_rounded,
                size: 18, color: colors.textFaint),
          ],
        ),
      ),
    );
  }
}

// ── Preference group ──────────────────────────────────────────────────────────

class _PreferenceGroup extends StatelessWidget {
  final List<Widget> items;
  final AppColorTokens colors;

  const _PreferenceGroup({required this.items, required this.colors});

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: colors.bgElev,
        borderRadius: BorderRadius.circular(AppSizes.radiusMd),
        border: Border.all(color: colors.border),
      ),
      child: Column(
        children: items.asMap().entries.map((e) {
          final isLast = e.key == items.length - 1;
          return Column(
            children: [
              e.value,
              if (!isLast)
                Divider(color: colors.border, height: 1, indent: 52),
            ],
          );
        }).toList(),
      ),
    );
  }
}

class _PreferenceToggle extends StatelessWidget {
  final IconData icon;
  final String label;
  final bool value;
  final ValueChanged<bool> onChanged;
  final AppColorTokens colors;

  const _PreferenceToggle({
    required this.icon,
    required this.label,
    required this.value,
    required this.onChanged,
    required this.colors,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding:
          const EdgeInsets.symmetric(horizontal: AppSizes.padX, vertical: 10),
      child: Row(
        children: [
          Container(
            width: 32,
            height: 32,
            decoration: BoxDecoration(
              color: colors.primarySoft,
              borderRadius: BorderRadius.circular(9),
            ),
            child: Icon(icon, size: 17, color: colors.primary),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Text(label, style: theme.textTheme.bodyLarge),
          ),
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
