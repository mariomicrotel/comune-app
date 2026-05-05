<?php
/**
 * Admin view: Send push notification.
 *
 * @package Comune_App_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! Comune_App_Manager_Permissions::can_manage_notifications() ) {
	wp_die( esc_html__( 'Non hai i permessi per accedere a questa pagina.', 'comune-app-manager' ) );
}

global $wpdb;

$send_result = null;

// -------------------------------------------------------------------------
// Handle POST
// -------------------------------------------------------------------------
if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['send_push_notification'] ) ) {
	check_admin_referer( 'cam_send_push_nonce', 'cam_send_push_nonce' );

	$title          = sanitize_text_field( wp_unslash( $_POST['push_title'] ?? '' ) );
	$body           = sanitize_textarea_field( wp_unslash( $_POST['push_body'] ?? '' ) );
	$target_type    = sanitize_key( $_POST['target_type'] ?? 'tutti' );
	$target_value   = sanitize_text_field( wp_unslash( $_POST['target_value'] ?? '' ) );
	$deep_link_type = sanitize_key( $_POST['deep_link_type'] ?? 'home' );
	$deep_link_id   = absint( $_POST['deep_link_id'] ?? 0 );

	$errors = [];

	if ( empty( $title ) ) {
		$errors[] = __( 'Il titolo è obbligatorio.', 'comune-app-manager' );
	}
	if ( empty( $body ) ) {
		$errors[] = __( 'Il testo della notifica è obbligatorio.', 'comune-app-manager' );
	}
	if ( mb_strlen( $title ) > 50 ) {
		$errors[] = __( 'Il titolo non può superare 50 caratteri.', 'comune-app-manager' );
	}
	if ( mb_strlen( $body ) > 200 ) {
		$errors[] = __( 'Il testo non può superare 200 caratteri.', 'comune-app-manager' );
	}

	if ( empty( $errors ) ) {
		// Build the target string expected by send_push().
		$target_string = 'all';
		if ( 'platform' === $target_type && in_array( $target_value, array( 'ios', 'android' ), true ) ) {
			$target_string = $target_value;
		} elseif ( 'zona_rifiuti' === $target_type && ! empty( $target_value ) ) {
			$target_string = 'zone:' . sanitize_key( $target_value );
		}

		$data_payload = array(
			'deep_link_type' => $deep_link_type,
			'deep_link_id'   => $deep_link_id ? (string) $deep_link_id : '',
		);

		/** @var Comune_App_Manager_Push_Notifications $push */
		$push   = new Comune_App_Manager_Push_Notifications();
		$result = $push->send_push( $title, $body, $target_string, $data_payload );

		if ( is_wp_error( $result ) ) {
			$send_result = array( 'success' => false, 'message' => $result->get_error_message() );
		} else {
			$send_result = array(
				'success' => true,
				'sent'    => $result['success'],
				'failed'  => $result['failure'],
				'total'   => $result['recipients'],
			);
		}
	} else {
		$send_result = array( 'success' => false, 'errors' => $errors );
	}
}

// -------------------------------------------------------------------------
// Load push logs
// -------------------------------------------------------------------------
$push_logs = $wpdb->get_results(
	"SELECT * FROM {$wpdb->prefix}cam_push_logs ORDER BY sent_at DESC LIMIT 10",
	ARRAY_A
);

// -------------------------------------------------------------------------
// Load zones for dropdown (distinct slugs from schedule or cam_waste_zones)
// -------------------------------------------------------------------------
$zone_rows = $wpdb->get_results(
	"SELECT DISTINCT zone_slug FROM {$wpdb->prefix}cam_waste_schedule ORDER BY zone_slug ASC",
	ARRAY_A
);
$zones = array_map( static function ( array $row ): array {
	$slug = $row['zone_slug'];
	return array(
		'id'   => $slug,
		'name' => ucwords( str_replace( array( '-', '_' ), ' ', $slug ) ),
	);
}, $zone_rows );

$deep_link_options = [
	'home'          => __( 'Home', 'comune-app-manager' ),
	'avvisi'        => __( 'Avvisi', 'comune-app-manager' ),
	'eventi'        => __( 'Eventi', 'comune-app-manager' ),
	'rifiuti'       => __( 'Calendario Rifiuti', 'comune-app-manager' ),
	'documenti'     => __( 'Documenti', 'comune-app-manager' ),
	'segnalazioni'  => __( 'Segnalazioni', 'comune-app-manager' ),
];
?>

<div class="wrap cam-wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-bell" style="vertical-align:middle;font-size:1.8rem;height:1.8rem;width:1.8rem;margin-right:6px;"></span>
		<?php esc_html_e( 'Invia notifica push', 'comune-app-manager' ); ?>
	</h1>
	<hr class="wp-header-end">

	<?php if ( null !== $send_result ) : ?>
		<?php if ( isset( $send_result['errors'] ) ) : ?>
			<div class="notice notice-error is-dismissible">
				<ul>
					<?php foreach ( $send_result['errors'] as $err ) : ?>
						<li><?php echo esc_html( $err ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php elseif ( $send_result['success'] ) : ?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php
					printf(
						/* translators: 1: sent count  2: total count  3: failed count */
						esc_html__( 'Notifica inviata: %1$d/%2$d dispositivi raggiunti (%3$d falliti).', 'comune-app-manager' ),
						esc_html( $send_result['sent'] ),
						esc_html( $send_result['total'] ),
						esc_html( $send_result['failed'] )
					);
					?>
					<?php if ( $send_result['invalid_tokens'] > 0 ) : ?>
						<?php
						printf(
							/* translators: %d = invalid token count */
							esc_html__( ' %d token non validi rimossi.', 'comune-app-manager' ),
							esc_html( $send_result['invalid_tokens'] )
						);
						?>
					<?php endif; ?>
				</p>
			</div>
		<?php else : ?>
			<div class="notice notice-error is-dismissible">
				<p><?php echo esc_html( $send_result['message'] ?? __( 'Errore durante l\'invio.', 'comune-app-manager' ) ); ?></p>
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<div class="cam-push-layout">

		<!-- ===== FORM ===== -->
		<div class="cam-push-form-wrap">
			<form method="post" action="" id="cam-push-form" class="cam-meta-box">
				<?php wp_nonce_field( 'cam_send_push_nonce', 'cam_send_push_nonce' ); ?>
				<h2><?php esc_html_e( 'Componi notifica', 'comune-app-manager' ); ?></h2>

				<table class="form-table">

					<!-- Title -->
					<tr>
						<th scope="row">
							<label for="push_title"><?php esc_html_e( 'Titolo *', 'comune-app-manager' ); ?></label>
						</th>
						<td>
							<input
								type="text"
								name="push_title"
								id="push_title"
								class="regular-text"
								maxlength="50"
								value="<?php echo esc_attr( sanitize_text_field( $_POST['push_title'] ?? '' ) ); ?>"
								required
							>
							<span class="cam-char-counter" id="title-counter">
								<span id="title-count">0</span>/50
							</span>
						</td>
					</tr>

					<!-- Body -->
					<tr>
						<th scope="row">
							<label for="push_body"><?php esc_html_e( 'Testo *', 'comune-app-manager' ); ?></label>
						</th>
						<td>
							<textarea
								name="push_body"
								id="push_body"
								class="large-text"
								rows="4"
								maxlength="200"
								required
							><?php echo esc_textarea( sanitize_textarea_field( wp_unslash( $_POST['push_body'] ?? '' ) ) ); ?></textarea>
							<span class="cam-char-counter" id="body-counter">
								<span id="body-count">0</span>/200
							</span>
						</td>
					</tr>

					<!-- Target type -->
					<tr>
						<th scope="row"><?php esc_html_e( 'Destinatari', 'comune-app-manager' ); ?></th>
						<td>
							<fieldset>
								<label class="cam-radio-label">
									<input type="radio" name="target_type" value="tutti" <?php checked( 'tutti', $_POST['target_type'] ?? 'tutti' ); ?>>
									<?php esc_html_e( 'Tutti i dispositivi', 'comune-app-manager' ); ?>
								</label><br>
								<label class="cam-radio-label">
									<input type="radio" name="target_type" value="platform" <?php checked( 'platform', $_POST['target_type'] ?? '' ); ?>>
									<?php esc_html_e( 'Per piattaforma', 'comune-app-manager' ); ?>
								</label><br>
								<label class="cam-radio-label">
									<input type="radio" name="target_type" value="zona_rifiuti" <?php checked( 'zona_rifiuti', $_POST['target_type'] ?? '' ); ?>>
									<?php esc_html_e( 'Per zona rifiuti', 'comune-app-manager' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>

					<!-- Target value: platform -->
					<tr id="target-platform-row" style="display:none;">
						<th scope="row">
							<label for="target_platform"><?php esc_html_e( 'Piattaforma', 'comune-app-manager' ); ?></label>
						</th>
						<td>
							<select name="target_value" id="target_platform" class="regular-text">
								<option value="ios" <?php selected( 'ios', $_POST['target_value'] ?? '' ); ?>>iOS</option>
								<option value="android" <?php selected( 'android', $_POST['target_value'] ?? '' ); ?>>Android</option>
							</select>
						</td>
					</tr>

					<!-- Target value: zona -->
					<tr id="target-zona-row" style="display:none;">
						<th scope="row">
							<label for="target_zona"><?php esc_html_e( 'Zona rifiuti', 'comune-app-manager' ); ?></label>
						</th>
						<td>
							<select name="target_value" id="target_zona" class="regular-text">
								<option value=""><?php esc_html_e( '— Seleziona zona —', 'comune-app-manager' ); ?></option>
								<?php foreach ( $zones as $zone ) : ?>
									<option value="<?php echo esc_attr( $zone['id'] ); ?>" <?php selected( (string) $zone['id'], $_POST['target_value'] ?? '' ); ?>>
										<?php echo esc_html( $zone['name'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>

					<!-- Deep link type -->
					<tr>
						<th scope="row">
							<label for="deep_link_type"><?php esc_html_e( 'Destinazione link', 'comune-app-manager' ); ?></label>
						</th>
						<td>
							<select name="deep_link_type" id="deep_link_type" class="regular-text">
								<?php foreach ( $deep_link_options as $val => $label ) : ?>
									<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $val, $_POST['deep_link_type'] ?? 'home' ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>

					<!-- Deep link ID -->
					<tr>
						<th scope="row">
							<label for="deep_link_id"><?php esc_html_e( 'ID elemento (opzionale)', 'comune-app-manager' ); ?></label>
						</th>
						<td>
							<input
								type="number"
								name="deep_link_id"
								id="deep_link_id"
								class="small-text"
								min="0"
								value="<?php echo esc_attr( absint( $_POST['deep_link_id'] ?? 0 ) ?: '' ); ?>"
							>
							<p class="description">
								<?php esc_html_e( 'ID del contenuto specifico (es. ID avviso). Lascia vuoto per aprire la sezione generica.', 'comune-app-manager' ); ?>
							</p>
						</td>
					</tr>

				</table>

				<p class="submit">
					<button
						type="submit"
						name="send_push_notification"
						class="button button-primary button-hero"
						id="cam-send-push-btn"
						onclick="return confirm('<?php esc_attr_e( 'Confermi l\'invio della notifica push?', 'comune-app-manager' ); ?>')"
					>
						<span class="dashicons dashicons-bell" style="vertical-align:middle;"></span>
						<?php esc_html_e( 'Invia Notifica', 'comune-app-manager' ); ?>
					</button>
					<span id="cam-push-spinner" class="spinner" style="float:none;margin-left:8px;vertical-align:middle;"></span>
				</p>
			</form>
		</div>

		<!-- ===== PREVIEW ===== -->
		<div class="cam-push-preview-wrap">
			<div class="cam-meta-box">
				<h2><?php esc_html_e( 'Anteprima notifica', 'comune-app-manager' ); ?></h2>
				<div class="cam-push-preview" id="push-preview">
					<div class="cam-push-preview-phone">
						<div class="cam-push-preview-notification">
							<div class="cam-push-preview-icon">
								<span class="dashicons dashicons-smartphone"></span>
							</div>
							<div class="cam-push-preview-content">
								<div class="cam-push-preview-title" id="preview-title">
									<?php esc_html_e( 'Titolo notifica', 'comune-app-manager' ); ?>
								</div>
								<div class="cam-push-preview-body" id="preview-body">
									<?php esc_html_e( 'Testo della notifica…', 'comune-app-manager' ); ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

	</div><!-- .cam-push-layout -->

	<!-- ===== LOG RECENTI ===== -->
	<div class="cam-meta-box" style="margin-top:24px;">
		<h2><?php esc_html_e( 'Log invii recenti', 'comune-app-manager' ); ?></h2>
		<div class="cam-table-wrap">
			<?php if ( ! empty( $push_logs ) ) : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Titolo', 'comune-app-manager' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Target', 'comune-app-manager' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Successi', 'comune-app-manager' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Falliti', 'comune-app-manager' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Data', 'comune-app-manager' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Operatore', 'comune-app-manager' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $push_logs as $log ) : ?>
							<tr>
								<td><?php echo esc_html( $log['title'] ); ?></td>
								<td><?php echo esc_html( $log['target_type'] ); ?></td>
								<td>
									<?php
									$ok = (int) $log['success_count'];
									echo '<span style="color:' . ( $ok > 0 ? 'green' : 'inherit' ) . '">' . esc_html( number_format_i18n( $ok ) ) . '</span>';
									?>
								</td>
								<td>
									<?php
									$fail = (int) $log['failure_count'];
									echo '<span style="color:' . ( $fail > 0 ? '#c00' : 'inherit' ) . '">' . esc_html( number_format_i18n( $fail ) ) . '</span>';
									?>
								</td>
								<td>
									<?php
									echo esc_html(
										wp_date(
											get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
											strtotime( $log['sent_at'] )
										)
									);
									?>
								</td>
								<td>
									<?php
									$op_user = get_userdata( (int) $log['sent_by'] );
									echo esc_html( $op_user ? $op_user->display_name : '—' );
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p class="cam-empty-state"><?php esc_html_e( 'Nessuna notifica inviata finora.', 'comune-app-manager' ); ?></p>
			<?php endif; ?>
		</div>
	</div>

</div><!-- .wrap.cam-wrap -->
