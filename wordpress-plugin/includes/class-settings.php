<?php
/**
 * Plugin settings registration and rendering.
 *
 * @package ComuneAppManager
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comune_App_Manager_Settings
 *
 * Registers all settings groups, sections and fields using the WordPress
 * Settings API. Also renders the settings page content (hooked via the
 * admin menu's 'cam_render_settings_page' action).
 */
class Comune_App_Manager_Settings {

	/** Settings option key. */
	const OPTION_KEY = 'cam_settings';

	/** Settings page slug. */
	const PAGE_SLUG = 'cam-settings';

	/**
	 * Register all settings sections and fields.
	 * Hooked to 'admin_init'.
	 */
	public function register_settings(): void {
		register_setting(
			self::PAGE_SLUG,
			self::OPTION_KEY,
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => array(),
			)
		);

		// ----------------------------------------------------------------
		// Section: Generale
		// ----------------------------------------------------------------
		add_settings_section(
			'cam_section_generale',
			__( 'Impostazioni generali', 'comune-app-manager' ),
			null,
			self::PAGE_SLUG
		);

		$this->add_field( 'comune_name',   __( 'Nome del Comune', 'comune-app-manager' ),        'text',     'cam_section_generale' );
		$this->add_field( 'comune_code',   __( 'Codice ISTAT', 'comune-app-manager' ),            'text',     'cam_section_generale' );
		$this->add_field( 'app_palette',   __( 'Tema colori app', 'comune-app-manager' ),         'palette',  'cam_section_generale' );
		$this->add_field( 'primary_color', __( 'Colore primario (override)', 'comune-app-manager' ), 'color', 'cam_section_generale' );
		$this->add_field( 'secondary_color', __( 'Colore secondario (override)', 'comune-app-manager' ), 'color', 'cam_section_generale' );
		$this->add_field( 'app_active',    __( 'App attiva', 'comune-app-manager' ),              'checkbox', 'cam_section_generale' );

		// ----------------------------------------------------------------
		// Section: Manutenzione
		// ----------------------------------------------------------------
		add_settings_section(
			'cam_section_maintenance',
			__( 'Modalità manutenzione', 'comune-app-manager' ),
			null,
			self::PAGE_SLUG
		);

		$this->add_field( 'maintenance_mode',    __( 'Attiva manutenzione', 'comune-app-manager' ),    'checkbox',  'cam_section_maintenance' );
		$this->add_field( 'maintenance_message', __( 'Messaggio di manutenzione', 'comune-app-manager' ), 'textarea', 'cam_section_maintenance' );

		// ----------------------------------------------------------------
		// Section: Store URL
		// ----------------------------------------------------------------
		add_settings_section(
			'cam_section_store',
			__( 'Link store', 'comune-app-manager' ),
			null,
			self::PAGE_SLUG
		);

		$this->add_field( 'app_store_url',  __( 'App Store URL (iOS)', 'comune-app-manager' ),   'url', 'cam_section_store' );
		$this->add_field( 'play_store_url', __( 'Play Store URL (Android)', 'comune-app-manager' ), 'url', 'cam_section_store' );

		// ----------------------------------------------------------------
		// Section: Firebase / Push
		// ----------------------------------------------------------------
		add_settings_section(
			'cam_section_firebase',
			__( 'Notifiche push (Firebase)', 'comune-app-manager' ),
			null,
			self::PAGE_SLUG
		);

		$this->add_field( 'push_notifications_enabled', __( 'Notifiche push attive', 'comune-app-manager' ),   'checkbox',    'cam_section_firebase' );
		$this->add_field( 'firebase_project_id',        __( 'Firebase Project ID', 'comune-app-manager' ),    'text',         'cam_section_firebase' );
		$this->add_field( 'firebase_client_email',      __( 'Service Account Email', 'comune-app-manager' ),  'email',        'cam_section_firebase' );
		$this->add_field( 'firebase_private_key',       __( 'Service Account Private Key (PEM)', 'comune-app-manager' ), 'pem', 'cam_section_firebase' );

		// ----------------------------------------------------------------
		// Section: Moduli
		// ----------------------------------------------------------------
		add_settings_section(
			'cam_section_modules',
			__( 'Moduli attivi', 'comune-app-manager' ),
			null,
			self::PAGE_SLUG
		);

		$this->add_field( 'waste_calendar_enabled', __( 'Calendario raccolta rifiuti', 'comune-app-manager' ), 'checkbox', 'cam_section_modules' );
		$this->add_field( 'surveys_enabled',        __( 'Sondaggi', 'comune-app-manager' ),                   'checkbox', 'cam_section_modules' );
		$this->add_field( 'reports_enabled',        __( 'Segnalazioni cittadini', 'comune-app-manager' ),     'checkbox', 'cam_section_modules' );

		// ----------------------------------------------------------------
		// Section: API
		// ----------------------------------------------------------------
		add_settings_section(
			'cam_section_api',
			__( 'API e sicurezza', 'comune-app-manager' ),
			null,
			self::PAGE_SLUG
		);

		$this->add_field( 'api_rate_limit',    __( 'Limite richieste API (per ora)', 'comune-app-manager' ), 'number', 'cam_section_api' );
		$this->add_field( 'token_expiry_hours', __( 'Scadenza token JWT (ore)', 'comune-app-manager' ),     'number', 'cam_section_api' );

		// ----------------------------------------------------------------
		// Section: Contatti
		// ----------------------------------------------------------------
		add_settings_section(
			'cam_section_contacts',
			__( 'Contatti e link', 'comune-app-manager' ),
			null,
			self::PAGE_SLUG
		);

		$this->add_field( 'support_email',      __( 'Email supporto', 'comune-app-manager' ),        'email', 'cam_section_contacts' );
		$this->add_field( 'support_phone',      __( 'Telefono supporto', 'comune-app-manager' ),     'text',  'cam_section_contacts' );
		$this->add_field( 'reports_email',      __( 'Email notifiche segnalazioni', 'comune-app-manager' ), 'email', 'cam_section_contacts' );
		$this->add_field( 'privacy_policy_url', __( 'URL Privacy Policy', 'comune-app-manager' ),    'url',   'cam_section_contacts' );
		$this->add_field( 'tos_url',            __( 'URL Termini di servizio', 'comune-app-manager' ), 'url',  'cam_section_contacts' );

		// ----------------------------------------------------------------
		// Section: Avanzate
		// ----------------------------------------------------------------
		add_settings_section(
			'cam_section_advanced',
			__( 'Impostazioni avanzate', 'comune-app-manager' ),
			null,
			self::PAGE_SLUG
		);

		$this->add_field( 'delete_on_uninstall', __( 'Elimina tutti i dati alla disinstallazione', 'comune-app-manager' ), 'checkbox', 'cam_section_advanced' );

		// Hook page render callback.
		add_action( 'cam_render_settings_page', array( $this, 'render_page' ) );
	}

	/**
	 * Sanitize the settings array before it is saved.
	 *
	 * @param mixed $input Raw POSTed values.
	 * @return array Sanitized settings array.
	 */
	public function sanitize_settings( mixed $input ): array {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$clean = array();

		// Text fields.
		$text_fields = array( 'comune_name', 'comune_code', 'firebase_project_id', 'support_phone', 'maintenance_message', 'jwt_secret' );
		foreach ( $text_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$clean[ $field ] = sanitize_text_field( $input[ $field ] );
			}
		}

		// Firebase service account email.
		if ( isset( $input['firebase_client_email'] ) ) {
			$clean['firebase_client_email'] = sanitize_email( $input['firebase_client_email'] );
		}

		// Firebase private key: store base64-encoded to avoid serialisation issues.
		// Accept the raw PEM submitted from the textarea.
		$existing = get_option( self::OPTION_KEY, array() );
		if ( ! empty( $input['firebase_private_key'] ) ) {
			$pem = trim( wp_unslash( $input['firebase_private_key'] ) );
			// If it already looks base64-encoded (no BEGIN header), keep as-is; otherwise encode.
			if ( str_contains( $pem, '-----BEGIN' ) ) {
				$clean['firebase_private_key'] = base64_encode( $pem );
			} else {
				$clean['firebase_private_key'] = $pem; // already encoded
			}
		} elseif ( ! empty( $existing['firebase_private_key'] ) ) {
			$clean['firebase_private_key'] = $existing['firebase_private_key']; // preserve existing
		}

		// Email fields.
		foreach ( array( 'support_email', 'reports_email' ) as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$clean[ $field ] = sanitize_email( $input[ $field ] );
			}
		}

		// URL fields.
		foreach ( array( 'app_store_url', 'play_store_url', 'privacy_policy_url', 'tos_url' ) as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$clean[ $field ] = esc_url_raw( $input[ $field ] );
			}
		}

		// Color fields (hex only).
		foreach ( array( 'primary_color', 'secondary_color' ) as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$color = sanitize_hex_color( $input[ $field ] );
				$clean[ $field ] = $color ?: '#1a5276';
			}
		}

		// Palette selection.
		$valid_palettes = array( 'blu_civico', 'blu_savoia', 'verde_borgo', 'tricolore' );
		$palette        = sanitize_key( $input['app_palette'] ?? 'blu_civico' );
		$clean['app_palette'] = in_array( $palette, $valid_palettes, true ) ? $palette : 'blu_civico';

		// Boolean / checkbox fields.
		foreach ( array( 'app_active', 'maintenance_mode', 'push_notifications_enabled', 'waste_calendar_enabled', 'surveys_enabled', 'reports_enabled', 'delete_on_uninstall' ) as $field ) {
			$clean[ $field ] = ! empty( $input[ $field ] );
		}

		// Integer fields.
		foreach ( array( 'api_rate_limit', 'api_rate_window', 'token_expiry_hours' ) as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$clean[ $field ] = absint( $input[ $field ] );
			}
		}

		// Carry over jwt_secret from existing options if not submitted (it's hidden).
		$existing = get_option( self::OPTION_KEY, array() );
		if ( empty( $clean['jwt_secret'] ) && ! empty( $existing['jwt_secret'] ) ) {
			$clean['jwt_secret'] = $existing['jwt_secret'];
		}

		// Mirror delete_on_uninstall to its own option for uninstall.php.
		update_option( 'cam_delete_on_uninstall', $clean['delete_on_uninstall'] ?? false );

		return $clean;
	}

	/**
	 * Render the full settings page.
	 * Triggered by 'cam_render_settings_page' action.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_comune_app' ) ) {
			return;
		}

		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error( self::OPTION_KEY, 'settings_saved', __( 'Impostazioni salvate.', 'comune-app-manager' ), 'updated' );
		}

		settings_errors( self::OPTION_KEY );
		?>
		<form method="post" action="options.php">
			<?php
			settings_fields( self::PAGE_SLUG );
			do_settings_sections( self::PAGE_SLUG );
			submit_button( __( 'Salva impostazioni', 'comune-app-manager' ) );
			?>
		</form>
		<?php
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Register and add a single settings field.
	 *
	 * @param string $key     Option key within cam_settings[].
	 * @param string $label   Human-readable label.
	 * @param string $type    Input type: text|url|email|color|checkbox|number|password|textarea.
	 * @param string $section Settings section ID.
	 */
	private function add_field( string $key, string $label, string $type, string $section ): void {
		add_settings_field(
			'cam_field_' . $key,
			$label,
			array( $this, 'render_field' ),
			self::PAGE_SLUG,
			$section,
			array(
				'key'   => $key,
				'type'  => $type,
				'label' => $label,
			)
		);
	}

	/**
	 * Generic field renderer callback.
	 *
	 * @param array{key:string,type:string,label:string} $args Field arguments.
	 */
	public function render_field( array $args ): void {
		$settings = get_option( self::OPTION_KEY, array() );
		$key      = $args['key'];
		$type     = $args['type'];
		$value    = $settings[ $key ] ?? '';
		$name     = self::OPTION_KEY . '[' . $key . ']';
		$id       = 'cam_field_' . $key;

		switch ( $type ) {
			case 'checkbox':
				printf(
					'<input type="checkbox" id="%s" name="%s" value="1" %s />',
					esc_attr( $id ),
					esc_attr( $name ),
					checked( $value, true, false )
				);
				break;

			case 'textarea':
				printf(
					'<textarea id="%s" name="%s" rows="4" class="large-text">%s</textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_textarea( $value )
				);
				break;

			case 'palette':
				$palettes = array(
					'blu_civico'  => 'Blu Civico (default)',
					'blu_savoia'  => 'Blu Savoia',
					'verde_borgo' => 'Verde Borgo',
					'tricolore'   => 'Tricolore',
				);
				echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
				foreach ( $palettes as $pval => $plabel ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $pval ),
						selected( $value, $pval, false ),
						esc_html( $plabel )
					);
				}
				echo '</select>';
				echo '<p class="description">' . esc_html__( 'Palette di colori usata dall\'app mobile.', 'comune-app-manager' ) . '</p>';
				break;

			case 'color':
				printf(
					'<input type="color" id="%s" name="%s" value="%s" class="cam-color-picker" />',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ?: '#0B5FFF' )
				);
				break;

			case 'pem':
				// Show a textarea; display placeholder if key is already stored.
				$placeholder = ! empty( $value )
					? __( '(chiave già salvata — incolla qui per sostituirla)', 'comune-app-manager' )
					: __( 'Incolla qui la chiave privata PEM del service account (-----BEGIN PRIVATE KEY-----…)', 'comune-app-manager' );
				printf(
					'<textarea id="%s" name="%s" rows="6" class="large-text" placeholder="%s" autocomplete="off" spellcheck="false"></textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $placeholder )
				);
				echo '<p class="description">' . esc_html__( 'Scarica il JSON del service account da Firebase Console → Impostazioni progetto → Account di servizio → Genera nuova chiave privata. Copia il campo "private_key".', 'comune-app-manager' ) . '</p>';
				break;

			case 'password':
				printf(
					'<input type="password" id="%s" name="%s" value="%s" class="regular-text" autocomplete="new-password" />',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value )
				);
				break;

			case 'number':
				printf(
					'<input type="number" id="%s" name="%s" value="%s" class="small-text" min="1" />',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value )
				);
				break;

			case 'url':
				printf(
					'<input type="url" id="%s" name="%s" value="%s" class="large-text" />',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_url( $value )
				);
				break;

			case 'email':
				printf(
					'<input type="email" id="%s" name="%s" value="%s" class="regular-text" />',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value )
				);
				break;

			default: // text.
				printf(
					'<input type="text" id="%s" name="%s" value="%s" class="regular-text" />',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value )
				);
				break;
		}
	}
}
