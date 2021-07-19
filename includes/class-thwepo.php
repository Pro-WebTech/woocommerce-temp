<?php
/**
 * The file that defines the core plugin class.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEPO')):

class THWEPO {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    2.3.0
	 * @access   protected
	 * @var      THWEPO_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    2.3.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    2.3.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;
	
	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    2.3.0
	 */
	public function __construct() {
		if ( defined( 'THWEPO_VERSION' ) ) {
			$this->version = THWEPO_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'woocommerce-extra-product-options-pro';
		
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		
		$this->loader->add_action( 'init', $this, 'init' );

		$this->set_compatibility();
	}
	
	public function init(){
		$this->define_constants();
		//$this->init_auto_updater();
	}
	
	private function define_constants(){
		!defined('THWEPO_ASSETS_URL_ADMIN') && define('THWEPO_ASSETS_URL_ADMIN', THWEPO_URL . 'admin/assets/');
		!defined('THWEPO_ASSETS_URL_PUBLIC') && define('THWEPO_ASSETS_URL_PUBLIC', THWEPO_URL . 'public/assets/');
		!defined('THWEPO_WOO_ASSETS_URL') && define('THWEPO_WOO_ASSETS_URL', WC()->plugin_url() . '/assets/');
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - THWEPO_Loader. Orchestrates the hooks of the plugin.
	 * - THWEPO_i18n. Defines internationalization functionality.
	 * - THWEPO_Admin. Defines all hooks for the admin area.
	 * - THWEPO_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    2.3.0
	 * @access   private
	 */
	private function load_dependencies() {
		if(!function_exists('is_plugin_active')){
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-thwepo-autoloader.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-thwepo-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-thwepo-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-thwepo-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-thwepo-public.php';

		$this->loader = new THWEPO_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the THWEPO_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    2.3.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new THWEPO_i18n($this->get_plugin_name());
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}
	
	private function init_auto_updater(){
		if(!class_exists('THWEPO_Auto_Update_License') ) {
			$api_url = 'https://themehigh.com/';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'class-thwepo-auto-update-license.php';
			THWEPO_Auto_Update_License::instance(__FILE__, THWEPO_SOFTWARE_TITLE, THWEPO_VERSION, 'plugin', $api_url, THWEPO_i18n::TEXT_DOMAIN);
		}
	}
	
	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    2.3.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new THWEPO_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles_and_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'admin_menu' );
		$this->loader->add_filter( 'woocommerce_screen_ids', $plugin_admin, 'add_screen_id' );
		$this->loader->add_filter( 'plugin_action_links_'.THWEPO_BASE_NAME, $plugin_admin, 'plugin_action_links' );
		$this->loader->add_filter( 'plugin_row_meta', $plugin_admin, 'plugin_row_meta', 10, 2 );

		$wepo_data = THWEPO_Data::instance();

		$this->loader->add_action('wp_ajax_thwepo_load_products', $wepo_data, 'load_products_ajax');
    	$this->loader->add_action('wp_ajax_nopriv_thwepo_load_products', $wepo_data, 'load_products_ajax');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    2.3.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new THWEPO_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles_and_scripts', 20 );
	}

	/**
	 * Include files to provide compatibility with requested plugins
	 *
	 *
	 * @since    2.3.0
	 * @access   private
	 *
	 * @return null
	 */
	private function set_compatibility(){
		if( apply_filters( 'thwepo_wpml_currency_swithcer_compatibility', false  ) ){
			new WEPO_WPML_Currency_Switcher_Handler();
		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    2.3.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     2.3.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     2.3.0
	 * @return    THWEPO_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     2.3.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}

endif;