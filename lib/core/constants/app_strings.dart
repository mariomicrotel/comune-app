class AppStrings {
  AppStrings._();

  // General
  static const String appName = 'Comune App';
  static const String retry = 'Riprova';
  static const String close = 'Chiudi';
  static const String cancel = 'Annulla';
  static const String confirm = 'Conferma';
  static const String save = 'Salva';
  static const String send = 'Invia';
  static const String loading = 'Caricamento…';
  static const String noData = 'Nessun dato disponibile';
  static const String errorGeneric = 'Si è verificato un errore. Riprova.';
  static const String errorNoConnection = 'Nessuna connessione internet.';
  static const String errorServer = 'Errore del server. Riprova più tardi.';
  static const String offline = 'Sei offline';
  static const String offlineBanner = 'Connessione assente — dati in cache';

  // Nav
  static const String navHome = 'Home';
  static const String navAvvisi = 'Avvisi';
  static const String navSegnala = 'Segnala';
  static const String navMappa = 'Mappa';
  static const String navProfilo = 'Profilo';

  // Home
  static const String homeGreetingMorning = 'Buongiorno';
  static const String homeGreetingAfternoon = 'Buon pomeriggio';
  static const String homeGreetingEvening = 'Buonasera';
  static const String homeTileAvvisi = 'Avvisi';
  static const String homeTileSegnala = 'Segnala';
  static const String homeTileRifiuti = 'Rifiuti';
  static const String homeTileEventi = 'Eventi';
  static const String homeTileUffici = 'Uffici';
  static const String homeTileMappa = 'Mappa';
  static const String homeTileDocumenti = 'Documenti';
  static const String homeTileSondaggi = 'Sondaggi';
  static const String homeNextWaste = 'Prossima raccolta';
  static const String homeToday = 'Oggi';
  static const String homeTomorrow = 'Domani';

  // Avvisi
  static const String avvisiTitle = 'Avvisi';
  static const String avvisiFilterAll = 'Tutti';
  static const String avvisiFilterUrgenti = 'Urgenti';
  static const String avvisiFilterImportanti = 'Importanti';
  static const String avvisiFilterInfo = 'Info';
  static const String avvisoPriorityUrgente = 'Urgente';
  static const String avvisoPriorityAlta = 'Urgente';
  static const String avvisoPriorityMedia = 'Importante';
  static const String avvisoPriorityBassa = 'Informazione';
  static const String avvisiEmpty = 'Nessun avviso al momento.';

  // Segnalazioni
  static const String segnalaTitle = 'Segnala un problema';
  static const String segnalaStep1 = 'Categoria';
  static const String segnalaStep2 = 'Foto/Media';
  static const String segnalaStep3 = 'Posizione';
  static const String segnalaStep4 = 'Riepilogo';
  static const String segnalaSuccess = 'Segnalazione inviata!';
  static const String segnalaSuccessDetail = 'Numero pratica';
  static const String segnalaDescHint = 'Descrivi il problema in dettaglio…';
  static const String segnalaDescMin = 'Descrizione troppo breve (min 20 caratteri).';
  static const String segnalaCatBuche = 'Buche / Strade';
  static const String segnalaCatIlluminazione = 'Illuminazione';
  static const String segnalaCatRifiuti = 'Rifiuti abbandonati';
  static const String segnalaCatVerde = 'Verde pubblico';
  static const String segnalaCatArredo = 'Arredo urbano';
  static const String segnalaCatAnimali = 'Animali / Igiene';
  static const String segnalaCatPerdite = 'Perdite idriche';
  static const String segnalaCatAltro = 'Altro';
  static const String segnalaAddPhoto = 'Aggiungi foto';
  static const String segnalaAddVideo = 'Aggiungi video';
  static const String segnalaAddVoice = 'Nota vocale';
  static const String segnalaTextOnly = 'Solo testo';
  static const String segnalaLocationDetect = 'Rileva posizione';
  static const String segnalaLocationManual = 'Seleziona manualmente';
  static const String segnalaSubmit = 'Invia segnalazione';
  static const String segnalaListEmpty = 'Nessuna segnalazione inviata.';
  static const String segnalaStatoAperta = 'Aperta';
  static const String segnalaStatoLavorazione = 'In lavorazione';
  static const String segnalaStatoRisolta = 'Risolta';
  static const String segnalaStatoRespinta = 'Non accettata';

  // Rifiuti
  static const String rifiutiTitle = 'Raccolta rifiuti';
  static const String rifiutiSelectZone = 'Seleziona zona';
  static const String rifiutiNoCollectionToday = 'Nessuna raccolta oggi';
  static const String rifiutiReminder = 'Promemoria raccolta';
  static const String rifiutiLegend = 'Legenda';

  // Eventi
  static const String eventiTitle = 'Eventi';
  static const String eventiEmpty = 'Nessun evento in programma.';

  // Uffici
  static const String ufficiTitle = 'Uffici comunali';
  static const String ufficiCall = 'Chiama';
  static const String ufficiEmail = 'Email';
  static const String ufficiEmpty = 'Nessun ufficio trovato.';

  // Documenti
  static const String documentiTitle = 'Documenti';
  static const String documentiDownload = 'Apri documento';
  static const String documentiEmpty = 'Nessun documento disponibile.';

  // Sondaggi
  static const String sondaggiTitle = 'Sondaggi';
  static const String sondaggiActive = 'Attivo';
  static const String sondaggiClosed = 'Concluso';
  static const String sondaggiSubmit = 'Invia risposta';
  static const String sondaggiThanks = 'Grazie per aver partecipato!';
  static const String sondaggiEmpty = 'Nessun sondaggio disponibile.';

  // Profilo
  static const String profiloTitle = 'Profilo';
  static const String profiloSegnalazioni = 'Le mie segnalazioni';
  static const String profiloDocumenti = 'Documenti salvati';
  static const String profiloImpostazioni = 'Impostazioni';
  static const String profiloPrivacy = 'Privacy';
  static const String profiloDeleteData = 'Cancella i miei dati';

  // Onboarding
  static const String onboardingTitle = 'Benvenuto';
  static const String onboardingPrivacyLabel = 'Accetto la Privacy Policy';
  static const String onboardingNotificationsLabel = 'Ricevi notifiche push';
  static const String onboardingContinue = 'Accetta e continua';

  // Settings
  static const String settingsTitle = 'Impostazioni';
  static const String settingsTheme = 'Tema colori';
  static const String settingsDarkMode = 'Modalità scura';
  static const String settingsFontSize = 'Dimensione testo';
  static const String settingsZonaRifiuti = 'Zona raccolta rifiuti';
  static const String settingsNotifications = 'Notifiche push';
}
