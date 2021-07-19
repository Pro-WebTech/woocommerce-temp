<?php
/**
 * Fired during plugin activation.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEPO_Activator')):

class THWEPO_Activator {

	/**
	 * Copy older version settings if any.
	 *
	 * Use pro version settings if available, if no pro version settings found 
	 * check for free version settings and use it.
	 *
	 * - Check for premium version settings, if found do nothing. 
	 * - If no premium version settings found, then check for free version settings and copy it.
	 *
	 * @since    2.3.0
	 */
	public static function activate() {
		self::check_for_premium_settings();
	}
	
	public static function check_for_premium_settings(){
		$premium_settings = get_option(THWEPO_Utils::OPTION_KEY_CUSTOM_SECTIONS);
		if($premium_settings && is_array($premium_settings)){			
			return;
		}else{		
			if(class_exists('WEPOF_Product_Field_InputText') && class_exists('WEPOF_Product_Field_Select')){	
				self::may_copy_free_version_settings();
			}
		}
	}
	
	public static function may_copy_free_version_settings(){
		if(THWEPOF_VERSION && THWEPOF_VERSION >= 2.0){
			self::may_copy_free_version_settings_200();
		}else{
			self::may_copy_free_version_settings_134();
		}
	}

   /***********************************************
	****** Copy Setting from version < 2.0.0 ******
	***********************************************/
	public static function may_copy_free_version_settings_134(){
		$extra_options = get_option('thwepof_custom_product_fields');
	
		if($extra_options && is_array($extra_options)){
			$section = THWEPO_Utils_Section::prepare_default_section();
			$section = self::prepare_section_and_fields_134($section, $extra_options);
			$section = THWEPO_Utils_Section::sort_fields($section);
		
			$general_settings = new THWEPO_Admin_Settings_General();
			$result1 = $general_settings->update_section($section);
			$result2 = $general_settings->update_options_name_title_map();
			
			delete_option('thwepof_custom_product_fields');
		}
	}

	public static function prepare_section_and_fields_134($section, $extra_options){
		foreach($extra_options as $hook => $fields){
			if($fields){
				foreach($fields as $field_name => $field){
					try{
						$new_field = self::prepare_fields_134($field);
						$section = THWEPO_Utils_Section::add_field($section, $new_field);
					} catch (Exception $e) {
					}
				}
			}
		}
		return $section;
	}
	
	public static function prepare_fields_134($field){
		$new_field = false;
		
		if($field){
			$type = $field->get_property('type');
			$order = $field->get_property('order');
			$id = $field->get_property('id');
			$name = $field->get_property('name');
			$value = $field->get_property('value');
			$placeholder = $field->get_property('placeholder');
			$validate = $field->get_property('validator');
			$cssclass = $field->get_property('cssclass');
			$title = $field->get_property('title');
			$title_position = $field->get_property('title_position');
			$title_class = $field->get_property('title_class');
			$cols = $field->get_property('cols');
			$rows = $field->get_property('rows');
			$checked = $field->get_property('checked');
			$required = $field->is_required();
			$enabled = $field->is_enabled();
			$conditional_rules = $field->get_property('conditional_rules');
			
			$new_field = THWEPO_Utils_Field::create_field($type);
			$new_field->set_property('order', $order);
			$new_field->set_property('id', $id);
			$new_field->set_property('name', $name);
			$new_field->set_property('value', $value);
			$new_field->set_property('placeholder', $placeholder);
			$new_field->set_property('validate', $validate);
			$new_field->set_property('cssclass', $cssclass);
			$new_field->set_property('title', $title);
			$new_field->set_property('title_position', $title_position);
			$new_field->set_property('title_type', 'label');
			$new_field->set_property('title_class', $title_class);
			$new_field->set_property('cols', $cols);
			$new_field->set_property('rows', $rows);
			$new_field->set_property('checked', $checked);
			$new_field->set_property('required', $required);
			$new_field->set_property('enabled', $enabled);
			
			if($type === 'select' || $type === 'radio'){
				$new_options = array();
				
				$options = $field->get_property('options');
				if($options && is_array($options)){
					foreach($options as $option){
						$new_option = array();
						$new_option['key'] = $option;
						$new_option['text'] = $option;
						$new_option['price'] = '';
						$new_option['price_type'] = '';
						
						$new_options[$option] = $new_option;
					}
				}
				
				$new_options_json = json_encode($new_options);
				$new_options_json = rawurlencode($new_options_json);
				$new_options_json = trim(stripslashes($new_options_json));
				
				$new_field->set_property('options', $new_options);
				$new_field->set_property('options_json', $new_options_json);
			}
			
			$new_field = self::prepare_conditional_rules($new_field, $conditional_rules);
			$new_field = THWEPO_Utils_Field::prepare_properties($new_field);
		}
		return $new_field;
	}

   /***********************************************
	****** Copy Setting from version >= 2.0.0 *****
	***********************************************/
	public static function may_copy_free_version_settings_200(){
		if(class_exists('THWEPOF_Section') && class_exists('WEPOF_Product_Field')){
			$custom_sections = get_option('thwepof_custom_sections');

			if(is_array($custom_sections)){
				$advanced_settings = get_option('thwepof_advanced_settings');
				$general_settings = new THWEPO_Admin_Settings_General();
				$result = true;

				foreach($custom_sections as $key => $sectionf){
					$section = self::prepare_section_200($sectionf, $advanced_settings);
					if($section){
						$result1 = $general_settings->update_section($section);
						$result2 = $general_settings->update_options_name_title_map();

						if(!$result1 || !$result2){
							$result = false;
						}
					}
				}

				if($result){
					//delete_option('thwepof_custom_sections');
					//delete_option('thwepof_section_hook_map');
					//delete_option('thwepof_options_name_title_map');
				}
			}
		}

		self::may_copy_advanced_settings();
	}

	public static function prepare_section_200($sectionf, $advanced_settings=array()){
		try{
			if($sectionf instanceof THWEPOF_Section){
				$section = new WEPO_Product_Page_Section();
				$section->set_property('id', $sectionf->get_property('id'));
				$section->set_property('name', $sectionf->get_property('name'));
				$section->set_property('title', $sectionf->get_property('title'));
				$section->set_property('show_title', $sectionf->get_property('show_title'));
				$section->set_property('position', $sectionf->get_property('position'));

				$fields = $sectionf->get_property('fields');
				if(is_array($fields)){
					$hide_in_cart  = isset($advanced_settings['hide_in_cart']) ? $advanced_settings['hide_in_cart'] : false;
					$hide_in_order = isset($advanced_settings['hide_in_order']) ? $advanced_settings['hide_in_order'] : false;
					$hide_in_checkout = isset($advanced_settings['hide_in_checkout']) ? $advanced_settings['hide_in_checkout'] : false;

					foreach($fields as $key => $fieldf){
						$field = self::prepare_field_200($fieldf);
						if($field){
							$field->set_property('hide_in_cart', $hide_in_cart);
							$field->set_property('hide_in_order', $hide_in_order);
							$field->set_property('hide_in_checkout', $hide_in_checkout);

							$section = THWEPO_Utils_Section::add_field($section, $field);
						}
					}
					$section = THWEPO_Utils_Section::sort_fields($section);
				}

				$conditional_rules = $sectionf->get_property('conditional_rules');
				$section = self::prepare_conditional_rules($section, $conditional_rules);
				
				return $section;
			}
		}catch (Exception $e) {
			return false;
		}
		return false;
	}
	
	public static function prepare_field_200($field){
		$new_field = false;
		
		if($field instanceof WEPOF_Product_Field){
			$type = $field->get_property('type');
			$order = $field->get_property('order');
			$id = $field->get_property('id');
			$name = $field->get_property('name');
			$value = $field->get_property('value');
			$placeholder = $field->get_property('placeholder');
			$validate = $field->get_property('validator');
			$cssclass = $field->get_property('cssclass');
			$title = $field->get_property('title');
			$title_position = $field->get_property('title_position');
			$title_class = $field->get_property('title_class');
			$cols = $field->get_property('cols');
			$rows = $field->get_property('rows');
			$checked = $field->get_property('checked');
			$required = $field->is_required();
			$enabled = $field->is_enabled();
			$readonly = $field->is_readonly();
			$conditional_rules = $field->get_property('conditional_rules');

			$type = $type === 'paragraph' ? 'label' : $type;
			
			$new_field = THWEPO_Utils_Field::create_field($type);
			$new_field->set_property('order', $order);
			$new_field->set_property('id', $id);
			$new_field->set_property('name', $name);
			$new_field->set_property('value', $value);
			$new_field->set_property('placeholder', $placeholder);
			$new_field->set_property('validate', $validate);
			$new_field->set_property('cssclass', $cssclass);
			$new_field->set_property('title', $title);
			$new_field->set_property('title_position', $title_position);
			$new_field->set_property('title_type', 'label');
			$new_field->set_property('title_class', $title_class);
			$new_field->set_property('cols', $cols);
			$new_field->set_property('rows', $rows);
			$new_field->set_property('checked', $checked);
			$new_field->set_property('required', $required);
			$new_field->set_property('enabled', $enabled);
			$new_field->set_property('readonly', $readonly);
			
			if($type === 'select' || $type === 'radio' || $type === 'checkboxgroup'){
				$new_options = array();
				
				$options = $field->get_property('options');
				if($options && is_array($options)){
					foreach($options as $option){
						$new_option = array();
						$new_option['key'] = $option;
						$new_option['text'] = $option;
						$new_option['price'] = '';
						$new_option['price_type'] = '';
						
						$new_options[$option] = $new_option;
					}
				}
				
				$new_options_json = json_encode($new_options);
				$new_options_json = rawurlencode($new_options_json);
				$new_options_json = trim(stripslashes($new_options_json));
				
				$new_field->set_property('options', $new_options);
				$new_field->set_property('options_json', $new_options_json);

			}else if($type === 'datepicker'){
				$new_field->set_property('default_date', $value);

			}else if($type === 'heading' || $type === 'label'){
				$new_field->set_property('title', $value);
				$new_field->set_property('title_class', $cssclass);
			}

			$new_field = self::prepare_conditional_rules($new_field, $conditional_rules);
			$new_field = THWEPO_Utils_Field::prepare_properties($new_field);
		}
		return $new_field;
	}

	public static function prepare_conditional_rules($object, $conditional_rules){
		$new_condition_rule_sets = array();	
		$cr_sets = array();
		
		if($conditional_rules && is_array($conditional_rules)){
			foreach($conditional_rules as $rule_set){
				if($rule_set){
					$logic = $rule_set->get_logic();
					$rules = $rule_set->get_condition_rules();
					
					$new_condition_rule_set_obj = new WEPO_Condition_Rule_Set();
					$new_condition_rule_set_obj->set_logic($logic);
					
					$cr_set = array();
					
					if($rules && is_array($rules)){
						foreach($rules as $condition_rule){
							$rule_logic = $condition_rule->get_logic();
							$condition_sets = $condition_rule->get_condition_sets();
							
							$new_condition_rule_obj = new WEPO_Condition_Rule();
							$new_condition_rule_obj->set_logic($rule_logic);
							
							$c_rule = array();
							
							if($condition_sets && is_array($condition_sets)){
								foreach($condition_sets as $condition_set){
									$cs_logic = $condition_set->get_logic();
									$conditions = $condition_set->get_conditions();
									
									$new_condition_set_obj = new WEPO_Condition_Set();
									$new_condition_set_obj->set_logic($cs_logic);
									
									$c_set = array();
									
									if($conditions && is_array($conditions)){
										foreach($conditions as $condition){
											$subject = $condition->get_subject();
											$comparison = $condition->get_comparison();
											$value = $condition->get_value();
										
											$new_condition_obj = new WEPO_Condition();
											$new_condition_obj->set_operand_type($subject);
											$new_condition_obj->set_operand($value);
											$new_condition_obj->set_operator($comparison);
											$new_condition_obj->set_value('');
											
											$new_condition_set_obj->add_condition($new_condition_obj);
											
											$c = array();
											$c['operand_type'] = $subject;
											$c['operand'] = $value;
											$c['operator'] = $comparison;
											$c['value'] = '';
											
											array_push($c_set, $c);
										}
									}
									$new_condition_rule_obj->add_condition_set($new_condition_set_obj);	
									array_push($c_rule, $c_set);
								}
							}
							$new_condition_rule_set_obj->add_condition_rule($new_condition_rule_obj);
							array_push($cr_set, $c_rule);
						}
					}
					$new_condition_rule_sets[] = $new_condition_rule_set_obj;
					array_push($cr_sets, $cr_set);
				}
			}
		}
		
		$new_conditional_rules_json = json_encode($cr_sets);
		$new_conditional_rules_json = rawurlencode($new_conditional_rules_json);
		$new_conditional_rules_json = trim(stripslashes($new_conditional_rules_json));
		
		$object->set_property('rules_action', 'show');
		$object->set_property('conditional_rules', $new_condition_rule_sets);
		$object->set_property('conditional_rules_json', $new_conditional_rules_json);

		return $object;
	}

	public static function may_copy_advanced_settings(){
		$settings = THWEPO_Utils::get_advanced_settings();

		if(!($settings && is_array($settings))){
			$settings = array();
			$fsettings = get_option('thwepof_advanced_settings');

			if($fsettings && is_array($fsettings)){
				$settings_mapping = array(
					'add_to_cart_text_addon'    => 'add_to_cart_text_addon',
					'add_to_cart_text_simple'   => 'add_to_cart_text_simple',
					'add_to_cart_text_variable' => 'add_to_cart_text_variable',
					'hide_in_cart'          => 'hide_in_cart',
					'hide_in_checkout'      => 'hide_in_checkout',
					'hide_in_order'         => 'hide_in_order',
				);

				foreach ($settings_mapping as $fkey => $pkey) {
					if(isset($fsettings[$fkey])){
						$settings[$pkey] = $fsettings[$fkey];
					}
				}

				if(!empty($settings)){
					$result = update_option(THWEPO_Utils::OPTION_KEY_ADVANCED_SETTINGS, $settings);
				}
			}
		}
	}
}

endif;