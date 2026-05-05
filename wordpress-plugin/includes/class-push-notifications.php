<?php
/**
 * Push notifications orchestration.
 *
 * Wires Firebase HTTP v1 sends to WordPress hooks, AJAX and WP-Cron.
 * The admin UI is rendered by admin/views/push-send.php.
 *
 * @package ComuneAppManager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comune_App_Manager_Push_Notifications
 */
class Comune_App_Manager_Push_Notifications {

	/**
	 * Constructor: register hooks.
	 */
	public function __construct() {
		add_action( 'cam_render_push_notifications_page', array( $this, 'render_page' ) );
		// wp_ajax_cam_send_push and cam_send_scheduled_push are registered
		// centrally in Comune_App_Manager_Plugin::register_hooks() to avoid double-firing.
	}

	// -----------------------------------------------------------------------
	// Admin page
	// -----------------------------------------------------------------------

	/**
	 * Include the push-send view (hooked to cam_render_push_notifications_page).
	 */
	public function render_page(): void {
		include CAM_PLUGIN_DIR . 'admin/views/push-send.php';
	}

	// -----------------------------------------------------------------------
	// AJAX handler (called by the admin JS in push-send.php)
	// -----------------------------------------------------------------------

	/**
	 * Handle AJAX push send from the admin form.
	 */
	public function handle_send_push_ajax(): void {
		check_ajax_referer( 'cam_send_push', 'cam_push_nonce' );

		if ( ! current_user_can( 'manage_comune_notifications' ) ) {
			wp_send_json_error( array( 'message' => __( 'Accesso non consentito.', 'comune-app-manager' ) ) );
		}

		$title    = sanitize_text_field( wp_unslash( $_POST['push_title'] ?? '' ) );
		$body     = sanitize_textarea_field( wp_unslash( $_POST['push_body'] ?? '' ) );
		$target   = sanitize_key( $_POST['push_target'] ?? 'all' );
		$schedule = sanitize_text_field( wp_unslash( $_POST['push_schedule'] ?? '' ) );
		$data     = array(
			'deep_link_type' => sanitize_key( $_POST['deep_link_type'] ?? 'home' ),
			'deep_link_id'   => (string) absint( $_POST['deep_link_id'] ?? 0 ),
		);

		if ( empty( $title ) || empty( $body ) ) {
			wp_send_json_error( array( 'message' => __( 'Titolo e testo sono obbligatori.', 'comune-app-manager' ) ) );
		}

		if ( mb_strlen( $title ) > 50 ) {
			wp_send_json_error( array( 'message' => __( 'Il titolo non può superare 50 caratteri.', 'comune-app-manager' ) ) );
		}

		if ( mb_strlen( $body ) > 200 ) {
			wp_send_json_error( array( 'message' => __( 'Il testo non può superare 200 caratteri.', 'comune-app-manager' ) ) );
		}

		// Scheduled send via WP-Cron.
		if ( ! empty( $schedule ) ) {
			$ts = strtotime( $schedule );
			if ( $ts && $ts > time() ) {
				update_option( 'cam_scheduled_push_' . $ts, compact( 'title', 'body', 'target', 'data' ) );
				wp_schedule_single_event( $ts, 'cam_send_scheduled_push', array( $ts ) );
				wp_send_json_success( array( 'message' => __( 'Notifica programmata.', 'comune-app-manager' ) ) );
			}
		}

		$result = $this->send_push( $title, $body, $target, $data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: 1: success count  2: failure count */
				__( 'Inviata: %1$d successi, %2$d errori.', 'comune-app-manager' ),
				$result['success'],
				$result['failure']
			),
			'details' => $result,
		) );
	}

	/**
	 * Process a WP-Cron scheduled push.
	 *
	 * @param int $timestamp The option key timestamp for the stored job.
	 */
	public function process_scheduled_push( int $timestamp ): void {
		$job = get_option( 'cam_scheduled_push_' . $timestamp );
		if ( ! is_array( $job ) ) {
			return;
		}
		delete_option( 'cam_scheduled_push_' . $timestamp );
		$this->send_push(
			$job['title'] ?? '',
			$job['body']  ?? '',
			$job['target'] ?? 'all',
			$job['data']   ?? array()
		);
	}

	// -----------------------------------------------------------------------
	// Core send methods (use Firebase HTTP v1)
	// -----------------------------------------------------------------------

	/**
	 * Send a push notification to an explicit list of FCM tokens.
	 *
	 * Used for targeted sends (e.g. notifying the device that submitted a report).
	 *
	 * @param string[] $tokens FCM token strings.
	 * @param string   $title  Notification title (≤ 50 chars recommended).
	 * @param string   $body   Notification body (≤ 200 chars recommended).
	 * @param array    $data   Key/value data payload strings.
	 * @return array{recipients:int,success:int,failure:int}|WP_Error
	 */
	public function send_push_to_tokens(
		array  $tokens,
		string $title,
		string $body,
		array  $data = array()
	): array|WP_Error {
		if ( empty( $tokens ) ) {
			return array( 'recipients' => 0, 'success' => 0, 'failure' => 0 );
		}

		$firebase = new Comune_App_Manager_Firebase();
		if ( ! $firebase->is_configured() ) {
			return new WP_Error( 'firebase_not_configured', __( 'Firebase non configurato.', 'comune-app-manager' ) );
		}

		$data['click_action'] = 'FLUTTER_NOTIFICATION_CLICK';
		$result = $firebase->send_multicast( $tokens, $title, $body, $data );

		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'cam_push_logs',
			array(
				'title'         => $title,
				'body'          => $body,
				'target_type'   => 'device',
				'platform'      => 'all',
				'recipients'    => count( $tokens ),
				'success_count' => $result['sent'],
				'failure_count' => $result['failed'],
				'sent_by'       => get_current_user_id() ?: null,
				'sent_at'       => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s' )
		);

		return array(
			'recipients' => count( $tokens ),
			'success'    => $result['sent'],
			'failure'    => $result['failed'],
		);
	}

	/**
	 * Send a push notification via Firebase HTTP v1.
	 *
	 * @param string $title  Notification title.
	 * @param string $body   Notification body.
	 * @param string $target 'all' | 'ios' | 'android' | 'zone:{slug}'
	 * @param array  $data   Key/value data payload strings.
	 * @return array{recipients:int,success:int,failure:int}|WP_Error
	 */
	public function send_push(
		string $title,
		string $body,
		string $target = 'all',
		array  $data   = array()
	): array|WP_Error {

		$firebase = new Comune_App_Manager_Firebase();

		if ( ! $firebase->is_configured() ) {
			return new WP_Error(
				'firebase_not_configured',
				__( 'Firebase non configurato. Inserisci le credenziali nelle Impostazioni.', 'comune-app-manager' )
			);
		}

		global $wpdb;
		$tokens_table = $wpdb->prefix . 'cam_device_tokens';

		// Build token query by target.
		if ( str_starts_with( $target, 'zone:' ) ) {
			$zone_slug = sanitize_key( substr( $target, 5 ) );
			$tokens    = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT token FROM {$tokens_table}
					 WHERE is_active = 1
					   AND JSON_CONTAINS(zones, JSON_QUOTE(%s))",
					$zone_slug
				)
			);
		} elseif ( in_array( $target, array( 'ios', 'android' ), true ) ) {
			$tokens = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT token FROM {$tokens_table} WHERE is_active = 1 AND platform = %s",
					$target
				)
			);
		} else {
			$tokens = $wpdb->get_col(
				"SELECT token FROM {$tokens_table} WHERE is_active = 1"
			);
		}

		if ( empty( $tokens ) ) {
			return array( 'recipients' => 0, 'success' => 0, 'failure' => 0 );
		}

		// Add Flutter click-action to data payload.
		$data['click_action'] = 'FLUTTER_NOTIFICATION_CLICK';

		$result = $firebase->send_multicast( $tokens, $title, $body, $data );

		// Log the send.
		$wpdb->insert(
			$wpdb->prefix . 'cam_push_logs',
			array(
				'title'             => $title,
				'body'              => $body,
				'target_type'       => $target,
				'platform'          => in_array( $target, array( 'ios', 'android' ), true ) ? $target : 'all',
				'recipients'        => count( $tokens ),
				'success_count'     => $result['sent'],
				'failure_count'     => $result['failed'],
				'sent_by'           => get_current_user_id() ?: null,
				'sent_at'           => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s' )
		);

		return array(
			'recipients' => count( $tokens ),
			'success'    => $result['sent'],
			'failure'    => $result['failed'],
		);
	}
}
