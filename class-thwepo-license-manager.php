<?php
/**
 * @version   2.0.3
 * @link      https://themehigh.com
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEPO_License_Manager')):

class THWEPO_License_Manager {
	protected static $_instance = null;

	const API_ENDPOINT_MANAGE_LICENSE = 'wp-json/thapi/v1/manage-license';
	const API_ENDPOINT_PRODUCT_INFO = 'wp-json/thapi/v1/plugin-info';

	const STATUS_ACTIVE = 'active';
	const STATUS_EXPIRED = 'expired';
	const STATUS_ACTIVATED = 'activated';
	const STATUS_DEACTIVATED = 'deactivated';

	const OKEY_ACTION = 'action';
	const OKEY_DOMAIN = 'domain';
	const OKEY_IDENTIFIER = 'identifier';
	const OKEY_LICENSE_KEY = 'license_key';

	const RKEY_LICENSE_STATUS = 'license_status';
	const RKEY_LICENSE_PDATE = 'purchase_date';
	const RKEY_LICENSE_EDATE = 'expiry_date';
	const RKEY_ACTIVATION_STATUS = 'activation_status';
	const RKEY_LATEST_VERSION = 'latest_version';
	const RKEY_RCODE = 'response_code';
	const RKEY_RMSG  = 'response_msg';
	const RKEY_RFLAG = 'response_flag';
	const RKEY_ANOTICE = 'site_notice';
	const RKEY_ENOTICE = 'expiry_notice';
	const RKEY_UNOTICE = 'update_message';
	const RKEY_UPDATE_FLAG = 'update_flag';

	const RCODE_LICENSE_DEACTIVATED = 'R006';
	const RCODE_LICENSE_NOT_ACTIVATED = 'R005';
	const RCODE_REQUEST_INVALID = 'R001';
	const RCODE_LICENSE_INVALID = 'R002';
	const RCODE_LICENSE_ACTIVATED = 'R003';

	public $okey_ldata = '';
	public $okey_lnotification = '';

	public $file             = '';
	public $software_title   = '';
	public $software_version = '';
	public $software_type    = '';
	public $api_url          = '';
	public $text_domain      = '';
	public $base_name        = '';
	public $sw_prefix        = '';
	public $identifier       = '';

	public $license_data	 = '';
	public $domain			 = '';
	public $resp_success_msgs = '';
	public $resp_warning_msgs = '';

	public function __construct($file, $api_url, $software_type='plugin', $software_title=false, $software_version=false, $text_domain=''){
		if(is_admin()){

			$this->file             = $file;
			$this->api_url          = $api_url;
			$this->software_type    = $software_type; //Plugin or Theme
			$this->software_title   = $software_title;
			$this->software_version = $software_version;
			$this->text_domain      = $text_domain;
			$this->base_name		= '';

			if( $software_type == 'plugin'){

				require_once(ABSPATH . '/wp-admin/includes/plugin.php');
				$software_data = $this->get_plugin_data($file);

				if(!$this->software_title){
					$this->software_title = isset($software_data['name']) ? $software_data['name'] : '';
				}
				if(!$this->software_version){
					$this->software_version = isset($software_data['version']) ? $software_data['version'] : '';
				}
				if(!$this->text_domain){
					$this->text_domain = isset($software_data['text_domain']) ? $software_data['text_domain'] : '';
				}

			}else{

				if( $software_type == 'theme'){
					$software_data = wp_get_theme(get_template());
				}elseif(  $software_type == 'child_theme'){
					$software_data = wp_get_theme(get_stylesheet());
				}

				if(!$this->software_title){
					$this->software_title = $software_data->get( 'Name' );
				}
				if(!$this->software_version){
					$this->software_version = $software_data->get( 'Version' );
				}
				if(!$this->text_domain){
					$this->text_domain = $software_data->get( 'TextDomain' );
				}

			}

			if($software_type == 'theme'){
				$this->base_name = get_template();
			}elseif($software_type == 'child_theme'){
				$this->base_name = get_stylesheet();
			}else{
				$this->base_name = plugin_basename($this->file);
			}

			$this->sw_prefix   = $this->prepare_software_prefix($this->software_title);
			$this->identifier  = $this->prepare_unique_identifier($this->software_title);

			$this->okey_ldata = $this->sw_prefix.'_thlmdata';
			$this->okey_lnotification = $this->sw_prefix.'_thlmnotification';
			$this->okey_dismiss_notice = $this->sw_prefix.'_dismiss_notice';
			$this->okey_dismiss_notification = $this->sw_prefix.'_dismiss_notification';

			if(is_multisite()){
				$this->domain = str_ireplace(array( 'http://', 'https://' ), '', network_site_url()); // blog domain name
			}else{
				$this->domain = str_ireplace(array( 'http://', 'https://' ), '', home_url()); // blog domain name
			}

			$this->may_copy_old_settings();// Older version compatibility added, delete later.

			add_action('admin_init', array( $this, 'admin_init'));
			add_action('init', array($this ,'license_form_listener'));

			if($this->software_type == 'plugin'){
				//Plugin info & update check
				add_filter('plugins_api', array($this, 'get_plugin_information'), 20, 3);
				add_filter('pre_set_site_transient_update_plugins', array($this, 'check_software_updates'));

				add_action('in_plugin_update_message-'.$this->base_name, array($this, 'display_plugin_update_message'), 10, 2);

				//Handle deactivation
				register_deactivation_hook( $this->file, array( $this, 'deactivation' ) );
			}else{
				// Theme update
				add_filter('pre_set_site_transient_update_themes', array($this, 'check_software_updates'));
				//add_filter('themes_api', array($this, 'get_theme_information'), 10, 3);

				//Handle theme switch
				add_action('switch_theme', array( $this, 'deactivation'));
			}

			if(apply_filters('thlm_enable_default_license_page', true, $this->identifier)){
				add_action('admin_menu', array($this ,'license_page_menu'));
			}

			add_action('admin_notices', array($this ,'display_admin_notices'));
			add_action('admin_notices', array($this ,'display_license_notification'));
			add_action( 'admin_init', array($this, 'handle_notice_dismiss') );

			// dismissable notice redirect using javascript
			add_action('admin_footer', array($this, 'custom_script_on_admin_footer'), 999);
		}
	}

	public static function instance($file, $api_url, $software_type, $software_title=false, $software_version=false, $text_domain='') {
		if(is_null( self::$_instance )) {
			self::$_instance = new self($file, $api_url, $software_type, $software_title, $software_version, $text_domain);
		}
		return self::$_instance;
	}

	public static function write_log ( $log )  {
		if (defined('WP_DEBUG') && true === WP_DEBUG) {
			if ( is_array( $log ) || is_object( $log ) ) {
				error_log( print_r( $log, true ) );
			} else {
				error_log( $log );
			}
		}
	}

	public function admin_init(){
		add_shortcode( 'licensepage_'.$this->sw_prefix, array($this, 'license_page_shortcode') );
	}

	public function license_page_shortcode($atts){
		ob_start();
		$this->output_license_page();
		return ob_get_clean();
	}

	public function license_page_menu() {
		$page_title = $this->software_title.' - License';
		$menu_title = $this->software_title.' - License';
		$menu_slug  = $this->sw_prefix.'_license';
	  	add_options_page($page_title, $menu_title, 'manage_options', $menu_slug, array($this, 'output_license_page'));
	}

	public function output_license_page(){
		$license_data = $this->get_license_data();
		$this->license_data = $license_data;
		$status = $license_data && isset($license_data[self::RKEY_ACTIVATION_STATUS]) ? $license_data[self::RKEY_ACTIVATION_STATUS] : '';

		$box_style = 'margin-top: 20px; padding: 20px 30px 10px 30px; background-color: #fff; box-shadow: 1px 1px 5px 1px rgba(0,0,0,.1); min-height: 240px;';
		$box_left  = $box_style;
		$box_right = $box_style;

		if($status === self::STATUS_ACTIVATED){
			$box_left  .= 'width: 35%; float:left; margin-right: 20px;';
			$box_right .= 'width: 35%; float:left;';
		}else{
			$box_left  .= 'width: 70%;';
		}
		?>
		<div style="<?php echo $box_left; ?>">
			<?php
			$this->output_license_form($status, $license_data);
			?>
		</div>
		<?php

		if($status === self::STATUS_ACTIVATED){
			?>
			<div style="<?php echo $box_right; ?>">
				<?php
				$this->output_license_info($status, $license_data);
				?>
			</div>
			<div style="clear: both;"></div>
			<?php
		}
	}

	private function output_license_form($status, $license_data){
		$license_key = '';

		if($license_data){
			$license_key = isset($license_data[self::OKEY_LICENSE_KEY]) ? $license_data[self::OKEY_LICENSE_KEY] : '';
		}

		$input_style = 'width: 100%; padding: 10px;';
		$license_field_attr  = 'name="'.self::OKEY_LICENSE_KEY.'"';
		$license_field_attr .= ' placeholder="License key ( e.g. LDXXRJZQ341X9TH9GFMADYDAA15PE8 )"';
		$form_title_note = '';
		$form_footer_note = '';

		if($status === self::STATUS_ACTIVATED){
			$license_field_attr .= ' value="'.$license_key.'"';
			$license_field_attr .= ' readonly';
			$btn_label  = 'Deactivate';
			$btn_action = 'deactivate';
			$form_footer_note = 'Deactivate License Key so that it can be used on another domain.';

			$this->display_expiry_notices($license_data);
		}else{
			$license_field_attr .= ' value=""';
			$btn_label  = 'Activate';
			$btn_action = 'activate';

			$license_form_title_note = 'Enter your License Key and hit activate button.';
			$license_form_title_note = apply_filters('thlm_license_form_title_note_'.$this->sw_prefix, $license_form_title_note);

			if($license_form_title_note){
				$form_title_note = '<p>'.$license_form_title_note.'</p>';
			}
		}
		$btn_action .= '-'.$this->identifier;

		$this->print_validation_notices();
		?>
		<h1>Software License Key</h1>
		<?php echo $form_title_note; ?>
		<form method='post' action='' >
			<p>
				<input type="text" <?php echo $license_field_attr ?> style="<?php echo $input_style; ?>">
				<?php echo wp_nonce_field('handle_license_form', 'nonce_license_form'); ?>
			</p>
			<p>
				<button type="submit" name="action" value="<?php echo $btn_action; ?>" class="button-primary"><?php echo $btn_label; ?></button>
			</p>
		</form>
		<?php
		echo $form_footer_note;
	}

	private function output_license_info($status, $license_data){
		?>
		<h1><?php _e('License Details', 'text-domain'); ?></h1>
		<?php
		if(($status === self::STATUS_ACTIVATED) or ($status === self::STATUS_DEACTIVATED)){
			$l_status = isset( $license_data[self::RKEY_LICENSE_STATUS] ) ? $license_data[self::RKEY_LICENSE_STATUS] : '';
			$p_date = isset( $license_data[self::RKEY_LICENSE_PDATE] ) ? $license_data[self::RKEY_LICENSE_PDATE] : '';
			$expiry = isset( $license_data[self::RKEY_LICENSE_EDATE] ) ? $license_data[self::RKEY_LICENSE_EDATE] : '';

			$l_status = $l_status === self::STATUS_ACTIVE ? '<label style="color: green;">Active<label>' : '<label style="color: red;">Expired<label>';
			$expiry = $expiry === 'never' ? ucwords($expiry) : $expiry;
			$cell_style = 'padding: 10px 0; border-bottom: 1px solid #eee;';

			?>
			<table width="100%" style="font-size: 15px;">
				<tbody>
					<tr style="border-bottom: 1px solid ">
						<td style="<?php echo $cell_style ?>" width="40%"><strong><?php _e('License status', 'text-domain'); ?></strong></td>
						<td style="<?php echo $cell_style ?>"><strong><?php echo $l_status; ?></strong></td>
					</tr>
					<tr>
						<td style="<?php echo $cell_style ?>"><strong><?php _e('Purchased on', 'text-domain'); ?></strong></td>
						<td style="<?php echo $cell_style ?>"><?php echo $p_date; ?></td>
					</tr>
					<tr>
						<td style="<?php echo $cell_style ?>"><strong><?php _e('Expiry', 'text-domain'); ?></strong></td>
						<td style="<?php echo $cell_style ?>"><?php echo $expiry; ?></td>
					</tr>
				</tbody>
			</table>
			<?php
		}
	}

	public function license_form_listener() {
		if(isset($_POST['nonce_license_form']) && $_POST['nonce_license_form']){
			if(!wp_verify_nonce($_POST['nonce_license_form'], 'handle_license_form')){
				die('You are not authorized to perform this action.');

			} else {
				$action = isset($_POST['action']) ? $_POST['action'] : '';

				if($action === 'activate-'.$this->identifier){
					$license_key = isset($_POST[self::OKEY_LICENSE_KEY]) ? $_POST[self::OKEY_LICENSE_KEY] : '';
					if($license_key){
						$this->trigger_license_request('activate', $_POST);
					}else{
						$this->handle_notices('E003');
					}
				}elseif($action === 'deactivate-'.$this->identifier){
					$this->trigger_license_request('deactivate', $_POST);
				}
			}
		}
	}

	private function trigger_license_request($action, $posted){
		$target_url   = $this->prepare_request_url($action);
		$request_data = $this->prepare_request_data_license_check($action, $posted);

		if($target_url){
			$request = wp_safe_remote_post( $target_url, array('body' => $request_data) );
		} else {
			$this->handle_notices('E001');
		}

		if(is_wp_error($request) || wp_remote_retrieve_response_code($request) != 200){
			$this->handle_notices('E002');
			$this->write_log('--- Start of error ' . $action . '');
			$this->write_log($request);
			$this->write_log('--- End of error ---');
		} else {
			$response = wp_remote_retrieve_body( $request );
			$response = json_decode($response, true);

			if(is_array($response) && !empty($response)){
				$this->remove_old_license_data($response); // remove old data compleetly

				$response[self::RKEY_LATEST_VERSION] = $this->software_version;
				$this->update_license_data($response);
				$this->handle_resp_notices($response);

				if(($response[self::RKEY_RCODE] === self::RCODE_REQUEST_INVALID) && ($action == 'deactivate')){
					$this->delete_license_data();
					$this->handle_notices('E005');
				}
			}
		}
	}

	/* Fire during View Details Popup */
	public function get_plugin_information( $res, $action, $args ){
		if($action === 'plugin_information' && $this->identifier == $args->slug){
			$request = "";
			$action = 'plugin_info';

			$target_url   = $this->prepare_request_url($action);
			$request_data = $this->prepare_request_data_updates_check($action);

			if($target_url){
				$request = wp_safe_remote_post( $target_url, array('body' => $request_data) );
			}

			if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
				$this->write_log('--- Start of error ' . $action . '');
				$this->write_log($request);
				$this->write_log('--- End of error ---');
			} else {
				$response = wp_remote_retrieve_body( $request );
				$response = json_decode( $response );
			}

			if($response){
				$description  = isset($response->sections->description) ? $response->sections->description : "";
				$installation = isset($response->sections->installation) ? $response->sections->installation : "";
				$changelog    = isset($response->sections->changelog) ? $response->sections->changelog : "";

				$author       = isset($response->author) ? $response->author : '';
				$author_url   = isset($response->author_url) ? $response->author_url : '';
				$download_url = isset($response->download_url) ? $response->download_url : '';

				$res = new stdClass();
				$res->name     = isset($response->name) ? $response->name : '';
				$res->slug     = isset($response->slug) ? $response->slug : '';
				$res->version  = isset($response->new_version) ? $response->new_version : '';
				$res->tested     = isset($response->wp_tested) ? $response->wp_tested : '';
				$res->requires   = isset($response->wp_required) ? $response->wp_required : '';
				$res->author        = '<a href="'.$author_url.'">'.$author.'</a>';
				$res->trunk         = $download_url;
				$res->download_link = $download_url;
				$res->last_updated  = isset($response->last_updated) ? $response->last_updated : '';
				$res->sections = array(
					'description'  => $description, // description tab
					'installation' => $installation, // installation tab
					'changelog'    => $changelog, // changelog tab
				);

				// in case you want the screenshots tab, use the following HTML format for its content:
				// <ol><li><a href="IMG_URL" target="_blank"><img src="IMG_URL" alt="CAPTION" /></a><p>CAPTION</p></li></ol>
				if(!empty($response->sections->screenshots)){
					$res->sections['screenshots'] = $response->sections->screenshots;
				}

				/*$res->banners = array(
					'low' => 'https://YOUR_WEBSITE/banner-772x250.jpg',
					'high' => 'https://YOUR_WEBSITE/banner-1544x500.jpg'
				);*/
			}
		}
		return $res;
	}

	/* Fire during View Details Popup on themes - TODO */
	public function get_theme_information( $res, $action, $args ){
		if($action === 'plugin_information' && $this->identifier == $args->slug){
			$request = "";
			$action = 'plugin_info';

			$target_url   = $this->prepare_request_url($action);
			$request_data = $this->prepare_request_data_updates_check($action);

			if($target_url){
				$request = wp_safe_remote_post( $target_url, array('body' => $request_data) );
			}

			if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
				$this->write_log('--- Start of error ' . $action . '');
				$this->write_log($request);
				$this->write_log('--- End of error ---');
			} else {
				$response = wp_remote_retrieve_body( $request );
				$response = json_decode( $response );
			}

			if($response){
				$description  = isset($response->sections->description) ? $response->sections->description : "";
				$installation = isset($response->sections->installation) ? $response->sections->installation : "";
				$changelog    = isset($response->sections->changelog) ? $response->sections->changelog : "";

				$author       = isset($response->author) ? $response->author : '';
				$author_url   = isset($response->author_url) ? $response->author_url : '';
				$download_url = isset($response->download_url) ? $response->download_url : '';

				$res = new stdClass();
				$res->name     = isset($response->name) ? $response->name : '';
				$res->slug     = isset($response->slug) ? $response->slug : '';
				$res->version  = isset($response->new_version) ? $response->new_version : '';
				$res->tested     = isset($response->wp_tested) ? $response->wp_tested : '';
				$res->requires   = isset($response->wp_required) ? $response->wp_required : '';
				$res->author        = '<a href="'.$author_url.'">'.$author.'</a>';
				$res->trunk         = $download_url;
				$res->download_link = $download_url;
				$res->last_updated  = isset($response->last_updated) ? $response->last_updated : '';
				$res->sections = array(
					'description'  => $description, // description tab
					'installation' => $installation, // installation tab
					'changelog'    => $changelog, // changelog tab
				);

				// in case you want the screenshots tab, use the following HTML format for its content:
				// <ol><li><a href="IMG_URL" target="_blank"><img src="IMG_URL" alt="CAPTION" /></a><p>CAPTION</p></li></ol>
				if(!empty($response->sections->screenshots)){
					$res->sections['screenshots'] = $response->sections->screenshots;
				}

				/*$res->banners = array(
					'low' => 'https://YOUR_WEBSITE/banner-772x250.jpg',
					'high' => 'https://YOUR_WEBSITE/banner-1544x500.jpg'
				);*/
			}
		}
		return $res;
	}


	/** This function will fire on plugin & theme update checking **/
	public function check_software_updates($transient){
		if(empty($transient->checked)){
			return $transient;
		}

		$action  = 'update_check';
		$request = "";

		$target_url   = $this->prepare_request_url($action);
		$request_data = $this->prepare_request_data_updates_check($action);

		if($target_url){
			$request = wp_safe_remote_post( $target_url, array('body' => $request_data) );
		}

		if(is_wp_error($request) || wp_remote_retrieve_response_code($request) != 200) {
			$this->write_log('--- Start of error ' . $action . '');
			$this->write_log($request);
			$this->write_log('--- End of error ---');
		} else {
			$response = wp_remote_retrieve_body($request);
			$response = json_decode( $response, true );
		}

		if(isset($response)) {

			if($this->software_type == 'plugin'){
				$transient = $this->update_plugin_transient($response, $transient);
			}else{
				$transient = $this->update_theme_transient($response, $transient);
			}

			$license_info = isset($response['license_info']) ? $response['license_info'] : array();

			if(isset($response[self::RKEY_RCODE]) && ($response[self::RKEY_RCODE] === self::RCODE_REQUEST_INVALID)){
				$this->delete_license_data();
				return $transient;
			}

			// Handle invalid request
			if(isset($license_info[self::RKEY_RCODE]) && ($license_info[self::RKEY_RCODE] === self::RCODE_REQUEST_INVALID)){
				$this->delete_license_data();
			// Handle invalid license
			}elseif(isset($license_info[self::RKEY_RCODE]) && ($license_info[self::RKEY_RCODE] === self::RCODE_LICENSE_INVALID)){
				$this->delete_license_data();
			// Handle not activated cases
			}elseif(isset($license_info[self::RKEY_RCODE]) && ($license_info[self::RKEY_RCODE] === self::RCODE_LICENSE_NOT_ACTIVATED)){
				$this->delete_license_data();
			}else{

				if($license_info){
					$wp_ver_req = isset($license_info['wp_required']) ? $license_info['wp_required'] : false;
					$wp_ver     = get_bloginfo('version');

					if(version_compare($wp_ver, $wp_ver_req, '<')){
						$license_info[self::RKEY_UNOTICE] = sprintf('<br>Your current Wordpress version is %s. The required Wordpress version is %s or later. ', $wp_ver, $wp_ver_req);
					}

					$this->update_license_data($license_info);
				}
			}

			$notification = isset($response['notification']) ? $response['notification'] : false;

			if($notification){
				$this->update_notification($notification);
			}
		}

        return $transient;
	}

	// Update plugin transient as per remote data
	private function update_plugin_transient($response, $transient){
		if(is_array($response)){
			$new_version = isset($response['new_version']) ? $response['new_version'] : false;

			if(version_compare($this->software_version, $new_version, '<')){
				$res = new stdClass();
				$res->slug        = $this->identifier;
				$res->plugin      = $this->base_name;
				$res->new_version = $new_version;
				$res->tested      = isset($response['wp_tested']) ? $response['wp_tested'] : '';
				$res->package     = isset($response['download_url']) ? $response['download_url'] : '';

				$transient->response[$res->plugin] = $res;
				$transient->checked[$res->plugin]  = $new_version;
			}
		}
		return $transient;
	}

	// Update theme transient as per remote data
	private function update_theme_transient($response, $transient){
		if(is_array($response)){
			$new_version = isset($response['new_version']) ? $response['new_version'] : false;

			if(version_compare($this->software_version, $new_version, '<')){
				$res = array();
				$res['theme'] = $this->base_name;
				$res['new_version'] = $new_version;
				$res['url'] = isset($response['software_url']) ? $response['software_url'] : '';
				$res['requires_php'] = isset($response['php_required']) ? $response['php_required'] : '';
				$res['requires'] = isset($response['wp_required']) ? $response['wp_required'] : '';
				$res['package'] = isset($response['download_url']) ? $response['download_url'] : '';

				$transient->response[$this->base_name] = $res;
				$transient->checked[$this->base_name]  = $new_version;
			}
		}
		return $transient;
	}

	/* Trigger when deactivating the software */
	public function deactivation() {
		$license_data = $this->get_license_data();
		if(is_array($license_data) && isset($license_data[self::OKEY_LICENSE_KEY])){
			$action = 'deactivate';
			$posted = array(
				self::OKEY_ACTION      => $action,
				self::OKEY_LICENSE_KEY => $license_data[self::OKEY_LICENSE_KEY],
				self::OKEY_IDENTIFIER  => $this->identifier,
				self::OKEY_DOMAIN      => $this->domain,
			);
			$this->trigger_license_request($action, $posted);
		}
	}

	private function prepare_request_url($action){
		if($action == 'activate' || $action == 'deactivate'){
			$api_url = $this->api_url . self::API_ENDPOINT_MANAGE_LICENSE;
		} else {
			$api_url = $this->api_url . self::API_ENDPOINT_PRODUCT_INFO;
		}
		return $api_url;
	}

	private function prepare_request_data_license_check($action, $posted){
		// Validate Posted Data & create data array to POST to API
		$license_key = isset($posted[self::OKEY_LICENSE_KEY]) ? $posted[self::OKEY_LICENSE_KEY] : '';
		$license_key = preg_replace('/\s+/', '', $license_key);

		$data = array(
			self::OKEY_ACTION      => $action,
			self::OKEY_LICENSE_KEY => $license_key,
			self::OKEY_IDENTIFIER  => $this->identifier,
			self::OKEY_DOMAIN      => $this->domain,
		);
		return $data;
	}

	private function prepare_request_data_updates_check($action){
		$data = false;
		$license_data = $this->get_license_data();

		$license_key = is_array($license_data) && isset($license_data[self::OKEY_LICENSE_KEY]) ? $license_data[self::OKEY_LICENSE_KEY] : '';
		//if(is_array($license_data) && isset($license_data[self::OKEY_LICENSE_KEY])){
			$data = array(
				self::OKEY_ACTION      => $action,
				self::OKEY_LICENSE_KEY => $license_key,
				self::OKEY_IDENTIFIER  => $this->identifier,
				self::OKEY_DOMAIN      => $this->domain,
			);
		//}

		return $data;
	}

	private function may_copy_old_settings(){
		$license_data = $this->get_license_data();
		if(!$license_data){
			$prefix = $this->prepare_data_prfix($this->software_title);
			$old_data_key = $prefix.'_thlmdata';

			$license_data_old = get_option($old_data_key);
			if($license_data_old){
				$this->save_license_data($license_data_old);
				delete_option($old_data_key);
			}
		}
	}

	private function get_license_data(){
		$license_data = false;
		if(is_multisite()){
			$license_data = get_site_option($this->okey_ldata);
		}else{
			$license_data = get_option($this->okey_ldata);
		}
		return is_array($license_data) && !empty($license_data) ? $license_data : false;
	}

	private function delete_license_data(){
		$result = false;
		if(is_multisite()){
			$result = delete_site_option($this->okey_ldata);
		}else{
			$result = delete_option($this->okey_ldata);
		}
		return $result;
	}

	private function save_license_data($license_data, $autoload=false){
		$result = false;
		if(is_multisite()){
			if($autoload){
				$result = update_site_option($this->okey_ldata, $license_data, $autoload);
			}else{
				$result = update_site_option($this->okey_ldata, $license_data);
			}
		}else{
			if($autoload){
				$result = update_option($this->okey_ldata, $license_data, $autoload);
			}else{
				$result = update_option($this->okey_ldata, $license_data);
			}
		}
		return $result;
	}

	private function update_license_data($license_data){
		$res_code = isset($license_data[self::RKEY_RCODE]) ? $license_data[self::RKEY_RCODE] : '';

		if($res_code === self::RCODE_LICENSE_DEACTIVATED){
			$this->delete_license_data();
		}else{
			$update_flag = isset($license_data[self::RKEY_UPDATE_FLAG]) ? $license_data[self::RKEY_UPDATE_FLAG] : false;
			if($update_flag === 'Y'){
				$result = $this->save_license_data($license_data, 'yes');
			}
		}
	}

	private function get_license_notification(){
		$notifications = '';
		if(is_multisite()){
			$notifications = get_site_option($this->okey_lnotification);
		}else{
			$notifications = get_option($this->okey_lnotification);
		}
		return $notifications;
	}

	private function update_notification($notification){
		if(is_multisite()){
			update_site_option($this->okey_lnotification, $notification, 'yes');
		}else{
			update_option($this->okey_lnotification, $notification, 'yes');
		}
	}

	private function delete_notification(){
		$result = false;
		if(is_multisite()){
			$result = delete_site_option($this->okey_lnotification);
		}else{
			$result = delete_option($this->okey_lnotification);
		}

		return $result;
	}

	private function save_license_data_updates($updates, $license_data=array()){
		if(empty($license_data)){
			$license_data = $this->get_license_data();
		}

		if(is_array($license_data) && is_array($updates)){
			$license_data = array_merge($license_data, $updates);
		}

		$result = $this->save_license_data($license_data, 'yes');
		return $result;
	}

	private function is_license_active(){
		$license_data = $this->get_license_data();
		$license_status = isset($license_data[self::RKEY_LICENSE_STATUS]) ? $license_data[self::RKEY_LICENSE_STATUS] : '';
		return $license_status === self::STATUS_ACTIVE ? true : false;
	}

	private function get_plugin_data($file){
		$data = get_file_data($file, [
			'name' => 'Plugin Name',
			'version' => 'Version',
			'text_domain' => 'Text Domain',
		], 'plugin');
		return $data;
	}

	private function get_license_page_url(){
		$menu_slug = $this->sw_prefix.'_license';
		$url = menu_page_url($menu_slug, false);
		$url = apply_filters('thlm_license_page_url_'.$this->sw_prefix, $url, $this->identifier);
		return $url;
	}

	private function prepare_unique_identifier($software_title){
		$identifier = '';
		if($software_title){
			$identifier = str_ireplace(array( ' ', '_', '&', '?' ), '_', strtolower($software_title));
			$identifier = str_ireplace('_', '-', $identifier);
		}
		return $identifier;
	}

	private function prepare_software_prefix($software_title){
		$prefix = '';
		if($software_title){
			$prefix = str_ireplace(array( ' ', '_', '&', '?', '-' ), '_', strtolower($software_title));
		}
		$prefix = apply_filters( 'thlm_software_prefix', $prefix, $software_title );
		return $prefix;
	}

	private function prepare_data_prfix($software_title){
		$prfix = '';
		if($software_title){
			$prfix = str_ireplace(array( ' ', '_', '&', '?' ), '_', strtolower($software_title));
			$prfix = str_ireplace('woocommerce', 'th', $prfix);
		}
		return $prfix;
	}

	/**** HANDLE NOTICES ****/
	/************************/
	private function print_validation_notices(){
		if($this->resp_warning_msgs){
			$emsg = __($this->resp_warning_msgs, $this->text_domain);
			$this->output_error_notices($emsg);
			$this->resp_warning_msgs = '';
		}

		if($this->resp_success_msgs){
			$wmsg = __($this->resp_success_msgs, $this->text_domain);
			$this->output_success_notices($wmsg);
			$this->resp_success_msgs = '';
		}
	}

	private function handle_resp_notices($response){
		if(is_array($response)){
			$rmsg  = isset($response[self::RKEY_RMSG]) ? $response[self::RKEY_RMSG] : '';
			$rflag = isset($response[self::RKEY_RFLAG]) ? $response[self::RKEY_RFLAG] : '';

			if($rflag === 'success'){
				$this->resp_success_msgs = $rmsg;
			}else{
				$this->resp_warning_msgs = $rmsg;
			}
		}
	}

	private function handle_notices($code){
		if($code === 'E001'){ // Unable to send data to API URL - Notice
			$this->resp_warning_msgs = 'Remote submission failed. Try again.';
		}else if($code === 'E002'){ // Request failed - Notice
			$this->resp_warning_msgs = 'Some error happen in remote server. Try again.';
		}else if($code === 'E003'){ // Invalid license key
			$this->resp_warning_msgs = 'Please enter a valid license key and try again.';
		}else if($code === 'E005'){ // Invalid license key
			$this->resp_warning_msgs = 'The license details are cleared successfully.';
		}
	}

	private function output_error_notices($msg){
		echo '<div style="background-color: #fbeaea; padding: 5px;" class="thlm-error-notice">'.$msg.'</div>';
	}
	private function output_warning_notices($msg){
		echo '<div style="background-color: #fbeaea; padding: 5px;" class="thlm-error-notice">'.$msg.'</div>';
	}
	private function output_success_notices($msg){
		echo '<div style="background-color: #ecf7ed; padding: 5px;" class="thlm-error-notice">'.$msg.'</div>';
	}

	public function display_admin_notices() {
		if(!apply_filters('thlm_show_admin_notice', true )){
			return;
		}

		$dismissed_notice = get_transient( $this->okey_dismiss_notice );
		if($dismissed_notice){
			return;
		}

		$ldata = $this->get_license_data();

		$is_dismissible = false;
		if(isset($ldata['license_status'])){
			$is_dismissible = ($ldata['license_status'] == 'expired') ? true : false;
		}

		$is_dismissible = apply_filters( 'thlm_allow_dismissible_admin_notice', $is_dismissible, $ldata );

		if($ldata){
			$notice = isset($ldata[self::RKEY_ANOTICE]) ? $ldata[self::RKEY_ANOTICE] : false;
			if(!$notice) {
				$status = isset($ldata[self::RKEY_ACTIVATION_STATUS]) ? $ldata[self::RKEY_ACTIVATION_STATUS] : false;

				if($status != self::STATUS_ACTIVATED){
					$notice = 'The license of <strong>%s</strong> is not activated. <a href="%s">Click here</a> to activate the license.';
				}
			}
		}else{
			$notice = 'The license of <strong>%s</strong> is not activated. <a href="%s">Click here</a> to activate the license.';
		}

		if(!empty($notice)){
			if(is_multisite()){
				$enable_notification_sub_site = apply_filters( 'thlm_enable_notifications_sub_site', '__return_true' );

				if(is_main_site()){
					$this->show_admin_notice_content($notice, 'admin_notice', $is_dismissible);
				}else{
					if($enable_notification_sub_site){
						$this->show_admin_notice_content($notice, 'admin_notice', $is_dismissible);
					}
				}
			}else{
				$this->show_admin_notice_content($notice, 'admin_notice', $is_dismissible);
			}
		}
	}

	public function display_license_notification() {
		if(!apply_filters('thlm_show_license_notification', true )){
			return;
		}

		$dismissed_notice = get_transient( $this->okey_dismiss_notification );
		if($dismissed_notice){
			return;
		}

		$ldata = $this->get_license_data();
		$is_dismissible = apply_filters( 'thlm_allow_dismissible_license_notification', false, $ldata );

		// License related notifications
		$lnotification = $this->get_license_notification();

		if(!empty($lnotification)){
			if(is_multisite()){
				$enable_notification_sub_site = apply_filters( 'thlm_enable_notifications_sub_site', '__return_true' );

				if(is_main_site()){
					$this->show_admin_notice_content($lnotification, 'license_notification', $is_dismissible);
				}else{
					if($enable_notification_sub_site){
						$this->show_admin_notice_content($lnotification, 'license_notification', $is_dismissible);
					}
				}
			}else{
				$this->show_admin_notice_content($lnotification, 'license_notification', $is_dismissible);
			}
		}
	}

	private function show_admin_notice_content($notice, $type='admin_notice', $is_dismissible = false){
		if($is_dismissible){
			if($type == 'license_notification'){
				$dismiss_url = add_query_arg( array(
		                $this->sw_prefix.'_dismiss_license_notification' => true,
		            ) );
			}else{
				$dismiss_url = add_query_arg( array(
		                $this->sw_prefix.'_dismiss_admin_notice' => true,
		            ) );
			}
		}

		if($type == 'license_notification'){
			$notice = html_entity_decode($notice);
			?>
			<div class="error notice <?php
					echo $this->sw_prefix . '_admin_notice ';
                    if ( $is_dismissible ) {
                        echo 'is-dismissible" data-dismiss-url="' . esc_url( $dismiss_url );
                    } ?>" >
			<?php $notice = html_entity_decode($notice);
			$translated = __($notice, $this->text_domain);
			echo wpautop($translated);
			?>
			</div>
			<?php
		}else{
			$url = $this->get_license_page_url();
			$notice = sprintf($notice, $this->software_title, $url);
			?>
			<div class="error notice <?php
					echo $this->sw_prefix . '_admin_notice ';
                    if ( $is_dismissible ) {
                        echo 'is-dismissible" data-dismiss-url="' . esc_url( $dismiss_url );
                    } ?>" >
				<p><?php _e($notice, $this->text_domain); ?></p>
			</div>
			<?php
		}
	}

	public function handle_notice_dismiss(){
		$dismiss_notice = filter_input( INPUT_GET, $this->sw_prefix.'_dismiss_admin_notice', FILTER_SANITIZE_STRING );
		if($dismiss_notice){
			$expiration = apply_filters( 'thlm_dismissible_notice_expiration', 1 * YEAR_IN_SECONDS );
			$expiration = absint( $expiration );
			set_transient( $this->okey_dismiss_notice, true, $expiration );
		}

		$dismiss_notification = filter_input( INPUT_GET, $this->sw_prefix.'_dismiss_license_notification', FILTER_SANITIZE_STRING );
		if($dismiss_notification){
			$notification_expiration = apply_filters( 'thlm_dismissible_notification_expiration', 1 * DAY_IN_SECONDS );
			$notification_expiration = absint( $notification_expiration );
			set_transient( $this->okey_dismiss_notification, true, $notification_expiration );
		}
	}

	public function custom_script_on_admin_footer(){
		?>
		<script>
		(function( $ ) {
		    'use strict';
			var wrapper = '.<?php echo $this->sw_prefix . '_admin_notice';?>';
		    $( function() {
		        $( wrapper ).on( 'click', '.notice-dismiss', function( event, el ) {
		            var $notice = $(this).parent('.notice.is-dismissible');
		            var dismiss_url = $notice.attr('data-dismiss-url');
		            if ( dismiss_url ) {
						window.location.replace(dismiss_url);
		            }
		        });
		    } );
		})( jQuery );
		</script>
		<?php
	}

	public function display_plugin_update_message($software_data, $response) {
		$ldata = $this->get_license_data();

		if($ldata){
			$notice = isset($ldata[self::RKEY_UNOTICE]) ? $ldata[self::RKEY_UNOTICE] : false;
			if($notice) {
				echo $notice;
			}
		}
	}

	public function display_expiry_notices($license_data) {
		if(is_array($license_data)){
			$notice = isset($license_data[self::RKEY_ENOTICE]) ? __($license_data[self::RKEY_ENOTICE], $this->text_domain) : false;
			if($notice)  {
				echo '<div class="thlm-expiry-notice"><h3 style="color: red;"><strong>'.$notice.'</strong></h3></div>';
			}
		}
	}

	private function remove_old_license_data($response){
		if((isset($response[self::RKEY_RCODE])) && ($response[self::RKEY_RCODE] === self::RCODE_LICENSE_ACTIVATED)){
			$this->delete_license_data();
			$this->delete_notification();
		}
	}
}

endif;

