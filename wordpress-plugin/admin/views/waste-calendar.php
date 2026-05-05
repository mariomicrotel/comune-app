<?php
/**
 * Admin view: Waste collection calendar.
 *
 * @package Comune_App_Manager
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! Comune_App_Manager_Permissions::can_manage_waste() ) {
	wp_die( esc_html__( 'Non hai i permessi per accedere a questa pagina.', 'comune-app-manager' ) );
}

global $wpdb;

$zones_table    = $wpdb->prefix . 'comune_app_waste_zones';
$calendar_table = $wpdb->prefix . 'comune_app_waste_calendar';

// -------------------------------------------------------------------------
// Parameters
// -------------------------------------------------------------------------
$selected_zone = isset( $_GET['zone_id'] ) ? absint( $_GET['zone_id'] ) : 0;
$nav_year      = isset( $_GET['cal_year'] )  ? absint( $_GET['cal_year'] )  : (int) gmdate( 'Y' );
$nav_month     = isset( $_GET['cal_month'] ) ? absint( $_GET['cal_month'] ) : (int) gmdate( 'n' );

// Clamp month.
if ( $nav_month < 1 )  { $nav_month = 12; --$nav_year; }
if ( $nav_month > 12 ) { $nav_month = 1;  ++$nav_year; }

$message      = '';
$message_type = '';

// -------------------------------------------------------------------------
// Handle CSV import
// -------------------------------------------------------------------------
if ( isset( $_POST['cam_import_csv'] ) ) {
	check_admin_referer( 'cam_waste_nonce', 'cam_waste_nonce' );

	if ( ! empty( $_FILES['import_csv']['tmp_name'] ) && Comune_App_Manager_Permissions::can_manage_waste() ) {
		$file = fopen( sanitize_text_field( wp_unslash( $_FILES['import_csv']['tmp_name'] ) ), 'r' );
		$row  = 0;
		$imported = 0;

		if ( $file ) {
			while ( ( $line = fgetcsv( $file ) ) !== false ) {
				++$row;
				if ( 1 === $row && ! is_numeric( $line[0][0] ) ) {
					continue; // skip header
				}
				if ( count( $line ) < 3 ) {
					continue;
				}

				$csv_date       = sanitize_text_field( $line[0] );
				$csv_zone_id    = absint( $line[1] );
				$csv_waste_type = sanitize_key( $line[2] );
				$csv_notes      = isset( $line[3] ) ? sanitize_textarea_field( $line[3] ) : '';

				if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $csv_date ) ) {
					continue;
				}

				$wpdb->replace(
					$calendar_table,
					[
						'collection_date' => $csv_date,
						'zone_id'         => $csv_zone_id,
						'waste_type'      => $csv_waste_type,
						'notes'           => $csv_notes,
					],
					[ '%s', '%d', '%s', '%s' ]
				);
				++$imported;
			}
			fclose( $file );

			$message      = sprintf(
				/* translators: %d = imported rows */
				__( 'Importate %d righe dal CSV.', 'comune-app-manager' ),
				$imported
			);
			$message_type = 'success';
		}
	}
}

// -------------------------------------------------------------------------
// Handle add entry
// -------------------------------------------------------------------------
if ( isset( $_POST['cam_add_entry'] ) ) {
	check_admin_referer( 'cam_waste_nonce', 'cam_waste_nonce' );

	$entry_date      = sanitize_text_field( wp_unslash( $_POST['entry_date'] ?? '' ) );
	$entry_zone_id   = absint( $_POST['entry_zone_id'] ?? 0 );
	$entry_waste     = sanitize_key( $_POST['entry_waste_type'] ?? '' );
	$entry_notes     = sanitize_textarea_field( wp_unslash( $_POST['entry_notes'] ?? '' ) );

	if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $entry_date ) && $entry_zone_id && $entry_waste ) {
		$wpdb->replace(
			$calendar_table,
			[
				'collection_date' => $entry_date,
				'zone_id'         => $entry_zone_id,
				'waste_type'      => $entry_waste,
				'notes'           => $entry_notes,
			],
			[ '%s', '%d', '%s', '%s' ]
		);
		$message      = __( 'Raccolta aggiunta.', 'comune-app-manager' );
		$message_type = 'success';
	} else {
		$message      = __( 'Dati non validi. Controlla data, zona e tipo rifiuto.', 'comune-app-manager' );
		$message_type = 'error';
	}
}

// -------------------------------------------------------------------------
// Handle delete entry
// -------------------------------------------------------------------------
if ( isset( $_GET['delete_entry'] ) && isset( $_GET['_wpnonce'] ) ) {
	if ( wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'cam_delete_waste_entry' ) ) {
		$del_id = absint( $_GET['delete_entry'] );
		$wpdb->delete( $calendar_table, [ 'id' => $del_id ], [ '%d' ] );
		$message      = __( 'Voce eliminata.', 'comune-app-manager' );
		$message_type = 'success';
	}
}

// -------------------------------------------------------------------------
// CSV export
// -------------------------------------------------------------------------
if ( isset( $_GET['export_csv'] ) && $selected_zone && isset( $_GET['_wpnonce'] ) ) {
	if ( wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'cam_export_waste' ) ) {
		$export_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT collection_date, zone_id, waste_type, notes
				 FROM {$calendar_table}
				 WHERE zone_id = %d
				   AND DATE_FORMAT(collection_date, '%%Y-%%m') = %s
				 ORDER BY collection_date ASC",
				$selected_zone,
				sprintf( '%04d-%02d', $nav_year, $nav_month )
			),
			ARRAY_A
		);

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="rifiuti-' . intval( $nav_year ) . '-' . intval( $nav_month ) . '-zona' . intval( $selected_zone ) . '.csv"' );
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, [ 'date', 'zone_id', 'waste_type', 'notes' ] );
		foreach ( $export_rows as $er ) {
			fputcsv( $out, $er );
		}
		fclose( $out );
		exit;
	}
}

// -------------------------------------------------------------------------
// Load zones
// -------------------------------------------------------------------------
$zones = $wpdb->get_results( "SELECT id, name FROM {$zones_table} ORDER BY name ASC", ARRAY_A );

// Auto-select first zone.
if ( ! $selected_zone && ! empty( $zones ) ) {
	$selected_zone = (int) $zones[0]['id'];
}

// -------------------------------------------------------------------------
// Build calendar data
// -------------------------------------------------------------------------
$days_in_month = (int) gmdate( 't', mktime( 0, 0, 0, $nav_month, 1, $nav_year ) );
$first_dow     = (int) gmdate( 'N', mktime( 0, 0, 0, $nav_month, 1, $nav_year ) ); // 1=Mon … 7=Sun

// Fetch entries for this zone+month.
$entries_raw = [];
if ( $selected_zone ) {
	$entries_raw = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT id, collection_date, waste_type, notes
			 FROM {$calendar_table}
			 WHERE zone_id = %d
			   AND DATE_FORMAT(collection_date, '%%Y-%%m') = %s
			 ORDER BY collection_date ASC",
			$selected_zone,
			sprintf( '%04d-%02d', $nav_year, $nav_month )
		),
		ARRAY_A
	);
}

$entries_by_day = [];
foreach ( $entries_raw as $entry ) {
	$day = (int) gmdate( 'j', strtotime( $entry['collection_date'] ) );
	$entries_by_day[ $day ][] = $entry;
}

// -------------------------------------------------------------------------
// Waste type labels + colours
// -------------------------------------------------------------------------
$waste_types = [
	'organico'          => [ 'label' => __( 'Organico', 'comune-app-manager' ),           'class' => 'waste-organico' ],
	'indifferenziato'   => [ 'label' => __( 'Indifferenziato', 'comune-app-manager' ),    'class' => 'waste-indifferenziato' ],
	'carta'             => [ 'label' => __( 'Carta', 'comune-app-manager' ),              'class' => 'waste-carta' ],
	'plastica_metalli'  => [ 'label' => __( 'Plastica/Metalli', 'comune-app-manager' ),   'class' => 'waste-plastica_metalli' ],
	'vetro'             => [ 'label' => __( 'Vetro', 'comune-app-manager' ),              'class' => 'waste-vetro' ],
	'ingombranti'       => [ 'label' => __( 'Ingombranti', 'comune-app-manager' ),        'class' => 'waste-ingombranti' ],
	'verde'             => [ 'label' => __( 'Verde', 'comune-app-manager' ),              'class' => 'waste-verde' ],
	'altro'             => [ 'label' => __( 'Altro', 'comune-app-manager' ),              'class' => 'waste-altro' ],
];

$today     = gmdate( 'Y-m-d' );
$today_day = (int) gmdate( 'j' );
$is_curr   = (int) gmdate( 'Y' ) === $nav_year && (int) gmdate( 'n' ) === $nav_month;

// Navigation URLs.
$prev_m = $nav_month - 1;
$prev_y = $nav_year;
if ( $prev_m < 1 ) { $prev_m = 12; --$prev_y; }

$next_m = $nav_month + 1;
$next_y = $nav_year;
if ( $next_m > 12 ) { $next_m = 1; ++$next_y; }

$base_url  = admin_url( 'admin.php?page=cam-waste&zone_id=' . $selected_zone );
$prev_url  = add_query_arg( [ 'cal_year' => $prev_y, 'cal_month' => $prev_m ], $base_url );
$next_url  = add_query_arg( [ 'cal_year' => $next_y, 'cal_month' => $next_m ], $base_url );
$month_str = wp_date( 'F Y', mktime( 0, 0, 0, $nav_month, 1, $nav_year ) );

$day_names = [ __( 'Lun', 'comune-app-manager' ), __( 'Mar', 'comune-app-manager' ), __( 'Mer', 'comune-app-manager' ), __( 'Gio', 'comune-app-manager' ), __( 'Ven', 'comune-app-manager' ), __( 'Sab', 'comune-app-manager' ), __( 'Dom', 'comune-app-manager' ) ];
?>

<div class="wrap cam-wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-calendar-alt" style="vertical-align:middle;font-size:1.8rem;height:1.8rem;width:1.8rem;margin-right:6px;"></span>
		<?php esc_html_e( 'Calendario raccolta rifiuti', 'comune-app-manager' ); ?>
	</h1>
	<hr class="wp-header-end">

	<?php if ( $message ) : ?>
		<div class="notice notice-<?php echo esc_attr( $message_type ); ?> is-dismissible">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
	<?php endif; ?>

	<!-- ===== ZONE SELECTOR ===== -->
	<div class="cam-waste-toolbar">
		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="cam-inline-form">
			<input type="hidden" name="page" value="cam-waste">
			<input type="hidden" name="cal_year"  value="<?php echo esc_attr( $nav_year ); ?>">
			<input type="hidden" name="cal_month" value="<?php echo esc_attr( $nav_month ); ?>">

			<label for="zone_id"><strong><?php esc_html_e( 'Zona:', 'comune-app-manager' ); ?></strong></label>
			<select name="zone_id" id="zone_id" onchange="this.form.submit()">
				<option value=""><?php esc_html_e( '— Seleziona zona —', 'comune-app-manager' ); ?></option>
				<?php foreach ( $zones as $zone ) : ?>
					<option value="<?php echo esc_attr( $zone['id'] ); ?>" <?php selected( $selected_zone, (int) $zone['id'] ); ?>>
						<?php echo esc_html( $zone['name'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<noscript><button type="submit" class="button"><?php esc_html_e( 'Vai', 'comune-app-manager' ); ?></button></noscript>
		</form>

		<a href="<?php echo esc_url( admin_url( 'admin.php?page=cam-waste&action=add_zone' ) ); ?>" class="button button-secondary">
			<span class="dashicons dashicons-plus-alt" style="vertical-align:middle;"></span>
			<?php esc_html_e( 'Aggiungi Zona', 'comune-app-manager' ); ?>
		</a>

		<?php if ( $selected_zone ) : ?>
			<?php $export_url = wp_nonce_url( add_query_arg( [ 'export_csv' => 1, 'zone_id' => $selected_zone, 'cal_year' => $nav_year, 'cal_month' => $nav_month ], admin_url( 'admin.php?page=cam-waste' ) ), 'cam_export_waste' ); ?>
			<a href="<?php echo esc_url( $export_url ); ?>" class="button button-secondary" style="margin-left:auto;">
				<span class="dashicons dashicons-download" style="vertical-align:middle;"></span>
				<?php esc_html_e( 'Esporta CSV mese', 'comune-app-manager' ); ?>
			</a>
		<?php endif; ?>
	</div>

	<!-- ===== CALENDAR ===== -->
	<div class="cam-meta-box" style="margin-top:16px;">
		<!-- Nav -->
		<div class="cam-calendar-nav">
			<a href="<?php echo esc_url( $prev_url ); ?>" class="button button-secondary">&laquo; <?php esc_html_e( 'Mese precedente', 'comune-app-manager' ); ?></a>
			<h2 style="margin:0;"><?php echo esc_html( ucfirst( $month_str ) ); ?></h2>
			<a href="<?php echo esc_url( $next_url ); ?>" class="button button-secondary"><?php esc_html_e( 'Mese successivo', 'comune-app-manager' ); ?> &raquo;</a>
		</div>

		<!-- Grid -->
		<div class="cam-waste-calendar">
			<?php foreach ( $day_names as $dn ) : ?>
				<div class="cam-calendar-header-cell"><?php echo esc_html( $dn ); ?></div>
			<?php endforeach; ?>

			<?php
			// Empty cells before first day (Monday-based).
			for ( $pad = 1; $pad < $first_dow; $pad++ ) :
			?>
				<div class="cam-calendar-cell cam-calendar-cell--empty"></div>
			<?php endfor; ?>

			<?php for ( $d = 1; $d <= $days_in_month; $d++ ) : ?>
				<?php
				$cell_date    = sprintf( '%04d-%02d-%02d', $nav_year, $nav_month, $d );
				$is_today     = $is_curr && $d === $today_day;
				$cell_entries = $entries_by_day[ $d ] ?? [];
				?>
				<div class="cam-calendar-cell<?php echo $is_today ? ' cam-calendar-cell--today' : ''; ?>">
					<span class="cam-calendar-day-num"><?php echo esc_html( $d ); ?></span>

					<?php foreach ( $cell_entries as $entry ) : ?>
						<?php
						$wt    = $entry['waste_type'];
						$winfo = $waste_types[ $wt ] ?? [ 'label' => $wt, 'class' => 'waste-altro' ];
						$del_url = wp_nonce_url(
							add_query_arg(
								[ 'delete_entry' => absint( $entry['id'] ), 'zone_id' => $selected_zone, 'cal_year' => $nav_year, 'cal_month' => $nav_month ],
								admin_url( 'admin.php?page=cam-waste' )
							),
							'cam_delete_waste_entry'
						);
						?>
						<div class="cam-waste-badge <?php echo esc_attr( $winfo['class'] ); ?>"
							title="<?php echo esc_attr( $entry['notes'] ?? '' ); ?>">
							<?php echo esc_html( $winfo['label'] ); ?>
							<a href="<?php echo esc_url( $del_url ); ?>"
							   class="cam-waste-delete"
							   onclick="return confirm('<?php esc_attr_e( 'Eliminare questa voce?', 'comune-app-manager' ); ?>')"
							   title="<?php esc_attr_e( 'Elimina', 'comune-app-manager' ); ?>">
								&times;
							</a>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endfor; ?>
		</div>
	</div>

	<!-- ===== ADD ENTRY FORM ===== -->
	<div class="cam-meta-box" style="margin-top:24px;">
		<h2><?php esc_html_e( 'Aggiungi raccolta', 'comune-app-manager' ); ?></h2>

		<form method="post" action="">
			<?php wp_nonce_field( 'cam_waste_nonce', 'cam_waste_nonce' ); ?>
			<input type="hidden" name="cam_add_entry" value="1">
			<input type="hidden" name="zone_id" value="<?php echo esc_attr( $selected_zone ); ?>">
			<input type="hidden" name="cal_year"  value="<?php echo esc_attr( $nav_year ); ?>">
			<input type="hidden" name="cal_month" value="<?php echo esc_attr( $nav_month ); ?>">

			<table class="form-table">
				<tr>
					<th scope="row"><label for="entry_date"><?php esc_html_e( 'Data *', 'comune-app-manager' ); ?></label></th>
					<td>
						<input type="date" name="entry_date" id="entry_date" class="regular-text" required
							min="<?php echo esc_attr( sprintf( '%04d-%02d-01', $nav_year, $nav_month ) ); ?>"
							max="<?php echo esc_attr( sprintf( '%04d-%02d-%02d', $nav_year, $nav_month, $days_in_month ) ); ?>"
						>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="entry_zone_id"><?php esc_html_e( 'Zona *', 'comune-app-manager' ); ?></label></th>
					<td>
						<select name="entry_zone_id" id="entry_zone_id" class="regular-text" required>
							<?php foreach ( $zones as $zone ) : ?>
								<option value="<?php echo esc_attr( $zone['id'] ); ?>" <?php selected( $selected_zone, (int) $zone['id'] ); ?>>
									<?php echo esc_html( $zone['name'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="entry_waste_type"><?php esc_html_e( 'Tipo rifiuto *', 'comune-app-manager' ); ?></label></th>
					<td>
						<select name="entry_waste_type" id="entry_waste_type" class="regular-text" required>
							<?php foreach ( $waste_types as $wt_val => $wt_info ) : ?>
								<option value="<?php echo esc_attr( $wt_val ); ?>"><?php echo esc_html( $wt_info['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="entry_notes"><?php esc_html_e( 'Note', 'comune-app-manager' ); ?></label></th>
					<td>
						<textarea name="entry_notes" id="entry_notes" class="large-text" rows="2"></textarea>
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" class="button button-primary">
					<span class="dashicons dashicons-plus-alt" style="vertical-align:middle;"></span>
					<?php esc_html_e( 'Aggiungi raccolta', 'comune-app-manager' ); ?>
				</button>
			</p>
		</form>
	</div>

	<!-- ===== CSV IMPORT ===== -->
	<div class="cam-meta-box" style="margin-top:24px;">
		<h2><?php esc_html_e( 'Importa da CSV', 'comune-app-manager' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Formato CSV: data (YYYY-MM-DD), zone_id, waste_type, notes (opzionale).', 'comune-app-manager' ); ?>
		</p>

		<form method="post" action="" enctype="multipart/form-data">
			<?php wp_nonce_field( 'cam_waste_nonce', 'cam_waste_nonce' ); ?>
			<input type="hidden" name="cam_import_csv" value="1">
			<input type="hidden" name="zone_id" value="<?php echo esc_attr( $selected_zone ); ?>">
			<input type="hidden" name="cal_year"  value="<?php echo esc_attr( $nav_year ); ?>">
			<input type="hidden" name="cal_month" value="<?php echo esc_attr( $nav_month ); ?>">

			<p>
				<input type="file" name="import_csv" id="import_csv" accept=".csv,text/csv" required>
				<button type="submit" class="button button-secondary" style="margin-left:8px;">
					<span class="dashicons dashicons-upload" style="vertical-align:middle;"></span>
					<?php esc_html_e( 'Importa', 'comune-app-manager' ); ?>
				</button>
			</p>
		</form>
	</div>

</div><!-- .wrap.cam-wrap -->
