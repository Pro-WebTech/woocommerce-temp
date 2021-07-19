<?php
/**
 * Custom product field Label data object.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes/model/fields
 */
if(!defined('WPINC')){	die; }

if(!class_exists('WEPO_Product_Field_Label')):

class WEPO_Product_Field_Label extends WEPO_Product_Field{
	public function __construct() {
		$this->type = 'label';
	}
}

endif;