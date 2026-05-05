<?php
/**
 * Plugin deactivation handler.
 *
 * @package ComuneAppManager
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comune_App_Manager_Deactivator
 *
 * Runs once when the plugin is deactivated via the WordPress admin.
 * Data and tables are intentionally preserved; removal is handled by uninstall.php.
 */
class Comune_App_Manager_Deactivator {

	/**
	 * Plugin deactivation routine.
	 *
	 * Flushes rewrite rules so the custom post type permalinks are removed
	 * from the global rewrite rule list until the plugin is re-activated.
	 */
	public static function deactivate(): void {
		// Clear any pending cron events added by this plugin.
		self::clear_scheduled_events();

		// Flush rewrite rules to remove CPT permalink structures.
		flush_rewrite_rules();
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Unschedule all recurring cron events registered by the plugin.
	 */
	private static function clear_scheduled_events(): void {
		$hooks = array(
			'cam_send_scheduled_push',
			'cam_cleanup_old_survey_responses',
			'cam_cleanup_expired_tokens',
		);

		foreach ( $hooks as $hook ) {
			$timestamp = wp_next_scheduled( $hook );
			if ( false !== $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
			}
			// Also clear all future instances (for non-single events).
			wp_clear_scheduled_hook( $hook );
		}
	}
}
