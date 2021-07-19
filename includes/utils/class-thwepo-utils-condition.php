<?php
/**
 * The display condition specific functionality for the plugin.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes/utils
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEPO_Utils_Condition')):

class THWEPO_Utils_Condition {
	const LOGIC_AND = 'and';
	const LOGIC_OR  = 'or';
	
	const OP_TYPE_PRODUCT   = 'product';
	const OP_TYPE_VARIATION = 'product_variation';
	const OP_TYPE_CATEGORY  = 'category';
	const OP_TYPE_TAG 		= 'tag';
	const OP_TYPE_USER      = 'user_role';
	const OP_TYPE_FIELD     = 'field';
	
	const OPERATOR_EQUALS = 'equals';
	const OPERATOR_NOT_EQUALS = 'not_equals';
	const OPERATOR_IN = 'in';
	const OPERATOR_NOT_IN = 'not_in';
	
	public static function is_valid_condition($condition){
		if(!empty($condition->operand_type) && !empty($condition->operator)){
			return true;
		}
		return false;
	}
	
	public static function is_satisfied($rules_set_list, $product, $categories, $tags=false){
		$valid = true;
		if(is_array($rules_set_list) && !empty($rules_set_list)){
			foreach($rules_set_list as $rules_set){				
				if(!self::is_satisfied_rules_set($rules_set, $product, $categories, $tags)){
					$valid = false;
				}
			}
		}
		return $valid;
	}
	
	private static function is_satisfied_condition($condition, $product, $categories, $tags){
		$satisfied = true;
		if(self::is_valid_condition($condition)){
			$op_type  = $condition->operand_type;
			$operator = $condition->operator;
			$operands = $condition->operand;
			
			if(!empty($operands) && !in_array(-1, $operands)){
				if($op_type == self::OP_TYPE_PRODUCT){
					$product = THWEPO_Utils::get_original_product_id($product);

					if($operator == self::OPERATOR_EQUALS) {
						if(!in_array($product, $operands)){
							return false;
						}
					}else if($operator == self::OPERATOR_NOT_EQUALS){
						if(in_array($product, $operands)){
							return false;
						}
					}
				}else if($op_type == self::OP_TYPE_CATEGORY){
					$commonCategories = array_intersect($categories, $operands);
					
					if($operator == self::OPERATOR_EQUALS) {
						if(empty($commonCategories)){
							return false;
						}
					}else if($operator == self::OPERATOR_NOT_EQUALS){
						if(!empty($commonCategories)){
							return false;
						}
					}
				}else if($op_type == self::OP_TYPE_TAG){
					$commonTags = array_intersect($tags, $operands);
					
					if($operator == self::OPERATOR_EQUALS) {
						if(empty($commonTags)){
							return false;
						}
					}else if($operator == self::OPERATOR_NOT_EQUALS){
						if(!empty($commonTags)){
							return false;
						}
					}
				}else if($op_type === self::OP_TYPE_USER){
					$user_roles = THWEPO_Utils::get_user_roles();
					
					if(is_array($user_roles) && is_array($operands)){
						$intersection = array_intersect($user_roles, $operands);
						
						if($operator == self::OPERATOR_EQUALS) {
							if(empty($intersection)){
								return false;
							}
						}else if($operator == self::OPERATOR_NOT_EQUALS){
							if(!empty($intersection)){
								return false;
							}
						}
					}
				}
			}
		}
		return $satisfied;
	}

	private static function is_satisfied_rules_set($rules_set, $product, $categories, $tags){
		$satisfied = true;
		$condition_rules = $rules_set->get_condition_rules();
		$logic = $rules_set->get_logic();
		
		if(!empty($condition_rules)){
			if($logic === self::LOGIC_AND){			
				foreach($condition_rules as $condition_rule){				
					if(!self::is_satisfied_rule($condition_rule, $product, $categories, $tags)){
						$satisfied = false;
						break;
					}
				}
			}else if($logic === self::LOGIC_OR){
				$satisfied = false;
				foreach($condition_rules as $condition_rule){				
					if(self::is_satisfied_rule($condition_rule, $product, $categories, $tags)){
						$satisfied = true;
						break;
					}
				}
			}
		}
		return $satisfied;
	}
	
	private static function is_satisfied_rule($rule, $product, $categories, $tags){
		$satisfied = true;
		$conditions_set_list = $rule->get_condition_sets();
		$logic = $rule->get_logic();
		
		if(!empty($conditions_set_list)){
			if($logic === self::LOGIC_AND){			
				foreach($conditions_set_list as $conditions_set){				
					if(!self::is_satisfied_conditions_set($conditions_set, $product, $categories, $tags)){
						$satisfied = false;
						break;
					}
				}
			}else if($logic === self::LOGIC_OR){
				$satisfied = false;
				foreach($conditions_set_list as $conditions_set){				
					if(self::is_satisfied_conditions_set($conditions_set, $product, $categories, $tags)){
						$satisfied = true;
						break;
					}
				}
			}			
		}
		return $satisfied;
	}
	
	private static function is_satisfied_conditions_set($conditions_set, $product, $categories, $tags){
		$satisfied = true;
		$conditions = $conditions_set->get_conditions();
		$logic = $conditions_set->get_logic();
		
		if(!empty($conditions)){			 
			if($logic === self::LOGIC_AND){			
				foreach($conditions as $condition){				
					if(!self::is_satisfied_condition($condition, $product, $categories, $tags)){
						$satisfied = false;
						break;
					}
				}
			}else if($logic === self::LOGIC_OR){
				$satisfied = false;
				foreach($conditions as $condition){				
					if(self::is_satisfied_condition($condition, $product, $categories, $tags)){
						$satisfied = true;
						break;
					}
				}
			}
		}
		return $satisfied;
	}
	
	public static function prepare_conditional_rules($posted, $ajax=false){
		$iname = $ajax ? 'i_rules_ajax' : 'i_rules';
		$conditional_rules = isset($posted[$iname]) ? trim(stripslashes($posted[$iname])) : '';
		
		$condition_rule_sets = array();	
		if(!empty($conditional_rules)){
			$conditional_rules = urldecode($conditional_rules);
			$rule_sets = json_decode($conditional_rules, true);
				
			if(is_array($rule_sets)){
				foreach($rule_sets as $rule_set){
					if(is_array($rule_set)){
						$condition_rule_set_obj = new WEPO_Condition_Rule_Set();
						$condition_rule_set_obj->set_logic('and');
												
						foreach($rule_set as $condition_sets){
							if(is_array($condition_sets)){
								$condition_rule_obj = new WEPO_Condition_Rule();
								$condition_rule_obj->set_logic('or');
														
								foreach($condition_sets as $condition_set){
									if(is_array($condition_set)){
										$condition_set_obj = new WEPO_Condition_Set();
										$condition_set_obj->set_logic('and');
													
										foreach($condition_set as $condition){
											if(is_array($condition)){
												$condition_obj = new WEPO_Condition();
												$condition_obj->set_operand_type(isset($condition['operand_type']) ? $condition['operand_type'] : '');
												$condition_obj->set_operand(isset($condition['operand']) ? $condition['operand'] : '');
												$condition_obj->set_operator(isset($condition['operator']) ? $condition['operator'] : '');
												$condition_obj->set_value(isset($condition['value']) ? trim($condition['value']) : '');
												
												$condition_set_obj->add_condition($condition_obj);
											}
										}										
										$condition_rule_obj->add_condition_set($condition_set_obj);	
									}								
								}
								$condition_rule_set_obj->add_condition_rule($condition_rule_obj);
							}
						}
						$condition_rule_sets[] = $condition_rule_set_obj;
					}
				}	
			}
		}
		return $condition_rule_sets;
	}
}

endif;