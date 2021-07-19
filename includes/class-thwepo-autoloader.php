<?php
/**
 * Auto-loads the required dependencies for this plugin.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEPO_Autoloader')):

class THWEPO_Autoloader {
	private $include_path = '';

	private $compatibility_classes = array(
		'WEPO_WPML_Currency_Switcher_Handler',
	);
	
	private $class_path = array(
		'wepo_condition' => 'includes/model/rules/class-wepo-condition.php',
		'wepo_condition_set' => 'includes/model/rules/class-wepo-condition-set.php',
		'wepo_condition_rule' => 'includes/model/rules/class-wepo-rule.php',
		'wepo_condition_rule_set' => 'includes/model/rules/class-wepo-rule-set.php',
		
		'wepo_product_page_section' => 'includes/model/class-wepo-section.php',
		'wepo_product_field' => 'includes/model/fields/class-wepo-field.php',
		'wepo_product_field_inputtext' => 'includes/model/fields/class-wepo-field-inputtext.php',
		'wepo_product_field_hidden' => 'includes/model/fields/class-wepo-field-hidden.php',
		'wepo_product_field_password' => 'includes/model/fields/class-wepo-field-password.php',
		'wepo_product_field_number' => 'includes/model/fields/class-wepo-field-number.php',
		'wepo_product_field_tel' => 'includes/model/fields/class-wepo-field-tel.php',
		'wepo_product_field_textarea' => 'includes/model/fields/class-wepo-field-textarea.php',				
		'wepo_product_field_select' => 'includes/model/fields/class-wepo-field-select.php',
		'wepo_product_field_multiselect' => 'includes/model/fields/class-wepo-field-multiselect.php',
		'wepo_product_field_radio' => 'includes/model/fields/class-wepo-field-radio.php',
		'wepo_product_field_checkbox' => 'includes/model/fields/class-wepo-field-checkbox.php',
		'wepo_product_field_checkboxgroup' => 'includes/model/fields/class-wepo-field-checkboxgroup.php',
		'wepo_product_field_datepicker' => 'includes/model/fields/class-wepo-field-datepicker.php',
		'wepo_product_field_timepicker' => 'includes/model/fields/class-wepo-field-timepicker.php',
		'wepo_product_field_file' => 'includes/model/fields/class-wepo-field-file.php',
		'wepo_product_field_heading' => 'includes/model/fields/class-wepo-field-heading.php',
		'wepo_product_field_html' => 'includes/model/fields/class-wepo-field-html.php',
		'wepo_product_field_label' => 'includes/model/fields/class-wepo-field-label.php',
		'wepo_product_field_colorpicker' => 'includes/model/fields/class-wepo-field-colorpicker.php',

		'wepo_wpml_currency_switcher_handler' => 'includes/compatibility/class-wepo-wpml-currency-switcher-handler.php',
	);

	public function __construct() {
		$this->include_path = untrailingslashit(THWEPO_PATH);
		
		if(function_exists("__autoload")){
			spl_autoload_register("__autoload");
		}
		spl_autoload_register(array($this, 'autoload'));
	}

	/** Include a class file. */
	private function load_file( $path ) {
		if ( $path && is_readable( $path ) ) {
			require_once( $path );
			return true;
		}
		return false;
	}
	
	public function autoload_( $class ) {
		if(isset($this->class_path[$class])){
			$file = $this->class_path[$class];
			$this->load_file( TH_WEPO_PATH.$file );
		}
	}
	
	/** Class name to file name. */
	private function get_file_name_from_class( $class ) {
		return 'class-' . str_replace( '_', '-', $class ) . '.php';
	}
	
	public function autoload( $class ) {
		$class = strtolower( $class );
		$file  = $this->get_file_name_from_class( $class );
		$path  = '';
		$file_path  = '';
		if (isset($this->class_path[$class])){
			$file_path = $this->include_path . '/' . $this->class_path[$class];
		} else {
			if (strpos($class, 'thwepo_admin') === 0){
				$path = $this->include_path . '/admin/';
			} elseif (strpos($class, 'thwepo_public') === 0){
				$path = $this->include_path . '/public/';
			} elseif (strpos($class, 'thwepo_utils') === 0){
				$path = $this->include_path . '/includes/utils/';
			} else{
				$path = $this->include_path . '/includes/';
			}
			$file_path = $path . $file;
		}
		
		if( empty($file_path) || (!$this->load_file($file_path) && strpos($class, 'thwepo_') === 0) ) {
			$this->load_file( $this->include_path . $file );
		}
	}
}

endif;

new THWEPO_Autoloader();
