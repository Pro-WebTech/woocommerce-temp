<?php
/**
 * The admin field forms functionalities.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/admin
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEPO_Admin_Form_Field')):

class THWEPO_Admin_Form_Field extends THWEPO_Admin_Form{
	private $field_props = array();

	public function __construct() {
		$this->init_constants();
	}

	private function init_constants(){
		$this->field_props = $this->get_field_form_props();
		//$this->field_props_display = $this->get_field_form_props_display();
	}

	private function get_field_types(){
		return array('inputtext' => 'Text', 'hidden' => 'Hidden', 'password' => 'Password', 'number' => 'Number', 'tel' => 'Telephone', 'textarea' => 'Textarea', 'select' => 'Select', 'multiselect' => 'Multiselect', 
			'radio' => 'Radio Button', 'checkbox' => 'Checkbox', 'checkboxgroup' => 'Checkbox Group', 'datepicker' => 'Date Picker', 'timepicker' => 'Time Picker', 'colorpicker' => 'Colorpicker', 
			'file' => 'File Upload', 'heading' => 'Heading', 'label' => 'Paragraph', 'html' => 'HTML');
		/*return array('inputtext' => 'Text', 'hidden' => 'Hidden', 'password' => 'Password', 'textarea' => 'Textarea', 'select' => 'Select', 'multiselect' => 'Multiselect', 
			'radio' => 'Radio', 'checkbox' => 'Checkbox', 'checkboxgroup' => 'Checkbox Group', 'datepicker' => 'Date Picker', 'timepicker' => 'Time Picker', 
			'heading' => 'Heading', 'label' => 'Label');*/
	}

	public function get_field_form_props(){
		$html_text_tags = $this->get_html_text_tags();
		$field_types = $this->get_field_types();
		
		$validators = array(
			'email' => 'Email',
			'number' => 'Number',
		);
		$custom_validators = THWEPO_Utils::get_settings('custom_validators');
		if(is_array($custom_validators)){
			foreach( $custom_validators as $vname => $validator ) {
				$validators[$vname] = $validator['label'];
			}
		}
		
		$confirm_validators = THWEPO_Utils::get_settings('confirm_validators');
		if(is_array($confirm_validators)){
			foreach( $confirm_validators as $vname => $validator ) {
				$validators[$vname] = $validator['label'];
			}
		}
		
		$price_types = array(
			'normal' => 'Fixed',
			'custom' => 'Custom',
			'percentage' => 'Percentage of Product Price',
			'dynamic' => 'Dynamic',
			'dynamic-excl-base-price' => 'Dynamic - Exclude base price ',
		);
		$price_types_non_input = array(
			'normal' => 'Fixed',
			'percentage' => 'Percentage of Product Price',
		);
		
		$title_positions = array(
			'left' => 'Left of the field',
			'above' => 'Above field',
		);
		
		$time_formats = array(
			'h:i A' => '12-hour format',
			'H:i' => '24-hour format',
		);
		
		$week_days = array(
			'sun' => 'Sunday',
			'mon' => 'Monday',
			'tue' => 'Tuesday',
			'wed' => 'Wednesday',
			'thu' => 'Thursday',
			'fri' => 'Friday',
			'sat' => 'Saturday',
		);
		
		$upload_file_types = array(
			'png'  => 'PNG',
			'jpg'  => 'JPG',
			'gif'  => 'GIF',
			'pdf'  => 'PDF',
			'docx' => 'DOCX',
		);

		$colorpicker_styles = array(
			'style1'  => 'Style1',
			'style2'  => 'Style2',
		);
		
		$hint_name = "Used to save values in database. Name must begin with a lowercase letter.";
		$hint_title = "Display name for the input field which will be shown on the product page. A link can be set by using the relevant HTML tags. For example: <a href='URL that you want to link to' target='_blank'>I agree to the terms and conditions</a>. Please use single quotes instead of double quotes";
		$hint_value = "Default value to be shown when the checkout form is loaded.";
		$hint_placeholder = "Short hint that describes the expected value/format of the input field.";
		$hint_input_class = "Define CSS class here to make the input field styled differently.";
		$hint_title_class = "Define CSS class name here to style Label.";
		
		$hint_accept = "Specify allowed file types separated by comma (e.g. png,jpg,docx,pdf).";
		
		$hint_default_date = "Specify a date in the current date format, or number of days from today (e.g. +7) or a string of values and periods ('y' for years, 'm' for months, 'w' for weeks, 'd' for days, e.g. '+1m +7d'), or leave empty for today.";
		$hint_date_format = "The format for parsed and displayed dates.";
		$hint_min_date = "The minimum selectable date. Specify a date in yyyy-mm-dd format, or number of days from today (e.g. -7) or a string of values and periods ('y' for years, 'm' for months, 'w' for weeks, 'd' for days, e.g. '-1m -7d'), or leave empty for no minimum limit.";
		$hint_max_date = "The maximum selectable date. Specify a date in yyyy-mm-dd format, or number of days from today (e.g. +7) or a string of values and periods ('y' for years, 'm' for months, 'w' for weeks, 'd' for days, e.g. '+1m +7d'), or leave empty for no maximum limit.";
		$hint_year_range = "The range of years displayed in the year drop-down: either relative to today's year ('-nn:+nn' e.g. -5:+3), relative to the currently selected year ('c-nn:c+nn' e.g. c-10:c+10), absolute ('nnnn:nnnn' e.g. 2002:2012), or combinations of these formats ('nnnn:+nn' e.g. 2002:+3). Note that this option only affects what appears in the drop-down, to restrict which dates may be selected use the minDate and/or maxDate options.";
		$hint_number_of_months = "The number of months to show at once.";
		$hint_disabled_dates = "Specify dates in yyyy-mm-dd format separated by comma.";
		
		return array(
			'name' 		  => array('type'=>'text', 'name'=>'name', 'label'=>'Name', 'required'=>1),
			'type' 		  => array('type'=>'select', 'name'=>'type', 'label'=>'Field Type', 'required'=>1, 'options'=>$field_types, 
								'onchange'=>'thwepoFieldTypeChangeListner(this)'),
			'value' 	  => array('type'=>'text', 'name'=>'value', 'label'=>'Default Value'),
			'placeholder' => array('type'=>'text', 'name'=>'placeholder', 'label'=>'Placeholder'),
			'validate' 	  => array('type'=>'multiselect', 'name'=>'validate', 'label'=>'Validations', 'placeholder'=>'Select validations', 'options'=>$validators),
			'cssclass'    => array('type'=>'text', 'name'=>'cssclass', 'label'=>'Wrapper Class', 'placeholder'=>'Seperate classes with comma'),
			'input_class'    => array('type'=>'text', 'name'=>'input_class', 'label'=>'Input Class', 'placeholder'=>'Seperate classes with comma'),
			
			'price'        => array('type'=>'text', 'name'=>'price', 'label'=>'Price', 'placeholder'=>'Price'),
			'price_unit'   => array('type'=>'text', 'name'=>'price_unit', 'label'=>'Unit', 'placeholder'=>'Unit'),
			'price_type'   => array('type'=>'select', 'name'=>'price_type', 'label'=>'Price Type', 'options'=>$price_types, 'onchange'=>'thwepoPriceTypeChangeListener(this)'),
			'price_min_unit' => array('type'=>'text', 'name'=>'price_min_unit', 'label'=>'Min. Unit', 'placeholder'=>'Min. Unit'),
			//'price_prefix' => array('type'=>'text', 'name'=>'price_prefix', 'label'=>'Price Prefix'),
			//'price_suffix' => array('type'=>'text', 'name'=>'price_suffix', 'label'=>'Price Suffix'),
			'show_price_label' => array('type'=>'checkbox', 'name'=>'show_price_label', 'label'=>"Display price label along with field input box", 'value'=>'yes', 'checked'=>1),
			'show_price_in_order' => array('type'=>'checkbox', 'name'=>'show_price_in_order', 'label'=>"Display price in Cart, Checkout and Order details", 'value'=>'yes', 'checked'=>1),
			'price_flat_fee' => array('type'=>'checkbox', 'name'=>'price_flat_fee', 'label'=>"Apply price as Flat fee", 'value'=>'yes', 'checked'=>0, 'onchange' => 'thwepoFlatFeeToggleListener(this)'),
			//'show_price_table' => array('type'=>'checkbox', 'name'=>'show_price_table', 'label'=>"Display calculated price in price table", 'value'=>'yes', 'checked'=>0),
			
			'minlength'   => array('type'=>'text', 'name'=>'minlength', 'label'=>'Min. Length', 'hint_text'=>'The minimum number of characters allowed'),
			'maxlength'   => array('type'=>'text', 'name'=>'maxlength', 'label'=>'Max. Length', 'hint_text'=>'The maximum number of characters allowed'),
			
			'checked'  => array('type'=>'checkbox', 'name'=>'checked', 'label'=>'Checked by default', 'value'=>'yes', 'checked'=>0),
			'required' => array('type'=>'checkbox', 'name'=>'required', 'label'=>'This field is Required', 'value'=>'yes', 'checked'=>0, 'status'=>1),
			'enabled'  => array('type'=>'checkbox', 'name'=>'enabled', 'label'=>'This field is Enabled', 'value'=>'yes', 'checked'=>1, 'status'=>1),

			'hide_in_cart' => array('type'=>'checkbox', 'name'=>'hide_in_cart', 'label'=>"Don't display in cart", 'value'=>'yes', 'checked'=>0),
			'hide_in_checkout' => array('type'=>'checkbox', 'name'=>'hide_in_checkout', 'label'=>"Don't display in checkout", 'value'=>'yes', 'checked'=>0),
			'hide_in_order' => array('type'=>'checkbox', 'name'=>'hide_in_order', 'label'=>"Don't display in order for customers", 'value'=>'yes', 'checked'=>0),
			'hide_in_order_admin' => array('type'=>'checkbox', 'name'=>'hide_in_order_admin', 'label'=>"Don't display in order for Admin users", 'value'=>'yes', 'checked'=>0),
			
			'title'          => array('type'=>'text', 'name'=>'title', 'label'=>'Title'),
			'title_position' => array('type'=>'select', 'name'=>'title_position', 'label'=>'Title Position', 'options'=>$title_positions, 'value'=>'left'),
			'title_type'     => array('type'=>'select', 'name'=>'title_type', 'label'=>'Title Type', 'value'=>'label', 'options'=>$html_text_tags),
			'title_color'    => array('type'=>'colorpicker', 'name'=>'title_color', 'label'=>'Title Color'),
			'title_class'    => array('type'=>'text', 'name'=>'title_class', 'label'=>'Title Class', 'placeholder'=>'Seperate classes with comma'),
			
			'subtitle'       => array('type'=>'text', 'name'=>'subtitle', 'label'=>'Description'),
			'subtitle_type'  => array('type'=>'select', 'name'=>'subtitle_type', 'label'=>'Description Type', 'value'=>'label', 'options'=>$html_text_tags),
			'subtitle_color' => array('type'=>'colorpicker', 'name'=>'subtitle_color', 'label'=>'Description Color'),
			'subtitle_class' => array('type'=>'text', 'name'=>'subtitle_class', 'label'=>'Description Class', 'placeholder'=>'Seperate classes with comma'),
			
			'maxsize' => array('type'=>'text', 'name'=>'maxsize', 'label'=>'Maxsize(in MB)'),
			'accept'  => array('type'=>'text', 'name'=>'accept', 'label'=>'Accepted File Types', 'placeholder'=>'eg: png,jpg,docx,pdf', 'hint_text'=>$hint_accept),

			'cols' => array('type'=>'text', 'name'=>'cols', 'label'=>'Cols'),
			'rows' => array('type'=>'text', 'name'=>'rows', 'label'=>'Rows'),
						
			'default_date' => array('type'=>'text','name'=>'default_date', 'label'=>'Default Date','placeholder'=>"Leave empty for today's date",'hint_text'=>$hint_default_date),
			'date_format'  => array('type'=>'text', 'name'=>'date_format', 'label'=>'Date Format', 'value'=>'dd/mm/yy', 'hint_text'=>$hint_date_format),
			'min_date'     => array('type'=>'text', 'name'=>'min_date', 'label'=>'Min. Date', 'placeholder'=>'The minimum selectable date', 'hint_text'=>$hint_min_date),
			'max_date'     => array('type'=>'text', 'name'=>'max_date', 'label'=>'Max. Date', 'placeholder'=>'The maximum selectable date', 'hint_text'=>$hint_max_date),
			'year_range'   => array('type'=>'text', 'name'=>'year_range', 'label'=>'Year Range', 'value'=>'-100:+1', 'hint_text'=>$hint_year_range),
			'number_of_months' => array('type'=>'text', 'name'=>'number_of_months', 'label'=>'Number Of Months', 'value'=>'1', 'hint_text'=>$hint_number_of_months),
			'disabled_days'  => array('type'=>'multiselect', 'name'=>'disabled_days', 'label'=>'Disabled Days', 'placeholder'=>'Select days to disable', 'options'=>$week_days),
			'disabled_dates' => array('type'=>'text', 'name'=>'disabled_dates', 'label'=>'Disabled Dates', 'placeholder'=>'Seperate dates with comma', 
			'hint_text'=>$hint_disabled_dates),
			
			'min_time'    => array('type'=>'text', 'name'=>'min_time', 'label'=>'Min. Time', 'value'=>'12:00am', 'sub_label'=>'ex: 12:30am'),
			'max_time'    => array('type'=>'text', 'name'=>'max_time', 'label'=>'Max. Time', 'value'=>'11:30pm', 'sub_label'=>'ex: 11:30pm'),
			'start_time'  => array('type'=>'text', 'name'=>'start_time', 'label'=>'Start Time', 'value'=>'', 'sub_label'=>'ex: 2h 30m'),
			'time_step'   => array('type'=>'text', 'name'=>'time_step', 'label'=>'Time Step', 'value'=>'30', 'sub_label'=>'In minutes, ex: 30'),
			'time_format' => array('type'=>'select', 'name'=>'time_format', 'label'=>'Time Format', 'value'=>'h:i A', 'options'=>$time_formats),
			'linked_date' => array('type'=>'text', 'name'=>'linked_date', 'label'=>'Linked Date'),

			'multiple_file'  => array('type'=>'checkbox', 'name'=>'multiple_file', 'label'=>'Multiple file upload', 'value'=>'yes', 'checked'=>0, 'onchange' => 'thwepoMultipleFileListener(this)'),
			'minfile'   => array('type'=>'text', 'name'=>'minfile', 'label'=>'Min. Files', 'hint_text'=>'The minimum number of files to be uploaded'),
			'maxfile'   => array('type'=>'text', 'name'=>'maxfile', 'label'=>'Max. Files', 'hint_text'=>'The maximum number of files to be uploaded'),

			'tooltip'	=> array('type'=>'text', 'name'=>'tooltip', 'label'=>'Tooltip Text'),
			'tooltip_size'     => array('type'=>'text', 'name'=>'tooltip_size', 'label'=>'Font Size', 'min' => '1'),
			'tooltip_color'    => array('type'=>'colorpicker', 'name'=>'tooltip_color', 'label'=>'Font Color'),
			'tooltip_bg_color'    => array('type'=>'colorpicker', 'name'=>'tooltip_bg_color', 'label'=>'Background Color'),
			'tooltip_border_color'    => array('type'=>'colorpicker', 'name'=>'tooltip_border_color', 'label'=>'Border Color'),

			'colorpicker_style' => array('type'=>'select', 'name'=>'colorpicker_style', 'label'=>'Colorpicker Style', 'options'=>$colorpicker_styles, 'onchange' => 'thwepoColorpickerStyleChangeListner(this)'),
			'colorpicker_radius'  => array('type'=>'text', 'name'=>'colorpicker_radius', 'label'=>'Border Radius 1'),
			'colorpreview_radius' => array('type'=>'text', 'name'=>'colorpreview_radius', 'label'=>'Border Radius 2', 'value' => 50),
		);
	}

	public function output_field_forms(){
		$this->output_field_form_pp();
		$this->output_form_fragments();
	}

	private function output_field_form_pp(){
		?>
        <div id="thwepo_field_form_pp" class="thpladmin-modal-mask">
          <?php $this->output_popup_form_fields(); ?>
        </div>
        <?php
	}

	/*****************************************/
	/********** POPUP FORM WIZARD ************/
	/*****************************************/
	private function output_popup_form_fields(){
		?>
		<div class="thpladmin-modal">
			<div class="modal-container">
				<span class="modal-close" onclick="thwepoCloseModal(this)">×</span>
				<div class="modal-content">
					<div class="modal-body">
						<div class="form-wizard wizard">
							<aside>
								<side-title class="wizard-title">Save Field</side-title>
								<ul class="pp_nav_links">
									<li class="text-primary active first pp-nav-link-basic" data-index="0">
										<i class="dashicons dashicons-admin-generic text-primary"></i>Basic Info
										<i class="i i-chevron-right dashicons dashicons-arrow-right-alt2"></i>
									</li>
									<li class="text-primary pp-nav-link-styles" data-index="1">
										<i class="dashicons dashicons-art text-primary"></i>Display Styles
										<i class="i i-chevron-right dashicons dashicons-arrow-right-alt2"></i>
									</li>
									<li class="text-primary pp-nav-link-tooltip" data-index="2">
										<i class="dashicons dashicons-admin-comments text-primary"></i>Tooltip Details
										<i class="i i-chevron-right dashicons dashicons-arrow-right-alt2"></i>
									</li>
									<li class="text-primary pp-nav-link-price" data-index="3">
										<i class="dashicons dashicons-cart text-primary"></i>Price Details
										<i class="i i-chevron-right dashicons dashicons-arrow-right-alt2"></i>
									</li>
									<li class="text-primary last pp-nav-link-rules" data-index="4">
										<i class="dashicons dashicons-filter text-primary"></i>Display Rules
										<i class="i i-chevron-right dashicons dashicons-arrow-right-alt2"></i>
									</li>
									<!--<li class="text-primary" data-index="5">
										<i class="dashicons dashicons-controls-repeat text-primary"></i>Repeat Rules
										<i class="i i-chevron-right dashicons dashicons-arrow-right-alt2"></i>
									</li>-->
								</ul>
							</aside>
							<main class="form-container main-full">
								<form method="post" id="thwepo_field_form" action="">
									<input type="hidden" name="f_action" value="" />
									<input type="hidden" name="i_name_old" value="" />
									<!--<input type="hidden" name="i_rowid" value="" />-->
					                <input type="hidden" name="i_original_type" value="" />

									<input type="hidden" name="i_options" value="" />
									<input type="hidden" name="i_rules" value="" />
									<input type="hidden" name="i_rules_ajax" value="" />

									<div class="data-panel data_panel_0">
										<?php $this->render_form_tab_general_info(); ?>
									</div>
									<div class="data-panel data_panel_1">
										<?php $this->render_form_tab_display_details(); ?>
									</div>
									<div class="data-panel data_panel_2">
										<?php $this->render_form_tab_tooltip_info(); ?>
									</div>
									<div class="data-panel data_panel_3">
										<?php $this->render_form_tab_price_info(); ?>
									</div>
									<div class="data-panel data_panel_4">
										<?php $this->render_form_tab_display_rules(); ?>
									</div>
									<!--<div class="data-panel data_panel_5">
										<?php //$this->render_form_tab_repeat_rules(); ?>
									</div>-->
								</form>
							</main>
							<footer>
								<span class="Loader"></span>
								<div class="btn-toolbar">
									<button class="save-btn pull-right btn btn-primary" onclick="thwepoSaveField(this)">
										<span>Save & Close</span>
									</button>
									<button class="next-btn pull-right btn btn-primary-alt" onclick="thwepoWizardNext(this)">
										<span>Next</span><i class="i i-plus"></i>
									</button>
									<button class="prev-btn pull-right btn btn-primary-alt" onclick="thwepoWizardPrevious(this)">
										<span>Back</span><i class="i i-plus"></i>
									</button>
								</div>
							</footer>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/*----- TAB - General Info -----*/
	private function render_form_tab_general_info(){
		$this->render_form_tab_main_title('Basic Details');

		?>
		<div style="display: inherit;" class="data-panel-content">
			<?php
			$this->render_form_fragment_general();
			//$this->render_form_field_inputtext();
			?>
			<table class="thwepo_field_form_tab_general_placeholder thwepo_pp_table thwepo-general-info"></table>
		</div>
		<?php
	}

	/*----- TAB - Display Details -----*/
	private function render_form_tab_display_details(){
		$this->render_form_tab_main_title('Display Settings');

		?>
		<div style="display: inherit;" class="data-panel-content mt-10">
			<table class="thwepo_pp_table compact thwepo-display-info">
				<?php
				$this->render_form_elm_row($this->field_props['cssclass']);
				$this->render_form_elm_row($this->field_props['input_class']);
				$this->render_form_elm_row($this->field_props['title_class']);
				$this->render_form_elm_row($this->field_props['subtitle_class']);

				$this->render_form_elm_row($this->field_props['title_position']);
				$this->render_form_elm_row($this->field_props['title_type']);
				$this->render_form_elm_row($this->field_props['title_color']);
				$this->render_form_elm_row($this->field_props['subtitle_type']);
				$this->render_form_elm_row($this->field_props['subtitle_color']);

				//$this->render_form_elm_row($this->field_props['colorpicker_style']);
				//$this->render_form_elm_row($this->field_props['colorpicker_radius']);
				//$this->render_form_elm_row($this->field_props['colorpreview_radius']);

				$this->render_form_elm_row_cb($this->field_props['hide_in_cart']);
				$this->render_form_elm_row_cb($this->field_props['hide_in_checkout']);
				$this->render_form_elm_row_cb($this->field_props['hide_in_order']);
				$this->render_form_elm_row_cb($this->field_props['hide_in_order_admin']);
				?>
			</table>
		</div>
		<?php
	}

	/*----- TAB - Tooltip Info -----*/
	private function render_form_tab_tooltip_info(){
		$this->render_form_tab_main_title('Tooltip Details');

		?>
		<div style="display: inherit;" class="data-panel-content">
			<table class="thwepo_pp_table thwepo-tooltip-info">
				<?php
				$this->render_form_elm_row($this->field_props['tooltip']);
				$this->render_form_elm_row($this->field_props['tooltip_size']);
				$this->render_form_elm_row_cp($this->field_props['tooltip_color']);
				$this->render_form_elm_row_cp($this->field_props['tooltip_bg_color']);
				//$this->render_form_elm_row_cp($this->field_props['tooltip_border_color']);
				?>
			</table>
		</div>
		<?php
	}

	/*----- TAB - Price Info -----*/
	private function render_form_tab_price_info(){
		$this->render_form_tab_main_title('Price Details');

		$price_type_props = $this->field_props['price_type'];
		$options = isset($price_type_props['options']) ? $price_type_props['options'] : array();
		
		/*if($type === 'datepicker' || $type === 'timepicker' || $type === 'checkbox' || $type === 'file'){
			unset($options['custom']);
			unset($options['dynamic']);
			unset($options['dynamic-excl-base-price']);
		}*/
		
		$price_type_props['options'] = $options;

		?>
		<div style="display: inherit;" class="data-panel-content">
			<table class="thwepo_pp_table thwepo-price-info">
				<tr class="form_field_price_type">
					<?php $this->render_form_field_element($price_type_props, $this->cell_props); ?>
		        </tr>
		        <tr class="form_field_price">
		            <td class="label"><?php THWEPO_i18n::_et('Price'); ?></td>
		            <?php $this->render_form_fragment_tooltip(false); ?>
		            <td class="field">
		            	<input type="text" name="i_price" placeholder="Price" style="width:260px;" class="thpladmin-price-field"/>
		                <label class="thpladmin-dynamic-price-field" style="display:none">per</label>
		                <input type="text" name="i_price_unit" placeholder="Unit" style="width:80px; display:none" class="thpladmin-dynamic-price-field"/>
		                <label class="thpladmin-dynamic-price-field" style="display:none">unit</label>
		            </td>
				</tr> 
				<tr style="display:none" class="thpladmin-dynamic-price-field">        
		            <?php          
		        	$this->render_form_field_element($this->field_props['price_min_unit'], $this->cell_props);
					?> 
				</tr>
				<?php
				$this->render_form_elm_row_cb($this->field_props['show_price_label']);
				$this->render_form_elm_row_cb($this->field_props['show_price_in_order']);
				$this->render_form_elm_row_cb($this->field_props['price_flat_fee']);
				//$this->render_form_elm_row_cb($this->field_props['show_price_table']);
				?>
			</table>
		</div>
		<?php
	}

	/*----- TAB - Display Rules -----*/
	private function render_form_tab_display_rules(){
		$this->render_form_tab_main_title('Display Rules');

		?>
		<div style="display: inherit;" class="data-panel-content">
			<table class="thwepo_pp_table thwepo-display-rules">
				<?php
				$this->render_form_fragment_rules(); 
				$this->render_form_fragment_rules_ajax();
				?>
			</table>
		</div>
		<?php
	}

	/*----- TAB - Repeat Rules -----*/
	/*private function render_form_tab_repeat_rules(){
		$this->render_form_tab_main_title('Repeat Rules');

		?>
		<div style="display: inherit;" class="data-panel-content">
			<?php
			?>
		</div>
		<?php
	}*/

	/*-------------------------------*/
	/*------ Form Field Groups ------*/
	/*-------------------------------*/
	private function render_form_fragment_general($input_field = true){
		$field_types = $this->get_field_types();
		
		$field_name_label = $input_field ? THWEPO_i18n::__t('Name') : THWEPO_i18n::__t('ID');
		?>
		<div class="err_msgs"></div>
        <table class="thwepo_pp_table">
        	<?php
			$this->render_form_elm_row($this->field_props['type']);
			$this->render_form_elm_row($this->field_props['name']);
			?>
        </table>  
        <?php
	}

	private function output_form_fragments(){
		$this->render_form_field_inputtext();
		$this->render_form_field_hidden();
		$this->render_form_field_password();
		$this->render_form_field_number();
		$this->render_form_field_tel();	
		$this->render_form_field_textarea();
		$this->render_form_field_select();
		$this->render_form_field_multiselect();		
		$this->render_form_field_radio();
		$this->render_form_field_checkbox();
		$this->render_form_field_checkboxgroup();
		$this->render_form_field_datepicker();
		$this->render_form_field_timepicker();
		$this->render_form_field_file();		
		$this->render_form_field_heading();
		$this->render_form_field_html();
		$this->render_form_field_label();
		$this->render_form_field_default();
		$this->render_form_field_colorpicker();
		
		$this->render_field_form_fragment_product_list();
		$this->render_field_form_fragment_category_list();
		$this->render_field_form_fragment_tag_list();
		$this->render_field_form_fragment_user_role_list();
		$this->render_field_form_fragment_fields_wrapper();
	}

	private function render_form_field_inputtext(){
		?>
        <table id="thwepo_field_form_id_inputtext" class="thwepo_pp_table" style="display:none;">
        	<?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['value']);
			$this->render_form_elm_row($this->field_props['placeholder']);
			$this->render_form_elm_row($this->field_props['maxlength']);
			$this->render_form_elm_row($this->field_props['validate']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>
        </table>
        <?php   
	}

	private function render_form_field_hidden(){
		?>
        <table id="thwepo_field_form_id_hidden" class="thwepo_field_form_table" width="100%" style="display:none;">
			<?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['value']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>  
        </table>
        <?php   
	}
	
	private function render_form_field_password(){
		?>
        <table id="thwepo_field_form_id_password" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['placeholder']);
			$this->render_form_elm_row($this->field_props['maxlength']);
			$this->render_form_elm_row($this->field_props['validate']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>  
        </table>
        <?php   
	}

	private function render_form_field_number(){
		?>
        <table id="thwepo_field_form_id_number" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['value']);
			$this->render_form_elm_row($this->field_props['placeholder']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>     
        </table>
        <?php   
	}

	private function render_form_field_tel(){
		?>
        <table id="thwepo_field_form_id_tel" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['value']);
			$this->render_form_elm_row($this->field_props['placeholder']);
			$this->render_form_elm_row($this->field_props['maxlength']);
			$this->render_form_elm_row($this->field_props['validate']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>    
        </table>
        <?php   
	}
	
	private function render_form_field_textarea(){
		?>
        <table id="thwepo_field_form_id_textarea" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['value']);
			$this->render_form_elm_row($this->field_props['placeholder']);
			$this->render_form_elm_row($this->field_props['maxlength']);
			$this->render_form_elm_row($this->field_props['cols']);
			$this->render_form_elm_row($this->field_props['rows']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>     
        </table>
        <?php   
	}
	
	private function render_form_field_select(){
		?>
        <table id="thwepo_field_form_id_select" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['value']);
			$this->render_form_elm_row($this->field_props['placeholder']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);

			$this->render_form_fragment_h_spacing();
			$this->render_form_fragment_options();
			?>
        </table>
        <?php   
	}
	
	private function render_form_field_multiselect(){
		$field_props_maxlength = $this->field_props['maxlength'];
		$field_props_maxlength['label'] = 'Max. Selections';
		$field_props_maxlength['hint_text'] = 'The maximum number of options that can be selected';
		?>
        <table id="thwepo_field_form_id_multiselect" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['value']);
			$this->render_form_elm_row($this->field_props['placeholder']);
			$this->render_form_elm_row($field_props_maxlength);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);

			$this->render_form_fragment_h_spacing();
			$this->render_form_fragment_options();
			?> 
        </table>
        <?php   
	}
	
	private function render_form_field_radio(){
		?>
        <table id="thwepo_field_form_id_radio" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['value']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);

			$this->render_form_fragment_h_spacing();
			$this->render_form_fragment_options();
			?>
        </table>
        <?php   
	}
	
	private function render_form_field_checkbox(){
		$value_props = $this->field_props['value'];
		$value_props['label'] = 'Value';

		?>
        <table id="thwepo_field_form_id_checkbox" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($value_props);

			$this->render_form_elm_row_cb($this->field_props['checked']);
			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);

			//$this->render_form_fragment_h_spacing();
			// $this->render_form_fragment_options();
			?>  
        </table>
        <?php   
	}
	
	private function render_form_field_checkboxgroup(){
		?>
        <table id="thwepo_field_form_id_checkboxgroup" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['value']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);

			$this->render_form_fragment_h_spacing();
			$this->render_form_fragment_options();
			?>
        </table>
        <?php   
	}
	
	private function render_form_field_datepicker(){
		?>
        <table id="thwepo_field_form_id_datepicker" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['placeholder']);

			$this->render_form_elm_row($this->field_props['date_format']);
			$this->render_form_elm_row($this->field_props['default_date']);
			$this->render_form_elm_row($this->field_props['min_date']);
			$this->render_form_elm_row($this->field_props['max_date']);
			$this->render_form_elm_row($this->field_props['year_range']);
			$this->render_form_elm_row($this->field_props['number_of_months']);
			$this->render_form_elm_row($this->field_props['disabled_days']);
			$this->render_form_elm_row($this->field_props['disabled_dates']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?> 
        </table>
        <?php   
	}
	
	private function render_form_field_timepicker(){
		?>
        <table id="thwepo_field_form_id_timepicker" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['value']);
			$this->render_form_elm_row($this->field_props['placeholder']);

			$this->render_form_elm_row($this->field_props['min_time']);
			$this->render_form_elm_row($this->field_props['max_time']);
			$this->render_form_elm_row($this->field_props['time_step']);
			$this->render_form_elm_row($this->field_props['time_format']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>
        </table>
        <?php   
	}

	private function render_form_field_colorpicker(){
		$colorpicker_style = isset( $this->field_props['colorpicker_style']['value'] ) && $this->field_props['colorpicker_style']['value'] == 'style2' ? "table-row" : "none";
		?>
        <table id="thwepo_field_form_id_colorpicker" class="thwepo_field_form_table" width="100%" style="display:none;">
			<?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['value']);
			//$this->render_form_elm_row($this->field_props['tooltip']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>
        </table>
        <?php   
	}
	
	private function render_form_field_file(){
		?>
        <table id="thwepo_field_form_id_file" class="thwepo_field_form_table" width="100%" style="display:none;">
			<?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['maxsize']);
			$this->render_form_elm_row($this->field_props['accept']);
			$this->render_form_elm_row($this->field_props['minfile']);
			$this->render_form_elm_row($this->field_props['maxfile']);

			$this->render_form_elm_row_cb($this->field_props['multiple_file']);
			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>
        </table>
        <?php   
	}
	
	private function render_form_field_heading(){
		$title_props = $this->field_props['title'];
		$title_props['required'] = true;

		?>
        <table id="thwepo_field_form_id_heading" class="thwepo_field_form_table" width="100%" style="display:none;">
			<?php
			$this->render_form_elm_row($title_props);
			$this->render_form_elm_row($this->field_props['title_type']);
			$this->render_form_elm_row_cp($this->field_props['title_color']);
			$this->render_form_elm_row($this->field_props['title_class']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['subtitle_type']);
			$this->render_form_elm_row_cp($this->field_props['subtitle_color']);
			$this->render_form_elm_row($this->field_props['subtitle_class']);

			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>
        </table>
        <?php   
	}

	private function render_form_field_html(){
		$content_props = $this->field_props['value'];
		$content_props['type']     = 'textarea';
		$content_props['label']    = 'Content';
		$content_props['required'] = true;
		?>
        <table id="thwepo_field_form_id_html" class="thwepo_field_form_table" width="100%" style="display:none;">
			<?php
			$this->render_form_elm_row_ta($content_props);
			$this->render_form_elm_row($this->field_props['cssclass']);

			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>     
        </table>
        <?php   
	}
	
	private function render_form_field_label(){
		$title_props = $this->field_props['title'];
		$title_props['type']     = 'textarea';
		$title_props['label']    = 'Content';
		$title_props['required'] = true;

		$title_type_props = $this->field_props['title_type'];
		$title_type_props['label'] = 'Tag Type';

		$title_color_props = $this->field_props['title_color'];
		$title_color_props['label'] = 'Content Color';

		$title_class_props = $this->field_props['title_class'];
		$title_class_props['label'] = 'Wrapper Class';
		?>
        <table id="thwepo_field_form_id_label" class="thwepo_field_form_table" width="100%" style="display:none;">
			<?php
			$this->render_form_elm_row_ta($title_props);
			$this->render_form_elm_row($title_type_props);
			$this->render_form_elm_row_cp($title_color_props);
			$this->render_form_elm_row($title_class_props);

			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>     
        </table>
        <?php   
	}
	
	private function render_form_field_default(){
		?>
        <table id="thwepo_field_form_id_default" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['value']);
			$this->render_form_elm_row($this->field_props['placeholder']);
			$this->render_form_elm_row($this->field_props['maxlength']);
			$this->render_form_elm_row($this->field_props['validate']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>    
        </table>
        <?php   
	}

	private function render_form_fragment_options(){
		?>
		<tr>
			<td class="sub-title"><?php THWEPO_i18n::_et('Options'); ?></td>
			<?php $this->render_form_fragment_tooltip(); ?>
			<td></td>
		</tr>
		<tr>
			<td colspan="3" class="p-0">
				<table border="0" cellpadding="0" cellspacing="0" class="thwepo-option-list thpladmin-options-table"><tbody>
					<tr>
						<td class="key"><input type="text" name="i_options_key[]" placeholder="Option Value"></td>
						<td class="value"><input type="text" name="i_options_text[]" placeholder="Option Text"></td>
						<td class="price"><input type="text" name="i_options_price[]" placeholder="Price"></td>
						<td class="price-type">    
							<select name="i_options_price_type[]">
								<option selected="selected" value="">Fixed</option>
								<option value="percentage">Percentage</option>
							</select>
						</td>
						<td class="action-cell">
							<a href="javascript:void(0)" onclick="thwepoAddNewOptionRow(this)" class="btn btn-tiny btn-primary" title="Add new option">+</a><a href="javascript:void(0)" onclick="thwepoRemoveOptionRow(this)" class="btn btn-tiny btn-danger" title="Remove option">x</a><span class="btn btn-tiny sort ui-sortable-handle"></span>
						</td>
					</tr>
				</tbody></table>            	
			</td>
		</tr>
        <?php
	}
}

endif;