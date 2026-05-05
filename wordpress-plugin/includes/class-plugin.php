<?php
/**
 * Core plugin orchestrator.
 *
 * Wires together every module through WordPress hooks.
 *
 * @package ComuneAppManager
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comune_App_Manager_Plugin
 *
 * Bootstraps all plugin modules and registers the necessary WordPress hooks.
 */
class Comune_App_Manager_Plugin {

	// -----------------------------------------------------------------------
	// Module instances
	// -----------------------------------------------------------------------

	/** @var Comune_App_Manager_Post_Types */
	private Comune_App_Manager_Post_Types $post_types;

	/** @var Comune_App_Manager_Taxonomies */
	private Comune_App_Manager_Taxonomies $taxonomies;

	/** @var Comune_App_Manager_Meta_Boxes */
	private Comune_App_Manager_Meta_Boxes $meta_boxes;

	/** @var Comune_App_Manager_REST_API */
	private Comune_App_Manager_REST_API $rest_api;

	/** @var Comune_App_Manager_Admin_Menu */
	private Comune_App_Manager_Admin_Menu $admin_menu;

	/** @var Comune_App_Manager_Settings */
	private Comune_App_Manager_Settings $settings;

	/** @var Comune_App_Manager_Reports */
	private Comune_App_Manager_Reports $reports;

	/** @var Comune_App_Manager_Waste_Calendar */
	private Comune_App_Manager_Waste_Calendar $waste_calendar;

	/** @var Comune_App_Manager_Surveys */
	private Comune_App_Manager_Surveys $surveys;

	/** @var Comune_App_Manager_Push_Notifications */
	private Comune_App_Manager_Push_Notifications $push_notifications;

	// -----------------------------------------------------------------------
	// Constructor
	// -----------------------------------------------------------------------

	/**
	 * Comune_App_Manager_Plugin constructor.
	 *
	 * Instantiates all modules and registers core hooks.
	 */
	public function __construct() {
		$this->load_modules();
		$this->register_hooks();
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Instantiate every module class.
	 */
	private function load_modules(): void {
		$this->post_types         = new Comune_App_Manager_Post_Types();
		$this->taxonomies         = new Comune_App_Manager_Taxonomies();
		$this->meta_boxes         = new Comune_App_Manager_Meta_Boxes();
		$this->rest_api           = new Comune_App_Manager_REST_API();
		$this->admin_menu         = new Comune_App_Manager_Admin_Menu();
		$this->settings           = new Comune_App_Manager_Settings();
		$this->reports            = new Comune_App_Manager_Reports();
		$this->waste_calendar     = new Comune_App_Manager_Waste_Calendar();
		$this->surveys            = new Comune_App_Manager_Surveys();
		$this->push_notifications = new Comune_App_Manager_Push_Notifications();
	}

	/**
	 * Register WordPress action and filter hooks for every module.
	 */
	private function register_hooks(): void {
		// i18n.
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Custom post types and taxonomies registration.
		add_action( 'init', array( $this, 'register_cpts_and_tax' ) );

		// REST API routes.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Admin menu pages.
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );

		// Meta boxes.
		add_action( 'add_meta_boxes', array( $this->meta_boxes, 'register' ) );
		add_action( 'save_post', array( $this->meta_boxes, 'save' ), 10, 2 );

		// Settings.
		add_action( 'admin_init', array( $this->settings, 'register_settings' ) );

		// Push notifications AJAX / cron.
		add_action( 'wp_ajax_cam_send_push',       array( $this->push_notifications, 'handle_send_push_ajax' ) );
		add_action( 'cam_send_scheduled_push',     array( $this->push_notifications, 'process_scheduled_push' ) );
		add_action( 'wp_ajax_cam_test_firebase',   array( $this, 'handle_test_firebase_ajax' ) );

		// Auto-push when a high/urgent avviso is published.
		add_action( 'transition_post_status', array( $this, 'maybe_push_on_avviso_publish' ), 10, 3 );

		// Default _cam_app_visible = 1 for new posts of the plugin's CPTs.
		add_action( 'wp_insert_post', array( $this, 'set_default_app_visible' ), 10, 3 );

		// Surveys AJAX.
		add_action( 'wp_ajax_nopriv_cam_submit_survey', array( $this->surveys, 'handle_submission' ) );
		add_action( 'wp_ajax_cam_submit_survey', array( $this->surveys, 'handle_submission' ) );

		// Reports AJAX.
		add_action( 'wp_ajax_cam_export_report',          array( $this->reports, 'handle_export' ) );
		add_action( 'wp_ajax_cam_get_dashboard_stats',    array( $this, 'handle_dashboard_stats_ajax' ) );
		add_action( 'wp_ajax_cam_run_demo_seed',          array( $this, 'handle_demo_seed_ajax' ) );

		// DB auto-migration on every request (cheap version_compare short-circuits quickly).
		add_action( 'plugins_loaded', array( $this, 'maybe_run_db_migration' ), 20 );

		// Admin assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	// -----------------------------------------------------------------------
	// Public hook callbacks
	// -----------------------------------------------------------------------

	/**
	 * Load plugin text domain for translations.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'comune-app-manager',
			false,
			dirname( CAM_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Register custom post types and taxonomies.
	 * Hooked to 'init'.
	 */
	public function register_cpts_and_tax(): void {
		$this->post_types->register();
		$this->taxonomies->register();
	}

	/**
	 * Register REST API routes.
	 * Hooked to 'rest_api_init'.
	 */
	public function register_rest_routes(): void {
		$this->rest_api->register_routes();
	}

	/**
	 * Register admin menu pages and sub-pages.
	 * Hooked to 'admin_menu'.
	 */
	public function register_admin_menu(): void {
		$this->admin_menu->register();
	}

	/**
	 * Enqueue admin-side CSS and JS assets.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		// Only enqueue on plugin pages.
		if ( false === strpos( $hook_suffix, 'comune-app' ) && false === strpos( $hook_suffix, 'cam-' ) ) {
			// Also load on post-type edit screens.
			global $post_type;
			$cam_post_types = array(
				'comune_avviso',
				'comune_evento',
				'comune_ufficio',
				'comune_luogo',
				'comune_documento',
			);
			if ( ! in_array( $post_type, $cam_post_types, true ) ) {
				return;
			}
		}

		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_style(
			'cam-admin',
			CAM_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			CAM_VERSION
		);

		wp_enqueue_script(
			'cam-admin',
			CAM_PLUGIN_URL . 'admin/js/admin.js',
			array( 'jquery', 'wp-color-picker' ),
			CAM_VERSION,
			true
		);

		wp_localize_script(
			'cam-admin',
			'camAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'cam_admin_nonce' ),
				'i18n'    => array(
					'confirmDelete' => __( 'Sei sicuro di voler eliminare questo elemento?', 'comune-app-manager' ),
					'sending'       => __( 'Invio in corso…', 'comune-app-manager' ),
					'sent'          => __( 'Inviato con successo.', 'comune-app-manager' ),
					'error'         => __( 'Si è verificato un errore. Riprova.', 'comune-app-manager' ),
				),
			)
		);
	}

	/**
	 * AJAX handler: return dashboard stat counts.
	 * Action: cam_get_dashboard_stats (admin only)
	 */
	public function handle_dashboard_stats_ajax(): void {
		check_ajax_referer( 'cam_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_comune_app' ) ) {
			wp_send_json_error( array( 'message' => __( 'Accesso non consentito.', 'comune-app-manager' ) ) );
		}

		global $wpdb;

		$avvisi  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='comune_avviso' AND post_status='publish'" );
		$reports = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}cam_segnalazioni WHERE status='pending'" );
		$tokens  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}cam_device_tokens WHERE is_active=1" );
		$surveys = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='cam_sondaggio' AND post_status='publish'" );

		wp_send_json_success( array(
			'avvisi'  => $avvisi,
			'reports' => $reports,
			'tokens'  => $tokens,
			'surveys' => $surveys,
		) );
	}

	/**
	 * AJAX handler: run demo seeder and return the execution log.
	 * Action: cam_run_demo_seed (admin only, manage_options).
	 */
	public function handle_demo_seed_ajax(): void {
		check_ajax_referer( 'cam_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Accesso non consentito.', 'comune-app-manager' ) ) );
		}

		$log = Comune_App_Manager_Demo_Seeder::run_once();

		$created = count( array_filter( $log, static fn( $e ) => 'created' === $e['type'] ) );
		$skipped = count( array_filter( $log, static fn( $e ) => 'skip' === $e['type'] ) );
		$errors  = count( array_filter( $log, static fn( $e ) => 'error' === $e['type'] ) );

		wp_send_json_success( array(
			'summary' => sprintf(
				__( 'Completato: %d creati, %d saltati, %d errori.', 'comune-app-manager' ),
				$created,
				$skipped,
				$errors
			),
			'log'     => $log,
		) );
	}

	/**
	 * Run database migrations if the stored schema version is behind the current one.
	 * Hooked to 'plugins_loaded' at priority 20.
	 */
	public function maybe_run_db_migration(): void {
		$installed = get_option( 'cam_db_version', '0.0.0' );

		if ( version_compare( $installed, Comune_App_Manager_DB::SCHEMA_VERSION, '<' ) ) {
			Comune_App_Manager_DB::create_tables();
			update_option( 'cam_db_version', Comune_App_Manager_DB::SCHEMA_VERSION );
		}
	}

	/**
	 * AJAX handler: test Firebase connection from the Settings page.
	 * Action: cam_test_firebase (admin only)
	 */
	public function handle_test_firebase_ajax(): void {
		check_ajax_referer( 'cam_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Accesso non consentito.', 'comune-app-manager' ) ) );
		}

		$firebase = new Comune_App_Manager_Firebase();
		$result   = $firebase->test_connection();

		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => $result['message'] ) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}
	}

	// -----------------------------------------------------------------------
	// Public run method
	// -----------------------------------------------------------------------

	/**
	 * Finalize plugin boot: assign capabilities and flush rewrite rules when needed.
	 *
	 * Call this once from the main plugin file after instantiation.
	 */
	public function run(): void {
		add_action( 'admin_init', array( $this, 'maybe_assign_caps' ) );
		add_action( 'wp_loaded', array( $this, 'maybe_flush_rewrite_rules' ) );
	}

	/**
	 * Assign capabilities to the administrator role if not already done.
	 * Hooked to 'admin_init'.
	 */
	public function maybe_assign_caps(): void {
		$admin_role = get_role( 'administrator' );
		if ( ! $admin_role ) {
			return;
		}

		// Check if the first capability is already present; if so, skip.
		$first_cap = Comune_App_Manager_Capabilities::CAPS[0] ?? 'manage_comune_app';
		if ( ! $admin_role->has_cap( $first_cap ) ) {
			Comune_App_Manager_Capabilities::add_caps();
		}
	}

	/**
	 * Send an automatic push notification when an avviso with alta/urgente priority
	 * transitions to 'publish' for the first time.
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Previous post status.
	 * @param WP_Post $post       The post being transitioned.
	 */
	public function maybe_push_on_avviso_publish( string $new_status, string $old_status, WP_Post $post ): void {
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}
		if ( 'comune_avviso' !== $post->post_type ) {
			return;
		}

		$priorita = get_post_meta( $post->ID, '_cam_priorita', true );
		if ( ! in_array( $priorita, array( 'alta', 'urgente' ), true ) ) {
			return;
		}

		$settings = get_option( 'cam_settings', array() );
		if ( empty( $settings['push_notifications_enabled'] ) ) {
			return;
		}

		$emoji    = 'urgente' === $priorita ? '🚨 ' : '⚠️ ';
		$push_title = mb_substr( $emoji . $post->post_title, 0, 50 );
		$push_body  = mb_substr( wp_trim_words( wp_strip_all_tags( $post->post_content ), 20, '…' ), 0, 200 );
		$this->push_notifications->send_push(
			$push_title,
			$push_body,
			'all',
			array(
				'deep_link_type' => 'avvisi',
				'deep_link_id'   => (string) $post->ID,
			)
		);
	}

	/**
	 * Set _cam_app_visible = 1 on new posts of the plugin's CPTs (if not already set).
	 *
	 * Fires on wp_insert_post. The meta is only written once — subsequent saves
	 * by the admin UI will overwrite it explicitly via the meta-box handler.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an update (true) or insert (false).
	 */
	public function set_default_app_visible( int $post_id, WP_Post $post, bool $update ): void {
		static $cam_cpts = array(
			'comune_avviso',
			'comune_evento',
			'comune_ufficio',
			'comune_luogo',
			'comune_documento',
		);

		if ( $update ) {
			return;
		}

		if ( ! in_array( $post->post_type, $cam_cpts, true ) ) {
			return;
		}

		// Only set when the meta has never been saved (add_post_meta is a no-op if meta exists).
		add_post_meta( $post_id, '_cam_app_visible', '1', true );
	}

	/**
	 * Flush rewrite rules once after activation.
	 * Hooked to 'wp_loaded'.
	 */
	public function maybe_flush_rewrite_rules(): void {
		if ( get_option( 'cam_flush_needed' ) ) {
			flush_rewrite_rules();
			delete_option( 'cam_flush_needed' );
		}
	}
}
