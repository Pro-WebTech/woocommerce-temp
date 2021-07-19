<?php
/**
 * The admin general settings page functionality of the plugin.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/admin
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEPO_Admin_Settings_General')):

class THWEPO_Admin_Settings_General extends THWEPO_Admin_Settings {
	protected static $_instance = null;

	private $section_form = null;
	private $field_form = null;
		
	private $field_props = array();

	public function __construct() {
		parent::__construct('general_settings', '');

		$this->section_form = new THWEPO_Admin_Form_Section();
		$this->field_form = new THWEPO_Admin_Form_Field();
		
		add_filter('thwepo_load_user_roles', array('THWEPO_Admin_Utils', 'load_user_roles'));
		
		$this->field_props = $this->field_form->get_field_form_props();
	}
	
	public static function instance() {
		if(is_null(self::$_instance)){
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	public function get_field_form_props_display(){
		return array(
			'name'  => array('name'=>'name', 'type'=>'text'),
			'type'  => array('name'=>'type', 'type'=>'select'),
			'title' => array('name'=>'title', 'type'=>'text', 'len'=>40),
			'placeholder' => array('name'=>'placeholder', 'type'=>'text', 'len'=>30),
			'validate' => array('name'=>'validate', 'type'=>'text'),
			'required' => array('name'=>'required', 'type'=>'checkbox', 'status'=>1),
			'enabled'  => array('name'=>'enabled', 'type'=>'checkbox', 'status'=>1),
		);
	}
	
	public function render_page(){
		$this->render_tabs();
		$this->render_sections();
		$this->render_content();
	}
	
	public function reset_to_default() {
		delete_option(THWEPO_Utils::OPTION_KEY_CUSTOM_SECTIONS);
		delete_option(THWEPO_Utils::OPTION_KEY_SECTION_HOOK_MAP);
		delete_option(THWEPO_Utils::OPTION_KEY_NAME_TITLE_MAP);
		
		return $this->print_notices('Product fields successfully reset', 'updated', true);
	}
	
	/*------------------------------------*
	*----- SECTION FUNCTIONS - START ----*
	*------------------------------------*/
	/* Override */
	public function render_sections() {
		$result = false;
		if(isset($_POST['reset_fields']))
			$result = $this->reset_to_default();

		$s_action = isset($_POST['s_action']) ? $_POST['s_action'] : false;
			
		if($s_action == 'new' || $s_action == 'copy'){
			$result = $this->create_section();
		}else if($s_action == 'edit'){
			$result = $this->edit_section();
		}else if($s_action == 'remove'){
			$result = $this->remove_section();
		}
			
		$sections = array();
		$sections = THWEPO_Admin_Utils::get_sections();
		if(empty($sections)){
			return;
		}
		
		$this->sort_sections($sections);
		
		$array_keys = array_keys( $sections );
		$current_section = $this->get_current_section();
				
		echo '<ul class="thpladmin-sections">';
		$i=0; 
		foreach( $sections as $name => $section ){
			$url = $this->get_admin_url($this->page_id, sanitize_title($name));
			$props_json = htmlspecialchars(THWEPO_Utils_Section::get_property_json($section));
			$rules_json = htmlspecialchars($section->get_property('conditional_rules_json'));
			$rules_json_ajax = htmlspecialchars($section->get_property('conditional_rules_ajax_json'));
			
			echo '<li><a href="'.$url.'" class="'.($current_section == $name ? 'current' : '').'">'.THWEPO_i18n::__t($section->get_property('title')).'</a></li>';
			
			if(THWEPO_Utils_Section::is_custom_section($section)){
				?>
                <li>
                	<form id="section_prop_form_<?php echo $name; ?>" method="post" action="">
						<?php /*?><input type="hidden" name="f_props[<?php echo $i; ?>]" class="f_props" value='<?php echo $props_json; ?>' /><?php */?>
                        <input type="hidden" name="f_rules[<?php echo $i; ?>]" class="f_rules" value="<?php echo $rules_json; ?>" />
                        <input type="hidden" name="f_rules_ajax[<?php echo $i; ?>]" class="f_rules_ajax" value="<?php echo $rules_json_ajax; ?>" />
                    </form>
					<span class='s_edit_btn dashicons dashicons-edit tips' data-tip='<?php THWEPO_i18n::_et('Edit Section'); ?>' onclick="thwepoOpenEditSectionForm(<?php echo $props_json; ?>)"></span>
                </li>
				<li>
					<span class="s_copy_btn dashicons dashicons-admin-page tips" data-tip="<?php THWEPO_i18n::_et('Duplicate Section'); ?>" onclick="thwepoOpenCopySectionForm(<?php echo $props_json; ?>)"></span>
				</li>
				<li>
                    <form method="post" action="">
                        <input type="hidden" name="s_action" value="remove" />
                        <input type="hidden" name="i_name" value="<?php echo $name; ?>" />
						<span class='s_delete_btn dashicons dashicons-no tips' data-tip='<?php THWEPO_i18n::_et('Delete Section'); ?>' onclick='thwepoRemoveSection(this)'></span>
					</form>
                </li>
                <?php
			}
			echo '<li>';
			echo(end( $array_keys ) == $name ? '' : '<li style="margin-right: 5px;">|</li>');
			echo '</li>';
			
			$i++;
		}
		echo '<li><a href="javascript:void(0)" onclick="thwepoOpenNewSectionForm()" class="btn btn-tiny btn-primary ml-30">+ '. THWEPO_i18n::__t( 'Add new section' ) .'</a></li>';
		echo '</ul>';		
		
		if($result){
			echo $result;
		}
	}
	
	public function prepare_copy_section($section, $posted){
		$s_name_copy = isset($posted['s_name_copy']) ? $posted['s_name_copy'] : '';
		if($s_name_copy){
			$section_copy = THWEPO_Admin_Utils::get_section($s_name_copy);
			if(THWEPO_Utils_Section::is_valid_section($section_copy)){
				$field_set = $section_copy->get_property('fields');
				if(is_array($field_set) && !empty($field_set)){
					$section->set_property('fields', $field_set);
				}
			}
		}
		return $section;
	}
	
	public function create_section(){
		$section = THWEPO_Utils_Section::prepare_section_from_posted_data($_POST);
		$section = $this->prepare_copy_section($section, $_POST);
		$result1 = $this->update_section($section);
		$result2 = $this->update_options_name_title_map();
		
		if($result1 == true){
			return $this->print_notices('New section added successfully.', 'updated', true);
		}else{
			return $this->print_notices('New section not added due to an error.', 'error', true);
		}		
	}
	
	public function edit_section(){
		$section  = THWEPO_Utils_Section::prepare_section_from_posted_data($_POST, 'edit');
		$name 	  = $section->get_property('name');
		$position = $section->get_property('position');
		$old_position = !empty($_POST['i_position_old']) ? $_POST['i_position_old'] : '';
		
		if($old_position && $position && ($old_position != $position)){			
			$this->remove_section_from_hook($position_old, $name);
		}
		
		$result = $this->update_section($section);
		
		if($result == true){
			return $this->print_notices('Section details updated successfully.', 'updated', true);
		}else{
			return $this->print_notices('Section details not updated due to an error.', 'error', true);
		}		
	}

	public function remove_section(){
		$section_name = !empty($_POST['i_name']) ? $_POST['i_name'] : false;		
		if($section_name){	
			$result = $this->delete_section($section_name);			
										
			if ($result == true) {
				return $this->print_notices('Section removed successfully.', 'updated', true);
			} else {
				return $this->print_notices('Section not removed due to an error.', 'error', true);
			}
		}
	}
	
	public function update_section($section){
	 	if(THWEPO_Utils_Section::is_valid_section($section)){	
			$sections = THWEPO_Admin_Utils::get_sections();
			$sections = (isset($sections) && is_array($sections)) ? $sections : array();
			
			$sections[$section->name] = $section;
			$this->sort_sections($sections);
			
			$result1 = $this->save_sections($sections);
			$result2 = $this->update_section_hook_map($section);
	
			return $result1;
		}
		return false;
	}
	
	private function update_section_hook_map($section){
		$section_name  = $section->name;
		$display_order = $section->get_property('order');
		$hook_name 	   = $section->position;
				
	 	if(isset($hook_name) && isset($section_name) && !empty($hook_name) && !empty($section_name)){	
			$hook_map = THWEPO_Utils::get_section_hook_map();
			
			//Remove from hook if already hooked
			if($hook_map && is_array($hook_map)){
				foreach($hook_map as $hname => $hsections){
					if($hsections && is_array($hsections)){
						if(($key = array_search($section_name, $hsections)) !== false) {
							unset($hsections[$key]);
							$hook_map[$hname] = $hsections;
						}
					}
					
					if(empty($hsections)){
						unset($hook_map[$hname]);
					}
				}
			}
			
			if(isset($hook_map[$hook_name])){
				$hooked_sections = $hook_map[$hook_name];
				if(!in_array($section_name, $hooked_sections)){
					$hooked_sections[] = $section_name;
					$hooked_sections = $this->sort_hooked_sections($hooked_sections);
					
					$hook_map[$hook_name] = $hooked_sections;
					$this->save_section_hook_map($hook_map);
				}
			}else{
				$hooked_sections = array();
				$hooked_sections[] = $section_name;
				$hooked_sections = $this->sort_hooked_sections($hooked_sections);
				
				$hook_map[$hook_name] = $hooked_sections;
				$this->save_section_hook_map($hook_map);
			}					
		}
	}
	
	public function update_options_name_title_map(){
	 	$name_title_map = array();
	 	$sections = $this->get_sections();
		if($sections && is_array($sections)){
			foreach($sections as $section_name => $section){
				if(THWEPO_Utils_Section::is_valid_section($section)){					
					$fields = $section->get_property('fields');					
					if($fields && is_array($fields)){
						foreach($fields as $field_name => $field){
							if(THWEPO_Utils_Field::is_valid_field($field) && THWEPO_Utils_Field::is_enabled($field)){
								$name_title_map[$field_name] = $field->get_display_label();
							}
						}
					}
				}
			}
		}
	 
		$result = $this->save_name_title_map($name_title_map);
		return $result;
	 }
	
	public function delete_section($section_name){
		if($section_name){	
			$sections = THWEPO_Admin_Utils::get_sections();
			if(is_array($sections) && isset($sections[$section_name])){
				$section = $sections[$section_name];
				
				if(THWEPO_Utils_Section::is_valid_section($section)){
					$hook_name = $section->get_property('position');
					
					$this->remove_section_from_hook($hook_name, $section_name);
					unset($sections[$section_name]);
								
					$result = $this->save_sections($sections);		
					return $result;
				}
			}
		}
		return false;
	}
	
	private function remove_section_from_hook($hook_name, $section_name){
		if(isset($hook_name) && isset($section_name) && !empty($hook_name) && !empty($section_name)){	
			$hook_map = THWEPO_Utils::get_section_hook_map();
			
			if(is_array($hook_map) && isset($hook_map[$hook_name])){
				$hooked_sections = $hook_map[$hook_name];
				if(is_array($hooked_sections) && !in_array($section_name, $hooked_sections)){
					unset($hooked_sections[$section_name]);				
					$hook_map[$hook_name] = $hooked_sections;
					$this->save_section_hook_map($hook_map);
				}
			}				
		}
	}
	
	private function save_sections($sections){
		$result = update_option(THWEPO_Utils::OPTION_KEY_CUSTOM_SECTIONS, $sections);
		return $result;
	}
	
	private function save_section_hook_map($section_hook_map){
		$result = update_option(THWEPO_Utils::OPTION_KEY_SECTION_HOOK_MAP, $section_hook_map);		
		return $result;
	}
	
	private function save_name_title_map($name_title_map){
		$result = update_option(THWEPO_Utils::OPTION_KEY_NAME_TITLE_MAP, $name_title_map);		
		return $result;
	}
	
	private function sort_sections(&$sections){
		if(is_array($sections) && !empty($sections)){
			THWEPO_Admin_Utils::stable_uasort($sections, array('THWEPO_Admin_Utils', 'sort_sections_by_order'));
		}
	}
	
	private function sort_hooked_sections(&$sections){
		if(is_array($sections) && !empty($sections)){
			THWEPO_Admin_Utils::stable_uasort($sections, array('THWEPO_Admin_Utils', 'sort_sections_by_order'));
		}
	}

   /*-----------------------------------*
	*----- SECTION FUNCTIONS - END -----*
	*-----------------------------------*/
	
	private function render_fields_table_heading(){
		?>
		<th class="sort"></th>
		<th class="check-column"><input type="checkbox" style="margin:0px 4px -1px -1px;" onclick="thwepoSelectAllProductFields(this)"/></th>
		<th class="name"><?php THWEPO_i18n::_et('Name'); ?></th>
		<th class="type"><?php THWEPO_i18n::_et('Type'); ?></th>
		<th class="label"><?php THWEPO_i18n::_et('Label'); ?></th>
		<th class="placeholder"><?php THWEPO_i18n::_et('Placeholder'); ?></th>
		<th class="validate"><?php THWEPO_i18n::_et('Validations'); ?></th>
        <th class="status"><?php THWEPO_i18n::_et('Required'); ?></th>
		<th class="status"><?php THWEPO_i18n::_et('Enabled'); ?></th>
		<th class="actions align-center"><?php THWEPO_i18n::_et('Actions'); ?></th>
        <?php
	}
	
	private function render_actions_row($section){
		if(THWEPO_Utils_Section::is_valid_section($section)){
		?>
			<th colspan="5">
				<button type="button" class="btn btn-small btn-primary" onclick="thwepoOpenNewFieldForm('<?php echo $section->get_property('name'); ?>')">
					<?php THWEPO_i18n::_et('+ Add field'); ?>
				</button>
				<button type="button" class="btn btn-small" onclick="thwepoRemoveSelectedFields()"><?php  THWEPO_i18n::_et('Remove'); ?></button>
				<button type="button" class="btn btn-small" onclick="thwepoEnableSelectedFields()"><?php  THWEPO_i18n::_et('Enable'); ?></button>
				<button type="button" class="btn btn-small" onclick="thwepoDisableSelectedFields()"><?php THWEPO_i18n::_et('Disable'); ?></button>
			</th>
			<th colspan="5">
				<input type="submit" name="save_fields" class="btn btn-small btn-primary" value="<?php THWEPO_i18n::_et('Save changes') ?>" style="float:right" />
				<input type="submit" name="reset_fields" class="btn btn-small" value="<?php THWEPO_i18n::_et('Reset to default fields') ?>" style="float:right; margin-right: 5px;" 
				onclick="return confirm('Are you sure you want to reset to default fields? all your changes will be deleted.');"/>
			</th>  
    	<?php 
		}
	}

	private function truncate_str($string, $offset){
		if($string && strlen($string) > $offset){
			$string = trim(substr($string, 0, $offset)).'...';
		}
		
		return $string;
	}
	
	private function render_content(){
		$action = isset($_POST['f_action']) ? $_POST['f_action'] : false;
		$section_name = $this->get_current_section();
		$section = THWEPO_Admin_Utils::get_section($section_name);
		if(!THWEPO_Utils_Section::is_valid_section($section)){
			$section = THWEPO_Utils_Section::prepare_default_section();
		}
		
		if($action === 'new' || $action === 'copy'){
			echo $this->save_or_update_field($section, $action);	
		}else if($action === 'edit'){
			echo $this->save_or_update_field($section, $action);
		}
		
		if(isset($_POST['save_fields'])){
			echo $this->save_fields($section);
		}
			
		$section = THWEPO_Admin_Utils::get_section($section_name);
		if(!THWEPO_Utils_Section::is_valid_section($section)){
			$section = THWEPO_Utils_Section::prepare_default_section();
		}
		?>            
        <div class="wrap woocommerce"><div class="icon32 icon32-attributes" id="icon-woocommerce"><br /></div>                
			<form method="post" id="thwepo_product_fields_form" action="">
            <table id="thwepo_product_fields" class="wc_gateways widefat thpladmin_fields_table" cellspacing="0">
                <thead>
                    <tr><?php $this->render_actions_row($section); ?></tr>
                    <tr><?php $this->render_fields_table_heading(); ?></tr>						
                </thead>
                <tfoot>
                    <tr><?php $this->render_fields_table_heading(); ?></tr>
                    <tr><?php $this->render_actions_row($section); ?></tr>
                </tfoot>
                <tbody class="ui-sortable">
                <?php 
				if(THWEPO_Utils_Section::is_valid_section($section) && THWEPO_Utils_Section::has_fields($section)){
					$i=0;												
					foreach( $section->get_property('fields') as $field ) {	
						$name = $field->get_property('name');
						$type = $field->get_property('type');
						$is_enabled = $field->get_property('enabled') ? 1 : 0;
						$props_json = htmlspecialchars($this->get_property_set_json($field));
						
						$options_json = htmlspecialchars($field->get_property('options_json'));
						$rules_json = htmlspecialchars($field->get_property('conditional_rules_json'));
						$rules_json_ajax = htmlspecialchars($field->get_property('conditional_rules_ajax_json'));
						
						$disabled_actions = !$is_enabled;
				?>
						<tr class="row_<?php echo $i; echo($is_enabled === 1 ? '' : ' thpladmin-disabled') ?>">
							<td width="1%" class="sort ui-sortable-handle">
								<input type="hidden" name="f_name[<?php echo $i; ?>]" class="f_name" value="<?php echo $name; ?>" />
								<input type="hidden" name="f_order[<?php echo $i; ?>]" class="f_order" value="<?php echo $i; ?>" />
								<input type="hidden" name="f_deleted[<?php echo $i; ?>]" class="f_deleted" value="0" />
								<input type="hidden" name="f_enabled[<?php echo $i; ?>]" class="f_enabled" value="<?php echo $is_enabled; ?>" />
								
								<input type="hidden" name="f_props[<?php echo $i; ?>]" class="f_props" value='<?php echo $props_json; ?>' />
								<input type="hidden" name="f_options[<?php echo $i; ?>]" class="f_options" value="<?php echo $options_json; ?>" />
								<input type="hidden" name="f_rules[<?php echo $i; ?>]" class="f_rules" value="<?php echo $rules_json; ?>" />
								<input type="hidden" name="f_rules_ajax[<?php echo $i; ?>]" class="f_rules_ajax" value="<?php echo $rules_json_ajax; ?>" />
							</td>
							<td class="td_select"><input type="checkbox" name="select_field"/></td>
							
							<?php
							$field_props_display = $this->get_field_form_props_display();

							foreach( $field_props_display as $pname => $property ){
								//$property = $this->field_props[$pname];
								$pvalue = '';
								
								if($type === 'html' && $pname == 'title'){
									$pvalue = $field->get_property('value');
								}else{
									$pvalue = $field->get_property($pname);
									$pvalue = is_array($pvalue) ? implode(',', $pvalue) : $pvalue;
								}
								
								if($property['type'] == 'checkbox'){
									$pvalue = $pvalue ? 1 : 0;
								}
								
								if(isset($property['status']) && $property['status'] == 1){
									$statusHtml = $pvalue == 1 ? '<span class="dashicons dashicons-yes tips" data-tip="'.THWEPO_i18n::__t('Yes').'"></span>' : '-';
									?>
									<td class="td_<?php echo $pname; ?> status"><?php echo $statusHtml; ?></td>
									<?php
								}else{
									$pvalue = esc_attr($pvalue);
									$pvalue = stripslashes($pvalue);
									$tooltip = '';

									$len = isset($property['len']) ? $property['len'] : false;

									if(is_numeric($len) && $len > 0){
										$tooltip = $pvalue;
										$pvalue = $this->truncate_str($pvalue, $len);
									}

									?>
									<td class="td_<?php echo $pname; ?>">
										<label title="<?php echo $tooltip; ?>"><?php echo $pvalue; ?></label>
									</td>
									<?php
								}
							}
							?>
							
							<td class="td_actions" align="center">
								<?php if($is_enabled){ ?>
									<span class="f_edit_btn dashicons dashicons-edit tips" data-tip="<?php THWEPO_i18n::_et('Edit Field'); ?>"  
									onclick="thwepoOpenEditFieldForm(this, <?php echo $i; ?>)"></span>
								<?php }else{ ?>
									<span class="f_edit_btn dashicons dashicons-edit disabled"></span>
								<?php } ?>
	
								<span class="f_copy_btn dashicons dashicons-admin-page tips" data-tip="<?php THWEPO_i18n::_et('Duplicate Field'); ?>"  
								onclick="thwepoOpenCopyFieldForm(this, <?php echo $i; ?>)"></span>
							</td>
						</tr>						
                <?php 
						$i++;
					} 
				}else{
					echo '<tr><td colspan="10" class="empty-msg-row">'.THWEPO_i18n::__t('No custom fields found. Click on Add Field button to create new fields.').'</td></tr>';
				} 
				?>
                </tbody>
            </table> 
            </form>
            <?php
            $this->section_form->output_section_forms();
            $this->field_form->output_field_forms();
			?>
    	</div>
    	<?php
    }
	
	public function get_property_set_json($field){
		if(THWEPO_Utils_Field::is_valid_field($field)){
			$props_set = array();
			
			foreach( $this->field_props as $pname => $property ){
				$pvalue = $field->get_property($pname);
				$pvalue = is_array($pvalue) ? implode(',', $pvalue) : $pvalue;
				$pvalue = esc_attr($pvalue);
				
				if($property['type'] == 'checkbox'){
					$pvalue = $pvalue ? 1 : 0;
				}
				$props_set[$pname] = $pvalue;
			}
						
			$props_set['custom'] = THWEPO_Utils_Field::is_custom_field($field) ? 1 : 0;
			$props_set['price_field'] = $field->get_property('price_field') ? 1 : 0;
			$props_set['rules_action'] = $field->get_property('rules_action');
			$props_set['rules_action_ajax'] = $field->get_property('rules_action_ajax');
						
			return json_encode($props_set);
		}else{
			return '';
		}
	}
	
	private function save_or_update_field($section, $action) {
		try {
			$field = THWEPO_Utils_Field::prepare_field_from_posted_data($_POST, $this->field_props);

			if($action === 'edit'){
				$section = THWEPO_Utils_Section::update_field($section, $field);
			}else{
				$section = THWEPO_Utils_Section::add_field($section, $field);
			}
			
			$result1 = $this->update_section($section);
			$result2 = $this->update_options_name_title_map();
			
			if($result1 == true) {
				$this->print_notices('Your changes were saved.', 'updated');
			}else {
				$this->print_notices('Your changes were not saved due to an error (or you made none!).', 'error');
			}
		} catch (Exception $e) {
			$this->print_notices('Your changes were not saved due to an error.', 'error');
		}
	}
	
	private function save_fields($section) {
		try {
			$f_names = !empty( $_POST['f_name'] ) ? $_POST['f_name'] : array();	
			if(empty($f_names)){
				$this->print_notices('Your changes were not saved due to no fields found.', 'error');
				return;
			}
			
			$f_order   = !empty( $_POST['f_order'] ) ? $_POST['f_order'] : array();	
			$f_deleted = !empty( $_POST['f_deleted'] ) ? $_POST['f_deleted'] : array();
			$f_enabled = !empty( $_POST['f_enabled'] ) ? $_POST['f_enabled'] : array();
						
			$sname = $section->get_property('name');
			$field_set = THWEPO_Utils_Section::get_fields($section);
						
			$max = max( array_map( 'absint', array_keys( $f_names ) ) );
			for($i = 0; $i <= $max; $i++) {
				$name = $f_names[$i];
				
				if(isset($field_set[$name])){
					if(isset($f_deleted[$i]) && $f_deleted[$i] == 1){
						unset($field_set[$name]);
						continue;
					}
					
					$field = $field_set[$name];
					$field->set_property('order', isset($f_order[$i]) ? trim(stripslashes($f_order[$i])) : 0);
					$field->set_property('enabled', isset($f_enabled[$i]) ? trim(stripslashes($f_enabled[$i])) : 0);
					
					$field_set[$name] = $field;
				}
			}
			$section->set_property('fields', $field_set);
			$section = THWEPO_Utils_Section::sort_fields($section);
			
			$result = $this->update_section($section);
			
			if ($result == true) {
				$this->print_notices('Your changes were saved.', 'updated');
			} else {
				$this->print_notices('Your changes were not saved due to an error (or you made none!).', 'error');
			}
		} catch (Exception $e) {
			$this->print_notices('Your changes were not saved due to an error.', 'error');
		}
	}
	
}

endif;