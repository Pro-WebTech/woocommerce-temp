
<?php
/**
 * WPML Currency Switcher compatibility handler page
 *
 * @link       https://themehigh.com
 * @since      2.5.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes/compatibility
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPO_WPML_Currency_Switcher_Handler')):

class WEPO_WPML_Currency_Switcher_Handler{

	public function __construct() {
		add_action('woocommerce_cart_loaded_from_session', array( $this, 'handle_product_price' ), 1, 1);
		add_filter('thwepo_product_field_extra_cost', array( $this, 'wpml_currency_val' ), 10, 4);
		add_action('wcml_switch_currency', array( $this, 'wpml_currency_swictched') );
		add_filter('wcml_multi_currency_ajax_actions', array( $this, 'add_action_to_multi_currency_ajax' ) );
		add_filter('thwepo_extra_cost_unit_price', array( $this, 'change_display_price_as_per_currency' ), 10, 3);
		add_filter('thwepo_extra_cost_option_price', array( $this, 'change_option_price' ), 10, 4);
	}

	public function handle_product_price($cart_object){
		foreach($cart_object->cart_contents as $key => &$value) {
			$extra_options = isset($value['thwepo_options']) ? $value['thwepo_options'] : false;
			if($extra_options) {
				global $woocommerce;
				$is_currency_switched = $woocommerce->session->get( 'client_currency_switched');
				$wepo_currency_flag = $woocommerce->session->get( 'wepo_client_currency_switched');
				if($is_currency_switched && $wepo_currency_flag){
					$value['thwepo-original_price'] = $value['data']->get_price('');
					$woocommerce->session->set( 'wepo_client_currency_switched', false );
				}
			}
		}
		
	}
	
	public function wpml_currency_val($price, $name, $price_info, $product_info){
		if($price){
			$price = $this->convert_price_as_per_currency_used($price);
		}

		return $price;
	}

	public function wpml_currency_swictched(){
		global $woocommerce;
		$woocommerce->session->set( 'wepo_client_currency_switched', true );
	}

	public function add_action_to_multi_currency_ajax( $array ) {
		$array[] = 'thwepo_calculate_extra_cost'; // Add a AJAX action to the array
		return $array;
	}

	public function change_display_price_as_per_currency($price, $product_price, $price_type){
		if($price){
			$price = $this->convert_price_as_per_currency_used($price);
		}

		return $price;
	}

	public function change_option_price($price, $price_type, $option, $name){
		if($price){
			$price = $this->convert_price_as_per_currency_used($price);
		}

		return $price;
	}

	public function is_woocommmerce_wpml_actice(){
		$is_currency_switcher_active = is_plugin_active('woocommerce-multilingual/wpml-woocommerce.php');
		return $is_currency_switcher_active;
	}

	public function convert_price_as_per_currency_used($price){
		global $woocommerce, $woocommerce_wpml;
		$is_active = is_woocommmerce_wpml_actice();

		if($price && $is_active){
			$multi_currency = $woocommerce_wpml->get_multi_currency();
			if($multi_currency){
				$currency_settings = $woocommerce_wpml->get_setting( 'currency_options' );
				$currecy_switcher = new WCML_Multi_Currency_Prices($multi_currency, $currency_settings);

			    if($currecy_switcher){
			    	$price = $currecy_switcher->raw_price_filter($price);
			    }
			}
		}

		return $price;
	}
}

endif;