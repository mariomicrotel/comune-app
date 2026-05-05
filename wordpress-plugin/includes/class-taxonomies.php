<?php
/**
 * Taxonomy registrations.
 *
 * @package ComuneAppManager
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comune_App_Manager_Taxonomies
 *
 * Registers all hierarchical and flat taxonomies used by the plugin.
 */
class Comune_App_Manager_Taxonomies {

	/**
	 * Register all taxonomies.
	 * Called from the 'init' hook.
	 */
	public function register(): void {
		$this->register_categoria_avviso();
		$this->register_categoria_evento();
		$this->register_categoria_luogo();
		$this->register_categoria_documento();
		$this->register_zona();
	}

	// -----------------------------------------------------------------------
	// Individual taxonomy registrations
	// -----------------------------------------------------------------------

	/** Categorie per gli avvisi. */
	private function register_categoria_avviso(): void {
		register_taxonomy(
			'cam_categoria_avviso',
			array( 'comune_avviso' ),
			array(
				'hierarchical'      => true,
				'labels'            => $this->build_labels( 'Categoria avviso', 'Categorie avvisi' ),
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rest_base'         => 'categorie-avvisi',
				'query_var'         => true,
				'rewrite'           => array( 'slug' => 'categorie-avvisi' ),
			)
		);
	}

	/** Categorie per gli eventi. */
	private function register_categoria_evento(): void {
		register_taxonomy(
			'cam_categoria_evento',
			array( 'comune_evento' ),
			array(
				'hierarchical'      => true,
				'labels'            => $this->build_labels( 'Categoria evento', 'Categorie eventi' ),
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rest_base'         => 'categorie-eventi',
				'query_var'         => true,
				'rewrite'           => array( 'slug' => 'categorie-eventi' ),
			)
		);
	}

	/** Categorie per i luoghi. */
	private function register_categoria_luogo(): void {
		register_taxonomy(
			'cam_categoria_luogo',
			array( 'comune_luogo' ),
			array(
				'hierarchical'      => true,
				'labels'            => $this->build_labels( 'Categoria luogo', 'Categorie luoghi' ),
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rest_base'         => 'categorie-luoghi',
				'query_var'         => true,
				'rewrite'           => array( 'slug' => 'categorie-luoghi' ),
			)
		);
	}

	/** Categorie per i documenti. */
	private function register_categoria_documento(): void {
		register_taxonomy(
			'cam_categoria_documento',
			array( 'comune_documento' ),
			array(
				'hierarchical'      => true,
				'labels'            => $this->build_labels( 'Categoria documento', 'Categorie documenti' ),
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rest_base'         => 'categorie-documenti',
				'query_var'         => true,
				'rewrite'           => array( 'slug' => 'categorie-documenti' ),
			)
		);
	}

	/**
	 * Zone geografiche (usate da avvisi, eventi, raccolta rifiuti e notifiche push).
	 * Flat taxonomy shared across multiple post types.
	 */
	private function register_zona(): void {
		register_taxonomy(
			'cam_zona',
			array( 'comune_avviso', 'comune_evento', 'comune_luogo' ),
			array(
				'hierarchical'      => false,
				'labels'            => $this->build_labels( 'Zona', 'Zone' ),
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rest_base'         => 'zone',
				'query_var'         => true,
				'rewrite'           => array( 'slug' => 'zone' ),
			)
		);
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Build a basic labels array for a taxonomy.
	 *
	 * @param string $singular Singular taxonomy name (Italian).
	 * @param string $plural   Plural taxonomy name (Italian).
	 * @return array<string,string>
	 */
	private function build_labels( string $singular, string $plural ): array {
		return array(
			'name'              => _x( $plural,   'taxonomy general name', 'comune-app-manager' ),
			'singular_name'     => _x( $singular, 'taxonomy singular name', 'comune-app-manager' ),
			'search_items'      => sprintf( /* translators: %s: taxonomy plural */ __( 'Cerca %s', 'comune-app-manager' ), strtolower( $plural ) ),
			'all_items'         => sprintf( /* translators: %s: taxonomy plural */ __( 'Tutte le %s', 'comune-app-manager' ), strtolower( $plural ) ),
			'parent_item'       => sprintf( /* translators: %s: taxonomy singular */ __( '%s genitore', 'comune-app-manager' ), $singular ),
			'parent_item_colon' => sprintf( /* translators: %s: taxonomy singular */ __( '%s genitore:', 'comune-app-manager' ), $singular ),
			'edit_item'         => sprintf( /* translators: %s: taxonomy singular */ __( 'Modifica %s', 'comune-app-manager' ), strtolower( $singular ) ),
			'update_item'       => sprintf( /* translators: %s: taxonomy singular */ __( 'Aggiorna %s', 'comune-app-manager' ), strtolower( $singular ) ),
			'add_new_item'      => sprintf( /* translators: %s: taxonomy singular */ __( 'Aggiungi %s', 'comune-app-manager' ), strtolower( $singular ) ),
			'new_item_name'     => sprintf( /* translators: %s: taxonomy singular */ __( 'Nuovo nome %s', 'comune-app-manager' ), strtolower( $singular ) ),
			'menu_name'         => $plural,
		);
	}
}
