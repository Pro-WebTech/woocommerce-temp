<?php
/**
 * 
 *
 * @link       https://themehigh.com
 * @since      3.0.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEPO_Price')):

class THWEPO_Price {
	public $flat_fee_names = array();

	public function __construct() {
		
	}

	public function define_hooks(){
		$hp_bf_total = apply_filters('thwepo_before_calculate_totals_hook_priority', 1);
		$hp_bf_mini_cart = apply_filters('thwepo_before_mini_cart_hook_priority', 10);

		if(THWEPO_Utils::is_rightpress_dynamic_pricing_plugin_active()){
			add_action('woocommerce_cart_loaded_from_session',array($this, 'calculate_cart_extra_costs'), $hp_bf_total, 1);
		}else{
			add_action('woocommerce_before_calculate_totals', array($this, 'calculate_cart_extra_costs'), $hp_bf_total, 1);
		}

		add_action('woocommerce_before_mini_cart', array($this, 'recalculate_cart_totals'), $hp_bf_mini_cart);
		add_filter('woocommerce_cart_totals_fee_html', array($this, 'cart_totals_fee_html'), 10, 2);

		add_action('wp_ajax_thwepo_calculate_extra_cost', array($this, 'calculate_extra_price_ajax_handler'), 10);
    	add_action('wp_ajax_nopriv_thwepo_calculate_extra_cost', array($this, 'calculate_extra_price_ajax_handler'), 10);
		
		$this->add_dynamic_pricing_plugin_support();
	}

	/********************************************
	********* Cart Request Handler - START ******
	********************************************/
	public function recalculate_cart_totals(){
		WC()->cart->calculate_totals();
	}

	public function cart_totals_fee_html($cart_totals_fee_html, $fee){
		if($fee->taxable){
			$name = $fee->name;

			if($name && in_array($name, $this->flat_fee_names)){
				$tax_display_cart = get_option('woocommerce_tax_display_cart');

				if ($tax_display_cart === 'incl'){
					if ( $fee->tax > 0 && ! wc_prices_include_tax() ) {
						$cart_totals_fee_html .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
					}
				} else {
					if ( $fee->tax > 0 && wc_prices_include_tax() ) {
						$cart_totals_fee_html .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
					}
				}
			}
		}
		
		return $cart_totals_fee_html;
	}

	public function calculate_cart_extra_costs($cart_object){
		$cart_flat_fees = array();
		$is_dynamic_plugin_active = THWEPO_Utils::is_woo_dynamic_pricing_plugin_active();

		foreach($cart_object->cart_contents as $key => &$value) {
			$skip_extra_cost = $is_dynamic_plugin_active && $value && isset($value['discounts']);

			if($skip_extra_cost){
				continue;
			}

			$request_data = $this->prepare_extra_price_request_data_cart($value);

			if(isset($request_data['product_price']) && is_numeric($request_data['product_price'])){
				$value['thwepo-original_price'] = $request_data['product_price'];
			}

			if($request_data) {
				try{
					$result = $this->calculate_extra_price($request_data);
					$result = $this->prepare_extra_price_response_data($result);

					if($result['code'] === 'E000'){
						$price_data  = $result['price_data'];

						$new_price = $price_data['price_final'];
						$flat_fees = isset($price_data['flat_fees']) ? $price_data['flat_fees'] : false;
						
						if(is_numeric($new_price)){
							$value['data']->set_price($new_price);
						}
						
						if(is_array($flat_fees)){
							$product = isset($request_data['product']) ? $request_data['product'] : false;

							$car_item_flat_fees = array(
								'product' => $product,
								'fees'    => $flat_fees,
							);

							$cart_flat_fees[$key] = $car_item_flat_fees;
						}
					}
				} catch (Exception $e) { }
			}
		}

		$this->add_cart_flat_fees($cart_flat_fees);
	}

	private function calculate_cart_item_extra_costs($cart_item, $return_extra_cost=false){
		$return = 0;
		$request_data  = $this->prepare_extra_price_request_data_cart($cart_item);
		$product_price = isset($request_data['product_price']) ? $request_data['product_price'] : false;

		if($request_data) {
			try{
				$result = $this->calculate_extra_price($request_data);
				$result = $this->prepare_extra_price_response_data($result);

				if($result['code'] === 'E000'){
					$price_data  = $result['price_data'];

					if(isset($price_data['price_extra']) && is_numeric($price_data['price_extra'])){
						$return = $price_data['price_extra'];
					}
				}
			} catch (Exception $e) { }
		}

		if(is_numeric($product_price)){
			$cart_item['thwepo-original_price'] = $product_price;

			if(!$return_extra_cost){
				$return += $product_price;
			}
		}

		return $return;
	}

	private function prepare_extra_price_request_data_cart($cart_item){
		$data = false;
		$extra_options = isset($cart_item['thwepo_options']) ? $cart_item['thwepo_options'] : false;

		if(is_array($extra_options)){
			$price_info_list = array();

			$product_id = isset($cart_item['product_id']) ? $cart_item['product_id'] : false;
			$product    = isset($cart_item['data']) ? $cart_item['data'] : '';

			foreach($extra_options as $name => $data){
				if(isset($data['price_field']) && $data['price_field']){
					$price_info = $this->prepare_extra_price_request_data_cart_single($data);
					$price_info_list[$name] = $price_info;
				}
			}

			$original_price = floatval( $product->get_price('') );
			if(isset($cart_item['thwepo-original_price']) && is_numeric($cart_item['thwepo-original_price'])){
				$original_price = floatval( $cart_item['thwepo-original_price'] );
			}

			$data = array();
			$data['product_id']    = $product_id;
			$data['product_price'] = $original_price;
			$data['product']       = $product;
			$data['price_info']    = $price_info_list;
		}

		return $data;
	}
	private function prepare_extra_price_request_data_cart_single($data){
		$price_info = false;

		if(is_array($data)){
			$field_type = isset($data['field_type']) ? $data['field_type'] : '';

			$price_info = array();
			$price_info['name']  = isset($data['name']) ? $data['name'] : '';
			$price_info['label'] = isset($data['label']) ? $data['label'] : '';
			$price_info['value'] = isset($data['value']) ? $data['value'] : '';

			if($this->is_price_field_type_option($field_type)){
				$of_price_info = $this->prepare_option_field_price_props($data);

				$price_info['price'] = isset($of_price_info['price']) ? $of_price_info['price'] : '';
				$price_info['price_type'] = isset($of_price_info['price_type']) ? $of_price_info['price_type'] : '';
				$price_info['price_unit'] = '';
				$price_info['price_min_unit'] = '';
			}else{
				$price_info['price'] = isset($data['price']) ? $data['price'] : '';
				$price_info['price_type'] = isset($data['price_type']) ? $data['price_type'] : '';
				$price_info['price_unit'] = isset($data['price_unit']) ? $data['price_unit'] : '';
				$price_info['price_min_unit'] = isset($data['price_min_unit']) ? $data['price_min_unit'] : '';
			}

			//$price_info['multiple']    = isset($data['multiple']) ? $data['multiple'] : '';
			$price_info['multiple']    = $this->is_price_field_type_multi_option($field_type);
			$price_info['quantity']    = isset($data['quantity']) ? $data['quantity'] : '';
			$price_info['is_flat_fee'] = isset($data['price_flat_fee']) && $data['price_flat_fee'] === 'yes' ? true : false;
		}

		return $price_info;
	}

	private function prepare_option_field_price_props($args){
		$price_props = array();
		$price = '';
		$price_type = '';

		$type    = isset($args['field_type']) ? $args['field_type'] : '';
		$name    = isset($args['name']) ? $args['name'] : '';
		$value   = isset($args['value']) ? $args['value'] : false;
		$options = isset($args['options']) ? $args['options'] : false;

		if(!is_array($options) || empty($options)){
			return $price_props;
		}

		if($type === 'select' || $type === 'radio'){
			$selected_option = isset($options[$value]) ? $options[$value] : false;

			if(is_array($selected_option)){
				$price      = isset($selected_option['price']) ? $selected_option['price'] : false;
				$price_type = isset($selected_option['price_type']) ? $selected_option['price_type'] : false;
				$price_type = $price_type ? $price_type : 'normal';
			}
		}else if($type === 'multiselect' || $type === 'checkboxgroup'){
			if(is_array($value)){
				foreach($value as $ovalue){
					$selected_option = isset($options[$ovalue]) ? $options[$ovalue] : false;

					if(is_array($selected_option)){
						$oprice      = isset($selected_option['price']) ? $selected_option['price'] : false;
						$oprice_type = isset($selected_option['price_type']) ? $selected_option['price_type'] : false;

						if(is_numeric($oprice)){
							$oprice_type = $oprice_type ? $oprice_type : 'normal';

							if(!empty($price)){
								$price .= ',';
							}

							if(!empty($price_type)){
								$price_type .= ',';
							}

							$price      .= $oprice;
							$price_type .= $oprice_type;
						}
					}
				}
			}
		}

		if(!empty($price) && !empty($price_type)){
			$price_props['price']      = $price;
			$price_props['price_type'] = $price_type;
		}

		return $price_props;
	}

	private function add_cart_flat_fees($cart_fees){
		global $woocommerce;

		if(is_array($cart_fees)){
			$ff_list = array();

			foreach($cart_fees as $key => $item_fee_data){
				if(is_array($item_fee_data)){
					$product = isset($item_fee_data['product']) ? $item_fee_data['product'] : false;
					$cart_item_fees = isset($item_fee_data['fees']) ? $item_fee_data['fees'] : false;

					if(is_array($cart_item_fees)){
						foreach($cart_item_fees as $ff_name => $fee_data){
							if(is_array($fee_data)){
								$ff_price = isset($fee_data['price']) ? $fee_data['price'] : '';

								if(is_numeric($ff_price) && $ff_price != 0){
									$ff_price = $this->get_flat_fee_excluding_tax($product, $ff_price);

									if(isset($ff_list[$ff_name])){
										$ff_price += $ff_list[$ff_name];
									}

									$ff_list[$ff_name] = $ff_price;
								}
							}
						}
					}
				}
			}

			if(!empty($ff_list)){
				$taxable = wc_tax_enabled() ? true : false;

				foreach($ff_list as $ff_name => $ff_price){
					if(is_numeric($ff_price) && $ff_price != 0){
						$woocommerce->cart->add_fee($ff_name, $ff_price, $taxable);
						$this->flat_fee_names[] = $ff_name;
					}
				}
			}
		}
	}

	private function get_flat_fee_excluding_tax($product, $price){
		$args = array(
			'qty'   => 1,
			'price' => abs($price),
		);

		$negative = $price < 0;

		if(wc_prices_include_tax()){
			$price = wc_get_price_excluding_tax($product, $args);
			$price = $negative ? $price * -1 : $price;
		}

		return $price;
	}
	
	/********************************************
	********* Cart Request Handler - END ********
	********************************************/


	/********************************************
	********* AJAX Request Handler - START ******
	********************************************/
	/*
	 * E000: Success, valid request data, with extra price.
	 * E001: Success, valid request data, without extra price.
	 * E002: Success, invalid request data.
	 * E101: Error, Unexpected.
	 */
	public function calculate_extra_price_ajax_handler(){
		$response = array(
			'code' => 'E002',
			'message' => ''
		);

		$request_data = $this->prepare_extra_price_request_data_ajax();
		
		if($request_data) {
			try{
				$response = $this->calculate_extra_price($request_data);
				$response = $this->prepare_extra_price_response_data($response, 'product');
			} catch (Exception $e) {
				$response = array(
					'code' => 'E101',
					'message' => $e->getMessage()
				);
			}
		}

		wp_send_json($response);
	}

	private function prepare_extra_price_request_data_ajax(){
		$data = false;
		$data_json = isset($_POST['price_info']) ? stripslashes($_POST['price_info']) : '';

		if($data_json) {
			$data = json_decode($data_json, true);
		}
		return $data;
	}
	/************************************
	********* AJAX Handler - END ********
	*************************************/


	/***********************************************
	********* Extra Price Calculation - START ******
	***********************************************/
	private function calculate_extra_price($args, $context='product'){
		if(!$this->is_valid_request($args)){
			return array('resp_code' => 'E002');
		}

		$product         = false;
		$product_id      = $args['product_id'];
		$price_info_list = isset($args['price_info']) ? $args['price_info'] : false;

		if(isset($args['product']) && $args['product'] instanceof WC_Product){
			$product = $args['product'];
		}else{
			//$product = $product_id ? wc_get_product( $product_id ) : false;
			$product = $this->get_product($args);
		}

		if(!is_array($price_info_list) || empty($price_info_list)){
			return array(
				'resp_code' => 'E001',
				'product'   => $product,
			);
		}

		$excl_base_price = apply_filters('thwepo_extra_cost_exclude_base_price', false, $product_id);
		$product_price = $this->get_product_price($args);

		$product_info = array();
		$product_info['id']    = $product_id;
		$product_info['price'] = $product_price;

		$extra_price = 0;
		$final_price = 0;
		$flat_fees   = array();

		foreach($price_info_list as $fname => $price_info){
			$price_type  = isset($price_info['price_type']) ? $price_info['price_type'] : '';
			$is_flat_fee = isset($price_info['is_flat_fee']) ? $price_info['is_flat_fee'] : false;

			$excl_base_price = $this->is_exclude_base_price($excl_base_price, $product_id, $fname, $price_type);
			$fprice = $this->calculate_extra_price_single($price_info, $product_info);

			if($is_flat_fee){
				$ff_name = $this->get_flatfee_name($product, $price_info);

				$flat_fee = array();
				$flat_fee['name']  = $ff_name;
				$flat_fee['price'] = $fprice;

				$flat_fees[$ff_name] = $flat_fee;
			}else{
				$extra_price += $fprice;
			}
		}

		$flat_fees = is_array($flat_fees) && !empty($flat_fees) ? $flat_fees : false;

		$price_data = array();
		$price_data['resp_code'] 	   = 'E000';
		$price_data['product']         = $product;
		$price_data['product_price']   = $product_price;
		$price_data['extra_price']     = $extra_price;
		$price_data['excl_base_price'] = $excl_base_price;
		$price_data['flat_fees']       = $flat_fees;

		return $price_data;
	}

	private function calculate_extra_price_single($price_info, $product_info){
		$fprice = 0;

		if(is_array($price_info)){
			$multiple    = isset($price_info['multiple']) ? $price_info['multiple'] : 0;
			$price_type  = isset($price_info['price_type']) ? $price_info['price_type'] : '';
			$price 		 = isset($price_info['price']) ? $price_info['price'] : 0;
			
			if($multiple == 1){
				$price_arr = explode(",", $price);
				$price_type_arr = explode(",", $price_type);
				
				foreach($price_arr as $index => $oprice){
					$oprice_type  = isset($price_type_arr[$index]) ? $price_type_arr[$index] : 'normal';
					$fprice 	 += $this->calculate_extra_cost($price_info, $product_info, $oprice_type, $oprice, $index);
				}
			}else{
				$fprice = $this->calculate_extra_cost($price_info, $product_info, $price_type, $price);
			}
		}

		return $fprice;
	}

	private function prepare_extra_price_response_data($args=array(), $context=''){
		$resp_msg   = '';
		$price_data = array();

		$resp_code = isset($args['resp_code']) ? $args['resp_code'] : '';
		$product   = isset($args['product']) ? $args['product'] : '';
		
		if( $product && ($resp_code === 'E000' || $resp_code === 'E001') ){
			$price_extra = 0;
			$price_final = 0;
			$flat_fees   = 0;
			$display_price_extra = '';
			$display_price_final = '';

			$product_id = $product->get_id();
			$price_original = isset($args['product_price']) ? $args['product_price'] : $product->get_price('');
			$display_price_original = $product->get_price_html();

			if($resp_code === 'E000'){
				$excl_base_price = isset($args['excl_base_price']) ? $args['excl_base_price'] : false;
				$price_extra     = isset($args['extra_price']) ? $args['extra_price'] : 0;
				$flat_fees       = isset($args['flat_fees']) ? $args['flat_fees'] : false;
			}

			if($price_extra === 0){
				$price_final = $price_original;
				$display_price_final = $display_price_original;

			}else{
				$price_final = $excl_base_price && $price_extra ? $price_extra : $price_original + $price_extra;

				$display_price_extra = self::get_price_to_display($product, $price_extra, array('context' => $context));
				$display_price_final = self::get_price_to_display($product, $price_final, array('context' => $context));
			}

			$display_price_final = apply_filters('thwepo_product_price_html', $display_price_final, $product_id);

			$price_data['price_original'] = $price_original;
			$price_data['price_extra']    = $price_extra;
			$price_data['price_final']    = $price_final;
			$price_data['flat_fees']      = $flat_fees;
			$price_data['display_price_original'] = $display_price_original;
			$price_data['display_price_extra']    = $display_price_extra;
			$price_data['display_price_final']    = $display_price_final;

		}else if($resp_code === 'E002'){
			$resp_msg = 'Invalid request';

		}else{
			$resp_code === 'E002';
		}

		$response = array();
		$response['code']       = $resp_code;
		$response['message']    = $resp_msg;
		$response['price_data'] = $price_data;

		return $response;
	}

	private function calculate_extra_cost($price_info, $product_info, $price_type, $price, $index=false){
		$fprice = 0;
		$name  = isset($price_info['name']) ? $price_info['name'] : '';
		$value = isset($price_info['value']) ? $price_info['value'] : false;
		$product_price = is_array($product_info) && isset($product_info['price']) ? $product_info['price'] : false;

		$price = apply_filters('thwepo_product_field_price', $price, $price_type, $name, $price_info, $index);

		if($price_type === 'percentage'){
			if(is_numeric($price) && is_numeric($product_price)){
				$fprice = ($price/100)*$product_price;
			}
		}else if($price_type === 'dynamic' || $price_type === 'dynamic-excl-base-price' || $price_type === 'char-count'){
			$price_unit = isset($price_info['price_unit']) ? $price_info['price_unit'] : false;
			$quantity   = isset($price_info['quantity']) ? $price_info['quantity'] : false;

			if($price_type === 'char-count' && !empty($value)){
				$quantity = strlen($value);
			}

			$quantity = apply_filters('thwepo_extra_cost_quantity_'.$name, $quantity, $value); //Deprecated
			$quantity = apply_filters('thwepo_extra_cost_quantity', $quantity, $name, $value);
			$value    = $quantity && is_numeric($quantity) ? $quantity : $value;
			
			if(is_numeric($price) && is_numeric($value) && is_numeric($price_unit) && $price_unit > 0){
				$price_min_unit = isset($price_info['price_min_unit']) && is_numeric($price_info['price_min_unit']) ? $price_info['price_min_unit'] : 0;
				$value = $value && ($value > $price_min_unit) ? $value - $price_min_unit : 0;
				
				$price = apply_filters('thwepo_extra_cost_unit_price_'.$name, $price, $product_price, $price_type);
				$price = apply_filters('thwepo_extra_cost_unit_price', $price, $name, $product_price, $price_type);
				$is_unit_type_range = apply_filters('thwepo_extra_cost_unit_price_type_range_'.$name, false);

				$total_units = $value/$price_unit;
				$total_units = $is_unit_type_range ? ceil($total_units) : $total_units;
							
				$fprice = $price * $total_units;
				//$fprice = $price*($value/$price_unit);
				
				if($price_type === 'dynamic-excl-base-price' && is_numeric($product_price) && $value >= $price_unit){
					//$fprice = $fprice - $product_price;
				}
			}
		}else if($price_type === 'custom'){
			if($value && is_numeric($value)){
				$fprice = $value;
			}
		}else{
			if(is_numeric($price)){
				$fprice = $price;
			}
		}
		
		if($name){
			$fprice = apply_filters('thwepo_product_field_extra_cost_'.$name, $fprice, $product_price, $price_info); //Deprecated
			$fprice = apply_filters('thwepo_product_field_extra_cost', $fprice, $name, $price_info, $product_info);
		}

		return is_numeric($fprice) ? $fprice : 0;
	}

	private function get_flatfee_name($product, $args){
		$fname    = isset($args['name']) ? $args['name'] : '';
		$fvalue   = isset($args['value']) ? $args['value'] : '';
		$fee_name = isset($args['label']) ? $args['label'] : $fname;

		$product_id   = $product->get_id();
		$product_name = $product->get_name();

		if( !apply_filters('thwepo_use_global_cart_fee_names', true, $fname, $product_id) ){
			$fee_name = $product_name . ' - ' . $fee_name . ' (' . $fvalue .')';
		}

		$fee_name = apply_filters('thwepo_cart_fee_name', $fee_name, $fname, $fvalue, $product_id);
		return $fee_name;
	}
	/***********************************************
	********* Extra Price Calculation - END ********
	***********************************************/


	/***********************************************
	********* Helper Functions - START *************
	***********************************************/
	private function is_valid_request($request){
		if(!is_array($request)){
			return false;
		}

		$product_id = isset($request['product_id']) ? $request['product_id'] : false;
		if(!$product_id){
			return false;
		}

		$is_variable_product = isset($request['is_variable_product']) ? $request['is_variable_product'] : false;
		$variation_id = isset($request['variation_id']) ? $request['variation_id'] : false;
		
		if($is_variable_product && !$variation_id){
			return false;
		}

		return true;
	}

	private function is_exclude_base_price($exclude, $product_id, $field_name, $price_type){
		$exclude = $price_type === 'dynamic-excl-base-price' ? true : $exclude;
		$exclude = apply_filters('thwepo_extra_cost_exclude_base_price_single', $exclude, $product_id, $field_name);
		return $exclude;
	}

	private function is_price_field_type_option($type){
		if($type && ($type === 'select' || $type === 'multiselect' || $type === 'radio' || $type === 'checkboxgroup')){
			return true;
		}
		return false;
	}

	private function is_price_field_type_multi_option($type){
		if($type && ($type === 'multiselect' || $type === 'checkboxgroup')){
			return true;
		}
		return false;
	}

	private function get_product_price($args, $is_default = false){
		$price = false;

		if(isset($args['product_price']) && is_numeric($args['product_price'])){
			$price = $args['product_price'];

		}else{
			$product = isset($args['product']) ? $args['product'] : false;
			if(!$product instanceof WC_Product){
				$product = $this->get_product($args);
			}

			if($product){
				$price = $is_default ? $product->get_price_html() : $product->get_price('');
			}

			$price = apply_filters('thwepo_product_price', $price, $product, $is_default);
		}

		return $price;
	}

	private function get_product($args){
		$product = false;

		$product_id = isset($args['product_id']) ? $args['product_id'] : false;
		$variation_id = isset($args['variation_id']) ? $args['variation_id'] : false;
		
		if($variation_id){
			$product = new WC_Product_Variation( $variation_id );
		}else if($product_id){
			$pf = new WC_Product_Factory();  
			$product = $pf->get_product($product_id);
		}

		return $product;
	}

	public static function display_price($price, $field, $args = array(), $plain = false){
		global $product;

		$display_price = self::get_price_to_display($product, $price);
		return apply_filters('thwepo_extra_option_display_price', $display_price, $price, $field);
	}

	private static function get_price_to_display($product, $original_price, $args=array()){
		$price = $original_price;

		if(!$product instanceof WC_Product){
			return $price;
		}
		//$product->is_taxable()

		$defaults = array(
		    'show_suffix' => true,
		    'plain' => false,
		    'context' => '',
		);
		$args = wp_parse_args( $args, $defaults );

		$is_shop  = $args['context'] === 'product' ? true : is_product();
		$negative = $price < 0;

		//$price = wc_get_price_to_display( $product, array('price' => abs($price)));
		if($is_shop){
			$price = self::get_price_to_display_shop( $product, array('price' => abs($price)));
		}else{
			$price = self::get_price_to_display_cart( $product, array('price' => abs($price)));
		}

		$price = $negative ? $price * -1 : $price;

		if($args['plain']){
			$display_price = $price;
		}else{
			$display_price = wc_price($price);

			if($args['show_suffix']){
				$display_price .= self::get_price_suffix($product, $original_price);
			}
		}

		return $display_price;
	}

	/*private function get_price_to_display($product, $price){
		if(is_product()){
			$price = $this->get_price_to_display_shop( $product, array('price' => abs($price)));
		}else{
			$price = $this->get_price_to_display_cart( $product, array('price' => abs($price)));
		}
		
		return $price;
	}*/

	private static function get_price_to_display_shop( $product, $args = array() ) {
		$price = wc_get_price_to_display( $product, $args);
		return $price;
	}

	private static function get_price_to_display_cart( $product, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'qty'   => 1,
				'price' => $product->get_price(),
			)
		);

		$price = $args['price'];
		$qty   = $args['qty'];

		return 'incl' === get_option( 'woocommerce_tax_display_cart' ) ?
			wc_get_price_including_tax(
				$product,
				array(
					'qty'   => $qty,
					'price' => $price,
				)
			) :
			wc_get_price_excluding_tax(
				$product,
				array(
					'qty'   => $qty,
					'price' => $price,
				)
			);
	}

	private function get_formatted_price($price, $args=array()){
		extract( apply_filters( 'wc_price_args', wp_parse_args( $args, array(
			'currency'           => '',
			'decimal_separator'  => wc_get_price_decimal_separator(),
			'thousand_separator' => wc_get_price_thousand_separator(),
			'decimals'           => wc_get_price_decimals(),
			'price_format'       => get_woocommerce_price_format(),
		) ) ) );

		$price = sprintf($price_format, get_woocommerce_currency_symbol($currency), $price);
		return $price;
	}

	private static function get_price_suffix($product, $price) {
		$price_suffix = '';

		if($product && apply_filters('thwepo_show_product_price_suffix', true, $product)){
			$price_suffix = $product->get_price_suffix($price);
		}

		return $price_suffix;
	}
	/***********************************************
	********* Helper Functions - END ***************
	***********************************************/


	/****************************************
	********* PRICE DISPLAY - START *********
	*****************************************/
	//Display Price Table
	public function render_price_table(){
		$settings = THWEPO_Utils::get_advanced_settings();

		$show = THWEPO_Utils::get_setting_value($settings, 'show_price_table');
		$show = apply_filters('thwepo_show_price_table', $show);

		if($show){
			global $product;
			$price = $product->get_price_html();
			$price_extra = $this->get_formatted_price(0);

			$label_extra = THWEPO_Utils::get_setting_value($settings, 'addon_price_label');
			$label_product = THWEPO_Utils::get_setting_value($settings, 'product_price_label');
			$label_total = THWEPO_Utils::get_setting_value($settings, 'total_price_label');
			
			?>
			<table class="thwepo-price-table" style="display: none">
				<tbody>
					<tr class="extra-price">
						<td class="label"><?php echo stripslashes(THWEPO_i18n::__t($label_extra)) ?></td>
						<td class="value"><?php echo $price_extra; ?></td>
					</tr>
					<tr class="product-price">
						<td class="label"><?php echo stripslashes(THWEPO_i18n::__t($label_product)) ?></td>
						<td class="value"><?php echo $price; ?></td>
					</tr>
					<tr class="total-price">
						<td class="label"><?php echo stripslashes(THWEPO_i18n::__t($label_total)) ?></td>
						<td class="value" data-price="<?php echo $product->get_price(); ?>"><?php echo $price; ?></td>
					</tr>
				</tbody>
			</table>
			<?php
		}
	}

	//Display Item Meta Price
	public function get_display_price_item_meta($args, $product_info, $plain = false){
		$args = wp_parse_args(
			$args,
			array(
				'name'           => '',
				'price_flat_fee' => false,
			)
		);

		$price_html  = '';
		$name        = $args['name'];
		$is_flat_fee = $args['price_flat_fee'];

		if(!$is_flat_fee){
			$price_info = $this->prepare_extra_price_request_data_cart_single($args);
			$price = $this->calculate_extra_price_single($price_info, $product_info);
			//$price = $this->calculate_extra_cost($args, $product_info, $args['price_type'], $args['price']);

			if(is_numeric($price) && $price != 0){
				$product_id = isset($product_info['id']) ? $product_info['id'] : false;
				$product    = wc_get_product($product_id);

				$price_html = self::get_price_to_display($product, $price, array('show_suffix' => false));

				$price_prefix = apply_filters('thwepo_item_meta_price_prefix', ' (', $name, $price, $args);
				$price_suffix = apply_filters('thwepo_item_meta_price_suffix', ')', $name, $price, $args);
				
				$price_html = $price_prefix.$price_html.$price_suffix;
			}
		}

		return apply_filters('thwepo_item_meta_display_price', $price_html, $name, $args);
	}
	/****************************************
	********* PRICE DISPLAY - END ***********
	*****************************************/


	/**************************************************
	***** DYNAMIC PRICING PLUGIN SUPPORT - START ******
	**************************************************/
	private function add_dynamic_pricing_plugin_support(){
		if(THWEPO_Utils::is_woo_dynamic_pricing_plugin_active()){
			add_action('wc_memberships_discounts_disable_price_adjustments', array($this, 'memberships_discounts_disable_price_adjustments'));
			add_filter('wc_dynamic_pricing_apply_cart_item_adjustment', array($this, 'dynamic_pricing_apply_cart_item_adjustment'), 10, 4);
			if(!apply_filters('thwepo_add_wepo_price_after_discount', false)){
				add_filter('woocommerce_dynamic_pricing_get_price_to_discount', array($this, 'dynamic_pricing_get_price_to_discount'), 10, 3);
			}
			//add_filter('wc_dynamic_pricing_get_use_sale_price', array($this, 'dynamic_pricing_get_use_sale_price'));
		}
	}

	public function memberships_discounts_disable_price_adjustments(){
       remove_filter('wc_dynamic_pricing_get_use_sale_price', array($this, 'dynamic_pricing_get_use_sale_price'), 10);
   	}

   	public function dynamic_pricing_apply_cart_item_adjustment($adjusted_price, $cart_item_key, $original_price, $module){
		if($cart_item_key && isset(WC()->cart->cart_contents[ $cart_item_key])){
			$cart_item = WC()->cart->cart_contents[$cart_item_key];
			$new_price = $this->calculate_cart_item_extra_costs($cart_item);

			if($new_price){
				add_filter('wc_dynamic_pricing_get_use_sale_price', array($this, 'dynamic_pricing_get_use_sale_price'), 10, 2);

				if(apply_filters('thwepo_add_wepo_price_after_discount', false)){
					$adjusted_price = $adjusted_price + $new_price;
				}
			}
		}

		return $adjusted_price;
	}

	public function dynamic_pricing_get_price_to_discount($result, $value, $key){
		$new_price = $this->calculate_cart_item_extra_costs($value);

		if($new_price){
			$result = $new_price;
		}

		return $result;
	}

	public function dynamic_pricing_get_use_sale_price($value, $product){
		$value = apply_filters('thwepo_dynamic_pricing_display_price_excluding_extra_cost', false);
		return $value;
	}
	/**************************************************
	***** DYNAMIC PRICING PLUGIN SUPPORT - END ********
	**************************************************/

}

endif;