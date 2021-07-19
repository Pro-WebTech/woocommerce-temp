<?php
/**
 * Custom product field File data object.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes/model/fields
 */
if(!defined('WPINC')){	die; }

if(!class_exists('WEPO_Product_Field_File')):

class WEPO_Product_Field_File extends WEPO_Product_Field{
	public $maxsize = false;
	public $accept = false;
	public $minfiles = false;
	public $maxfiles = false;
	public $multiple_file = false;
	
	public function __construct() {
		$this->type = 'file';
	}	
}

endif;