<?php
/**
 * Display condition data object.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes/model/rules
 */
if(!defined('WPINC')){	die; }

if(!class_exists('WEPO_Condition')):

class WEPO_Condition {
	const PRODUCT = 'product';
	const CATEGORY = 'category';
	const TAG = 'tag';
	const FIELD = 'field';
	
	const EQUALS = 'equals';
	const NOT_EQUALS = 'not_equals';
	const IN = 'in';
	const NOT_IN = 'not_in';
	//const EMPTY = 'empty';
	//const NOT_EMPTY = 'not_empty';
		
	/*public $subject = '';
	public $comparison = '';
	public $value = '';*/
	
	public $operand_type = '';
	public $operand = '';
	public $operator = '';
	public $value = '';
		
	public function __construct() {
		
	}	
	
	public function is_valid(){
		if(!empty($this->operand_type) && !empty($this->operator)){
			return true;
		}
		return false;
	}
	
	public function is_satisfied($product, $categories, $tags=false){
		$satisfied = true;
		if($this->is_valid()){
			$operands = $this->operand;
			
			if(!empty($operands) && !in_array(-1, $operands)){
				if($this->operand_type == self::PRODUCT){
					if($this->operator == self::EQUALS) {
						if(!in_array($product, $operands)){
							return false;
						}
					}else if($this->operator == self::NOT_EQUALS){
						if(in_array($product, $operands)){
							return false;
						}
					}
				}else if($this->operand_type == self::CATEGORY){
					$commonCategories = array_intersect($categories, $operands);
					
					if($this->operator == self::EQUALS) {
						if(empty($commonCategories)){
							return false;
						}
					}else if($this->operator == self::NOT_EQUALS){
						if(!empty($commonCategories)){
							return false;
						}
					}
				}else if($this->subject == self::TAG){
					$commonTags = array_intersect($tags, $operands);
					
					if($this->operator == self::EQUALS) {
						if(empty($commonTags)){
							return false;
						}
					}else if($this->operator == self::NOT_EQUALS){
						if(!empty($commonTags)){
							return false;
						}
					}
				}
			}
		}
		return $satisfied;
	}
	
	public function set_operand_type($operand_type){
		$this->operand_type = $operand_type;
	}	
	public function get_operand_type(){
		return $this->operand_type;
	}
	
	public function set_operand($operand){
		$this->operand = $operand;
	}	
	public function get_operand(){
		return $this->operand;
	}
	
	public function set_operator($operator){
		$this->operator = $operator;
	}	
	public function get_operator(){
		return $this->operator;
	}
	
	public function set_value($value){
		$this->value = $value;
	}	
	public function get_value(){
		return $this->value;
	}
}

endif;