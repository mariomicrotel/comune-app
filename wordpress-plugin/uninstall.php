<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * If the administrator has opted in to full data removal, this script drops
 * all custom tables, deletes all plugin options, and removes all posts that
 * belong to the plugin's custom post types.
 *
 * @package ComuneAppManager
 * @since   1.0.0
 */

// Bail if not called from the WordPress uninstall flow.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// ------------------------------------------------------------------
// Read the user preference for data deletion.
// ------------------------------------------------------------------
$delete_data = (bool) get_option( 'cam_delete_on_uninstall', false );

if ( ! $delete_data ) {
	// User opted to keep data; nothing to do.
	return;
}

// ------------------------------------------------------------------
// 1. Drop all custom tables.
// ------------------------------------------------------------------

/**
 * List of all 9 custom tables introduced by the plugin.
 * We use the raw table name (without the $wpdb->prefix) so the script
 * works regardless of any custom prefix.
 */
$tables = array(
	$wpdb->prefix . 'cam_device_tokens',
	$wpdb->prefix . 'cam_push_logs',
	$wpdb->prefix . 'cam_segnalazioni',
	$wpdb->prefix . 'cam_survey_responses',
	$wpdb->prefix . 'cam_survey_answers',
	$wpdb->prefix . 'cam_waste_schedule',
	$wpdb->prefix . 'cam_waste_categories',
	$wpdb->prefix . 'cam_api_tokens',
	$wpdb->prefix . 'cam_access_logs',
);

foreach ( $tables as $table ) {
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
}

// ------------------------------------------------------------------
// 2. Delete all plugin options (cam_* prefix).
// ------------------------------------------------------------------

// Retrieve all option names that start with 'cam_'.
$option_names = $wpdb->get_col(
	"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'cam_%'"
);

foreach ( $option_names as $option_name ) {
	delete_option( $option_name );
}

// Also remove the main settings option in case it was stored under a different key.
delete_option( 'cam_settings' );
delete_option( 'cam_db_version' );
delete_option( 'cam_flush_needed' );
delete_option( 'cam_delete_on_uninstall' );

// ------------------------------------------------------------------
// 3. Delete all posts belonging to plugin custom post types.
// ------------------------------------------------------------------

$custom_post_types = array(
	'comune_avviso',
	'comune_evento',
	'comune_ufficio',
	'comune_luogo',
	'comune_documento',
);

foreach ( $custom_post_types as $post_type ) {
	$post_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s",
			$post_type
		)
	);

	foreach ( $post_ids as $post_id ) {
		// wp_delete_post( $id, true ) bypasses the trash and deletes permanently.
		wp_delete_post( (int) $post_id, true );
	}
}

// ------------------------------------------------------------------
// 4. Remove plugin capabilities from all roles.
// ------------------------------------------------------------------

$plugin_caps = array(
	'manage_comune_app',
	'manage_comune_reports',
	'manage_comune_notifications',
	'manage_comune_waste',
	'manage_comune_surveys',
	'read_comune_reports',
);

global $wp_roles;

if ( ! isset( $wp_roles ) ) {
	$wp_roles = new WP_Roles();
}

foreach ( $wp_roles->role_objects as $role ) {
	foreach ( $plugin_caps as $cap ) {
		$role->remove_cap( $cap );
	}
}

// ------------------------------------------------------------------
// 5. Clear any transients created by the plugin.
// ------------------------------------------------------------------

$wpdb->query(
	"DELETE FROM {$wpdb->options}
	 WHERE option_name LIKE '_transient_cam_%'
	    OR option_name LIKE '_transient_timeout_cam_%'"
);
