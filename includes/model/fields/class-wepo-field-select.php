<?php
/**
 * Custom product field Select data object.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes/model/fields
 */
if(!defined('WPINC')){	die; }

if(!class_exists('WEPO_Product_Field_Select')):

class WEPO_Product_Field_Select extends WEPO_Product_Field{
	public function __construct() {
		$this->type = 'select';
	}
}

endif;