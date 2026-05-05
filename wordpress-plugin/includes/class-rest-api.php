<?php
/**
 * REST API route registrations.
 *
 * @package ComuneAppManager
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comune_App_Manager_REST_API
 *
 * Registers all custom REST API endpoints under the namespace 'comune-app/v1'.
 * Every public endpoint applies rate limiting; authenticated endpoints also
 * verify a JWT token stored in the Authorization header.
 */
class Comune_App_Manager_REST_API {

	/** REST namespace. */
	const NAMESPACE = 'comune-app/v1';

	/** Seconds to wait before retrying after a rate-limit rejection (updated per request). */
	private int $rate_limit_retry_after = 60;

	/** Cacheable GET routes and their TTL in seconds. */
	private const CACHE_TTLS = array(
		'/config'       => 300,
		'/rifiuti/zone' => 3600,
		'/uffici'       => 600,
		'/luoghi'       => 600,
		'/documenti'    => 600,
	);

	/**
	 * Register all REST routes.
	 * Hooked to 'rest_api_init'.
	 */
	public function register_routes(): void {
		add_filter( 'rest_post_dispatch', array( $this, 'add_cache_headers' ), 10, 3 );
		// ----------------------------------------------------------------
		// Auth
		// ----------------------------------------------------------------
		register_rest_route( self::NAMESPACE, '/auth/login', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'login' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'username' => array( 'required' => true, 'sanitize_callback' => 'sanitize_user' ),
				'password' => array( 'required' => true ),
			),
		) );

		register_rest_route( self::NAMESPACE, '/auth/refresh', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'refresh_token' ),
			'permission_callback' => array( $this, 'check_auth' ),
		) );

		// ----------------------------------------------------------------
		// Avvisi
		// ----------------------------------------------------------------
		register_rest_route( self::NAMESPACE, '/avvisi', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_avvisi' ),
			'permission_callback' => '__return_true',
			'args'                => array_merge( $this->pagination_args(), array(
				'priorita' => array(
					'sanitize_callback' => 'sanitize_key',
					'validate_callback' => static fn( $v ) => in_array( $v, array( 'bassa', 'media', 'alta', 'urgente', '' ), true ),
				),
			) ),
		) );

		register_rest_route( self::NAMESPACE, '/avvisi/(?P<id>\d+)', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_avviso' ),
			'permission_callback' => '__return_true',
			'args'                => array( 'id' => array( 'validate_callback' => 'is_numeric' ) ),
		) );

		// ----------------------------------------------------------------
		// Eventi
		// ----------------------------------------------------------------
		register_rest_route( self::NAMESPACE, '/eventi', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_eventi' ),
			'permission_callback' => '__return_true',
			'args'                => array_merge( $this->pagination_args(), array(
				'from' => array( 'sanitize_callback' => 'sanitize_text_field' ),
				'to'   => array( 'sanitize_callback' => 'sanitize_text_field' ),
			) ),
		) );

		register_rest_route( self::NAMESPACE, '/eventi/(?P<id>\d+)', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_evento' ),
			'permission_callback' => '__return_true',
			'args'                => array( 'id' => array( 'validate_callback' => 'is_numeric' ) ),
		) );

		// ----------------------------------------------------------------
		// Uffici
		// ----------------------------------------------------------------
		register_rest_route( self::NAMESPACE, '/uffici', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_uffici' ),
			'permission_callback' => '__return_true',
			'args'                => $this->pagination_args(),
		) );

		register_rest_route( self::NAMESPACE, '/uffici/(?P<id>\d+)', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_ufficio' ),
			'permission_callback' => '__return_true',
			'args'                => array( 'id' => array( 'validate_callback' => 'is_numeric' ) ),
		) );

		// ----------------------------------------------------------------
		// Luoghi
		// ----------------------------------------------------------------
		register_rest_route( self::NAMESPACE, '/luoghi', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_luoghi' ),
			'permission_callback' => '__return_true',
			'args'                => $this->pagination_args(),
		) );

		// ----------------------------------------------------------------
		// Documenti
		// ----------------------------------------------------------------
		register_rest_route( self::NAMESPACE, '/documenti', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_documenti' ),
			'permission_callback' => '__return_true',
			'args'                => $this->pagination_args(),
		) );

		register_rest_route( self::NAMESPACE, '/documenti/(?P<id>\d+)', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_documento' ),
			'permission_callback' => '__return_true',
			'args'                => array( 'id' => array( 'validate_callback' => 'is_numeric' ) ),
		) );

		// ----------------------------------------------------------------
		// Luoghi (detail)
		// ----------------------------------------------------------------
		register_rest_route( self::NAMESPACE, '/luoghi/(?P<id>\d+)', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_luogo' ),
			'permission_callback' => '__return_true',
			'args'                => array( 'id' => array( 'validate_callback' => 'is_numeric' ) ),
		) );

		// ----------------------------------------------------------------
		// Raccolta rifiuti
		// ----------------------------------------------------------------
		register_rest_route( self::NAMESPACE, '/rifiuti', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_waste_schedule' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'zona' => array( 'sanitize_callback' => 'sanitize_key' ),
				'from' => array( 'sanitize_callback' => 'sanitize_text_field' ),
				'to'   => array( 'sanitize_callback' => 'sanitize_text_field' ),
			),
		) );

		// ----------------------------------------------------------------
		// Zone rifiuti
		// ----------------------------------------------------------------
		register_rest_route( self::NAMESPACE, '/rifiuti/zone', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_waste_zones' ),
			'permission_callback' => '__return_true',
		) );

		// ----------------------------------------------------------------
		// Sondaggi
		// ----------------------------------------------------------------
		register_rest_route( self::NAMESPACE, '/sondaggi', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_surveys' ),
			'permission_callback' => '__return_true',
			'args'                => $this->pagination_args(),
		) );

		register_rest_route( self::NAMESPACE, '/sondaggi/(?P<id>\d+)', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_survey' ),
			'permission_callback' => '__return_true',
			'args'                => array( 'id' => array( 'validate_callback' => 'is_numeric' ) ),
		) );

		register_rest_route( self::NAMESPACE, '/sondaggi/(?P<id>\d+)/risposte', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'submit_survey_response' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'id'      => array( 'validate_callback' => 'is_numeric' ),
				'answers' => array( 'required' => true ),
			),
		) );

		// ----------------------------------------------------------------
		// Segnalazioni (device tokens & citizen reports)
		// ----------------------------------------------------------------
		register_rest_route( self::NAMESPACE, '/segnalazioni', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_segnalazioni' ),
				'permission_callback' => '__return_true',
				'args'                => array_merge( $this->pagination_args(), array(
					'device_id' => array( 'sanitize_callback' => 'sanitize_text_field' ),
				) ),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'submit_segnalazione' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'title'       => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
					'description' => array( 'required' => true, 'sanitize_callback' => 'sanitize_textarea_field' ),
					'category'    => array( 'sanitize_callback' => 'sanitize_text_field' ),
				),
			),
		) );

		register_rest_route( self::NAMESPACE, '/segnalazioni/(?P<code>[A-Z0-9\-]+)/stato', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_segnalazione_status' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( self::NAMESPACE, '/segnalazioni/(?P<code>[A-Z0-9\-]+)/allegati', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'upload_segnalazione_allegato' ),
			'permission_callback' => '__return_true',
		) );

		// ----------------------------------------------------------------
		// Device token registration
		// ----------------------------------------------------------------
		register_rest_route( self::NAMESPACE, '/dispositivi/registra', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'register_device_token' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'token'     => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
				'platform'  => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
				'device_id' => array( 'sanitize_callback' => 'sanitize_text_field' ),
			),
		) );

		// ----------------------------------------------------------------
		// Notifiche (storico push ricevuti dal dispositivo)
		// ----------------------------------------------------------------
		register_rest_route( self::NAMESPACE, '/notifiche', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_notifiche' ),
			'permission_callback' => '__return_true',
			'args'                => $this->pagination_args(),
		) );

		// ----------------------------------------------------------------
		// Deregistrazione device
		// ----------------------------------------------------------------
		register_rest_route( self::NAMESPACE, '/dispositivi/(?P<token>[A-Za-z0-9_\-:]+)', array(
			'methods'             => WP_REST_Server::DELETABLE,
			'callback'            => array( $this, 'deregister_device_token' ),
			'permission_callback' => '__return_true',
		) );

		// ----------------------------------------------------------------
		// App configuration
		// ----------------------------------------------------------------
		register_rest_route( self::NAMESPACE, '/config', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_app_config' ),
			'permission_callback' => '__return_true',
		) );

		// ----------------------------------------------------------------
		// Schema endpoint
		// ----------------------------------------------------------------
		register_rest_route( self::NAMESPACE, '/schema', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_schema' ),
			'permission_callback' => '__return_true',
		) );
	}

	// -----------------------------------------------------------------------
	// Permission callbacks
	// -----------------------------------------------------------------------

	/**
	 * Check that the incoming request carries a valid JWT token.
	 *
	 * @param WP_REST_Request $request Incoming REST request.
	 * @return bool|WP_Error
	 */
	public function check_auth( WP_REST_Request $request ): bool|WP_Error {
		$auth_header = $request->get_header( 'Authorization' );

		if ( empty( $auth_header ) || 0 !== strpos( $auth_header, 'Bearer ' ) ) {
			return new WP_Error( 'unauthorized', __( 'Token di autenticazione mancante.', 'comune-app-manager' ), array( 'status' => 401 ) );
		}

		$token = substr( $auth_header, 7 );
		$valid = $this->validate_jwt( $token );

		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		return true;
	}

	// -----------------------------------------------------------------------
	// Endpoint callbacks
	// -----------------------------------------------------------------------

	/**
	 * POST /auth/login
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function login( WP_REST_Request $request ): WP_REST_Response {
		$ip = Comune_App_Manager_Utils::get_client_ip();

		if ( ! Comune_App_Manager_Utils::check_rate_limit( 'cam_login_' . $ip, 5, 300 ) ) {
			return Comune_App_Manager_Utils::format_response(
				false, null, '',
				'rate_limited',
				__( 'Troppi tentativi di accesso. Riprova tra qualche minuto.', 'comune-app-manager' )
			);
		}

		$username = $request->get_param( 'username' );
		$password = $request->get_param( 'password' );

		$user = wp_authenticate( $username, $password );

		if ( is_wp_error( $user ) ) {
			Comune_App_Manager_Logger::log_rest_error( '/auth/login', 'Authentication failed for user: ' . $username );
			return Comune_App_Manager_Utils::format_response(
				false, null, '',
				'unauthorized',
				__( 'Credenziali non valide.', 'comune-app-manager' )
			);
		}

		$token = $this->generate_jwt( $user );

		return Comune_App_Manager_Utils::format_response(
			true,
			array(
				'token'      => $token,
				'user_id'    => $user->ID,
				'user_email' => $user->user_email,
				'user_name'  => $user->display_name,
			),
			__( 'Accesso effettuato.', 'comune-app-manager' )
		);
	}

	/**
	 * POST /auth/refresh
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function refresh_token( WP_REST_Request $request ): WP_REST_Response {
		$auth_header = $request->get_header( 'Authorization' );
		$token_str   = substr( $auth_header, 7 );
		$parts       = explode( '.', $token_str );
		$payload     = json_decode( $this->base64url_decode( $parts[1] ?? '' ), true );

		$user_id = absint( $payload['sub'] ?? 0 );
		$user    = $user_id ? get_userdata( $user_id ) : false;

		if ( ! $user || ! $user->exists() || ! $user->has_cap( 'read' ) ) {
			return Comune_App_Manager_Utils::format_response(
				false, null, '',
				'unauthorized',
				__( 'Utente non valido o disabilitato.', 'comune-app-manager' )
			);
		}

		return Comune_App_Manager_Utils::format_response(
			true,
			array( 'token' => $this->generate_jwt( $user ) ),
			__( 'Token rinnovato.', 'comune-app-manager' )
		);
	}

	/**
	 * GET /avvisi
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function get_avvisi( WP_REST_Request $request ): WP_REST_Response {
		if ( ! $this->apply_rate_limit( $request ) ) {
			return $this->rate_limit_response();
		}

		$meta_query = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array( 'key' => '_cam_app_visible', 'value' => '1', 'compare' => '=' ),
		);

		$priorita = sanitize_key( $request->get_param( 'priorita' ) ?: '' );
		if ( $priorita ) {
			$meta_query[] = array( 'key' => '_cam_priorita', 'value' => $priorita, 'compare' => '=' );
		}

		$args = array(
			'post_type'      => 'comune_avviso',
			'post_status'    => 'publish',
			'posts_per_page' => min( absint( $request->get_param( 'per_page' ) ?: 20 ), 100 ),
			'paged'          => absint( $request->get_param( 'page' ) ?: 1 ),
			'meta_query'     => $meta_query,
		);

		if ( $request->get_param( 'search' ) ) {
			$args['s'] = sanitize_text_field( $request->get_param( 'search' ) );
		}

		$query = new WP_Query( $args );
		$items = array_map( array( $this, 'format_avviso' ), $query->posts );

		return Comune_App_Manager_Utils::format_response(
			true,
			array(
				'items'       => $items,
				'total'       => (int) $query->found_posts,
				'total_pages' => (int) $query->max_num_pages,
			)
		);
	}

	/**
	 * GET /avvisi/{id}
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function get_avviso( WP_REST_Request $request ): WP_REST_Response {
		$post = get_post( absint( $request->get_param( 'id' ) ) );

		if ( ! $post || 'comune_avviso' !== $post->post_type || 'publish' !== $post->post_status ) {
			return Comune_App_Manager_Utils::format_response( false, null, '', 'not_found', __( 'Avviso non trovato.', 'comune-app-manager' ) );
		}

		return Comune_App_Manager_Utils::format_response( true, $this->format_avviso( $post ) );
	}

	/**
	 * GET /eventi
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function get_eventi( WP_REST_Request $request ): WP_REST_Response {
		if ( ! $this->apply_rate_limit( $request ) ) {
			return $this->rate_limit_response();
		}

		$meta_query = array(
			array( 'key' => '_cam_app_visible', 'value' => '1', 'compare' => '=' ),
		);

		if ( $request->get_param( 'from' ) ) {
			$meta_query[] = array(
				'key'     => '_cam_event_start_date',
				'value'   => sanitize_text_field( $request->get_param( 'from' ) ),
				'compare' => '>=',
				'type'    => 'DATE',
			);
		}

		if ( $request->get_param( 'to' ) ) {
			$meta_query[] = array(
				'key'     => '_cam_event_start_date',
				'value'   => sanitize_text_field( $request->get_param( 'to' ) ),
				'compare' => '<=',
				'type'    => 'DATE',
			);
		}

		$args = array(
			'post_type'      => 'comune_evento',
			'post_status'    => 'publish',
			'posts_per_page' => min( absint( $request->get_param( 'per_page' ) ?: 20 ), 100 ),
			'paged'          => absint( $request->get_param( 'page' ) ?: 1 ),
			'meta_query'     => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'orderby'        => 'meta_value',
			'meta_key'       => '_cam_event_start_date', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'order'          => 'ASC',
		);

		$query = new WP_Query( $args );
		$items = array_map( array( $this, 'format_evento' ), $query->posts );

		return Comune_App_Manager_Utils::format_response(
			true,
			array(
				'items'       => $items,
				'total'       => (int) $query->found_posts,
				'total_pages' => (int) $query->max_num_pages,
			)
		);
	}

	/**
	 * GET /eventi/{id}
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function get_evento( WP_REST_Request $request ): WP_REST_Response {
		$post = get_post( absint( $request->get_param( 'id' ) ) );

		if ( ! $post || 'comune_evento' !== $post->post_type || 'publish' !== $post->post_status ) {
			return Comune_App_Manager_Utils::format_response( false, null, '', 'not_found', __( 'Evento non trovato.', 'comune-app-manager' ) );
		}

		return Comune_App_Manager_Utils::format_response( true, $this->format_evento( $post ) );
	}

	/**
	 * GET /uffici
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function get_uffici( WP_REST_Request $request ): WP_REST_Response {
		if ( ! $this->apply_rate_limit( $request ) ) {
			return $this->rate_limit_response();
		}

		$args = array(
			'post_type'      => 'comune_ufficio',
			'post_status'    => 'publish',
			'posts_per_page' => min( absint( $request->get_param( 'per_page' ) ?: 50 ), 200 ),
			'paged'          => absint( $request->get_param( 'page' ) ?: 1 ),
		);

		$query = new WP_Query( $args );
		$items = array_map( array( $this, 'format_ufficio' ), $query->posts );

		return Comune_App_Manager_Utils::format_response( true, array( 'items' => $items, 'total' => (int) $query->found_posts ) );
	}

	/**
	 * GET /uffici/{id}
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function get_ufficio( WP_REST_Request $request ): WP_REST_Response {
		$post = get_post( absint( $request->get_param( 'id' ) ) );
		if ( ! $post || 'comune_ufficio' !== $post->post_type || 'publish' !== $post->post_status ) {
			return Comune_App_Manager_Utils::format_response( false, null, '', 'not_found', __( 'Ufficio non trovato.', 'comune-app-manager' ) );
		}
		return Comune_App_Manager_Utils::format_response( true, $this->format_ufficio( $post ) );
	}

	/**
	 * GET /luoghi
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function get_luoghi( WP_REST_Request $request ): WP_REST_Response {
		if ( ! $this->apply_rate_limit( $request ) ) {
			return $this->rate_limit_response();
		}

		$args = array(
			'post_type'      => 'comune_luogo',
			'post_status'    => 'publish',
			'posts_per_page' => min( absint( $request->get_param( 'per_page' ) ?: 50 ), 200 ),
			'paged'          => absint( $request->get_param( 'page' ) ?: 1 ),
		);

		$query = new WP_Query( $args );
		$items = array_map( array( $this, 'format_luogo' ), $query->posts );

		return Comune_App_Manager_Utils::format_response( true, array( 'items' => $items, 'total' => (int) $query->found_posts ) );
	}

	/**
	 * GET /documenti
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function get_documenti( WP_REST_Request $request ): WP_REST_Response {
		if ( ! $this->apply_rate_limit( $request ) ) {
			return $this->rate_limit_response();
		}

		$args = array(
			'post_type'      => 'comune_documento',
			'post_status'    => 'publish',
			'posts_per_page' => min( absint( $request->get_param( 'per_page' ) ?: 20 ), 100 ),
			'paged'          => absint( $request->get_param( 'page' ) ?: 1 ),
		);

		$query = new WP_Query( $args );
		$items = array_map( array( $this, 'format_documento' ), $query->posts );

		return Comune_App_Manager_Utils::format_response( true, array( 'items' => $items, 'total' => (int) $query->found_posts ) );
	}

	/**
	 * GET /documenti/{id}
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function get_documento( WP_REST_Request $request ): WP_REST_Response {
		$post = get_post( absint( $request->get_param( 'id' ) ) );
		if ( ! $post || 'comune_documento' !== $post->post_type || 'publish' !== $post->post_status ) {
			return Comune_App_Manager_Utils::format_response( false, null, '', 'not_found', __( 'Documento non trovato.', 'comune-app-manager' ) );
		}
		return Comune_App_Manager_Utils::format_response( true, $this->format_documento( $post ) );
	}

	/**
	 * GET /rifiuti
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function get_waste_schedule( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		if ( ! $this->apply_rate_limit( $request ) ) {
			return $this->rate_limit_response();
		}

		$zona = sanitize_key( $request->get_param( 'zona' ) ?: '' );
		$from = sanitize_text_field( $request->get_param( 'from' ) ?: gmdate( 'Y-m-d' ) );
		$to   = sanitize_text_field( $request->get_param( 'to' ) ?: gmdate( 'Y-m-d', strtotime( '+30 days' ) ) );

		$table = $wpdb->prefix . 'cam_waste_schedule';
		$cat_table = $wpdb->prefix . 'cam_waste_categories';

		if ( $zona ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT s.*, c.name AS category_name, c.color, c.icon
					 FROM `{$table}` s
					 LEFT JOIN `{$cat_table}` c ON c.id = s.category_id
					 WHERE s.zone_slug = %s
					   AND s.collection_date BETWEEN %s AND %s
					 ORDER BY s.collection_date ASC",
					$zona, $from, $to
				),
				ARRAY_A
			);
		} else {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT s.*, c.name AS category_name, c.color, c.icon
					 FROM `{$table}` s
					 LEFT JOIN `{$cat_table}` c ON c.id = s.category_id
					 WHERE s.collection_date BETWEEN %s AND %s
					 ORDER BY s.collection_date ASC, s.zone_slug ASC",
					$from, $to
				),
				ARRAY_A
			);
		}

		return Comune_App_Manager_Utils::format_response( true, array( 'schedule' => $results ?: array() ) );
	}

	/**
	 * GET /sondaggi
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function get_surveys( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		if ( ! $this->apply_rate_limit( $request ) ) {
			return $this->rate_limit_response();
		}

		$args = array(
			'post_type'      => 'cam_sondaggio',
			'post_status'    => 'publish',
			'posts_per_page' => min( absint( $request->get_param( 'per_page' ) ?: 20 ), 50 ),
			'paged'          => absint( $request->get_param( 'page' ) ?: 1 ),
		);

		$query = new WP_Query( $args );

		$items = array_map( static function ( WP_Post $post ) use ( $wpdb ): array {
			$expires_at   = get_post_meta( $post->ID, '_cam_survey_expires', true );
			$aperto       = empty( $expires_at ) || strtotime( $expires_at ) > time();
			$partecipanti = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}cam_survey_responses WHERE survey_id = %d AND is_complete = 1",
					$post->ID
				)
			);
			return array(
				'id'           => $post->ID,
				'title'        => $post->post_title,
				'description'  => wp_strip_all_tags( $post->post_content ),
				'expires_at'   => $expires_at,
				'aperto'       => $aperto,
				'partecipanti' => $partecipanti,
			);
		}, $query->posts );

		return Comune_App_Manager_Utils::format_response( true, array( 'items' => $items, 'total' => (int) $query->found_posts ) );
	}

	/**
	 * POST /sondaggi/{id}/risposte
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function submit_survey_response( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$survey_id = absint( $request->get_param( 'id' ) );
		$post      = get_post( $survey_id );

		if ( ! $post || 'cam_sondaggio' !== $post->post_type || 'publish' !== $post->post_status ) {
			return Comune_App_Manager_Utils::format_response( false, null, '', 'not_found', __( 'Sondaggio non trovato.', 'comune-app-manager' ) );
		}

		// Reject submissions to closed surveys.
		$expires_at = get_post_meta( $post->ID, '_cam_survey_expires', true );
		if ( ! empty( $expires_at ) && strtotime( $expires_at ) <= time() ) {
			return Comune_App_Manager_Utils::format_response( false, null, '', 'forbidden', __( 'Il sondaggio è chiuso e non accetta più risposte.', 'comune-app-manager' ) );
		}

		if ( ! $this->apply_rate_limit( $request, 5 ) ) {
			return $this->rate_limit_response();
		}

		$ip = Comune_App_Manager_Utils::get_client_ip();

		$response_id = $wpdb->insert(
			$wpdb->prefix . 'cam_survey_responses',
			array(
				'survey_id'    => $survey_id,
				'ip_address'   => $ip,
				'is_complete'  => 0,
				'started_at'   => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%d', '%s' )
		);

		if ( ! $response_id ) {
			return Comune_App_Manager_Utils::format_response( false, null, '', 'server_error', __( 'Impossibile salvare la risposta.', 'comune-app-manager' ) );
		}

		$resp_id = $wpdb->insert_id;
		$answers = (array) $request->get_param( 'answers' );

		foreach ( $answers as $question_id => $answer_value ) {
			$wpdb->insert(
				$wpdb->prefix . 'cam_survey_answers',
				array(
					'response_id'  => $resp_id,
					'survey_id'    => $survey_id,
					'question_id'  => sanitize_key( (string) $question_id ),
					'answer_value' => wp_json_encode( $answer_value ),
				),
				array( '%d', '%d', '%s', '%s' )
			);
		}

		$wpdb->update(
			$wpdb->prefix . 'cam_survey_responses',
			array( 'is_complete' => 1, 'completed_at' => current_time( 'mysql' ) ),
			array( 'id' => $resp_id ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		return Comune_App_Manager_Utils::format_response( true, array( 'response_id' => $resp_id ), __( 'Grazie per aver partecipato!', 'comune-app-manager' ) );
	}

	/**
	 * GET /segnalazioni?device_id={uuid}
	 *
	 * Returns the citizen reports submitted by a given device.
	 * Requires device_id; without it returns an empty list (privacy guard).
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function get_segnalazioni( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		if ( ! $this->apply_rate_limit( $request ) ) {
			return $this->rate_limit_response();
		}

		$device_id = sanitize_text_field( $request->get_param( 'device_id' ) ?: '' );
		if ( empty( $device_id ) ) {
			return Comune_App_Manager_Utils::format_response( true, array( 'items' => array(), 'total' => 0 ) );
		}

		$per_page = min( absint( $request->get_param( 'per_page' ) ?: 20 ), 50 );
		$page     = absint( $request->get_param( 'page' ) ?: 1 );
		$offset   = ( $page - 1 ) * $per_page;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, public_code, category, title, description, address,
				        lat, lng, status, priority, created_at, updated_at, resolved_at
				 FROM {$wpdb->prefix}cam_segnalazioni
				 WHERE device_id = %s
				 ORDER BY created_at DESC
				 LIMIT %d OFFSET %d",
				$device_id,
				$per_page,
				$offset
			),
			ARRAY_A
		);

		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}cam_segnalazioni WHERE device_id = %s",
				$device_id
			)
		);

		// Attach photo URLs for each report.
		$table_allegati = $wpdb->prefix . 'cam_segnalazione_allegati';
		foreach ( $rows as &$row ) {
			$row['foto_urls'] = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT file_url FROM {$table_allegati} WHERE segnalazione_id = %d ORDER BY id ASC",
					$row['id']
				)
			);
			unset( $row['id'] ); // expose only public_code, not internal ID
		}
		unset( $row );

		return Comune_App_Manager_Utils::format_response( true, array(
			'items'       => $rows ?: array(),
			'total'       => $total,
			'total_pages' => $per_page > 0 ? (int) ceil( $total / $per_page ) : 1,
		) );
	}

	/**
	 * POST /segnalazioni
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function submit_segnalazione( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		if ( ! $this->apply_rate_limit( $request, 3 ) ) {
			return $this->rate_limit_response();
		}

		$title       = sanitize_text_field( $request->get_param( 'title' ) );
		$description = sanitize_textarea_field( $request->get_param( 'description' ) );
		$category    = sanitize_text_field( $request->get_param( 'category' ) ?: 'generale' );
		$address     = sanitize_text_field( $request->get_param( 'address' ) ?: '' );
		$lat         = $request->get_param( 'lat' ) ? (float) $request->get_param( 'lat' ) : null;
		$lng         = $request->get_param( 'lng' ) ? (float) $request->get_param( 'lng' ) : null;
		$name        = sanitize_text_field( $request->get_param( 'name' ) ?: '' );
		$email       = sanitize_email( $request->get_param( 'email' ) ?: '' );
		$device_id   = sanitize_text_field( $request->get_param( 'device_id' ) ?: '' );

		$result = $wpdb->insert(
			$wpdb->prefix . 'cam_segnalazioni',
			array(
				'public_code'     => 'TEMP',
				'device_id'       => $device_id,
				'category'        => $category,
				'title'           => $title,
				'description'     => $description,
				'address'         => $address,
				'lat'             => $lat,
				'lng'             => $lng,
				'submitter_name'  => $name,
				'submitter_email' => $email,
				'status'          => 'pending',
				'priority'        => 'medium',
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s' )
		);

		if ( ! $result ) {
			return Comune_App_Manager_Utils::format_response( false, null, '', 'server_error', __( 'Impossibile registrare la segnalazione.', 'comune-app-manager' ) );
		}

		$new_id      = $wpdb->insert_id;
		$public_code = Comune_App_Manager_Utils::generate_public_code( $new_id );

		$wpdb->update(
			$wpdb->prefix . 'cam_segnalazioni',
			array( 'public_code' => $public_code ),
			array( 'id' => $new_id ),
			array( '%s' ),
			array( '%d' )
		);

		return Comune_App_Manager_Utils::format_response(
			true,
			array( 'public_code' => $public_code ),
			__( 'Segnalazione inviata con successo.', 'comune-app-manager' )
		);
	}

	/**
	 * GET /segnalazioni/{code}/stato
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function get_segnalazione_status( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$code = sanitize_text_field( $request->get_param( 'code' ) );
		$row  = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT public_code, status, created_at, resolved_at FROM {$wpdb->prefix}cam_segnalazioni WHERE public_code = %s",
				$code
			),
			ARRAY_A
		);

		if ( ! $row ) {
			return Comune_App_Manager_Utils::format_response( false, null, '', 'not_found', __( 'Segnalazione non trovata.', 'comune-app-manager' ) );
		}

		return Comune_App_Manager_Utils::format_response( true, $row );
	}

	/**
	 * GET /luoghi/{id}
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function get_luogo( WP_REST_Request $request ): WP_REST_Response {
		$post = get_post( absint( $request->get_param( 'id' ) ) );
		if ( ! $post || 'comune_luogo' !== $post->post_type || 'publish' !== $post->post_status ) {
			return Comune_App_Manager_Utils::format_response( false, null, '', 'not_found', __( 'Luogo non trovato.', 'comune-app-manager' ) );
		}
		return Comune_App_Manager_Utils::format_response( true, $this->format_luogo( $post ) );
	}

	/**
	 * GET /rifiuti/zone
	 *
	 * Returns the list of distinct waste zones with their categories.
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function get_waste_zones( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		if ( ! $this->apply_rate_limit( $request ) ) {
			return $this->rate_limit_response();
		}

		// Named zones from cam_waste_zones; fall back to distinct slugs from schedule.
		$zones = $wpdb->get_results(
			"SELECT z.slug, z.name, z.description
			 FROM {$wpdb->prefix}cam_waste_zones z
			 WHERE z.is_active = 1
			 ORDER BY z.sort_order ASC, z.name ASC",
			ARRAY_A
		);

		if ( empty( $zones ) ) {
			$raw = $wpdb->get_results(
				"SELECT DISTINCT zone_slug FROM {$wpdb->prefix}cam_waste_schedule ORDER BY zone_slug ASC",
				ARRAY_A
			);
			$zones = array_map( static function ( array $row ): array {
				$slug = $row['zone_slug'];
				return array(
					'slug'        => $slug,
					'name'        => ucwords( str_replace( array( '-', '_' ), ' ', $slug ) ),
					'description' => null,
				);
			}, $raw );
		}

		$categories = $wpdb->get_results(
			"SELECT id, name, slug, color, icon FROM {$wpdb->prefix}cam_waste_categories WHERE is_active = 1 ORDER BY sort_order ASC",
			ARRAY_A
		);

		return Comune_App_Manager_Utils::format_response( true, array(
			'zones'      => $zones ?: array(),
			'categories' => $categories ?: array(),
		) );
	}

	/**
	 * GET /sondaggi/{id}
	 *
	 * Returns a single survey with questions and participant count.
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function get_survey( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$post = get_post( absint( $request->get_param( 'id' ) ) );
		if ( ! $post || 'cam_sondaggio' !== $post->post_type || 'publish' !== $post->post_status ) {
			return Comune_App_Manager_Utils::format_response( false, null, '', 'not_found', __( 'Sondaggio non trovato.', 'comune-app-manager' ) );
		}

		$expires_at    = get_post_meta( $post->ID, '_cam_survey_expires', true );
		$aperto        = empty( $expires_at ) || strtotime( $expires_at ) > time();
		$partecipanti  = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}cam_survey_responses WHERE survey_id = %d AND is_complete = 1",
				$post->ID
			)
		);

		return Comune_App_Manager_Utils::format_response( true, array(
			'id'           => $post->ID,
			'title'        => $post->post_title,
			'description'  => wp_strip_all_tags( $post->post_content ),
			'questions'    => json_decode( get_post_meta( $post->ID, '_cam_survey_questions', true ) ?: '[]', true ),
			'expires_at'   => $expires_at,
			'aperto'       => $aperto,
			'partecipanti' => $partecipanti,
		) );
	}

	/**
	 * POST /segnalazioni/{code}/allegati
	 *
	 * Handles multipart file upload and attaches to the segnalazione.
	 * Max 3 files, max 5 MB each, only images.
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function upload_segnalazione_allegato( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		if ( ! $this->apply_rate_limit( $request, 10 ) ) {
			return $this->rate_limit_response();
		}

		$code = sanitize_text_field( $request->get_param( 'code' ) );
		$row  = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}cam_segnalazioni WHERE public_code = %s",
				$code
			)
		);

		if ( ! $row ) {
			return Comune_App_Manager_Utils::format_response( false, null, '', 'not_found', __( 'Segnalazione non trovata.', 'comune-app-manager' ) );
		}

		$files = $request->get_file_params();
		if ( empty( $files['file'] ) ) {
			return Comune_App_Manager_Utils::format_response( false, null, '', 'missing_file', __( 'Nessun file allegato.', 'comune-app-manager' ) );
		}

		// Check existing attachment count (max 3).
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}cam_segnalazione_allegati WHERE segnalazione_id = %d",
				$row->id
			)
		);
		if ( $count >= 3 ) {
			return Comune_App_Manager_Utils::format_response( false, null, '', 'limit_reached', __( 'Massimo 3 allegati per segnalazione.', 'comune-app-manager' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$file = $files['file'];

		// Validate MIME (images only).
		$allowed_mimes = array( 'image/jpeg', 'image/png', 'image/webp', 'image/gif' );
		$finfo         = new finfo( FILEINFO_MIME_TYPE );
		$mime          = $finfo->file( $file['tmp_name'] );
		if ( ! in_array( $mime, $allowed_mimes, true ) ) {
			return Comune_App_Manager_Utils::format_response( false, null, '', 'invalid_mime', __( 'Solo immagini JPEG, PNG, WebP o GIF.', 'comune-app-manager' ) );
		}

		// Max 5 MB.
		if ( $file['size'] > 5 * 1024 * 1024 ) {
			return Comune_App_Manager_Utils::format_response( false, null, '', 'file_too_large', __( 'Dimensione massima 5 MB.', 'comune-app-manager' ) );
		}

		// Redirect uploads to a dedicated subdirectory so they are not mixed
		// with general media and can be restricted by server rules if needed.
		$upload_dir_filter = static function ( array $dirs ): array {
			$dirs['subdir'] = '/cam-segnalazioni';
			$dirs['path']   = $dirs['basedir'] . $dirs['subdir'];
			$dirs['url']    = $dirs['baseurl'] . $dirs['subdir'];
			return $dirs;
		};
		add_filter( 'upload_dir', $upload_dir_filter );
		$overrides = array( 'test_form' => false );
		$upload    = wp_handle_upload( $file, $overrides );
		remove_filter( 'upload_dir', $upload_dir_filter );

		if ( isset( $upload['error'] ) ) {
			return Comune_App_Manager_Utils::format_response( false, null, '', 'upload_error', $upload['error'] );
		}

		$wpdb->insert(
			$wpdb->prefix . 'cam_segnalazione_allegati',
			array(
				'segnalazione_id' => $row->id,
				'file_url'        => $upload['url'],
				'file_path'       => $upload['file'],
				'mime_type'       => $upload['type'],
			),
			array( '%d', '%s', '%s', '%s' )
		);

		return Comune_App_Manager_Utils::format_response(
			true,
			array( 'url' => $upload['url'] ),
			__( 'Allegato caricato.', 'comune-app-manager' )
		);
	}

	/**
	 * GET /notifiche
	 *
	 * Returns recent push notification log entries (public-safe fields only).
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function get_notifiche( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		if ( ! $this->apply_rate_limit( $request ) ) {
			return $this->rate_limit_response();
		}

		$per_page = min( absint( $request->get_param( 'per_page' ) ?: 20 ), 50 );
		$page     = absint( $request->get_param( 'page' ) ?: 1 );
		$offset   = ( $page - 1 ) * $per_page;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, title, body, target_type, sent_at
				 FROM {$wpdb->prefix}cam_push_logs
				 ORDER BY sent_at DESC
				 LIMIT %d OFFSET %d",
				$per_page,
				$offset
			),
			ARRAY_A
		);

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}cam_push_logs" );

		return Comune_App_Manager_Utils::format_response( true, array(
			'items'       => $rows ?: array(),
			'total'       => $total,
			'total_pages' => $per_page > 0 ? (int) ceil( $total / $per_page ) : 1,
		) );
	}

	/**
	 * DELETE /dispositivi/{token}
	 *
	 * Deactivates a device token (soft-delete via is_active = 0).
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function deregister_device_token( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$token = sanitize_text_field( $request->get_param( 'token' ) );

		$updated = $wpdb->update(
			$wpdb->prefix . 'cam_device_tokens',
			array( 'is_active' => 0, 'updated_at' => current_time( 'mysql' ) ),
			array( 'token' => $token ),
			array( '%d', '%s' ),
			array( '%s' )
		);

		if ( false === $updated ) {
			return Comune_App_Manager_Utils::format_response( false, null, '', 'server_error', __( 'Impossibile aggiornare il token.', 'comune-app-manager' ) );
		}

		return Comune_App_Manager_Utils::format_response( true, null, __( 'Dispositivo rimosso.', 'comune-app-manager' ) );
	}

	/**
	 * POST /dispositivi/registra
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function register_device_token( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		if ( ! $this->apply_rate_limit( $request, 10 ) ) {
			return $this->rate_limit_response();
		}

		$token = sanitize_text_field( $request->get_param( 'token' ) );

		try {
			$platform = Comune_App_Manager_Utils::sanitize_platform( $request->get_param( 'platform' ) );
		} catch ( InvalidArgumentException $e ) {
			return Comune_App_Manager_Utils::format_response( false, null, '', 'invalid_param', $e->getMessage() );
		}

		$device_id  = sanitize_text_field( $request->get_param( 'device_id' ) ?: '' );
		$categories = wp_json_encode( (array) ( $request->get_param( 'categories' ) ?: array() ) );
		$zones      = wp_json_encode( (array) ( $request->get_param( 'zones' ) ?: array() ) );

		// Upsert: update if token already exists, insert if new.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}cam_device_tokens WHERE token = %s",
				$token
			)
		);

		if ( $existing ) {
			$wpdb->update(
				$wpdb->prefix . 'cam_device_tokens',
				array(
					'platform'   => $platform,
					'device_id'  => $device_id ?: null,
					'categories' => $categories,
					'zones'      => $zones,
					'is_active'  => 1,
					'updated_at' => current_time( 'mysql' ),
				),
				array( 'id' => $existing ),
				array( '%s', '%s', '%s', '%s', '%d', '%s' ),
				array( '%d' )
			);
		} else {
			$wpdb->insert(
				$wpdb->prefix . 'cam_device_tokens',
				array(
					'token'      => $token,
					'platform'   => $platform,
					'device_id'  => $device_id ?: null,
					'categories' => $categories,
					'zones'      => $zones,
					'is_active'  => 1,
				),
				array( '%s', '%s', '%s', '%s', '%s', '%d' )
			);
		}

		return Comune_App_Manager_Utils::format_response( true, null, __( 'Dispositivo registrato.', 'comune-app-manager' ) );
	}

	/**
	 * GET /config
	 *
	 * Returns public configuration values for the app.
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function get_app_config( WP_REST_Request $request ): WP_REST_Response {
		$settings = get_option( 'cam_settings', array() );

		$valid_palettes = array( 'blu_civico', 'blu_savoia', 'verde_borgo', 'tricolore' );
		$palette        = $settings['app_palette'] ?? 'blu_civico';
		if ( ! in_array( $palette, $valid_palettes, true ) ) {
			$palette = 'blu_civico';
		}

		// Only expose non-sensitive settings to the app.
		$public = array(
			'comune_name'            => $settings['comune_name'] ?? '',
			'comune_code'            => $settings['comune_code'] ?? '',
			'palette'                => $palette,
			'primary_color'          => $settings['primary_color'] ?? '#0B5FFF',
			'secondary_color'        => $settings['secondary_color'] ?? '#1ED27A',
			'app_active'             => (bool) ( $settings['app_active'] ?? true ),
			'maintenance_mode'       => (bool) ( $settings['maintenance_mode'] ?? false ),
			'maintenance_message'    => $settings['maintenance_message'] ?? '',
			'app_store_url'          => $settings['app_store_url'] ?? '',
			'play_store_url'         => $settings['play_store_url'] ?? '',
			'waste_calendar_enabled' => (bool) ( $settings['waste_calendar_enabled'] ?? true ),
			'surveys_enabled'        => (bool) ( $settings['surveys_enabled'] ?? true ),
			'reports_enabled'        => (bool) ( $settings['reports_enabled'] ?? true ),
			'privacy_policy_url'     => $settings['privacy_policy_url'] ?? '',
			'tos_url'                => $settings['tos_url'] ?? '',
			'support_email'          => $settings['support_email'] ?? '',
			'support_phone'          => $settings['support_phone'] ?? '',
		);

		return Comune_App_Manager_Utils::format_response( true, $public );
	}

	/**
	 * GET /schema – returns a minimal OpenAPI-like schema object.
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function get_schema( WP_REST_Request $request ): WP_REST_Response {
		$base = rest_url( self::NAMESPACE );

		$schema = array(
			'openapi'  => '3.0.0',
			'info'     => array(
				'title'   => 'Comune App Manager API',
				'version' => CAM_VERSION,
			),
			'servers'  => array( array( 'url' => $base ) ),
			'paths'    => array(
				'/config'                           => array( 'get'    => array( 'summary' => 'App configuration' ) ),
				'/avvisi'                           => array( 'get'    => array( 'summary' => 'List avvisi (filter: priorita, search, page, per_page)' ) ),
				'/avvisi/{id}'                      => array( 'get'    => array( 'summary' => 'Get single avviso' ) ),
				'/eventi'                           => array( 'get'    => array( 'summary' => 'List eventi (filter: from, to, search, page, per_page)' ) ),
				'/eventi/{id}'                      => array( 'get'    => array( 'summary' => 'Get single evento' ) ),
				'/uffici'                           => array( 'get'    => array( 'summary' => 'List uffici' ) ),
				'/uffici/{id}'                      => array( 'get'    => array( 'summary' => 'Get single ufficio' ) ),
				'/luoghi'                           => array( 'get'    => array( 'summary' => 'List luoghi' ) ),
				'/luoghi/{id}'                      => array( 'get'    => array( 'summary' => 'Get single luogo' ) ),
				'/documenti'                        => array( 'get'    => array( 'summary' => 'List documenti' ) ),
			'/documenti/{id}'                   => array( 'get'    => array( 'summary' => 'Get single documento' ) ),
				'/rifiuti'                          => array( 'get'    => array( 'summary' => 'Waste collection schedule (filter: zona, from, to)' ) ),
				'/rifiuti/zone'                     => array( 'get'    => array( 'summary' => 'List waste zones and categories' ) ),
				'/sondaggi'                         => array( 'get'    => array( 'summary' => 'List surveys' ) ),
				'/sondaggi/{id}'                    => array( 'get'    => array( 'summary' => 'Get single survey with questions' ) ),
				'/sondaggi/{id}/risposte'           => array( 'post'   => array( 'summary' => 'Submit survey answers' ) ),
				'/segnalazioni'                     => array(
				'get'  => array( 'summary' => 'List reports by device_id (requires ?device_id=)' ),
				'post' => array( 'summary' => 'Submit citizen report' ),
			),
				'/segnalazioni/{code}/stato'        => array( 'get'    => array( 'summary' => 'Check report status' ) ),
				'/segnalazioni/{code}/allegati'     => array( 'post'   => array( 'summary' => 'Upload attachment to report (multipart, max 3 files, 5 MB each)' ) ),
				'/notifiche'                        => array( 'get'    => array( 'summary' => 'Push notification history (page, per_page)' ) ),
				'/dispositivi/registra'             => array( 'post'   => array( 'summary' => 'Register device token' ) ),
				'/dispositivi/{token}'              => array( 'delete' => array( 'summary' => 'Deregister device token' ) ),
				'/auth/login'                       => array( 'post'   => array( 'summary' => 'Authenticate user' ) ),
				'/auth/refresh'                     => array( 'post'   => array( 'summary' => 'Refresh JWT token' ) ),
			),
		);

		return new WP_REST_Response( $schema, 200 );
	}

	/**
	 * Add Cache-Control headers to cacheable GET responses.
	 *
	 * @param WP_HTTP_Response $result  Response object.
	 * @param WP_REST_Server   $server  Server instance.
	 * @param WP_REST_Request  $request Request object.
	 * @return WP_HTTP_Response
	 */
	public function add_cache_headers( WP_HTTP_Response $result, WP_REST_Server $server, WP_REST_Request $request ): WP_HTTP_Response {
		// Security headers on every REST response.
		$result->header( 'X-Content-Type-Options', 'nosniff' );
		$result->header( 'X-Frame-Options', 'DENY' );
		$result->header( 'Referrer-Policy', 'strict-origin-when-cross-origin' );
		$result->header( 'X-Permitted-Cross-Domain-Policies', 'none' );

		if ( 'GET' !== $request->get_method() ) {
			return $result;
		}

		$route = $request->get_route();
		// Strip namespace prefix.
		$path = str_replace( '/' . self::NAMESPACE, '', $route );

		// Exact match first.
		if ( isset( self::CACHE_TTLS[ $path ] ) ) {
			$ttl = self::CACHE_TTLS[ $path ];
			$result->header( 'Cache-Control', "public, max-age={$ttl}, stale-while-revalidate=60" );
			$result->header( 'Vary', 'Accept' );
			return $result;
		}

		// Pattern matches for item routes (short TTL).
		$short_patterns = array( '/avvisi/', '/eventi/', '/uffici/', '/luoghi/', '/sondaggi/' );
		foreach ( $short_patterns as $prefix ) {
			if ( str_starts_with( $path, $prefix ) ) {
				$result->header( 'Cache-Control', 'public, max-age=120, stale-while-revalidate=30' );
				$result->header( 'Vary', 'Accept' );
				return $result;
			}
		}

		// Everything else: no public cache.
		$result->header( 'Cache-Control', 'no-store, no-cache, must-revalidate' );

		return $result;
	}

	// -----------------------------------------------------------------------
	// Private formatters
	// -----------------------------------------------------------------------

	/** Format a WP_Post as an avviso API object. */
	private function format_avviso( WP_Post $post ): array {
		$priorita = get_post_meta( $post->ID, '_cam_priorita', true ) ?: 'bassa';
		return array(
			'id'           => $post->ID,
			'title'        => $post->post_title,
			'content'      => wp_strip_all_tags( $post->post_content ),
			'content_html' => wp_kses_post( apply_filters( 'the_content', $post->post_content ) ),
			'excerpt'      => $post->post_excerpt,
			'slug'        => $post->post_name,
			'date'        => $post->post_date_gmt,
			'modified'    => $post->post_modified_gmt,
			'thumbnail'   => get_the_post_thumbnail_url( $post->ID, 'large' ) ?: null,
			'priorita'    => $priorita,
			'featured'    => get_post_meta( $post->ID, '_cam_app_featured', true ) === '1',
			'expiry_date' => get_post_meta( $post->ID, '_cam_expiry_date', true ),
			'allegati'    => $this->get_post_attachments( $post->ID ),
			'categories'  => wp_get_post_terms( $post->ID, 'cam_categoria_avviso', array( 'fields' => 'names' ) ),
			'url'         => get_permalink( $post->ID ),
		);
	}

	/** Return attachment URLs linked to a post. */
	private function get_post_attachments( int $post_id ): array {
		$attachments = get_attached_media( '', $post_id );
		return array_values( array_map( static fn( WP_Post $a ) => wp_get_attachment_url( $a->ID ), $attachments ) );
	}

	/** Format a WP_Post as an evento API object. */
	private function format_evento( WP_Post $post ): array {
		return array(
			'id'           => $post->ID,
			'title'        => $post->post_title,
			'content'      => wp_strip_all_tags( $post->post_content ),
			'content_html' => wp_kses_post( apply_filters( 'the_content', $post->post_content ) ),
			'excerpt'      => $post->post_excerpt,
			'slug'        => $post->post_name,
			'date'        => $post->post_date_gmt,
			'thumbnail'   => get_the_post_thumbnail_url( $post->ID, 'large' ) ?: null,
			'featured'    => get_post_meta( $post->ID, '_cam_app_featured', true ) === '1',
			'start_date'  => get_post_meta( $post->ID, '_cam_event_start_date', true ),
			'start_time'  => get_post_meta( $post->ID, '_cam_event_start_time', true ),
			'end_date'    => get_post_meta( $post->ID, '_cam_event_end_date', true ),
			'end_time'    => get_post_meta( $post->ID, '_cam_event_end_time', true ),
			'all_day'     => get_post_meta( $post->ID, '_cam_event_all_day', true ) === '1',
			'location'    => get_post_meta( $post->ID, '_cam_event_location', true ),
			'location_url'=> get_post_meta( $post->ID, '_cam_event_location_url', true ),
			'lat'         => get_post_meta( $post->ID, '_cam_lat', true ),
			'lng'         => get_post_meta( $post->ID, '_cam_lng', true ),
			'organizer'   => get_post_meta( $post->ID, '_cam_event_organizer', true ),
			'categories'  => wp_get_post_terms( $post->ID, 'cam_categoria_evento', array( 'fields' => 'names' ) ),
			'url'         => get_permalink( $post->ID ),
		);
	}

	/** Format a WP_Post as an ufficio API object. */
	private function format_ufficio( WP_Post $post ): array {
		return array(
			'id'           => $post->ID,
			'title'        => $post->post_title,
			'content'      => wp_strip_all_tags( $post->post_content ),
			'slug'         => $post->post_name,
			'thumbnail'    => get_the_post_thumbnail_url( $post->ID, 'medium' ) ?: null,
			'featured'     => get_post_meta( $post->ID, '_cam_app_featured', true ) === '1',
			'phone'        => get_post_meta( $post->ID, '_cam_phone', true ),
			'email'        => get_post_meta( $post->ID, '_cam_email', true ),
			'pec'          => get_post_meta( $post->ID, '_cam_pec', true ),
			'address'      => get_post_meta( $post->ID, '_cam_address', true ),
			'manager'      => get_post_meta( $post->ID, '_cam_manager', true ),
			'office_hours' => get_post_meta( $post->ID, '_cam_office_hours', true ),
			'url'          => get_permalink( $post->ID ),
		);
	}

	/** Format a WP_Post as a luogo API object. */
	private function format_luogo( WP_Post $post ): array {
		return array(
			'id'        => $post->ID,
			'title'     => $post->post_title,
			'content'   => wp_strip_all_tags( $post->post_content ),
			'slug'      => $post->post_name,
			'thumbnail' => get_the_post_thumbnail_url( $post->ID, 'large' ) ?: null,
			'featured'  => get_post_meta( $post->ID, '_cam_app_featured', true ) === '1',
			'lat'       => get_post_meta( $post->ID, '_cam_lat', true ),
			'lng'       => get_post_meta( $post->ID, '_cam_lng', true ),
			'address'   => get_post_meta( $post->ID, '_cam_address', true ),
			'phone'     => get_post_meta( $post->ID, '_cam_phone', true ),
			'website'   => get_post_meta( $post->ID, '_cam_website', true ),
			'categories'=> wp_get_post_terms( $post->ID, 'cam_categoria_luogo', array( 'fields' => 'names' ) ),
			'url'       => get_permalink( $post->ID ),
		);
	}

	/** Format a WP_Post as a documento API object. */
	private function format_documento( WP_Post $post ): array {
		return array(
			'id'         => $post->ID,
			'title'      => $post->post_title,
			'excerpt'    => $post->post_excerpt,
			'slug'       => $post->post_name,
			'date'       => $post->post_date_gmt,
			'featured'   => get_post_meta( $post->ID, '_cam_app_featured', true ) === '1',
			'file_url'   => get_post_meta( $post->ID, '_cam_file_url', true ),
			'file_type'  => get_post_meta( $post->ID, '_cam_file_type', true ),
			'file_size'  => (int) get_post_meta( $post->ID, '_cam_file_size', true ),
			'doc_number' => get_post_meta( $post->ID, '_cam_doc_number', true ),
			'doc_date'   => get_post_meta( $post->ID, '_cam_doc_date', true ),
			'categories' => wp_get_post_terms( $post->ID, 'cam_categoria_documento', array( 'fields' => 'names' ) ),
		);
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Apply per-IP rate limiting for a REST request.
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @param int             $max     Max requests per window. Default uses cam_settings.
	 * @return bool True if allowed, false if rate-limited.
	 */
	private function apply_rate_limit( WP_REST_Request $request, int $max = 0 ): bool {
		if ( 0 === $max ) {
			$settings = get_option( 'cam_settings', array() );
			$max      = (int) ( $settings['api_rate_limit'] ?? 60 );
			$window   = (int) ( $settings['api_rate_window'] ?? 3600 );
		} else {
			$window = 3600;
		}

		$ip  = Comune_App_Manager_Utils::get_client_ip();
		$key = 'cam_api_' . $ip . '_' . $request->get_route();

		$allowed = Comune_App_Manager_Utils::check_rate_limit( $key, $max, $window );

		if ( ! $allowed ) {
			// Compute remaining window time for Retry-After header.
			$transient_key = 'cam_rl_' . md5( $key );
			$expires_at    = (int) get_transient( $transient_key . '_exp' );
			$remaining     = $expires_at > time() ? $expires_at - time() : $window;
			$this->rate_limit_retry_after = max( 1, $remaining );
		}

		return $allowed;
	}

	/**
	 * Return a standardised rate-limit exceeded response with Retry-After header.
	 *
	 * @return WP_REST_Response
	 */
	private function rate_limit_response(): WP_REST_Response {
		$response = Comune_App_Manager_Utils::format_response(
			false, null, '',
			'rate_limited',
			__( 'Troppe richieste. Riprova tra qualche minuto.', 'comune-app-manager' )
		);
		$response->header( 'Retry-After', (string) $this->rate_limit_retry_after );
		return $response;
	}

	/**
	 * Return standard pagination argument definitions.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function pagination_args(): array {
		return array(
			'page'     => array( 'sanitize_callback' => 'absint', 'default' => 1 ),
			'per_page' => array( 'sanitize_callback' => 'absint', 'default' => 20 ),
			'search'   => array( 'sanitize_callback' => 'sanitize_text_field' ),
		);
	}

	/**
	 * Generate a simple HMAC-based JWT token for the given user.
	 *
	 * @param WP_User $user WordPress user object.
	 * @return string Dot-separated base64url-encoded JWT.
	 */
	private function generate_jwt( WP_User $user ): string {
		$settings = get_option( 'cam_settings', array() );
		$secret   = $settings['jwt_secret'] ?? wp_salt( 'auth' );
		$expiry   = (int) ( $settings['token_expiry_hours'] ?? 24 );

		$header  = $this->base64url_encode( wp_json_encode( array( 'alg' => 'HS256', 'typ' => 'JWT' ) ) );
		$payload = $this->base64url_encode( wp_json_encode( array(
			'iss' => get_bloginfo( 'url' ),
			'iat' => time(),
			'exp' => time() + ( $expiry * HOUR_IN_SECONDS ),
			'sub' => $user->ID,
			'email' => $user->user_email,
		) ) );

		$signature = $this->base64url_encode( hash_hmac( 'sha256', $header . '.' . $payload, $secret, true ) );

		return $header . '.' . $payload . '.' . $signature;
	}

	/**
	 * Validate a JWT token string.
	 *
	 * @param string $token Raw JWT string.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	private function validate_jwt( string $token ): true|WP_Error {
		$parts = explode( '.', $token );

		if ( 3 !== count( $parts ) ) {
			return new WP_Error( 'invalid_token', __( 'Token non valido.', 'comune-app-manager' ), array( 'status' => 401 ) );
		}

		$settings = get_option( 'cam_settings', array() );
		$secret   = $settings['jwt_secret'] ?? wp_salt( 'auth' );

		$expected_sig = $this->base64url_encode( hash_hmac( 'sha256', $parts[0] . '.' . $parts[1], $secret, true ) );

		if ( ! hash_equals( $expected_sig, $parts[2] ) ) {
			return new WP_Error( 'invalid_token', __( 'Firma del token non valida.', 'comune-app-manager' ), array( 'status' => 401 ) );
		}

		$payload = json_decode( $this->base64url_decode( $parts[1] ), true );

		if ( empty( $payload['exp'] ) || time() > $payload['exp'] ) {
			return new WP_Error( 'token_expired', __( 'Token scaduto. Effettua nuovamente il login.', 'comune-app-manager' ), array( 'status' => 401 ) );
		}

		return true;
	}

	/**
	 * Base64url encode (RFC 4648 §5).
	 *
	 * @param string $data Binary string.
	 * @return string URL-safe base64 string without padding.
	 */
	private function base64url_encode( string $data ): string {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	/**
	 * Base64url decode.
	 *
	 * @param string $data URL-safe base64 string.
	 * @return string Decoded binary string.
	 */
	private function base64url_decode( string $data ): string {
		return base64_decode( strtr( $data, '-_', '+/' ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
	}
}
