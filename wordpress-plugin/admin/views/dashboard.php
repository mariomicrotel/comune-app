<?php
/**
 * Admin Dashboard view for Comune App Manager.
 *
 * @package Comune_App_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! Comune_App_Manager_Permissions::can_manage_app() ) {
	wp_die( esc_html__( 'Non hai i permessi per accedere a questa pagina.', 'comune-app-manager' ) );
}

global $wpdb;

// -------------------------------------------------------------------------
// Stat queries (corrected table names)
// -------------------------------------------------------------------------

$avvisi_count = (int) $wpdb->get_var(
	"SELECT COUNT(*) FROM {$wpdb->posts}
	 WHERE post_type = 'comune_avviso'
	   AND post_status = 'publish'"
);

$reports_open_count = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->prefix}cam_segnalazioni
		 WHERE status NOT IN (%s, %s)",
		'resolved',
		'rejected'
	)
);

$tokens_count = (int) $wpdb->get_var(
	"SELECT COUNT(*) FROM {$wpdb->prefix}cam_device_tokens WHERE is_active = 1"
);

$surveys_active_count = (int) $wpdb->get_var(
	"SELECT COUNT(*) FROM {$wpdb->posts} p
	 WHERE p.post_type = 'cam_sondaggio'
	   AND p.post_status = 'publish'
	   AND (
	     NOT EXISTS (
	       SELECT 1 FROM {$wpdb->postmeta} pm
	       WHERE pm.post_id = p.ID
	         AND pm.meta_key = '_cam_survey_expires'
	         AND pm.meta_value != ''
	         AND pm.meta_value < NOW()
	     )
	   )"
);

// -------------------------------------------------------------------------
// Latest reports
// -------------------------------------------------------------------------

$latest_reports = $wpdb->get_results(
	"SELECT id, public_code, category, status, created_at
	 FROM {$wpdb->prefix}cam_segnalazioni
	 ORDER BY created_at DESC
	 LIMIT 5",
	ARRAY_A
);

// -------------------------------------------------------------------------
// Latest push logs
// -------------------------------------------------------------------------

$latest_push = $wpdb->get_results(
	"SELECT title, target_type, success_count, failure_count, sent_at
	 FROM {$wpdb->prefix}cam_push_logs
	 ORDER BY sent_at DESC
	 LIMIT 5",
	ARRAY_A
);

// -------------------------------------------------------------------------
// Firebase configured?
// -------------------------------------------------------------------------

$firebase      = new Comune_App_Manager_Firebase();
$fb_configured = $firebase->is_configured();

// -------------------------------------------------------------------------
// Status label helper
// -------------------------------------------------------------------------

$status_labels = array(
	'pending'     => __( 'In attesa', 'comune-app-manager' ),
	'in_progress' => __( 'In lavorazione', 'comune-app-manager' ),
	'resolved'    => __( 'Risolta', 'comune-app-manager' ),
	'rejected'    => __( 'Rifiutata', 'comune-app-manager' ),
);
?>

<div class="wrap cam-wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-smartphone" style="vertical-align:middle;font-size:2rem;height:2rem;width:2rem;margin-right:6px;"></span>
		<?php esc_html_e( 'App Comunale — Dashboard', 'comune-app-manager' ); ?>
	</h1>
	<hr class="wp-header-end">

	<!-- ===== STAT CARDS ===== -->
	<div class="cam-dashboard-stats">

		<div class="cam-stat-card">
			<div class="count"><?php echo esc_html( number_format_i18n( $avvisi_count ) ); ?></div>
			<div class="label"><?php esc_html_e( 'Avvisi pubblicati', 'comune-app-manager' ); ?></div>
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=comune_avviso' ) ); ?>" class="cam-card-link">
				<?php esc_html_e( 'Vedi tutti', 'comune-app-manager' ); ?> &rarr;
			</a>
		</div>

		<div class="cam-stat-card cam-stat-card--orange">
			<div class="count"><?php echo esc_html( number_format_i18n( $reports_open_count ) ); ?></div>
			<div class="label"><?php esc_html_e( 'Segnalazioni aperte', 'comune-app-manager' ); ?></div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=cam-reports' ) ); ?>" class="cam-card-link">
				<?php esc_html_e( 'Vedi tutte', 'comune-app-manager' ); ?> &rarr;
			</a>
		</div>

		<div class="cam-stat-card cam-stat-card--green">
			<div class="count"><?php echo esc_html( number_format_i18n( $tokens_count ) ); ?></div>
			<div class="label"><?php esc_html_e( 'Dispositivi attivi', 'comune-app-manager' ); ?></div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=cam-push' ) ); ?>" class="cam-card-link">
				<?php esc_html_e( 'Gestisci', 'comune-app-manager' ); ?> &rarr;
			</a>
		</div>

		<div class="cam-stat-card cam-stat-card--purple">
			<div class="count"><?php echo esc_html( number_format_i18n( $surveys_active_count ) ); ?></div>
			<div class="label"><?php esc_html_e( 'Sondaggi attivi', 'comune-app-manager' ); ?></div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=cam-surveys' ) ); ?>" class="cam-card-link">
				<?php esc_html_e( 'Vedi tutti', 'comune-app-manager' ); ?> &rarr;
			</a>
		</div>

	</div><!-- .cam-dashboard-stats -->

	<div class="cam-dashboard-lower">

		<!-- ===== ULTIME SEGNALAZIONI ===== -->
		<div class="cam-dashboard-panel">
			<h2><?php esc_html_e( 'Ultime 5 Segnalazioni', 'comune-app-manager' ); ?></h2>
			<div class="cam-table-wrap">
				<?php if ( ! empty( $latest_reports ) ) : ?>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Codice', 'comune-app-manager' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Categoria', 'comune-app-manager' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Stato', 'comune-app-manager' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Data', 'comune-app-manager' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $latest_reports as $report ) : ?>
								<tr>
									<td>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=cam-reports&action=detail&id=' . absint( $report['id'] ) ) ); ?>">
											<?php echo esc_html( $report['public_code'] ); ?>
										</a>
									</td>
									<td><?php echo esc_html( $report['category'] ); ?></td>
									<td>
										<span class="cam-status-badge status-<?php echo esc_attr( $report['status'] ); ?>">
											<?php echo esc_html( $status_labels[ $report['status'] ] ?? ucfirst( $report['status'] ) ); ?>
										</span>
									</td>
									<td>
										<?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $report['created_at'] ) ) ); ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p class="cam-empty-state"><?php esc_html_e( 'Nessuna segnalazione trovata.', 'comune-app-manager' ); ?></p>
				<?php endif; ?>
			</div>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=cam-reports' ) ); ?>">
					<?php esc_html_e( 'Tutte le segnalazioni &rarr;', 'comune-app-manager' ); ?>
				</a>
			</p>
		</div>

		<!-- ===== ULTIME NOTIFICHE ===== -->
		<div class="cam-dashboard-panel">
			<h2><?php esc_html_e( 'Ultime 5 notifiche inviate', 'comune-app-manager' ); ?></h2>
			<div class="cam-table-wrap">
				<?php if ( ! empty( $latest_push ) ) : ?>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Titolo', 'comune-app-manager' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Target', 'comune-app-manager' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Inviati', 'comune-app-manager' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Data', 'comune-app-manager' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $latest_push as $push ) : ?>
								<tr>
									<td><?php echo esc_html( $push['title'] ); ?></td>
									<td><?php echo esc_html( $push['target_type'] ); ?></td>
									<td><?php echo esc_html( number_format_i18n( (int) $push['success_count'] ) ); ?></td>
									<td><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $push['sent_at'] ) ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p class="cam-empty-state"><?php esc_html_e( 'Nessuna notifica inviata.', 'comune-app-manager' ); ?></p>
				<?php endif; ?>
			</div>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=cam-push' ) ); ?>">
					<?php esc_html_e( 'Tutti i log notifiche &rarr;', 'comune-app-manager' ); ?>
				</a>
			</p>
		</div>

	</div><!-- .cam-dashboard-lower -->

	<div class="cam-dashboard-sidebar">

		<!-- ===== STATO FIREBASE ===== -->
		<div class="cam-dashboard-panel">
			<h2><?php esc_html_e( 'Stato Firebase', 'comune-app-manager' ); ?></h2>
			<?php if ( $fb_configured ) : ?>
				<div class="cam-firebase-status cam-firebase-status--ok">
					<span class="dashicons dashicons-yes-alt"></span>
					<span><?php esc_html_e( 'Firebase configurato correttamente.', 'comune-app-manager' ); ?></span>
				</div>
				<p>
					<button type="button" id="cam-test-firebase" class="button button-secondary">
						<?php esc_html_e( 'Testa connessione', 'comune-app-manager' ); ?>
					</button>
					<span id="cam-firebase-test-result" class="cam-inline-result" style="display:none;margin-left:8px;"></span>
				</p>
			<?php else : ?>
				<div class="cam-firebase-status cam-firebase-status--error">
					<span class="dashicons dashicons-dismiss"></span>
					<span><?php esc_html_e( 'Firebase non configurato.', 'comune-app-manager' ); ?></span>
				</div>
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=cam-settings&tab=firebase' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Configura Firebase', 'comune-app-manager' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>

		<!-- ===== AZIONI RAPIDE ===== -->
		<div class="cam-dashboard-panel">
			<h2><?php esc_html_e( 'Azioni rapide', 'comune-app-manager' ); ?></h2>
			<div class="cam-quick-actions">
				<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=comune_avviso' ) ); ?>" class="button button-primary">
					<span class="dashicons dashicons-plus-alt" style="vertical-align:middle;"></span>
					<?php esc_html_e( 'Aggiungi Avviso', 'comune-app-manager' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=cam-push' ) ); ?>" class="button button-secondary">
					<span class="dashicons dashicons-bell" style="vertical-align:middle;"></span>
					<?php esc_html_e( 'Invia Notifica', 'comune-app-manager' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=cam-waste' ) ); ?>" class="button button-secondary">
					<span class="dashicons dashicons-calendar-alt" style="vertical-align:middle;"></span>
					<?php esc_html_e( 'Calendario Rifiuti', 'comune-app-manager' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=cam-surveys' ) ); ?>" class="button button-secondary">
					<span class="dashicons dashicons-forms" style="vertical-align:middle;"></span>
					<?php esc_html_e( 'Nuovo Sondaggio', 'comune-app-manager' ); ?>
				</a>
			</div>
		</div>

		<!-- ===== DATI DEMO ===== -->
		<?php if ( current_user_can( 'manage_options' ) ) : ?>
		<div class="cam-dashboard-panel" style="border-top:3px solid #f0b849;">
			<h2>
				<span class="dashicons dashicons-database-import" style="vertical-align:middle;font-size:1.2em;height:1.2em;width:1.2em;"></span>
				<?php esc_html_e( 'Dati dimostrativi', 'comune-app-manager' ); ?>
			</h2>
			<p style="color:#666;font-size:13px;">
				<?php esc_html_e( 'Carica contenuti dimostrativi per Comune di Acerno: avvisi, eventi, uffici, luoghi, documenti, sondaggi e calendario rifiuti. L\'operazione è idempotente (gli elementi già presenti vengono saltati).', 'comune-app-manager' ); ?>
			</p>
			<p>
				<button type="button" id="cam-run-seed" class="button button-secondary">
					<span class="dashicons dashicons-upload" style="vertical-align:middle;"></span>
					<?php esc_html_e( 'Carica dati demo', 'comune-app-manager' ); ?>
				</button>
				<span class="spinner" id="cam-seed-spinner" style="float:none;vertical-align:middle;margin-left:4px;"></span>
			</p>
			<div id="cam-seed-result" style="display:none;margin-top:8px;"></div>
		</div>

		<script>
		(function($){
			$('#cam-run-seed').on('click', function(){
				var $btn     = $(this);
				var $spinner = $('#cam-seed-spinner');
				var $result  = $('#cam-seed-result');

				$btn.prop('disabled', true);
				$spinner.addClass('is-active');
				$result.hide();

				$.ajax({
					url:    (window.camAdmin && camAdmin.ajaxUrl) || ajaxurl,
					method: 'POST',
					data:   {
						action: 'cam_run_demo_seed',
						nonce:  (window.camAdmin && camAdmin.nonce) || '',
					},
					success: function(res){
						$btn.prop('disabled', false);
						$spinner.removeClass('is-active');
						if(res.success){
							var html = '<p style="color:#1a7a1a;font-weight:600;">' + res.data.summary + '</p>';
							html += '<details style="margin-top:6px;"><summary style="cursor:pointer;color:#555;font-size:12px;"><?php echo esc_js( __( 'Mostra log dettagliato', 'comune-app-manager' ) ); ?></summary>';
							html += '<ul style="max-height:240px;overflow-y:auto;font-size:12px;margin-top:6px;">';
							$.each(res.data.log, function(i, entry){
								var icon = entry.type === 'created' ? '✅' : (entry.type === 'error' ? '❌' : '⏭️');
								html += '<li>' + icon + ' ' + $('<span>').text(entry.message).html() + '</li>';
							});
							html += '</ul></details>';
							$result.html(html).show();
						} else {
							$result.html('<p style="color:#c00;">' + (res.data && res.data.message ? res.data.message : '<?php echo esc_js( __( 'Errore sconosciuto.', 'comune-app-manager' ) ); ?>') + '</p>').show();
						}
					},
					error: function(){
						$btn.prop('disabled', false);
						$spinner.removeClass('is-active');
						$result.html('<p style="color:#c00;"><?php echo esc_js( __( 'Errore di connessione.', 'comune-app-manager' ) ); ?></p>').show();
					},
				});
			});
		}(jQuery));
		</script>
		<?php endif; ?>

	</div><!-- .cam-dashboard-sidebar -->

</div><!-- .wrap.cam-wrap -->
