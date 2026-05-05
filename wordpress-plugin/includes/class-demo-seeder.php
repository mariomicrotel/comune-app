<?php
/**
 * Demo data seeder — Comune di Acerno (SA).
 *
 * Popola tutti i tipi di contenuto consumati dall'app Flutter:
 * avvisi, eventi, uffici, luoghi, documenti, sondaggi,
 * calendario rifiuti (3 mesi), categorie tassonomiche, impostazioni plugin.
 *
 * È idempotente: ogni elemento viene saltato se il suo slug esiste già,
 * quindi può essere eseguito più volte senza duplicare i dati.
 *
 * Utilizzo:
 *   — Via WP-CLI:   wp eval-file path/to/seed.php
 *   — Via admin UI: Dashboard > App Comunale > "Carica dati demo"
 *   — Via codice:   Comune_App_Manager_Demo_Seeder::run_once()
 *
 * @package ComuneAppManager
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comune_App_Manager_Demo_Seeder
 */
class Comune_App_Manager_Demo_Seeder {

	// -----------------------------------------------------------------------
	// Coordinate geografiche — Acerno (SA) ~40.7519°N, 15.0307°E
	// -----------------------------------------------------------------------
	private const COORDS = array(
		'municipio'    => array( 'lat' => 40.7519, 'lng' => 15.0308 ),
		'chiesa'       => array( 'lat' => 40.7523, 'lng' => 15.0301 ),
		'santuario'    => array( 'lat' => 40.7415, 'lng' => 15.0220 ),
		'parco'        => array( 'lat' => 40.7591, 'lng' => 15.0488 ),
		'campo_sport'  => array( 'lat' => 40.7540, 'lng' => 15.0325 ),
		'fontana_orso' => array( 'lat' => 40.7516, 'lng' => 15.0295 ),
	);

	/** @var array<array{type:string,message:string}> */
	private array $log = array();

	/** @var int WordPress user ID to use as post author. */
	private int $author_id;

	// -----------------------------------------------------------------------
	// Entry points
	// -----------------------------------------------------------------------

	public function __construct() {
		$this->author_id = (int) get_current_user_id() ?: 1;
	}

	/**
	 * Instantiate and run the seeder.
	 *
	 * @return array<array{type:string,message:string}> Execution log.
	 */
	public static function run_once(): array {
		return ( new self() )->run();
	}

	/**
	 * Execute all seed steps in order.
	 *
	 * @return array<array{type:string,message:string}>
	 */
	public function run(): array {
		$this->seed_settings();
		$this->seed_taxonomy_terms();
		$this->seed_avvisi();
		$this->seed_eventi();
		$this->seed_uffici();
		$this->seed_luoghi();
		$this->seed_documenti();
		$this->seed_sondaggi();
		$this->seed_waste_schedule();
		return $this->log;
	}

	// -----------------------------------------------------------------------
	// 1. Plugin settings
	// -----------------------------------------------------------------------

	private function seed_settings(): void {
		$existing = get_option( 'cam_settings', array() );

		// Only set defaults — never overwrite an existing non-empty config.
		$defaults = array(
			'comune_name'            => 'Comune di Acerno',
			'comune_code'            => 'A027',
			'app_active'             => true,
			'palette'                => 'blu_civico',
			'primary_color'          => '#0B5FFF',
			'secondary_color'        => '#1ED27A',
			'waste_calendar_enabled' => true,
			'surveys_enabled'        => true,
			'reports_enabled'        => true,
			'api_rate_limit'         => 60,
			'api_rate_window'        => 3600,
			'token_expiry_hours'     => 24,
			'privacy_policy_url'     => 'https://comune.acerno.sa.it/privacy',
			'tos_url'                => 'https://comune.acerno.sa.it/termini',
			'support_email'          => 'info@comune.acerno.sa.it',
			'support_phone'          => '089 830011',
			'app_store_url'          => '',
			'play_store_url'         => '',
			'maintenance_mode'       => false,
			'maintenance_message'    => '',
		);

		$merged = array_merge( $defaults, $existing );
		update_option( 'cam_settings', $merged );
		$this->log( 'ok', 'Impostazioni plugin aggiornate.' );
	}

	// -----------------------------------------------------------------------
	// 2. Taxonomy terms
	// -----------------------------------------------------------------------

	private function seed_taxonomy_terms(): void {
		$terms = array(
			'cam_categoria_avviso' => array(
				'Urgenze e allerte'      => 'urgenze-allerte',
				'Tributi e pagamenti'    => 'tributi-pagamenti',
				'Bandi e contributi'     => 'bandi-contributi',
				'Lavori pubblici'        => 'lavori-pubblici',
				'Servizi comunali'       => 'servizi-comunali',
				'Scuola e formazione'    => 'scuola-formazione',
				'Salute e ambiente'      => 'salute-ambiente',
			),
			'cam_categoria_evento' => array(
				'Cultura e spettacolo'   => 'cultura-spettacolo',
				'Sagre e tradizioni'     => 'sagre-tradizioni',
				'Sport e tempo libero'   => 'sport-tempo-libero',
				'Istituzionale'          => 'istituzionale',
				'Ambiente'               => 'ambiente',
			),
			'cam_categoria_luogo' => array(
				'Istituzionale'          => 'istituzionale',
				'Luoghi di culto'        => 'luoghi-di-culto',
				'Natura e parchi'        => 'natura-parchi',
				'Sport'                  => 'sport',
				'Cultura'                => 'cultura',
			),
			'cam_categoria_documento' => array(
				'Modulistica'            => 'modulistica',
				'Regolamenti'            => 'regolamenti',
				'Urbanistica'            => 'urbanistica',
				'Bandi'                  => 'bandi',
				'Delibere e determine'   => 'delibere-determine',
			),
			'cam_zona' => array(
				'Zona A — Sant\'Anna'     => 'zona-a',
				'Zona B — Centro Storico' => 'zona-b',
				'Zona C — Piaggine'       => 'zona-c',
				'Zona D — Località Rurali'=> 'zona-d',
			),
		);

		foreach ( $terms as $taxonomy => $entries ) {
			foreach ( $entries as $name => $slug ) {
				if ( ! term_exists( $slug, $taxonomy ) ) {
					$result = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );
					if ( is_wp_error( $result ) ) {
						$this->log( 'error', "Termine '{$slug}': " . $result->get_error_message() );
					} else {
						$this->log( 'created', "Termine creato: [{$taxonomy}] {$name}" );
					}
				} else {
					$this->log( 'skip', "Termine già presente: [{$taxonomy}] {$name}" );
				}
			}
		}
	}

	// -----------------------------------------------------------------------
	// 3. Avvisi (6 notices with varied priorities)
	// -----------------------------------------------------------------------

	private function seed_avvisi(): void {
		$today = gmdate( 'Y-m-d' );

		$avvisi = array(
			array(
				'post_name'    => 'interruzione-idrica-zona-b',
				'post_title'   => 'Interruzione idrica in Zona B — Intervento urgente',
				'post_content' => '<p>Si comunica che <strong>giovedì ' . gmdate( 'd/m/Y', strtotime( '+2 days' ) ) . ' dalle ore 8:00 alle ore 18:00</strong> sarà sospesa l\'erogazione dell\'acqua potabile nelle seguenti vie della Zona B (Centro Storico):</p><ul><li>Via Matteotti (tratto nord)</li><li>Piazza del Municipio e strade adiacenti</li><li>Via Garibaldi</li></ul><p>L\'interruzione si rende necessaria per lavori di manutenzione straordinaria sulla rete idrica. Ci scusiamo per il disagio. Per informazioni: Ufficio Tecnico 089 830013.</p>',
				'post_excerpt' => 'Giovedì ' . gmdate( 'd/m/Y', strtotime( '+2 days' ) ) . ' interruzione idrica in Zona B dalle 8:00 alle 18:00 per lavori straordinari.',
				'meta'         => array(
					'_cam_priorita'    => 'urgente',
					'_cam_expiry_date' => gmdate( 'Y-m-d', strtotime( '+3 days' ) ),
					'_cam_app_visible' => '1',
					'_cam_app_featured'=> '1',
				),
				'terms'        => array(
					'cam_categoria_avviso' => array( 'urgenze-allerte' ),
					'cam_zona'             => array( 'zona-b' ),
				),
			),
			array(
				'post_name'    => 'chiusura-ufficio-anagrafe-manutenzione',
				'post_title'   => 'Chiusura Ufficio Anagrafe — Aggiornamento sistemi',
				'post_content' => '<p>Si rende noto che l\'<strong>Ufficio Anagrafe e Stato Civile</strong> resterà chiuso <strong>venerdì ' . gmdate( 'd/m/Y', strtotime( '+4 days' ) ) . '</strong> per l\'aggiornamento dei sistemi informatici.</p><p>Il servizio riprenderà regolarmente <strong>lunedì ' . gmdate( 'd/m/Y', strtotime( '+7 days' ) ) . '</strong>.</p><p>Per le pratiche urgenti (denunce di nascita e decesso) contattare il numero di emergenza: <strong>089 830011</strong>.</p>',
				'post_excerpt' => 'L\'Ufficio Anagrafe sarà chiuso venerdì ' . gmdate( 'd/m/Y', strtotime( '+4 days' ) ) . ' per aggiornamento sistemi.',
				'meta'         => array(
					'_cam_priorita'    => 'alta',
					'_cam_expiry_date' => gmdate( 'Y-m-d', strtotime( '+5 days' ) ),
					'_cam_app_visible' => '1',
					'_cam_app_featured'=> '0',
				),
				'terms'        => array(
					'cam_categoria_avviso' => array( 'servizi-comunali' ),
				),
			),
			array(
				'post_name'    => 'bando-contributi-fotovoltaico-2026',
				'post_title'   => 'Bando contributi impianti fotovoltaici 2026',
				'post_content' => '<p>Il Comune di Acerno ha approvato il bando per la concessione di <strong>contributi a fondo perduto</strong> per l\'installazione di impianti fotovoltaici da parte di privati residenti.</p><h3>Beneficiari</h3><p>Cittadini residenti nel Comune di Acerno che intendono installare un impianto fotovoltaico sulla propria abitazione principale.</p><h3>Contributo</h3><ul><li>Fino al <strong>50%</strong> della spesa ammissibile</li><li>Importo massimo: <strong>€ 3.000,00</strong> per impianti fino a 6 kWp</li></ul><h3>Scadenza</h3><p>Le domande devono essere presentate entro il <strong>' . gmdate( 'd/m/Y', strtotime( '+55 days' ) ) . '</strong> all\'Ufficio Tecnico Comunale.</p><p>Il modulo di domanda è disponibile sul sito del Comune e presso la Segreteria.</p>',
				'post_excerpt' => 'Contributi fino al 50% per impianti fotovoltaici. Scadenza: ' . gmdate( 'd/m/Y', strtotime( '+55 days' ) ) . '.',
				'meta'         => array(
					'_cam_priorita'    => 'media',
					'_cam_expiry_date' => gmdate( 'Y-m-d', strtotime( '+56 days' ) ),
					'_cam_app_visible' => '1',
					'_cam_app_featured'=> '0',
				),
				'terms'        => array(
					'cam_categoria_avviso' => array( 'bandi-contributi' ),
				),
			),
			array(
				'post_name'    => 'avviso-imu-scadenza-giugno-2026',
				'post_title'   => 'IMU 2026 — Scadenza acconto 16 giugno',
				'post_content' => '<p>Si ricorda a tutti i contribuenti che il <strong>16 giugno 2026</strong> scade il termine per il pagamento della <strong>prima rata (acconto) dell\'IMU</strong> per l\'anno 2026.</p><p>Il pagamento va effettuato tramite <strong>Modello F24</strong> utilizzando i codici tributo vigenti.</p><h3>Come calcolare</h3><p>Per il calcolo dell\'importo dovuto è disponibile il servizio online sul portale del Comune oppure ci si può rivolgere all\'Ufficio Tributi.</p><h3>Esenzioni</h3><p>Sono esenti dall\'IMU le abitazioni principali, salvo quelle classificate in categoria catastale A/1, A/8 e A/9.</p><p>Per informazioni: <strong>Ufficio Tributi</strong> — tel. 089 830012 — tributi@comune.acerno.sa.it</p>',
				'post_excerpt' => 'Prima rata IMU 2026 in scadenza il 16 giugno. Pagamento tramite Modello F24.',
				'meta'         => array(
					'_cam_priorita'    => 'media',
					'_cam_expiry_date' => '2026-06-17',
					'_cam_app_visible' => '1',
					'_cam_app_featured'=> '0',
				),
				'terms'        => array(
					'cam_categoria_avviso' => array( 'tributi-pagamenti' ),
				),
			),
			array(
				'post_name'    => 'nuovi-orari-estivi-sportello-unico',
				'post_title'   => 'Nuovi orari estivi — Sportello Unico',
				'post_content' => '<p>A partire dal <strong>1° giugno 2026</strong> e fino al <strong>31 agosto 2026</strong>, lo Sportello Unico del Comune di Acerno osserverà i seguenti <strong>orari estivi</strong>:</p><ul><li><strong>Lunedì — Venerdì:</strong> 9:00 – 12:00</li><li><strong>Martedì e Giovedì:</strong> anche 15:30 – 17:00 (solo su appuntamento)</li></ul><p>Per prenotare un appuntamento pomeridiano: <strong>089 830011</strong> o scrivere a segreteria@comune.acerno.sa.it</p><p>I servizi di anagrafe, stato civile, tributi e ufficio tecnico rimangono accessibili negli orari indicati.</p>',
				'post_excerpt' => 'Dal 1° giugno orari estivi: 9:00–12:00 tutti i giorni, pomeriggio solo su appuntamento.',
				'meta'         => array(
					'_cam_priorita'    => 'bassa',
					'_cam_expiry_date' => '2026-09-01',
					'_cam_app_visible' => '1',
					'_cam_app_featured'=> '0',
				),
				'terms'        => array(
					'cam_categoria_avviso' => array( 'servizi-comunali' ),
				),
			),
			array(
				'post_name'    => 'inaugurazione-area-verde-piaggine',
				'post_title'   => 'Inaugurata la nuova area verde attrezzata in Zona C',
				'post_content' => '<p>L\'Amministrazione Comunale è lieta di annunciare il completamento e l\'inaugurazione della <strong>nuova area verde attrezzata</strong> in Zona C (Piaggine), realizzata nell\'ambito del progetto "Acerno Verde".</p><p>L\'area è dotata di:</p><ul><li>Giochi per bambini 0–10 anni con pavimentazione antitrauma</li><li>Panchine e tavoli da picnic</li><li>Fontanella d\'acqua potabile</li><li>Illuminazione a LED a basso consumo</li><li>Rastrelliere per biciclette</li></ul><p>L\'area sarà aperta tutti i giorni dalle ore 7:00 alle ore 22:00.</p>',
				'post_excerpt' => 'Inaugurata la nuova area verde attrezzata in Zona C — Piaggine, con giochi, panchine e illuminazione LED.',
				'meta'         => array(
					'_cam_priorita'    => 'bassa',
					'_cam_expiry_date' => '',
					'_cam_app_visible' => '1',
					'_cam_app_featured'=> '0',
				),
				'terms'        => array(
					'cam_categoria_avviso' => array( 'lavori-pubblici' ),
					'cam_zona'             => array( 'zona-c' ),
				),
			),
		);

		foreach ( $avvisi as $data ) {
			$this->insert_post(
				array(
					'post_type'    => 'comune_avviso',
					'post_name'    => $data['post_name'],
					'post_title'   => $data['post_title'],
					'post_content' => $data['post_content'],
					'post_excerpt' => $data['post_excerpt'],
				),
				$data['meta'],
				$data['terms']
			);
		}
	}

	// -----------------------------------------------------------------------
	// 4. Eventi (5 events across 3 months)
	// -----------------------------------------------------------------------

	private function seed_eventi(): void {
		$eventi = array(
			array(
				'post_name'    => 'festa-patronale-san-donato-2026',
				'post_title'   => 'Festa Patronale di San Donato 2026',
				'post_content' => '<p>Acerno celebra il suo santo patrono <strong>San Donato Vescovo</strong> con tre giorni di festeggiamenti che uniscono devozione, cultura e tradizione.</p><h3>Programma</h3><ul><li><strong>6 agosto</strong> — 21:00: Concerto della Banda Musicale di Acerno in Piazza del Municipio</li><li><strong>7 agosto</strong> — 10:00: Santa Messa solenne in onore del Patrono, officiata dal Vescovo di Salerno-Campagna-Acerno</li><li><strong>7 agosto</strong> — 12:30: Processione solenne per le vie del paese con la statua del Santo</li><li><strong>7 agosto</strong> — 19:00: Giochi per bambini e famiglie in Piazza Sant\'Anna</li><li><strong>7 agosto</strong> — 23:00: Spettacolo pirotecnico</li><li><strong>8 agosto</strong> — 20:00: Serata danzante con orchestra</li></ul><p>L\'accesso a tutti gli eventi è libero e gratuito.</p>',
				'post_excerpt' => 'Tre giorni di festeggiamenti per San Donato: messa solenne, processione, musica e spettacolo pirotecnico.',
				'meta'         => array(
					'_cam_event_start_date'  => '2026-08-06',
					'_cam_event_start_time'  => '21:00',
					'_cam_event_end_date'    => '2026-08-08',
					'_cam_event_end_time'    => '23:59',
					'_cam_event_all_day'     => '0',
					'_cam_event_location'    => 'Piazza del Municipio, Acerno (SA)',
					'_cam_event_organizer'   => 'Comune di Acerno — Pro Loco Acerno',
					'_cam_lat'               => '40.7519',
					'_cam_lng'               => '15.0308',
					'_cam_app_visible'       => '1',
					'_cam_app_featured'      => '1',
				),
				'terms'        => array(
					'cam_categoria_evento' => array( 'cultura-spettacolo', 'sagre-tradizioni' ),
				),
			),
			array(
				'post_name'    => 'sagra-fusilli-acernesi-2026',
				'post_title'   => 'Sagra dei Fusilli Acernesi 2026',
				'post_content' => '<p>Torna la tradizionale <strong>Sagra dei Fusilli Acernesi</strong>, uno degli appuntamenti più attesi dell\'estate borgarola. Due giorni all\'insegna dei sapori autentici del territorio, con i fusilli al ragù di agnello preparati secondo la ricetta della nonna.</p><h3>Programma</h3><ul><li><strong>19 luglio</strong> — Ore 19:30: Apertura stand gastronomici</li><li><strong>19 luglio</strong> — Ore 21:00: Musica live con il gruppo "I Picensi"</li><li><strong>20 luglio</strong> — Ore 19:30: Apertura stand gastronomici</li><li><strong>20 luglio</strong> — Ore 20:30: Gara del fusillo più lungo (iscrizioni sul posto)</li><li><strong>20 luglio</strong> — Ore 22:00: Serata danzante</li></ul><p>Prezzo degustazione: €12,00 (fusilli + vino locale + dolce)</p>',
				'post_excerpt' => 'Due giorni di fusilli, musica e tradizione acernese. 19-20 luglio in Piazza Sant\'Anna.',
				'meta'         => array(
					'_cam_event_start_date'  => '2026-07-19',
					'_cam_event_start_time'  => '19:30',
					'_cam_event_end_date'    => '2026-07-20',
					'_cam_event_end_time'    => '23:30',
					'_cam_event_all_day'     => '0',
					'_cam_event_location'    => 'Piazza Sant\'Anna, Acerno (SA)',
					'_cam_event_organizer'   => 'Pro Loco Acerno',
					'_cam_lat'               => '40.7519',
					'_cam_lng'               => '15.0308',
					'_cam_app_visible'       => '1',
					'_cam_app_featured'      => '1',
				),
				'terms'        => array(
					'cam_categoria_evento' => array( 'sagre-tradizioni' ),
				),
			),
			array(
				'post_name'    => 'giornata-mondiale-ambiente-2026',
				'post_title'   => 'Giornata Mondiale dell\'Ambiente — Puliamo Acerno',
				'post_content' => '<p>In occasione della <strong>Giornata Mondiale dell\'Ambiente (5 giugno)</strong>, il Comune di Acerno organizza una giornata di sensibilizzazione e volontariato attivo.</p><h3>Attività</h3><ul><li>09:00 — Raccolta di rifiuti nelle aree verdi e sentieri del Parco Picentini</li><li>11:00 — Piantumazione di 20 alberi da frutto nel parco urbano</li><li>13:00 — Pranzo al sacco comunitario</li><li>15:00 — Laboratorio per bambini "Costruiamo un orto"</li><li>17:00 — Proiezione del documentario "I Picentini al futuro"</li></ul><p>La partecipazione è libera e aperta a tutti. Portare guanti da lavoro, scarpe robuste e tanta voglia di fare!</p><p>Ritrovo: Piazza del Municipio ore 9:00</p>',
				'post_excerpt' => 'Per la Giornata Mondiale dell\'Ambiente, puliamo e piantiamo insieme. 5 giugno, ritrovo ore 9:00 in Piazza.',
				'meta'         => array(
					'_cam_event_start_date'  => '2026-06-05',
					'_cam_event_start_time'  => '09:00',
					'_cam_event_end_date'    => '2026-06-05',
					'_cam_event_end_time'    => '18:00',
					'_cam_event_all_day'     => '0',
					'_cam_event_location'    => 'Piazza del Municipio e Parco Picentini',
					'_cam_event_organizer'   => 'Comune di Acerno — Associazione AcernoVerde',
					'_cam_lat'               => '40.7591',
					'_cam_lng'               => '15.0488',
					'_cam_app_visible'       => '1',
					'_cam_app_featured'      => '0',
				),
				'terms'        => array(
					'cam_categoria_evento' => array( 'ambiente' ),
				),
			),
			array(
				'post_name'    => 'consiglio-comunale-aperto-maggio-2026',
				'post_title'   => 'Consiglio Comunale Aperto — Maggio 2026',
				'post_content' => '<p>L\'Amministrazione Comunale invita tutti i cittadini a partecipare al <strong>Consiglio Comunale Aperto</strong> del mese di maggio.</p><h3>Ordine del giorno</h3><ol><li>Approvazione verbale seduta precedente</li><li>Presentazione Piano di Zona Sociale 2026–2028</li><li>Variazione di bilancio — Interventi manutenzione strade</li><li>Regolamento per l\'utilizzo del campo sportivo comunale</li><li>Interrogazioni e comunicazioni</li></ol><p>Il Consiglio è aperto alla partecipazione pubblica. I cittadini che desiderano intervenire devono iscriversi entro le ore 12:00 del giorno precedente contattando la Segreteria: <strong>segreteria@comune.acerno.sa.it</strong></p>',
				'post_excerpt' => 'Consiglio Comunale Aperto il 15 maggio 2026 alle 18:00. Ordine del giorno: Piano Sociale e manutenzione strade.',
				'meta'         => array(
					'_cam_event_start_date'  => '2026-05-15',
					'_cam_event_start_time'  => '18:00',
					'_cam_event_end_date'    => '2026-05-15',
					'_cam_event_end_time'    => '21:00',
					'_cam_event_all_day'     => '0',
					'_cam_event_location'    => 'Sala Consiliare del Municipio, Piazza del Municipio 1',
					'_cam_event_organizer'   => 'Comune di Acerno',
					'_cam_lat'               => '40.7519',
					'_cam_lng'               => '15.0308',
					'_cam_app_visible'       => '1',
					'_cam_app_featured'      => '0',
				),
				'terms'        => array(
					'cam_categoria_evento' => array( 'istituzionale' ),
				),
			),
			array(
				'post_name'    => 'trekking-monte-accellica-2026',
				'post_title'   => 'Trekking sul Monte Accellica — Escursione guidata',
				'post_content' => '<p>L\'associazione "Sentieri Picentini" organizza un\'<strong>escursione guidata</strong> sul <strong>Monte Accellica (1.660 m)</strong>, la vetta più alta del Parco Regionale dei Monti Picentini.</p><h3>Dettagli</h3><ul><li><strong>Difficoltà:</strong> Media (E)</li><li><strong>Dislivello:</strong> ~700 m</li><li><strong>Durata:</strong> circa 6 ore A/R</li><li><strong>Ritrovo:</strong> Ore 8:00 — Piazza del Municipio, Acerno</li></ul><h3>Cosa portare</h3><p>Scarpe da trekking, zaino con acqua (almeno 2L), pranzo al sacco, abbigliamento a strati, cappellino e crema solare.</p><p><strong>Iscrizione obbligatoria</strong> entro il ' . gmdate( 'd/m/Y', strtotime( '2026-06-26' ) ) . ' al numero 333 123 4567 o via email: sentierpicentini@gmail.com</p><p>Quota di partecipazione: €10,00 (include assicurazione RC)</p>',
				'post_excerpt' => 'Escursione guidata sul Monte Accellica (1.660 m). 28 giugno, ritrovo ore 8:00.',
				'meta'         => array(
					'_cam_event_start_date'  => '2026-06-28',
					'_cam_event_start_time'  => '08:00',
					'_cam_event_end_date'    => '2026-06-28',
					'_cam_event_end_time'    => '17:00',
					'_cam_event_all_day'     => '0',
					'_cam_event_location'    => 'Parco Regionale dei Monti Picentini, Acerno',
					'_cam_event_organizer'   => 'Associazione Sentieri Picentini',
					'_cam_lat'               => '40.7591',
					'_cam_lng'               => '15.0488',
					'_cam_app_visible'       => '1',
					'_cam_app_featured'      => '0',
				),
				'terms'        => array(
					'cam_categoria_evento' => array( 'sport-tempo-libero', 'ambiente' ),
				),
			),
		);

		foreach ( $eventi as $data ) {
			$this->insert_post(
				array(
					'post_type'    => 'comune_evento',
					'post_name'    => $data['post_name'],
					'post_title'   => $data['post_title'],
					'post_content' => $data['post_content'],
					'post_excerpt' => $data['post_excerpt'],
				),
				$data['meta'],
				$data['terms']
			);
		}
	}

	// -----------------------------------------------------------------------
	// 5. Uffici (6 municipal offices)
	// -----------------------------------------------------------------------

	private function seed_uffici(): void {
		$uffici = array(
			array(
				'post_name'    => 'ufficio-anagrafe-stato-civile',
				'post_title'   => 'Anagrafe e Stato Civile',
				'post_content' => '<p>L\'Ufficio Anagrafe e Stato Civile gestisce l\'iscrizione anagrafica dei cittadini, le pratiche di stato civile (nascite, decessi, matrimoni, divorzi) e il rilascio di certificati.</p><h3>Servizi erogati</h3><ul><li>Rilascio certificati di residenza, cittadinanza, stato di famiglia</li><li>Cambio di residenza e variazioni anagrafiche</li><li>Carta d\'Identità Elettronica (CIE)</li><li>Autenticazione di firme e copie</li><li>Atti di nascita, matrimonio e morte</li><li>Pratiche di cittadinanza italiana</li></ul>',
				'meta'         => array(
					'_cam_phone'        => '089 830011',
					'_cam_email'        => 'anagrafe@comune.acerno.sa.it',
					'_cam_pec'          => 'anagrafe.acerno@pec.it',
					'_cam_address'      => 'Piazza del Municipio 1, 84042 Acerno (SA)',
					'_cam_manager'      => 'Dott.ssa Maria Russo',
					'_cam_office_hours' => "Lunedì–Venerdì: 9:00–12:30\nMartedì e Giovedì: 15:30–17:00",
					'_cam_app_visible'  => '1',
					'_cam_app_featured' => '1',
				),
			),
			array(
				'post_name'    => 'ufficio-tecnico-lavori-pubblici',
				'post_title'   => 'Ufficio Tecnico e Lavori Pubblici',
				'post_content' => '<p>L\'Ufficio Tecnico si occupa della pianificazione urbanistica, dell\'edilizia privata e delle opere pubbliche del territorio comunale.</p><h3>Servizi erogati</h3><ul><li>Permessi di costruire e SCIA</li><li>Condono edilizio</li><li>Certificati di agibilità e abitabilità</li><li>Visure e certificati urbanistici</li><li>Progettazione e direzione lavori pubblici</li><li>Manutenzione strade, marciapiedi e verde pubblico</li></ul>',
				'meta'         => array(
					'_cam_phone'        => '089 830013',
					'_cam_email'        => 'tecnico@comune.acerno.sa.it',
					'_cam_pec'          => 'tecnico.acerno@pec.it',
					'_cam_address'      => 'Piazza del Municipio 1, 84042 Acerno (SA)',
					'_cam_manager'      => 'Geom. Antonio Ferrara',
					'_cam_office_hours' => "Lunedì–Venerdì: 9:00–12:00\nMartedì: 15:00–17:00 (solo su appuntamento)",
					'_cam_app_visible'  => '1',
					'_cam_app_featured' => '0',
				),
			),
			array(
				'post_name'    => 'ufficio-tributi-finanze',
				'post_title'   => 'Tributi e Finanze',
				'post_content' => '<p>L\'Ufficio Tributi gestisce le entrate comunali: IMU, TARI, TOSAP e altri tributi locali. Fornisce assistenza ai contribuenti per il calcolo e il pagamento delle imposte.</p><h3>Servizi erogati</h3><ul><li>Assistenza per il calcolo IMU e TARI</li><li>Rateizzazione dei tributi arretrati</li><li>Accertamenti e rettifiche</li><li>Rimborsi e compensazioni</li><li>TOSAP — occupazione suolo pubblico</li></ul>',
				'meta'         => array(
					'_cam_phone'        => '089 830012',
					'_cam_email'        => 'tributi@comune.acerno.sa.it',
					'_cam_pec'          => 'tributi.acerno@pec.it',
					'_cam_address'      => 'Piazza del Municipio 1, 84042 Acerno (SA)',
					'_cam_manager'      => 'Dott. Roberto Campagna',
					'_cam_office_hours' => "Lunedì, Mercoledì, Venerdì: 9:00–12:00\nMartedì: 15:30–17:00",
					'_cam_app_visible'  => '1',
					'_cam_app_featured' => '0',
				),
			),
			array(
				'post_name'    => 'ufficio-servizi-sociali',
				'post_title'   => 'Servizi Sociali e Assistenza',
				'post_content' => '<p>L\'Ufficio Servizi Sociali si prende cura delle fasce più fragili della comunità, coordinando interventi di sostegno, assistenza e inclusione sociale.</p><h3>Servizi erogati</h3><ul><li>Assistenza domiciliare anziani e disabili</li><li>Contributi per il disagio socio-economico</li><li>Bonus sociale luce e gas</li><li>Iscrizioni asilo nido e servizi per l\'infanzia</li><li>Reddito di Cittadinanza — pratiche comunali</li><li>Centro diurno per anziani</li></ul>',
				'meta'         => array(
					'_cam_phone'        => '089 830015',
					'_cam_email'        => 'sociale@comune.acerno.sa.it',
					'_cam_pec'          => 'sociale.acerno@pec.it',
					'_cam_address'      => 'Via Nazionale 5, 84042 Acerno (SA)',
					'_cam_manager'      => 'Dott.ssa Anna De Luca',
					'_cam_office_hours' => "Lunedì–Venerdì: 9:00–12:30\nLunedì: 15:00–17:30",
					'_cam_app_visible'  => '1',
					'_cam_app_featured' => '0',
				),
			),
			array(
				'post_name'    => 'segreteria-comunale-protocollo',
				'post_title'   => 'Segreteria Comunale e Protocollo',
				'post_content' => '<p>La Segreteria Comunale coordina le attività amministrative del Municipio, gestisce il Protocollo Generale e supporta l\'attività del Consiglio e della Giunta Comunale.</p><h3>Servizi erogati</h3><ul><li>Protocollazione documenti in entrata e uscita</li><li>Accesso agli atti (L. 241/1990)</li><li>Atti del Consiglio e della Giunta Comunale</li><li>Albo Pretorio Online</li><li>Archivio storico</li></ul>',
				'meta'         => array(
					'_cam_phone'        => '089 830011',
					'_cam_email'        => 'segreteria@comune.acerno.sa.it',
					'_cam_pec'          => 'protocollo.acerno@pec.it',
					'_cam_address'      => 'Piazza del Municipio 1, 84042 Acerno (SA)',
					'_cam_manager'      => 'Dott.ssa Carmela Iannone',
					'_cam_office_hours' => "Lunedì–Venerdì: 10:00–12:30",
					'_cam_app_visible'  => '1',
					'_cam_app_featured' => '0',
				),
			),
			array(
				'post_name'    => 'polizia-municipale-acerno',
				'post_title'   => 'Polizia Municipale',
				'post_content' => '<p>Il Corpo di Polizia Municipale garantisce la sicurezza urbana, il rispetto del Codice della Strada e il controllo del territorio comunale.</p><h3>Competenze principali</h3><ul><li>Vigilanza sul traffico e sicurezza stradale</li><li>Controllo edilizio e abusivismo</li><li>Attività di polizia giudiziaria e amministrativa</li><li>Emissione di ordinanze e verbali</li><li>Servizio di pattugliamento del centro storico</li></ul><p>Per emergenze notturne: <strong>112</strong> (Carabinieri)</p>',
				'meta'         => array(
					'_cam_phone'        => '089 830016',
					'_cam_email'        => 'poliziamunicipale@comune.acerno.sa.it',
					'_cam_pec'          => 'pm.acerno@pec.it',
					'_cam_address'      => 'Via Garibaldi 3, 84042 Acerno (SA)',
					'_cam_manager'      => 'Isp. Capo Vincenzo Amabile',
					'_cam_office_hours' => "Lunedì–Venerdì: 8:30–14:00\nSabato: 9:00–12:00",
					'_cam_app_visible'  => '1',
					'_cam_app_featured' => '0',
				),
			),
		);

		foreach ( $uffici as $data ) {
			$this->insert_post(
				array(
					'post_type'    => 'comune_ufficio',
					'post_name'    => $data['post_name'],
					'post_title'   => $data['post_title'],
					'post_content' => $data['post_content'],
				),
				$data['meta']
			);
		}
	}

	// -----------------------------------------------------------------------
	// 6. Luoghi (6 points of interest)
	// -----------------------------------------------------------------------

	private function seed_luoghi(): void {
		$luoghi = array(
			array(
				'post_name'    => 'municipio-acerno',
				'post_title'   => 'Municipio di Acerno',
				'post_content' => '<p>Il <strong>Palazzo Municipale</strong> di Acerno, situato nella centrale Piazza del Municipio, ospita tutti gli uffici comunali e la Sala Consiliare dove si riuniscono il Consiglio e la Giunta Comunale.</p><p>L\'edificio, risalente al XVIII secolo, è stato restaurato nel 2010 e presenta una facciata in pietra locale caratteristica dell\'architettura borgarola cilentana-picentina.</p>',
				'meta'         => array(
					'_cam_lat'        => (string) self::COORDS['municipio']['lat'],
					'_cam_lng'        => (string) self::COORDS['municipio']['lng'],
					'_cam_address'    => 'Piazza del Municipio 1, 84042 Acerno (SA)',
					'_cam_phone'      => '089 830011',
					'_cam_website'    => 'https://www.comune.acerno.sa.it',
					'_cam_app_visible'=> '1',
					'_cam_app_featured'=> '1',
				),
				'terms'        => array(
					'cam_categoria_luogo' => array( 'istituzionale' ),
					'cam_zona'            => array( 'zona-b' ),
				),
			),
			array(
				'post_name'    => 'chiesa-san-donato-vescovo',
				'post_title'   => 'Chiesa di San Donato Vescovo (Patrono)',
				'post_content' => '<p>La <strong>Chiesa di San Donato Vescovo</strong>, patrono di Acerno, è il principale luogo di culto del paese. Costruita nel XIV secolo e più volte ristrutturata, custodisce preziose opere d\'arte sacra tra cui un altare maggiore in marmo policromo del XVIII secolo e tele di scuola napoletana.</p><p>Ogni anno, il 7 agosto, la chiesa è il cuore dei festeggiamenti patronali con la messa solenne e la successiva processione per le vie del centro storico.</p>',
				'meta'         => array(
					'_cam_lat'         => (string) self::COORDS['chiesa']['lat'],
					'_cam_lng'         => (string) self::COORDS['chiesa']['lng'],
					'_cam_address'     => 'Via Matteotti, 84042 Acerno (SA)',
					'_cam_phone'       => '',
					'_cam_website'     => '',
					'_cam_app_visible' => '1',
					'_cam_app_featured'=> '0',
				),
				'terms'        => array(
					'cam_categoria_luogo' => array( 'luoghi-di-culto' ),
					'cam_zona'            => array( 'zona-b' ),
				),
			),
			array(
				'post_name'    => 'santuario-madonna-del-monte',
				'post_title'   => 'Santuario della Madonna del Monte',
				'post_content' => '<p>Il <strong>Santuario della Madonna del Monte</strong> sorge su un colle a circa 2 km dal centro di Acerno, a 950 m di altitudine, immerso nel verde dei boschi picentini. Meta di pellegrinaggio secolare, il santuario custodisce un\'antica statua lignea della Madonna, veneratissima dagli acernesi.</p><p>Il sito è raggiungibile a piedi tramite il sentiero devozionale (45 min dalla piazza del paese) oppure in auto percorrendo la strada comunale.</p>',
				'meta'         => array(
					'_cam_lat'         => (string) self::COORDS['santuario']['lat'],
					'_cam_lng'         => (string) self::COORDS['santuario']['lng'],
					'_cam_address'     => 'Strada del Monte, 84042 Acerno (SA)',
					'_cam_phone'       => '',
					'_cam_website'     => '',
					'_cam_app_visible' => '1',
					'_cam_app_featured'=> '1',
				),
				'terms'        => array(
					'cam_categoria_luogo' => array( 'luoghi-di-culto', 'natura-parchi' ),
				),
			),
			array(
				'post_name'    => 'parco-regionale-monti-picentini',
				'post_title'   => 'Parco Regionale dei Monti Picentini',
				'post_content' => '<p>Il <strong>Parco Regionale dei Monti Picentini</strong> è una delle più importanti aree naturali protette della Campania, con una superficie di circa 63.000 ettari che include boschi di faggio, querce e cerri, sorgenti e torrenti cristallini, e la fauna tipica appenninica.</p><p>Il territorio di Acerno è completamente compreso nel Parco. Dal paese partono diversi sentieri CAI che portano alle cime più alte del massiccio, tra cui il Monte Accellica (1.660 m) e la Cima del Polveracchio (1.790 m).</p><p>Centro visite: Via della Foresta — aperto nei fine settimana da aprile a ottobre.</p>',
				'meta'         => array(
					'_cam_lat'         => (string) self::COORDS['parco']['lat'],
					'_cam_lng'         => (string) self::COORDS['parco']['lng'],
					'_cam_address'     => 'Via della Foresta, 84042 Acerno (SA)',
					'_cam_phone'       => '089 830020',
					'_cam_website'     => 'https://www.parcopicentini.it',
					'_cam_app_visible' => '1',
					'_cam_app_featured'=> '1',
				),
				'terms'        => array(
					'cam_categoria_luogo' => array( 'natura-parchi' ),
				),
			),
			array(
				'post_name'    => 'campo-sportivo-comunale',
				'post_title'   => 'Campo Sportivo Comunale',
				'post_content' => '<p>Il <strong>Campo Sportivo Comunale</strong> di Acerno dispone di un campo di calcio a 11 in erba naturale, tribune coperte con capienza di 500 posti, e un campo da tennis in terra rossa.</p><p>La struttura è gestita dall\'ASD Acerno Calcio e disponibile per prenotazioni da parte di associazioni sportive e privati.</p><p>Per prenotazioni: Ufficio Tecnico Comunale — tel. 089 830013</p>',
				'meta'         => array(
					'_cam_lat'         => (string) self::COORDS['campo_sport']['lat'],
					'_cam_lng'         => (string) self::COORDS['campo_sport']['lng'],
					'_cam_address'     => 'Via dello Sport, 84042 Acerno (SA)',
					'_cam_phone'       => '089 830013',
					'_cam_website'     => '',
					'_cam_app_visible' => '1',
					'_cam_app_featured'=> '0',
				),
				'terms'        => array(
					'cam_categoria_luogo' => array( 'sport' ),
				),
			),
			array(
				'post_name'    => 'fontana-dellorso',
				'post_title'   => 'Fontana dell\'Orso',
				'post_content' => '<p>La <strong>Fontana dell\'Orso</strong> è uno dei simboli più antichi di Acerno. Questa fontana pubblica settecentesca, posta all\'ingresso del centro storico, deve il suo nome alla scultura di un orso — animale un tempo presente nei boschi picentini — che un tempo adornava la vasca.</p><p>L\'acqua della fontana proviene dalla sorgente del Monte Accellica e viene considerata tra le più pure della Campania. È un punto di sosta tradizionale per escursionisti e visitatori.</p>',
				'meta'         => array(
					'_cam_lat'         => (string) self::COORDS['fontana_orso']['lat'],
					'_cam_lng'         => (string) self::COORDS['fontana_orso']['lng'],
					'_cam_address'     => 'Via Fontana, 84042 Acerno (SA)',
					'_cam_phone'       => '',
					'_cam_website'     => '',
					'_cam_app_visible' => '1',
					'_cam_app_featured'=> '0',
				),
				'terms'        => array(
					'cam_categoria_luogo' => array( 'cultura' ),
					'cam_zona'            => array( 'zona-b' ),
				),
			),
		);

		foreach ( $luoghi as $data ) {
			$this->insert_post(
				array(
					'post_type'    => 'comune_luogo',
					'post_name'    => $data['post_name'],
					'post_title'   => $data['post_title'],
					'post_content' => $data['post_content'],
				),
				$data['meta'],
				$data['terms'] ?? array()
			);
		}
	}

	// -----------------------------------------------------------------------
	// 7. Documenti (7 downloadable forms / regulations)
	// -----------------------------------------------------------------------

	private function seed_documenti(): void {
		$base = 'https://comune.acerno.sa.it/documenti/';

		$documenti = array(
			array(
				'post_name'    => 'modulo-domanda-cambio-residenza',
				'post_title'   => 'Modulo domanda cambio di residenza',
				'post_excerpt' => 'Istanza per la dichiarazione di cambio residenza da presentare all\'Ufficio Anagrafe.',
				'meta'         => array(
					'_cam_file_url'    => $base . 'modulo-cambio-residenza.pdf',
					'_cam_file_type'   => 'pdf',
					'_cam_file_size'   => 125,
					'_cam_doc_number'  => 'ANAG-001/2026',
					'_cam_doc_date'    => '2026-01-10',
					'_cam_app_visible' => '1',
					'_cam_app_featured'=> '0',
				),
				'terms'        => array( 'cam_categoria_documento' => array( 'modulistica' ) ),
			),
			array(
				'post_name'    => 'modulo-dichiarazione-tari-2026',
				'post_title'   => 'Dichiarazione TARI 2026',
				'post_excerpt' => 'Modulo per la dichiarazione di inizio, variazione o cessazione ai fini TARI (Tassa Rifiuti).',
				'meta'         => array(
					'_cam_file_url'    => $base . 'dichiarazione-tari-2026.pdf',
					'_cam_file_type'   => 'pdf',
					'_cam_file_size'   => 210,
					'_cam_doc_number'  => 'TRIB-TARI/2026',
					'_cam_doc_date'    => '2026-01-15',
					'_cam_app_visible' => '1',
					'_cam_app_featured'=> '0',
				),
				'terms'        => array( 'cam_categoria_documento' => array( 'modulistica' ) ),
			),
			array(
				'post_name'    => 'istanza-accesso-agli-atti',
				'post_title'   => 'Istanza di accesso agli atti (L. 241/90)',
				'post_excerpt' => 'Modulo per richiedere l\'accesso ai documenti amministrativi ai sensi della Legge 241/1990.',
				'meta'         => array(
					'_cam_file_url'    => $base . 'istanza-accesso-atti.pdf',
					'_cam_file_type'   => 'pdf',
					'_cam_file_size'   => 98,
					'_cam_doc_number'  => 'SEG-002/2026',
					'_cam_doc_date'    => '2026-01-10',
					'_cam_app_visible' => '1',
					'_cam_app_featured'=> '0',
				),
				'terms'        => array( 'cam_categoria_documento' => array( 'modulistica' ) ),
			),
			array(
				'post_name'    => 'modulo-richiesta-certificato-stato-famiglia',
				'post_title'   => 'Richiesta certificato di stato di famiglia',
				'post_excerpt' => 'Modulo per richiedere il certificato di stato di famiglia presso l\'Ufficio Anagrafe.',
				'meta'         => array(
					'_cam_file_url'    => $base . 'richiesta-certificato-stato-famiglia.pdf',
					'_cam_file_type'   => 'pdf',
					'_cam_file_size'   => 88,
					'_cam_doc_number'  => 'ANAG-002/2026',
					'_cam_doc_date'    => '2026-01-10',
					'_cam_app_visible' => '1',
					'_cam_app_featured'=> '0',
				),
				'terms'        => array( 'cam_categoria_documento' => array( 'modulistica' ) ),
			),
			array(
				'post_name'    => 'regolamento-edilizio-comunale-2023',
				'post_title'   => 'Regolamento Edilizio Comunale (agg. 2023)',
				'post_excerpt' => 'Testo aggiornato del Regolamento Edilizio del Comune di Acerno, approvato con delibera CC n. 18 del 14/09/2023.',
				'meta'         => array(
					'_cam_file_url'    => $base . 'regolamento-edilizio-2023.pdf',
					'_cam_file_type'   => 'pdf',
					'_cam_file_size'   => 1845,
					'_cam_doc_number'  => 'DCC-18/2023',
					'_cam_doc_date'    => '2023-09-14',
					'_cam_app_visible' => '1',
					'_cam_app_featured'=> '0',
				),
				'terms'        => array( 'cam_categoria_documento' => array( 'regolamenti', 'urbanistica' ) ),
			),
			array(
				'post_name'    => 'bando-contributi-fotovoltaico-2026-doc',
				'post_title'   => 'Bando contributi impianti fotovoltaici 2026 — Testo integrale',
				'post_excerpt' => 'Testo integrale del bando per la concessione di contributi a fondo perduto per l\'installazione di impianti fotovoltaici.',
				'meta'         => array(
					'_cam_file_url'    => $base . 'bando-fotovoltaico-2026.pdf',
					'_cam_file_type'   => 'pdf',
					'_cam_file_size'   => 345,
					'_cam_doc_number'  => 'DGC-12/2026',
					'_cam_doc_date'    => '2026-04-20',
					'_cam_app_visible' => '1',
					'_cam_app_featured'=> '1',
				),
				'terms'        => array( 'cam_categoria_documento' => array( 'bandi' ) ),
			),
			array(
				'post_name'    => 'piano-regolatore-generale-vigente',
				'post_title'   => 'Piano Regolatore Generale (P.R.G.) vigente',
				'post_excerpt' => 'Elaborati grafici e norme tecniche di attuazione del Piano Regolatore Generale del Comune di Acerno.',
				'meta'         => array(
					'_cam_file_url'    => $base . 'prg-acerno.pdf',
					'_cam_file_type'   => 'pdf',
					'_cam_file_size'   => 4200,
					'_cam_doc_number'  => 'PRG-ACERNO',
					'_cam_doc_date'    => '2019-06-01',
					'_cam_app_visible' => '1',
					'_cam_app_featured'=> '0',
				),
				'terms'        => array( 'cam_categoria_documento' => array( 'urbanistica' ) ),
			),
		);

		foreach ( $documenti as $data ) {
			$this->insert_post(
				array(
					'post_type'    => 'comune_documento',
					'post_name'    => $data['post_name'],
					'post_title'   => $data['post_title'],
					'post_excerpt' => $data['post_excerpt'],
					'post_content' => '',
				),
				$data['meta'],
				$data['terms']
			);
		}
	}

	// -----------------------------------------------------------------------
	// 8. Sondaggi (1 open + 1 closed)
	// -----------------------------------------------------------------------

	private function seed_sondaggi(): void {
		// Survey 1 — OPEN: citizen priorities.
		$q1 = wp_json_encode( array(
			array(
				'id'      => 1,
				'testo'   => 'Quali servizi comunali vorresti vedere migliorati?',
				'tipo'    => 'scelta_multipla',
				'opzioni' => array(
					'Raccolta differenziata',
					'Manutenzione strade e marciapiedi',
					'Illuminazione pubblica',
					'Verde pubblico e parchi',
					'Servizi digitali e app',
					'Trasporto locale',
					'Attività culturali e ricreative',
				),
			),
			array(
				'id'      => 2,
				'testo'   => 'Come valuti la qualità complessiva dei servizi comunali?',
				'tipo'    => 'scelta_singola',
				'opzioni' => array( 'Ottima', 'Buona', 'Discreta', 'Scarsa', 'Insufficiente' ),
			),
			array(
				'id'      => 3,
				'testo'   => 'Con quale frequenza utilizzi l\'app del Comune?',
				'tipo'    => 'scelta_singola',
				'opzioni' => array( 'Tutti i giorni', 'Qualche volta a settimana', 'Una volta a settimana', 'Qualche volta al mese', 'Raramente' ),
			),
			array(
				'id'      => 4,
				'testo'   => 'Hai suggerimenti o segnalazioni particolari per migliorare Acerno?',
				'tipo'    => 'testo',
				'opzioni' => array(),
			),
		) );

		$this->insert_post(
			array(
				'post_type'    => 'cam_sondaggio',
				'post_name'    => 'sondaggio-servizi-digitali-2026',
				'post_title'   => 'Come migliorare Acerno: la tua voce conta',
				'post_content' => 'Aiutaci a capire le tue priorità. Il sondaggio è anonimo e richiede meno di 3 minuti.',
				'post_excerpt' => 'Dacci la tua opinione sui servizi comunali e sull\'app. Sondaggio anonimo.',
			),
			array(
				'_cam_survey_questions' => $q1,
				'_cam_survey_expires'   => '',
				'_cam_app_visible'      => '1',
				'_cam_app_featured'     => '1',
			)
		);

		// Survey 2 — CLOSED: past year satisfaction.
		$q2 = wp_json_encode( array(
			array(
				'id'      => 1,
				'testo'   => 'Sei soddisfatto dei tempi di risposta dell\'Ufficio Anagrafe?',
				'tipo'    => 'scelta_singola',
				'opzioni' => array( 'Molto soddisfatto', 'Abbastanza soddisfatto', 'Poco soddisfatto', 'Per niente soddisfatto' ),
			),
			array(
				'id'      => 2,
				'testo'   => 'La raccolta differenziata nel tuo quartiere funziona bene?',
				'tipo'    => 'scelta_singola',
				'opzioni' => array( 'Sempre', 'Quasi sempre', 'A volte', 'Quasi mai' ),
			),
			array(
				'id'      => 3,
				'testo'   => 'Come valuti la manutenzione delle strade comunali?',
				'tipo'    => 'scelta_singola',
				'opzioni' => array( 'Ottima', 'Buona', 'Sufficiente', 'Insufficiente', 'Pessima' ),
			),
			array(
				'id'      => 4,
				'testo'   => 'L\'app comunale ti ha aiutato a trovare informazioni utili?',
				'tipo'    => 'scelta_singola',
				'opzioni' => array( 'Sì, molto', 'Sì, abbastanza', 'Poco', 'Non l\'ho ancora usata' ),
			),
		) );

		$this->insert_post(
			array(
				'post_type'    => 'cam_sondaggio',
				'post_name'    => 'soddisfazione-servizi-2025',
				'post_title'   => 'Soddisfazione servizi comunali — Anno 2025',
				'post_content' => 'Sondaggio annuale sulla qualità dei servizi erogati dal Comune di Acerno nell\'anno 2025. Il sondaggio è ora chiuso.',
				'post_excerpt' => 'Sondaggio annuale sulla qualità dei servizi comunali. CHIUSO.',
			),
			array(
				'_cam_survey_questions' => $q2,
				'_cam_survey_expires'   => '2026-01-31 23:59:59',
				'_cam_app_visible'      => '1',
				'_cam_app_featured'     => '0',
			)
		);
	}

	// -----------------------------------------------------------------------
	// 9. Calendario raccolta rifiuti — 3 months (dynamic dates)
	// -----------------------------------------------------------------------

	private function seed_waste_schedule(): void {
		global $wpdb;

		// Resolve category IDs by slug.
		$cats = $wpdb->get_results(
			"SELECT id, slug FROM {$wpdb->prefix}cam_waste_categories",
			ARRAY_A
		);

		if ( empty( $cats ) ) {
			$this->log( 'error', 'Nessuna categoria rifiuti trovata — esegui prima l\'attivazione plugin.' );
			return;
		}

		$cat_id = array();
		foreach ( $cats as $c ) {
			$cat_id[ $c['slug'] ] = (int) $c['id'];
		}

		// Fallback IDs if seeded slugs differ.
		$organico       = $cat_id['organico']       ?? 0;
		$plastica       = $cat_id['plastica']        ?? 0;
		$carta          = $cat_id['carta']           ?? 0;
		$vetro          = $cat_id['vetro']           ?? 0;
		$indifferenziato= $cat_id['indifferenziato'] ?? 0;

		if ( ! $organico || ! $plastica || ! $carta || ! $vetro || ! $indifferenziato ) {
			$this->log( 'error', 'Una o più categorie rifiuti mancanti (slug non trovato). Verifica i dati di seed.' );
			return;
		}

		// Check existing entries to stay idempotent.
		$existing_count = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}cam_waste_schedule WHERE collection_date >= CURDATE()"
		);
		if ( $existing_count > 0 ) {
			$this->log( 'skip', "Calendario rifiuti: {$existing_count} voci già presenti — skip." );
			return;
		}

		// Generate 3 months from today.
		$start = strtotime( gmdate( 'Y-m-01' ) );         // first day of current month
		$end   = strtotime( '+3 months', $start );

		// Weekly patterns: zone_slug => [ weekday (0=Sun…6=Sat) => category_id ]
		// Zona B matches the plan: Mon=plastica, Tue=organico, Wed=carta, Thu=organico, Fri=indiff, Sat=vetro
		$patterns = array(
			'zona-a' => array(
				1 => $organico,        // Monday
				3 => $carta,           // Wednesday
				5 => $plastica,        // Friday
			),
			'zona-b' => array(
				1 => $plastica,        // Monday
				2 => $organico,        // Tuesday
				4 => $carta,           // Wednesday
				4 => $organico,        // Thursday — kept below correctly
				5 => $indifferenziato, // Friday
				6 => $vetro,           // Saturday
			),
			'zona-c' => array(
				2 => $organico,        // Tuesday
				4 => $indifferenziato, // Thursday
				6 => $carta,           // Saturday
			),
			'zona-d' => array(
				3 => $organico,        // Wednesday
				5 => $indifferenziato, // Friday
			),
		);

		// Zona B corrected (PHP array keys can't repeat — build manually).
		$patterns['zona-b'] = array(
			1 => $plastica,        // Monday
			2 => $organico,        // Tuesday
			3 => $carta,           // Wednesday
			4 => $organico,        // Thursday
			5 => $indifferenziato, // Friday
			6 => $vetro,           // Saturday
		);

		// Zona A: every-other-Saturday add vetro.
		$vetro_saturdays = array(); // track by week-of-year

		$count  = 0;
		$cursor = $start;

		while ( $cursor < $end ) {
			$dow  = (int) gmdate( 'w', $cursor );  // 0=Sun, 1=Mon…
			$date = gmdate( 'Y-m-d', $cursor );
			$week = (int) gmdate( 'W', $cursor );  // ISO week number

			foreach ( $patterns as $zone => $schedule ) {
				if ( isset( $schedule[ $dow ] ) ) {
					$wpdb->insert(
						$wpdb->prefix . 'cam_waste_schedule',
						array(
							'category_id'     => $schedule[ $dow ],
							'zone_slug'       => $zone,
							'collection_date' => $date,
							'recurrence'      => 'weekly',
							'notes'           => '',
						),
						array( '%d', '%s', '%s', '%s', '%s' )
					);
					$count++;
				}
			}

			// Zona A: vetro every 2 weeks on Saturday.
			if ( 6 === $dow && ! isset( $vetro_saturdays[ $week ] ) && ( $week % 2 === 0 ) ) {
				$wpdb->insert(
					$wpdb->prefix . 'cam_waste_schedule',
					array(
						'category_id'     => $vetro,
						'zone_slug'       => 'zona-a',
						'collection_date' => $date,
						'recurrence'      => 'biweekly',
						'notes'           => '',
					),
					array( '%d', '%s', '%s', '%s', '%s' )
				);
				$vetro_saturdays[ $week ] = true;
				$count++;
			}

			// Zona D: carta every 2 weeks on Monday.
			if ( 1 === $dow && ( $week % 2 === 1 ) ) {
				$wpdb->insert(
					$wpdb->prefix . 'cam_waste_schedule',
					array(
						'category_id'     => $carta,
						'zone_slug'       => 'zona-d',
						'collection_date' => $date,
						'recurrence'      => 'biweekly',
						'notes'           => '',
					),
					array( '%d', '%s', '%s', '%s', '%s' )
				);
				$count++;
			}

			$cursor = strtotime( '+1 day', $cursor );
		}

		$this->log( 'created', "Calendario rifiuti: {$count} voci inserite (3 mesi, 4 zone)." );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Insert a post if no post with the same slug and post_type exists.
	 * Returns the existing or new post ID (0 on failure).
	 *
	 * @param array $post_args  wp_insert_post arguments.
	 * @param array $meta       Post meta key => value.
	 * @param array $terms      Taxonomy => term slug(s).
	 * @return int
	 */
	private function insert_post( array $post_args, array $meta = array(), array $terms = array() ): int {
		$post_type = $post_args['post_type'] ?? 'post';
		$slug      = $post_args['post_name'] ?? '';

		$existing = get_posts( array(
			'post_type'      => $post_type,
			'name'           => $slug,
			'post_status'    => 'any',
			'numberposts'    => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		) );

		if ( $existing ) {
			$this->log( 'skip', "Già presente: [{$post_type}] {$post_args['post_title']}" );
			return (int) $existing[0];
		}

		$defaults = array(
			'post_status' => 'publish',
			'post_author' => $this->author_id,
		);

		$id = wp_insert_post( array_merge( $defaults, $post_args ), true );

		if ( is_wp_error( $id ) ) {
			$this->log( 'error', "Errore [{$post_type}] {$post_args['post_title']}: " . $id->get_error_message() );
			return 0;
		}

		foreach ( $meta as $key => $value ) {
			update_post_meta( $id, $key, $value );
		}

		foreach ( $terms as $taxonomy => $slugs ) {
			wp_set_post_terms( $id, (array) $slugs, $taxonomy );
		}

		$this->log( 'created', "Creato: [{$post_type}] {$post_args['post_title']} (ID: {$id})" );
		return $id;
	}

	/**
	 * Append an entry to the execution log.
	 *
	 * @param string $type    'created'|'skip'|'ok'|'error'
	 * @param string $message Human-readable message.
	 */
	private function log( string $type, string $message ): void {
		$this->log[] = array( 'type' => $type, 'message' => $message );
	}
}
