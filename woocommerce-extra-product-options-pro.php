<?php
/**
 * Plugin Name:       WooCommerce Extra Product Options Pro
 * Plugin URI:        https://themehigh.com/product/woocommerce-extra-product-options
 * Description:       Design woocommerce Product form in your own way, customize Product fields(Add, Edit, Delete and re arrange fields).
 * Version:           3.0.3
 * Author:            ThemeHigh
 * Author URI:        https://themehigh.com/
 *
 * Text Domain:       woocommerce-extra-product-options-pro
 * Domain Path:       /languages
 *
 * WC requires at least: 3.0.0
 * WC tested up to: 4.8.0
 */

if(!defined('WPINC')){	die; }

if (!function_exists('is_woocommerce_active')){
	function is_woocommerce_active(){
	    $active_plugins = (array) get_option('active_plugins', array());
	    if(is_multisite()){
		   $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
	    }
	    return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins) || class_exists('WooCommerce');
	}
}

if(is_woocommerce_active()) {
	define('THWEPO_VERSION', '3.0.3');
	!defined('THWEPO_SOFTWARE_TITLE') && define('THWEPO_SOFTWARE_TITLE', 'WooCommerce Extra Product Options');
	!defined('THWEPO_FILE') && define('THWEPO_FILE', __FILE__);
	!defined('THWEPO_PATH') && define('THWEPO_PATH', plugin_dir_path( __FILE__ ));
	!defined('THWEPO_URL') && define('THWEPO_URL', plugins_url( '/', __FILE__ ));
	!defined('THWEPO_BASE_NAME') && define('THWEPO_BASE_NAME', plugin_basename( __FILE__ ));
	
	/**
	 * The code that runs during plugin activation.
	 */
	function activate_thwepo() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-thwepo-activator.php';
		THWEPO_Activator::activate();
	}
	
	/**
	 * The code that runs during plugin deactivation.
	 */
	function deactivate_thwepo() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-thwepo-deactivator.php';
		THWEPO_Deactivator::deactivate();
	}
	
	register_activation_hook( __FILE__, 'activate_thwepo' );
	register_deactivation_hook( __FILE__, 'deactivate_thwepo' );

	function thwepo_license_form_title_note($title_note){
		$help_doc_url = 'https://www.themehigh.com/help-guides/general-guides/download-purchased-plugin-file';

		$title_note .= ' Find out how to <a href="%s" target="_blank">get your license key</a>.';
		$title_note  = sprintf($title_note, $help_doc_url);
		return $title_note;
	}
	
	function thwepo_license_page_url($url, $prefix){
		$url = 'edit.php?post_type=product&page=th_extra_product_options_pro&tab=license_settings';
		return admin_url($url);
	}

	function init_auto_updater_thwepo(){
		if(!class_exists('THWEPO_License_Manager') ) {
			add_filter('thlm_license_form_title_note_woocommerce_extra_product_options', 'thwepo_license_form_title_note');
			add_filter('thlm_license_page_url_woocommerce_extra_product_options', 'thwepo_license_page_url', 10, 2);
			add_filter('thlm_enable_default_license_page', '__return_false');

			require_once( plugin_dir_path( __FILE__ ) . 'class-thwepo-license-manager.php' );
			$api_url = 'https://themehigh.com/';
			THWEPO_License_Manager::instance(__FILE__, $api_url, 'plugin', THWEPO_SOFTWARE_TITLE);
		}
	}
	init_auto_updater_thwepo();
	
	/**
	 * The core plugin class that is used to define internationalization,
	 * admin-specific hooks, and public-facing site hooks.
	 */
	require plugin_dir_path( __FILE__ ) . 'includes/class-thwepo.php';
	
	/**
	 * Begins execution of the plugin.
	 */
	function run_thwepo() {
		$plugin = new THWEPO();
		$plugin->run();
	}
	run_thwepo();
}