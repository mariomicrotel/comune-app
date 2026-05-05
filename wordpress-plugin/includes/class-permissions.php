<?php
/**
 * Permission helpers for Comune App Manager.
 *
 * @package Comune_App_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comune_App_Manager_Permissions
 *
 * Static helper methods used by admin pages and REST API callbacks to check
 * whether the current user holds a given Comune App Manager capability.
 */
class Comune_App_Manager_Permissions {

	// -------------------------------------------------------------------------
	// Standard capability checks
	// -------------------------------------------------------------------------

	/**
	 * Whether the current user can manage the app in general.
	 *
	 * @return bool
	 */
	public static function can_manage_app(): bool {
		return current_user_can( 'manage_comune_app' );
	}

	/**
	 * Whether the current user can manage citizen reports (segnalazioni).
	 *
	 * @return bool
	 */
	public static function can_manage_reports(): bool {
		return current_user_can( 'manage_comune_reports' );
	}

	/**
	 * Whether the current user can send / manage push notifications.
	 *
	 * @return bool
	 */
	public static function can_manage_notifications(): bool {
		return current_user_can( 'manage_comune_notifications' );
	}

	/**
	 * Whether the current user can manage the waste calendar.
	 *
	 * @return bool
	 */
	public static function can_manage_waste(): bool {
		return current_user_can( 'manage_comune_waste' );
	}

	/**
	 * Whether the current user can manage surveys (sondaggi).
	 *
	 * @return bool
	 */
	public static function can_manage_surveys(): bool {
		return current_user_can( 'manage_comune_surveys' );
	}

	/**
	 * Whether the current user can read (view) citizen reports.
	 *
	 * Users with either the read-only or the full management capability qualify.
	 *
	 * @return bool
	 */
	public static function can_read_reports(): bool {
		return current_user_can( 'read_comune_reports' )
			|| current_user_can( 'manage_comune_reports' );
	}

	// -------------------------------------------------------------------------
	// REST API permission callbacks
	// -------------------------------------------------------------------------

	/**
	 * REST permission callback: user must be logged in and able to manage the app.
	 *
	 * @return bool
	 */
	public static function rest_permission_manage_app(): bool {
		return is_user_logged_in() && self::can_manage_app();
	}

	/**
	 * REST permission callback: user must be logged in and able to manage reports.
	 *
	 * @return bool
	 */
	public static function rest_permission_manage_reports(): bool {
		return is_user_logged_in() && self::can_manage_reports();
	}

	/**
	 * REST permission callback: user must be logged in and able to send notifications.
	 *
	 * @return bool
	 */
	public static function rest_permission_send_notifications(): bool {
		return is_user_logged_in() && self::can_manage_notifications();
	}
}
