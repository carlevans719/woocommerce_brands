<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       TBC
 * @since      0.1.0
 *
 * @package    Woocommerce_Brands
 * @subpackage Woocommerce_Brands/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woocommerce_Brands
 * @subpackage Woocommerce_Brands/public
 * @author     Carl Evans <carlevans719@msn.com>
 */
class Woocommerce_Brands_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    0.1.0
	 * @access   private
	 * @var      string    $Woocommerce_Brands    The ID of this plugin.
	 */
	private $Woocommerce_Brands;

	/**
	 * The version of this plugin.
	 *
	 * @since    0.1.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.1.0
	 * @param      string    $Woocommerce_Brands       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $Woocommerce_Brands, $version ) {

		$this->Woocommerce_Brands = $Woocommerce_Brands;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    0.1.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woocommerce_Brands_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woocommerce_Brands_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->Woocommerce_Brands, plugin_dir_url( __FILE__ ) . 'css/woocommerce_brands-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    0.1.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woocommerce_Brands_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woocommerce_Brands_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		// wp_enqueue_script( $this->Woocommerce_Brands, plugin_dir_url( __FILE__ ) . 'js/woocommerce_brands-public.js', array( 'jquery' ), $this->version, false );

	}


}
