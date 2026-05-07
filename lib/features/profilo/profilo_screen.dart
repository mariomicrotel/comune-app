import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
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

    // User info from preferences
    final displayName = prefs.displayName;
    final email = prefs.email;
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
    final first = prefs.firstName;
    final last = prefs.lastName;
    String initials;
    if (first != null && last != null && last.isNotEmpty) {
      initials = '${first[0]}${last[0]}'.toUpperCase();
    } else if (first != null && first.isNotEmpty) {
      initials = first.substring(0, first.length.clamp(1, 2)).toUpperCase();
    } else {
      initials = 'C';
    }

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
                  displayName: displayName,
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

                // ── Gestione consensi (GDPR) ─────────────────────
                _SectionLabel(label: 'Gestione consensi', colors: colors),
                const SizedBox(height: 8),
                _ConsentManagementGroup(prefs: prefs, colors: colors, ref: ref),
                const SizedBox(height: 24),

                // ── Privacy e dati ───────────────────────────────
                _SectionLabel(label: 'Privacy e dati', colors: colors),
                const SizedBox(height: 8),
                _NavGroup(
                  colors: colors,
                  items: [
                    _ActivityItem(
                      icon: Icons.privacy_tip_outlined,
                      label: 'Informativa Privacy',
                      onTap: () => context.push('/privacy-policy'),
                      colors: colors,
                    ),
                    _ActivityItem(
                      icon: Icons.download_outlined,
                      label: 'Esporta i miei dati',
                      onTap: () => _exportData(context, ref, colors),
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
                const SizedBox(height: 24),

                // ── Impostazioni ─────────────────────────────────
                _SectionLabel(label: 'Altro', colors: colors),
                const SizedBox(height: 8),
                _NavGroup(
                  colors: colors,
                  items: [
                    _ActivityItem(
                      icon: Icons.settings_outlined,
                      label: 'Impostazioni',
                      onTap: () => context.push('/profilo/settings'),
                      colors: colors,
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

  // ── GDPR: Data Export (Art. 20) ───────────────────────────────────────────

  void _exportData(BuildContext ctx, WidgetRef ref, AppColorTokens colors) {
    final auth = ref.read(authServiceProvider);
    final json = auth.exportUserDataJson();

    showModalBottomSheet(
      context: ctx,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) {
        return DraggableScrollableSheet(
          initialChildSize: 0.6,
          maxChildSize: 0.9,
          minChildSize: 0.3,
          expand: false,
          builder: (context, scrollCtrl) {
            return Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Center(
                    child: Container(
                      width: 40, height: 4,
                      decoration: BoxDecoration(
                        color: colors.border,
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      Icon(Icons.download_outlined, color: colors.primary),
                      const SizedBox(width: 10),
                      Text(
                        'I tuoi dati (Art. 20 GDPR)',
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Ecco tutti i dati personali memorizzati dall\'app. '
                    'Puoi copiarli negli appunti.',
                    style: TextStyle(color: colors.textMuted, fontSize: 13),
                  ),
                  const SizedBox(height: 16),
                  Expanded(
                    child: Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: colors.chip,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: SingleChildScrollView(
                        controller: scrollCtrl,
                        child: SelectableText(
                          json,
                          style: TextStyle(
                            fontFamily: 'monospace',
                            fontSize: 12,
                            color: colors.text,
                          ),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  SizedBox(
                    width: double.infinity,
                    height: 48,
                    child: ElevatedButton.icon(
                      onPressed: () {
                        Clipboard.setData(ClipboardData(text: json));
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(
                            content: const Text('Dati copiati negli appunti'),
                            backgroundColor: colors.accent,
                          ),
                        );
                        Navigator.pop(context);
                      },
                      icon: const Icon(Icons.copy_rounded, size: 18),
                      label: const Text('Copia negli appunti'),
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  // ── GDPR: Data Deletion (Art. 17) ─────────────────────────────────────────

  void _confirmDelete(
    BuildContext ctx,
    WidgetRef ref,
    AppColorTokens colors,
    PreferencesService prefs,
  ) {
    showDialog(
      context: ctx,
      builder: (_) => AlertDialog(
        title: const Text('Cancella i miei dati'),
        content: const Text(
          'Stai richiedendo la cancellazione di tutti i tuoi dati personali '
          'in conformità all\'Art. 17 del GDPR (diritto all\'oblio).\n\n'
          'Verranno eliminati:\n'
          '• Nome, cognome, email\n'
          '• Preferenze e consensi\n'
          '• Token notifiche\n'
          '• Zona rifiuti\n\n'
          'Questa azione è irreversibile. L\'app tornerà alla schermata iniziale.',
        ),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Annulla')),
          TextButton(
            onPressed: () async {
              final auth = ref.read(authServiceProvider);
              await auth.requestDataDeletion();
              if (ctx.mounted) {
                Navigator.pop(ctx);
                ctx.go('/onboarding');
              }
            },
            child:
                Text('Cancella tutto', style: TextStyle(color: colors.danger)),
          ),
        ],
      ),
    );
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Consent management group (GDPR)
// ═══════════════════════════════════════════════════════════════════════════════

class _ConsentManagementGroup extends StatelessWidget {
  final PreferencesService prefs;
  final AppColorTokens colors;
  final WidgetRef ref;

  const _ConsentManagementGroup({
    required this.prefs,
    required this.colors,
    required this.ref,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final consentDate = prefs.privacyConsentDate;

    return Container(
      decoration: BoxDecoration(
        color: colors.bgElev,
        borderRadius: BorderRadius.circular(AppSizes.radiusMd),
        border: Border.all(color: colors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Privacy consent date info
          if (consentDate != null)
            Padding(
              padding: const EdgeInsets.fromLTRB(18, 14, 18, 4),
              child: Row(
                children: [
                  Icon(Icons.check_circle_outlined,
                      size: 16, color: colors.accent),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'Consenso privacy dato il ${_formatDate(consentDate)}',
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: colors.accent,
                        fontSize: 12,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          Divider(color: colors.border, height: 1, indent: 18, endIndent: 18),

          // Analytics consent
          _ConsentToggleRow(
            icon: Icons.analytics_outlined,
            label: 'Statistiche anonime',
            subtitle: 'Dati anonimi per migliorare l\'app',
            value: prefs.analyticsConsent,
            onChanged: (v) => prefs.setAnalyticsConsent(v),
            colors: colors,
          ),
          Divider(color: colors.border, height: 1, indent: 52),

          // Location consent
          _ConsentToggleRow(
            icon: Icons.location_on_outlined,
            label: 'Geolocalizzazione',
            subtitle: 'Posizione per segnalazioni e mappa',
            value: prefs.locationConsent,
            onChanged: (v) => prefs.setLocationConsent(v),
            colors: colors,
          ),
          Divider(color: colors.border, height: 1, indent: 52),

          // Notifications consent
          _ConsentToggleRow(
            icon: Icons.notifications_outlined,
            label: 'Notifiche push',
            subtitle: 'Avvisi e aggiornamenti dal Comune',
            value: prefs.notificationsConsent,
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
        ],
      ),
    );
  }

  String _formatDate(String isoDate) {
    try {
      final dt = DateTime.parse(isoDate);
      return '${dt.day.toString().padLeft(2, '0')}/'
          '${dt.month.toString().padLeft(2, '0')}/'
          '${dt.year}';
    } catch (_) {
      return isoDate;
    }
  }
}

class _ConsentToggleRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String subtitle;
  final bool value;
  final ValueChanged<bool> onChanged;
  final AppColorTokens colors;

  const _ConsentToggleRow({
    required this.icon,
    required this.label,
    required this.subtitle,
    required this.value,
    required this.onChanged,
    required this.colors,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.symmetric(
          horizontal: AppSizes.padX, vertical: 12),
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
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(label, style: theme.textTheme.bodyLarge),
                Text(
                  subtitle,
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: colors.textMuted,
                    fontSize: 12,
                  ),
                ),
              ],
            ),
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

// ═══════════════════════════════════════════════════════════════════════════════
// Avatar card
// ═══════════════════════════════════════════════════════════════════════════════

class _AvatarCard extends StatelessWidget {
  final String initials;
  final String displayName;
  final String? email;
  final String? zonaName;
  final AppColorTokens colors;

  const _AvatarCard({
    required this.initials,
    required this.displayName,
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
                Text(displayName,
                    style: theme.textTheme.titleLarge
                        ?.copyWith(fontWeight: FontWeight.w700)),
                if (email != null && email!.isNotEmpty) ...[
                  const SizedBox(height: 2),
                  Text(email!, style: theme.textTheme.bodySmall),
                ],
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

// ═══════════════════════════════════════════════════════════════════════════════
// Shared internal widgets
// ═══════════════════════════════════════════════════════════════════════════════

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
