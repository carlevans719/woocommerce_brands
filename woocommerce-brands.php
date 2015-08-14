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

$wcb_latest_version = '0.1.0';
if (!defined('WCB_VERSION_KEY'))
	define('WCB_VERSION_KEY', 'wcb__currentversion');

if (!defined('WCB_PLUGIN_NAME'))
	define('WCB_PLUGIN_NAME', trim(dirname(plugin_basename(__FILE__)), '/'));

if (!defined('WCB_PLUGIN_DIR'))
	define('WCB_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . WCB_PLUGIN_NAME);

if (!defined('WCB_PLUGIN_URL'))
	define('WCB_PLUGIN_URL', WP_PLUGIN_URL . '/' . WCB_PLUGIN_NAME);

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) )
	die;

// upgrade info
if (get_option(WCB_VERSION_KEY) != $wcb_latest_version) {
	$res = wcb_upgrade();
	if ($res['status'] == "success") {
  	update_option(WCB_VERSION_KEY, $wcb_latest_version);
	} else {
		logit($res);
	}
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
 */
function wcb_create_custom_post_type() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/woocommerce_brands-activator.php';
	Woocommerce_Brands_Activator::setup_post_type();
}

/**
 * The code that sets up the post activation actions
 */
function post_init_actions() {
	wcb_create_custom_post_type();
}
add_action( 'init', 'post_init_actions');

register_activation_hook( __FILE__, 'activate_woocommerce_brands' );
register_deactivation_hook( __FILE__, 'deactivate_woocommerce_brands' );


function wcb_add_custom_fields() {
	global $woocommerce, $post;

	  $output = '<div class="options_group">';
		$args = array('post_type' => 'wcb_brand');
		$the_query = null;
		$the_query = new WP_Query($args);
		if( $the_query->have_posts() ) {
			$brand_ids = $brand_names = array();
			foreach ($the_query->get_posts() as $key => $value) {
				$brand_ids[] = $value->ID;
				$brand_names[] = $value->post_title;
			}
			$select_options = array_combine($brand_ids, $brand_names);
		}
		wp_reset_query();  // Restore global post data stomped by the_post().
		woocommerce_wp_select(
			array(
				'id'      => 'brand_select',
				'label'   => 'Product\'s Brand',
				'options' => $select_options
				)
			);
	  // Custom fields will be created here...

	  $output .= '</div>';
	  echo $output;
}
add_action( 'woocommerce_product_options_general_product_data', 'wcb_add_custom_fields' );


function wcb_save_custom_fields( $post_id ) {
	$woocommerce_select = $_POST['brand_select'];
	if( !empty( $woocommerce_select ) ) {
		update_post_meta( $post_id, 'wcb_brand', esc_attr( $woocommerce_select ) );
	}
}
add_action( 'woocommerce_process_product_meta', 'wcb_save_custom_fields' );


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

function wcb_upgrade() {
	return array("status" => 'success');
}

function logit($message) {
    if (WP_DEBUG === true) {
        if (is_array($message) || is_object($message)) {
            error_log(print_r($message, true));
        } else {
            error_log($message);
        }
    }
}
