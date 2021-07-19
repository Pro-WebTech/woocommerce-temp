<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/public
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEPO_Public')):
 
class THWEPO_Public {
	private $plugin_name;
	private $version;
	private $price;
	private $file;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		$this->price = new THWEPO_Price(); //THWEPO_Price::instance();
		$this->file = new THWEPO_File(); //THWEPO_File::instance();
		
		add_action('after_setup_theme', array($this, 'define_public_hooks'));
	}

	public function define_public_hooks(){
		$this->hooks_override_add_to_cart_link();
		$this->hooks_render_product_fields();
		$this->hooks_process_product_fields();
		$this->hooks_display_item_meta();
		
		$this->price->define_hooks();
		$this->file->define_hooks(); 
	}

	public function enqueue_styles_and_scripts() {
		global $wp_scripts;
		$is_quick_view = THWEPO_Utils::is_quick_view_plugin_active();
		
		if(is_product() || ( $is_quick_view && (is_shop() || is_product_category()) ) || apply_filters('thwepo_enqueue_public_scripts', false)){
			$debug_mode = apply_filters('thwepo_debug_mode', false);
			$suffix = $debug_mode ? '' : '.min';
			$jquery_version = isset($wp_scripts->registered['jquery-ui-core']->ver) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';
			
			$this->enqueue_styles($suffix, $jquery_version);
			$this->enqueue_scripts($suffix, $jquery_version, $is_quick_view);
		}
	}
	
	private function enqueue_styles($suffix, $jquery_version) {
		//wp_register_style('select2', THWEPO_WOO_ASSETS_URL.'/css/select2.css');
		
		wp_enqueue_style('select2');
		wp_enqueue_style('jquery-ui-style', '//ajax.googleapis.com/ajax/libs/jqueryui/'. $jquery_version .'/themes/smoothness/jquery-ui.css');
		wp_enqueue_style('thwepo-timepicker-style', THWEPO_ASSETS_URL_PUBLIC.'js/timepicker/jquery.timepicker.css');
		wp_enqueue_style('thwepo-public-style', THWEPO_ASSETS_URL_PUBLIC . 'css/thwepo-public'. $suffix .'.css', $this->version);
		wp_enqueue_style('wp-color-picker');
	}

	private function enqueue_scripts($suffix, $jquery_version, $is_quick_view) {
		$in_footer = apply_filters( 'thwepo_enqueue_script_in_footer', true );
		$deps = array();
		
		wp_register_script('thwepo-timepicker-script', THWEPO_ASSETS_URL_PUBLIC.'js/timepicker/jquery.timepicker.min.js', array('jquery'), '1.0.1');
			
		if(apply_filters('thwepo_include_jquery_ui_i18n', true)){
			//wp_register_script('jquery-ui-i18n', '//ajax.googleapis.com/ajax/libs/jqueryui/'.$jquery_version.'/i18n/jquery-ui-i18n.min.js', array('jquery','jquery-ui-datepicker'), $in_footer);
			wp_register_script('jquery-ui-i18n', THWEPO_ASSETS_URL_PUBLIC.'js/jquery-ui-i18n.min.js', array('jquery','jquery-ui-datepicker'), $in_footer);
			
			$deps[] = 'jquery-ui-i18n';
		}else{
			$deps[] = 'jquery';
			$deps[] = 'jquery-ui-datepicker';
		}
		
		if(THWEPO_Utils::get_settings('disable_select2_for_select_fields') != 'yes'){
			$deps[] = 'selectWoo';
			
			$select2_languages = apply_filters( 'thwepo_select2_i18n_languages', false);
			if(is_array($select2_languages)){
				foreach($select2_languages as $lang){
					$handle = 'select2_i18n_'.$lang;
					wp_register_script($handle, '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/i18n/'.$lang.'.js', array('jquery','selectWoo'));
					$deps[] = $handle;
				}
			}
		}

		wp_enqueue_script('iris', admin_url( 'js/iris.min.js' ), array( 'jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch' ), false, 1);

		wp_register_script('thwepo-public-script', THWEPO_ASSETS_URL_PUBLIC . 'js/thwepo-public'. $suffix .'.js', $deps, $this->version, true );
		
		wp_enqueue_script('thwepo-timepicker-script');						
		wp_enqueue_script('thwepo-public-script');
		
		$wepo_var = array(
			'lang' => array( 
						'am' => THWEPO_i18n::__t('am'), 
						'pm' => THWEPO_i18n::__t('pm'),  
						'AM' => THWEPO_i18n::__t('AM'), 
						'PM' => THWEPO_i18n::__t('PM'),
						'decimal' => THWEPO_i18n::__t('.'), 
						'mins' => THWEPO_i18n::__t('mins'), 
						'hr'   => THWEPO_i18n::__t('hr'), 
						'hrs'  => THWEPO_i18n::__t('hrs'),
					),
			'language' 	  => THWEPO_Utils::get_locale_code(),
			'date_format' => THWEPO_Utils::get_jquery_date_format(wc_date_format()),
			'readonly_date_field' => apply_filters('thwepo_date_picker_field_readonly', true),
			'ajax_url'    => admin_url( 'admin-ajax.php' ),
			'price_ph_simple'	=> apply_filters('thwepo_product_price_placeholder', ''),
			'price_ph_variable'	=> apply_filters('thwepo_variable_product_price_placeholder', ''),
			'is_quick_view' => $is_quick_view,
			'change_event_disabled_fields' => apply_filters('thwepo_change_event_disabled_fields', ''),
			'price_symbol'	=> get_woocommerce_currency_symbol(),
		);
		wp_localize_script('thwepo-public-script', 'thwepo_public_var', $wepo_var);
	}
	

	/**********************************************
	***** ADD TO CART LINK OVERRIDES - START ******
	***********************************************/
	private function hooks_override_add_to_cart_link(){
		$hp_atc_link = apply_filters('thwepo_loop_add_to_cart_link_hook_priority', 20);

		add_filter('woocommerce_loop_add_to_cart_args', array($this, 'woo_loop_add_to_cart_args'), $hp_atc_link, 2);
		add_filter('woocommerce_product_add_to_cart_url', array($this, 'woo_product_add_to_cart_url'), $hp_atc_link, 2);
		add_filter('woocommerce_product_add_to_cart_text', array($this, 'woo_product_add_to_cart_text'), $hp_atc_link, 2);

		if(THWEPO_Utils::woo_version_check('3.3')){
			add_filter('woocommerce_loop_add_to_cart_link', array($this, 'woo_loop_add_to_cart_link'), $hp_atc_link, 3);
		}else{
			add_filter('woocommerce_loop_add_to_cart_link', array($this, 'woo_loop_add_to_cart_link'), $hp_atc_link, 2);
		}
	}

	public function woo_loop_add_to_cart_args($args, $product){
		if($this->is_modify_product_add_to_cart_link($product)){
			if(THWEPO_Utils::woo_version_check('3.3')){
				if(isset($args['class'])){
					$args['class'] = str_replace("ajax_add_to_cart", "", $args['class']);
				}
			}
		}
		return $args;
	}

	public function woo_product_add_to_cart_url($url, $product){
		if($this->is_modify_product_add_to_cart_link($product)){
			$url = $product->get_permalink();
		}
		return $url;
	}

	public function woo_product_add_to_cart_text($text, $product){
		$modify = $this->is_modify_product_add_to_cart_text($product);
		$product_type = THWEPO_Utils::get_product_type($product);

		if(THWEPO_Utils::has_extra_options($product)){
			if($modify){
				$text = $this->add_to_cart_text_addon($text, $product, $product_type);
			}
		}else{
			$text = $this->add_to_cart_text_default($text, $product, $product_type);
		}

		$text = apply_filters('thwepo_loop_add_to_cart_text', $text);
		return $text;
	}

	public function woo_loop_add_to_cart_link($link, $product, $args=false){
		if($this->is_modify_product_add_to_cart_link($product)){
			$class = '';
			if($args && isset($args['class'])){
				$args['class'] = str_replace("ajax_add_to_cart", "", $args['class']);
				$class = $args['class'];
				$class = $class ? $class : 'button';
			}

			if(THWEPO_Utils::is_active_theme('flatsome')){
				$product_type = THWEPO_Utils::get_product_type($product);

				$flatsome_classes = array(
					'add_to_cart_button', 
					'product_type_'.$product_type, 
					'button',
					'primary',
					'mb-0',
					'is-'.get_theme_mod( 'add_to_cart_style', 'outline' ),
					'is-small'
				);

				$class  = str_replace($flatsome_classes, "", $class);
				$class .= ' '.implode(" ", $flatsome_classes);

				$args['class'] = $class;
			}

			if(THWEPO_Utils::woo_version_check('3.3')){
				$link = sprintf( '<a href="%s" data-quantity="%s" class="%s" %s>%s</a>',
					esc_url( $product->add_to_cart_url() ),
					esc_attr( isset( $args['quantity'] ) ? $args['quantity'] : 1 ),
					esc_attr( isset( $args['class'] ) ? $args['class'] : 'button' ),
					isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
					esc_html( $product->add_to_cart_text() )
				);
			}else{
				$product_id = false;
				$product_sku = false;
	    		if(THWEPO_Utils::woo_version_check()){
	    			$product_id = $product->get_id();
	    			$product_sku = $product->get_sku();
	    		}else{
	    			$product_id = $product->id;
	    			$product_sku = $product->sku;
	    		}

				$link = sprintf( '<a rel="nofollow" href="%s" data-quantity="%s" data-product_id="%s" data-product_sku="%s" class="%s">%s</a>',
					esc_url( $product->add_to_cart_url() ),
					esc_attr( isset( $quantity ) ? $quantity : 1 ),
					esc_attr( $product_id ),
					esc_attr( $product_sku ),
					esc_attr( isset( $class ) ? $class : 'button' ),
					esc_html( $product->add_to_cart_text() )
				);
			}
		}
		return $link;
	}

	private function add_to_cart_text_addon($text, $product, $product_type){
		$new_text = '';

		if($product_type === 'simple' || $product_type === 'bundle'){
			//if($product->is_in_stock()){
				$new_text = THWEPO_Utils::get_settings('add_to_cart_text_addon_simple');
			//}
		}else if($product_type === 'variable'){
			//if($product->is_purchasable()){
				$new_text = THWEPO_Utils::get_settings('add_to_cart_text_addon_variable');
			//}
		}

		return !empty($new_text) ? esc_html(THWEPO_i18n::__t($new_text)) : __( 'Select options', 'woocommerce' );
	}

	private function add_to_cart_text_default($text, $product, $product_type){
		$new_text = '';

		if($product_type === 'simple' || $product_type === 'bundle'){
			if($product->is_in_stock()){
				$new_text = THWEPO_Utils::get_settings('add_to_cart_text_simple');
			}
		}else if($product_type === 'variable'){
			if($product->is_purchasable()){
				$new_text = THWEPO_Utils::get_settings('add_to_cart_text_variable');
			}
		}

		return !empty($new_text) ? esc_html(THWEPO_i18n::__t($new_text)) : $text;
	}

	private function is_modify_product_add_to_cart_text($product){
		$disable_override = THWEPO_Utils::get_settings('disable_loop_add_to_cart_text_override');
		$modify = $disable_override === 'yes' ? false : true;
		
		return apply_filters('thwepo_modify_loop_add_to_cart_text', $modify);
	}

	private function is_modify_product_add_to_cart_link($product){
		$disable_override = THWEPO_Utils::get_settings('disable_loop_add_to_cart_link_override');
		$modify = $disable_override === 'yes' ? false : true;
		$modify = apply_filters('thwepo_modify_loop_add_to_cart_link', $modify);
		$product_type = THWEPO_Utils::get_product_type($product);

		if($modify && THWEPO_Utils::has_extra_options($product) && $product->is_in_stock() && ($product_type === 'simple' || $product_type === 'bundle')){
			return true;
		}
		return false;
	}
	/**********************************************
	***** ADD TO CART LINK OVERRIDES - END ********
	***********************************************/
	

	/**********************************************
	***** RENDER PRODUCT FIELDS - START ***********
	***********************************************/
	private function hooks_render_product_fields(){
		$hp_display = apply_filters('thwepo_display_hooks_priority', 10);

		$hn_before_single_product = apply_filters('hook_name_before_single_product', 'woocommerce_before_single_product');
		$hns_before_single_product = apply_filters('hook_names_before_single_product', array());

		$hn_before_atc_button = apply_filters('hook_name_before_add_to_cart_button', 'woocommerce_before_add_to_cart_button');
		$hn_after_atc_button = apply_filters('hook_name_after_add_to_cart_button', 'woocommerce_after_add_to_cart_button');

		add_action( $hn_before_single_product, array($this, 'prepare_section_hook_map') ); //Deprecated
		if(is_array($hns_before_single_product)){
			foreach($hns_before_single_product as $hook_name){
				add_action($hook_name, array($this, 'prepare_section_hook_map'));
			}
		}

		if(THWEPO_Utils::is_yith_quick_view_enabled()){
			add_action('yith_wcqv_product_summary', array($this, 'prepare_section_hook_map'), 1);
		}
		if(THWEPO_Utils::is_flatsome_quick_view_enabled()){
			add_action('woocommerce_single_product_lightbox_summary',array($this, 'prepare_section_hook_map'), 1);
		}
		if(THWEPO_Utils::is_astra_quick_view_enabled()){
			add_action('astra_woo_quick_view_product_summary',array($this, 'prepare_section_hook_map'), 1);
		}
		
		add_action( $hn_before_atc_button, array($this, 'action_before_add_to_cart_button'), $hp_display);	
		add_action( $hn_after_atc_button, array($this, 'action_after_add_to_cart_button'), $hp_display);
		add_action( 'woocommerce_single_variation', array($this, 'action_before_variation_data'), 5);

		if(apply_filters('thwepo_enable_additional_positions', false)){
			add_action( 'woocommerce_before_variations_form', array($this, 'action_before_variations_form'), $hp_display);
		}
		
		//add_action( 'woocommerce_before_add_to_cart_quantity', array($this, 'action_before_add_to_cart_quantity'), $hp_display);
		//add_action( 'woocommerce_after_add_to_cart_quantity', array($this, 'action_after_add_to_cart_quantity'), $hp_display);
		//add_action( 'woocommerce_before_variations_form', array($this, 'action_before_variations_form'), $hp_display);
		//add_action( 'woocommerce_after_variations_form', array($this, 'action_after_variations_form'), $hp_display);
		//add_action( 'woocommerce_before_single_variation', array($this, 'action_before_single_variation'), $hp_display);
		//add_action( 'woocommerce_after_single_variation', array($this, 'action_after_single_variation'), $hp_display);
		//add_action( 'woocommerce_single_variation', array($this, 'action_single_variation_90'), 90);

		add_action( $hn_before_atc_button, array($this, 'render_price_table'), $hp_display+10);
	}

	/**
	 * Prepare section hook map to display section and fields in product, quickview pages.
	 */
	public function prepare_section_hook_map(){ 
		global $product;
		
		$product_id = THWEPO_Utils::get_product_id($product);
		$categories = THWEPO_Utils::get_product_categories($product_id);
		$tags 		= THWEPO_Utils::get_product_tags($product_id);
		
		$sections = THWEPO_Utils::get_custom_sections();
		$section_hook_map = array();
		
		if($sections && is_array($sections) && !empty($sections)){
			foreach($sections as $section_name => $section){
				$section = THWEPO_Utils_Section::prepare_section_and_fields($section, $product_id, $categories, $tags);
				
				if($section){
					$hook_name = $section->get_property('position');

					if(array_key_exists($hook_name, $section_hook_map) && is_array($section_hook_map[$hook_name])) {
						$section_hook_map[$hook_name][$section_name] = $section;
					}else{
						$section_hook_map[$hook_name] = array();
						$section_hook_map[$hook_name][$section_name] = $section;
					}
				}
			}
		}
		
		$this->sections_extra = $section_hook_map;
	}
	
	public function action_before_add_to_cart_button(){
		$this->render_disabled_field_names_hidden_field();
		$this->render_sections('woo_before_add_to_cart_button');
	}
	public function action_after_add_to_cart_button(){
		$this->render_sections('woo_after_add_to_cart_button');
	}
	public function action_before_variations_form(){
		$this->render_sections('woo_before_variations_form');
	}
	public function action_after_variations_form(){
		$this->render_sections('woo_after_variations_form');
	}
	public function action_before_add_to_cart_quantity(){
		$this->render_sections('woo_before_add_to_cart_quantity');
	}
	public function action_after_add_to_cart_quantity(){
		$this->render_sections('woo_after_add_to_cart_quantity');
	}
	public function action_before_single_variation(){
		$this->render_sections('woo_before_single_variation');
	}
	public function action_after_single_variation(){
		$this->render_sections('woo_after_single_variation');
	}
	public function action_before_variation_data(){
		$this->render_sections('woo_single_variation_5');
	}
	public function action_single_variation_90(){
		$this->render_sections('woo_single_variation_90');
	}

	public function render_disabled_field_names_hidden_field(){
		global $product;
		$prod_field_names = THWEPO_Utils_Section::get_product_fields($product, true);
		$prod_field_names = is_array($prod_field_names) ? implode(",", $prod_field_names) : '';
		
		echo '<input type="hidden" id="thwepo_product_fields" name="thwepo_product_fields" value="'.$prod_field_names.'"/>';
		echo '<input type="hidden" id="thwepo_disabled_fields" name="thwepo_disabled_fields" value=""/>';
		echo '<input type="hidden" id="thwepo_disabled_sections" name="thwepo_disabled_sections" value=""/>';
	}
	
	private function render_sections($hook_name){
		global $product;
		$product_type = THWEPO_Utils::get_product_type($product);
		
		$sections = THWEPO_Utils::get_sections_by_hook($this->sections_extra, $hook_name);
		if($sections){						
			foreach($sections as $section_name => $section){
				$section_html = THWEPO_Utils_Section::prepare_section_html($section, $product_type);
				echo $section_html;
			}
		}
	}

	public function render_price_table(){
		$this->price->render_price_table();
	}
	/**********************************************
	***** RENDER PRODUCT FIELDS - START ***********
	***********************************************/


	/**********************************************
	***** PROCESS PRODUCT FIELDS - START **********
	***********************************************/
	private function hooks_process_product_fields(){
		$hp_validation = apply_filters('thwepo_add_to_cart_validation_hook_priority', 99);
		$hp_add_item_data = apply_filters('thwepo_add_cart_item_data_hook_priority', 10);
		$hp_new_order = apply_filters('thwepo_new_order_item_hook_priority', 10);

		add_filter('woocommerce_add_to_cart_validation', array($this, 'add_to_cart_validation'), $hp_validation, 6);
		add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), $hp_add_item_data, 3);

		if(THWEPO_Utils::woo_version_check()){
			add_action( 'woocommerce_new_order_item', array($this, 'woo_new_order_item'), $hp_new_order, 3);
		}else{
			//Older version WooCommerce support
			add_action( 'woocommerce_add_order_item_meta', array($this, 'woo_add_order_item_meta'), 1, 3 ); 
		}

		add_filter('woocommerce_order_again_cart_item_data', array($this, 'filter_order_again_cart_item_data'), 10, 3);
	}

	public function add_to_cart_validation($passed, $product_id, $quantity, $variation_id=false, $variations=false, $cart_item_data=false){ 
		$extra_options = $this->prepare_product_options(false);
		$ignore_unposted = apply_filters( 'thwepo_ignore_unposted_fields', false );
		
		if($extra_options){
			//$upload_fields = array();
			
			foreach($extra_options as $field_name => $field){
				$type = $field->get_property('type');
				$is_posted = isset($_POST[$field_name]) || isset($_REQUEST[$field_name]) ? true : false;
				$posted_value = $this->get_posted_value($field_name, $type);
				
				if(($type === 'radio' || $type === 'multiselect' || $type === 'checkboxgroup') && (!$is_posted || !$posted_value) && !$ignore_unposted){
					$passed = $this->validate_field($passed, $field, $posted_value);
					
				}else if($type === 'file'){
					//$upload_fields[$field_name] = $field;
					$file = isset($_FILES[$field_name]) ? $_FILES[$field_name] : false;
					// Change starts here - no if else before. Was only the statement inside else
					if( $field->get_property('multiple_file') === 'yes' ){
						if( isset( $file['name'] ) && array_filter( $file['name'] ) ){
							if( $this->file->validate_file_count( $file['name'], $field ) ){
								foreach ($file['name'] as $index => $fname) {
									$nfile = wp_list_pluck($file, $index);
									$passed = $this->file->validate_file($passed, $field, $nfile);
								}
							}else{
								$passed = false;
							}
						}
					}else{
						$passed = $this->file->validate_file($passed, $field, $file);
					}
					// Change ends here
				}else if($is_posted){
					$passed = $this->validate_field($passed, $field, $posted_value);
				}
			}
			
			/*if($passed){
				foreach($upload_fields as $name => $field){
					$uploaded = $this->file->upload_file($_FILES, $name, $field);
					if(isset($uploaded['error'])){
						$this->wepo_add_error('<strong>'.$title.'</strong> '. $upload['error']);
						$passed = false;
					}
				}
			}*/
		}
		return $passed;
	}

	private function validate_field($passed, $field, $posted_value){
		$name  = $field->get_property('name');
		$type  = $field->get_property('type');
		$value = $this->get_posted_value($name, $type);
		
		if(is_array($value)){
			foreach($value as $key => $val){
				if(THWEPO_Utils::is_blank($val)){
					unset($value[$key]);
				}
			}
		}
		
		if($field->get_property('required') && empty($value)) {
			$this->wepo_add_error( sprintf(THWEPO_i18n::__t('Please enter a value for %s'), $field->get_property('title')) );
			$passed = false;
		}else{
			$title = THWEPO_i18n::__t(wc_clean($field->get_property('title')));
			$validators = $field->get_property('validate');
			$validators = !empty($validators) ? explode("|", $validators) : false;

			if($validators && !empty($value)){
				foreach($validators as $validator){
					switch($validator) {
						case 'number' :
							if(!is_numeric($value)){
								$this->wepo_add_error('<strong>'.$title.'</strong> '. sprintf(THWEPO_i18n::__t('(%s) is not a valid number.'), $value));
								$passed = false;
							}
							break;

						case 'email' :
							if(!is_email($value)){
								$this->wepo_add_error('<strong>'.$title.'</strong> '. sprintf(THWEPO_i18n::__t('(%s) is not a valid email address.'), $value));
								$passed = false;
							}
							break;
						default:
							$custom_validators = THWEPO_Utils::get_settings('custom_validators');
							$custom_validator  = is_array($custom_validators) && isset($custom_validators[$validator]) ? $custom_validators[$validator] : false;
							
							if(is_array($custom_validator)){
								$pattern = $custom_validator['pattern'];
								
								if(preg_match($pattern, $value) === 0) {
									$this->wepo_add_error(sprintf(THWEPO_i18n::__t($custom_validator['message']), $title));
									$passed = false;
								}
							}else{
								$con_validators = THWEPO_Utils::get_settings('confirm_validators');
								$cnf_validator = is_array($con_validators) && isset($con_validators[$validator]) ? $con_validators[$validator] : false;
								if(is_array($cnf_validator)){
									$cfield = $cnf_validator['pattern'];
									$cvalue = $this->get_posted_value($cfield);
									
									if($value && $cvalue && $value != $cvalue) {
										$this->wepo_add_error(sprintf(THWEPO_i18n::__t($cnf_validator['message']), $title));
										$passed = false;
									}
								}
							}
							break;
					}
				}
			}
		}
		return $passed;
	}

	public function add_cart_item_data($cart_item_data, $product_id = 0, $variation_id = 0){
		$skip = (isset($cart_item_data['bundled_by']) && apply_filters('thwepo_skip_extra_options_for_bundled_items', true)) ? true : false;

		$skip = apply_filters('thwepo_skip_extra_options_for_cart_item', $skip, $cart_item_data, $product_id, $variation_id);
		
		if(!$skip){
			$extra_cart_item_data = $this->prepare_extra_cart_item_data();
			
			if($extra_cart_item_data){
				if(apply_filters('thwepo_set_unique_key_for_cart_item', false, $cart_item_data, $product_id, $variation_id)){
					$cart_item_data['unique_key'] = md5( microtime().rand() );
				}
				$cart_item_data['thwepo_options'] = $extra_cart_item_data;
			}
		}
		return $cart_item_data;
	}

	public function woo_new_order_item($item_id, $item, $order_id){
		$legacy_values = is_object($item) && isset($item->legacy_values) ? $item->legacy_values : false;
		if($legacy_values){
			$extra_options = isset($legacy_values['thwepo_options']) ? $legacy_values['thwepo_options'] : false;
			$product_price = isset($legacy_values['thwepo-original_price']) ? $legacy_values['thwepo-original_price'] : false;
			
			$this->add_order_item_meta($item_id, $item, $extra_options, $product_price);
		}
	}
	
	public function woo_add_order_item_meta( $item_id, $values, $cart_item_key ) {
		if($values && is_array($values)){
			$extra_options = isset($values['thwepo_options']) ? $values['thwepo_options'] : false;
			$product_price = isset($values['thwepo-original_price']) ? $values['thwepo-original_price'] : false;
			
			$this->add_order_item_meta($item_id, $values, $extra_options, $product_price);
		}
	}

	public function add_order_item_meta($item_id, $item, $extra_options, $product_price) {
		if($extra_options){
			$product_info = array();
			$product_info['id'] = $item['product_id'];
			$product_info['price'] = $product_price;

			foreach($extra_options as $name => $data){
				$ftype = isset($data['field_type']) ? $data['field_type'] : false;
				$value = isset($data['value']) ? $data['value'] : '';
				
				if($ftype === 'file'){
					$value = json_encode($value);//THWEPO_Utils::get_file_display_name($value);
				}else{
					$value = is_array($value) ? implode(",", $value) : $value;
				}
				
				//$display_value = $value;
				$value = apply_filters('thwepo_add_order_item_meta_value', $value, $name, $value);

				if($ftype != 'file'){
					$value = trim(stripslashes($value));
				}

				$price_html = $this->price->get_display_price_item_meta($data, $product_info, true);
				if($price_html){
					$price_html = apply_filters('thwepo_add_order_item_meta_price_html', $price_html, $name, $data);
					$price_meta_key_prefix = $this->get_order_item_price_meta_key_prefix();

					wc_add_order_item_meta( $item_id, $price_meta_key_prefix.$name, trim(stripslashes($price_html)) );
				}
				
				/*if($this->is_show_option_price_in_order($name, $data)){
					$display_value .= $price_html;
				}*/

				wc_add_order_item_meta($item_id, $name, $value);
			}
		}
	}

	private function prepare_product_options($names_only = true){
		$final_fields = array();
		$allow_get_method = THWEPO_Utils::get_settings('allow_get_method');
		$posted = $allow_get_method ? $_REQUEST : $_POST;

		$product_fields  = isset($posted['thwepo_product_fields']) ? wc_clean($posted['thwepo_product_fields']) : '';
		$disabled_fields = isset($posted['thwepo_disabled_fields']) ? wc_clean($posted['thwepo_disabled_fields']) : '';
		$disabled_sections = isset($posted['thwepo_disabled_sections']) ? wc_clean($posted['thwepo_disabled_sections']) : '';

		$prod_fields = $product_fields ? explode(",", $product_fields) : array();
		$dis_sections  = $disabled_sections ? explode(",", $disabled_sections) : array();
		$dis_fields  = $disabled_fields ? explode(",", $disabled_fields) : array();
		
		if(is_array($dis_sections)){
			$sections = THWEPO_Utils::get_custom_sections();
			if($sections && is_array($sections)){
				foreach($dis_sections as $sname) {
					$section = isset($sections[$sname]) ? $sections[$sname] : false;
					if(THWEPO_Utils_Section::is_valid_section($section)){
						$sfields = THWEPO_Utils_Section::get_fields($section);
						foreach($sfields as $name => $field) {
							if(THWEPO_Utils_Field::is_enabled($field) && ($key = array_search($name, $prod_fields)) !== false){
								unset($prod_fields[$key]);
							}
							/*if(isset($prod_fields[$name])){
								unset($prod_fields[$name]);
							}*/
						}
					}
				}
			}
		}
		
		$result = array_diff($prod_fields, $dis_fields);
		if($names_only){
			$final_fields = $result;
		}else{
			$extra_options = THWEPO_Utils::get_custom_fields_full(true);
			foreach($result as $name) {
				if(isset($extra_options[$name])){
					$final_fields[$name] = $extra_options[$name];
				}
			}
		}
		
		return $final_fields;
	}

	private function prepare_extra_cart_item_data(){
		$extra_data = array();
		$extra_options = $this->prepare_product_options(false);
		
		if($extra_options){
			foreach($extra_options as $name => $field){
				$type = $field->get_property('type');
				$posted_value = false;
				
				if($type === 'file'){
					if(isset($_FILES[$name])){
						$file = $_FILES[$name];
						if( $field->get_property('multiple_file') === 'yes' ){
							$posted_files = [];
							if( isset( $file['name'] ) && is_array( $file['name'] ) ){
								foreach ($file['name'] as $index => $fname) {
									$nfile = wp_list_pluck($file, $index);
									$posted_value = $this->file->prepare_file_upload( $nfile, $name, $field );
									if( !$posted_value){
										continue;
									}
									array_push( $posted_files, $posted_value);
								}
								$posted_value = $posted_files;
							}
						}else{
							$posted_value = $this->file->prepare_file_upload( $file, $name, $field );
							if( !$posted_value ){
								continue;
							}
						}
					}
				}else{
					$posted_value = $this->get_posted_value($name, $field->get_property('type'));
				}
				
				if($posted_value) {
					$price_type = $field->get_property('price_type');
					$price_unit = $field->get_property('price_unit');
					$quantity   = false;
					
					if($price_type && ($price_type === 'dynamic' || $price_type === 'dynamic-excl-base-price')){
						if($price_unit && !is_numeric($price_unit)){
							$qty_field = isset($extra_options['price_unit']) ? $extra_options['price_unit'] : false;
							$quantity = $qty_field && $this->get_posted_value($qty_field->get_property('name'), $qty_field->get_property('type'));
							$price_unit = 1;
						}
					}else{
						$price_unit = 0;
					}
				
					$data_arr = array();
					$data_arr['field_type']  	  		= $field->get_property('type');
					$data_arr['name']  			  		= $name;
					$data_arr['label'] 		 	  		= THWEPO_Utils_Field::get_display_label($field);
					$data_arr['value'] 		 	  		= $posted_value;
					$data_arr['price']       	  		= $field->get_property('price');
					$data_arr['price_type']  	  		= $price_type;
					$data_arr['price_unit']  	  		= $price_unit;
					$data_arr['price_min_unit']   		= $field->get_property('price_min_unit');
					$data_arr['quantity'] 		  		= $quantity;
					$data_arr['price_field'] 	  		= $field->get_property('price_field');
					$data_arr['options']          		= $field->get_property('options');
					$data_arr['hide_in_cart']     		= $field->get_property('hide_in_cart');
					$data_arr['hide_in_checkout'] 		= $field->get_property('hide_in_checkout');
					$data_arr['show_price_in_order'] 	= $field->get_property('show_price_in_order');
					$data_arr['price_flat_fee'] 		= $field->get_property('price_flat_fee');
					
					$extra_data[$name] = $data_arr;
				}
			}
		}
		$extra_data = apply_filters('thwepo_extra_cart_item_data', $extra_data);
		return $extra_data;
	}
	/**********************************************
	***** PROCESS PRODUCT FIELDS - END ************
	***********************************************/


	/**********************************************
	***** DISPLAY PRODUCT ITEM META - START *******
	***********************************************/
	private function hooks_display_item_meta(){
		add_filter( 'woocommerce_get_item_data', array($this, 'filter_get_item_data'), 10, 2 );
		add_filter( 'woocommerce_order_item_get_formatted_meta_data', array($this, 'filter_order_item_get_formatted_meta_data'), 10, 2);
	}

	// Filter item data to allow 3rd parties to add more to the array.
	public function filter_get_item_data($item_data, $cart_item = null){
		if(apply_filters('thwepo_display_custom_cart_item_meta', true)){
			$item_data = is_array($item_data) ? $item_data : array();		
			$extra_options = $cart_item && isset($cart_item['thwepo_options']) ? $cart_item['thwepo_options'] : false;
			$product_price = $cart_item && isset($cart_item['thwepo-original_price']) ? $cart_item['thwepo-original_price'] : false;
			$display_option_text = apply_filters('thwepo_order_item_meta_display_option_text', true);
			
			if($extra_options){
				$product_info = array();
				$product_info['id'] = $cart_item['product_id'];
				$product_info['price'] = $product_price;
				
				foreach($extra_options as $name => $data){
					//if(isset($data['value']) && isset($data['label'])) {
					if($this->is_show_option_in_cart($name, $data)){
						$ftype = isset($data['field_type']) ? $data['field_type'] : false;
						$value = isset($data['value']) ? $data['value'] : '';
						
						if($ftype === 'file'){
							$value = THWEPO_Utils::get_file_display_name($value, apply_filters('thwepo_item_display_filename_as_link', false, $name));
							//$value = THWEPO_Utils::get_filename_from_path($value);
						}else{
							$value = is_array($value) ? implode(",", $value) : $value;
							$value = $display_option_text ? THWEPO_Utils::get_option_display_value($name, $value, $data) : $value;
						}
						
						if($this->is_show_option_price_in_cart($name, $data)){
							$value .= $this->price->get_display_price_item_meta($data, $product_info);
						}
						
						$item_data[] = array("name" => THWEPO_i18n::__t($data['label']), "value" => trim(stripslashes($value)));
					}
				}
			}
		}
		return $item_data;
	}

	public function filter_order_item_get_formatted_meta_data($formatted_meta, $order_item){
		if(!empty($formatted_meta)){
			//$name_title_map = THWEPO_Utils::get_options_name_title_map();
			$custom_fields = THWEPO_Utils::get_custom_fields_full();
			$display_option_text = apply_filters('thwepo_order_item_meta_display_option_text', true);
			$price_meta_key_prefix = $this->get_order_item_price_meta_key_prefix();
			
			//if($name_title_map){
			if($custom_fields){
				foreach($formatted_meta as $key => $meta){
					//if(array_key_exists($meta->key, $name_title_map)) {
					if(array_key_exists($meta->key, $custom_fields)) {
						$field = $custom_fields[$meta->key];

						if($this->is_show_option_in_order($field)){
							$type = $field->get_property('type');
							$display_key = THWEPO_Utils_Field::get_display_label($field);
							$value = $meta->value;
							$display_value = '';
							$price_meta_key = $price_meta_key_prefix.$meta->key;
							
							if($type === 'file'){
								$value = THWEPO_Utils::get_file_display_name_order($value, apply_filters('thwepo_order_display_filename_as_link', true, $meta->key));
								$display_value = $value;
							}else{
								$display_value = $display_option_text ? THWEPO_Utils::get_option_display_value($meta->key, $value, null) : $value;
								//$display_value = $display_option_text ? THWEPO_Utils::get_option_display_value($meta->key, $meta->value, null) : $meta->value;
							}

							if($this->is_show_option_price_in_order($field)){
								$price_html = $order_item->get_meta($price_meta_key);
								if($price_html){
									$display_value .= ' '.$price_html;
								}
							}

							$display_key = apply_filters('thwepo_order_item_display_meta_key', $display_key, $meta, $order_item);
							$display_value = apply_filters('thwepo_order_item_display_meta_value', $display_value, $meta, $order_item);

							$formatted_meta[$key] = (object) array(
								'key'           => $meta->key,
								'value'         => $value,
								//'display_key'   => apply_filters( 'woocommerce_order_item_display_meta_key', $name_title_map[$meta->key] ),
								'display_key'   => THWEPO_i18n::__t($display_key),
								'display_value' => wpautop( make_clickable($display_value) ),
							);
						}else{
							unset($formatted_meta[$key]);
						}
					}else{
						if(THWEPO_Utils::startsWith($meta->key, $price_meta_key_prefix)){
							unset($formatted_meta[$key]);
						}
					}
				}
			}
		}
		return $formatted_meta;
	}

	private function is_show_option_in_cart($name, $data){
		$show = true;

		if(isset($data['value']) && isset($data['label'])){
			if(is_checkout()){
				$hide_in_checkout = isset($data['hide_in_checkout']) ? $data['hide_in_checkout'] : false;

				$show = $hide_in_checkout ? false : true;
				$show = apply_filters('thwepo_display_custom_checkout_item_meta', $show, $name);

			}else if(is_cart()){
				$hide_in_cart = isset($data['hide_in_cart']) ? $data['hide_in_cart'] : false;

				$show = $hide_in_cart ? false : true;
				$show = apply_filters('thwepo_display_custom_cart_item_meta', $show, $name);

			}else{ //To handle mini cart view. This is same as cart page behaviour.
				$hide_in_cart = isset($data['hide_in_cart']) ? $data['hide_in_cart'] : false;
				$show = $hide_in_cart ? false : true;
				$show = apply_filters('thwepo_display_custom_cart_item_meta', $show, $name);
			}
		}else{
			$show = false;
		}

		return $show;
	}

	private function is_show_option_in_order($field){
		$show = true;

		if($field){
			$capability = apply_filters('thwepo_required_capability', 'manage_woocommerce');

			if(current_user_can($capability)){
				$show = $field->get_property('hide_in_order_admin') ? false : $show;
				$show = apply_filters('thwepo_display_custom_order_item_meta_admin', $show, $field->get_property('name'));
			}else{
				$show = $field->get_property('hide_in_order') ? false : $show;
				$show = apply_filters('thwepo_display_custom_order_item_meta', $show, $field->get_property('name'));
			}
		}else{
			$show = false;
		}

		return $show;
	}

	private function is_show_option_price_in_cart($name, $data){
		$show = true;

		if(is_checkout()){
			$show = isset($data['show_price_in_order']) ? filter_var($data['show_price_in_order'], FILTER_VALIDATE_BOOLEAN) : true;
			$show = apply_filters('thwepo_show_price_for_item_meta', $show, $name); //Deprecated
			$show = apply_filters('thwepo_show_option_price_in_checkout', $show, $name);

		}else if(is_cart()){
			$show = isset($data['show_price_in_order']) ? filter_var($data['show_price_in_order'], FILTER_VALIDATE_BOOLEAN) : true;
			$show = apply_filters('thwepo_show_price_for_item_meta', $show, $name); //Deprecated
			$show = apply_filters('thwepo_show_option_price_in_cart', $show, $name);

		}else{ //To handle mini cart view. This is same as cart page behaviour.
			$show = isset($data['show_price_in_order']) ? filter_var($data['show_price_in_order'], FILTER_VALIDATE_BOOLEAN) : true;
			$show = apply_filters('thwepo_show_price_for_item_meta', $show, $name); //Deprecated
			$show = apply_filters('thwepo_show_option_price_in_cart', $show, $name);
		}

		return $show;
	}

	private function is_show_option_price_in_order($field){
		$show = true;

		if($field){
			$name = $field->get_property('name');
			$capability = apply_filters('thwepo_required_capability', 'manage_woocommerce');

			if(current_user_can($capability)){
				$show = $field->get_property('show_price_in_order') ? true : false;

				$show = apply_filters('thwepo_show_price_for_order_formatted_meta', $show, $name); //Deperecated
				$show = apply_filters('thwepo_show_option_price_in_order_admin', $show, $name);
			}else{
				$show = $field->get_property('show_price_in_order') ? true : false;

				$show = apply_filters('thwepo_show_price_for_order_formatted_meta', $show, $name); //Deperecated
				$show = apply_filters('thwepo_show_option_price_in_order', $show, $name);
			}
		}else{
			$show = false;
		}

		return $show;
	}
	/**********************************************
	***** DISPLAY PRODUCT ITEM META - START *******
	***********************************************/


   /***************************************************
	************** ORDER AGAIN DATA - START ***********
	***************************************************/
	public function filter_order_again_cart_item_data($cart_item_data, $item, $order){
		$extra_cart_item_data = $this->prepare_order_again_extra_cart_item_data($item, $order);
			
		if($extra_cart_item_data){
			$cart_item_data['thwepo_options'] = $extra_cart_item_data;
		}
		return $cart_item_data;
	}

	private function prepare_order_again_extra_cart_item_data($item, $order){
		$extra_data = array();

		if($item){
			$meta_data = $item->get_meta_data();
			if(is_array($meta_data)){
				$extra_options = THWEPO_Utils::get_custom_fields_full();

				foreach($meta_data as $key => $meta){
					if(array_key_exists($meta->key, $extra_options)) {
						$field = $extra_options[$meta->key];

						if($meta->value){
							$price_type = $field->get_property('price_type');
							$price_unit = $field->get_property('price_unit');
							$quantity   = false;

							$type = $field->get_property('type');
							$value = $meta->value;
							
							if($type === 'file'){
								$value = json_decode($value, true);
							}else if(THWEPO_Utils_Field::is_multi_option_field($type)){
								$value = is_string($value) ? explode(",", $value) : $value;
							}
							
							if($price_type && ($price_type === 'dynamic' || $price_type === 'dynamic-excl-base-price')){
								if($price_unit && !is_numeric($price_unit)){
									$qty_field = isset($extra_options['price_unit']) ? $extra_options['price_unit'] : false;
									$quantity = $qty_field && $this->get_posted_value($qty_field->get_property('name'), $qty_field->get_property('type'));
									$price_unit = 1;
								}
							}else{
								$price_unit = 0;
							}

							$data_arr = array();
							$data_arr['field_type']  	  = $type;
							$data_arr['name']  			  = $meta->key;
							$data_arr['label'] 			  = THWEPO_Utils_Field::get_display_label($field);
							$data_arr['value'] 	          = $value;
							$data_arr['price']       	  = $field->get_property('price');
							$data_arr['price_type']  	  = $price_type;
							$data_arr['price_unit']  	  = $price_unit;
							$data_arr['price_min_unit']   = $field->get_property('price_min_unit');
							$data_arr['quantity'] 		  = $quantity;
							$data_arr['price_field'] 	  = $field->get_property('price_field');
							$data_arr['options']          = $field->get_property('options');
							$data_arr['hide_in_cart']     = $field->get_property('hide_in_cart');
							$data_arr['hide_in_checkout'] = $field->get_property('hide_in_checkout');
							$data_arr['price_flat_fee']   = $field->get_property('price_flat_fee');
							
							$extra_data[$meta->key] = $data_arr;
						}
					}
				}
			}
		}

		return $extra_data;
	}
   /***************************************************
	************** ORDER AGAIN DATA - END ***********
	***************************************************/


	public function get_order_item_price_meta_key_prefix(){
		return apply_filters('thwepo_add_order_item_price_meta_key_prefix', '_thwepoprice_');
	}

	public function get_posted_value($name, $type = false){
		$is_posted = isset($_POST[$name]) || isset($_REQUEST[$name]) ? true : false;
		$value = false;
		
		if($is_posted){
			$value = isset($_POST[$name]) && $_POST[$name] ? $_POST[$name] : false;
			$value = empty($value) && isset($_REQUEST[$name]) ? $_REQUEST[$name] : $value;

			if($type === 'textarea'){
				$value = sanitize_textarea_field(wp_unslash($value));

			}else if($type === 'multiselect' || $type === 'checkboxgroup'){
				$value = wc_clean(wp_unslash(($value)));

			}else{
				if(is_string($value)){
					//$value = sanitize_text_field($value);
					$value = wc_clean(wp_unslash(($value)));
				}
			}
		}

		$value = apply_filters('thwepo_add_to_cart_posted_value', $value, $name, $type);
		return $value;
	}
	
	public function wepo_add_error($msg){
		if(THWEPO_Utils::woo_version_check('2.3.0')){
			wc_add_notice($msg, 'error');
		} else {
			WC()->add_error($msg);
		}
	}

	/*private function remove_disabled_fields($extra_options){
		$disabled_fields = isset( $_POST['thwepo_disabled_fields'] ) ? wc_clean( $_POST['thwepo_disabled_fields'] ) : '';
		
		if(is_array($extra_options) && $disabled_fields){
			$dis_fields = explode(",", $disabled_fields);
			
			if(is_array($dis_fields) && !empty($dis_fields)){
				foreach($extra_options as $fname => $field) {
					if(in_array($fname, $dis_fields)){
						unset($extra_options[$fname]);
					}
				}
			}
		}
		return $extra_options;
	}*/
}

endif;