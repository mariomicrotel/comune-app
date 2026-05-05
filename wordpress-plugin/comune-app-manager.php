<?php
/**
 * Plugin Name:       Comune App Manager
 * Plugin URI:        https://github.com/comune-dev/comune-app-manager
 * Description:       Gestione completa dei contenuti per l'app mobile del Comune: avvisi, eventi, uffici, luoghi, documenti, calendario raccolta rifiuti, sondaggi e notifiche push.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Comune Dev
 * Author URI:        https://comune-dev.it
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       comune-app-manager
 * Domain Path:       /languages
 *
 * @package ComuneAppManager
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin version.
define( 'CAM_VERSION', '1.0.0' );

// Absolute path to the plugin directory (with trailing slash).
define( 'CAM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// URL to the plugin directory (with trailing slash).
define( 'CAM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Plugin basename: "comune-app-manager/comune-app-manager.php".
define( 'CAM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// ---------------------------------------------------------------------------
// Require all class files
// ---------------------------------------------------------------------------

require_once CAM_PLUGIN_DIR . 'includes/class-logger.php';
require_once CAM_PLUGIN_DIR . 'includes/class-utils.php';
require_once CAM_PLUGIN_DIR . 'includes/class-capabilities.php';
require_once CAM_PLUGIN_DIR . 'includes/class-activator.php';
require_once CAM_PLUGIN_DIR . 'includes/class-deactivator.php';
require_once CAM_PLUGIN_DIR . 'includes/class-db.php';
require_once CAM_PLUGIN_DIR . 'includes/class-post-types.php';
require_once CAM_PLUGIN_DIR . 'includes/class-taxonomies.php';
require_once CAM_PLUGIN_DIR . 'includes/class-meta-boxes.php';
require_once CAM_PLUGIN_DIR . 'includes/class-firebase.php';
require_once CAM_PLUGIN_DIR . 'includes/class-permissions.php';
require_once CAM_PLUGIN_DIR . 'includes/class-rest-api.php';
require_once CAM_PLUGIN_DIR . 'includes/class-admin-menu.php';
require_once CAM_PLUGIN_DIR . 'includes/class-settings.php';
require_once CAM_PLUGIN_DIR . 'includes/class-reports.php';
require_once CAM_PLUGIN_DIR . 'includes/class-waste-calendar.php';
require_once CAM_PLUGIN_DIR . 'includes/class-surveys.php';
require_once CAM_PLUGIN_DIR . 'includes/class-push-notifications.php';
require_once CAM_PLUGIN_DIR . 'includes/class-demo-seeder.php';
require_once CAM_PLUGIN_DIR . 'includes/class-plugin.php';

// ---------------------------------------------------------------------------
// Activation / deactivation hooks
// ---------------------------------------------------------------------------

register_activation_hook( __FILE__, array( 'Comune_App_Manager_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Comune_App_Manager_Deactivator', 'deactivate' ) );

// ---------------------------------------------------------------------------
// Bootstrap
// ---------------------------------------------------------------------------

/**
 * Returns the singleton instance of the main plugin class.
 *
 * @return Comune_App_Manager_Plugin
 */
function comune_app_manager(): Comune_App_Manager_Plugin {
	static $instance = null;
	if ( null === $instance ) {
		$instance = new Comune_App_Manager_Plugin();
	}
	return $instance;
}

// Kick off the plugin.
comune_app_manager()->run();
