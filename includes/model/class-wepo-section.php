<?php
/**
 * Custom section data object.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes/model
 */
if(!defined('WPINC')){	die; }

if(!class_exists('WEPO_Product_Page_Section')):

class WEPO_Product_Page_Section{
	public $id = '';
	public $name = '';
	public $position = '';
	public $order = '';
	public $type = '';
	public $cssclass = '';
	
	public $title_cell_with = '';
	public $field_cell_with = '';
	
	public $show_title = 1;
		
	public $title = '';
	public $title_type  = '';
	public $title_color = '';
	public $title_position = '';
	public $title_class = '';
	
	public $subtitle = '';
	public $subtitle_type  = '';
	public $subtitle_color = '';
	public $subtitle_position = '';
	public $subtitle_class = '';
	
	public $cssclass_str = '';
	public $title_class_str = '';
	public $subtitle_class_str = '';
	
	public $rules_action = '';
	public $rules_action_ajax = '';
	
	public $conditional_rules_json = '';
	public $conditional_rules = array();
	
	public $conditional_rules_ajax_json = '';
	public $conditional_rules_ajax = array();
	
	public $condition_sets = array();
	public $fields = array();
	
	public function __construct() {
	}
	
	public function set_property($name, $value){
		if(property_exists($this, $name)){
			$this->$name = $value;
		}
	}
	
	public function get_property($name){
		if(property_exists($this, $name)){
			return $this->$name;
		}else{
			return '';
		}
	}
}

endif;