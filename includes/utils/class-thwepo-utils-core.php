<?php
/**
 * The core utility functionality for the plugin.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes/utils
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEPO_Utils_Core')):

class THWEPO_Utils_Core {
	public static function log($log) {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }
}

endif;