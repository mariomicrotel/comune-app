import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';
import '../providers/core_providers.dart';
import '../config/app_config.dart';
import '../../widgets/primary_button.dart';

class OnboardingPrivacyScreen extends ConsumerStatefulWidget {
  const OnboardingPrivacyScreen({super.key});

  @override
  ConsumerState<OnboardingPrivacyScreen> createState() =>
      _OnboardingPrivacyScreenState();
}

class _OnboardingPrivacyScreenState
    extends ConsumerState<OnboardingPrivacyScreen> {
  bool _privacyAccepted = false;
  bool _notifiche = false;
  bool _loading = false;

  Future<void> _continue() async {
    if (!_privacyAccepted) return;
    setState(() => _loading = true);
    final prefs = ref.read(preferencesServiceProvider);
    await prefs.setPrivacyConsent(true);
    await prefs.setNotificationsConsent(_notifiche);
    if (_notifiche) {
      await ref.read(notificationServiceProvider).requestPermissionAndRegister();
    }
    if (mounted) context.go('/home');
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Spacer(),
              // Icon
              Container(
                width: 64,
                height: 64,
                decoration: BoxDecoration(
                  color: theme.colorScheme.primaryContainer,
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Icon(Icons.location_city_rounded,
                    size: 36, color: theme.colorScheme.primary),
              ),
              const SizedBox(height: 24),
              Text('Benvenuto in\n${AppConfig.comuneName}',
                  style: theme.textTheme.displaySmall),
              const SizedBox(height: 12),
              Text(
                'Per utilizzare l\'app è necessario accettare la Privacy Policy.',
                style: theme.textTheme.bodyMedium,
              ),
              const Spacer(),
              // Privacy toggle
              _ConsentTile(
                icon: Icons.privacy_tip_outlined,
                title: 'Privacy Policy',
                subtitle: 'Accetto il trattamento dei dati personali',
                value: _privacyAccepted,
                onChanged: (v) => setState(() => _privacyAccepted = v),
                trailing: TextButton(
                  onPressed: () => launchUrl(
                    Uri.parse('https://comune.example.it/privacy'),
                    mode: LaunchMode.externalApplication,
                  ),
                  child: const Text('Leggi'),
                ),
              ),
              const SizedBox(height: 12),
              _ConsentTile(
                icon: Icons.notifications_outlined,
                title: 'Notifiche push',
                subtitle: 'Ricevi avvisi e aggiornamenti dal Comune',
                value: _notifiche,
                onChanged: (v) => setState(() => _notifiche = v),
              ),
              const SizedBox(height: 32),
              PrimaryButton(
                label: 'Accetta e continua',
                onPressed: _privacyAccepted ? _continue : null,
                loading: _loading,
              ),
              const SizedBox(height: 16),
            ],
          ),
        ),
      ),
    );
  }
}

class _ConsentTile extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  final bool value;
  final ValueChanged<bool> onChanged;
  final Widget? trailing;

  const _ConsentTile({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.value,
    required this.onChanged,
    this.trailing,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: theme.colorScheme.outline),
      ),
      child: Row(
        children: [
          Icon(icon, size: 22, color: theme.colorScheme.primary),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: theme.textTheme.titleMedium),
                Text(subtitle,
                    style: theme.textTheme.bodySmall),
              ],
            ),
          ),
          if (trailing != null) trailing!,
          Switch(value: value, onChanged: onChanged),
        ],
      ),
    );
  }
}
