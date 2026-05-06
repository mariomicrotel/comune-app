import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/config/app_config.dart';
import '../../core/constants/app_colors.dart';
import '../../core/constants/app_sizes.dart';
import '../../core/providers/core_providers.dart';

class SettingsScreen extends ConsumerWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final colors = AppColors.resolve(theme.brightness, ref.watch(activePaletteProvider));
    final prefs = ref.watch(preferencesServiceProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Impostazioni')),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(AppSizes.padX, 16, AppSizes.padX, 100),
        children: [
          // ── Tema ─────────────────────────────────────────────────────────
          _SectionLabel(label: 'Aspetto', colors: colors),
          const SizedBox(height: 8),
          _Group(
            colors: colors,
            children: [
              _ToggleRow(
                icon: Icons.dark_mode_outlined,
                label: 'Tema scuro',
                value: prefs.themeMode == 'dark',
                colors: colors,
                onChanged: (v) => prefs.setThemeMode(v ? 'dark' : 'light'),
              ),
            ],
          ),
          const SizedBox(height: 24),

          // ── Notifiche ─────────────────────────────────────────────────────
          _SectionLabel(label: 'Notifiche', colors: colors),
          const SizedBox(height: 8),
          _Group(
            colors: colors,
            children: [
              _ToggleRow(
                icon: Icons.notifications_outlined,
                label: 'Notifiche push',
                value: prefs.notificationsConsent,
                colors: colors,
                onChanged: (v) async {
                  await prefs.setNotificationsConsent(v);
                  if (v) {
                    await ref
                        .read(notificationServiceProvider)
                        .requestPermissionAndRegister();
                  }
                },
              ),
              _ToggleRow(
                icon: Icons.campaign_rounded,
                label: 'Avvisi urgenti',
                value: true,
                colors: colors,
                onChanged: (_) {},
              ),
              _ToggleRow(
                icon: Icons.event_outlined,
                label: 'Nuovi eventi',
                value: false,
                colors: colors,
                onChanged: (_) {},
              ),
            ],
          ),
          const SizedBox(height: 24),

          // ── Zona rifiuti ──────────────────────────────────────────────────
          _SectionLabel(label: 'Raccolta rifiuti', colors: colors),
          const SizedBox(height: 8),
          _Group(
            colors: colors,
            children: [
              _ToggleRow(
                icon: Icons.alarm_outlined,
                label: 'Promemoria sera prima',
                value: false,
                colors: colors,
                onChanged: (_) {},
              ),
            ],
          ),
          const SizedBox(height: 24),

          // ── Informazioni ──────────────────────────────────────────────────
          _SectionLabel(label: 'Informazioni', colors: colors),
          const SizedBox(height: 8),
          _Group(
            colors: colors,
            children: [
              _NavRow(
                icon: Icons.info_outline_rounded,
                label: 'Versione app',
                trailing: 'v1.0.0',
                colors: colors,
                onTap: () {},
              ),
              _NavRow(
                icon: Icons.privacy_tip_outlined,
                label: 'Privacy Policy',
                colors: colors,
                onTap: () {},
              ),
              _NavRow(
                icon: Icons.description_outlined,
                label: 'Termini di servizio',
                colors: colors,
                onTap: () {},
              ),
            ],
          ),
          const SizedBox(height: 32),

          Center(
            child: Text(
              'App Comune · ${AppConfig.comuneName}',
              style: TextStyle(fontSize: 12, color: colors.textFaint),
            ),
          ),
        ],
      ),
    );
  }
}

// ── Helpers ───────────────────────────────────────────────────────────────────

class _SectionLabel extends StatelessWidget {
  final String label;
  final AppColorTokens colors;
  const _SectionLabel({required this.label, required this.colors});

  @override
  Widget build(BuildContext context) =>
      Text(label, style: Theme.of(context).textTheme.titleLarge);
}

class _Group extends StatelessWidget {
  final List<Widget> children;
  final AppColorTokens colors;
  const _Group({required this.children, required this.colors});

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: colors.bgElev,
        borderRadius: BorderRadius.circular(AppSizes.radiusMd),
        border: Border.all(color: colors.border),
      ),
      child: Column(
        children: children.asMap().entries.map((e) {
          final isLast = e.key == children.length - 1;
          return Column(
            children: [
              e.value,
              if (!isLast) Divider(color: colors.border, height: 1, indent: 52),
            ],
          );
        }).toList(),
      ),
    );
  }
}

class _ToggleRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final bool value;
  final ValueChanged<bool> onChanged;
  final AppColorTokens colors;
  const _ToggleRow({
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
          Expanded(child: Text(label, style: theme.textTheme.bodyLarge)),
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

class _NavRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String? trailing;
  final VoidCallback onTap;
  final AppColorTokens colors;
  const _NavRow({
    required this.icon,
    required this.label,
    required this.onTap,
    required this.colors,
    this.trailing,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
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
                color: colors.primarySoft,
                borderRadius: BorderRadius.circular(9),
              ),
              child: Icon(icon, size: 17, color: colors.primary),
            ),
            const SizedBox(width: 14),
            Expanded(
                child: Text(label, style: theme.textTheme.bodyLarge)),
            if (trailing != null)
              Text(trailing!,
                  style: TextStyle(
                      fontSize: 13, color: colors.textMuted)),
            const SizedBox(width: 4),
            Icon(Icons.chevron_right_rounded,
                size: 18, color: colors.textFaint),
          ],
        ),
      ),
    );
  }
}
