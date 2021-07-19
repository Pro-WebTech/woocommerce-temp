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

if(!class_exists('THWEPO_File')):

class THWEPO_File {
	protected static $_instance = null;

	public function __construct() {
		
	}

	public static function instance() {
		if(is_null(self::$_instance)){
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function define_hooks(){
		/*
    	add_action('wp_ajax_thwepo_file_upload', array($this, 'ajax_file_upload'));
		add_action('wp_ajax_nopriv_thwepo_file_upload', array($this, 'ajax_file_upload'));

		add_action('wp_ajax_thwepo_remove_uploaded', array($this, 'ajax_remove_uploaded'));
		add_action('wp_ajax_nopriv_thwepo_remove_uploaded', array($this, 'ajax_remove_uploaded'));
		*/
	}

	public function validate_file_count( $names, $field ){
		$file_count = count( $names );
		$min_count = $field->get_property('minfile');
		$max_count = $field->get_property('maxfile');
		$passed = true;
		$title = $field->get_property('title');
		if( empty( $min_count ) && empty( $max_count ) ){
			return true;
		}

		if( ( $min_count === $max_count ) && ($min_count !== $file_count ) ){
			THWEPO_Utils::add_error('<strong>'.$title.': </strong> '. sprintf(THWEPO_i18n::__t('%d files need to be uploaded.'), $min_count ) );
			$passed = false;

		}else if( $file_count < $min_count ){
			THWEPO_Utils::add_error('<strong>'.$title.': </strong> '. sprintf(THWEPO_i18n::__t('Minimum of %d files need to be uploaded.'), $min_count ) );
			$passed = false;

		}else if( $file_count > $max_count ){
			THWEPO_Utils::add_error('<strong>'.$title.': </strong> '. sprintf(THWEPO_i18n::__t('Maximum of %d files need to be uploaded.'), $max_count ) );
			$passed = false;
		}
		return $passed;
	}

	public function validate_file($passed, $field, $file){
		if($field->get_property('required') && !$file) {
			THWEPO_Utils::add_error( sprintf(THWEPO_i18n::__t('Please select a file for %s'), $field->get_property('title')) );
			$passed = false;
		}
		$title = THWEPO_Utils_Field::get_display_label($field);
		
		if($file){
			$file_type = THWEPO_Utils::get_posted_file_type($file);
			$file_size = isset($file['size']) ? $file['size'] : false;
			
			if($file_type && $file_size){
				$name = $field->get_property('name');
				$maxsize = apply_filters('thwepo_file_upload_maxsize', $field->get_property('maxsize'), $name);
				$maxsize_bytes = is_numeric($maxsize) ? $maxsize*1048576 : false;
				$accept = apply_filters('thwepo_file_upload_accepted_file_types', $field->get_property('accept'), $name);
				$accept = $accept && !is_array($accept) ? array_map('trim', explode(",", $accept)) : $accept;
				
				if(is_array($accept) && !empty($accept) && !in_array($file_type, $accept)){
					THWEPO_Utils::add_error('<strong>'.$title.'</strong> '. sprintf(THWEPO_i18n::__t('Invalid file type.')));
					$passed = false;
					
				}else if($maxsize_bytes && is_numeric($maxsize_bytes) && $file_size >= $maxsize_bytes){
					THWEPO_Utils::add_error('<strong>'.$title.'</strong> '. sprintf(THWEPO_i18n::__t('File too large. File must be less than %s megabytes.'), $maxsize));
					$passed = false;
				}
			}else if($field->get_property('required')) {
				THWEPO_Utils::add_error('<strong>'.$title.'</strong> '. sprintf(THWEPO_i18n::__t('Please choose a file to upload')) );
				$passed = false;
			}
		}else if($field->get_property('required')) {
			THWEPO_Utils::add_error('<strong>'.$title.'</strong> '. sprintf(THWEPO_i18n::__t('Please choose a file to upload')) );
			$passed = false;
		}
		
		return $passed;
	}

	public function prepare_file_upload( $file, $name, $field ){
		$posted_value = false;

		if(!$field->get_property('required') && !THWEPO_Utils::is_valid_file($file)){
			return false;
		}

		$uploaded = $this->upload_file($file, $name, $field);

		if($uploaded && !isset($uploaded['error'])){
			$upload_info = array();
			$upload_info['name'] = $file['name'];
			$upload_info['url'] = $uploaded['url'];
			
			$posted_value = $upload_info;
			//$posted_value = $uploaded['url'] . '/' . $file['name']; 
		}else{
			$title = THWEPO_i18n::__t($field->get_property('title'));
			THWEPO_Utils::add_error('<strong>'.$title.'</strong>: '. $uploaded['error']);
			return false;
		}
		return $posted_value;
	}

	public function upload_file($file, $name, $field){
		$upload = false;
		
		if(is_array($file)){
			if(!function_exists('wp_handle_upload')){
				require_once(ABSPATH. 'wp-admin/includes/file.php');
				require_once(ABSPATH. 'wp-admin/includes/media.php');
			}
			
			add_filter('upload_dir', array('THWEPO_Utils', 'upload_dir'));
			//add_filter('upload_mimes', array('THWEPO_Utils', 'upload_mimes'));
			$upload = wp_handle_upload($file, array('test_form' => false));
			remove_filter('upload_dir', array('THWEPO_Utils', 'upload_dir'));
			//remove_filter('upload_mimes', array('THWEPO_Utils', 'upload_mimes'));
			
			/*if($upload && !isset($upload['error'])){
				echo "File is valid, and was successfully uploaded.\n";
			} else {
				echo $upload['error'];
			}*/
		}
		return $upload;
	}
}

endif;