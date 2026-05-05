<?php
/**
 * Custom post type registrations.
 *
 * @package ComuneAppManager
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comune_App_Manager_Post_Types
 *
 * Registers all custom post types used by the plugin.
 */
class Comune_App_Manager_Post_Types {

	/**
	 * Register all custom post types.
	 * Called from the 'init' hook.
	 */
	public function register(): void {
		$this->register_avvisi();
		$this->register_eventi();
		$this->register_uffici();
		$this->register_luoghi();
		$this->register_documenti();
	}

	// -----------------------------------------------------------------------
	// Individual CPT registrations
	// -----------------------------------------------------------------------

	/** Avvisi e notizie comunali. */
	private function register_avvisi(): void {
		$labels = array(
			'name'               => _x( 'Avvisi', 'post type general name', 'comune-app-manager' ),
			'singular_name'      => _x( 'Avviso', 'post type singular name', 'comune-app-manager' ),
			'menu_name'          => _x( 'Avvisi', 'admin menu', 'comune-app-manager' ),
			'add_new'            => __( 'Aggiungi nuovo', 'comune-app-manager' ),
			'add_new_item'       => __( 'Aggiungi nuovo avviso', 'comune-app-manager' ),
			'edit_item'          => __( 'Modifica avviso', 'comune-app-manager' ),
			'new_item'           => __( 'Nuovo avviso', 'comune-app-manager' ),
			'view_item'          => __( 'Visualizza avviso', 'comune-app-manager' ),
			'search_items'       => __( 'Cerca avvisi', 'comune-app-manager' ),
			'not_found'          => __( 'Nessun avviso trovato.', 'comune-app-manager' ),
			'not_found_in_trash' => __( 'Nessun avviso nel cestino.', 'comune-app-manager' ),
		);

		register_post_type(
			'comune_avviso',
			array(
				'labels'              => $labels,
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => false, // Shown under custom admin menu.
				'show_in_rest'        => true,
				'rest_base'           => 'avvisi',
				'capability_type'     => 'post',
				'has_archive'         => true,
				'rewrite'             => array( 'slug' => 'avvisi', 'with_front' => false ),
				'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author', 'revisions', 'custom-fields' ),
				'taxonomies'          => array( 'cam_categoria_avviso' ),
				'menu_icon'           => 'dashicons-megaphone',
				'delete_with_user'    => false,
			)
		);
	}

	/** Eventi comunali. */
	private function register_eventi(): void {
		$labels = array(
			'name'               => _x( 'Eventi', 'post type general name', 'comune-app-manager' ),
			'singular_name'      => _x( 'Evento', 'post type singular name', 'comune-app-manager' ),
			'menu_name'          => _x( 'Eventi', 'admin menu', 'comune-app-manager' ),
			'add_new'            => __( 'Aggiungi nuovo', 'comune-app-manager' ),
			'add_new_item'       => __( 'Aggiungi nuovo evento', 'comune-app-manager' ),
			'edit_item'          => __( 'Modifica evento', 'comune-app-manager' ),
			'new_item'           => __( 'Nuovo evento', 'comune-app-manager' ),
			'view_item'          => __( 'Visualizza evento', 'comune-app-manager' ),
			'search_items'       => __( 'Cerca eventi', 'comune-app-manager' ),
			'not_found'          => __( 'Nessun evento trovato.', 'comune-app-manager' ),
			'not_found_in_trash' => __( 'Nessun evento nel cestino.', 'comune-app-manager' ),
		);

		register_post_type(
			'comune_evento',
			array(
				'labels'              => $labels,
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'show_in_rest'        => true,
				'rest_base'           => 'eventi',
				'capability_type'     => 'post',
				'has_archive'         => true,
				'rewrite'             => array( 'slug' => 'eventi', 'with_front' => false ),
				'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author', 'revisions', 'custom-fields' ),
				'taxonomies'          => array( 'cam_categoria_evento' ),
				'menu_icon'           => 'dashicons-calendar-alt',
				'delete_with_user'    => false,
			)
		);
	}

	/** Uffici e sportelli comunali. */
	private function register_uffici(): void {
		$labels = array(
			'name'               => _x( 'Uffici', 'post type general name', 'comune-app-manager' ),
			'singular_name'      => _x( 'Ufficio', 'post type singular name', 'comune-app-manager' ),
			'menu_name'          => _x( 'Uffici', 'admin menu', 'comune-app-manager' ),
			'add_new'            => __( 'Aggiungi nuovo', 'comune-app-manager' ),
			'add_new_item'       => __( 'Aggiungi nuovo ufficio', 'comune-app-manager' ),
			'edit_item'          => __( 'Modifica ufficio', 'comune-app-manager' ),
			'new_item'           => __( 'Nuovo ufficio', 'comune-app-manager' ),
			'view_item'          => __( 'Visualizza ufficio', 'comune-app-manager' ),
			'search_items'       => __( 'Cerca uffici', 'comune-app-manager' ),
			'not_found'          => __( 'Nessun ufficio trovato.', 'comune-app-manager' ),
			'not_found_in_trash' => __( 'Nessun ufficio nel cestino.', 'comune-app-manager' ),
		);

		register_post_type(
			'comune_ufficio',
			array(
				'labels'              => $labels,
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'show_in_rest'        => true,
				'rest_base'           => 'uffici',
				'capability_type'     => 'post',
				'has_archive'         => true,
				'rewrite'             => array( 'slug' => 'uffici', 'with_front' => false ),
				'supports'            => array( 'title', 'editor', 'thumbnail', 'revisions', 'custom-fields' ),
				'menu_icon'           => 'dashicons-building',
				'delete_with_user'    => false,
			)
		);
	}

	/** Luoghi di interesse sul territorio. */
	private function register_luoghi(): void {
		$labels = array(
			'name'               => _x( 'Luoghi', 'post type general name', 'comune-app-manager' ),
			'singular_name'      => _x( 'Luogo', 'post type singular name', 'comune-app-manager' ),
			'menu_name'          => _x( 'Luoghi', 'admin menu', 'comune-app-manager' ),
			'add_new'            => __( 'Aggiungi nuovo', 'comune-app-manager' ),
			'add_new_item'       => __( 'Aggiungi nuovo luogo', 'comune-app-manager' ),
			'edit_item'          => __( 'Modifica luogo', 'comune-app-manager' ),
			'new_item'           => __( 'Nuovo luogo', 'comune-app-manager' ),
			'view_item'          => __( 'Visualizza luogo', 'comune-app-manager' ),
			'search_items'       => __( 'Cerca luoghi', 'comune-app-manager' ),
			'not_found'          => __( 'Nessun luogo trovato.', 'comune-app-manager' ),
			'not_found_in_trash' => __( 'Nessun luogo nel cestino.', 'comune-app-manager' ),
		);

		register_post_type(
			'comune_luogo',
			array(
				'labels'              => $labels,
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'show_in_rest'        => true,
				'rest_base'           => 'luoghi',
				'capability_type'     => 'post',
				'has_archive'         => true,
				'rewrite'             => array( 'slug' => 'luoghi', 'with_front' => false ),
				'supports'            => array( 'title', 'editor', 'thumbnail', 'revisions', 'custom-fields' ),
				'taxonomies'          => array( 'cam_categoria_luogo' ),
				'menu_icon'           => 'dashicons-location',
				'delete_with_user'    => false,
			)
		);
	}

	/** Documenti e modulistica scaricabile. */
	private function register_documenti(): void {
		$labels = array(
			'name'               => _x( 'Documenti', 'post type general name', 'comune-app-manager' ),
			'singular_name'      => _x( 'Documento', 'post type singular name', 'comune-app-manager' ),
			'menu_name'          => _x( 'Documenti', 'admin menu', 'comune-app-manager' ),
			'add_new'            => __( 'Aggiungi nuovo', 'comune-app-manager' ),
			'add_new_item'       => __( 'Aggiungi nuovo documento', 'comune-app-manager' ),
			'edit_item'          => __( 'Modifica documento', 'comune-app-manager' ),
			'new_item'           => __( 'Nuovo documento', 'comune-app-manager' ),
			'view_item'          => __( 'Visualizza documento', 'comune-app-manager' ),
			'search_items'       => __( 'Cerca documenti', 'comune-app-manager' ),
			'not_found'          => __( 'Nessun documento trovato.', 'comune-app-manager' ),
			'not_found_in_trash' => __( 'Nessun documento nel cestino.', 'comune-app-manager' ),
		);

		register_post_type(
			'comune_documento',
			array(
				'labels'              => $labels,
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'show_in_rest'        => true,
				'rest_base'           => 'documenti',
				'capability_type'     => 'post',
				'has_archive'         => true,
				'rewrite'             => array( 'slug' => 'documenti', 'with_front' => false ),
				'supports'            => array( 'title', 'editor', 'excerpt', 'revisions', 'custom-fields' ),
				'taxonomies'          => array( 'cam_categoria_documento' ),
				'menu_icon'           => 'dashicons-media-document',
				'delete_with_user'    => false,
			)
		);
	}
}
