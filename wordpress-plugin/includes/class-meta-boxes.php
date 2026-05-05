<?php
/**
 * Meta box registrations and save handlers.
 *
 * @package ComuneAppManager
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comune_App_Manager_Meta_Boxes
 *
 * Adds custom meta boxes to every plugin post type and handles saving
 * their values with full nonce verification and sanitisation.
 */
class Comune_App_Manager_Meta_Boxes {

	/**
	 * Register all meta boxes.
	 * Hooked to 'add_meta_boxes'.
	 */
	public function register(): void {
		// Avviso meta.
		add_meta_box(
			'cam_avviso_details',
			__( 'Dettagli avviso', 'comune-app-manager' ),
			array( $this, 'render_avviso_details' ),
			'comune_avviso',
			'normal',
			'high'
		);

		// Evento meta.
		add_meta_box(
			'cam_evento_details',
			__( 'Dettagli evento', 'comune-app-manager' ),
			array( $this, 'render_evento_details' ),
			'comune_evento',
			'normal',
			'high'
		);

		// Ufficio meta.
		add_meta_box(
			'cam_ufficio_details',
			__( 'Dettagli ufficio', 'comune-app-manager' ),
			array( $this, 'render_ufficio_details' ),
			'comune_ufficio',
			'normal',
			'high'
		);

		// Luogo meta.
		add_meta_box(
			'cam_luogo_details',
			__( 'Coordinate e dettagli', 'comune-app-manager' ),
			array( $this, 'render_luogo_details' ),
			'comune_luogo',
			'normal',
			'high'
		);

		// Documento meta.
		add_meta_box(
			'cam_documento_details',
			__( 'Dettagli documento', 'comune-app-manager' ),
			array( $this, 'render_documento_details' ),
			'comune_documento',
			'normal',
			'high'
		);

		// App visibility sidebar meta box (shared by all CPTs).
		foreach ( array( 'comune_avviso', 'comune_evento', 'comune_ufficio', 'comune_luogo', 'comune_documento' ) as $pt ) {
			add_meta_box(
				'cam_app_visibility',
				__( 'Visibilità nell\'app', 'comune-app-manager' ),
				array( $this, 'render_app_visibility' ),
				$pt,
				'side',
				'default'
			);
		}
	}

	// -----------------------------------------------------------------------
	// Render callbacks
	// -----------------------------------------------------------------------

	/**
	 * Render meta box for avviso details.
	 *
	 * @param WP_Post $post Current post object.
	 */
	/** Priority levels available for avvisi. */
	private const PRIORITA_OPTIONS = array(
		'bassa'   => 'Bassa — informazione',
		'media'   => 'Media — importante',
		'alta'    => 'Alta — urgente',
		'urgente' => 'Urgente — massima priorità',
	);

	public function render_avviso_details( WP_Post $post ): void {
		wp_nonce_field( 'cam_avviso_save_' . $post->ID, 'cam_avviso_nonce' );
		$expiry   = get_post_meta( $post->ID, '_cam_expiry_date', true );
		$priorita = get_post_meta( $post->ID, '_cam_priorita', true ) ?: 'bassa';
		?>
		<table class="form-table cam-meta-table">
			<tr>
				<th><label for="cam_priorita"><?php esc_html_e( 'Priorità', 'comune-app-manager' ); ?></label></th>
				<td>
					<select id="cam_priorita" name="cam_priorita">
						<?php foreach ( self::PRIORITA_OPTIONS as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $priorita, $value ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Determina il colore del badge e la posizione nell\'app.', 'comune-app-manager' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="cam_expiry_date"><?php esc_html_e( 'Data scadenza', 'comune-app-manager' ); ?></label></th>
				<td>
					<input type="date" id="cam_expiry_date" name="cam_expiry_date"
					       value="<?php echo esc_attr( $expiry ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Lascia vuoto per nessuna scadenza.', 'comune-app-manager' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render meta box for evento details.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_evento_details( WP_Post $post ): void {
		wp_nonce_field( 'cam_evento_save_' . $post->ID, 'cam_evento_nonce' );
		$start_date  = get_post_meta( $post->ID, '_cam_event_start_date', true );
		$start_time  = get_post_meta( $post->ID, '_cam_event_start_time', true );
		$end_date    = get_post_meta( $post->ID, '_cam_event_end_date', true );
		$end_time    = get_post_meta( $post->ID, '_cam_event_end_time', true );
		$location    = get_post_meta( $post->ID, '_cam_event_location', true );
		$location_url= get_post_meta( $post->ID, '_cam_event_location_url', true );
		$event_lat   = get_post_meta( $post->ID, '_cam_lat', true );
		$event_lng   = get_post_meta( $post->ID, '_cam_lng', true );
		$all_day     = get_post_meta( $post->ID, '_cam_event_all_day', true );
		$organizer   = get_post_meta( $post->ID, '_cam_event_organizer', true );
		?>
		<table class="form-table cam-meta-table">
			<tr>
				<th><label for="cam_event_all_day"><?php esc_html_e( 'Tutto il giorno', 'comune-app-manager' ); ?></label></th>
				<td>
					<input type="checkbox" id="cam_event_all_day" name="cam_event_all_day" value="1"
					       <?php checked( $all_day, '1' ); ?> />
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Inizio', 'comune-app-manager' ); ?></th>
				<td>
					<input type="date" name="cam_event_start_date" value="<?php echo esc_attr( $start_date ); ?>" />
					<input type="time" name="cam_event_start_time" value="<?php echo esc_attr( $start_time ); ?>" />
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Fine', 'comune-app-manager' ); ?></th>
				<td>
					<input type="date" name="cam_event_end_date" value="<?php echo esc_attr( $end_date ); ?>" />
					<input type="time" name="cam_event_end_time" value="<?php echo esc_attr( $end_time ); ?>" />
				</td>
			</tr>
			<tr>
				<th><label for="cam_event_location"><?php esc_html_e( 'Luogo', 'comune-app-manager' ); ?></label></th>
				<td>
					<input type="text" id="cam_event_location" name="cam_event_location"
					       value="<?php echo esc_attr( $location ); ?>" class="large-text" />
				</td>
			</tr>
			<tr>
				<th><label for="cam_event_location_url"><?php esc_html_e( 'URL mappa', 'comune-app-manager' ); ?></label></th>
				<td>
					<input type="url" id="cam_event_location_url" name="cam_event_location_url"
					       value="<?php echo esc_url( $location_url ); ?>" class="large-text" />
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Coordinate GPS', 'comune-app-manager' ); ?></th>
				<td>
					<input type="text" name="cam_lat" placeholder="<?php esc_attr_e( 'Latitudine', 'comune-app-manager' ); ?>"
					       value="<?php echo esc_attr( $event_lat ); ?>" style="width:140px" />
					<input type="text" name="cam_lng" placeholder="<?php esc_attr_e( 'Longitudine', 'comune-app-manager' ); ?>"
					       value="<?php echo esc_attr( $event_lng ); ?>" style="width:140px" />
				</td>
			</tr>
			<tr>
				<th><label for="cam_event_organizer"><?php esc_html_e( 'Organizzatore', 'comune-app-manager' ); ?></label></th>
				<td>
					<input type="text" id="cam_event_organizer" name="cam_event_organizer"
					       value="<?php echo esc_attr( $organizer ); ?>" class="regular-text" />
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render meta box for ufficio details.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_ufficio_details( WP_Post $post ): void {
		wp_nonce_field( 'cam_ufficio_save_' . $post->ID, 'cam_ufficio_nonce' );
		$phone     = get_post_meta( $post->ID, '_cam_phone', true );
		$email     = get_post_meta( $post->ID, '_cam_email', true );
		$address   = get_post_meta( $post->ID, '_cam_address', true );
		$hours     = get_post_meta( $post->ID, '_cam_office_hours', true );
		$manager   = get_post_meta( $post->ID, '_cam_manager', true );
		$pec       = get_post_meta( $post->ID, '_cam_pec', true );
		?>
		<table class="form-table cam-meta-table">
			<tr>
				<th><label for="cam_phone"><?php esc_html_e( 'Telefono', 'comune-app-manager' ); ?></label></th>
				<td><input type="tel" id="cam_phone" name="cam_phone" value="<?php echo esc_attr( $phone ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="cam_email"><?php esc_html_e( 'Email', 'comune-app-manager' ); ?></label></th>
				<td><input type="email" id="cam_email" name="cam_email" value="<?php echo esc_attr( $email ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="cam_pec"><?php esc_html_e( 'PEC', 'comune-app-manager' ); ?></label></th>
				<td><input type="email" id="cam_pec" name="cam_pec" value="<?php echo esc_attr( $pec ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="cam_address"><?php esc_html_e( 'Indirizzo', 'comune-app-manager' ); ?></label></th>
				<td><input type="text" id="cam_address" name="cam_address" value="<?php echo esc_attr( $address ); ?>" class="large-text" /></td>
			</tr>
			<tr>
				<th><label for="cam_manager"><?php esc_html_e( 'Responsabile', 'comune-app-manager' ); ?></label></th>
				<td><input type="text" id="cam_manager" name="cam_manager" value="<?php echo esc_attr( $manager ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="cam_office_hours"><?php esc_html_e( 'Orari di apertura', 'comune-app-manager' ); ?></label></th>
				<td>
					<textarea id="cam_office_hours" name="cam_office_hours" rows="4" class="large-text"><?php echo esc_textarea( $hours ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Es.: Lun-Ven 9:00-12:00 / Mar-Gio 15:00-17:00', 'comune-app-manager' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render meta box for luogo details.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_luogo_details( WP_Post $post ): void {
		wp_nonce_field( 'cam_luogo_save_' . $post->ID, 'cam_luogo_nonce' );
		$lat     = get_post_meta( $post->ID, '_cam_lat', true );
		$lng     = get_post_meta( $post->ID, '_cam_lng', true );
		$address = get_post_meta( $post->ID, '_cam_address', true );
		$phone   = get_post_meta( $post->ID, '_cam_phone', true );
		$website = get_post_meta( $post->ID, '_cam_website', true );
		?>
		<table class="form-table cam-meta-table">
			<tr>
				<th><label for="cam_lat"><?php esc_html_e( 'Latitudine', 'comune-app-manager' ); ?></label></th>
				<td><input type="text" id="cam_lat" name="cam_lat" value="<?php echo esc_attr( $lat ); ?>" class="regular-text" placeholder="45.4642" /></td>
			</tr>
			<tr>
				<th><label for="cam_lng"><?php esc_html_e( 'Longitudine', 'comune-app-manager' ); ?></label></th>
				<td><input type="text" id="cam_lng" name="cam_lng" value="<?php echo esc_attr( $lng ); ?>" class="regular-text" placeholder="9.1900" /></td>
			</tr>
			<tr>
				<th><label for="cam_address_luogo"><?php esc_html_e( 'Indirizzo', 'comune-app-manager' ); ?></label></th>
				<td><input type="text" id="cam_address_luogo" name="cam_address" value="<?php echo esc_attr( $address ); ?>" class="large-text" /></td>
			</tr>
			<tr>
				<th><label for="cam_phone_luogo"><?php esc_html_e( 'Telefono', 'comune-app-manager' ); ?></label></th>
				<td><input type="tel" id="cam_phone_luogo" name="cam_phone" value="<?php echo esc_attr( $phone ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="cam_website"><?php esc_html_e( 'Sito web', 'comune-app-manager' ); ?></label></th>
				<td><input type="url" id="cam_website" name="cam_website" value="<?php echo esc_url( $website ); ?>" class="large-text" /></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render meta box for documento details.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_documento_details( WP_Post $post ): void {
		wp_nonce_field( 'cam_documento_save_' . $post->ID, 'cam_documento_nonce' );
		$file_url    = get_post_meta( $post->ID, '_cam_file_url', true );
		$file_size   = get_post_meta( $post->ID, '_cam_file_size', true );
		$file_type   = get_post_meta( $post->ID, '_cam_file_type', true );
		$doc_number  = get_post_meta( $post->ID, '_cam_doc_number', true );
		$doc_date    = get_post_meta( $post->ID, '_cam_doc_date', true );
		?>
		<table class="form-table cam-meta-table">
			<tr>
				<th><label for="cam_file_url"><?php esc_html_e( 'URL file', 'comune-app-manager' ); ?></label></th>
				<td>
					<input type="url" id="cam_file_url" name="cam_file_url"
					       value="<?php echo esc_url( $file_url ); ?>" class="large-text" />
					<button type="button" class="button cam-media-upload"
					        data-target="#cam_file_url">
						<?php esc_html_e( 'Scegli file', 'comune-app-manager' ); ?>
					</button>
				</td>
			</tr>
			<tr>
				<th><label for="cam_file_type"><?php esc_html_e( 'Tipo file', 'comune-app-manager' ); ?></label></th>
				<td>
					<select id="cam_file_type" name="cam_file_type">
						<?php
						$types = array( 'PDF', 'DOC', 'DOCX', 'XLS', 'XLSX', 'ZIP', 'altro' );
						foreach ( $types as $t ) {
							printf(
								'<option value="%s" %s>%s</option>',
								esc_attr( strtolower( $t ) ),
								selected( strtolower( $file_type ), strtolower( $t ), false ),
								esc_html( $t )
							);
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="cam_file_size"><?php esc_html_e( 'Dimensione (KB)', 'comune-app-manager' ); ?></label></th>
				<td><input type="number" id="cam_file_size" name="cam_file_size" value="<?php echo esc_attr( $file_size ); ?>" class="small-text" min="0" /></td>
			</tr>
			<tr>
				<th><label for="cam_doc_number"><?php esc_html_e( 'Numero documento', 'comune-app-manager' ); ?></label></th>
				<td><input type="text" id="cam_doc_number" name="cam_doc_number" value="<?php echo esc_attr( $doc_number ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="cam_doc_date"><?php esc_html_e( 'Data documento', 'comune-app-manager' ); ?></label></th>
				<td><input type="date" id="cam_doc_date" name="cam_doc_date" value="<?php echo esc_attr( $doc_date ); ?>" /></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render sidebar meta box: app visibility controls.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_app_visibility( WP_Post $post ): void {
		wp_nonce_field( 'cam_visibility_save_' . $post->ID, 'cam_visibility_nonce' );
		$visible  = get_post_meta( $post->ID, '_cam_app_visible', true );
		$featured = get_post_meta( $post->ID, '_cam_app_featured', true );

		// Default to visible if not yet set.
		if ( '' === $visible ) {
			$visible = '1';
		}
		?>
		<p>
			<label>
				<input type="checkbox" name="cam_app_visible" value="1" <?php checked( $visible, '1' ); ?> />
				<?php esc_html_e( 'Visibile nell\'app', 'comune-app-manager' ); ?>
			</label>
		</p>
		<p>
			<label>
				<input type="checkbox" name="cam_app_featured" value="1" <?php checked( $featured, '1' ); ?> />
				<?php esc_html_e( 'In evidenza', 'comune-app-manager' ); ?>
			</label>
		</p>
		<?php
	}

	// -----------------------------------------------------------------------
	// Save handler
	// -----------------------------------------------------------------------

	/**
	 * Save meta box values on post save.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function save( int $post_id, WP_Post $post ): void {
		// Skip autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Skip revisions.
		if ( 'revision' === $post->post_type ) {
			return;
		}

		// Save app visibility (shared).
		if ( isset( $_POST['cam_visibility_nonce'] ) &&
		     wp_verify_nonce( sanitize_key( $_POST['cam_visibility_nonce'] ), 'cam_visibility_save_' . $post_id ) ) {
			update_post_meta( $post_id, '_cam_app_visible',  isset( $_POST['cam_app_visible'] ) ? '1' : '0' );
			update_post_meta( $post_id, '_cam_app_featured', isset( $_POST['cam_app_featured'] ) ? '1' : '0' );
		}

		switch ( $post->post_type ) {
			case 'comune_avviso':
				$this->save_avviso( $post_id );
				break;
			case 'comune_evento':
				$this->save_evento( $post_id );
				break;
			case 'comune_ufficio':
				$this->save_ufficio( $post_id );
				break;
			case 'comune_luogo':
				$this->save_luogo( $post_id );
				break;
			case 'comune_documento':
				$this->save_documento( $post_id );
				break;
		}
	}

	// -----------------------------------------------------------------------
	// Individual save methods
	// -----------------------------------------------------------------------

	/** Save avviso meta. */
	private function save_avviso( int $post_id ): void {
		if ( ! isset( $_POST['cam_avviso_nonce'] ) ||
		     ! wp_verify_nonce( sanitize_key( $_POST['cam_avviso_nonce'] ), 'cam_avviso_save_' . $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$priorita = sanitize_key( wp_unslash( $_POST['cam_priorita'] ?? 'bassa' ) );
		if ( ! array_key_exists( $priorita, self::PRIORITA_OPTIONS ) ) {
			$priorita = 'bassa';
		}

		update_post_meta( $post_id, '_cam_expiry_date', sanitize_text_field( wp_unslash( $_POST['cam_expiry_date'] ?? '' ) ) );
		update_post_meta( $post_id, '_cam_priorita',    $priorita );
		// Legacy field kept for backwards compat — derived from priorita.
		update_post_meta( $post_id, '_cam_is_important', in_array( $priorita, array( 'alta', 'urgente' ), true ) ? '1' : '0' );
	}

	/** Save evento meta. */
	private function save_evento( int $post_id ): void {
		if ( ! isset( $_POST['cam_evento_nonce'] ) ||
		     ! wp_verify_nonce( sanitize_key( $_POST['cam_evento_nonce'] ), 'cam_evento_save_' . $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array(
			'_cam_event_start_date' => sanitize_text_field( wp_unslash( $_POST['cam_event_start_date'] ?? '' ) ),
			'_cam_event_start_time' => sanitize_text_field( wp_unslash( $_POST['cam_event_start_time'] ?? '' ) ),
			'_cam_event_end_date'   => sanitize_text_field( wp_unslash( $_POST['cam_event_end_date'] ?? '' ) ),
			'_cam_event_end_time'   => sanitize_text_field( wp_unslash( $_POST['cam_event_end_time'] ?? '' ) ),
			'_cam_event_location'   => sanitize_text_field( wp_unslash( $_POST['cam_event_location'] ?? '' ) ),
			'_cam_event_organizer'  => sanitize_text_field( wp_unslash( $_POST['cam_event_organizer'] ?? '' ) ),
			'_cam_event_all_day'    => isset( $_POST['cam_event_all_day'] ) ? '1' : '0',
			'_cam_lat'              => sanitize_text_field( wp_unslash( $_POST['cam_lat'] ?? '' ) ),
			'_cam_lng'              => sanitize_text_field( wp_unslash( $_POST['cam_lng'] ?? '' ) ),
			'_cam_event_location_url' => esc_url_raw( wp_unslash( $_POST['cam_event_location_url'] ?? '' ) ),
		);

		foreach ( $fields as $meta_key => $value ) {
			update_post_meta( $post_id, $meta_key, $value );
		}
	}

	/** Save ufficio meta. */
	private function save_ufficio( int $post_id ): void {
		if ( ! isset( $_POST['cam_ufficio_nonce'] ) ||
		     ! wp_verify_nonce( sanitize_key( $_POST['cam_ufficio_nonce'] ), 'cam_ufficio_save_' . $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array(
			'_cam_phone'        => sanitize_text_field( wp_unslash( $_POST['cam_phone'] ?? '' ) ),
			'_cam_email'        => sanitize_email( wp_unslash( $_POST['cam_email'] ?? '' ) ),
			'_cam_pec'          => sanitize_email( wp_unslash( $_POST['cam_pec'] ?? '' ) ),
			'_cam_address'      => sanitize_text_field( wp_unslash( $_POST['cam_address'] ?? '' ) ),
			'_cam_manager'      => sanitize_text_field( wp_unslash( $_POST['cam_manager'] ?? '' ) ),
			'_cam_office_hours' => sanitize_textarea_field( wp_unslash( $_POST['cam_office_hours'] ?? '' ) ),
		);

		foreach ( $fields as $meta_key => $value ) {
			update_post_meta( $post_id, $meta_key, $value );
		}
	}

	/** Save luogo meta. */
	private function save_luogo( int $post_id ): void {
		if ( ! isset( $_POST['cam_luogo_nonce'] ) ||
		     ! wp_verify_nonce( sanitize_key( $_POST['cam_luogo_nonce'] ), 'cam_luogo_save_' . $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$lat = sanitize_text_field( wp_unslash( $_POST['cam_lat'] ?? '' ) );
		$lng = sanitize_text_field( wp_unslash( $_POST['cam_lng'] ?? '' ) );

		// Basic numeric validation for coordinates.
		if ( '' !== $lat && ( ! is_numeric( $lat ) || abs( (float) $lat ) > 90 ) ) {
			$lat = '';
		}
		if ( '' !== $lng && ( ! is_numeric( $lng ) || abs( (float) $lng ) > 180 ) ) {
			$lng = '';
		}

		update_post_meta( $post_id, '_cam_lat',     $lat );
		update_post_meta( $post_id, '_cam_lng',     $lng );
		update_post_meta( $post_id, '_cam_address', sanitize_text_field( wp_unslash( $_POST['cam_address'] ?? '' ) ) );
		update_post_meta( $post_id, '_cam_phone',   sanitize_text_field( wp_unslash( $_POST['cam_phone'] ?? '' ) ) );
		update_post_meta( $post_id, '_cam_website', esc_url_raw( wp_unslash( $_POST['cam_website'] ?? '' ) ) );
	}

	/** Save documento meta. */
	private function save_documento( int $post_id ): void {
		if ( ! isset( $_POST['cam_documento_nonce'] ) ||
		     ! wp_verify_nonce( sanitize_key( $_POST['cam_documento_nonce'] ), 'cam_documento_save_' . $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$allowed_types = array( 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'altro' );
		$file_type     = sanitize_key( wp_unslash( $_POST['cam_file_type'] ?? 'altro' ) );
		if ( ! in_array( $file_type, $allowed_types, true ) ) {
			$file_type = 'altro';
		}

		update_post_meta( $post_id, '_cam_file_url',  esc_url_raw( wp_unslash( $_POST['cam_file_url'] ?? '' ) ) );
		update_post_meta( $post_id, '_cam_file_type', $file_type );
		update_post_meta( $post_id, '_cam_file_size', absint( $_POST['cam_file_size'] ?? 0 ) );
		update_post_meta( $post_id, '_cam_doc_number', sanitize_text_field( wp_unslash( $_POST['cam_doc_number'] ?? '' ) ) );
		update_post_meta( $post_id, '_cam_doc_date',   sanitize_text_field( wp_unslash( $_POST['cam_doc_date'] ?? '' ) ) );
	}
}
