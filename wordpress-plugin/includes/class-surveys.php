<?php
/**
 * Surveys management.
 *
 * @package ComuneAppManager
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comune_App_Manager_Surveys
 *
 * Manages surveys (sondaggi): registers the cam_sondaggio custom post type,
 * provides admin UI for results, and handles AJAX survey submissions.
 */
class Comune_App_Manager_Surveys {

	/**
	 * Constructor: register hooks.
	 */
	public function __construct() {
		add_action( 'init',                      array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes',            array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_cam_sondaggio',   array( $this, 'save_meta' ) );
		add_action( 'cam_render_surveys_page',   array( $this, 'render_page' ) );
		add_action( 'wp_ajax_cam_survey_results', array( $this, 'ajax_get_results' ) );
	}

	/**
	 * Register the cam_sondaggio custom post type.
	 */
	public function register_post_type(): void {
		register_post_type(
			'cam_sondaggio',
			array(
				'labels'         => array(
					'name'          => _x( 'Sondaggi', 'post type general name', 'comune-app-manager' ),
					'singular_name' => _x( 'Sondaggio', 'post type singular name', 'comune-app-manager' ),
					'add_new_item'  => __( 'Nuovo sondaggio', 'comune-app-manager' ),
					'edit_item'     => __( 'Modifica sondaggio', 'comune-app-manager' ),
				),
				'public'         => false,
				'show_ui'        => true,
				'show_in_menu'   => false,
				'show_in_rest'   => true,
				'supports'       => array( 'title', 'editor' ),
				'capability_type'=> 'post',
				'map_meta_cap'   => true,
			)
		);
	}

	/**
	 * Add meta boxes to the sondaggio edit screen.
	 */
	public function add_meta_boxes(): void {
		add_meta_box(
			'cam_survey_questions',
			__( 'Domande del sondaggio', 'comune-app-manager' ),
			array( $this, 'render_questions_meta_box' ),
			'cam_sondaggio',
			'normal',
			'high'
		);

		add_meta_box(
			'cam_survey_settings',
			__( 'Impostazioni sondaggio', 'comune-app-manager' ),
			array( $this, 'render_settings_meta_box' ),
			'cam_sondaggio',
			'side',
			'default'
		);
	}

	/**
	 * Render the questions meta box.
	 *
	 * @param WP_Post $post Current post.
	 */
	public function render_questions_meta_box( WP_Post $post ): void {
		wp_nonce_field( 'cam_survey_save_' . $post->ID, 'cam_survey_nonce' );
		$questions = json_decode( get_post_meta( $post->ID, '_cam_survey_questions', true ) ?: '[]', true );
		?>
		<p><?php esc_html_e( 'Inserisci le domande in formato JSON. Ogni domanda deve avere: id, type (single|multiple|text), text, e options (array) per i tipi a scelta.', 'comune-app-manager' ); ?></p>
		<textarea name="cam_survey_questions" rows="15" class="large-text" style="font-family:monospace"><?php echo esc_textarea( wp_json_encode( $questions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ); ?></textarea>
		<p class="description"><?php esc_html_e( 'Esempio: [{"id":"q1","type":"single","text":"Come valuti il servizio?","options":["Ottimo","Buono","Scarso"]}]', 'comune-app-manager' ); ?></p>
		<?php
	}

	/**
	 * Render the settings meta box.
	 *
	 * @param WP_Post $post Current post.
	 */
	public function render_settings_meta_box( WP_Post $post ): void {
		$expires    = get_post_meta( $post->ID, '_cam_survey_expires', true );
		$allow_anon = get_post_meta( $post->ID, '_cam_survey_allow_anonymous', true );
		$max_resp   = get_post_meta( $post->ID, '_cam_survey_max_responses', true );
		?>
		<p>
			<label for="cam_survey_expires"><?php esc_html_e( 'Data scadenza', 'comune-app-manager' ); ?></label><br />
			<input type="date" id="cam_survey_expires" name="cam_survey_expires"
			       value="<?php echo esc_attr( $expires ); ?>" class="widefat" />
		</p>
		<p>
			<label>
				<input type="checkbox" name="cam_survey_allow_anonymous" value="1" <?php checked( $allow_anon, '1' ); ?> />
				<?php esc_html_e( 'Consenti risposte anonime', 'comune-app-manager' ); ?>
			</label>
		</p>
		<p>
			<label for="cam_survey_max_responses"><?php esc_html_e( 'Max risposte (0 = illimitate)', 'comune-app-manager' ); ?></label><br />
			<input type="number" id="cam_survey_max_responses" name="cam_survey_max_responses"
			       value="<?php echo esc_attr( $max_resp ?: '0' ); ?>" class="widefat" min="0" />
		</p>
		<?php
	}

	/**
	 * Save survey meta on post save.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_meta( int $post_id ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['cam_survey_nonce'] ) ||
		     ! wp_verify_nonce( sanitize_key( $_POST['cam_survey_nonce'] ), 'cam_survey_save_' . $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_comune_surveys' ) ) {
			return;
		}

		// Validate and store questions JSON.
		$raw_json  = wp_unslash( $_POST['cam_survey_questions'] ?? '[]' );
		$questions = json_decode( $raw_json, true );

		if ( ! is_array( $questions ) ) {
			$questions = array();
		}

		// Re-encode to ensure it is clean.
		update_post_meta( $post_id, '_cam_survey_questions', wp_json_encode( $questions, JSON_UNESCAPED_UNICODE ) );
		update_post_meta( $post_id, '_cam_survey_expires',           sanitize_text_field( wp_unslash( $_POST['cam_survey_expires'] ?? '' ) ) );
		update_post_meta( $post_id, '_cam_survey_allow_anonymous',   isset( $_POST['cam_survey_allow_anonymous'] ) ? '1' : '0' );
		update_post_meta( $post_id, '_cam_survey_max_responses',     absint( $_POST['cam_survey_max_responses'] ?? 0 ) );
	}

	/**
	 * Handle AJAX survey submission (public endpoint, no auth required).
	 * Action: cam_submit_survey
	 */
	public function handle_submission(): void {
		// Nonce is optional for public app submissions; we rely on rate limiting instead.
		$survey_id = absint( $_POST['survey_id'] ?? 0 );
		$ip        = Comune_App_Manager_Utils::get_client_ip();

		if ( ! Comune_App_Manager_Utils::check_rate_limit( 'cam_survey_' . $ip, 5, 3600 ) ) {
			wp_send_json_error( array( 'message' => __( 'Troppe richieste. Riprova più tardi.', 'comune-app-manager' ) ) );
		}

		$post = get_post( $survey_id );
		if ( ! $post || 'cam_sondaggio' !== $post->post_type || 'publish' !== $post->post_status ) {
			wp_send_json_error( array( 'message' => __( 'Sondaggio non trovato.', 'comune-app-manager' ) ) );
		}

		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'cam_survey_responses',
			array(
				'survey_id'  => $survey_id,
				'ip_address' => $ip,
				'started_at' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s' )
		);
		$resp_id = $wpdb->insert_id;

		$answers = (array) ( $_POST['answers'] ?? array() );
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

		wp_send_json_success( array( 'message' => __( 'Grazie per aver partecipato!', 'comune-app-manager' ) ) );
	}

	/**
	 * Render the surveys admin overview page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_comune_surveys' ) ) {
			return;
		}

		$surveys = get_posts( array(
			'post_type'      => 'cam_sondaggio',
			'post_status'    => array( 'publish', 'draft' ),
			'posts_per_page' => 50,
		) );

		global $wpdb;
		?>
		<div style="margin-bottom:16px">
			<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=cam_sondaggio' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Nuovo sondaggio', 'comune-app-manager' ); ?>
			</a>
		</div>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Titolo', 'comune-app-manager' ); ?></th>
					<th><?php esc_html_e( 'Stato', 'comune-app-manager' ); ?></th>
					<th><?php esc_html_e( 'Risposte', 'comune-app-manager' ); ?></th>
					<th><?php esc_html_e( 'Scadenza', 'comune-app-manager' ); ?></th>
					<th><?php esc_html_e( 'Azioni', 'comune-app-manager' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php if ( empty( $surveys ) ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'Nessun sondaggio.', 'comune-app-manager' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $surveys as $survey ) :
					$total_resp = (int) $wpdb->get_var( $wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->prefix}cam_survey_responses WHERE survey_id = %d AND is_complete = 1",
						$survey->ID
					) );
					$expires = get_post_meta( $survey->ID, '_cam_survey_expires', true );
				?>
				<tr>
					<td>
						<a href="<?php echo esc_url( get_edit_post_link( $survey->ID ) ); ?>">
							<?php echo esc_html( $survey->post_title ); ?>
						</a>
					</td>
					<td><?php echo esc_html( 'publish' === $survey->post_status ? __( 'Pubblicato', 'comune-app-manager' ) : __( 'Bozza', 'comune-app-manager' ) ); ?></td>
					<td><?php echo esc_html( number_format_i18n( $total_resp ) ); ?></td>
					<td><?php echo $expires ? esc_html( wp_date( get_option( 'date_format' ), strtotime( $expires ) ) ) : '—'; ?></td>
					<td>
						<a href="<?php echo esc_url( get_edit_post_link( $survey->ID ) ); ?>" class="button button-small">
							<?php esc_html_e( 'Modifica', 'comune-app-manager' ); ?>
						</a>
						<button type="button" class="button button-small cam-view-results"
						        data-id="<?php echo esc_attr( $survey->ID ); ?>">
							<?php esc_html_e( 'Risultati', 'comune-app-manager' ); ?>
						</button>
					</td>
				</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>

		<div id="cam-results-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);z-index:9999">
			<div style="background:#fff;margin:50px auto;max-width:800px;padding:24px;border-radius:4px;max-height:80vh;overflow-y:auto">
				<button id="cam-close-results" class="button" style="float:right"><?php esc_html_e( 'Chiudi', 'comune-app-manager' ); ?></button>
				<h2 id="cam-results-title"></h2>
				<div id="cam-results-content"></div>
			</div>
		</div>

		<script>
		jQuery(function($){
			var nonce = '<?php echo esc_js( wp_create_nonce( 'cam_survey_results' ) ); ?>';

			$(document).on('click', '.cam-view-results', function(){
				var id = $(this).data('id');
				$.post(ajaxurl, { action:'cam_survey_results', id:id, nonce:nonce }, function(res){
					if(res.success){
						$('#cam-results-title').text(res.data.title);
						var html = '<p><?php echo esc_js( __( 'Risposte totali:', 'comune-app-manager' ) ); ?> <strong>'+res.data.total+'</strong></p>';
						$.each(res.data.questions, function(i, q){
							html += '<h3>'+q.text+'</h3>';
							if(q.type === 'text'){
								html += '<ul>';
								$.each(q.answers, function(j,a){ html += '<li>'+a+'</li>'; });
								html += '</ul>';
							} else {
								$.each(q.options, function(opt, count){
									var pct = res.data.total > 0 ? Math.round((count/res.data.total)*100) : 0;
									html += '<div style="margin:4px 0">'+opt+': <strong>'+count+'</strong> ('+pct+'%)</div>';
									html += '<div style="background:#eee;height:8px;border-radius:4px"><div style="background:#1a5276;height:8px;width:'+pct+'%;border-radius:4px"></div></div>';
								});
							}
						});
						$('#cam-results-content').html(html);
						$('#cam-results-modal').show();
					}
				});
			});

			$('#cam-close-results').on('click', function(){ $('#cam-results-modal').hide(); });
		});
		</script>
		<?php
	}

	/**
	 * Handle AJAX: return aggregated survey results for the admin.
	 */
	public function ajax_get_results(): void {
		check_ajax_referer( 'cam_survey_results', 'nonce' );

		if ( ! current_user_can( 'manage_comune_surveys' ) ) {
			wp_send_json_error();
		}

		$survey_id = absint( $_POST['id'] ?? 0 );
		$post      = get_post( $survey_id );

		if ( ! $post || 'cam_sondaggio' !== $post->post_type ) {
			wp_send_json_error();
		}

		global $wpdb;

		$total = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}cam_survey_responses WHERE survey_id = %d AND is_complete = 1",
			$survey_id
		) );

		$questions = json_decode( get_post_meta( $survey_id, '_cam_survey_questions', true ) ?: '[]', true );
		$result_questions = array();

		foreach ( $questions as $q ) {
			$q_id   = $q['id'];
			$q_type = $q['type'];
			$q_text = $q['text'];

			$raw_answers = $wpdb->get_col( $wpdb->prepare(
				"SELECT a.answer_value FROM {$wpdb->prefix}cam_survey_answers a
				 INNER JOIN {$wpdb->prefix}cam_survey_responses r ON r.id = a.response_id
				 WHERE a.survey_id = %d AND a.question_id = %s AND r.is_complete = 1",
				$survey_id, $q_id
			) );

			if ( 'text' === $q_type ) {
				$decoded = array_map( static function( string $v ): string {
					$d = json_decode( $v, true );
					return is_string( $d ) ? $d : $v;
				}, $raw_answers );

				$result_questions[] = array( 'text' => $q_text, 'type' => 'text', 'answers' => array_slice( $decoded, 0, 50 ) );
			} else {
				$counts = array();
				foreach ( $q['options'] ?? array() as $opt ) {
					$counts[ $opt ] = 0;
				}

				foreach ( $raw_answers as $raw ) {
					$val = json_decode( $raw, true );
					if ( is_array( $val ) ) {
						foreach ( $val as $chosen ) {
							if ( isset( $counts[ $chosen ] ) ) {
								$counts[ $chosen ]++;
							}
						}
					} elseif ( is_string( $val ) && isset( $counts[ $val ] ) ) {
						$counts[ $val ]++;
					}
				}

				$result_questions[] = array( 'text' => $q_text, 'type' => $q_type, 'options' => $counts );
			}
		}

		wp_send_json_success( array(
			'title'     => $post->post_title,
			'total'     => $total,
			'questions' => $result_questions,
		) );
	}
}
