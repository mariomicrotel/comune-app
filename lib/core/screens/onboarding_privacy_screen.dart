import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../providers/core_providers.dart';
import '../config/app_config.dart';
import '../constants/app_colors.dart';
import '../../widgets/primary_button.dart';

/// Onboarding multi-step:
///   Step 0 — Benvenuto (welcome)
///   Step 1 — Registrazione (nome, cognome, email opzionale)
///   Step 2 — Consensi GDPR granulari
class OnboardingPrivacyScreen extends ConsumerStatefulWidget {
  const OnboardingPrivacyScreen({super.key});

  @override
  ConsumerState<OnboardingPrivacyScreen> createState() =>
      _OnboardingPrivacyScreenState();
}

class _OnboardingPrivacyScreenState
    extends ConsumerState<OnboardingPrivacyScreen> {
  final _pageController = PageController();
  int _step = 0;

  // Step 1 — Registration fields
  final _firstNameCtrl = TextEditingController();
  final _lastNameCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _formKey = GlobalKey<FormState>();

  // Step 2 — GDPR consents
  bool _privacyAccepted = false;
  bool _notifiche = false;
  bool _analytics = false;
  bool _location = false;
  bool _loading = false;

  @override
  void dispose() {
    _pageController.dispose();
    _firstNameCtrl.dispose();
    _lastNameCtrl.dispose();
    _emailCtrl.dispose();
    super.dispose();
  }

  void _goToStep(int step) {
    setState(() => _step = step);
    _pageController.animateToPage(
      step,
      duration: const Duration(milliseconds: 350),
      curve: Curves.easeInOut,
    );
  }

  void _nextStep() => _goToStep(_step + 1);

  Future<void> _complete() async {
    if (!_privacyAccepted) return;
    setState(() => _loading = true);

    final prefs = ref.read(preferencesServiceProvider);

    // Save user data
    final firstName = _firstNameCtrl.text.trim();
    final lastName = _lastNameCtrl.text.trim();
    final email = _emailCtrl.text.trim();

    if (firstName.isNotEmpty) await prefs.setFirstName(firstName);
    if (lastName.isNotEmpty) await prefs.setLastName(lastName);
    if (email.isNotEmpty) await prefs.setEmail(email);

    // Save GDPR consents
    await prefs.setPrivacyConsent(true);
    await prefs.setNotificationsConsent(_notifiche);
    await prefs.setAnalyticsConsent(_analytics);
    await prefs.setLocationConsent(_location);
    await prefs.setOnboardingComplete(true);

    // Request notification permissions if consented
    if (_notifiche) {
      await ref.read(notificationServiceProvider).requestPermissionAndRegister();
    }

    if (mounted) context.go('/home');
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colors = AppColors.resolve(
      theme.brightness,
      ref.watch(activePaletteProvider),
    );

    return Scaffold(
      backgroundColor: colors.bg,
      body: SafeArea(
        child: Column(
          children: [
            // ── Progress bar ───────────────────────────────────
            Padding(
              padding: const EdgeInsets.fromLTRB(24, 16, 24, 0),
              child: _StepProgressBar(
                step: _step,
                totalSteps: 3,
                activeColor: colors.primary,
                inactiveColor: colors.border,
              ),
            ),
            const SizedBox(height: 8),

            // ── Pages ──────────────────────────────────────────
            Expanded(
              child: PageView(
                controller: _pageController,
                physics: const NeverScrollableScrollPhysics(),
                children: [
                  _WelcomeStep(
                    colors: colors,
                    onContinue: _nextStep,
                  ),
                  _RegistrationStep(
                    colors: colors,
                    formKey: _formKey,
                    firstNameCtrl: _firstNameCtrl,
                    lastNameCtrl: _lastNameCtrl,
                    emailCtrl: _emailCtrl,
                    onBack: () => _goToStep(0),
                    onContinue: () {
                      if (_formKey.currentState!.validate()) {
                        _nextStep();
                      }
                    },
                  ),
                  _ConsentsStep(
                    colors: colors,
                    privacyAccepted: _privacyAccepted,
                    notifiche: _notifiche,
                    analytics: _analytics,
                    location: _location,
                    loading: _loading,
                    onPrivacyChanged: (v) =>
                        setState(() => _privacyAccepted = v),
                    onNotificheChanged: (v) =>
                        setState(() => _notifiche = v),
                    onAnalyticsChanged: (v) =>
                        setState(() => _analytics = v),
                    onLocationChanged: (v) =>
                        setState(() => _location = v),
                    onBack: () => _goToStep(1),
                    onComplete: _complete,
                    onShowPrivacyPolicy: () => context.push('/privacy-policy'),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Step progress bar
// ═══════════════════════════════════════════════════════════════════════════════

class _StepProgressBar extends StatelessWidget {
  final int step;
  final int totalSteps;
  final Color activeColor;
  final Color inactiveColor;

  const _StepProgressBar({
    required this.step,
    required this.totalSteps,
    required this.activeColor,
    required this.inactiveColor,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: List.generate(totalSteps, (i) {
        final isActive = i <= step;
        return Expanded(
          child: Container(
            margin: EdgeInsets.only(right: i < totalSteps - 1 ? 6 : 0),
            height: 5,
            decoration: BoxDecoration(
              color: isActive ? activeColor : inactiveColor,
              borderRadius: BorderRadius.circular(99),
            ),
          ),
        );
      }),
    );
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Step 0 — Welcome
// ═══════════════════════════════════════════════════════════════════════════════

class _WelcomeStep extends StatelessWidget {
  final AppColorTokens colors;
  final VoidCallback onContinue;

  const _WelcomeStep({required this.colors, required this.onContinue});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Spacer(flex: 2),
          // Icon
          Container(
            width: 72,
            height: 72,
            decoration: BoxDecoration(
              color: colors.primarySoft,
              borderRadius: BorderRadius.circular(20),
            ),
            child: Icon(Icons.location_city_rounded,
                size: 40, color: colors.primary),
          ),
          const SizedBox(height: 28),
          Text(
            'Benvenuto in',
            style: theme.textTheme.headlineSmall?.copyWith(
              color: colors.textMuted,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            AppConfig.comuneName,
            style: theme.textTheme.displaySmall?.copyWith(
              fontWeight: FontWeight.w800,
              color: colors.text,
            ),
          ),
          const SizedBox(height: 16),
          Text(
            'L\'app ufficiale per restare in contatto con il tuo Comune. '
            'Ricevi avvisi, segnala problemi, consulta eventi e servizi.',
            style: theme.textTheme.bodyLarge?.copyWith(
              color: colors.textMuted,
              height: 1.5,
            ),
          ),
          const Spacer(flex: 3),
          PrimaryButton(
            label: 'Inizia',
            onPressed: onContinue,
          ),
          const SizedBox(height: 16),
        ],
      ),
    );
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Step 1 — Registration (nome, cognome, email opzionale)
// ═══════════════════════════════════════════════════════════════════════════════

class _RegistrationStep extends StatelessWidget {
  final AppColorTokens colors;
  final GlobalKey<FormState> formKey;
  final TextEditingController firstNameCtrl;
  final TextEditingController lastNameCtrl;
  final TextEditingController emailCtrl;
  final VoidCallback onBack;
  final VoidCallback onContinue;

  const _RegistrationStep({
    required this.colors,
    required this.formKey,
    required this.firstNameCtrl,
    required this.lastNameCtrl,
    required this.emailCtrl,
    required this.onBack,
    required this.onContinue,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.all(24),
      child: Form(
        key: formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const SizedBox(height: 8),
            Text(
              'Come ti chiami?',
              style: theme.textTheme.headlineSmall?.copyWith(
                fontWeight: FontWeight.w700,
                color: colors.text,
              ),
            ),
            const SizedBox(height: 6),
            Text(
              'Questi dati restano sul tuo dispositivo e non vengono '
              'condivisi con terzi. L\'email è opzionale.',
              style: theme.textTheme.bodyMedium?.copyWith(
                color: colors.textMuted,
                height: 1.4,
              ),
            ),
            const SizedBox(height: 28),
            // Nome
            _StyledField(
              controller: firstNameCtrl,
              label: 'Nome *',
              hint: 'Il tuo nome',
              colors: colors,
              validator: (v) {
                if (v == null || v.trim().isEmpty) return 'Inserisci il nome';
                if (v.trim().length < 2) return 'Minimo 2 caratteri';
                return null;
              },
              textInputAction: TextInputAction.next,
            ),
            const SizedBox(height: 16),
            // Cognome
            _StyledField(
              controller: lastNameCtrl,
              label: 'Cognome',
              hint: 'Il tuo cognome (opzionale)',
              colors: colors,
              textInputAction: TextInputAction.next,
            ),
            const SizedBox(height: 16),
            // Email
            _StyledField(
              controller: emailCtrl,
              label: 'Email',
              hint: 'name@email.it (opzionale)',
              colors: colors,
              keyboardType: TextInputType.emailAddress,
              textInputAction: TextInputAction.done,
              validator: (v) {
                if (v == null || v.trim().isEmpty) return null; // opzionale
                final emailRegex =
                    RegExp(r'^[\w\-.]+@([\w\-]+\.)+[\w\-]{2,}$');
                if (!emailRegex.hasMatch(v.trim())) {
                  return 'Email non valida';
                }
                return null;
              },
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Icon(Icons.lock_outlined, size: 14, color: colors.textFaint),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(
                    'I tuoi dati sono conservati solo localmente sul dispositivo.',
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: colors.textFaint,
                      fontSize: 12,
                    ),
                  ),
                ),
              ],
            ),
            const Spacer(),
            Row(
              children: [
                TextButton.icon(
                  onPressed: onBack,
                  icon: const Icon(Icons.arrow_back_rounded, size: 18),
                  label: const Text('Indietro'),
                ),
                const Spacer(),
              ],
            ),
            const SizedBox(height: 8),
            PrimaryButton(
              label: 'Continua',
              onPressed: onContinue,
            ),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }
}

class _StyledField extends StatelessWidget {
  final TextEditingController controller;
  final String label;
  final String hint;
  final AppColorTokens colors;
  final String? Function(String?)? validator;
  final TextInputType? keyboardType;
  final TextInputAction? textInputAction;

  const _StyledField({
    required this.controller,
    required this.label,
    required this.hint,
    required this.colors,
    this.validator,
    this.keyboardType,
    this.textInputAction,
  });

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      controller: controller,
      validator: validator,
      keyboardType: keyboardType,
      textInputAction: textInputAction,
      decoration: InputDecoration(
        labelText: label,
        hintText: hint,
        filled: true,
        fillColor: colors.bgElev,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide(color: colors.border),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide(color: colors.border),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide(color: colors.primary, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide(color: colors.danger),
        ),
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      ),
    );
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Step 2 — GDPR Consents
// ═══════════════════════════════════════════════════════════════════════════════

class _ConsentsStep extends StatelessWidget {
  final AppColorTokens colors;
  final bool privacyAccepted;
  final bool notifiche;
  final bool analytics;
  final bool location;
  final bool loading;
  final ValueChanged<bool> onPrivacyChanged;
  final ValueChanged<bool> onNotificheChanged;
  final ValueChanged<bool> onAnalyticsChanged;
  final ValueChanged<bool> onLocationChanged;
  final VoidCallback onBack;
  final VoidCallback onComplete;
  final VoidCallback onShowPrivacyPolicy;

  const _ConsentsStep({
    required this.colors,
    required this.privacyAccepted,
    required this.notifiche,
    required this.analytics,
    required this.location,
    required this.loading,
    required this.onPrivacyChanged,
    required this.onNotificheChanged,
    required this.onAnalyticsChanged,
    required this.onLocationChanged,
    required this.onBack,
    required this.onComplete,
    required this.onShowPrivacyPolicy,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const SizedBox(height: 8),
          Text(
            'Privacy e consensi',
            style: theme.textTheme.headlineSmall?.copyWith(
              fontWeight: FontWeight.w700,
              color: colors.text,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            'In conformità al Regolamento UE 2016/679 (GDPR), '
            'ti chiediamo di esprimere i tuoi consensi. '
            'Potrai modificarli in qualsiasi momento dal Profilo.',
            style: theme.textTheme.bodyMedium?.copyWith(
              color: colors.textMuted,
              height: 1.4,
            ),
          ),
          const SizedBox(height: 24),

          Expanded(
            child: SingleChildScrollView(
              child: Column(
                children: [
                  // ── Mandatory: Privacy consent ─────────────────
                  _ConsentTile(
                    icon: Icons.privacy_tip_outlined,
                    title: 'Trattamento dati personali *',
                    subtitle:
                        'Acconsento al trattamento dei miei dati come descritto '
                        'nell\'informativa privacy (Art. 6, GDPR).',
                    value: privacyAccepted,
                    onChanged: onPrivacyChanged,
                    required_: true,
                    colors: colors,
                    trailing: TextButton(
                      onPressed: onShowPrivacyPolicy,
                      child: Text(
                        'Leggi',
                        style: TextStyle(color: colors.primary),
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),

                  // ── Optional: Notifications ────────────────────
                  _ConsentTile(
                    icon: Icons.notifications_outlined,
                    title: 'Notifiche push',
                    subtitle:
                        'Ricevi avvisi urgenti, aggiornamenti sulle tue '
                        'segnalazioni e comunicazioni dal Comune.',
                    value: notifiche,
                    onChanged: onNotificheChanged,
                    colors: colors,
                  ),
                  const SizedBox(height: 12),

                  // ── Optional: Analytics ────────────────────────
                  _ConsentTile(
                    icon: Icons.analytics_outlined,
                    title: 'Statistiche anonime',
                    subtitle:
                        'Aiutaci a migliorare l\'app inviando dati anonimi '
                        'sull\'utilizzo (nessun dato personale).',
                    value: analytics,
                    onChanged: onAnalyticsChanged,
                    colors: colors,
                  ),
                  const SizedBox(height: 12),

                  // ── Optional: Location ─────────────────────────
                  _ConsentTile(
                    icon: Icons.location_on_outlined,
                    title: 'Geolocalizzazione',
                    subtitle:
                        'Permetti l\'accesso alla posizione per le '
                        'segnalazioni e la mappa dei servizi.',
                    value: location,
                    onChanged: onLocationChanged,
                    colors: colors,
                  ),
                ],
              ),
            ),
          ),

          const SizedBox(height: 12),
          if (!privacyAccepted)
            Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: Row(
                children: [
                  Icon(Icons.info_outline, size: 14, color: colors.warn),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      'Il consenso al trattamento dati è obbligatorio per continuare.',
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: colors.warn,
                        fontSize: 12,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          Row(
            children: [
              TextButton.icon(
                onPressed: onBack,
                icon: const Icon(Icons.arrow_back_rounded, size: 18),
                label: const Text('Indietro'),
              ),
              const Spacer(),
            ],
          ),
          const SizedBox(height: 8),
          PrimaryButton(
            label: 'Accetta e continua',
            onPressed: privacyAccepted ? onComplete : null,
            loading: loading,
          ),
          const SizedBox(height: 16),
        ],
      ),
    );
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Consent tile widget
// ═══════════════════════════════════════════════════════════════════════════════

class _ConsentTile extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  final bool value;
  final ValueChanged<bool> onChanged;
  final bool required_;
  final AppColorTokens colors;
  final Widget? trailing;

  const _ConsentTile({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.value,
    required this.onChanged,
    required this.colors,
    this.required_ = false,
    this.trailing,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final borderColor = required_ && !value ? colors.warn : colors.border;

    return Semantics(
      toggled: value,
      label: '$title — $subtitle',
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: colors.bgElev,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: borderColor, width: required_ && !value ? 1.5 : 1),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    color: required_ ? colors.primarySoft : colors.chip,
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(icon, size: 20, color: colors.primary),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(title,
                                style: theme.textTheme.titleSmall?.copyWith(
                                  fontWeight: FontWeight.w600,
                                )),
                          ),
                          if (trailing != null) trailing!,
                        ],
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
            Padding(
              padding: const EdgeInsets.only(left: 48, top: 4),
              child: Text(
                subtitle,
                style: theme.textTheme.bodySmall?.copyWith(
                  color: colors.textMuted,
                  height: 1.4,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
