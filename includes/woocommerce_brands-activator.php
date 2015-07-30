<?php

/**
 * Fired during plugin activation
 *
 * @link       TBC
 * @since      0.1.0
 *
 * @package    Woocommerce_Brands
 * @subpackage Woocommerce_Brands/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      0.1.0
 * @package    Woocommerce_Brands
 * @subpackage Woocommerce_Brands/includes
 * @author     Carl Evans <carlevans719@msn.com>
 */
class Woocommerce_Brands_Activator {

	/**
	 * Activates the plugin.
	 *
	 * Sets up the CPT and flushes rewrite rules.
	 *
	 * @since    0.1.0
	 */
	public static function activate() {
		// add cpt
		if (!self::setup_post_type()) {
			die("Unable to create custom post type");
		};

		// add version
		if (!defined('WCB_VERSION_KEY'))
	    define('WCB_VERSION_KEY', 'wcb_version');

		if (!defined('WCB_VERSION_NUMBER'))
	    define('WCB_VERSION_NUMBER', '0.1.0');

		add_option(WCB_VERSION_KEY, WCB_VERSION_NUMBER);

		// flush
    flush_rewrite_rules();
	}


	/**
	 * Sets up the CPT "Brands".
	 *
	 * Sets up the CPT for Brands.
	 *
	 * @since    0.1.0
	 */
	public static function setup_post_type() {
	  $labels = array(
	    "name" => "Brands/Manufacturers of products",
	    "singular_name" => "Product Brand/Manufacturer",
	    "menu_name" => "Product Brands",
	    "name_admin_bar" => "Product Brand",
	    "all_items" => "Brands",
	    "add_new" => "Add brand",
	    "add_new_item" => "Add new brand",
	    "edit_item" => "Edit brand",
	    "new_item" => "New brand",
	    "view_item" => "View brand",
	    "search_items" => "Search brands",
	    "not_found" => "No brands found",
	    "not_found_in_trash" => "No brands found in trash",
	    "parent_item_colon" => "Parent brand"
	  );
	  $args = array(
	    "labels" => $labels,
	    "description" => "A brand or manufacturer to assign to Woocommerce products. Products can then be filtered by this.",
	    "public" => true,
	    "menu_position" => 5,
	    "menu_icon" => "dashicons-tag",
	    "supports" => array(
	      0 => "title",
	      1 => "thumbnail"
	    )
	  );
	  register_post_type('wcb_brand', $args);
		return true;
	}

}
