<?php
/**
 * Plugin logger utility.
 *
 * @package ComuneAppManager
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comune_App_Manager_Logger
 *
 * Lightweight, debug-gated logger that writes to PHP's error_log.
 * Sensitive context keys are scrubbed before any output is emitted.
 */
class Comune_App_Manager_Logger {

	/**
	 * Context keys whose values must never appear in logs.
	 *
	 * @var string[]
	 */
	private const SENSITIVE_KEYS = array(
		'private_key',
		'token',
		'password',
		'secret',
		'api_key',
		'auth_key',
		'firebase_server_key',
		'jwt_secret',
	);

	/**
	 * Log a generic message.
	 *
	 * Only writes to error_log when WP_DEBUG is true.
	 *
	 * @param string  $message Human-readable log message.
	 * @param string  $level   Severity level: 'debug'|'info'|'warning'|'error'. Default 'info'.
	 * @param mixed[] $context Optional associative array of additional data.
	 */
	public static function log( string $message, string $level = 'info', array $context = [] ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$level   = strtoupper( $level );
		$context = self::sanitize_context( $context );

		$entry = sprintf( '[CAM][%s] %s', $level, $message );

		if ( ! empty( $context ) ) {
			$entry .= ' | context: ' . wp_json_encode( $context );
		}

		error_log( $entry ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}

	/**
	 * Log a Firebase-related error.
	 *
	 * @param string     $message  Human-readable description of the error.
	 * @param mixed|null $response Optional HTTP response array or WP_Error to decode.
	 */
	public static function log_firebase_error( string $message, mixed $response = null ): void {
		$context = array();

		if ( $response instanceof WP_Error ) {
			$context['wp_error_code']    = $response->get_error_code();
			$context['wp_error_message'] = $response->get_error_message();
		} elseif ( is_array( $response ) ) {
			$body = wp_remote_retrieve_body( $response );
			$code = wp_remote_retrieve_response_code( $response );

			$context['http_code'] = $code;
			if ( ! empty( $body ) ) {
				$decoded = json_decode( $body, true );
				$context['body'] = is_array( $decoded ) ? $decoded : $body;
			}
		}

		self::log( 'Firebase: ' . $message, 'error', $context );
	}

	/**
	 * Log a REST API error.
	 *
	 * @param string $endpoint The REST route that triggered the error.
	 * @param string $message  Human-readable description of the error.
	 */
	public static function log_rest_error( string $endpoint, string $message ): void {
		self::log(
			sprintf( 'REST error on %s: %s', $endpoint, $message ),
			'error',
			array( 'endpoint' => $endpoint )
		);
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Redact sensitive values from a context array (recursive).
	 *
	 * @param mixed[] $context Raw context array.
	 * @return mixed[] Context with sensitive values replaced by '[REDACTED]'.
	 */
	private static function sanitize_context( array $context ): array {
		$sanitized = array();

		foreach ( $context as $key => $value ) {
			if ( in_array( strtolower( (string) $key ), self::SENSITIVE_KEYS, true ) ) {
				$sanitized[ $key ] = '[REDACTED]';
			} elseif ( is_array( $value ) ) {
				$sanitized[ $key ] = self::sanitize_context( $value );
			} else {
				$sanitized[ $key ] = $value;
			}
		}

		return $sanitized;
	}
}
