<?php
/**
 * Firebase FCM HTTP v1 integration.
 *
 * @package Comune_App_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comune_App_Manager_Firebase
 *
 * Handles Firebase Cloud Messaging via the HTTP v1 API using JWT / OAuth2.
 */
class Comune_App_Manager_Firebase {

	/** @var string Firebase project ID */
	private string $project_id;

	/** @var string Service-account client e-mail */
	private string $client_email;

	/** @var string Service-account private key (PEM) */
	private string $private_key;

	/** @var string|null In-memory cached bearer token */
	private ?string $cached_access_token = null;

	/** @var int Unix timestamp when the cached token expires */
	private int $token_expires_at = 0;

	/** Transient key for the cached OAuth2 token */
	private const TOKEN_TRANSIENT = 'cam_firebase_access_token';

	/** FCM HTTP v1 endpoint template */
	private const FCM_ENDPOINT = 'https://fcm.googleapis.com/v1/projects/%s/messages:send';

	/** Google OAuth2 token endpoint */
	private const OAUTH_ENDPOINT = 'https://oauth2.googleapis.com/token';

	/** FCM messaging scope */
	private const FCM_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

	/**
	 * Constructor — reads credentials from the cam_settings option.
	 *
	 * Private keys are stored base64-encoded to avoid serialisation issues.
	 */
	public function __construct() {
		$settings = get_option( 'cam_settings', [] );

		$this->project_id   = sanitize_text_field( $settings['firebase_project_id']   ?? '' );
		$this->client_email = sanitize_email( $settings['firebase_client_email']       ?? '' );

		// Private key is stored base64-encoded.
		$encoded_key = $settings['firebase_private_key'] ?? '';
		if ( ! empty( $encoded_key ) ) {
			$this->private_key = base64_decode( $encoded_key, true ) ?: '';
		} else {
			$this->private_key = '';
		}
	}

	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

	/**
	 * Check whether all three required credentials are present.
	 *
	 * @return bool
	 */
	public function is_configured(): bool {
		return ! empty( $this->project_id )
			&& ! empty( $this->client_email )
			&& ! empty( $this->private_key );
	}

	/**
	 * Return a valid OAuth2 bearer token, refreshing when necessary.
	 *
	 * Tokens are cached in a WordPress transient for 55 minutes (the Google
	 * token TTL is 60 minutes; we refresh 5 minutes early).
	 *
	 * @return string Bearer access token.
	 * @throws RuntimeException When the token exchange fails.
	 */
	public function get_access_token(): string {
		// 1. In-memory cache (same request).
		if ( $this->cached_access_token && time() < $this->token_expires_at ) {
			return $this->cached_access_token;
		}

		// 2. Transient cache (across requests).
		$transient = get_transient( self::TOKEN_TRANSIENT );
		if ( $transient && isset( $transient['token'], $transient['expires_at'] ) && time() < (int) $transient['expires_at'] ) {
			$this->cached_access_token = $transient['token'];
			$this->token_expires_at    = (int) $transient['expires_at'];
			return $this->cached_access_token;
		}

		// 3. Fetch a new token.
		$jwt  = $this->generate_jwt();
		$args = [
			'method'  => 'POST',
			'timeout' => 15,
			'headers' => [ 'Content-Type' => 'application/x-www-form-urlencoded' ],
			'body'    => http_build_query( [
				'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
				'assertion'  => $jwt,
			] ),
		];

		$response = wp_remote_post( self::OAUTH_ENDPOINT, $args );

		if ( is_wp_error( $response ) ) {
			Comune_App_Manager_Logger::log_firebase_error(
				'Token exchange WP_Error: ' . $response->get_error_message()
			);
			throw new RuntimeException( 'Firebase token exchange failed: ' . $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== (int) $code || empty( $body['access_token'] ) ) {
			$error_msg = $body['error_description'] ?? $body['error'] ?? 'unknown error';
			Comune_App_Manager_Logger::log_firebase_error(
				"Token exchange HTTP {$code}: {$error_msg}"
			);
			throw new RuntimeException( "Firebase token exchange HTTP {$code}: {$error_msg}" );
		}

		$expires_at = time() + 55 * MINUTE_IN_SECONDS;

		$this->cached_access_token = $body['access_token'];
		$this->token_expires_at    = $expires_at;

		set_transient(
			self::TOKEN_TRANSIENT,
			[ 'token' => $body['access_token'], 'expires_at' => $expires_at ],
			55 * MINUTE_IN_SECONDS
		);

		return $this->cached_access_token;
	}

	/**
	 * Build a signed JWT for the Google OAuth2 token endpoint.
	 *
	 * Algorithm: RS256 (openssl_sign with SHA-256).
	 *
	 * @return string Signed JWT (header.payload.signature).
	 * @throws RuntimeException When signing fails.
	 */
	public function generate_jwt(): string {
		$now = time();

		$header = $this->base64url_encode( (string) wp_json_encode( [
			'alg' => 'RS256',
			'typ' => 'JWT',
		] ) );

		$payload = $this->base64url_encode( (string) wp_json_encode( [
			'iss'   => $this->client_email,
			'sub'   => $this->client_email,
			'aud'   => self::OAUTH_ENDPOINT,
			'iat'   => $now,
			'exp'   => $now + 3600,
			'scope' => self::FCM_SCOPE,
		] ) );

		$signing_input = $header . '.' . $payload;

		$signature = '';
		$pkey      = openssl_pkey_get_private( $this->private_key );

		if ( false === $pkey ) {
			$openssl_error = openssl_error_string();
			Comune_App_Manager_Logger::log_firebase_error( 'Invalid private key: ' . $openssl_error );
			throw new RuntimeException( 'Firebase JWT: invalid private key — ' . $openssl_error );
		}

		$signed = openssl_sign( $signing_input, $signature, $pkey, OPENSSL_ALGO_SHA256 );

		if ( ! $signed ) {
			$openssl_error = openssl_error_string();
			Comune_App_Manager_Logger::log_firebase_error( 'JWT signing failed: ' . $openssl_error );
			throw new RuntimeException( 'Firebase JWT signing failed — ' . $openssl_error );
		}

		return $signing_input . '.' . $this->base64url_encode( $signature );
	}

	/**
	 * Send a push notification to a single FCM registration token.
	 *
	 * @param string $token FCM device token.
	 * @param string $title Notification title.
	 * @param string $body  Notification body.
	 * @param array  $data  Optional key/value data payload.
	 * @return array{success: bool, response: mixed}
	 */
	public function send_to_token( string $token, string $title, string $body, array $data = [] ): array {
		if ( ! $this->is_configured() ) {
			return [ 'success' => false, 'response' => 'Firebase not configured' ];
		}

		try {
			$access_token = $this->get_access_token();
		} catch ( RuntimeException $e ) {
			return [ 'success' => false, 'response' => $e->getMessage() ];
		}

		$message = [
			'message' => [
				'token'        => $token,
				'notification' => [
					'title' => $title,
					'body'  => $body,
				],
			],
		];

		if ( ! empty( $data ) ) {
			// FCM data payload values must be strings.
			$string_data = array_map( 'strval', $data );
			$message['message']['data'] = $string_data;
		}

		$endpoint = sprintf( self::FCM_ENDPOINT, $this->project_id );

		$args = [
			'method'  => 'POST',
			'timeout' => 15,
			'headers' => [
				'Authorization' => 'Bearer ' . $access_token,
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( $message ),
		];

		$response = wp_remote_post( $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			Comune_App_Manager_Logger::log_firebase_error(
				'send_to_token WP_Error for token ' . substr( $token, 0, 20 ) . '…: ' . $response->get_error_message()
			);
			return [ 'success' => false, 'response' => $response->get_error_message() ];
		}

		$code          = wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
		$success       = 200 === (int) $code;

		if ( ! $success ) {
			$error_msg = $response_body['error']['message'] ?? 'HTTP ' . $code;
			Comune_App_Manager_Logger::log_firebase_error(
				'send_to_token failed for token ' . substr( $token, 0, 20 ) . '…: ' . $error_msg
			);
		}

		return [ 'success' => $success, 'response' => $response_body ];
	}

	/**
	 * Send a notification to multiple FCM tokens in batches of 500.
	 *
	 * @param array  $tokens Array of FCM registration tokens.
	 * @param string $title  Notification title.
	 * @param string $body   Notification body.
	 * @param array  $data   Optional data payload.
	 * @return array{sent: int, failed: int, invalid_tokens: array}
	 */
	public function send_multicast( array $tokens, string $title, string $body, array $data = [] ): array {
		$sent           = 0;
		$failed         = 0;
		$invalid_tokens = [];

		$batches = array_chunk( $tokens, 500 );

		foreach ( $batches as $batch ) {
			foreach ( $batch as $token ) {
				$result = $this->send_to_token( $token, $title, $body, $data );

				if ( $result['success'] ) {
					++$sent;
				} else {
					++$failed;

					// Detect unregistered / invalid tokens.
					$error_code = $result['response']['error']['details'][0]['errorCode'] ?? '';
					$error_msg  = $result['response']['error']['message'] ?? '';

					if (
						in_array( $error_code, [ 'UNREGISTERED', 'INVALID_ARGUMENT' ], true ) ||
						str_contains( (string) $error_msg, 'UNREGISTERED' ) ||
						str_contains( (string) $error_msg, 'INVALID_REGISTRATION' )
					) {
						$invalid_tokens[] = $token;
						$this->handle_invalid_token( $token );
					}
				}
			}
		}

		return [
			'sent'           => $sent,
			'failed'         => $failed,
			'invalid_tokens' => $invalid_tokens,
		];
	}

	/**
	 * Remove an invalid/unregistered token from the device-tokens table.
	 *
	 * @param string $token The FCM token to delete.
	 */
	public function handle_invalid_token( string $token ): void {
		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . 'cam_device_tokens',
			[ 'is_active' => 0 ],
			[ 'token' => $token ],
			[ '%d' ],
			[ '%s' ]
		);
	}

	/**
	 * Perform a connectivity test by sending a message to a dummy token.
	 *
	 * A 404 response with UNREGISTERED is actually proof that FCM accepted the
	 * request and returned a meaningful error (credentials are valid).
	 *
	 * @return array{success: bool, message: string}
	 */
	public function test_connection(): array {
		if ( ! $this->is_configured() ) {
			return [
				'success' => false,
				'message' => __( 'Firebase non configurato: mancano project_id, client_email o private_key.', 'comune-app-manager' ),
			];
		}

		try {
			$access_token = $this->get_access_token();
		} catch ( RuntimeException $e ) {
			return [ 'success' => false, 'message' => $e->getMessage() ];
		}

		$dummy_token = 'xxxxxxxx-dummy-token-for-connection-test-xxxxxxxx';

		$message = [
			'message' => [
				'token'        => $dummy_token,
				'notification' => [
					'title' => 'Test',
					'body'  => 'Connection test',
				],
			],
		];

		$endpoint = sprintf( self::FCM_ENDPOINT, $this->project_id );

		$args = [
			'method'  => 'POST',
			'timeout' => 15,
			'headers' => [
				'Authorization' => 'Bearer ' . $access_token,
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( $message ),
		];

		$response = wp_remote_post( $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'message' => sprintf(
					__( 'Errore di connessione: %s', 'comune-app-manager' ),
					$response->get_error_message()
				),
			];
		}

		$code          = wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
		$error_code    = $response_body['error']['details'][0]['errorCode'] ?? '';
		$error_status  = $response_body['error']['status'] ?? '';

		// 404 UNREGISTERED means credentials are correct (dummy token is unknown).
		if (
			404 === (int) $code &&
			( 'UNREGISTERED' === $error_code || 'NOT_FOUND' === $error_status )
		) {
			return [
				'success' => true,
				'message' => __( 'Connessione a Firebase riuscita (token dummy non trovato — comportamento atteso).', 'comune-app-manager' ),
			];
		}

		if ( 200 === (int) $code ) {
			return [
				'success' => true,
				'message' => __( 'Connessione a Firebase riuscita.', 'comune-app-manager' ),
			];
		}

		$error_msg = $response_body['error']['message'] ?? 'HTTP ' . $code;
		Comune_App_Manager_Logger::log_firebase_error( 'test_connection failed: ' . $error_msg );

		return [
			'success' => false,
			'message' => sprintf(
				__( 'Test connessione fallito (HTTP %d): %s', 'comune-app-manager' ),
				$code,
				$error_msg
			),
		];
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Base64-URL encode a string (RFC 4648 §5, no padding).
	 *
	 * @param string $input Raw bytes.
	 * @return string URL-safe base64 without padding.
	 */
	private function base64url_encode( string $input ): string {
		return rtrim( strtr( base64_encode( $input ), '+/', '-_' ), '=' );
	}
}
