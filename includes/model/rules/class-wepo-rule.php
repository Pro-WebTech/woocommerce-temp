<?php
/**
 * Display conditions rule data object.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes/model/rules
 */
if(!defined('WPINC')){	die; }

if(!class_exists('WEPO_Condition_Rule')):

class WEPO_Condition_Rule {
	const LOGIC_AND = 'and';
	const LOGIC_OR  = 'or';
	
	public $logic = self::LOGIC_OR;
	public $condition_sets = array();
	
	public function __construct() {
		
	}
	
	public function is_satisfied($product, $categories, $tags=false){
		$satisfied = true;
		$condition_sets = $this->get_condition_sets();
		if(!empty($condition_sets)){
			if($this->get_logic() === self::LOGIC_AND){			
				foreach($condition_sets as $condition_set){				
					if(!$condition_set->is_satisfied($product, $categories, $tags)){
						$satisfied = false;
						break;
					}
				}
			}else if($this->get_logic() === self::LOGIC_OR){
				$satisfied = false;
				foreach($condition_sets as $condition_set){				
					if($condition_set->is_satisfied($product, $categories, $tags)){
						$satisfied = true;
						break;
					}
				}
			}			
		}
		return $satisfied;
	}
	
	public function add_condition_set($condition_set){
		if(isset($condition_set) && $condition_set instanceof WEPO_Condition_Set){
			$this->condition_sets[] = $condition_set;
		} 
	}
	
	public function set_logic($logic){
		$this->logic = $logic;
	}	
	public function get_logic(){
		return $this->logic;
	}
		
	public function set_condition_sets($condition_sets){
		$this->condition_sets = $condition_sets;
	}	
	public function get_condition_sets(){
		return $this->condition_sets; 
	}	
}

endif;