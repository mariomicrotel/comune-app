<?php
/**
 * General utility helpers.
 *
 * @package ComuneAppManager
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comune_App_Manager_Utils
 *
 * Static utility methods shared across the entire plugin.
 */
class Comune_App_Manager_Utils {

	// -----------------------------------------------------------------------
	// Public-facing code generation
	// -----------------------------------------------------------------------

	/**
	 * Generate a human-readable public code for a post (e.g. a segnalazione).
	 *
	 * Format: SEG-{YEAR}-{ID zero-padded to 6 digits}
	 * Example: SEG-2026-000123
	 *
	 * @param int $id WordPress post ID.
	 * @return string Formatted public code.
	 */
	public static function generate_public_code( int $id ): string {
		return sprintf( 'SEG-%s-%06d', gmdate( 'Y' ), $id );
	}

	// -----------------------------------------------------------------------
	// Platform sanitization
	// -----------------------------------------------------------------------

	/**
	 * Validate and normalise a device platform string.
	 *
	 * @param string $platform Raw platform value supplied by the client.
	 * @return string 'ios' or 'android'.
	 * @throws InvalidArgumentException When the value is not a recognised platform.
	 */
	public static function sanitize_platform( string $platform ): string {
		$normalised = strtolower( trim( $platform ) );

		if ( ! in_array( $normalised, array( 'ios', 'android' ), true ) ) {
			throw new InvalidArgumentException(
				sprintf(
					/* translators: %s: supplied platform value */
					esc_html__( 'Piattaforma non valida: "%s". Valori accettati: ios, android.', 'comune-app-manager' ),
					esc_html( $platform )
				)
			);
		}

		return $normalised;
	}

	// -----------------------------------------------------------------------
	// Client IP detection
	// -----------------------------------------------------------------------

	/**
	 * Return a sanitized version of the client's IP address.
	 *
	 * Inspects common proxy / load-balancer headers in order of trust.
	 * Falls back to REMOTE_ADDR.
	 *
	 * @return string Sanitized IP address, or '0.0.0.0' if none could be determined.
	 */
	public static function get_client_ip(): string {
		// REMOTE_ADDR is always the direct TCP peer — unforgeable.
		$remote = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );

		// Trust forwarded headers only when the direct peer is a known proxy/LB.
		// Covers: Cloudflare CIDR, private RFC-1918 ranges, loopback.
		$trusted_proxy = self::is_trusted_proxy( $remote );

		if ( $trusted_proxy ) {
			$forwarded_headers = array(
				'HTTP_CF_CONNECTING_IP', // Cloudflare — set by CF edge, not client.
				'HTTP_X_REAL_IP',        // Nginx proxy.
				'HTTP_X_FORWARDED_FOR',  // Standard proxy chain (left-most = client).
			);

			foreach ( $forwarded_headers as $header ) {
				if ( empty( $_SERVER[ $header ] ) ) {
					continue;
				}
				$raw = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
				$ip  = trim( explode( ',', $raw )[0] );
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $ip;
				}
			}
		}

		if ( filter_var( $remote, FILTER_VALIDATE_IP ) ) {
			return $remote;
		}

		return '0.0.0.0';
	}

	/**
	 * Return true when an IP belongs to a trusted proxy (private ranges or loopback).
	 *
	 * @param string $ip IP address string.
	 * @return bool
	 */
	private static function is_trusted_proxy( string $ip ): bool {
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return false;
		}
		// Private / reserved ranges that could be a reverse proxy.
		return ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
	}

	// -----------------------------------------------------------------------
	// Rate limiting
	// -----------------------------------------------------------------------

	/**
	 * Check and enforce a sliding-window rate limit using WordPress transients.
	 *
	 * Returns true when the request is within the allowed limit (i.e. it should
	 * proceed), false when the limit has been exceeded.
	 *
	 * Each call that is within the limit increments the counter.
	 *
	 * @param string $key    A unique identifier for the rate-limit bucket
	 *                       (e.g. 'cam_rl_192.168.1.1_register').
	 * @param int    $max    Maximum number of requests allowed within the window.
	 * @param int    $window Window length in seconds. Default 3600 (1 hour).
	 * @return bool True if the request is allowed, false if rate-limited.
	 */
	public static function check_rate_limit( string $key, int $max = 10, int $window = 3600 ): bool {
		$transient_key = 'cam_rl_' . md5( $key );

		$current = (int) get_transient( $transient_key );

		if ( $current >= $max ) {
			return false; // Rate limit exceeded.
		}

		if ( 0 === $current ) {
			// First hit: set transient with full expiry window.
			set_transient( $transient_key, 1, $window );
		} else {
			// Subsequent hits: increment without resetting the expiry.
			// We use a separate "expires" transient to preserve the original TTL.
			$expires_key = $transient_key . '_exp';
			$expires_at  = (int) get_transient( $expires_key );

			if ( 0 === $expires_at ) {
				// Safety fallback: reset with full window.
				set_transient( $transient_key, $current + 1, $window );
				set_transient( $expires_key, time() + $window, $window );
			} else {
				$remaining = max( 1, $expires_at - time() );
				set_transient( $transient_key, $current + 1, $remaining );
			}
		}

		// Store the absolute expiry time on the first hit.
		$expires_key = $transient_key . '_exp';
		if ( false === get_transient( $expires_key ) ) {
			set_transient( $expires_key, time() + $window, $window );
		}

		return true; // Request allowed.
	}

	// -----------------------------------------------------------------------
	// Standardised REST response
	// -----------------------------------------------------------------------

	/**
	 * Build a standardised WP_REST_Response.
	 *
	 * Success shape:
	 *   { "success": true, "data": {…}, "message": "…" }
	 *
	 * Error shape:
	 *   { "success": false, "error": { "code": "…", "message": "…" } }
	 *
	 * @param bool        $success       Whether the operation succeeded.
	 * @param mixed|null  $data          Response payload (used when $success is true).
	 * @param string      $message       Human-readable success message.
	 * @param string|null $error_code    Machine-readable error code slug (used when $success is false).
	 * @param string      $error_message Human-readable error description (used when $success is false).
	 * @return WP_REST_Response
	 */
	public static function format_response(
		bool $success,
		mixed $data = null,
		string $message = '',
		?string $error_code = null,
		string $error_message = ''
	): WP_REST_Response {
		if ( $success ) {
			$body = array(
				'success' => true,
				'data'    => $data ?? new stdClass(),
				'message' => $message,
			);
			return new WP_REST_Response( $body, 200 );
		}

		$body = array(
			'success' => false,
			'error'   => array(
				'code'    => $error_code ?? 'unknown_error',
				'message' => $error_message ?: __( 'Si è verificato un errore imprevisto.', 'comune-app-manager' ),
			),
		);

		// Map common error codes to HTTP status codes.
		$http_status = self::error_code_to_http_status( $error_code ?? '' );

		return new WP_REST_Response( $body, $http_status );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Map a plugin error code slug to an appropriate HTTP status code.
	 *
	 * @param string $error_code Plugin error code slug.
	 * @return int HTTP status code.
	 */
	private static function error_code_to_http_status( string $error_code ): int {
		$map = array(
			'unauthorized'       => 401,
			'forbidden'          => 403,
			'not_found'          => 404,
			'rate_limited'       => 429,
			'validation_failed'  => 422,
			'invalid_param'      => 400,
			'bad_request'        => 400,
			'server_error'       => 500,
			'service_unavailable'=> 503,
		);

		return $map[ $error_code ] ?? 400;
	}
}
