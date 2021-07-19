<?php
/**
 * The application scope class to retreive data.
 *
 * @link       https://themehigh.com
 * @since      2.3.9
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/admin
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEPO_Data')):

class THWEPO_Data {
	protected static $_instance = null;
	private $products = array();
	private $categories = array();
	
	public function __construct() {
		
	}
	
	public static function instance() {
		if(is_null(self::$_instance)){
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function load_products_ajax(){
		$productsList = array();
		$value = isset($_POST['value']) ? stripslashes($_POST['value']) : '';
		$count = 0;

		$limit = apply_filters('thwepo_load_products_per_page', 100);

		if(!empty($value)){
			$value_arr = $value ? explode(',', $value) : false;

			$args = array(
			    'include' => $value_arr,
				'orderby' => 'name', 
				'order' => 'ASC', 
				'return' => 'ids',
				'limit' => $limit,
			);
			$products = $this->get_products($args);

			if(is_array($products) && !empty($products)){
				foreach($products as $pid){
					$productsList[] = array("id" => $pid, "text" => get_the_title($pid), "selected" => true);
				}
			}

			$count = count($products);

		}else{
			$term = isset($_POST['term']) ? stripslashes($_POST['term']) : '';
			$page = isset($_POST['page']) ? stripslashes($_POST['page']) : 1;

		    $status = apply_filters('thwepo_load_products_status', 'publish');

		    $args = array(
				's' => $term,
			    'limit' => $limit,
			    'page'  => $page,
			    'status' => $status, 
				'orderby' => 'name', 
				'order' => 'ASC', 
				'return' => 'ids'
			);
			$products = $this->get_products($args);
			
			if(is_array($products) && !empty($products)){
				foreach($products as $pid){
					$productsList[] = array("id" => $pid, "text" => get_the_title($pid));
					//$productsList[] = array("id" => $product->ID, "title" => $product->post_title);
				}
			}

			$count = count($products);
		}

		$morePages = $count < $limit ? false : true;

		$results = array(
			"results" => $productsList,
			"pagination" => array( "more" => $morePages )
		);

		wp_send_json_success($results);
  		die();
	}

	public function get_products($args){
		$products = false;
		$is_wpml_active = THWEPO_Utils::is_wpml_active();

		if($is_wpml_active){
			global $sitepress;
			global $icl_adjust_id_url_filter_off;

			$orig_flag_value = $icl_adjust_id_url_filter_off;
			$icl_adjust_id_url_filter_off = true;
			$default_lang = $sitepress->get_default_language();
			$current_lang = $sitepress->get_current_language();
			$sitepress->switch_lang($default_lang);

			$products = wc_get_products($args);

			$sitepress->switch_lang($current_lang);
			$icl_adjust_id_url_filter_off = $orig_flag_value;
		}else{
			$products = wc_get_products($args);
		}
		return $products;
	}

	/*public function get_products($only_id = false){
		$skip = $this->skip_products_loading();

		if(!$skip){
			if(empty($this->products)){
				$this->products = $this->load_products($only_id);
			}	
		}	
		return $this->products;
	}*/

	/*private function load_products($only_id = false){
		$productsList = array();

		$limit = apply_filters('thwepo_load_products_per_page', -1);
	    $status = apply_filters('thwepo_load_products_status', 'publish');
	    $only_id = apply_filters('thwepo_load_products_id_only', $only_id);

		$args = array(
			'status' => $status, 
			'limit' => $limit, 
			'orderby' => 'name', 
			'order' => 'ASC', 
			'return' => 'ids'
		);
		$products = wc_get_products( $args );
		
		if(!empty($products)){
			if($only_id){
				return $products;
			}else{
				foreach($products as $pid){				
					//$productsList[] = array("id" => $product->ID, "title" => $product->post_title);
					$productsList[] = array("id" => $pid, "title" => get_the_title($pid));
				}
			}
		}
		return $productsList;
	}*/

	/*private function skip_products_loading(){
		$skip = apply_filters('thwepo_disable_product_dropdown', false);
		return $skip;
	}*/
}

endif;