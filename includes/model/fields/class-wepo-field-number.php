<?php
/**
 * Custom product field Number data object.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes/model/fields
 */
if(!defined('WPINC')){	die; }

if(!class_exists('WEPO_Product_Field_Number')):

class WEPO_Product_Field_Number extends WEPO_Product_Field{
	public function __construct() {
		$this->type = 'number';
	}
}

endif;