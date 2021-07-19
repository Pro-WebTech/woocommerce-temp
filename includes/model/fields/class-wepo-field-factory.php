<?php
/**
 * Product field factory.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes/model/fields
 */
if(!defined('WPINC')){	die; }

if(!class_exists('WEPO_Product_Field_Factory')):

class WEPO_Product_Field_Factory {
	public function __construct() {
		
	}	
	
	public function create_field($type){
		if(isset($type)){
			if($type === 'inputtext'){
				return new WEPO_Product_Field_InputText();
			}if($type === 'hidden'){
				return new WEPO_Product_Field_Hidden();
			}else if($type === 'password'){
				return new WEPO_Product_Field_Password();
			}else if($type === 'textarea'){
				return new WEPO_Product_Field_Textarea();
			}else if($type === 'select'){
				return new WEPO_Product_Field_Select();
			}else if($type === 'multiselect'){
				return new WEPO_Product_Field_Multiselect();
			}else if($type === 'radio'){
				return new WEPO_Product_Field_Radio();
			}else if($type === 'checkbox'){
				return new WEPO_Product_Field_Checkbox();
			}else if($type === 'checkboxgroup'){
				return new WEPO_Product_Field_CheckboxGroup();
			}else if($type === 'datepicker'){
				return new WEPO_Product_Field_DatePicker();
			}else if($type === 'timepicker'){
				return new WEPO_Product_Field_TimePicker();
			}else if($type === 'heading'){
				return new WEPO_Product_Field_Heading();
			}else if($type === 'html'){
				return new WEPO_Product_Field_HTML();
			}else if($type === 'label'){
				return new WEPO_Product_Field_Label();
			}			
		}
		return false;
	}	
}

endif;