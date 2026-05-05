<?php
/**
 * Custom capabilities manager.
 *
 * @package ComuneAppManager
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comune_App_Manager_Capabilities
 *
 * Manages all plugin-specific capabilities for WordPress roles.
 */
class Comune_App_Manager_Capabilities {

	/**
	 * All custom capabilities introduced by this plugin.
	 *
	 * @var string[]
	 */
	const CAPS = array(
		'manage_comune_app',           // Full plugin administration.
		'manage_comune_reports',       // View and export segnalazioni / reports.
		'manage_comune_notifications', // Send and manage push notifications.
		'manage_comune_waste',         // Manage waste-collection calendar.
		'manage_comune_surveys',       // Create and view survey results.
		'read_comune_reports',         // Read-only access to reports.
	);

	/**
	 * Add all plugin capabilities to the administrator role.
	 *
	 * Safe to call multiple times; WordPress ignores duplicate cap additions.
	 */
	public static function add_caps(): void {
		$role = get_role( 'administrator' );

		if ( ! $role instanceof WP_Role ) {
			return;
		}

		foreach ( self::CAPS as $cap ) {
			$role->add_cap( $cap, true );
		}
	}

	/**
	 * Remove all plugin capabilities from the administrator role.
	 *
	 * Called during uninstall to leave no trace of plugin-specific capabilities.
	 */
	public static function remove_caps(): void {
		$role = get_role( 'administrator' );

		if ( ! $role instanceof WP_Role ) {
			return;
		}

		foreach ( self::CAPS as $cap ) {
			$role->remove_cap( $cap );
		}
	}

	/**
	 * Check whether the current user has a specific plugin capability.
	 *
	 * Convenience wrapper around current_user_can() that also validates
	 * the capability name against the plugin's own cap list.
	 *
	 * @param string $cap One of the capability slugs defined in self::CAPS.
	 * @return bool True if the current user holds the capability.
	 */
	public static function current_user_can( string $cap ): bool {
		if ( ! in_array( $cap, self::CAPS, true ) ) {
			return false;
		}
		return current_user_can( $cap );
	}
}
