<?php
/**
 * Custom product field Radio data object.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes/model/fields
 */
if(!defined('WPINC')){	die; }

if(!class_exists('WEPO_Product_Field_Radio')):

class WEPO_Product_Field_Radio extends WEPO_Product_Field{
	public $options = array();
	
	public function __construct() {
		$this->type = 'radio';
	}
}

endif;