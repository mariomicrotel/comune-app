<?php
/**
 * Admin menu and page registrations.
 *
 * @package ComuneAppManager
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comune_App_Manager_Admin_Menu
 *
 * Registers the top-level admin menu and all sub-menu pages.
 */
class Comune_App_Manager_Admin_Menu {

	/**
	 * Register menu and sub-menu pages.
	 * Hooked to 'admin_menu'.
	 */
	public function register(): void {
		// Top-level menu.
		add_menu_page(
			__( 'Comune App', 'comune-app-manager' ),
			__( 'Comune App', 'comune-app-manager' ),
			'manage_comune_app',
			'comune-app-manager',
			array( $this, 'render_dashboard' ),
			'dashicons-smartphone',
			30
		);

		// Dashboard (duplicated to give it a proper label in the sub-menu).
		add_submenu_page(
			'comune-app-manager',
			__( 'Dashboard', 'comune-app-manager' ),
			__( 'Dashboard', 'comune-app-manager' ),
			'manage_comune_app',
			'comune-app-manager',
			array( $this, 'render_dashboard' )
		);

		// Avvisi.
		add_submenu_page(
			'comune-app-manager',
			__( 'Avvisi', 'comune-app-manager' ),
			__( 'Avvisi', 'comune-app-manager' ),
			'manage_comune_app',
			'edit.php?post_type=comune_avviso'
		);

		// Eventi.
		add_submenu_page(
			'comune-app-manager',
			__( 'Eventi', 'comune-app-manager' ),
			__( 'Eventi', 'comune-app-manager' ),
			'manage_comune_app',
			'edit.php?post_type=comune_evento'
		);

		// Uffici.
		add_submenu_page(
			'comune-app-manager',
			__( 'Uffici', 'comune-app-manager' ),
			__( 'Uffici', 'comune-app-manager' ),
			'manage_comune_app',
			'edit.php?post_type=comune_ufficio'
		);

		// Luoghi.
		add_submenu_page(
			'comune-app-manager',
			__( 'Luoghi', 'comune-app-manager' ),
			__( 'Luoghi', 'comune-app-manager' ),
			'manage_comune_app',
			'edit.php?post_type=comune_luogo'
		);

		// Documenti.
		add_submenu_page(
			'comune-app-manager',
			__( 'Documenti', 'comune-app-manager' ),
			__( 'Documenti', 'comune-app-manager' ),
			'manage_comune_app',
			'edit.php?post_type=comune_documento'
		);

		// Raccolta Rifiuti.
		add_submenu_page(
			'comune-app-manager',
			__( 'Raccolta Rifiuti', 'comune-app-manager' ),
			__( 'Raccolta Rifiuti', 'comune-app-manager' ),
			'manage_comune_waste',
			'cam-waste-calendar',
			array( $this, 'render_waste_calendar' )
		);

		// Sondaggi.
		add_submenu_page(
			'comune-app-manager',
			__( 'Sondaggi', 'comune-app-manager' ),
			__( 'Sondaggi', 'comune-app-manager' ),
			'manage_comune_surveys',
			'cam-surveys',
			array( $this, 'render_surveys' )
		);

		// Notifiche Push.
		add_submenu_page(
			'comune-app-manager',
			__( 'Notifiche Push', 'comune-app-manager' ),
			__( 'Notifiche Push', 'comune-app-manager' ),
			'manage_comune_notifications',
			'cam-push-notifications',
			array( $this, 'render_push_notifications' )
		);

		// Segnalazioni.
		add_submenu_page(
			'comune-app-manager',
			__( 'Segnalazioni', 'comune-app-manager' ),
			__( 'Segnalazioni', 'comune-app-manager' ),
			'manage_comune_reports',
			'cam-reports',
			array( $this, 'render_reports' )
		);

		// Impostazioni.
		add_submenu_page(
			'comune-app-manager',
			__( 'Impostazioni', 'comune-app-manager' ),
			__( 'Impostazioni', 'comune-app-manager' ),
			'manage_comune_app',
			'cam-settings',
			array( $this, 'render_settings' )
		);
	}

	// -----------------------------------------------------------------------
	// Page render callbacks
	// -----------------------------------------------------------------------

	/**
	 * Render the plugin dashboard page.
	 */
	public function render_dashboard(): void {
		if ( ! current_user_can( 'manage_comune_app' ) ) {
			wp_die( esc_html__( 'Accesso non consentito.', 'comune-app-manager' ) );
		}

		$avvisi_count    = wp_count_posts( 'comune_avviso' )->publish ?? 0;
		$eventi_count    = wp_count_posts( 'comune_evento' )->publish ?? 0;
		$uffici_count    = wp_count_posts( 'comune_ufficio' )->publish ?? 0;
		$documenti_count = wp_count_posts( 'comune_documento' )->publish ?? 0;

		global $wpdb;
		$segnalazioni_pending = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}cam_segnalazioni WHERE status = 'pending'"
		);
		$device_count = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}cam_device_tokens WHERE is_active = 1"
		);

		$settings     = get_option( 'cam_settings', array() );
		$app_active   = (bool) ( $settings['app_active'] ?? true );
		$comune_name  = $settings['comune_name'] ?? get_bloginfo( 'name' );
		?>
		<div class="wrap cam-dashboard">
			<h1><?php esc_html_e( 'Comune App Manager', 'comune-app-manager' ); ?></h1>

			<?php if ( ! $app_active ) : ?>
			<div class="notice notice-warning">
				<p><?php esc_html_e( 'L\'app è attualmente disattivata. Puoi riattivarla dalle Impostazioni.', 'comune-app-manager' ); ?></p>
			</div>
			<?php endif; ?>

			<div class="cam-stats-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin:24px 0">
				<?php
				$stats = array(
					array( 'label' => __( 'Avvisi pubblicati', 'comune-app-manager' ),  'value' => $avvisi_count,    'icon' => 'megaphone',        'link' => admin_url( 'edit.php?post_type=comune_avviso' ) ),
					array( 'label' => __( 'Eventi pubblicati', 'comune-app-manager' ),   'value' => $eventi_count,    'icon' => 'calendar-alt',     'link' => admin_url( 'edit.php?post_type=comune_evento' ) ),
					array( 'label' => __( 'Uffici', 'comune-app-manager' ),              'value' => $uffici_count,    'icon' => 'building',         'link' => admin_url( 'edit.php?post_type=comune_ufficio' ) ),
					array( 'label' => __( 'Documenti', 'comune-app-manager' ),           'value' => $documenti_count, 'icon' => 'media-document',   'link' => admin_url( 'edit.php?post_type=comune_documento' ) ),
					array( 'label' => __( 'Segnalazioni in attesa', 'comune-app-manager' ), 'value' => $segnalazioni_pending, 'icon' => 'flag', 'link' => admin_url( 'admin.php?page=cam-reports' ) ),
					array( 'label' => __( 'Dispositivi registrati', 'comune-app-manager' ), 'value' => $device_count, 'icon' => 'smartphone',      'link' => admin_url( 'admin.php?page=cam-push-notifications' ) ),
				);
				foreach ( $stats as $stat ) :
				?>
				<div class="cam-stat-card" style="background:#fff;border:1px solid #ddd;border-radius:4px;padding:16px;text-align:center">
					<span class="dashicons dashicons-<?php echo esc_attr( $stat['icon'] ); ?>" style="font-size:32px;width:32px;height:32px;color:#1a5276"></span>
					<div style="font-size:28px;font-weight:700;margin:8px 0"><?php echo esc_html( number_format_i18n( (int) $stat['value'] ) ); ?></div>
					<a href="<?php echo esc_url( $stat['link'] ); ?>" style="text-decoration:none;color:#555"><?php echo esc_html( $stat['label'] ); ?></a>
				</div>
				<?php endforeach; ?>
			</div>

			<p style="color:#888">
				<?php
				printf(
					/* translators: %s: comune name */
					esc_html__( 'Comune: %s', 'comune-app-manager' ),
					esc_html( $comune_name )
				);
				?>
				&nbsp;|&nbsp;
				<?php
				printf(
					/* translators: %s: plugin version */
					esc_html__( 'Versione plugin: %s', 'comune-app-manager' ),
					esc_html( CAM_VERSION )
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render the waste calendar admin page.
	 */
	public function render_waste_calendar(): void {
		if ( ! current_user_can( 'manage_comune_waste' ) ) {
			wp_die( esc_html__( 'Accesso non consentito.', 'comune-app-manager' ) );
		}
		echo '<div class="wrap"><h1>' . esc_html__( 'Calendario Raccolta Rifiuti', 'comune-app-manager' ) . '</h1>';
		do_action( 'cam_render_waste_calendar_page' );
		echo '</div>';
	}

	/**
	 * Render the surveys admin page.
	 */
	public function render_surveys(): void {
		if ( ! current_user_can( 'manage_comune_surveys' ) ) {
			wp_die( esc_html__( 'Accesso non consentito.', 'comune-app-manager' ) );
		}
		echo '<div class="wrap"><h1>' . esc_html__( 'Sondaggi', 'comune-app-manager' ) . '</h1>';
		do_action( 'cam_render_surveys_page' );
		echo '</div>';
	}

	/**
	 * Render the push notifications admin page.
	 */
	public function render_push_notifications(): void {
		if ( ! current_user_can( 'manage_comune_notifications' ) ) {
			wp_die( esc_html__( 'Accesso non consentito.', 'comune-app-manager' ) );
		}
		echo '<div class="wrap"><h1>' . esc_html__( 'Notifiche Push', 'comune-app-manager' ) . '</h1>';
		do_action( 'cam_render_push_notifications_page' );
		echo '</div>';
	}

	/**
	 * Render the reports / segnalazioni admin page.
	 */
	public function render_reports(): void {
		if ( ! current_user_can( 'manage_comune_reports' ) ) {
			wp_die( esc_html__( 'Accesso non consentito.', 'comune-app-manager' ) );
		}
		echo '<div class="wrap"><h1>' . esc_html__( 'Segnalazioni Cittadini', 'comune-app-manager' ) . '</h1>';
		do_action( 'cam_render_reports_page' );
		echo '</div>';
	}

	/**
	 * Render the settings admin page.
	 */
	public function render_settings(): void {
		if ( ! current_user_can( 'manage_comune_app' ) ) {
			wp_die( esc_html__( 'Accesso non consentito.', 'comune-app-manager' ) );
		}
		echo '<div class="wrap"><h1>' . esc_html__( 'Impostazioni Comune App', 'comune-app-manager' ) . '</h1>';
		do_action( 'cam_render_settings_page' );
		echo '</div>';
	}
}
