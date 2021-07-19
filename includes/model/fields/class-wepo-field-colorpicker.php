<?php
/**
 * Custom product field Color Picker data object.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes/model/fields
 */
if(!defined('WPINC')){	die; }

if(!class_exists('WEPO_Product_Field_ColorPicker')):

class WEPO_Product_Field_ColorPicker extends WEPO_Product_Field{
	public $tooltip = '';
	public $colorpicker_style = '';
	public $colorpicker_radius = '';
	public $colorpreview_radius = '';

	public function __construct() {
		$this->type = 'colorpicker';
	}	
}

endif;