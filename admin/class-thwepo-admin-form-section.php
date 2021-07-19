<?php
/**
 * The admin section forms functionalities.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/admin
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEPO_Admin_Form_Section')):

class THWEPO_Admin_Form_Section extends THWEPO_Admin_Form{
	private $section_props = array();

	public function __construct() {
		$this->section_props = $this->get_section_form_props();
	}

	public function get_available_positions(){
		$positions = array(
			'woo_before_add_to_cart_button'		=> 'Before Add To Cart Button',
			'woo_after_add_to_cart_button'		=> 'After Add To Cart Button',
			'woo_single_variation_5' 			=> 'Before Variation Price (for variable products)',
			/*'woo_before_add_to_cart_quantity' 	=> 'Before Add To Cart Quantity',
			'woo_after_add_to_cart_quantity'  	=> 'After Add To Cart Quantity',
			'woo_before_variations_form' 		=> 'Before Variations Form',
			'woo_after_variations_form'  		=> 'After Variations Form',
			'woo_before_single_variation' 		=> 'Before Single Variation',
			'woo_after_single_variation'  		=> 'After Single Variation',
			'woo_single_variation_90' 			=> 'woo_single_variation_90',
			
			'woo_single_product_before_title' 		=> 'Before Title',
			'woo_single_product_after_title' 		=> 'After Title',
			'woo_single_product_before_rating' 		=> 'Before Rating',
			'woo_single_product_after_rating' 		=> 'After Rating',
			'woo_single_product_before_price' 		=> 'Before Price',
			'woo_single_product_after_price' 		=> 'After Price',
			'woo_single_product_before_excerpt' 	=> 'Before Excerpt',
			'woo_single_product_after_excerpt' 		=> 'After Excerpt',
			'woo_single_product_before_add_to_cart' => 'Before Add To Cart',
			'woo_single_product_after_add_to_cart'  => 'After Add To Cart',			
			'woo_single_product_before_meta' 		=> 'Before Meta',
			'woo_single_product_after_meta' 		=> 'After Meta',
			'woo_single_product_before_sharing' 	=> 'Before Sharing',
			'woo_single_product_after_sharing' 		=> 'After Sharing',*/
		);

		if(apply_filters('thwepo_enable_additional_positions', false)){
			$positions['woo_before_variations_form'] = 'Before Variations Form';
		}

		return apply_filters('thwepo_extra_fields_display_position', $positions);
	}
	
	public function get_section_form_props(){
		$positions = $this->get_available_positions();
		$html_text_tags = $this->get_html_text_tags();
		
		/*$box_types = array(
			'' 				 => 'Normal (clear)',
			'box' 			 => 'Box',
			'collapse' 		 => 'Expand and Collapse (start opened)',
			'collapseclosed' => 'Expand and Collapse (start closed)',
			'accordion' 	 => 'Accordion',
		);*/
		//$title_positions = array( '' => 'Above field', 'left' => 'Left of the field', 'right' => 'Right of the field', 'disable' => 'Disable' );
		
		return array(
			'name' 		 => array('name'=>'name', 'label'=>'Name/ID', 'type'=>'text', 'required'=>1),
			'position' 	 => array('name'=>'position', 'label'=>'Display Position', 'type'=>'select', 'options'=>$positions, 'required'=>1),
			//'box_type' 	 => array('name'=>'box_type', 'label'=>'Box Type', 'type'=>'select', 'options'=>$box_types),
			'order' 	 => array('name'=>'order', 'label'=>'Display Order', 'type'=>'text'),
			'cssclass' 	 => array('name'=>'cssclass', 'label'=>'CSS Class', 'type'=>'text'),
			'show_title' => array('name'=>'show_title', 'label'=>'Show section title in product page.', 'type'=>'checkbox', 'value'=>'yes', 'checked'=>1),
			
			'title_cell_with' => array('name'=>'title_cell_with', 'label'=>'Col-1 Width', 'type'=>'text', 'value'=>''),
			'field_cell_with' => array('name'=>'field_cell_with', 'label'=>'Col-2 Width', 'type'=>'text', 'value'=>''),
			
			'title' 		   => array('name'=>'title', 'label'=>'Title', 'type'=>'text', 'required'=>1),
			//'title_position' => array('name'=>'title_position', 'label'=>'Title Position', 'type'=>'select', 'options'=>$title_positions),
			'title_type' 	   => array('name'=>'title_type', 'label'=>'Title Type', 'type'=>'select', 'value'=>'h3', 'options'=>$html_text_tags),
			'title_color' 	   => array('name'=>'title_color', 'label'=>'Title Color', 'type'=>'colorpicker'),
			'title_class' 	   => array('name'=>'title_class', 'label'=>'Title Class', 'type'=>'text'),
			
			'subtitle' 			  => array('name'=>'subtitle', 'label'=>'Subtitle', 'type'=>'text'),
			//'subtitle_position' => array('name'=>'subtitle_position', 'label'=>'Subtitle Position', 'type'=>'select', 'options'=>$title_positions),
			'subtitle_type' 	  => array('name'=>'subtitle_type', 'label'=>'Subtitle Type', 'type'=>'select', 'value'=>'h3', 'options'=>$html_text_tags),
			'subtitle_color' 	  => array('name'=>'subtitle_color', 'label'=>'Subtitle Color', 'type'=>'colorpicker'),
			'subtitle_class' 	  => array('name'=>'subtitle_class', 'label'=>'Subtitle Class', 'type'=>'text'),
		);
	}

	public function output_section_forms(){
		?>
        <div id="thwepo_section_form_pp" class="thpladmin-modal-mask">
          <?php $this->output_popup_form_section(); ?>
        </div>
        <?php
	}

	/*****************************************/
	/********** POPUP FORM WIZARD ************/
	/*****************************************/

	private function output_popup_form_section(){
		?>
		<div class="thpladmin-modal">
			<div class="modal-container">
				<span class="modal-close" onclick="thwepoCloseModal(this)">×</span>
				<div class="modal-content">
					<div class="modal-body">
						<div class="form-wizard wizard">
							<aside>
								<side-title class="wizard-title">Save Section</side-title>
								<ul class="pp_nav_links">
									<li class="text-primary active first" data-index="0">
										<i class="dashicons dashicons-admin-generic text-primary"></i>Basic Info
										<i class="i i-chevron-right dashicons dashicons-arrow-right-alt2"></i>
									</li>
									<li class="text-primary" data-index="1">
										<i class="dashicons dashicons-art text-primary"></i>Display Styles
										<i class="i i-chevron-right dashicons dashicons-arrow-right-alt2"></i>
									</li>
									<li class="text-primary last" data-index="2">
										<i class="dashicons dashicons-filter text-primary"></i>Display Rules
										<i class="i i-chevron-right dashicons dashicons-arrow-right-alt2"></i>
									</li>
									<!--<li class="text-primary" data-index="3">
										<i class="dashicons dashicons-controls-repeat text-primary"></i>Repeat Rules
										<i class="i i-chevron-right dashicons dashicons-arrow-right-alt2"></i>
									</li>-->
								</ul>
							</aside>
							<main class="form-container main-full">
								<form method="post" id="thwepo_section_form" action="">
									<input type="hidden" name="s_action" value="" />
									<input type="hidden" name="s_name" value="" />
									<input type="hidden" name="s_name_copy" value="" />
									<input type="hidden" name="i_position_old" value="" />
									<input type="hidden" name="i_rules" value="" />
									<input type="hidden" name="i_rules_ajax" value="" />

									<div class="data-panel data_panel_0">
										<?php $this->render_form_tab_general_info(); ?>
									</div>
									<div class="data-panel data_panel_1">
										<?php $this->render_form_tab_display_details(); ?>
									</div>
									<div class="data-panel data_panel_2">
										<?php $this->render_form_tab_display_rules(); ?>
									</div>
									<!--<div class="data-panel data_panel_3">
										<?php //$this->render_form_tab_repeat_rules(); ?>
									</div>-->
								</form>
							</main>
							<footer>
								<span class="Loader"></span>
								<div class="btn-toolbar">
									<button class="save-btn pull-right btn btn-primary" onclick="thwepoSaveSection(this)">
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
			<div class="err_msgs"></div>
			<table class="thwepo_pp_table">
				<?php
				$this->render_form_elm_row($this->section_props['name']);
				$this->render_form_elm_row($this->section_props['title']);
				$this->render_form_elm_row($this->section_props['subtitle']);
				$this->render_form_elm_row($this->section_props['position']);
				$this->render_form_elm_row($this->section_props['order']);
				$this->render_form_elm_row($this->section_props['title_cell_with']);
				$this->render_form_elm_row($this->section_props['field_cell_with']);

				$this->render_form_elm_row_cb($this->section_props['show_title']);			
				?>
			</table>
		</div>
		<?php
	}

	/*----- TAB - Display Details -----*/
	private function render_form_tab_display_details(){
		$this->render_form_tab_main_title('Display Settings');

		?>
		<div style="display: inherit;" class="data-panel-content">
			<table class="thwepo_pp_table">
				<?php
				$this->render_form_elm_row($this->section_props['cssclass']);
				$this->render_form_elm_row($this->section_props['title_class']);
				$this->render_form_elm_row($this->section_props['subtitle_class']);

				$this->render_form_elm_row($this->section_props['title_type']);
				$this->render_form_elm_row($this->section_props['title_color']);
				$this->render_form_elm_row($this->section_props['subtitle_type']);
				$this->render_form_elm_row($this->section_props['subtitle_color']);
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
				$this->render_form_fragment_rules('section'); 
				$this->render_form_fragment_rules_ajax('section');
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
	
}

endif;