<?php
/**
 * The admin license settings page functionality of the plugin.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/admin
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEPO_Admin_Settings_License')):

class THWEPO_Admin_Settings_License extends THWEPO_Admin_Settings{
	protected static $_instance = null;
	
	public $ame_data_key;
	public $ame_deactivate_checkbox;
	public $ame_activation_tab_key;
	public $ame_deactivation_tab_key;
	
	public function __construct() {
		parent::__construct('license_settings');
		
		$this->data_prefix = str_ireplace( array( ' ', '_', '&', '?' ), '_', strtolower(THWEPO_SOFTWARE_TITLE) );
		$this->data_prefix = str_ireplace( 'woocommerce', 'th', $this->data_prefix );
		$this->ame_data_key             = $this->data_prefix . '_data';
		$this->ame_deactivate_checkbox  = $this->data_prefix . '_deactivate_checkbox';
		$this->ame_activation_tab_key   = $this->data_prefix . '_license_activate';
		$this->ame_deactivation_tab_key = $this->data_prefix . '_license_deactivate';		
	}
	
	public static function instance() {
		if(is_null(self::$_instance)){
			self::$_instance = new self();
		}
		return self::$_instance;
	} 
	
	public function render_page(){
		settings_errors();
		$this->render_tabs();
		$this->render_content();
	}

	private function render_content(){
		echo do_shortcode('[licensepage_woocommerce_extra_product_options]');
	}
}

endif;