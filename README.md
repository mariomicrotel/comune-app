# App Civica — Comune di Acerno

App mobile iOS/Android per i servizi digitali del Comune di Acerno (SA).  
Permette ai cittadini di ricevere avvisi, inviare segnalazioni, consultare il calendario rifiuti, eventi, uffici, documenti e sondaggi — il tutto connesso a un backend WordPress tramite REST API.

---

## Struttura del repository

```
comune-app/
├── lib/                   # App Flutter
├── android/               # Configurazione Android
├── ios/                   # Configurazione iOS
├── assets/images/         # Immagini locali (stemma, foto header)
├── pubspec.yaml
└── wordpress-plugin/      # Plugin WordPress (backend)
```

---

## App Flutter

### Stack tecnologico

| Categoria | Libreria |
|-----------|----------|
| Framework | Flutter ≥ 3.19 / Dart ≥ 3.3 |
| State management | flutter_riverpod (AsyncNotifier) |
| Routing | go_router (StatefulShellRoute) |
| HTTP | dio + interceptors (auth, error, retry) |
| Storage locale | shared_preferences |
| Push notifications | firebase_messaging + flutter_local_notifications |
| Mappa | flutter_map (OpenStreetMap) |
| Geolocalizzazione | geolocator |
| Foto | image_picker |
| Font | Google Fonts — Inter Tight + Fraunces |

### Funzionalità

| Sezione | Descrizione |
|---------|-------------|
| **Home** | Dashboard con foto del comune in trasparenza, 8 tile di accesso rapido, banner avviso urgente, prossima raccolta rifiuti |
| **Avvisi** | Lista con filtri (Tutti / Urgenti / Importanti / Info), badge priorità colorato, dettaglio completo |
| **Segnalazioni** | Flusso 4 step: categoria → foto/audio/video → posizione + descrizione → riepilogo e invio |
| **Rifiuti** | Calendario settimanale per zona, hero card con tipo e colore raccolta, promemoria push sera prima |
| **Mappa** | Mappa OpenStreetMap con marker colorati per categoria, ricerca e filtri, lista luoghi con distanza |
| **Eventi** | Card con header colorato per categoria, dettaglio con link a Google Maps |
| **Uffici** | Schede uffici con orari, pulsanti chiama/email diretti |
| **Documenti** | Lista raggruppata per categoria, apertura PDF in-app o browser |
| **Sondaggi** | Domande scelta singola / multipla / testo libero, form disabilitato a sondaggio chiuso |
| **Notifiche** | Storico notifiche push ricevute con stato letto/non letto |
| **Profilo** | Preferenze (tema scuro, notifiche), zona rifiuti, gestione dati locali |

### Design system

- **4 palette colori**: `bluCivico` (default), `bluSavoia`, `verdeBorgo`, `tricolore`
- **Dark mode** completa su tutte le schermate
- **Font**: Inter Tight (UI/body) + Fraunces (display/titoli)
- **Accessibilità**: tap target ≥ 48dp, `Semantics` label su ogni elemento interattivo, contrasto WCAG ≥ 4.5:1

### Struttura del codice

```
lib/
├── core/
│   ├── config/          # AppConfig (baseUrl, comuneName via --dart-define)
│   ├── constants/       # AppColors (4 palette + dark mode), AppSizes
│   ├── network/         # Dio factory + interceptors (auth, error, retry)
│   ├── providers/       # core_providers.dart — tutti i Provider Riverpod
│   ├── router/          # app_router.dart — go_router con StatefulShellRoute
│   ├── screens/         # SplashScreen, OnboardingPrivacyScreen
│   ├── theme/           # AppTheme light/dark con Material 3
│   └── utils/           # AppException, DateFormatter, Validators
├── features/
│   ├── avvisi/          # Notifier + list screen + detail screen
│   ├── documenti/       # Notifier + screen
│   ├── eventi/          # Notifier + list screen + detail screen
│   ├── home/            # HomeScreen
│   ├── luoghi/          # Notifier + mappa screen
│   ├── notifiche/       # Storico notifiche push
│   ├── profilo/         # ProfiloScreen + SettingsScreen
│   ├── rifiuti/         # Notifier + screen (calendario, zone, promemoria)
│   ├── segnalazioni/    # Notifier + create + list + detail screen
│   ├── sondaggi/        # Notifier + list screen + detail screen
│   └── uffici/          # Notifier + list screen
├── models/              # Avviso, Evento, Segnalazione, Ufficio, Luogo,
│                        # ZonaRifiuti, Documento, Sondaggio
├── services/            # Un service per ogni dominio + PreferencesService,
│                        # AuthService (stub SPID/CIE), NotificationService (FCM)
└── widgets/             # PriorityBadge, PrimaryButton, LoadingState,
                         # EmptyState, ErrorState, ErrorScreen,
                         # AttachmentTile, LocationPickerWidget,
                         # ComuneBottomNavigation
```

### Avvio rapido

**Prerequisiti**: Flutter ≥ 3.19, emulatore Android/iOS o dispositivo fisico.

```bash
# 1. Installa le dipendenze
flutter pub get

# 2. Configura Firebase (una sola volta)
dart pub global activate flutterfire_cli
flutterfire configure --project=TUO_PROGETTO_FIREBASE --platforms=android,ios

# 3. Aggiungi le immagini locali
#    assets/images/acerno_bg.jpg      — foto panoramica del comune (header home)
#    assets/images/stemma-acerno.png  — stemma comunale

# 4. Avvia in debug
flutter run \
  --dart-define=BASE_URL=https://tuocomune.it/wp-json/comune/v1 \
  --dart-define=COMUNE_NAME="Comune di Acerno"
```

**Build di produzione:**

```bash
# Android APK
flutter build apk --release \
  --dart-define=BASE_URL=https://tuocomune.it/wp-json/comune/v1 \
  --dart-define=COMUNE_NAME="Comune di Acerno"

# Android App Bundle (Play Store)
flutter build appbundle --release \
  --dart-define=BASE_URL=https://tuocomune.it/wp-json/comune/v1 \
  --dart-define=COMUNE_NAME="Comune di Acerno"

# iOS IPA (richiede macOS + Xcode)
flutter build ipa --release \
  --dart-define=BASE_URL=https://tuocomune.it/wp-json/comune/v1 \
  --dart-define=COMUNE_NAME="Comune di Acerno" \
  --export-options-plist=ios/ExportOptions.plist
```

### Variabili di configurazione

| Variabile | Default | Descrizione |
|-----------|---------|-------------|
| `BASE_URL` | `https://comune.example.it` | URL base delle REST API WordPress |
| `COMUNE_NAME` | `Il Mio Comune` | Nome visualizzato nell'app |

---

## Plugin WordPress — `comune-app-manager`

Plugin che espone tutte le REST API consumate dall'app Flutter.

### Installazione

1. Copia la cartella `wordpress-plugin/` in `wp-content/plugins/comune-app-manager/`
2. Attiva il plugin da **Plugin → Plugin installati**
3. Vai in **Comune App → Impostazioni** e inserisci la chiave Firebase
4. (Opzionale) Clicca **Carica dati demo** per popolare avvisi, eventi e luoghi di esempio

**Requisiti**: WordPress ≥ 6.0, PHP ≥ 8.0

### Endpoint REST API

Prefisso base: `/wp-json/comune/v1/`

| Metodo | Endpoint | Descrizione |
|--------|----------|-------------|
| GET | `/avvisi` | Lista avvisi (filtri: `priorita`, `categoria`) |
| GET | `/avvisi/:id` | Dettaglio avviso |
| GET | `/eventi` | Lista eventi (filtro: `categoria`) |
| GET | `/eventi/:id` | Dettaglio evento |
| GET | `/uffici` | Lista uffici comunali |
| GET | `/luoghi` | Lista luoghi/POI con coordinate |
| GET | `/rifiuti/zone` | Zone di raccolta disponibili |
| GET | `/rifiuti/calendario` | Calendario raccolta (`zona_id`, `from`, `to`) |
| GET | `/documenti` | Lista documenti scaricabili |
| GET | `/sondaggi` | Lista sondaggi |
| GET | `/sondaggi/:id` | Dettaglio sondaggio con domande |
| POST | `/sondaggi/:id/risposte` | Invio risposte sondaggio |
| GET | `/segnalazioni` | Segnalazioni del dispositivo (`device_id`) |
| GET | `/segnalazioni/:id` | Dettaglio segnalazione |
| POST | `/segnalazioni` | Nuova segnalazione (multipart, supporta foto) |
| POST | `/notifications/register` | Registrazione token FCM |
| DELETE | `/notifications/unregister` | Rimozione token FCM |

### Formato risposta

```json
{
  "success": true,
  "data": {},
  "meta": {
    "total": 12,
    "page": 1
  }
}
```

### Struttura del plugin

```
wordpress-plugin/
├── comune-app-manager.php        # Entry point, hooks attivazione/disattivazione
├── uninstall.php                 # Pulizia DB alla disinstallazione
├── includes/
│   ├── class-plugin.php          # Bootstrap e caricamento classi
│   ├── class-activator.php       # Creazione tabelle custom all'attivazione
│   ├── class-post-types.php      # CPT: avviso, evento, ufficio, luogo, documento
│   ├── class-taxonomies.php      # Tassonomie: categoria_avviso, categoria_evento…
│   ├── class-meta-boxes.php      # Meta box admin per ogni CPT
│   ├── class-rest-api.php        # Registrazione e gestione tutti gli endpoint
│   ├── class-db.php              # Tabelle custom: segnalazioni, sondaggi, risposte
│   ├── class-reports.php         # Logica gestione segnalazioni
│   ├── class-surveys.php         # Logica sondaggi e raccolta risposte
│   ├── class-waste-calendar.php  # Generazione calendario raccolta rifiuti
│   ├── class-push-notifications.php # Invio push via Firebase FCM HTTP v1
│   ├── class-firebase.php        # Client Firebase (autenticazione + messaging)
│   ├── class-settings.php        # Pagina impostazioni admin
│   ├── class-admin-menu.php      # Voci menu WordPress admin
│   ├── class-capabilities.php    # Ruoli e permessi custom
│   ├── class-permissions.php     # Controllo accessi endpoint API
│   ├── class-logger.php          # Log interno del plugin
│   ├── class-utils.php           # Helpers (sanitize, format date…)
│   └── class-demo-seeder.php     # Dati demo per Comune di Acerno
└── admin/
    ├── css/admin.css
    ├── js/admin.js
    └── views/
        ├── dashboard.php         # Statistiche generali
        ├── reports-list.php      # Lista segnalazioni ricevute
        ├── reports-detail.php    # Dettaglio segnalazione con cambio stato
        ├── waste-calendar.php    # Gestione calendario raccolta rifiuti
        ├── survey-results.php    # Risultati e grafici sondaggi
        └── push-send.php         # Invio notifiche push manuale
```

---

## Roadmap

- [ ] **Autenticazione SPID/CIE** — stub `AuthService` già presente nel codice
- [ ] **pagoPA** — modulo `/features/pagamenti/` isolato
- [ ] **Certificati anagrafici** — compatibile con ApiClient esistente
- [ ] **Chat in tempo reale** — WebSocket service separato, non impatta i moduli attuali
- [ ] **Multi-comune** — cambio completo di palette, assets e `BASE_URL` via `--dart-define`

---

## Licenza

Codice sviluppato per uso istituzionale del Comune di Acerno (SA).  
Per riutilizzo su altri comuni contattare il fornitore.
