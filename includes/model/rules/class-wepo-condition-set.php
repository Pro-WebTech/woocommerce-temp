<?php
/**
 * Display conditions set data object.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes/model/rules
 */
if(!defined('WPINC')){	die; }

if(!class_exists('WEPO_Condition_Set')):

class WEPO_Condition_Set {
	const LOGIC_AND = 'and';
	const LOGIC_OR  = 'or';
	
	public $logic = self::LOGIC_AND;
	public $conditions = array();
	
	public function __construct() {
		
	}
	
	public function is_satisfied($product, $categories, $tags=false){
		$satisfied = true;
		$conditions = $this->get_conditions();
		if(!empty($conditions)){			 
			if($this->get_logic() === self::LOGIC_AND){			
				foreach($conditions as $condition){				
					if(!$condition->is_satisfied($product, $categories, $tags)){
						$satisfied = false;
						break;
					}
				}
			}else if($this->get_logic() === self::LOGIC_OR){
				$satisfied = false;
				foreach($conditions as $condition){				
					if($condition->is_satisfied($product, $categories, $tags)){
						$satisfied = true;
						break;
					}
				}
			}
		}
		return $satisfied;
	}
	
	public function add_condition($condition){
		if(isset($condition) && $condition instanceof WEPO_Condition && $condition->is_valid()){
			$this->conditions[] = $condition;
		} 
	}
	
	public function set_logic($logic){
		$this->logic = $logic;
	}	
	public function get_logic(){
		return $this->logic;
	}
		
	public function set_conditions($conditions){
		$this->conditions = $conditions;
	}	
	public function get_conditions(){
		return $this->conditions; 
	}	
}

endif;