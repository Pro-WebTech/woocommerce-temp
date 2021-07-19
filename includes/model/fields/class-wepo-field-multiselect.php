<?php
/**
 * Custom product field Multiselect data object.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes/model/fields
 */
if(!defined('WPINC')){	die; }

if(!class_exists('WEPO_Product_Field_Multiselect')):

class WEPO_Product_Field_Multiselect extends WEPO_Product_Field{
	public function __construct() {
		$this->type = 'multiselect';
	}
}

endif;