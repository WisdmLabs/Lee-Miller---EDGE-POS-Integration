<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.wisdmlabs.com
 * @since             1.0.0
 * @package           Wdm_Edge_Integration
 *
 * @wordpress-plugin
 * Plugin Name:       WDM EDGE Integration
 * Plugin URI:        https://www.wisdmlabs.com
 * Description:       Creates a sync between the EDGE and wordpress site woocommerce store.
 * Version:           1.0.0
 * Author:            WisdmLabs
 * Author URI:        https://www.wisdmlabs.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wdm-edge-integration
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WDM_EDGE_INTEGRATION_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wdm-edge-integration-activator.php
 */
function activate_wdm_edge_integration() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wdm-edge-integration-activator.php';
	Wdm_Edge_Integration_Activator::activate();
	
	// Also run the admin class activation
	require_once plugin_dir_path( __FILE__ ) . 'admin/class-wdm-edge-integration-admin.php';
	Wdm_Edge_Integration_Admin::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wdm-edge-integration-deactivator.php
 */
function deactivate_wdm_edge_integration() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wdm-edge-integration-deactivator.php';
	Wdm_Edge_Integration_Deactivator::deactivate();
	
	// Also run the admin class deactivation
	require_once plugin_dir_path( __FILE__ ) . 'admin/class-wdm-edge-integration-admin.php';
	Wdm_Edge_Integration_Admin::deactivate();
}

register_activation_hook( __FILE__, 'activate_wdm_edge_integration' );
register_deactivation_hook( __FILE__, 'deactivate_wdm_edge_integration' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wdm-edge-integration.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wdm_edge_integration() {

	$plugin = new Wdm_Edge_Integration();
	$plugin->run();

}
run_wdm_edge_integration();
