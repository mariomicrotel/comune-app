=== Comune App Manager ===
Contributors: comune-dev
Donate link: https://comune-dev.it/donate
Tags: comune, municipio, app, mobile, avvisi, eventi, notifiche push, raccolta rifiuti, sondaggi
Requires at least: 6.4
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gestione completa dei contenuti per l'app mobile del Comune: avvisi, eventi, uffici, luoghi, documenti, raccolta rifiuti, sondaggi e notifiche push.

== Description ==

**Comune App Manager** è il plugin WordPress che alimenta l'app mobile ufficiale del Comune. Fornisce un pannello di amministrazione completo e una REST API sicura per gestire tutti i contenuti e le funzionalità dell'app.

= Funzionalità principali =

* **Avvisi e notizie** – Pubblica comunicati, ordinanze e notizie dell'ente. Supporta categorie, tag, immagini in evidenza e programmazione della pubblicazione.
* **Calendario eventi** – Gestione degli eventi comunali con data/ora, luogo, categorie e mappa integrata.
* **Uffici e sportelli** – Rubrica completa degli uffici con orari di apertura, contatti, responsabili e descrizione dei servizi erogati.
* **Luoghi di interesse** – Punti di interesse sul territorio con coordinate GPS e galleria fotografica.
* **Documenti e modulistica** – Raccolta di documenti scaricabili (PDF, DOCX) organizzati per categoria.
* **Calendario raccolta rifiuti** – Pianificazione e promemoria per la raccolta differenziata per zona geografica.
* **Sondaggi e questionari** – Creazione di sondaggi con domande a risposta singola, multipla o aperta; raccolta e visualizzazione dei risultati in tempo reale.
* **Notifiche push** – Invio di notifiche push ai dispositivi registrati tramite Firebase Cloud Messaging (FCM). Supporto per invio immediato e programmato, targeting per categoria, zona e piattaforma.
* **Segnalazioni cittadini** – Ricezione e gestione delle segnalazioni inviate dagli utenti dell'app con sistema di ticketing e codice pubblico univoco.
* **REST API** – Endpoint REST completi e sicuri per tutte le funzionalità, con autenticazione JWT, rate limiting e risposte standardizzate.
* **Dashboard e report** – Statistiche di utilizzo, report esportabili in CSV/PDF, log delle notifiche inviate.

= Requisiti di sistema =

* WordPress 6.4 o superiore
* PHP 8.1 o superiore
* MySQL 8.0 / MariaDB 10.6 o superiore
* Account Firebase con progetto FCM configurato (solo per notifiche push)
* HTTPS attivo sul sito (obbligatorio per sicurezza JWT)

= Sicurezza =

Il plugin implementa:

* Autenticazione JWT per le chiamate REST dall'app
* Rate limiting per endpoint pubblici (configurable)
* Sanitizzazione e validazione di tutti gli input
* Escape di tutti gli output
* Controllo delle capability WordPress granulare
* Log di debug che non espongono mai chiavi private o token

== Installation ==

= Installazione automatica =

1. Vai su **Plugin > Aggiungi nuovo** nel pannello WordPress.
2. Cerca **"Comune App Manager"**.
3. Clicca **Installa ora** e poi **Attiva**.
4. Vai su **Comune App > Impostazioni** e completa la configurazione iniziale.

= Installazione manuale =

1. Scarica il file `.zip` del plugin.
2. Vai su **Plugin > Aggiungi nuovo > Carica plugin**.
3. Seleziona il file `.zip` e clicca **Installa ora**.
4. Clicca **Attiva plugin**.
5. Vai su **Comune App > Impostazioni** e completa la configurazione iniziale.

= Configurazione Firebase (notifiche push) =

1. Crea un progetto su [Firebase Console](https://console.firebase.google.com/).
2. Vai su **Impostazioni progetto > Cloud Messaging** e copia la **Server Key**.
3. Nel pannello WordPress vai su **Comune App > Impostazioni > Notifiche Push**.
4. Incolla la Server Key e il Project ID nei campi corrispondenti.
5. Abilita l'interruttore **Notifiche push attive**.

== Frequently Asked Questions ==

= Il plugin funziona con qualsiasi tema? =

Sì. Il plugin non interferisce con il frontend del sito WordPress. Opera esclusivamente tramite REST API e pannello di amministrazione.

= Posso usare il plugin senza un'app mobile? =

Sì, puoi usarlo come CMS avanzato per avvisi, eventi, uffici e documenti che vengono visualizzati tramite shortcode o blocchi Gutenberg (da installare separatamente).

= I dati vengono eliminati quando disattivo il plugin? =

No. La disattivazione non rimuove alcun dato. Tutti i contenuti e le impostazioni vengono eliminati definitivamente solo se, prima di disinstallare, abiliti l'opzione **"Elimina tutti i dati alla disinstallazione"** in **Comune App > Impostazioni > Avanzate**.

= Come funziona il rate limiting? =

Il plugin traccia il numero di richieste per ogni indirizzo IP usando i transient di WordPress. L'amministratore può configurare il limite massimo di richieste e la finestra temporale in **Comune App > Impostazioni > API**.

= Quali versioni di PHP sono supportate? =

Il plugin richiede PHP 8.1 o superiore per sfruttare le proprietà tipizzate, le enumerazioni e le altre funzionalità moderne del linguaggio.

= Come faccio a tradurre il plugin? =

Il plugin è completamente internazionalizzato. Puoi creare le tue traduzioni con Poedit usando i file `.pot` presenti nella cartella `/languages/`. Le traduzioni ufficiali vengono gestite su [translate.wordpress.org](https://translate.wordpress.org/).

= Dove trovo la documentazione per l'API REST? =

La documentazione OpenAPI è disponibile all'URL `/wp-json/comune-app/v1/schema` una volta attivato il plugin.

== Screenshots ==

1. Dashboard principale con statistiche di utilizzo.
2. Gestione avvisi e notizie.
3. Configurazione calendario raccolta rifiuti.
4. Pannello notifiche push con targeting avanzato.
5. Visualizzazione risultati sondaggi.
6. Impostazioni generali del plugin.

== Changelog ==

= 1.0.0 =
* Versione iniziale.
* Registrazione custom post types: avvisi, eventi, uffici, luoghi, documenti.
* REST API v1 con autenticazione JWT.
* Gestione calendario raccolta rifiuti per zona.
* Sistema sondaggi con aggregazione risultati.
* Integrazione Firebase Cloud Messaging per notifiche push.
* Gestione segnalazioni cittadini con codice pubblico univoco.
* Dashboard con statistiche e report esportabili.
* Rate limiting per endpoint pubblici.
* Log di debug con oscuramento automatico dei dati sensibili.

== Upgrade Notice ==

= 1.0.0 =
Prima versione stabile. Nessuna nota di aggiornamento.
