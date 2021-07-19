<?php
/**
 * Display conditions rule set data object.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes/model/rules
 */
if(!defined('WPINC')){	die; }

if(!class_exists('WEPO_Condition_Rule_Set')):

class WEPO_Condition_Rule_Set {
	const LOGIC_AND = 'and';
	const LOGIC_OR  = 'or';
	
	public $logic = self::LOGIC_OR;
	public $condition_rules = array();
	
	public function __construct() {
		
	}
	
	public function is_satisfied($product, $categories, $tags=false){
		$satisfied = true;
		$condition_rules = $this->get_condition_rules();
		if(!empty($condition_rules)){
			if($this->get_logic() === self::LOGIC_AND){			
				foreach($condition_rules as $condition_rule){				
					if(!$condition_rule->is_satisfied($product, $categories, $tags)){
						$satisfied = false;
						break;
					}
				}
			}else if($this->get_logic() === self::LOGIC_OR){
				$satisfied = false;
				foreach($condition_rules as $condition_rule){				
					if($condition_rule->is_satisfied($product, $categories, $tags)){
						$satisfied = true;
						break;
					}
				}
			}
		}
		return $satisfied;
	}
	
	public function add_condition_rule($condition_rule){
		if(isset($condition_rule) && $condition_rule instanceof WEPO_Condition_Rule){
			$this->condition_rules[] = $condition_rule;
		} 
	}
	
	public function set_logic($logic){
		$this->logic = $logic;
	}	
	public function get_logic(){
		return $this->logic;
	}
		
	public function set_condition_rules($condition_rules){
		$this->condition_rules = $condition_rules;
	}	
	public function get_condition_rules(){
		return $this->condition_rules; 
	}	
}

endif;