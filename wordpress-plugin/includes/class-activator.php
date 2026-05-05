<?php
/**
 * Plugin activation handler.
 *
 * @package ComuneAppManager
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comune_App_Manager_Activator
 *
 * Runs once when the plugin is activated via the WordPress admin.
 */
class Comune_App_Manager_Activator {

	/**
	 * Plugin activation routine.
	 *
	 * - Creates custom database tables.
	 * - Assigns custom capabilities to the administrator role.
	 * - Seeds default plugin options.
	 * - Flags rewrite rules for flushing on next load.
	 */
	public static function activate(): void {
		// Ensure the DB class is available (loaded by the main plugin file,
		// but we guard here in case activation fires before autoloading).
		if ( ! class_exists( 'Comune_App_Manager_DB' ) ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-db.php';
		}
		if ( ! class_exists( 'Comune_App_Manager_Capabilities' ) ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-capabilities.php';
		}

		// 1. Create custom database tables.
		Comune_App_Manager_DB::create_tables();

		// 2. Add custom capabilities to the administrator role.
		Comune_App_Manager_Capabilities::add_caps();

		// 3. Seed default plugin options (only if not already set).
		self::seed_default_options();

		// 4. Set DB version.
		update_option( 'cam_db_version', Comune_App_Manager_DB::SCHEMA_VERSION );

		// 5. Flag that rewrite rules need to be flushed on the next request.
		update_option( 'cam_flush_needed', true );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Write default values for the cam_settings option and any standalone options
	 * only when they don't already exist (preserves user customisation on re-activation).
	 */
	private static function seed_default_options(): void {
		$defaults = array(
			'comune_name'                => '',
			'comune_code'                => '',
			'primary_color'              => '#1a5276',
			'secondary_color'            => '#2e86c1',
			'app_active'                 => true,
			'maintenance_mode'           => false,
			'maintenance_message'        => __( 'App in manutenzione. Torneremo presto.', 'comune-app-manager' ),
			'app_store_url'              => '',
			'play_store_url'             => '',
			'firebase_project_id'        => '',
			'firebase_client_email'      => '',
			'firebase_private_key'       => '',
			'push_notifications_enabled' => false,
			'waste_calendar_enabled'     => true,
			'surveys_enabled'            => true,
			'reports_enabled'            => true,
			'reports_email'              => get_option( 'admin_email', '' ),
			'delete_on_uninstall'        => false,
			'api_rate_limit'             => 60,
			'api_rate_window'            => 3600,
			'jwt_secret'                 => wp_generate_password( 64, true, true ),
			'token_expiry_hours'         => 24,
			'privacy_policy_url'         => '',
			'tos_url'                    => '',
			'support_email'              => get_option( 'admin_email', '' ),
			'support_phone'              => '',
		);

		$existing = get_option( 'cam_settings', array() );

		// Merge: existing values take precedence; only new keys get default values.
		$merged = array_merge( $defaults, $existing );

		update_option( 'cam_settings', $merged, true );

		// Standalone option used by uninstall.php.
		if ( false === get_option( 'cam_delete_on_uninstall' ) ) {
			add_option( 'cam_delete_on_uninstall', false, '', true );
		}
	}
}
