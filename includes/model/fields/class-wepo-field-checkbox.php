<?php
/**
 * Custom product field Checkbox data object.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes/model/fields
 */
if(!defined('WPINC')){	die; }

if(!class_exists('WEPO_Product_Field_Checkbox')):

class WEPO_Product_Field_Checkbox extends WEPO_Product_Field{
	public $checked = false;
	
	public function __construct() {
		$this->type = 'checkbox';
	}
}

endif;