<?php
/**
 * Admin detail/edit view for a single segnalazione.
 *
 * @package Comune_App_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! Comune_App_Manager_Permissions::can_read_reports() ) {
	wp_die( esc_html__( 'Non hai i permessi per accedere a questa pagina.', 'comune-app-manager' ) );
}

global $wpdb;

$report_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

if ( ! $report_id ) {
	wp_die( esc_html__( 'ID segnalazione non valido.', 'comune-app-manager' ) );
}

$table  = $wpdb->prefix . 'comune_app_reports';
$report = $wpdb->get_row(
	$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $report_id ),
	ARRAY_A
);

if ( ! $report ) {
	wp_die( esc_html__( 'Segnalazione non trovata.', 'comune-app-manager' ) );
}

// -------------------------------------------------------------------------
// Handle POST update
// -------------------------------------------------------------------------
$update_message = '';
$update_type    = '';

if ( 'POST' === $_SERVER['REQUEST_METHOD'] && Comune_App_Manager_Permissions::can_manage_reports() ) {
	check_admin_referer( 'cam_update_report_nonce', 'cam_update_report_nonce' );

	$allowed_statuses    = [ 'ricevuta', 'presa_in_carico', 'in_lavorazione', 'chiusa', 'respinta', 'non_di_competenza' ];
	$allowed_priorities  = [ 'bassa', 'media', 'alta', 'urgente' ];

	$new_status          = isset( $_POST['status'] )          ? sanitize_key( $_POST['status'] )                     : $report['status'];
	$new_priority        = isset( $_POST['priority'] )        ? sanitize_key( $_POST['priority'] )                   : $report['priority'];
	$new_assigned_to     = isset( $_POST['assigned_to'] )     ? absint( $_POST['assigned_to'] )                      : (int) $report['assigned_to'];
	$new_resolution_note = isset( $_POST['resolution_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['resolution_note'] ) ) : '';
	$send_push           = isset( $_POST['send_push_on_update'] ) && '1' === $_POST['send_push_on_update'];

	if ( ! in_array( $new_status, $allowed_statuses, true ) ) {
		$new_status = $report['status'];
	}

	if ( ! in_array( $new_priority, $allowed_priorities, true ) ) {
		$new_priority = $report['priority'];
	}

	$update_data = [
		'status'          => $new_status,
		'priority'        => $new_priority,
		'assigned_to'     => $new_assigned_to ?: null,
		'resolution_note' => $new_resolution_note,
		'updated_at'      => current_time( 'mysql' ),
	];

	$updated = $wpdb->update( $table, $update_data, [ 'id' => $report_id ], null, [ '%d' ] );

	if ( false !== $updated ) {
		$report         = array_merge( $report, $update_data );
		$update_message = __( 'Segnalazione aggiornata con successo.', 'comune-app-manager' );
		$update_type    = 'success';

		// Optional: send push notification to the device that submitted the report.
		if ( $send_push && ! empty( $report['device_uuid'] ) ) {
			$token_row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT token FROM {$wpdb->prefix}comune_app_device_tokens WHERE device_uuid = %s LIMIT 1",
					$report['device_uuid']
				),
				ARRAY_A
			);

			if ( ! empty( $token_row['token'] ) ) {
				$firebase = new Comune_App_Manager_Firebase();

				if ( $firebase->is_configured() ) {
					$push_title = __( 'Aggiornamento segnalazione', 'comune-app-manager' );
					/* translators: %s = report code */
					$push_body = sprintf( __( 'La tua segnalazione %s è stata aggiornata.', 'comune-app-manager' ), $report['code'] );

					$firebase->send_to_token(
						$token_row['token'],
						$push_title,
						$push_body,
						[
							'report_id'   => (string) $report_id,
							'report_code' => $report['code'],
							'new_status'  => $new_status,
							'deep_link'   => 'segnalazioni',
						]
					);
				}
			}
		}
	} else {
		$update_message = __( 'Errore durante l\'aggiornamento della segnalazione.', 'comune-app-manager' );
		$update_type    = 'error';
	}
}

// -------------------------------------------------------------------------
// Load attachments
// -------------------------------------------------------------------------
$attachments = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT a.id, a.post_id, p.guid, p.post_mime_type
		 FROM {$wpdb->prefix}comune_app_report_attachments a
		 INNER JOIN {$wpdb->posts} p ON p.ID = a.post_id
		 WHERE a.report_id = %d
		 ORDER BY a.id ASC",
		$report_id
	),
	ARRAY_A
);

// -------------------------------------------------------------------------
// Load users for assigned_to dropdown
// -------------------------------------------------------------------------
$assignable_users = get_users( [
	'capability__in' => [ 'manage_comune_reports', 'manage_comune_app' ],
	'fields'         => [ 'ID', 'display_name' ],
	'number'         => 100,
] );

// -------------------------------------------------------------------------
// Status / priority options
// -------------------------------------------------------------------------
$status_options = [
	'ricevuta'          => __( 'Ricevuta', 'comune-app-manager' ),
	'presa_in_carico'   => __( 'Presa in carico', 'comune-app-manager' ),
	'in_lavorazione'    => __( 'In lavorazione', 'comune-app-manager' ),
	'chiusa'            => __( 'Chiusa', 'comune-app-manager' ),
	'respinta'          => __( 'Respinta', 'comune-app-manager' ),
	'non_di_competenza' => __( 'Non di competenza', 'comune-app-manager' ),
];

$priority_options = [
	'bassa'   => __( 'Bassa', 'comune-app-manager' ),
	'media'   => __( 'Media', 'comune-app-manager' ),
	'alta'    => __( 'Alta', 'comune-app-manager' ),
	'urgente' => __( 'Urgente', 'comune-app-manager' ),
];

// Maps link.
$maps_url = '';
if ( ! empty( $report['latitude'] ) && ! empty( $report['longitude'] ) ) {
	$maps_url = 'https://www.google.com/maps?q=' . rawurlencode( $report['latitude'] . ',' . $report['longitude'] );
}
?>

<div class="wrap cam-wrap">
	<h1 class="wp-heading-inline">
		<?php
		printf(
			/* translators: %s = report code */
			esc_html__( 'Segnalazione: %s', 'comune-app-manager' ),
			esc_html( $report['code'] )
		);
		?>
		<span class="cam-status-badge status-<?php echo esc_attr( $report['status'] ); ?>" style="margin-left:12px;font-size:0.8rem;">
			<?php echo esc_html( $status_options[ $report['status'] ] ?? $report['status'] ); ?>
		</span>
	</h1>

	<a href="<?php echo esc_url( admin_url( 'admin.php?page=cam-reports' ) ); ?>" class="page-title-action">
		&larr; <?php esc_html_e( 'Torna alla lista', 'comune-app-manager' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php if ( $update_message ) : ?>
		<div class="notice notice-<?php echo esc_attr( $update_type ); ?> is-dismissible">
			<p><?php echo esc_html( $update_message ); ?></p>
		</div>
	<?php endif; ?>

	<div class="cam-detail-grid">

		<!-- ===== LEFT: READ-ONLY INFO ===== -->
		<div class="cam-detail-info">

			<div class="cam-meta-box">
				<h2><?php esc_html_e( 'Informazioni generali', 'comune-app-manager' ); ?></h2>
				<table class="cam-info-table">
					<tr>
						<th><?php esc_html_e( 'Codice', 'comune-app-manager' ); ?></th>
						<td><?php echo esc_html( $report['code'] ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Data invio', 'comune-app-manager' ); ?></th>
						<td>
							<?php
							echo esc_html(
								wp_date(
									get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
									strtotime( $report['created_at'] )
								)
							);
							?>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Categoria', 'comune-app-manager' ); ?></th>
						<td><?php echo esc_html( $report['category'] ); ?></td>
					</tr>
					<?php if ( ! empty( $report['subcategory'] ) ) : ?>
						<tr>
							<th><?php esc_html_e( 'Sottocategoria', 'comune-app-manager' ); ?></th>
							<td><?php echo esc_html( $report['subcategory'] ); ?></td>
						</tr>
					<?php endif; ?>
				</table>
			</div>

			<div class="cam-meta-box">
				<h2><?php esc_html_e( 'Cittadino', 'comune-app-manager' ); ?></h2>
				<table class="cam-info-table">
					<tr>
						<th><?php esc_html_e( 'Nome', 'comune-app-manager' ); ?></th>
						<td><?php echo esc_html( $report['citizen_name'] ?? '—' ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Email', 'comune-app-manager' ); ?></th>
						<td>
							<?php if ( ! empty( $report['citizen_email'] ) ) : ?>
								<a href="mailto:<?php echo esc_attr( $report['citizen_email'] ); ?>">
									<?php echo esc_html( $report['citizen_email'] ); ?>
								</a>
							<?php else : ?>
								—
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Telefono', 'comune-app-manager' ); ?></th>
						<td><?php echo esc_html( $report['citizen_phone'] ?? '—' ); ?></td>
					</tr>
					<?php if ( ! empty( $report['device_uuid'] ) ) : ?>
						<tr>
							<th><?php esc_html_e( 'Device UUID', 'comune-app-manager' ); ?></th>
							<td><code><?php echo esc_html( $report['device_uuid'] ); ?></code></td>
						</tr>
					<?php endif; ?>
				</table>
			</div>

			<div class="cam-meta-box">
				<h2><?php esc_html_e( 'Descrizione', 'comune-app-manager' ); ?></h2>
				<div class="cam-description">
					<?php echo wp_kses_post( nl2br( $report['description'] ) ); ?>
				</div>
			</div>

			<div class="cam-meta-box">
				<h2><?php esc_html_e( 'Luogo', 'comune-app-manager' ); ?></h2>
				<p><strong><?php esc_html_e( 'Indirizzo:', 'comune-app-manager' ); ?></strong> <?php echo esc_html( $report['address'] ?? '—' ); ?></p>
				<?php if ( $maps_url ) : ?>
					<p>
						<a href="<?php echo esc_url( $maps_url ); ?>" target="_blank" rel="noopener noreferrer" class="button button-secondary button-small">
							<span class="dashicons dashicons-location" style="vertical-align:middle;"></span>
							<?php esc_html_e( 'Apri in Google Maps', 'comune-app-manager' ); ?>
						</a>
						<small style="margin-left:8px;">
							<?php echo esc_html( $report['latitude'] ); ?>, <?php echo esc_html( $report['longitude'] ); ?>
						</small>
					</p>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $attachments ) ) : ?>
				<div class="cam-meta-box">
					<h2><?php esc_html_e( 'Foto allegate', 'comune-app-manager' ); ?></h2>
					<div class="cam-attachments">
						<?php foreach ( $attachments as $att ) : ?>
							<?php if ( str_contains( (string) $att['post_mime_type'], 'image' ) ) : ?>
								<a href="<?php echo esc_url( $att['guid'] ); ?>" target="_blank" rel="noopener noreferrer">
									<?php echo wp_get_attachment_image( (int) $att['post_id'], [ 120, 120 ], false, [ 'class' => 'cam-attachment-thumb' ] ); ?>
								</a>
							<?php else : ?>
								<a href="<?php echo esc_url( $att['guid'] ); ?>" target="_blank" rel="noopener noreferrer" class="button button-small">
									<?php esc_html_e( 'Allegato', 'comune-app-manager' ); ?> #<?php echo esc_html( $att['id'] ); ?>
								</a>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

		</div><!-- .cam-detail-info -->

		<!-- ===== RIGHT: EDIT FORM ===== -->
		<?php if ( Comune_App_Manager_Permissions::can_manage_reports() ) : ?>
			<div class="cam-detail-edit">
				<div class="cam-meta-box">
					<h2><?php esc_html_e( 'Gestione segnalazione', 'comune-app-manager' ); ?></h2>

					<form method="post" action="" id="cam-report-edit-form">
						<?php wp_nonce_field( 'cam_update_report_nonce', 'cam_update_report_nonce' ); ?>
						<input type="hidden" name="report_id" value="<?php echo esc_attr( $report_id ); ?>">

						<table class="form-table cam-edit-table">
							<tr>
								<th scope="row">
									<label for="report_status"><?php esc_html_e( 'Stato', 'comune-app-manager' ); ?></label>
								</th>
								<td>
									<select name="status" id="report_status" class="regular-text">
										<?php foreach ( $status_options as $val => $label ) : ?>
											<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $report['status'], $val ); ?>>
												<?php echo esc_html( $label ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="report_priority"><?php esc_html_e( 'Priorità', 'comune-app-manager' ); ?></label>
								</th>
								<td>
									<select name="priority" id="report_priority" class="regular-text">
										<?php foreach ( $priority_options as $val => $label ) : ?>
											<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $report['priority'] ?? 'media', $val ); ?>>
												<?php echo esc_html( $label ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="report_assigned_to"><?php esc_html_e( 'Assegnato a', 'comune-app-manager' ); ?></label>
								</th>
								<td>
									<select name="assigned_to" id="report_assigned_to" class="regular-text">
										<option value=""><?php esc_html_e( '— Non assegnato —', 'comune-app-manager' ); ?></option>
										<?php foreach ( $assignable_users as $user ) : ?>
											<option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( (int) $report['assigned_to'], (int) $user->ID ); ?>>
												<?php echo esc_html( $user->display_name ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="report_resolution_note"><?php esc_html_e( 'Note di risoluzione', 'comune-app-manager' ); ?></label>
								</th>
								<td>
									<textarea
										name="resolution_note"
										id="report_resolution_note"
										class="large-text"
										rows="5"
									><?php echo esc_textarea( $report['resolution_note'] ?? '' ); ?></textarea>
									<p class="description">
										<?php esc_html_e( 'Note interne visibili solo agli operatori.', 'comune-app-manager' ); ?>
									</p>
								</td>
							</tr>

							<?php if ( ! empty( $report['device_uuid'] ) ) : ?>
								<tr>
									<th scope="row"><?php esc_html_e( 'Notifica al cittadino', 'comune-app-manager' ); ?></th>
									<td>
										<label>
											<input type="checkbox" name="send_push_on_update" value="1">
											<?php esc_html_e( 'Invia push notification al dispositivo del cittadino', 'comune-app-manager' ); ?>
										</label>
										<p class="description">
											<?php esc_html_e( 'Richiede Firebase configurato e token dispositivo valido.', 'comune-app-manager' ); ?>
										</p>
									</td>
								</tr>
							<?php endif; ?>
						</table>

						<p class="submit">
							<button type="submit" name="submit_update" class="button button-primary">
								<?php esc_html_e( 'Salva modifiche', 'comune-app-manager' ); ?>
							</button>
							<span id="cam-update-spinner" class="spinner" style="float:none;"></span>
						</p>
					</form>
				</div>

				<?php if ( ! empty( $report['resolution_note'] ) && $report['resolution_note'] !== ( $_POST['resolution_note'] ?? null ) ) : ?>
					<div class="cam-meta-box">
						<h2><?php esc_html_e( 'Note esistenti', 'comune-app-manager' ); ?></h2>
						<div class="cam-description">
							<?php echo wp_kses_post( nl2br( $report['resolution_note'] ) ); ?>
						</div>
					</div>
				<?php endif; ?>

			</div><!-- .cam-detail-edit -->
		<?php endif; ?>

	</div><!-- .cam-detail-grid -->

</div><!-- .wrap.cam-wrap -->
