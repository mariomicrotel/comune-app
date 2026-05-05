<?php
/**
 * Segnalazioni (citizen reports) management.
 *
 * @package ComuneAppManager
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comune_App_Manager_Reports
 *
 * Manages the admin interface and export logic for citizen reports (segnalazioni).
 */
class Comune_App_Manager_Reports {

	/**
	 * Allowed status values.
	 *
	 * @var string[]
	 */
	private const STATUSES = array( 'pending', 'in_progress', 'resolved', 'rejected' );

	/**
	 * Constructor: registers hooks related to the reports page.
	 */
	public function __construct() {
		add_action( 'cam_render_reports_page', array( $this, 'render_page' ) );
		add_action( 'wp_ajax_cam_update_segnalazione_status', array( $this, 'handle_status_update' ) );
	}

	/**
	 * Render the segnalazioni admin list page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_comune_reports' ) ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'cam_segnalazioni';

		// Handle status filter.
		$filter_status = sanitize_key( $_GET['cam_status'] ?? '' );
		$filter_status = in_array( $filter_status, self::STATUSES, true ) ? $filter_status : '';

		$per_page    = 20;
		$current_page = max( 1, absint( $_GET['paged'] ?? 1 ) );
		$offset      = ( $current_page - 1 ) * $per_page;

		if ( $filter_status ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM `{$table}` WHERE status = %s ORDER BY created_at DESC LIMIT %d OFFSET %d",
					$filter_status, $per_page, $offset
				),
				ARRAY_A
			);
			$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE status = %s", $filter_status ) );
		} else {
			$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset ), ARRAY_A );
			$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );
		}

		$total_pages = (int) ceil( $total / $per_page );
		?>
		<div class="cam-reports-wrap">
			<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
				<form method="get">
					<input type="hidden" name="page" value="cam-reports" />
					<select name="cam_status">
						<option value=""><?php esc_html_e( 'Tutti gli stati', 'comune-app-manager' ); ?></option>
						<?php foreach ( self::STATUSES as $s ) : ?>
						<option value="<?php echo esc_attr( $s ); ?>" <?php selected( $filter_status, $s ); ?>>
							<?php echo esc_html( $this->status_label( $s ) ); ?>
						</option>
						<?php endforeach; ?>
					</select>
					<input type="submit" class="button" value="<?php esc_attr_e( 'Filtra', 'comune-app-manager' ); ?>" />
				</form>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
					<input type="hidden" name="action" value="cam_export_report" />
					<?php wp_nonce_field( 'cam_export_report', 'cam_export_nonce' ); ?>
					<input type="hidden" name="status" value="<?php echo esc_attr( $filter_status ); ?>" />
					<input type="submit" class="button button-secondary" value="<?php esc_attr_e( 'Esporta CSV', 'comune-app-manager' ); ?>" />
				</form>
			</div>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Codice', 'comune-app-manager' ); ?></th>
						<th><?php esc_html_e( 'Titolo', 'comune-app-manager' ); ?></th>
						<th><?php esc_html_e( 'Categoria', 'comune-app-manager' ); ?></th>
						<th><?php esc_html_e( 'Stato', 'comune-app-manager' ); ?></th>
						<th><?php esc_html_e( 'Priorità', 'comune-app-manager' ); ?></th>
						<th><?php esc_html_e( 'Segnalato da', 'comune-app-manager' ); ?></th>
						<th><?php esc_html_e( 'Data', 'comune-app-manager' ); ?></th>
						<th><?php esc_html_e( 'Azioni', 'comune-app-manager' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr>
						<td colspan="8"><?php esc_html_e( 'Nessuna segnalazione trovata.', 'comune-app-manager' ); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td><code><?php echo esc_html( $row['public_code'] ); ?></code></td>
						<td><?php echo esc_html( $row['title'] ); ?></td>
						<td><?php echo esc_html( $row['category'] ); ?></td>
						<td>
							<span class="cam-status-badge cam-status-<?php echo esc_attr( $row['status'] ); ?>">
								<?php echo esc_html( $this->status_label( $row['status'] ) ); ?>
							</span>
						</td>
						<td><?php echo esc_html( ucfirst( $row['priority'] ) ); ?></td>
						<td><?php echo esc_html( $row['submitter_name'] ?: __( 'Anonimo', 'comune-app-manager' ) ); ?></td>
						<td><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $row['created_at'] ) ) ); ?></td>
						<td>
							<select class="cam-status-changer" data-id="<?php echo esc_attr( $row['id'] ); ?>">
								<?php foreach ( self::STATUSES as $s ) : ?>
								<option value="<?php echo esc_attr( $s ); ?>" <?php selected( $row['status'], $s ); ?>>
									<?php echo esc_html( $this->status_label( $s ) ); ?>
								</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>

			<?php if ( $total_pages > 1 ) : ?>
			<div class="tablenav bottom">
				<div class="tablenav-pages">
					<?php
					echo paginate_links( array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'base'      => add_query_arg( 'paged', '%#%' ),
						'format'    => '',
						'current'   => $current_page,
						'total'     => $total_pages,
					) );
					?>
				</div>
			</div>
			<?php endif; ?>
		</div>

		<script>
		jQuery(function($){
			$('.cam-status-changer').on('change', function(){
				var id = $(this).data('id');
				var status = $(this).val();
				$.post(ajaxurl, {
					action: 'cam_update_segnalazione_status',
					id: id,
					status: status,
					nonce: '<?php echo esc_js( wp_create_nonce( 'cam_update_status' ) ); ?>'
				}, function(res){
					if(!res.success){ alert('<?php echo esc_js( __( 'Aggiornamento fallito.', 'comune-app-manager' ) ); ?>'); }
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Handle AJAX export of segnalazioni to CSV.
	 */
	public function handle_export(): void {
		if ( ! current_user_can( 'manage_comune_reports' ) ) {
			wp_die( esc_html__( 'Accesso non consentito.', 'comune-app-manager' ), 403 );
		}

		if ( ! isset( $_POST['cam_export_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['cam_export_nonce'] ), 'cam_export_report' ) ) {
			wp_die( esc_html__( 'Nonce non valido.', 'comune-app-manager' ), 403 );
		}

		global $wpdb;
		$table         = $wpdb->prefix . 'cam_segnalazioni';
		$filter_status = sanitize_key( $_POST['status'] ?? '' );
		$filter_status = in_array( $filter_status, self::STATUSES, true ) ? $filter_status : '';

		if ( $filter_status ) {
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE status = %s ORDER BY created_at DESC", $filter_status ), ARRAY_A );
		} else {
			$rows = $wpdb->get_results( "SELECT * FROM `{$table}` ORDER BY created_at DESC", ARRAY_A );
		}

		$filename = 'segnalazioni-' . gmdate( 'Y-m-d' ) . '.csv';

		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Cache-Control: no-cache, must-revalidate' );

		$output = fopen( 'php://output', 'w' );
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) ); // UTF-8 BOM for Excel.

		// Header row.
		fputcsv( $output, array(
			'ID', 'Codice pubblico', 'Categoria', 'Titolo', 'Descrizione',
			'Indirizzo', 'Stato', 'Priorità', 'Nome segnalante', 'Email segnalante',
			'Data creazione', 'Data risoluzione',
		) );

		foreach ( $rows as $row ) {
			fputcsv( $output, array(
				$row['id'],
				$row['public_code'],
				$row['category'],
				$row['title'],
				$row['description'],
				$row['address'],
				$this->status_label( $row['status'] ),
				ucfirst( $row['priority'] ),
				$row['submitter_name'],
				$row['submitter_email'],
				$row['created_at'],
				$row['resolved_at'],
			) );
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * Handle AJAX status update for a segnalazione.
	 */
	public function handle_status_update(): void {
		check_ajax_referer( 'cam_update_status', 'nonce' );

		if ( ! current_user_can( 'manage_comune_reports' ) ) {
			wp_send_json_error( array( 'message' => __( 'Accesso non consentito.', 'comune-app-manager' ) ) );
		}

		$id     = absint( $_POST['id'] ?? 0 );
		$status = sanitize_key( $_POST['status'] ?? '' );

		if ( ! $id || ! in_array( $status, self::STATUSES, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Parametri non validi.', 'comune-app-manager' ) ) );
		}

		global $wpdb;

		$data = array( 'status' => $status );
		if ( 'resolved' === $status ) {
			$data['resolved_at'] = current_time( 'mysql' );
		}

		$result = $wpdb->update(
			$wpdb->prefix . 'cam_segnalazioni',
			$data,
			array( 'id' => $id ),
			array_fill( 0, count( $data ), '%s' ),
			array( '%d' )
		);

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Aggiornamento fallito.', 'comune-app-manager' ) ) );
		}

		// Notify the submitting device when status reaches a meaningful milestone.
		if ( in_array( $status, array( 'in_progress', 'resolved', 'rejected' ), true ) ) {
			$this->maybe_notify_device( $id, $status );
		}

		wp_send_json_success( array( 'status' => $status ) );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Send a push notification to the device that submitted the segnalazione,
	 * if the FCM token for that device is known.
	 *
	 * @param int    $segnalazione_id Internal DB id.
	 * @param string $new_status      New status slug.
	 */
	private function maybe_notify_device( int $segnalazione_id, string $new_status ): void {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT public_code, title, device_id FROM {$wpdb->prefix}cam_segnalazioni WHERE id = %d",
				$segnalazione_id
			)
		);

		if ( ! $row || empty( $row->device_id ) ) {
			return;
		}

		$token = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT token FROM {$wpdb->prefix}cam_device_tokens WHERE device_id = %s AND is_active = 1 ORDER BY updated_at DESC LIMIT 1",
				$row->device_id
			)
		);

		if ( ! $token ) {
			return;
		}

		$status_messages = array(
			'in_progress' => __( 'La tua segnalazione è in lavorazione.', 'comune-app-manager' ),
			'resolved'    => __( 'La tua segnalazione è stata risolta. Grazie!', 'comune-app-manager' ),
			'rejected'    => __( 'La tua segnalazione non è stata accettata.', 'comune-app-manager' ),
		);

		$push_title = mb_substr( $row->title, 0, 50 );
		$push_body  = mb_substr( $status_messages[ $new_status ] ?? $this->status_label( $new_status ), 0, 200 );

		$push = new Comune_App_Manager_Push_Notifications();
		$push->send_push_to_tokens(
			array( $token ),
			$push_title,
			$push_body,
			array(
				'deep_link_type' => 'segnalazioni',
				'public_code'    => $row->public_code,
			)
		);
	}

	/**
	 * Return the human-readable Italian label for a status slug.
	 *
	 * @param string $status Status slug.
	 * @return string Translated label.
	 */
	private function status_label( string $status ): string {
		$map = array(
			'pending'     => __( 'In attesa', 'comune-app-manager' ),
			'in_progress' => __( 'In lavorazione', 'comune-app-manager' ),
			'resolved'    => __( 'Risolta', 'comune-app-manager' ),
			'rejected'    => __( 'Rifiutata', 'comune-app-manager' ),
		);

		return $map[ $status ] ?? ucfirst( $status );
	}
}
