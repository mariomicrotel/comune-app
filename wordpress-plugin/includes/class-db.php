<?php
/**
 * Database table manager.
 *
 * Creates and upgrades all custom tables used by the plugin.
 *
 * @package ComuneAppManager
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comune_App_Manager_DB
 *
 * Responsible for creating and upgrading custom database tables.
 * Uses dbDelta() for safe, idempotent schema creation.
 */
class Comune_App_Manager_DB {

	/** Current schema version. Must match the value stored in cam_db_version option. */
	const SCHEMA_VERSION = '1.4.0';

	/**
	 * Create (or upgrade) all custom plugin tables.
	 *
	 * Safe to call multiple times; dbDelta only applies changes.
	 */
	public static function create_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		// ------------------------------------------------------------------
		// 1. Device tokens (FCM push registration)
		// ------------------------------------------------------------------
		$sql[] = "CREATE TABLE {$wpdb->prefix}cam_device_tokens (
			id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id       BIGINT(20) UNSIGNED DEFAULT NULL,
			token         VARCHAR(512)        NOT NULL,
			platform      ENUM('ios','android') NOT NULL,
			device_id     VARCHAR(255)        DEFAULT NULL COMMENT 'UUID from Flutter SharedPreferences',
			app_version   VARCHAR(20)         DEFAULT NULL,
			device_model  VARCHAR(100)        DEFAULT NULL,
			language      VARCHAR(10)         NOT NULL DEFAULT 'it',
			categories    LONGTEXT            DEFAULT NULL COMMENT 'JSON array of subscribed category slugs',
			zones         LONGTEXT            DEFAULT NULL COMMENT 'JSON array of subscribed zone slugs',
			is_active     TINYINT(1)          NOT NULL DEFAULT 1,
			created_at    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY token (token(255)),
			KEY user_id (user_id),
			KEY platform (platform),
			KEY device_id (device_id(191)),
			KEY is_active (is_active)
		) $charset_collate;";

		// ------------------------------------------------------------------
		// 2. Push notification send log
		// ------------------------------------------------------------------
		$sql[] = "CREATE TABLE {$wpdb->prefix}cam_push_logs (
			id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			notification_id BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'WP post ID of the notification',
			title           VARCHAR(255)        NOT NULL DEFAULT '',
			body            TEXT                NOT NULL,
			target_type     VARCHAR(50)         NOT NULL DEFAULT 'all' COMMENT 'all|category|zone|user',
			target_value    VARCHAR(255)        DEFAULT NULL,
			platform        VARCHAR(20)         DEFAULT 'all',
			recipients      INT(11)             NOT NULL DEFAULT 0,
			success_count   INT(11)             NOT NULL DEFAULT 0,
			failure_count   INT(11)             NOT NULL DEFAULT 0,
			firebase_response LONGTEXT          DEFAULT NULL,
			sent_by         BIGINT(20) UNSIGNED DEFAULT NULL,
			sent_at         DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY notification_id (notification_id),
			KEY sent_at (sent_at)
		) $charset_collate;";

		// ------------------------------------------------------------------
		// 3. Citizen reports (segnalazioni)
		// ------------------------------------------------------------------
		$sql[] = "CREATE TABLE {$wpdb->prefix}cam_segnalazioni (
			id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			public_code     VARCHAR(30)         NOT NULL COMMENT 'e.g. SEG-2026-000001',
			post_id         BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'Linked WP post if created',
			category        VARCHAR(100)        NOT NULL DEFAULT '',
			title           VARCHAR(255)        NOT NULL,
			description     TEXT                NOT NULL,
			address         VARCHAR(255)        DEFAULT NULL,
			lat             DECIMAL(10,7)       DEFAULT NULL,
			lng             DECIMAL(10,7)       DEFAULT NULL,
			status          ENUM('pending','in_progress','resolved','rejected') NOT NULL DEFAULT 'pending',
			priority        ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
			device_id       VARCHAR(255)        DEFAULT NULL COMMENT 'UUID from Flutter SharedPreferences',
			submitted_by    BIGINT(20) UNSIGNED DEFAULT NULL,
			submitter_email VARCHAR(254)        DEFAULT NULL,
			submitter_name  VARCHAR(150)        DEFAULT NULL,
			assigned_to     BIGINT(20) UNSIGNED DEFAULT NULL,
			internal_notes  TEXT                DEFAULT NULL,
			resolved_at     DATETIME            DEFAULT NULL,
			created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY public_code (public_code),
			KEY status (status),
			KEY device_id (device_id(191)),
			KEY created_at (created_at)
		) $charset_collate;";

		// ------------------------------------------------------------------
		// 4. Survey definitions (stored as WP post meta; table for responses)
		// ------------------------------------------------------------------
		$sql[] = "CREATE TABLE {$wpdb->prefix}cam_survey_responses (
			id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			survey_id   BIGINT(20) UNSIGNED NOT NULL COMMENT 'WP post ID of the survey',
			user_id     BIGINT(20) UNSIGNED DEFAULT NULL,
			device_token VARCHAR(512)       DEFAULT NULL,
			ip_address  VARCHAR(45)         DEFAULT NULL,
			started_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			completed_at DATETIME           DEFAULT NULL,
			is_complete TINYINT(1)          NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			KEY survey_id (survey_id),
			KEY user_id (user_id),
			KEY is_complete (is_complete)
		) $charset_collate;";

		// ------------------------------------------------------------------
		// 5. Individual survey answers
		// ------------------------------------------------------------------
		$sql[] = "CREATE TABLE {$wpdb->prefix}cam_survey_answers (
			id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			response_id BIGINT(20) UNSIGNED NOT NULL,
			survey_id   BIGINT(20) UNSIGNED NOT NULL,
			question_id VARCHAR(100)        NOT NULL,
			answer_value LONGTEXT           NOT NULL COMMENT 'JSON-encoded answer',
			created_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY response_id (response_id),
			KEY survey_id (survey_id),
			KEY question_id (question_id)
		) $charset_collate;";

		// ------------------------------------------------------------------
		// 6. Waste collection schedule entries
		// ------------------------------------------------------------------
		$sql[] = "CREATE TABLE {$wpdb->prefix}cam_waste_schedule (
			id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			category_id  BIGINT(20) UNSIGNED NOT NULL,
			zone_slug    VARCHAR(100)        NOT NULL,
			collection_date DATE             NOT NULL,
			recurrence   VARCHAR(50)         DEFAULT NULL COMMENT 'weekly|biweekly|monthly|none',
			notes        TEXT                DEFAULT NULL,
			created_at   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY zone_slug (zone_slug),
			KEY collection_date (collection_date),
			KEY category_id (category_id)
		) $charset_collate;";

		// ------------------------------------------------------------------
		// 7. Waste collection categories (types of waste)
		// ------------------------------------------------------------------
		$sql[] = "CREATE TABLE {$wpdb->prefix}cam_waste_categories (
			id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name        VARCHAR(150)        NOT NULL,
			slug        VARCHAR(150)        NOT NULL,
			color       VARCHAR(7)          NOT NULL DEFAULT '#000000' COMMENT 'Hex color',
			icon        VARCHAR(255)        DEFAULT NULL,
			description TEXT                DEFAULT NULL,
			sort_order  INT(11)             NOT NULL DEFAULT 0,
			is_active   TINYINT(1)          NOT NULL DEFAULT 1,
			created_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY slug (slug),
			KEY is_active (is_active)
		) $charset_collate;";

		// ------------------------------------------------------------------
		// 8. Waste collection zones (human-readable names)
		// ------------------------------------------------------------------
		$sql[] = "CREATE TABLE {$wpdb->prefix}cam_waste_zones (
			id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			slug        VARCHAR(100)        NOT NULL COMMENT 'Matches zone_slug in cam_waste_schedule',
			name        VARCHAR(150)        NOT NULL,
			description TEXT                DEFAULT NULL,
			is_active   TINYINT(1)          NOT NULL DEFAULT 1,
			sort_order  INT(11)             NOT NULL DEFAULT 0,
			created_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY slug (slug),
			KEY is_active (is_active)
		) $charset_collate;";

		// ------------------------------------------------------------------
		// 9. Citizen report attachments
		// ------------------------------------------------------------------
		$sql[] = "CREATE TABLE {$wpdb->prefix}cam_segnalazione_allegati (
			id               BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			segnalazione_id  BIGINT(20) UNSIGNED NOT NULL,
			file_url         VARCHAR(512)        NOT NULL,
			file_path        VARCHAR(512)        DEFAULT NULL,
			mime_type        VARCHAR(100)        DEFAULT NULL,
			created_at       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY segnalazione_id (segnalazione_id)
		) $charset_collate;";

		// ------------------------------------------------------------------
		// 10. JWT / API tokens for app users
		// ------------------------------------------------------------------
		$sql[] = "CREATE TABLE {$wpdb->prefix}cam_api_tokens (
			id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id      BIGINT(20) UNSIGNED DEFAULT NULL,
			token_hash   VARCHAR(255)        NOT NULL COMMENT 'SHA-256 hash of the token',
			device_id    VARCHAR(255)        DEFAULT NULL,
			platform     ENUM('ios','android','web') DEFAULT NULL,
			expires_at   DATETIME            NOT NULL,
			revoked      TINYINT(1)          NOT NULL DEFAULT 0,
			created_at   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			last_used_at DATETIME            DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY token_hash (token_hash),
			KEY user_id (user_id),
			KEY expires_at (expires_at),
			KEY revoked (revoked)
		) $charset_collate;";

		// ------------------------------------------------------------------
		// 11. REST API access log
		// ------------------------------------------------------------------
		$sql[] = "CREATE TABLE {$wpdb->prefix}cam_access_logs (
			id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			endpoint     VARCHAR(255)        NOT NULL,
			method       VARCHAR(10)         NOT NULL,
			ip_address   VARCHAR(45)         DEFAULT NULL,
			user_id      BIGINT(20) UNSIGNED DEFAULT NULL,
			http_status  SMALLINT(5) UNSIGNED NOT NULL DEFAULT 200,
			duration_ms  SMALLINT(5) UNSIGNED DEFAULT NULL,
			created_at   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY endpoint (endpoint(100)),
			KEY ip_address (ip_address),
			KEY created_at (created_at)
		) $charset_collate;";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}

		// Seed default data if tables are freshly created.
		self::seed_waste_categories();
		self::seed_waste_zones();
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Insert default waste categories if none exist yet.
	 */
	private static function seed_waste_categories(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'cam_waste_categories';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );
		if ( $count > 0 ) {
			return;
		}

		$defaults = array(
			array( 'name' => 'Indifferenziato',       'slug' => 'indifferenziato',  'color' => '#555555', 'sort_order' => 1 ),
			array( 'name' => 'Organico',               'slug' => 'organico',         'color' => '#8B4513', 'sort_order' => 2 ),
			array( 'name' => 'Carta e Cartone',        'slug' => 'carta',            'color' => '#1a5276', 'sort_order' => 3 ),
			array( 'name' => 'Plastica e Metalli',     'slug' => 'plastica',         'color' => '#f39c12', 'sort_order' => 4 ),
			array( 'name' => 'Vetro',                  'slug' => 'vetro',            'color' => '#27ae60', 'sort_order' => 5 ),
			array( 'name' => 'Ingombranti',            'slug' => 'ingombranti',      'color' => '#8e44ad', 'sort_order' => 6 ),
		);

		foreach ( $defaults as $cat ) {
			$wpdb->insert(
				$table,
				array(
					'name'       => $cat['name'],
					'slug'       => $cat['slug'],
					'color'      => $cat['color'],
					'sort_order' => $cat['sort_order'],
					'is_active'  => 1,
				),
				array( '%s', '%s', '%s', '%d', '%d' )
			);
		}
	}

	/**
	 * Insert default waste zones for Comune di Acerno if none exist yet.
	 */
	private static function seed_waste_zones(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'cam_waste_zones';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );
		if ( $count > 0 ) {
			return;
		}

		$defaults = array(
			array( 'slug' => 'zona-a', 'name' => 'Zona A — Sant\'Anna',        'sort_order' => 1 ),
			array( 'slug' => 'zona-b', 'name' => 'Zona B — Centro Storico',    'sort_order' => 2 ),
			array( 'slug' => 'zona-c', 'name' => 'Zona C — Piaggine',          'sort_order' => 3 ),
			array( 'slug' => 'zona-d', 'name' => 'Zona D — Località Rurali',   'sort_order' => 4 ),
		);

		foreach ( $defaults as $zone ) {
			$wpdb->insert(
				$table,
				array(
					'slug'       => $zone['slug'],
					'name'       => $zone['name'],
					'sort_order' => $zone['sort_order'],
					'is_active'  => 1,
				),
				array( '%s', '%s', '%d', '%d' )
			);
		}
	}
}
