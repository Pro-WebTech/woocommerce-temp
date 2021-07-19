<?php
/**
 * Custom product field TimePicker data object.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes/model/fields
 */
if(!defined('WPINC')){	die; }

if(!class_exists('WEPO_Product_Field_TimePicker')):

class WEPO_Product_Field_TimePicker extends WEPO_Product_Field{
	public $min_time = '';
	public $max_time = '';
	public $time_step = '';
	public $time_format = '';
	
	public function __construct() {
		$this->type = 'timepicker';
	}
}

endif;