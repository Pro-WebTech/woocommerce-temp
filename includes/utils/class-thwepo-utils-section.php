<?php
/**
 * The custom sections specific functionality for the plugin.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes/utils
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEPO_Utils_Section')):

class THWEPO_Utils_Section {
	static $DEFAULT_SECTIONS = array('billing', 'shipping', 'additional');
	static $SECTION_PROPS = array(
		'name' 	   => array('name'=>'name', 'value'=>''),		
		'position' => array('name'=>'position', 'value'=>''),
		'order'    => array('name'=>'order', 'value'=>''),
		'cssclass' => array('name'=>'cssclass', 'value'=>array(), 'value_type'=>'array'),
		
		'title_cell_with' => array('name'=>'title_cell_with', 'value'=>''),
		'field_cell_with' => array('name'=>'field_cell_with', 'value'=>''),
		
		'show_title'     => array('name'=>'show_title', 'value'=>1, 'value_type'=>'boolean'),
		//'custom_section' => array('name'=>'custom_section', 'value'=>1, 'value_type'=>'boolean'),
		
		'title' 	     => array('name'=>'title', 'value'=>''),
		'title_type'     => array('name'=>'title_type', 'value'=>''),
		'title_color'    => array('name'=>'title_color', 'value'=>''),
		'title_position' => array('name'=>'title_position', 'value'=>''),
		'title_class'    => array('name'=>'title_class', 'value'=>array(), 'value_type'=>'array'),
		
		'subtitle' 		 	=> array('name'=>'subtitle', 'value'=>''),
		'subtitle_type'     => array('name'=>'subtitle_type', 'value'=>''),
		'subtitle_color'    => array('name'=>'subtitle_color', 'value'=>''),
		'subtitle_position' => array('name'=>'subtitle_position', 'value'=>''),
		'subtitle_class' 	=> array('name'=>'subtitle_class', 'value'=>array(), 'value_type'=>'array'),
	);
	
	public static function is_valid_section($section){
		if(isset($section) && $section instanceof WEPO_Product_Page_Section && !empty($section->name)){
			return true;
		} 
		return false;
	}
	
	public static function is_enabled($section){
		if($section->get_property('enabled')){
			return true;
		}
		return false;
	}
	
	public static function is_custom_section($section){
		//return $section->custom_section;
		return true;
	}
	
	public static function has_fields($section){
		if($section->get_property('fields')){
			return true;
		}
		return false;
	}
	
	public static function is_show_section($section, $product, $categories, $tags=false){
		/*$show = true;
		if(!empty($condition_sets)){			
			foreach($condition_sets as $condition_set){
				if($condition_set->show_element()){
					$show = false;
				}
			}
		}
		
		return $show;*/
		
		$rules_set_list = $section->get_property('conditional_rules');
		$valid = THWEPO_Utils_Condition::is_satisfied($rules_set_list, $product, $categories, $tags);
		
		$show = true;
		if($section->get_property('rules_action') === 'hide'){
			$show = $valid ? false : true;
		}else{
			$show = $valid ? true : false;
		}
		$show = apply_filters('thwepo_show_section', $show, $section->name);
		return $show;
	}
	
	public static function prepare_default_section(){
		$section = new WEPO_Product_Page_Section();
		$section->set_property('id', 'default');
		$section->set_property('name', 'default');
		$section->set_property('title', 'Default');
		$section->set_property('show_title', 0);
		$section->set_property('position', 'woo_before_add_to_cart_button');
		
		return $section;
	}
	
	public static function prepare_section_from_posted_data($posted, $form = 'new'){
		$name     = isset($posted['i_name']) ? $posted['i_name'] : '';
		$position = isset($posted['i_position']) ? $posted['i_position'] : '';
		$title    = isset($posted['i_title']) ? $posted['i_title'] : '';

		if(!$name || !$title || !$position){
			return;
		}
		
		if($form === 'edit'){
			$section = THWEPO_Admin_Utils::get_section($name);
		}else{
			$name = strtolower($name);
			$name = is_numeric($name) ? "s_".$name : $name;
				
			$section = new WEPO_Product_Page_Section();
			$section->set_property('id', $name);
		}
		
		foreach( self::$SECTION_PROPS as $pname => $property ){
			$iname  = 'i_'.$pname;
			$pvalue = isset($posted[$iname]) ? trim(stripslashes($posted[$iname])) : $property['value'];
			
			if($pname === 'show_title'){
				$pvalue = !empty($pvalue) && $pvalue === 'yes' ? 1 : 0;
			}
			
			$section->set_property($pname, $pvalue);
		}
		
		$section->set_property('custom_section', 1);
		
		$section->set_property('rules_action', isset($posted['i_rules_action']) ? trim(stripslashes($posted['i_rules_action'])) : '');
		$section->set_property('conditional_rules_json', isset($posted['i_rules']) ? trim(stripslashes($posted['i_rules'])) : '');
		$section->set_property('conditional_rules', THWEPO_Utils_Condition::prepare_conditional_rules($posted, false));
		
		$section->set_property('rules_action_ajax', isset($posted['i_rules_action_ajax']) ? trim(stripslashes($posted['i_rules_action_ajax'])) : '');
		$section->set_property('conditional_rules_ajax_json', isset($posted['i_rules_ajax']) ? trim(stripslashes($posted['i_rules_ajax'])) : '');
		$section->set_property('conditional_rules_ajax', THWEPO_Utils_Condition::prepare_conditional_rules($posted, true));
		
		//WPML Support
		self::add_wpml_support($section);
		return $section;
	}
	
	public static function get_property_set($section){
		if(self::is_valid_section($section)){
			$props_set = array();
			
			foreach(self::$SECTION_PROPS as $pname => $props){
				$pvalue = $section->get_property($props['name']);
				
				if(isset($props['value_type']) && $props['value_type'] === 'array' && !empty($pvalue)){
					$pvalue = is_array($pvalue) ? $pvalue : explode(',', $pvalue);
				}
				
				if(isset($props['value_type']) && $props['value_type'] != 'boolean'){
					$pvalue = empty($pvalue) ? $props['value'] : $pvalue;
				}
				
				$props_set[$pname] = $pvalue;
			}
			
			$props_set['custom'] = self::is_custom_section($section);
			$props_set['rules_action'] = $section->get_property('rules_action');
			$props_set['rules_action_ajax'] = $section->get_property('rules_action_ajax');
			
			return $props_set;
		}else{
			return false;
		}
	}
	
	public static function get_property_json($section){
		$props_json = '';
		$props_set = self::get_property_set($section);
		
		if($props_set){
			$props_json = json_encode($props_set);
		}
		return $props_json;
	}
	
	public static function add_field($section, $field){
		if(self::is_valid_section($section) && THWEPO_Utils_Field::is_valid_field($field)){
			$size = sizeof($section->fields);
			$field->set_property('order', $size);
			$field->set_property('custom_field', 1);
			$section->fields[$field->get_property('name')] = $field;
			return $section;
		}else{
			throw new Exception('Invalid Section or Field Object.');
		}
	}
	
	public static function update_field($section, $field){
		if(self::is_valid_section($section) && THWEPO_Utils_Field::is_valid_field($field)){
			$name = $field->get_property('name');
			$name_old = $field->get_property('name_old');
			$field_set = $section->fields;
			
			if(!empty($name) && is_array($field_set) && isset($field_set[$name_old])){
				$o_field = $field_set[$name_old];				
				//$index = array_search($name_old, array_keys($field_set));
				$field->set_property('order', $o_field->get_property('order'));
				$field->set_property('custom_field', $o_field->get_property('custom_field'));
				$field_set[$name] = $field;
				
				if($name != $name_old){
					unset($field_set[$name_old]);
				}
				$field_set = self::sort_field_set($field_set);
				$section->set_property('fields', $field_set);
			}
			return $section;
		}else{
			throw new Exception('Invalid Section or Field Object.');
		}
	}
	
	public static function get_fields($section, $ignore_disabled=false){
		$fields = (is_array($section->fields) && !empty($section->fields)) ? $section->fields : array();

		if($ignore_disabled && is_array($fields)){
			foreach($fields as $key => $field){
				if(!THWEPO_Utils_Field::is_enabled($field)){
					unset($fields[$key]);
				}
			}
		}
		return $fields;
	}
	
	public static function get_product_sections_and_fields($product){
		$product_id = THWEPO_Utils::get_product_id($product);
		$categories = THWEPO_Utils::get_product_categories($product_id);
		$tags 		= THWEPO_Utils::get_product_tags($product_id);
		$sections   = THWEPO_Utils::get_custom_sections();
		
		if($sections && is_array($sections) && !empty($sections)){
			foreach($sections as $section_name => $section){
				$section = self::prepare_section_and_fields($section, $product_id, $categories, $tags);
				if($section){
					$sections[$section_name] = $section;
				}else{
					unset($sections[$section_name]);
				}
			}
		}
		return $sections;
	}
	
	/**
	 * Check and filter invalid, disabled and conditionally hidden section and fields.
	 *
	 * @param $section
	 * @param $product_id
	 * @param $categories
	 * @param $tags
	 * 
	 * @return $section - A cleaned Section object
	 */
	public static function prepare_section_and_fields($section, $product_id, $categories, $tags=false){
		if(self::is_valid_section($section) && self::is_show_section($section, $product_id, $categories, $tags)){					
			$fields = self::get_fields($section);
			if(is_array($fields)){
				foreach($fields as $field_name => $field){
					if(THWEPO_Utils_Field::is_enabled($field) && THWEPO_Utils_Field::is_show_field($field, $product_id, $categories, $tags)){
						$fields[$field_name] = $field;
					}else{
						unset($fields[$field_name]);
					}
				}

				if(!empty($fields)){
					$section->set_property('fields', $fields);
					return $section;
				}
			}
		}
		return false;
	}
	
	public static function get_product_fields($product, $names_only = true){
		$product_id = THWEPO_Utils::get_product_id($product);
		$categories = THWEPO_Utils::get_product_categories($product_id);
		$tags 		= THWEPO_Utils::get_product_tags($product_id);
		$sections   = THWEPO_Utils::get_custom_sections();
		$prod_fields = array();
		
		if($sections && is_array($sections) && !empty($sections)){
			foreach($sections as $section_name => $section){
				$fields = self::get_product_section_fields($section, $product_id, $categories, $tags, $names_only);
				if($fields){
					$prod_fields = array_merge($prod_fields, $fields);
				}
			}
		}
		return $prod_fields;
	}
	
	public static function get_product_section_fields($section, $product_id, $categories, $tags=false, $names_only = true){
		$prod_fields = array();
		if(self::is_valid_section($section) && self::is_show_section($section, $product_id, $categories, $tags)){					
			$fields = self::get_fields($section);

			if(is_array($fields)){
				foreach($fields as $field_name => $field){
					if(THWEPO_Utils_Field::is_enabled($field) && THWEPO_Utils_Field::is_show_field($field, $product_id, $categories, $tags)){
						if($names_only){
							$prod_fields[] = $field_name;
						}else{
							$prod_fields[$field_name] = $field;
						}
					}
				}
			}
		}
		return $prod_fields;
	}
	
	public static function clear_fields($section){
		if(self::is_valid_section($section)){
			$section->fields = array();
		}
		return $section;
	}
	
	public static function sort_fields($section){
		uasort($section->fields, array('self', 'sort_by_order'));
		return $section;
	}
	
	public static function sort_field_set($field_set){
		uasort($field_set, array('self', 'sort_by_order'));
		return $field_set;
	}
	
	public static function sort_by_order($a, $b){
	    if($a->get_property('order') == $b->get_property('order')){
	        return 0;
	    }
	    return ($a->get_property('order') < $b->get_property('order')) ? -1 : 1;
	}
	
	public static function add_wpml_support($section){
		THWEPO_i18n::wpml_register_string('Section Title - '.$section->name, $section->title );
		THWEPO_i18n::wpml_register_string('Section Subtitle - '.$section->name, $section->subtitle );
	}
	
	/***********************************************
	 *********** DISPLAY SECTIONS - START **********
	 ***********************************************/
	public static function prepare_section_html($section, $product_type = false){
		$field_html = '';
		$field_html_hidden = '';
		$section_html = '';
		
		$fields = self::get_fields($section);
		if(is_array($fields)){
			foreach($fields as $field){
				if($field->get_property('type') === 'hidden'){
					$field_html_hidden .= THWEPO_Utils_Field::prepare_field_html($field, $section);
				}else{
					$field_html .= THWEPO_Utils_Field::prepare_field_html($field, $section);
				}
			}
		}
		
		if(!empty($field_html)){
			$cssclass  = THWEPO_Utils::convert_cssclass_string($section->get_property('cssclass'));
			$cssclass .= $product_type ? ' thwepo_'.$product_type : '';
			
			$conditions_data = self::get_ajax_conditions_data($section);
			if($conditions_data){
				$cssclass .= empty($cssclass) ? 'thwepo-conditional-section' : ' thwepo-conditional-section';
			}

			$section_html .= '<table class="extra-options '. trim($cssclass) .'" '.$conditions_data.' cellspacing="0"><tbody>';
			$section_html .= $section->get_property('show_title') ? self::prepare_title_html($section) : '';
			$section_html .= $field_html;
			$section_html .= '</tbody></table>';
		}
		
		if(!empty($field_html_hidden)){
			$section_html .= $field_html_hidden;
		}
		return $section_html;
	}
	
	public static function prepare_title_html($section){
		$title_html = '';
		if($section->get_property('title')){
			$title_class = THWEPO_Utils::convert_cssclass_string($section->get_property('title_class'));
			$title_type  = $section->get_property('title_type') ? $section->get_property('title_type') : 'label';
			$title_style = $section->get_property('title_color') ? 'style="color:'.$section->get_property('title_color').';"' : '';
			
			$title_html .= '<'.$title_type.' class="'.$title_class.'" '.$title_style.'>';
			$title_html .= THWEPO_i18n::esc_html__t($section->get_property('title'));
			$title_html .= '</'.$title_type.'>';
		}
		
		$subtitle_html = '';
		if($section->get_property('subtitle')){
			$subtitle_class = THWEPO_Utils::convert_cssclass_string($section->get_property('subtitle_class'));
			$subtitle_type  = $section->get_property('subtitle_type') ? $section->get_property('subtitle_type') : 'span';
			$subtitle_style = $section->get_property('subtitle_color') ? 'color:'.$section->get_property('subtitle_color').';' : '';
			$subtitle_style = 'style="font-size:80%; '.$subtitle_style.'"';
			
			$subtitle_html .= !empty($title_html) ? '<br/>' : '';
			$subtitle_html .= '<'.$subtitle_type.' class="'.$subtitle_class.'" '.$subtitle_style.'>';
			$subtitle_html .= THWEPO_i18n::esc_html__t($section->get_property('subtitle'));
			$subtitle_html .= '</'.$subtitle_type.'>';
		}
		
		$html = $title_html;
		if(!empty($subtitle_html)){
			$html .= $subtitle_html;
		}
		
		if(!empty($html)){
			$html = '<tr><td colspan="2" class="section-title">'.$html.'</td></tr>';
		}else{
			$html = '<tr><td colspan="2" class="section-title">&nbsp;</td></tr>';
		}		
		return $html;
	}
	
	private static function get_ajax_conditions_data($section){
		$data_str = false;
		if($section->get_property('conditional_rules_ajax_json')){
			$rules_action = $section->get_property('rules_action_ajax') ? $section->get_property('rules_action_ajax') : 'show';
			$rules = urldecode($section->get_property('conditional_rules_ajax_json'));
			$rules = esc_js($rules);
			
			$data_str = 'id="'.$section->name.'" data-rules="'. $rules .'" data-rules-action="'. $rules_action .'" data-rules-elm="section"';
		}
		return $data_str;
	}
}

endif;