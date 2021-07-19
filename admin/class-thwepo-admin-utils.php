<?php
/**
 * The admin settings page common utility functionalities.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/admin
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEPO_Admin_Utils')):

class THWEPO_Admin_Utils {
	public static function get_sections(){				
		$sections = THWEPO_Utils::get_custom_sections();
		
		if($sections && is_array($sections) && !empty($sections)){
			return $sections;
		}else{
			$section = THWEPO_Utils_Section::prepare_default_section();
			
			$sections = array();
			$sections[$section->get_property('name')] = $section;
			return $sections;
		}		
	}
	
	public static function get_section($section_name){
	 	if($section_name){	
			$sections = self::get_sections();
			if(is_array($sections) && isset($sections[$section_name])){
				$section = $sections[$section_name];	
				if(THWEPO_Utils_Section::is_valid_section($section)){
					return $section;
				} 
			}
		}
		return false;
	}

	//Example function to move fields from one section to another.
	public static function copy_fields_from_one_to_another($from, $to, $fields){
		if(is_array($fields) && !empty($fields) && $from && $to){
			$sections = THWEPO_Utils::get_custom_sections();

			if(is_array($sections) && isset($sections[$from]) && isset($sections[$to])){
				$section_from = $sections[$from];
				$section_to = $sections[$to];

				if(THWEPO_Utils_Section::is_valid_section($section_from) && THWEPO_Utils_Section::is_valid_section($section_to)){
					$update_flag = false;
					$field_set_from = THWEPO_Utils_Section::get_fields($section_from);
					$field_set_to = THWEPO_Utils_Section::get_fields($section_to);
					
					foreach($fields as $fname){
						if(!isset($field_set_to[$fname])){
							$field = $field_set_from[$fname];
							$section_to = THWEPO_Utils_Section::add_field($section_to, $field);
							$update_flag = true;
						}
					}

					if($update_flag){
						$sections[$to] = $section_to;
						$result = update_option(THWEPO_Utils::OPTION_KEY_CUSTOM_SECTIONS, $sections);
					}
				}
			}
		}
	}

	public static function skip_products_loading(){
		$skip = apply_filters('thwepo_disable_product_dropdown', false);
		return $skip;
	}
	
	public static function load_products($only_id = false){
		$productsList = array();
		$skip = self::skip_products_loading();

		if(!$skip){
			$limit = apply_filters('thwepo_load_products_per_page', -1);
		    $only_id = apply_filters('thwepo_load_products_id_only', $only_id);
		    $status = apply_filters('thwepo_load_products_status', 'publish');

			$args = array('status' => $status, 'limit' => $limit, 'orderby' => 'name', 'order' => 'ASC', 'return' => 'ids');

			$products = wc_get_products( $args );
			
			if(count($products) > 0){
				if($only_id){
					return $products;
				}else{
					foreach($products as $pid){				
						//$productsList[] = array("id" => $product->ID, "title" => $product->post_title);
						$productsList[] = array("id" => $pid, "title" => get_the_title($pid));
					}
				}
			}	
		}	
		return $productsList;
	}
	
	/*public static function load_products($only_id = false){
		$productsList = array();
		$skip = self::skip_products_loading();

		if(!$skip){
			$posts_per_page = apply_filters('thwepo_load_products_per_page', -1);
		    $only_id = apply_filters('thwepo_load_products_id_only', $only_id);
			$args = array( 'post_type' => 'product', 'order' => 'ASC', 'posts_per_page' => $posts_per_page, 'fields' => 'ids' );

			$products = get_posts( $args );
			
			if(count($products) > 0){
				if($only_id){
					return $products;
				}else{
					foreach($products as $pid){				
						//$productsList[] = array("id" => $product->ID, "title" => $product->post_title);
						$productsList[] = array("id" => $pid, "title" => get_the_title($pid));
					}
				}
			}	
		}	
		return $productsList;
	}*/
	
	/*public static function load_products(){
		$args = array( 'post_type' => 'product', 'order' => 'ASC', 'posts_per_page' => -1, 'fields' => 'ids' );
		$products = get_posts( $args );
		$productsList = array();
		
		if(count($products) > 0){
			foreach($products as $pid){				
				//$productsList[] = array("id" => $product->ID, "title" => $product->post_title);
				$productsList[] = array("id" => $pid, "title" => get_the_title($pid));
			}
		}		
		return $productsList;
	}*/
	
	/*public static function load_products_cat(){
		$ignore_translation = apply_filters('thwepo_ignore_wpml_translation_for_product_category', true);
		$is_wpml_active = THWEPO_Utils::is_wpml_active();
		
		$product_cat = array();
		$pcat_terms = get_terms('product_cat', 'orderby=count&hide_empty=0');
		
		foreach($pcat_terms as $pterm){
			$pcat_slug = $pterm->slug;
			$pcat_slug = THWEPO_Utils::check_for_wpml_traslation($pcat_slug, $pterm, $is_wpml_active, $ignore_translation);
							
			$product_cat[] = array("id" => $pcat_slug, "title" => $pterm->name);
		}		
		return $product_cat;
	}*/

	public static function load_products_cat($only_slug = false){
		$product_cats = self::load_product_terms('category', 'product_cat', $only_slug);
		return $product_cats;
	}

	public static function load_product_tags($only_slug = false){
		$product_tags = self::load_product_terms('tag', 'product_tag', $only_slug);
		return $product_tags;
	}

	public static function load_product_terms($type, $taxonomy, $only_slug = false){
		$product_terms = array();
		$pterms = get_terms($taxonomy, 'orderby=count&hide_empty=0');

		$ignore_translation = true;
		$is_wpml_active = THWEPO_Utils::is_wpml_active();
		if($is_wpml_active && $ignore_translation){
			/*global $sitepress;
			global $icl_adjust_id_url_filter_off;
			$orig_flag_value = $icl_adjust_id_url_filter_off;
			$icl_adjust_id_url_filter_off = true;
			$default_lang = $sitepress->get_default_language();*/
			$default_lang = THWEPO_Utils::off_wpml_translation();
		}

		if(is_array($pterms)){
			foreach($pterms as $term){
				$dterm = $term;

				if($is_wpml_active && $ignore_translation){
					//$def_term_id = icl_object_id($term->term_id, $type, true, $default_lang);
					//$def_term = get_term($def_term_id);
					$dterm = THWEPO_Utils::get_default_lang_term($term, $taxonomy, $default_lang);
				}

				if($only_slug){
					$product_terms[] = $dterm->slug;
				}else{
					$product_terms[] = array("id" => $dterm->slug, "title" => $dterm->name);
				}
			}
		}

		if($is_wpml_active && $ignore_translation){
			//$icl_adjust_id_url_filter_off = $orig_flag_value;
			THWEPO_Utils::may_on_wpml_translation($default_lang);
		}

		return $product_terms;
	}
	
	public static function load_user_roles(){
		$user_roles = array();
		
		global $wp_roles;
    	$roles = $wp_roles->roles;
		//$roles = get_editable_roles();
		foreach($roles as $key => $role){
			$user_roles[] = array("id" => $key, "title" => $role['name']);
		}		
		
		return $user_roles;
	}
	
	public static function sort_sections_by_order($a, $b){
		if(is_array($a) && is_array($b)){
			$order_a = isset($a['order']) && is_numeric($a['order']) ? $a['order'] : 0;
			$order_b = isset($b['order']) && is_numeric($b['order']) ? $b['order'] : 0;
			
			if($order_a == $order_b){
				return 0;
			}
			return ($order_a < $order_b) ? -1 : 1;
		}else if(THWEPO_Utils_Section::is_valid_section($a) && THWEPO_Utils_Section::is_valid_section($b)){
			$order_a = is_numeric($a->get_property('order')) ? $a->get_property('order') : 0;
			$order_b = is_numeric($b->get_property('order')) ? $b->get_property('order') : 0;
			
			if($order_a == $order_b){
				return 0;
			}
			return ($order_a < $order_b) ? -1 : 1;
		}else{
			return 0;
		}
	}
	
	public static function stable_uasort(&$array, $cmp_function) {
		if(count($array) < 2) {
			return;
		}
		
		$halfway = count($array) / 2;
		$array1 = array_slice($array, 0, $halfway, TRUE);
		$array2 = array_slice($array, $halfway, NULL, TRUE);
	
		self::stable_uasort($array1, $cmp_function);
		self::stable_uasort($array2, $cmp_function);
		if(call_user_func_array($cmp_function, array(end($array1), reset($array2))) < 1) {
			$array = $array1 + $array2;
			return;
		}
		
		$array = array();
		reset($array1);
		reset($array2);
		while(current($array1) && current($array2)) {
			if(call_user_func_array($cmp_function, array(current($array1), current($array2))) < 1) {
				$array[key($array1)] = current($array1);
				next($array1);
			} else {
				$array[key($array2)] = current($array2);
				next($array2);
			}
		}
		while(current($array1)) {
			$array[key($array1)] = current($array1);
			next($array1);
		}
		while(current($array2)) {
			$array[key($array2)] = current($array2);
			next($array2);
		}
		return;
	}
}

endif;