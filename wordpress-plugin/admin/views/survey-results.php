<?php
/**
 * Admin view: Survey results.
 *
 * @package Comune_App_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! Comune_App_Manager_Permissions::can_manage_surveys() ) {
	wp_die( esc_html__( 'Non hai i permessi per accedere a questa pagina.', 'comune-app-manager' ) );
}

global $wpdb;

$survey_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

if ( ! $survey_id ) {
	wp_die( esc_html__( 'ID sondaggio non valido.', 'comune-app-manager' ) );
}

$surveys_table   = $wpdb->prefix . 'comune_app_surveys';
$questions_table = $wpdb->prefix . 'comune_app_survey_questions';
$answers_table   = $wpdb->prefix . 'comune_app_survey_answers';

// -------------------------------------------------------------------------
// Load survey
// -------------------------------------------------------------------------
$survey = $wpdb->get_row(
	$wpdb->prepare( "SELECT * FROM {$surveys_table} WHERE id = %d LIMIT 1", $survey_id ),
	ARRAY_A
);

if ( ! $survey ) {
	wp_die( esc_html__( 'Sondaggio non trovato.', 'comune-app-manager' ) );
}

// -------------------------------------------------------------------------
// CSV export: raw answers
// -------------------------------------------------------------------------
if ( isset( $_GET['export'] ) && 'csv' === $_GET['export'] && isset( $_GET['_wpnonce'] ) ) {
	if ( wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'cam_export_survey_' . $survey_id ) ) {
		$export_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.respondent_uuid, q.question_text, a.answer_value, a.created_at
				 FROM {$answers_table} a
				 INNER JOIN {$questions_table} q ON q.id = a.question_id
				 WHERE a.survey_id = %d
				 ORDER BY a.respondent_uuid, q.sort_order ASC",
				$survey_id
			),
			ARRAY_A
		);

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="sondaggio-' . $survey_id . '-risposte.csv"' );
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, [ 'respondent_uuid', 'question', 'answer', 'date' ] );
		foreach ( $export_rows as $er ) {
			fputcsv( $out, $er );
		}
		fclose( $out );
		exit;
	}
}

// -------------------------------------------------------------------------
// Load questions
// -------------------------------------------------------------------------
$questions = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT * FROM {$questions_table} WHERE survey_id = %d ORDER BY sort_order ASC",
		$survey_id
	),
	ARRAY_A
);

// -------------------------------------------------------------------------
// Total respondents (distinct UUIDs)
// -------------------------------------------------------------------------
$total_respondents = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(DISTINCT respondent_uuid) FROM {$answers_table} WHERE survey_id = %d",
		$survey_id
	)
);

// -------------------------------------------------------------------------
// Status label
// -------------------------------------------------------------------------
$status_map = [
	'draft'     => __( 'Bozza', 'comune-app-manager' ),
	'published' => __( 'Pubblicato', 'comune-app-manager' ),
	'closed'    => __( 'Chiuso', 'comune-app-manager' ),
];

$status_badge_class = [
	'draft'     => 'status-ricevuta',
	'published' => 'status-chiusa',
	'closed'    => 'status-non_di_competenza',
];

$export_url = wp_nonce_url(
	add_query_arg( [ 'page' => 'cam-surveys', 'action' => 'results', 'id' => $survey_id, 'export' => 'csv' ], admin_url( 'admin.php' ) ),
	'cam_export_survey_' . $survey_id
);
?>

<div class="wrap cam-wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-chart-bar" style="vertical-align:middle;font-size:1.8rem;height:1.8rem;width:1.8rem;margin-right:6px;"></span>
		<?php esc_html_e( 'Risultati sondaggio', 'comune-app-manager' ); ?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=cam-surveys' ) ); ?>" class="page-title-action">
		&larr; <?php esc_html_e( 'Torna ai sondaggi', 'comune-app-manager' ); ?>
	</a>
	<hr class="wp-header-end">

	<!-- ===== SURVEY HEADER ===== -->
	<div class="cam-meta-box">
		<div class="cam-survey-header">
			<div>
				<h2 style="margin:0 0 8px;"><?php echo esc_html( $survey['title'] ); ?></h2>
				<?php if ( ! empty( $survey['description'] ) ) : ?>
					<p style="margin:0 0 8px;color:#666;"><?php echo esc_html( $survey['description'] ); ?></p>
				<?php endif; ?>
				<div class="cam-survey-meta">
					<span class="cam-status-badge <?php echo esc_attr( $status_badge_class[ $survey['status'] ] ?? 'status-non_di_competenza' ); ?>">
						<?php echo esc_html( $status_map[ $survey['status'] ] ?? $survey['status'] ); ?>
					</span>

					<?php if ( ! empty( $survey['start_date'] ) ) : ?>
						<span class="cam-meta-item">
							<span class="dashicons dashicons-calendar-alt" style="vertical-align:middle;"></span>
							<?php
							echo esc_html(
								wp_date( get_option( 'date_format' ), strtotime( $survey['start_date'] ) )
							);
							if ( ! empty( $survey['end_date'] ) ) {
								echo ' &rarr; ' . esc_html( wp_date( get_option( 'date_format' ), strtotime( $survey['end_date'] ) ) );
							}
							?>
						</span>
					<?php endif; ?>

					<span class="cam-meta-item">
						<span class="dashicons dashicons-groups" style="vertical-align:middle;"></span>
						<?php
						printf(
							/* translators: %d = respondent count */
							esc_html( _n( '%d partecipante', '%d partecipanti', $total_respondents, 'comune-app-manager' ) ),
							esc_html( number_format_i18n( $total_respondents ) )
						);
						?>
					</span>
				</div>
			</div>

			<div>
				<a href="<?php echo esc_url( $export_url ); ?>" class="button button-secondary">
					<span class="dashicons dashicons-download" style="vertical-align:middle;"></span>
					<?php esc_html_e( 'Esporta CSV risposte', 'comune-app-manager' ); ?>
				</a>
			</div>
		</div>
	</div>

	<!-- ===== QUESTIONS RESULTS ===== -->
	<?php if ( empty( $questions ) ) : ?>
		<p class="cam-empty-state"><?php esc_html_e( 'Nessuna domanda trovata per questo sondaggio.', 'comune-app-manager' ); ?></p>
	<?php endif; ?>

	<?php foreach ( $questions as $idx => $question ) : ?>
		<?php
		$q_id   = (int) $question['id'];
		$q_type = $question['question_type'];
		$q_num  = $idx + 1;

		// Fetch all answers for this question.
		$all_answers = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT answer_value, COUNT(*) as cnt
				 FROM {$answers_table}
				 WHERE question_id = %d
				 GROUP BY answer_value
				 ORDER BY cnt DESC",
				$q_id
			),
			ARRAY_A
		);

		$total_answers = array_sum( array_column( $all_answers, 'cnt' ) );
		?>
		<div class="cam-meta-box cam-survey-question-result" style="margin-top:16px;">
			<h3 style="margin-top:0;">
				<span class="cam-question-num"><?php echo esc_html( $q_num ); ?>.</span>
				<?php echo esc_html( $question['question_text'] ); ?>
				<small class="cam-question-type" style="font-weight:400;color:#888;margin-left:8px;">
					(<?php echo esc_html( $q_type ); ?>)
				</small>
			</h3>

			<?php if ( 0 === $total_answers ) : ?>
				<p class="cam-empty-state" style="font-style:italic;"><?php esc_html_e( 'Nessuna risposta ricevuta.', 'comune-app-manager' ); ?></p>

			<?php elseif ( in_array( $q_type, [ 'scelta_singola', 'scelta_multipla' ], true ) ) : ?>
				<!-- BAR CHART for single/multiple choice -->
				<div class="cam-bar-chart">
					<?php foreach ( $all_answers as $ans ) : ?>
						<?php
						$pct = $total_answers > 0 ? round( ( $ans['cnt'] / $total_answers ) * 100, 1 ) : 0;
						?>
						<div class="cam-bar-row">
							<span class="cam-bar-label"><?php echo esc_html( $ans['answer_value'] ); ?></span>
							<div class="cam-bar-track">
								<div
									class="cam-bar-fill"
									data-width="<?php echo esc_attr( $pct ); ?>"
									style="width:0%;"
								></div>
							</div>
							<span class="cam-bar-stats">
								<?php echo esc_html( $pct ); ?>%
								<small>(<?php echo esc_html( number_format_i18n( (int) $ans['cnt'] ) ); ?>)</small>
							</span>
						</div>
					<?php endforeach; ?>
				</div>
				<p class="cam-total-responses">
					<?php
					printf(
						/* translators: %d = total answer count */
						esc_html( _n( 'Totale: %d risposta', 'Totale: %d risposte', $total_answers, 'comune-app-manager' ) ),
						esc_html( number_format_i18n( $total_answers ) )
					);
					?>
				</p>

			<?php elseif ( 'scala_1_5' === $q_type ) : ?>
				<!-- SCALE 1–5 -->
				<?php
				$sum = 0;
				$distribution = [ 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0 ];
				foreach ( $all_answers as $ans ) {
					$v = (int) $ans['answer_value'];
					if ( $v >= 1 && $v <= 5 ) {
						$distribution[ $v ] += (int) $ans['cnt'];
						$sum += $v * (int) $ans['cnt'];
					}
				}
				$avg = $total_answers > 0 ? round( $sum / $total_answers, 2 ) : 0;
				?>
				<div class="cam-scale-summary">
					<div class="cam-scale-avg">
						<span class="cam-scale-avg-value"><?php echo esc_html( number_format_i18n( $avg, 2 ) ); ?></span>
						<span class="cam-scale-avg-label"><?php esc_html_e( '/ 5 media', 'comune-app-manager' ); ?></span>
					</div>
				</div>
				<div class="cam-bar-chart cam-bar-chart--scale">
					<?php for ( $s = 1; $s <= 5; $s++ ) : ?>
						<?php
						$cnt = $distribution[ $s ];
						$pct = $total_answers > 0 ? round( ( $cnt / $total_answers ) * 100, 1 ) : 0;
						$stars = str_repeat( '★', $s ) . str_repeat( '☆', 5 - $s );
						?>
						<div class="cam-bar-row">
							<span class="cam-bar-label cam-star-label" title="<?php echo esc_attr( (string) $s ); ?>"><?php echo esc_html( $stars ); ?></span>
							<div class="cam-bar-track">
								<div
									class="cam-bar-fill cam-bar-fill--yellow"
									data-width="<?php echo esc_attr( $pct ); ?>"
									style="width:0%;"
								></div>
							</div>
							<span class="cam-bar-stats">
								<?php echo esc_html( $pct ); ?>%
								<small>(<?php echo esc_html( number_format_i18n( $cnt ) ); ?>)</small>
							</span>
						</div>
					<?php endfor; ?>
				</div>

			<?php elseif ( 'testo_libero' === $q_type ) : ?>
				<!-- FREE TEXT: last 10 answers -->
				<?php
				$text_answers = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT answer_value, created_at
						 FROM {$answers_table}
						 WHERE question_id = %d AND answer_value != ''
						 ORDER BY created_at DESC
						 LIMIT 10",
						$q_id
					),
					ARRAY_A
				);
				?>
				<ul class="cam-text-answers">
					<?php foreach ( $text_answers as $ta ) : ?>
						<li class="cam-text-answer-item">
							<blockquote><?php echo esc_html( $ta['answer_value'] ); ?></blockquote>
							<small class="cam-text-answer-date">
								<?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $ta['created_at'] ) ) ); ?>
							</small>
						</li>
					<?php endforeach; ?>
				</ul>
				<?php if ( $total_answers > 10 ) : ?>
					<p class="description">
						<?php
						printf(
							/* translators: 1: shown 2: total */
							esc_html__( 'Mostrate le ultime 10 di %d risposte totali. Esporta CSV per tutte.', 'comune-app-manager' ),
							esc_html( number_format_i18n( $total_answers ) )
						);
						?>
					</p>
				<?php endif; ?>

			<?php else : ?>
				<!-- Fallback: simple list -->
				<div class="cam-bar-chart">
					<?php foreach ( $all_answers as $ans ) : ?>
						<?php $pct = $total_answers > 0 ? round( ( $ans['cnt'] / $total_answers ) * 100, 1 ) : 0; ?>
						<div class="cam-bar-row">
							<span class="cam-bar-label"><?php echo esc_html( $ans['answer_value'] ); ?></span>
							<div class="cam-bar-track">
								<div class="cam-bar-fill" data-width="<?php echo esc_attr( $pct ); ?>" style="width:0%;"></div>
							</div>
							<span class="cam-bar-stats"><?php echo esc_html( $pct ); ?>% (<?php echo esc_html( $ans['cnt'] ); ?>)</span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

		</div>
	<?php endforeach; ?>

</div><!-- .wrap.cam-wrap -->
