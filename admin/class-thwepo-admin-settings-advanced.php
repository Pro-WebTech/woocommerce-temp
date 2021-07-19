<?php
/**
 * The admin advanced settings page functionality of the plugin.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/admin
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEPO_Admin_Settings_Advanced')):

class THWEPO_Admin_Settings_Advanced extends THWEPO_Admin_Settings{
	protected static $_instance = null;
	
	private $settings_fields = NULL;
	private $cell_props = array();
	private $cell_props_CB = array();
	private $cell_props_TA = array();
	
	public function __construct() {
		parent::__construct('advanced_settings');
		$this->init_constants();
	}
	
	public static function instance() {
		if(is_null(self::$_instance)){
			self::$_instance = new self();
		}
		return self::$_instance;
	} 
	
	public function init_constants(){
		$this->cell_props = array( 
			'label_cell_props' => 'class="label"', 
			'input_cell_props' => 'class="field"',
			'input_width' => '260px',
			'label_cell_th' => true
		);

		$this->cell_props_TA = array( 
			'label_cell_props' => 'class="label"', 
			'input_cell_props' => 'class="field"',
			'rows' => 10,
			'cols' => 100,
		);

		$this->cell_props_CB = array( 
			'label_props' => 'style="margin-right: 40px;"', 
		);

		/*$this->cell_props = array( 
			'label_cell_props' => 'class="titledesc" scope="row" style="width: 25%;"', 
			'input_cell_props' => 'class="forminp"', 
			'input_width' => '250px', 
			'label_cell_th' => true 
		);*/
		$this->cell_props_R = array( 'label_cell_width' => '13%', 'input_cell_width' => '34%', 'input_width' => '250px' );
		//$this->cell_props_CB = array( 'cell_props' => 'colspan="3"', 'render_input_cell' => true );
		/*$this->cell_props_TA = array( 
			'label_cell_props' => 'class="titledesc" scope="row" style="width: 20%; vertical-align:top"', 
			'rows' => 10, 
		);*/
		
		$this->settings_fields = $this->get_advanced_settings_fields();
	}
	
	public function get_advanced_settings_fields(){
		return array(
			'show_price_table' => array('name'=>'show_price_table', 'label'=>"Show price table", 'type'=>'checkbox', 'value'=>'yes', 'checked'=>0),
			'addon_price_label' => array(
				'name'=>'addon_price_label', 'label'=>'Extra Price Label', 'type'=>'text', 'value'=>'Extra Product Options Price'
			),
			'product_price_label' => array(
				'name'=>'product_price_label', 'label'=>'Product Price Label', 'type'=>'text', 'value'=>'Product Price'
			),
			'total_price_label' => array(
				'name'=>'total_price_label', 'label'=>'Total Price Label', 'type'=>'text', 'value'=>'Total Price'
			),
			'add-to_cart_text_settings' => array('title'=>'Modify Add to cart button text', 'type'=>'separator', 'colspan'=>'3'),
			/*'add_to_cart_text_addon' => array(
				'name'=>'add_to_cart_text_addon', 'label'=>'Products having Extra Options', 'type'=>'text', 'value'=>'Select options', 'placeholder'=>'ex: Select options'
			),*/
			'disable_loop_add_to_cart_link_override' => array(
				'name'=>'disable_loop_add_to_cart_link_override', 'label'=>'Disable overriding Add to cart link', 'type'=>'checkbox', 'value'=>'yes', 'checked'=>0
			),
			'disable_loop_add_to_cart_text_override' => array(
				'name'=>'disable_loop_add_to_cart_text_override', 'label'=>'Disable overriding Add to cart text', 'type'=>'checkbox', 'value'=>'yes', 'checked'=>0
			),
			'add_to_cart_text_addon_simple' => array(
				'name'=>'add_to_cart_text_addon_simple', 'label'=>'Products with Extra Options - Simple', 'type'=>'text', 'value'=>'Select options', 'placeholder'=>'ex: Select options'
			),
			'add_to_cart_text_addon_variable' => array(
				'name'=>'add_to_cart_text_addon_variable', 'label'=>'Products with Extra Options - Variable', 'type'=>'text', 'value'=>'Select options', 'placeholder'=>'ex: Select options'
			),
			'add_to_cart_text_simple' => array(
				'name'=>'add_to_cart_text_simple', 'label'=>'Products without Extra Options - Simple', 'type'=>'text', 'value'=>'', 'placeholder'=>'ex: Add to cart'
			),
			'add_to_cart_text_variable' => array(
				'name'=>'add_to_cart_text_variable', 'label'=>'Products without Extra Options - Variable', 'type'=>'text', 'value'=>'', 'placeholder'=>'ex: Select options'
			),
			'section_custom_validators' => array('title'=>'Custom validators', 'type'=>'separator', 'colspan'=>'3'),
			'custom_validators' => array(
				'name'=>'custom_validators', 'label'=>'Custom validators', 'type'=>'dynamic_options'
			),
			'confirm_validators' => array(
				'name'=>'confirm_validators', 'label'=>'Confirm field validators', 'type'=>'dynamic_options', 'prefix'=>'cnf'
			),
			'section_other_settings' => array('title'=>'Other Settings', 'type'=>'separator', 'colspan'=>'3'),
			'disable_select2_for_select_fields' => array(
				'name'=>'disable_select2_for_select_fields', 'label'=>'Disable "Enhanced Select(Select2)" for select fields.', 'type'=>'checkbox', 'value'=>'yes', 'checked'=>0
			),
			'allow_get_method' => array(
				'name'=>'allow_get_method', 'label'=>'Allow posting extra options as url parameters', 'type'=>'checkbox', 'value'=>'yes', 'checked'=>0
			),
		);
	}
	
	public function render_page(){
		$this->render_tabs();
		$this->render_content();
		$this->render_import_export_settings();
	}
		
	public function save_advanced_settings($settings){
		$result = update_option(THWEPO_Utils::OPTION_KEY_ADVANCED_SETTINGS, $settings);
		return $result;
	}
	
	private function reset_settings(){
		delete_option(THWEPO_Utils::OPTION_KEY_ADVANCED_SETTINGS);
		echo '<div class="updated"><p>'. THWEPO_i18n::__t('Settings successfully reset') .'</p></div>';	
	}
	
	private function save_settings(){
		$settings = array();
		
		foreach( $this->settings_fields as $name => $field ) {
			if($field['type'] === 'dynamic_options'){
				$prefix = isset($field['prefix']) ? 'i_'.$field['prefix'].'_' : 'i_';
				
				$vnames = !empty( $_POST[$prefix.'validator_name'] ) ? $_POST[$prefix.'validator_name'] : array();
				$vlabels = !empty( $_POST[$prefix.'validator_label'] ) ? $_POST[$prefix.'validator_label'] : array();
				$vpatterns = !empty( $_POST[$prefix.'validator_pattern'] ) ? $_POST[$prefix.'validator_pattern'] : array();
				$vmessages = !empty( $_POST[$prefix.'validator_message'] ) ? $_POST[$prefix.'validator_message'] : array();
				
				$validators = array();
				$max = max( array_map( 'absint', array_keys( $vnames ) ) );
				for($i = 0; $i <= $max; $i++) {
					$vname = isset($vnames[$i]) ? stripslashes(trim($vnames[$i])) : '';
					$vlabel = isset($vlabels[$i]) ? stripslashes(trim($vlabels[$i])) : '';
					$vpattern = isset($vpatterns[$i]) ? stripslashes(trim($vpatterns[$i])) : '';
					$vmessage = isset($vmessages[$i]) ? stripslashes(trim($vmessages[$i])) : '';

					if(empty($vname) && !empty($vlabel)){
						$vname = sanitize_title_with_dashes($vlabel);
					}
					
					if(!empty($vname) && !empty($vpattern)){
						$vlabel = empty($vlabel) ? $vname : $vlabel;
						
						$validator = array();
						$validator['name'] = $vname;
						$validator['label'] = $vlabel;
						$validator['pattern'] = $vpattern;
						$validator['message'] = $vmessage;
						
						$validators[$vname] = $validator;
					}
				}
				$settings[$name] = $validators;
			}else{
				$value = '';
				
				if($field['type'] === 'checkbox'){
					$value = !empty( $_POST['i_'.$name] ) ? $_POST['i_'.$name] : '';

				}else if($field['type'] === 'multiselect_grouped'){
					$value = !empty( $_POST['i_'.$name] ) ? $_POST['i_'.$name] : '';
					$value = is_array($value) ? implode(',', wc_clean(wp_unslash($value))) : wc_clean(wp_unslash($value));

				}else if($field['type'] === 'text' || $field['type'] === 'textarea'){
					$value = !empty( $_POST['i_'.$name] ) ? $_POST['i_'.$name] : '';
					$value = !empty($value) ? wc_clean( wp_unslash($value)) : '';

				}else{
					$value = !empty( $_POST['i_'.$name] ) ? $_POST['i_'.$name] : '';
					$value = !empty($value) ? wc_clean( wp_unslash($value)) : '';
				}
				
				$settings[$name] = $value;
			}
		}
				
		$result = $this->save_advanced_settings($settings);
		if ($result == true) {
			echo '<div class="updated"><p>'. THWEPO_i18n::__t('Your changes were saved.') .'</p></div>';
		} else {
			echo '<div class="error"><p>'. THWEPO_i18n::__t('Your changes were not saved due to an error (or you made none!).') .'</p></div>';
		}	
	}
	
	private function render_content(){
		if(isset($_POST['reset_settings']))
			$this->reset_settings();	
			
		if(isset($_POST['save_settings']))
			$this->save_settings();
			
		$settings = THWEPO_Utils::get_advanced_settings();
		$settings = $this->may_migrate_old_settings($settings);

		?>            
        <div style="padding-left: 30px;">               
		    <form id="advanced_settings_form" method="post" action="">
                <table class="thwepo-settings-table thpladmin-form-table">
                    <tbody>
                    <?php
                    $this->render_price_table_settings($settings);
                    $this->render_add_to_cart_btn_settings($settings);
                    $this->render_validator_settings($settings);
                    $this->render_other_settings($settings);

                    /*
					foreach( $this->settings_fields as $name => $field ) { 
						if($field['type'] === 'separator'){
							$this->render_form_elm_row_title($field);

						}else {
							if($field['type'] === 'dynamic_options'){
								$this->render_validator_settings($settings, $field);
								
							}else{
								if(is_array($settings) && isset($settings[$name])){
									if($field['type'] === 'checkbox'){
										if($field['value'] === $settings[$name]){
											$field['checked'] = 1;
										}
									}else{
										$field['value'] = $settings[$name];
									}
								}
								
								if($field['type'] === 'checkbox'){
									$this->render_form_elm_row_cb($field);
								}else if($field['type'] === 'multiselect' || $field['type'] === 'textarea'){
									$this->render_form_elm_row($field);
								}else{
									$this->render_form_elm_row($field);
								} 
							}
						}
					} */
					?>
                    </tbody>
                </table> 
                <p class="submit">
					<input type="submit" name="save_settings" class="btn btn-small btn-primary" value="Save changes">
                    <input type="submit" name="reset_settings" class="btn btn-small" value="Reset to default" 
					onclick="return confirm('Are you sure you want to reset to default settings? all your changes will be deleted.');">
            	</p>
            </form>
    	</div>       
    	<?php
	}

	private function render_price_table_settings($settings){
		$this->render_form_elm_row_title('Price Table');
		$this->render_form_elm_row_cb($this->settings_fields['show_price_table'], $settings);
		$this->render_form_elm_row($this->settings_fields['addon_price_label'], $settings);
		$this->render_form_elm_row($this->settings_fields['product_price_label'], $settings);
		$this->render_form_elm_row($this->settings_fields['total_price_label'], $settings);
	}

	private function render_add_to_cart_btn_settings($settings){
		$this->render_form_elm_row_title('Modify Add to cart button text');
		$this->render_form_elm_row_cb($this->settings_fields['disable_loop_add_to_cart_link_override'], $settings);
		$this->render_form_elm_row_cb($this->settings_fields['disable_loop_add_to_cart_text_override'], $settings);

		$this->render_form_elm_row($this->settings_fields['add_to_cart_text_addon_simple'], $settings);
		$this->render_form_elm_row($this->settings_fields['add_to_cart_text_addon_variable'], $settings);
		$this->render_form_elm_row($this->settings_fields['add_to_cart_text_simple'], $settings);
		$this->render_form_elm_row($this->settings_fields['add_to_cart_text_variable'], $settings);		
	}

	private function render_validator_settings($settings){
		$this->render_form_elm_row_title('Custom validators');
		$this->render_form_elm_row_validator($this->settings_fields['custom_validators'], $settings);
		$this->render_form_elm_row_validator($this->settings_fields['confirm_validators'], $settings);
	}

	private function render_other_settings($settings){
		$this->render_form_elm_row_title('Other Settings');
		$this->render_form_elm_row_cb($this->settings_fields['disable_select2_for_select_fields'], $settings, true);
		$this->render_form_elm_row_cb($this->settings_fields['allow_get_method'], $settings, true);
	}
	
	private function may_migrate_old_settings($settings){
		if(is_array($settings)){
			$add_cart_text_addon = isset($settings['add_to_cart_text_addon']) ? $settings['add_to_cart_text_addon'] : '';

			if(!empty($add_cart_text_addon)){
				$act_simple = isset($settings['add_to_cart_text_addon_simple']) ? $settings['add_to_cart_text_addon_simple'] : '';
				$act_variable = isset($settings['add_to_cart_text_addon_variable']) ? $settings['add_to_cart_text_addon_variable'] : '';

				if(empty($act_simple)){
					$settings['add_to_cart_text_addon_simple'] = $add_cart_text_addon;
				}

				if(empty($act_variable)){
					$settings['add_to_cart_text_addon_variable'] = $add_cart_text_addon;
				}
			}
		}
		return $settings;
	}
	/*----- Form Element Row -----*/
	public function render_form_elm_row_title($title=''){
		?>
		<tr>
			<td colspan="3" class="section-title" ><?php echo $title; ?></td>
		</tr>
		<?php
	}

	private function render_form_elm_row($field, $settings=false){
		$name = $field['name'];
		if(is_array($settings) && isset($settings[$name])){
			$field['value'] = $settings[$name];
		}

		?>
		<tr>
			<?php $this->render_form_field_element($field, $this->cell_props); ?>
		</tr>
		<?php
	}

	private function render_form_elm_row_ta($field, $settings=false){
		$name = $field['name'];
		if(is_array($settings) && isset($settings[$name])){
			$field['value'] = $settings[$name];
		}
		
		?>
		<tr valign="top">
			<?php $this->render_form_field_element($field, $this->cell_props_TA); ?>
		</tr>
		<?php
	}

	private function render_form_elm_row_cb($field, $settings=false, $merge_cells=false){
		$name = $field['name'];
		if(is_array($settings) && isset($settings[$name])){
			if($field['value'] === $settings[$name]){
				$field['checked'] = 1;
			}
		}

		if($merge_cells){
			?>
			<tr>
				<td colspan="3">
		    		<?php $this->render_form_field_element($field, $this->cell_props_CB, false); ?>
		    	</td>
		    </tr>
			<?php
		}else{
			?>
			<tr>
				<td colspan="2"></td>
				<td class="field">
		    		<?php $this->render_form_field_element($field, $this->cell_props_CB, false); ?>
		    	</td>
		    </tr>
			<?php
		}
	}

	private function render_form_elm_row_validator($field, $settings){
		$name = is_array($field) && isset($field['name']) ? $field['name'] : false;
		if($name){
			$custom_validators = is_array($settings) && isset($settings[$name]) ? $settings[$name] : array();
		
			?>
			<tr>
				<td class="label"><?php echo $field['label']; ?></td>
				<?php $this->render_form_fragment_tooltip(false); ?>
				<td class="field">
					<table border="0" cellpadding="0" cellspacing="0" class="thwepo-validations-list thpladmin-dynamic-row-table"><tbody>
						<?php
						if(is_array($custom_validators) && !empty($custom_validators)){
							foreach( $custom_validators as $vname => $validator ) {
								$this->render_validator_row($settings, $field, $validator);
							}
						}else{
							$this->render_validator_row($settings, $field, false);
						}
						?>
					</tbody></table>            	
				</td>
			</tr>
			<?php
		}
	}
	
	private function render_validator_row($settings, $field, $validator = false){
		$vname = ''; $vlabel = ''; $vpattern = ''; $vmessage = '';
		$prefix = isset($field['prefix']) ? 'i_'.$field['prefix'].'_' : 'i_';
		$prefix_index = 0;
		
		$pattern_ph = 'Validator Pattern';
		
		if(isset($field['prefix']) && $field['prefix'] === 'cnf'){
			$prefix_index = 1;
			$pattern_ph = 'Field Name';
		}
		
		if($validator && is_array($validator)){
			$vname = isset($validator['name']) ? $validator['name'] : '';
			$vlabel = isset($validator['label']) ? $validator['label'] : '';
			$vpattern = isset($validator['pattern']) ? $validator['pattern'] : '';
			$vmessage = isset($validator['message']) ? $validator['message'] : '';
		}
		
		?>
		<tr>
			<td style="width:190px;">
				<input type="hidden" name="<?php echo $prefix ?>validator_name[]" value="<?php echo $vname; ?>" />
				<input type="text" name="<?php echo $prefix ?>validator_label[]" value="<?php echo $vlabel; ?>" placeholder="Validator Label" style="width:180px;"/>
			</td>
			<td style="width:210px;">
				<input type="text" name="<?php echo $prefix ?>validator_pattern[]" value="<?php echo $vpattern; ?>" placeholder="<?php echo $pattern_ph; ?>" style="width:200px;"/>
			</td>
			<td style="width:250px;">
				<input type="text" name="<?php echo $prefix ?>validator_message[]" value="<?php echo $vmessage; ?>" placeholder="Validator Message" style="width:240px;"/>
			</td>
			<td class="action-cell">
				<a href="javascript:void(0)" onclick="thwepoAddNewValidatorRow(this, <?php echo $prefix_index; ?>)" class="dashicons dashicons-plus" title="Add new validator"></a>
			</td>
			<td class="action-cell">
				<a href="javascript:void(0)" onclick="thwepoRemoveValidatorRow(this, <?php echo $prefix_index; ?>)" class="dashicons dashicons-no-alt" title="Remove validator"></a>
			</td>
		</tr>
		<?php
	}
	
    /************************************************
	 *-------- IMPORT & EXPORT SETTINGS - START -----
	 ************************************************/
	public function prepare_plugin_settings(){
		$settings_sections = get_option(THWEPO_Utils::OPTION_KEY_CUSTOM_SECTIONS);
		$settings_hook_map = get_option(THWEPO_Utils::OPTION_KEY_SECTION_HOOK_MAP);
		$settings_name_title_map = get_option(THWEPO_Utils::OPTION_KEY_NAME_TITLE_MAP);
		$settings_advanced = get_option(THWEPO_Utils::OPTION_KEY_ADVANCED_SETTINGS);

		$plugin_settings = array(
			'OPTION_KEY_CUSTOM_SECTIONS' => $settings_sections,
			'OPTION_KEY_SECTION_HOOK_MAP' => $settings_hook_map,
			'OPTION_KEY_NAME_TITLE_MAP' => $settings_name_title_map,
			'OPTION_KEY_ADVANCED_SETTINGS' => $settings_advanced,
		);

		return base64_encode(serialize($plugin_settings));
	}
	
	public function render_import_export_settings(){
		if(isset($_POST['save_plugin_settings'])) 
			$result = $this->save_plugin_settings(); 
		
		if(isset($_POST['import_settings'])){			   
		} 
		
		$plugin_settings = $this->prepare_plugin_settings();
		if(isset($_POST['export_settings']))
			echo $this->export_settings($plugin_settings);   
		
		$imp_exp_fields = array(
			'section_import_export' => array('title'=>'Backup and Import Settings', 'type'=>'separator', 'colspan'=>'3'),
			'settings_data' => array(
				'name'=>'settings_data', 'label'=>'Plugin Settings Data', 'type'=>'textarea', 'value' => $plugin_settings,
				'sub_label'=>'You can transfer the saved settings data between different installs by copying the text inside the text box. To import data from another install, replace the data in the text box with the one from another install and click "Import Settings".',
				//'sub_label'=>'You can insert the settings data to the textarea field to import the settings from one site to another website.'
			),
		);
		?>
		<div style="padding-left: 30px;">               
		    <form id="import_export_settings_form" method="post" action="" class="clear">
                <table class="thwepo-settings-table">
                    <tbody>
                    <?php
                    $this->render_form_elm_row_title('Backup and Import Settings');
					$this->render_form_elm_row_ta($imp_exp_fields['settings_data']);
					/*
					foreach( $imp_exp_fields as $name => $field ) { 
						if($field['type'] === 'separator'){
							$this->render_form_elm_row_title($field);
						}else {
							?>
							<?php  
							if($field['type'] === 'checkbox'){
								$this->render_form_elm_row_cb($field);
							}else if($field['type'] === 'multiselect'){
								$this->render_form_elm_row($field);
							}else if($field['type'] === 'textarea'){
								$this->render_form_elm_row_ta($field);
							}else{
								$this->render_form_elm_row($field);
							}
							?>
                    		<?php 
						}
					} */
					?>
                    </tbody>
					<tfoot>
						<tr valign="top">
							<td colspan="2">&nbsp;</td>
							<td class="submit">
								<input type="submit" name="save_plugin_settings" class="btn btn-small btn-primary" value="Import Settings">
								<!--<input type="submit" name="import_settings" class="button" value="Import Settings(CSV)">-->
								<!--<input type="submit" name="export_settings" class="button" value="Export Settings(CSV)">-->
							</td>
						</tr>
					</tfoot>
                </table> 
            </form>
    	</div> 
		<?php
	}
		
	public function save_plugin_settings(){		
		if(isset($_POST['i_settings_data']) && !empty($_POST['i_settings_data'])) {
			$settings_data_encoded = $_POST['i_settings_data'];   
			$settings = unserialize(base64_decode($settings_data_encoded)); 
			
			if($settings){	
				foreach($settings as $key => $value){	
					if($key === 'OPTION_KEY_CUSTOM_SECTIONS'){
						$result = update_option(THWEPO_Utils::OPTION_KEY_CUSTOM_SECTIONS, $value);	
					}
					if($key === 'OPTION_KEY_SECTION_HOOK_MAP'){ 
						$result1 = update_option(THWEPO_Utils::OPTION_KEY_SECTION_HOOK_MAP, $value);  
					}
					if($key === 'OPTION_KEY_NAME_TITLE_MAP'){ 
						$result2 = update_option(THWEPO_Utils::OPTION_KEY_NAME_TITLE_MAP, $value); 
					}
					if($key === 'OPTION_KEY_ADVANCED_SETTINGS'){ 
						$result3 = $this->save_advanced_settings($value);  
					}						  
				}					
			}		
									
			if($result || $result1 || $result2 || $result3){
				echo '<div class="updated"><p>'. THWEPO_i18n::__t('Your Settings Updated.') .'</p></div>';
				return true; 
			}else{
				echo '<div class="error"><p>'. THWEPO_i18n::__t('Your changes were not saved due to an error (or you made none!).') .'</p></div>';
				return false;
			}	 			
		}
	}

	public function export_settings($settings){
		ob_clean();
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private", false);
		header("Content-Type: text/csv");
		header("Content-Disposition: attachment; filename=\"wcfe-checkout-field-editor-settings.csv\";" );
		echo $settings;	
        ob_flush();     
     	exit; 		
	}
	
	public function import_settings(){
	
	}
    /**********************************************
	 *-------- IMPORT & EXPORT SETTINGS - END -----
	 **********************************************/
}

endif;