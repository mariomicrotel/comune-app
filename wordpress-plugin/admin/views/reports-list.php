<?php
/**
 * Admin view: Segnalazioni list.
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

$table = $wpdb->prefix . 'comune_app_reports';

// -------------------------------------------------------------------------
// Filter parameters
// -------------------------------------------------------------------------
$filter_status   = isset( $_GET['filter_status'] )   ? sanitize_key( $_GET['filter_status'] )   : '';
$filter_category = isset( $_GET['filter_category'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_category'] ) ) : '';
$search          = isset( $_GET['search'] )          ? sanitize_text_field( wp_unslash( $_GET['search'] ) )          : '';
$paged           = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 );
$per_page        = 20;
$offset          = ( $paged - 1 ) * $per_page;

// -------------------------------------------------------------------------
// Build WHERE clause
// -------------------------------------------------------------------------
$where_clauses = [ '1=1' ];
$where_values  = [];

if ( ! empty( $filter_status ) ) {
	$where_clauses[] = 'status = %s';
	$where_values[]  = $filter_status;
}

if ( ! empty( $filter_category ) ) {
	$where_clauses[] = 'category = %s';
	$where_values[]  = $filter_category;
}

if ( ! empty( $search ) ) {
	$where_clauses[] = '(code LIKE %s OR citizen_name LIKE %s OR address LIKE %s)';
	$like            = '%' . $wpdb->esc_like( $search ) . '%';
	$where_values[]  = $like;
	$where_values[]  = $like;
	$where_values[]  = $like;
}

$where_sql = implode( ' AND ', $where_clauses );

// -------------------------------------------------------------------------
// CSV export
// -------------------------------------------------------------------------
if ( isset( $_GET['export'] ) && 'csv' === $_GET['export'] && Comune_App_Manager_Permissions::can_manage_reports() ) {
	check_admin_referer( 'cam_export_reports' );

	$export_query = ! empty( $where_values )
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		? $wpdb->prepare( "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY created_at DESC", ...$where_values )
		: "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY created_at DESC";

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$rows = $wpdb->get_results( $export_query, ARRAY_A );

	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="segnalazioni-' . gmdate( 'Y-m-d' ) . '.csv"' );
	header( 'Pragma: no-cache' );

	$out = fopen( 'php://output', 'w' );

	if ( ! empty( $rows ) ) {
		fputcsv( $out, array_keys( $rows[0] ) );
		foreach ( $rows as $row ) {
			fputcsv( $out, $row );
		}
	}

	fclose( $out );
	exit;
}

// -------------------------------------------------------------------------
// Count total rows for pagination
// -------------------------------------------------------------------------
$count_sql = ! empty( $where_values )
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	? $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}", ...$where_values )
	: "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";

// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
$total_items = (int) $wpdb->get_var( $count_sql );
$total_pages = max( 1, (int) ceil( $total_items / $per_page ) );

// -------------------------------------------------------------------------
// Fetch rows
// -------------------------------------------------------------------------
$rows_sql = ! empty( $where_values )
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	? $wpdb->prepare(
		"SELECT id, code, citizen_name, citizen_email, category, address, priority, status, created_at
		 FROM {$table}
		 WHERE {$where_sql}
		 ORDER BY created_at DESC
		 LIMIT %d OFFSET %d",
		...array_merge( $where_values, [ $per_page, $offset ] )
	)
	: $wpdb->prepare(
		"SELECT id, code, citizen_name, citizen_email, category, address, priority, status, created_at
		 FROM {$table}
		 WHERE {$where_sql}
		 ORDER BY created_at DESC
		 LIMIT %d OFFSET %d",
		$per_page,
		$offset
	);

// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
$reports = $wpdb->get_results( $rows_sql, ARRAY_A );

// -------------------------------------------------------------------------
// Available categories from DB
// -------------------------------------------------------------------------
$categories = $wpdb->get_col( "SELECT DISTINCT category FROM {$table} ORDER BY category ASC" );

// -------------------------------------------------------------------------
// Labels
// -------------------------------------------------------------------------
$status_options = [
	''                   => __( 'Tutti gli stati', 'comune-app-manager' ),
	'ricevuta'           => __( 'Ricevuta', 'comune-app-manager' ),
	'presa_in_carico'    => __( 'Presa in carico', 'comune-app-manager' ),
	'in_lavorazione'     => __( 'In lavorazione', 'comune-app-manager' ),
	'chiusa'             => __( 'Chiusa', 'comune-app-manager' ),
	'respinta'           => __( 'Respinta', 'comune-app-manager' ),
	'non_di_competenza'  => __( 'Non di competenza', 'comune-app-manager' ),
];

$priority_labels = [
	'bassa'   => __( 'Bassa', 'comune-app-manager' ),
	'media'   => __( 'Media', 'comune-app-manager' ),
	'alta'    => __( 'Alta', 'comune-app-manager' ),
	'urgente' => __( 'Urgente', 'comune-app-manager' ),
];

// -------------------------------------------------------------------------
// Pagination URL helper
// -------------------------------------------------------------------------
$base_url = admin_url( 'admin.php' );
$url_args = [
	'page'            => 'cam-reports',
	'filter_status'   => $filter_status,
	'filter_category' => $filter_category,
	'search'          => $search,
];
?>

<div class="wrap cam-wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-flag" style="vertical-align:middle;font-size:1.8rem;height:1.8rem;width:1.8rem;margin-right:6px;"></span>
		<?php esc_html_e( 'Segnalazioni cittadini', 'comune-app-manager' ); ?>
	</h1>
	<hr class="wp-header-end">

	<!-- ===== FILTER BAR ===== -->
	<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="cam-filter-bar">
		<input type="hidden" name="page" value="cam-reports">

		<select name="filter_status" id="filter_status">
			<?php foreach ( $status_options as $val => $label ) : ?>
				<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $filter_status, $val ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<select name="filter_category" id="filter_category">
			<option value=""><?php esc_html_e( 'Tutte le categorie', 'comune-app-manager' ); ?></option>
			<?php foreach ( $categories as $cat ) : ?>
				<option value="<?php echo esc_attr( $cat ); ?>" <?php selected( $filter_category, $cat ); ?>>
					<?php echo esc_html( $cat ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<input
			type="search"
			name="search"
			value="<?php echo esc_attr( $search ); ?>"
			placeholder="<?php esc_attr_e( 'Cerca codice, cittadino, indirizzo…', 'comune-app-manager' ); ?>"
			class="cam-search-input"
		>

		<button type="submit" class="button button-secondary">
			<span class="dashicons dashicons-search" style="vertical-align:middle;"></span>
			<?php esc_html_e( 'Filtra', 'comune-app-manager' ); ?>
		</button>

		<?php if ( ! empty( $filter_status ) || ! empty( $filter_category ) || ! empty( $search ) ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=cam-reports' ) ); ?>" class="button button-link cam-reset-filters">
				<?php esc_html_e( 'Azzera filtri', 'comune-app-manager' ); ?>
			</a>
		<?php endif; ?>

		<?php if ( Comune_App_Manager_Permissions::can_manage_reports() ) : ?>
			<?php
			$export_args = array_merge( $url_args, [ 'export' => 'csv' ] );
			$export_url  = wp_nonce_url( add_query_arg( $export_args, $base_url ), 'cam_export_reports' );
			?>
			<a href="<?php echo esc_url( $export_url ); ?>" class="button button-secondary cam-export-btn" style="margin-left:auto;">
				<span class="dashicons dashicons-download" style="vertical-align:middle;"></span>
				<?php esc_html_e( 'Esporta CSV', 'comune-app-manager' ); ?>
			</a>
		<?php endif; ?>
	</form>

	<!-- ===== TABLE ===== -->
	<div class="cam-table-wrap">
		<?php if ( ! empty( $reports ) ) : ?>
			<table class="wp-list-table widefat fixed striped cam-reports-table">
				<thead>
					<tr>
						<th scope="col" class="cam-col-num">#</th>
						<th scope="col"><?php esc_html_e( 'Codice', 'comune-app-manager' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Cittadino', 'comune-app-manager' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Categoria', 'comune-app-manager' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Indirizzo', 'comune-app-manager' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Priorità', 'comune-app-manager' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Stato', 'comune-app-manager' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Data', 'comune-app-manager' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Azioni', 'comune-app-manager' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$row_num = $offset + 1;
					foreach ( $reports as $report ) :
						$detail_url = admin_url( 'admin.php?page=cam-reports&action=detail&id=' . absint( $report['id'] ) );
					?>
						<tr>
							<td class="cam-col-num"><?php echo esc_html( $row_num++ ); ?></td>
							<td>
								<a href="<?php echo esc_url( $detail_url ); ?>" class="row-title">
									<?php echo esc_html( $report['code'] ); ?>
								</a>
							</td>
							<td>
								<?php echo esc_html( $report['citizen_name'] ); ?>
								<?php if ( ! empty( $report['citizen_email'] ) ) : ?>
									<br><small><?php echo esc_html( $report['citizen_email'] ); ?></small>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $report['category'] ); ?></td>
							<td><?php echo esc_html( $report['address'] ); ?></td>
							<td>
								<?php
								$priority = $report['priority'] ?? 'media';
								$p_class  = 'alta' === $priority || 'urgente' === $priority ? 'cam-priority--high' : 'cam-priority--normal';
								?>
								<span class="cam-priority <?php echo esc_attr( $p_class ); ?>">
									<?php echo esc_html( $priority_labels[ $priority ] ?? $priority ); ?>
								</span>
							</td>
							<td>
								<span class="cam-status-badge status-<?php echo esc_attr( $report['status'] ); ?>">
									<?php echo esc_html( $status_options[ $report['status'] ] ?? $report['status'] ); ?>
								</span>
							</td>
							<td>
								<?php
								echo esc_html(
									wp_date( get_option( 'date_format' ), strtotime( $report['created_at'] ) )
								);
								?>
							</td>
							<td>
								<a href="<?php echo esc_url( $detail_url ); ?>" class="button button-small">
									<?php esc_html_e( 'Dettaglio', 'comune-app-manager' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<div class="cam-empty-state">
				<span class="dashicons dashicons-info-outline" style="font-size:3rem;width:3rem;height:3rem;color:#999;"></span>
				<p><?php esc_html_e( 'Nessuna segnalazione trovata con i filtri selezionati.', 'comune-app-manager' ); ?></p>
			</div>
		<?php endif; ?>
	</div>

	<!-- ===== PAGINATION ===== -->
	<?php if ( $total_pages > 1 ) : ?>
		<div class="tablenav bottom">
			<div class="tablenav-pages">
				<span class="displaying-num">
					<?php
					printf(
						/* translators: %d = total items */
						esc_html( _n( '%d elemento', '%d elementi', $total_items, 'comune-app-manager' ) ),
						esc_html( number_format_i18n( $total_items ) )
					);
					?>
				</span>
				<span class="pagination-links">
					<?php if ( $paged > 1 ) : ?>
						<a href="<?php echo esc_url( add_query_arg( array_merge( $url_args, [ 'paged' => 1 ] ), $base_url ) ); ?>" class="first-page button">&laquo;</a>
						<a href="<?php echo esc_url( add_query_arg( array_merge( $url_args, [ 'paged' => $paged - 1 ] ), $base_url ) ); ?>" class="prev-page button">&lsaquo;</a>
					<?php endif; ?>

					<span class="paging-input">
						<span class="tablenav-paging-text">
							<?php echo esc_html( $paged ); ?> / <?php echo esc_html( $total_pages ); ?>
						</span>
					</span>

					<?php if ( $paged < $total_pages ) : ?>
						<a href="<?php echo esc_url( add_query_arg( array_merge( $url_args, [ 'paged' => $paged + 1 ] ), $base_url ) ); ?>" class="next-page button">&rsaquo;</a>
						<a href="<?php echo esc_url( add_query_arg( array_merge( $url_args, [ 'paged' => $total_pages ] ), $base_url ) ); ?>" class="last-page button">&raquo;</a>
					<?php endif; ?>
				</span>
			</div>
		</div>
	<?php endif; ?>

</div><!-- .wrap.cam-wrap -->
