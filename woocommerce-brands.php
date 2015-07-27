<?php
/**
 * @link              TBC
 * @since             0.1.0
 * @package           woocommerce_brands
 *
 * @wordpress-plugin
 * Plugin Name:				Woocommerce Brands
 * Plugin URI:        TBC
 * Description:       Adds custom post type for brands. E.g "McDonalds", or "KFC". Adds this to the Woocommerce product taxonomy and allows customers to filter products by it
 * Version:           0.1.0
 * Author:            Carl Evans
 * Author URI:        TBC
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       TBC
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/woocommerce_brands-activator.php
 */
function activate_woocommerce_brands() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/woocommerce_brands-activator.php';
	Woocommerce_Brands_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/woocommerce_brands-deactivator.php
 */
function deactivate_woocommerce_brands() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/woocommerce_brands-deactivator.php';
	Woocommerce_Brands_Deactivator::deactivate();
}

/**
 * The code that creates the custom post type
 * This action is documented in includes/woocommerce_brands-activator.php
 */
function create_custom_post_type() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/woocommerce_brands-activator.php';
	Woocommerce_Brands_Activator::setup_post_type();
}

add_action( 'init', 'create_custom_post_type');
register_activation_hook( __FILE__, 'activate_woocommerce_brands' );
register_deactivation_hook( __FILE__, 'deactivate_woocommerce_brands' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/woocommerce_brands.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    0.1.0
 */
function run_woocommerce_brands() {

	$plugin = new Woocommerce_Brands();
	$plugin->run();

}
run_woocommerce_brands();
