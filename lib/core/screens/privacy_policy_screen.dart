import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../constants/app_colors.dart';
import '../constants/app_sizes.dart';
import '../config/app_config.dart';
import '../providers/core_providers.dart';

/// Informativa privacy in-app ai sensi del Reg. UE 2016/679 (GDPR).
/// Accessibile dall'onboarding e dal profilo utente.
class PrivacyPolicyScreen extends ConsumerWidget {
  const PrivacyPolicyScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final colors = AppColors.resolve(
      theme.brightness,
      ref.watch(activePaletteProvider),
    );

    return Scaffold(
      appBar: AppBar(
        title: const Text('Informativa Privacy'),
        backgroundColor: colors.bgElev,
        surfaceTintColor: Colors.transparent,
      ),
      backgroundColor: colors.bg,
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSizes.padX),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _section(theme, colors, 'Informativa sul trattamento dei dati personali',
              'Ai sensi degli artt. 13 e 14 del Regolamento (UE) 2016/679 '
              '("GDPR"), il ${AppConfig.comuneName} fornisce la seguente informativa '
              'sul trattamento dei dati personali effettuato tramite la presente '
              'applicazione mobile.',
            ),

            _heading(theme, colors, '1. Titolare del trattamento'),
            _body(theme, colors,
              'Il Titolare del trattamento è il ${AppConfig.comuneName}, '
              '${AppConfig.comuneProvince}. '
              'Per contatti: [email del DPO da configurare].',
            ),

            _heading(theme, colors, '2. Finalità e base giuridica'),
            _body(theme, colors,
              'I dati personali sono trattati per le seguenti finalità:\n\n'
              'a) Funzionamento dell\'applicazione — Base giuridica: consenso '
              'dell\'interessato (Art. 6, par. 1, lett. a, GDPR).\n\n'
              'b) Invio di notifiche push relative ad avvisi comunali, '
              'aggiornamenti sulle segnalazioni e comunicazioni di servizio — '
              'Base giuridica: consenso esplicito, revocabile in qualsiasi momento.\n\n'
              'c) Raccolta di statistiche anonime sull\'utilizzo dell\'app per '
              'il miglioramento del servizio — Base giuridica: consenso esplicito.\n\n'
              'd) Geolocalizzazione per la funzionalità di segnalazione e mappa — '
              'Base giuridica: consenso esplicito, revocabile.',
            ),

            _heading(theme, colors, '3. Dati trattati'),
            _body(theme, colors,
              'L\'applicazione tratta esclusivamente:\n\n'
              '• Nome e cognome (forniti volontariamente dall\'utente)\n'
              '• Indirizzo email (opzionale, fornito volontariamente)\n'
              '• Identificativo anonimo del dispositivo (UUID generato localmente)\n'
              '• Token per notifiche push (se consenso concesso)\n'
              '• Posizione GPS (solo se consenso concesso, solo durante l\'uso)\n'
              '• Preferenze di utilizzo (tema, zona rifiuti)\n\n'
              'I dati personali (nome, cognome, email) sono conservati '
              'esclusivamente nella memoria locale del dispositivo e non '
              'vengono trasmessi a server remoti salvo espressa indicazione.',
            ),

            _heading(theme, colors, '4. Conservazione dei dati'),
            _body(theme, colors,
              'I dati sono conservati localmente sul dispositivo dell\'utente. '
              'Le segnalazioni inviate al server sono conservate per il tempo '
              'necessario alla loro gestione da parte degli uffici comunali. '
              'I dati anonimi di utilizzo sono aggregati e non riconducibili '
              'all\'utente.',
            ),

            _heading(theme, colors, '5. Diritti dell\'interessato'),
            _body(theme, colors,
              'In conformità agli artt. 15-22 del GDPR, l\'utente ha diritto di:\n\n'
              '• Accesso — visualizzare i propri dati personali\n'
              '• Rettifica — modificare i dati dal proprio profilo\n'
              '• Cancellazione (Art. 17) — richiedere la cancellazione di '
              'tutti i dati personali tramite la funzione "Cancella i miei dati"\n'
              '• Portabilità (Art. 20) — esportare i propri dati in formato '
              'strutturato tramite la funzione "Esporta dati"\n'
              '• Revoca del consenso — modificare i consensi in qualsiasi '
              'momento dalla sezione Profilo > Gestione consensi\n'
              '• Opposizione — opporsi al trattamento rivolgendosi al DPO\n\n'
              'Per esercitare i propri diritti è possibile utilizzare le '
              'funzioni integrate nell\'app o contattare il DPO ai recapiti '
              'del Titolare.',
            ),

            _heading(theme, colors, '6. Trasferimento dati'),
            _body(theme, colors,
              'I dati personali non vengono trasferiti al di fuori dello Spazio '
              'Economico Europeo (SEE). Il servizio di notifiche push utilizza '
              'Firebase Cloud Messaging (Google LLC), che opera nell\'ambito '
              'delle Clausole Contrattuali Standard approvate dalla Commissione '
              'Europea.',
            ),

            _heading(theme, colors, '7. Sicurezza'),
            _body(theme, colors,
              'L\'app adotta misure tecniche e organizzative adeguate a garantire '
              'la sicurezza dei dati, tra cui: crittografia delle comunicazioni '
              '(HTTPS/TLS), archiviazione locale sicura, identificazione anonima '
              'tramite UUID.',
            ),

            _heading(theme, colors, '8. Contatti'),
            _body(theme, colors,
              'Per qualsiasi richiesta relativa al trattamento dei dati '
              'personali è possibile contattare:\n\n'
              '${AppConfig.comuneName}\n'
              '${AppConfig.comuneProvince}\n'
              'Email: [da configurare]\n'
              'PEC: [da configurare]',
            ),

            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: colors.chip,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Row(
                children: [
                  Icon(Icons.info_outline, size: 18, color: colors.textMuted),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      'Ultimo aggiornamento: maggio 2026',
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: colors.textMuted,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 80),
          ],
        ),
      ),
    );
  }

  Widget _section(ThemeData theme, AppColorTokens colors, String title, String text) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: theme.textTheme.titleLarge?.copyWith(
            fontWeight: FontWeight.w700,
            color: colors.text,
          ),
        ),
        const SizedBox(height: 12),
        Text(
          text,
          style: theme.textTheme.bodyMedium?.copyWith(
            color: colors.textMuted,
            height: 1.5,
          ),
        ),
        const SizedBox(height: 24),
      ],
    );
  }

  Widget _heading(ThemeData theme, AppColorTokens colors, String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Text(
        text,
        style: theme.textTheme.titleMedium?.copyWith(
          fontWeight: FontWeight.w700,
          color: colors.text,
        ),
      ),
    );
  }

  Widget _body(ThemeData theme, AppColorTokens colors, String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 20),
      child: Text(
        text,
        style: theme.textTheme.bodyMedium?.copyWith(
          color: colors.textMuted,
          height: 1.5,
        ),
      ),
    );
  }
}
