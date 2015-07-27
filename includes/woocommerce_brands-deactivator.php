<?php

/**
 * Fired during plugin deactivation
 *
 * @link       TBC
 * @since      0.1.0
 *
 * @package    Woocommerce_Brands
 * @subpackage Woocommerce_Brands/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      0.1.0
 * @package    Woocommerce_Brands
 * @subpackage Woocommerce_Brands/includes
 * @author     Carl Evans <carlevans719@msn.com>
 */
class Woocommerce_Brands_Deactivator {

	/**
	 * Deactivates the plugin.
	 *
	 * Flushes rewrite rules.
	 *
	 * @since    0.1.0
	 */
	public static function deactivate() {
	  flush_rewrite_rules();
	}

}
