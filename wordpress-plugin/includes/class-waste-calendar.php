<?php
/**
 * Waste collection calendar management.
 *
 * @package ComuneAppManager
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comune_App_Manager_Waste_Calendar
 *
 * Manages the waste collection calendar entries: add/edit/delete schedule
 * entries for each zone/category combination, and renders the admin page.
 */
class Comune_App_Manager_Waste_Calendar {

	/**
	 * Constructor: hook into admin rendering and AJAX handlers.
	 */
	public function __construct() {
		add_action( 'cam_render_waste_calendar_page', array( $this, 'render_page' ) );
		add_action( 'wp_ajax_cam_save_waste_entry',   array( $this, 'handle_save_entry' ) );
		add_action( 'wp_ajax_cam_delete_waste_entry', array( $this, 'handle_delete_entry' ) );
		add_action( 'wp_ajax_cam_get_waste_entries',  array( $this, 'handle_get_entries' ) );
	}

	/**
	 * Render the waste calendar admin page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_comune_waste' ) ) {
			return;
		}

		global $wpdb;
		$categories = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}cam_waste_categories WHERE is_active = 1 ORDER BY sort_order ASC", ARRAY_A );

		// Named zones from cam_waste_zones; fall back to distinct slugs from schedule.
		$zones = $wpdb->get_results(
			"SELECT slug, name FROM {$wpdb->prefix}cam_waste_zones WHERE is_active = 1 ORDER BY sort_order ASC, name ASC",
			ARRAY_A
		);
		if ( empty( $zones ) ) {
			$raw_slugs = $wpdb->get_col( "SELECT DISTINCT zone_slug FROM {$wpdb->prefix}cam_waste_schedule ORDER BY zone_slug ASC" );
			$zones     = array_map( static function ( string $slug ): array {
				return array( 'slug' => $slug, 'name' => ucwords( str_replace( array( '-', '_' ), ' ', $slug ) ) );
			}, $raw_slugs );
		}

		// Default to current month.
		$month = sanitize_text_field( $_GET['cam_month'] ?? gmdate( 'Y-m' ) );
		if ( ! preg_match( '/^\d{4}-\d{2}$/', $month ) ) {
			$month = gmdate( 'Y-m' );
		}

		$from = $month . '-01';
		$to   = gmdate( 'Y-m-t', strtotime( $from ) );

		$entries = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT s.*, c.name AS category_name, c.color,
				        COALESCE(z.name, s.zone_slug) AS zone_name
				 FROM {$wpdb->prefix}cam_waste_schedule s
				 LEFT JOIN {$wpdb->prefix}cam_waste_categories c ON c.id = s.category_id
				 LEFT JOIN {$wpdb->prefix}cam_waste_zones z ON z.slug = s.zone_slug
				 WHERE s.collection_date BETWEEN %s AND %s
				 ORDER BY s.collection_date ASC, s.zone_slug ASC",
				$from, $to
			),
			ARRAY_A
		);

		$prev_month = gmdate( 'Y-m', strtotime( $from . ' -1 month' ) );
		$next_month = gmdate( 'Y-m', strtotime( $from . ' +1 month' ) );
		?>
		<div class="cam-waste-wrap">
			<!-- Month navigation -->
			<div style="display:flex;align-items:center;gap:16px;margin-bottom:20px">
				<a href="<?php echo esc_url( add_query_arg( 'cam_month', $prev_month ) ); ?>" class="button">&laquo; <?php esc_html_e( 'Mese precedente', 'comune-app-manager' ); ?></a>
				<h2 style="margin:0"><?php echo esc_html( wp_date( 'F Y', strtotime( $from ) ) ); ?></h2>
				<a href="<?php echo esc_url( add_query_arg( 'cam_month', $next_month ) ); ?>" class="button"><?php esc_html_e( 'Mese successivo', 'comune-app-manager' ); ?> &raquo;</a>
			</div>

			<!-- Add new entry form -->
			<div class="postbox" style="padding:16px;margin-bottom:20px">
				<h3><?php esc_html_e( 'Aggiungi raccolta', 'comune-app-manager' ); ?></h3>
				<form id="cam-waste-add-form">
					<?php wp_nonce_field( 'cam_save_waste_entry', 'cam_waste_nonce' ); ?>
					<table class="form-table">
						<tr>
							<th><label for="cam_wc_date"><?php esc_html_e( 'Data', 'comune-app-manager' ); ?></label></th>
							<td><input type="date" id="cam_wc_date" name="collection_date" class="regular-text" required /></td>
						</tr>
						<tr>
							<th><label for="cam_wc_zone"><?php esc_html_e( 'Zona', 'comune-app-manager' ); ?></label></th>
							<td>
								<select id="cam_wc_zone" name="zone_slug" required>
									<option value=""><?php esc_html_e( '— Seleziona zona —', 'comune-app-manager' ); ?></option>
									<?php foreach ( $zones as $zone ) : ?>
									<option value="<?php echo esc_attr( $zone['slug'] ); ?>">
										<?php echo esc_html( $zone['name'] ); ?>
									</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="cam_wc_cat"><?php esc_html_e( 'Tipo rifiuto', 'comune-app-manager' ); ?></label></th>
							<td>
								<select id="cam_wc_cat" name="category_id" required>
									<option value=""><?php esc_html_e( '— Seleziona —', 'comune-app-manager' ); ?></option>
									<?php foreach ( $categories as $cat ) : ?>
									<option value="<?php echo esc_attr( $cat['id'] ); ?>">
										<?php echo esc_html( $cat['name'] ); ?>
									</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="cam_wc_recurrence"><?php esc_html_e( 'Ricorrenza', 'comune-app-manager' ); ?></label></th>
							<td>
								<select id="cam_wc_recurrence" name="recurrence">
									<option value="none"><?php esc_html_e( 'Nessuna (singola data)', 'comune-app-manager' ); ?></option>
									<option value="weekly"><?php esc_html_e( 'Settimanale', 'comune-app-manager' ); ?></option>
									<option value="biweekly"><?php esc_html_e( 'Bisettimanale', 'comune-app-manager' ); ?></option>
									<option value="monthly"><?php esc_html_e( 'Mensile', 'comune-app-manager' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="cam_wc_notes"><?php esc_html_e( 'Note', 'comune-app-manager' ); ?></label></th>
							<td><textarea id="cam_wc_notes" name="notes" rows="2" class="large-text"></textarea></td>
						</tr>
					</table>
					<p>
						<button type="submit" class="button button-primary">
							<?php esc_html_e( 'Aggiungi raccolta', 'comune-app-manager' ); ?>
						</button>
						<span id="cam-waste-feedback" style="margin-left:12px"></span>
					</p>
				</form>
			</div>

			<!-- Entries table -->
			<table class="wp-list-table widefat fixed striped" id="cam-waste-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Data', 'comune-app-manager' ); ?></th>
						<th><?php esc_html_e( 'Zona', 'comune-app-manager' ); ?></th>
						<th><?php esc_html_e( 'Tipo rifiuto', 'comune-app-manager' ); ?></th>
						<th><?php esc_html_e( 'Ricorrenza', 'comune-app-manager' ); ?></th>
						<th><?php esc_html_e( 'Note', 'comune-app-manager' ); ?></th>
						<th><?php esc_html_e( 'Azioni', 'comune-app-manager' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $entries ) ) : ?>
					<tr><td colspan="6"><?php esc_html_e( 'Nessuna raccolta programmata per questo mese.', 'comune-app-manager' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $entries as $entry ) : ?>
					<tr data-id="<?php echo esc_attr( $entry['id'] ); ?>">
						<td><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $entry['collection_date'] ) ) ); ?></td>
						<td><?php echo esc_html( $entry['zone_name'] ); ?></td>
						<td>
							<span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:<?php echo esc_attr( $entry['color'] ); ?>;margin-right:6px"></span>
							<?php echo esc_html( $entry['category_name'] ); ?>
						</td>
						<td><?php echo esc_html( ucfirst( $entry['recurrence'] ?: 'none' ) ); ?></td>
						<td><?php echo esc_html( $entry['notes'] ); ?></td>
						<td>
							<button type="button" class="button button-small cam-delete-waste" data-id="<?php echo esc_attr( $entry['id'] ); ?>">
								<?php esc_html_e( 'Elimina', 'comune-app-manager' ); ?>
							</button>
						</td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>

		<script>
		jQuery(function($){
			var nonce = '<?php echo esc_js( wp_create_nonce( 'cam_save_waste_entry' ) ); ?>';

			$('#cam-waste-add-form').on('submit', function(e){
				e.preventDefault();
				var data = $(this).serialize() + '&action=cam_save_waste_entry&nonce=' + nonce;
				$.post(ajaxurl, data, function(res){
					if(res.success){
						$('#cam-waste-feedback').text('<?php echo esc_js( __( 'Salvato. Aggiorna la pagina per vedere le modifiche.', 'comune-app-manager' ) ); ?>').css('color','green');
					} else {
						$('#cam-waste-feedback').text(res.data.message || '<?php echo esc_js( __( 'Errore.', 'comune-app-manager' ) ); ?>').css('color','red');
					}
				});
			});

			$(document).on('click', '.cam-delete-waste', function(){
				if(!confirm('<?php echo esc_js( __( 'Eliminare questa raccolta?', 'comune-app-manager' ) ); ?>')) return;
				var id = $(this).data('id');
				var $row = $(this).closest('tr');
				$.post(ajaxurl, { action:'cam_delete_waste_entry', id:id, nonce:nonce }, function(res){
					if(res.success){ $row.remove(); }
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Handle AJAX: save a waste calendar entry.
	 */
	public function handle_save_entry(): void {
		check_ajax_referer( 'cam_save_waste_entry', 'nonce' );

		if ( ! current_user_can( 'manage_comune_waste' ) ) {
			wp_send_json_error( array( 'message' => __( 'Accesso non consentito.', 'comune-app-manager' ) ) );
		}

		global $wpdb;

		$date        = sanitize_text_field( $_POST['collection_date'] ?? '' );
		$zone_slug   = sanitize_key( $_POST['zone_slug'] ?? '' );
		$category_id = absint( $_POST['category_id'] ?? 0 );
		$recurrence  = sanitize_key( $_POST['recurrence'] ?? 'none' );
		$notes       = sanitize_textarea_field( $_POST['notes'] ?? '' );

		if ( ! $date || ! $zone_slug || ! $category_id ) {
			wp_send_json_error( array( 'message' => __( 'Tutti i campi obbligatori devono essere compilati.', 'comune-app-manager' ) ) );
		}

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			wp_send_json_error( array( 'message' => __( 'Formato data non valido.', 'comune-app-manager' ) ) );
		}

		$allowed_recurrences = array( 'none', 'weekly', 'biweekly', 'monthly' );
		if ( ! in_array( $recurrence, $allowed_recurrences, true ) ) {
			$recurrence = 'none';
		}

		$result = $wpdb->insert(
			$wpdb->prefix . 'cam_waste_schedule',
			array(
				'category_id'     => $category_id,
				'zone_slug'       => $zone_slug,
				'collection_date' => $date,
				'recurrence'      => $recurrence,
				'notes'           => $notes,
			),
			array( '%d', '%s', '%s', '%s', '%s' )
		);

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Errore durante il salvataggio.', 'comune-app-manager' ) ) );
		}

		wp_send_json_success( array( 'id' => $wpdb->insert_id ) );
	}

	/**
	 * Handle AJAX: delete a waste calendar entry.
	 */
	public function handle_delete_entry(): void {
		check_ajax_referer( 'cam_save_waste_entry', 'nonce' );

		if ( ! current_user_can( 'manage_comune_waste' ) ) {
			wp_send_json_error( array( 'message' => __( 'Accesso non consentito.', 'comune-app-manager' ) ) );
		}

		global $wpdb;
		$id = absint( $_POST['id'] ?? 0 );

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'ID non valido.', 'comune-app-manager' ) ) );
		}

		$result = $wpdb->delete(
			$wpdb->prefix . 'cam_waste_schedule',
			array( 'id' => $id ),
			array( '%d' )
		);

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Eliminazione fallita.', 'comune-app-manager' ) ) );
		}

		wp_send_json_success();
	}

	/**
	 * Handle AJAX: get waste entries for a given month/zone (used by calendar widget).
	 */
	public function handle_get_entries(): void {
		check_ajax_referer( 'cam_save_waste_entry', 'nonce' );

		if ( ! current_user_can( 'manage_comune_waste' ) ) {
			wp_send_json_error();
		}

		global $wpdb;

		$month = sanitize_text_field( $_POST['month'] ?? gmdate( 'Y-m' ) );
		if ( ! preg_match( '/^\d{4}-\d{2}$/', $month ) ) {
			$month = gmdate( 'Y-m' );
		}

		$from = $month . '-01';
		$to   = gmdate( 'Y-m-t', strtotime( $from ) );

		$entries = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT s.*, c.name AS category_name, c.color FROM {$wpdb->prefix}cam_waste_schedule s
				 LEFT JOIN {$wpdb->prefix}cam_waste_categories c ON c.id = s.category_id
				 WHERE s.collection_date BETWEEN %s AND %s
				 ORDER BY s.collection_date ASC",
				$from, $to
			),
			ARRAY_A
		);

		wp_send_json_success( $entries );
	}
}
