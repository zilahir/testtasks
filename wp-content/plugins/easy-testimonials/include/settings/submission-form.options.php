<?php
class easyTestimonialSubmissionFormOptions extends easyTestimonialOptions{
	var $tabs;
	var $config;
	
	function __construct($config){			
		//call register settings function
		add_action( 'admin_init', array($this, 'register_settings'));	
		
		//assign config
		$this->config = $config;
		
		//insert top level tab	
		add_action('easy_t_admin_tabs', array($this, 'insert_top_level_tab'), 1);
		
		//insert submenu item
		add_action('easy_t_admin_submenu_pages' , array($this, 'insert_submenu_page'), 1);
	}

	//insert our submenu page
	function insert_submenu_page($submenu_pages){		
		$submission_form_page = array(
			//basic options page
			array(
				'top_level_slug' => 'easy-testimonials-settings',
				'page_title' => 'Submission Form',
				'menu_title' => 'Submission Form Settings',
				'role' => 'administrator',
				'slug' => 'easy-testimonials-submission-settings',
				'callback' => array($this, 'render_settings_page'),
				'hide_in_menu' => true
			)
		);
		
		// insert the new menu item after the import/export menu item
		// Note: this function takes $submenu_pages by reference,
		// and returns nothing
		$this->insert_submenu_page_after_target(
			$submenu_pages,
			'easy-testimonials-style-settings',
			$submission_form_page
		);
		
		return $submenu_pages;
	}
	
	/**
	* Inserts a new page into an existing list of submenu pages.
	* Insertion is performed *after* the first array item who's
	* menu_slug key matches the target
	*
	* @param array      $submenu_pages	The array of pages. Modified directly.
	* @param string 	$target_slug	The menu_slug to match against
	* @param mixed      $insert			The submenu page to insert
	*/
	function insert_submenu_page_after_target(&$submenu_pages, $target_slug, $insert)
	{
		$pos = count($submenu_pages) - 1; // default to last position
		
		// find the target slug in the list of pages
		foreach ($submenu_pages as $index => $page) {
			if ( $page['slug'] == $target_slug ) {
				$pos = $index;
				break;
			}
		}
		// insert the new page at the target position
		$submenu_pages = array_merge(
			array_slice($submenu_pages, 0, $pos + 1),
			$insert,
			array_slice($submenu_pages, $pos + 1)
		);
	}
	
	//adds tab to top level of settings screen
	function insert_top_level_tab($tabs){
		$tabs['easy-testimonials-submission-settings'] = __('Submission Form', 'easy-testimonials');
		
		return $tabs;
	}
			
	//register our settings	
	function register_settings(){		
		/* Submission form settings */
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_title_field_label' );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_title_field_description' );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_hide_title_field' );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_name_field_label' );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_name_field_description' );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_position_web_other_field_label' );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_position_web_other_field_description' );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_other_other_field_label' );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_other_other_field_description' );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_category_field_label' );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_category_field_description' );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_body_content_field_label' );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_body_content_field_description' );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_submit_button_label' );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_submit_success_message' );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_submit_notification_address' );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_submit_notification_include_testimonial' );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_submit_success_redirect_url' );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_hide_position_web_other_field' );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_hide_other_other_field' );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_hide_category_field' );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_hide_name_field' );	
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_hide_email_field' );	
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_email_field_label' );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_email_field_description' );		
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_use_captcha', array($this, 'enable_captcha_callback')  );	
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_image_field_label' );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_image_field_description' );	
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_use_image_field' );	
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_captcha_field_label' );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_captcha_field_description' );	
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_recaptcha_api_key' );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_recaptcha_secret_key' );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_recaptcha_lang' );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_rating_field_label' );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_rating_field_description' );	
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_use_rating_field' );	
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_cache_buster', array($this, 'easy_t_bust_options_cache') );
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_general_error' );	
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_captcha_field_error' );	
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_body_field_error' );	
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_title_field_error' );	
		register_setting( 'easy-testimonials-submission_form_options-settings-group', 'easy_t_testimonial_author' );
	}
	
	function render_settings_page(){	
		//add upgrade button if free version
		$extra_buttons = array();
		if(!$this->config->is_pro){
			$extra_buttons = array(
				array(
					'class' => 'btn-purple',
					'label' => 'Upgrade To Pro',
					'url' => 'https://goldplugins.com/our-plugins/easy-testimonials-details/upgrade-to-easy-testimonials-pro/'
				)
			);
		}
		
		//instantiate tabs object for output basic settings page tabs
		$tabs = new GP_Sajak( array(
			'header_label' => 'Submission Form Settings',
			'settings_field_key' => 'easy-testimonials-submission_form_options-settings-group', // can be an array			
			'extra_buttons_header' => $extra_buttons, // extra header buttons
			'extra_buttons_footer' => $extra_buttons // extra footer buttons
		) );		
		
		$this->settings_page_top();
		$this->setup_basic_tabs($tabs);
		$this->settings_page_bottom();
	}
	
	function setup_basic_tabs($tabs){	
		$this->tabs = $tabs;
	
		$this->tabs->add_tab(
			'submission_settings_page', // section id, used in url fragment
			'Field Labels &amp; Descriptions', // section label
			array($this, 'output_field_settings'), // display callback
			array(
				'class' => 'extra_li_class', // extra classes to add sidebar menu <li> and tab wrapper <div>
				'icon' => 'list-ul' // icons here: http://fontawesome.io/icons/
			)
		);
	
		$this->tabs->add_tab(
			'author_settings_page', // section id, used in url fragment
			'Author Options', // section label
			array($this, 'output_author_settings'), // display callback
			array(
				'class' => 'extra_li_class', // extra classes to add sidebar menu <li> and tab wrapper <div>
				'icon' => 'user' // icons here: http://fontawesome.io/icons/
			)
		);
	
		$this->tabs->add_tab(
			'notification_settings_page', // section id, used in url fragment
			'Notification Options', // section label
			array($this, 'output_notification_settings'), // display callback
			array(
				'class' => 'extra_li_class', // extra classes to add sidebar menu <li> and tab wrapper <div>
				'icon' => 'envelope-o' // icons here: http://fontawesome.io/icons/
			)
		);
	
		$this->tabs->add_tab(
			'spam_settings_page', // section id, used in url fragment
			'Spam Prevention Options', // section label
			array($this, 'output_spam_settings'), // display callback
			array(
				'class' => 'extra_li_class', // extra classes to add sidebar menu <li> and tab wrapper <div>
				'icon' => 'shield' // icons here: http://fontawesome.io/icons/
			)
		);
		
		$this->tabs->display();
	}

	function output_field_settings(){
?>
		<h3 id="field-labels-descriptions">Field Labels and Descriptions</h3>		
		<fieldset>
			<legend><?php echo get_option('easy_t_title_field_label', 'Title'); ?> Field</legend>			
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="easy_t_title_field_label">Label</label></th>
					<td><input type="text" name="easy_t_title_field_label" id="easy_t_title_field_label" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?> value="<?php echo get_option('easy_t_title_field_label', 'Title'); ?>" />
					<p class="description">This is the label of the <?php echo get_option('easy_t_title_field_label', 'Title'); ?> field in the form, which defaults to "Title".  Contents of this field will be passed through to the Title field inside WordPress.</p>
					</td>
				</tr>
			</table>
			
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="easy_t_title_field_description">Description</label></th>
					<td><textarea name="easy_t_title_field_description" id="easy_t_title_field_description" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?>><?php echo get_option('easy_t_title_field_description', 'Please give your Testimonial a Title.'); ?></textarea>
					<p class="description">This is the description below the <?php echo get_option('easy_t_title_field_label', 'Title'); ?> field in the form.</p>
					</td>
				</tr>
			</table>
			
			<table class="form-table">
				<tr valign="top">
					<td><input type="checkbox" name="easy_t_hide_title_field" id="easy_t_hide_title_field" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?> value="1" <?php if(get_option('easy_t_hide_title_field', 0)){ ?> checked="CHECKED" <?php } ?>/>
					<label for="easy_t_hide_title_field">Disable Display</label>
					<p class="description">If checked, the <?php echo get_option('easy_t_title_field_label', 'Title'); ?> field will not be displayed in the form.  The testimoinal title will default to the name of the person leaving the testimonial, if not manually entered.</p>
					</td>
				</tr>
			</table>
		</fieldset>
		
		<fieldset>
			<legend><?php echo get_option('easy_t_name_field_label', 'Name'); ?> Field</legend>		
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="easy_t_name_field_label">Label</label></th>
					<td><input type="text" name="easy_t_name_field_label" id="easy_t_name_field_label" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?> value="<?php echo get_option('easy_t_name_field_label', 'Name'); ?>" />
					<p class="description">This is the label of the <?php echo get_option('easy_t_name_field_label', 'Name'); ?> field in the form, which defaults to "Name".  Contents of this field will be passed through to the Client Name field inside WordPress.</p>
					</td>
				</tr>
			</table>
						
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="easy_t_name_field_description">Description</label></th>
					<td><textarea name="easy_t_name_field_description" id="easy_t_name_field_description" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?>><?php echo get_option('easy_t_name_field_description', 'Please enter your Full Name.'); ?></textarea>
					<p class="description">This is the description below the <?php echo get_option('easy_t_name_field_label', 'Name'); ?> field.</p>
					</td>
				</tr>
			</table>
			
			<table class="form-table">
				<tr valign="top">
					<td><input type="checkbox" name="easy_t_hide_name_field" id="easy_t_hide_name_field" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?> value="1" <?php if(get_option('easy_t_hide_name_field', 0)){ ?> checked="CHECKED" <?php } ?>/>
					<label for="easy_t_hide_name_field">Disable Display</label>
					<p class="description">If checked, the <?php echo get_option('easy_t_name_field_label', 'Name'); ?> field will not be displayed in the form .</p>
					</td>
				</tr>
			</table>
		</fieldset>
		
		<fieldset>
			<legend><?php echo get_option('easy_t_email_field_label', 'Your E-Mail Address'); ?> Field</legend>		
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="easy_t_email_field_label">Label</label></th>
					<td><input type="text" name="easy_t_email_field_label" id="easy_t_email_field_label" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?> value="<?php echo get_option('easy_t_email_field_label', 'Your E-Mail Address'); ?>" />
					<p class="description">This is the label of the <?php echo get_option('easy_t_email_field_label', 'Your E-Mail Address'); ?> field in the form, which defaults to "Your E-Mail Address".  Contents of this field will be passed through to the E-Mail Address field inside WordPress.</p>
					</td>
				</tr>
			</table>
						
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="easy_t_email_field_description">Description</label></th>
					<td><textarea name="easy_t_email_field_description" id="easy_t_email_field_description" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?>><?php echo get_option('easy_t_email_field_description', 'Please enter your e-mail address.  This information will not be publicly displayed.'); ?></textarea>
					<p class="description">This is the description below the <?php echo get_option('easy_t_email_field_label', 'Your E-Mail Address'); ?> field.</p>
					</td>
				</tr>
			</table>
			
			<table class="form-table">
				<tr valign="top">
					<td><input type="checkbox" name="easy_t_hide_email_field" id="easy_t_hide_email_field" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?> value="1" <?php if(get_option('easy_t_hide_email_field', 0)){ ?> checked="CHECKED" <?php } ?>/>
					<label for="easy_t_hide_email_field">Disable Display</label>
					<p class="description">If checked, the <?php echo get_option('easy_t_email_field_label', 'Your E-Mail Address'); ?> field will not be displayed in the form .</p>
					</td>
				</tr>
			</table>
		</fieldset>
		
		<fieldset>
			<legend><?php echo get_option('easy_t_position_web_other_field_label', 'Position / Web Address / Other'); ?> Field</legend>			
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="easy_t_position_web_other_field_label">Label</label></th>
					<td><input type="text" name="easy_t_position_web_other_field_label" id="easy_t_position_web_other_field_label" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?> value="<?php echo get_option('easy_t_position_web_other_field_label', 'Position / Web Address / Other'); ?>" />
					<p class="description">This is the label of the <?php echo get_option('easy_t_position_web_other_field_label', 'Position / Web Address / Other'); ?> field in the form, which defaults to "Position / Web Address / Other".  Contents of this field will be passed through to the Position / Web Address / Other field inside WordPress.</p>
					</td>
				</tr>
			</table>
						
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="easy_t_position_web_other_field_description">Description</label></th>
					<td><textarea name="easy_t_position_web_other_field_description" id="easy_t_position_web_other_field_description" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?>><?php echo get_option('easy_t_position_web_other_field_description', 'Please enter your Job Title or Website address.'); ?></textarea>
					<p class="description">This is the description below the <?php echo get_option('easy_t_position_web_other_field_label', 'Position / Web Address / Other'); ?> field in the form.</p>
					</td>
				</tr>
			</table>
			
			<table class="form-table">
				<tr valign="top">
					<td><input type="checkbox" name="easy_t_hide_position_web_other_field" id="easy_t_hide_position_web_other_field" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?> value="1" <?php if(get_option('easy_t_hide_position_web_other_field')){ ?> checked="CHECKED" <?php } ?>/>
					<label for="easy_t_hide_position_web_other_field">Disable Display</label>
					<p class="description">If checked, the <?php echo get_option('easy_t_position_web_other_field_label', 'Position / Web Address / Other'); ?> field in the form will not be displayed.</p>
					</td>
				</tr>
			</table>
		</fieldset>
		
		<fieldset>
			<legend><?php echo get_option('easy_t_other_other_field_label', 'Location Reviewed / Product Reviewed / Item Reviewed'); ?> Field</legend>			
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="easy_t_other_other_field_label">Label</label></th>
					<td><input type="text" name="easy_t_other_other_field_label" id="easy_t_other_other_field_label" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?> value="<?php echo get_option('easy_t_other_other_field_label', 'Location Reviewed / Product Reviewed / Item Reviewed'); ?>" />
					<p class="description">This is the label of the <?php echo get_option('easy_t_other_other_field_label', 'Location Reviewed / Product Reviewed / Item Reviewed'); ?> field in the form, which defaults to "Location Reviewed / Product Reviewed / Item Reviewed".  Contents of this field will be passed through to the Location Reviewed / Product Reviewed / Item Reviewed field inside WordPress.</p>
					</td>
				</tr>
			</table>
						
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="easy_t_other_other_field_description">Description</label></th>
					<td><textarea name="easy_t_other_other_field_description" id="easy_t_other_other_field_description" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?>><?php echo get_option('easy_t_other_other_field_description', 'Please enter the Location or the Product being Reviewed.'); ?></textarea>
					<p class="description">This is the description below the <?php echo get_option('easy_t_other_other_field_label', 'Location Reviewed / Product Reviewed / Item Reviewed'); ?> field in the form.</p>
					</td>
				</tr>
			</table>
			
			<table class="form-table">
				<tr valign="top">
					<td><input type="checkbox" name="easy_t_hide_other_other_field" id="easy_t_hide_other_other_field" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?> value="1" <?php if(get_option('easy_t_hide_other_other_field')){ ?> checked="CHECKED" <?php } ?>/>
					<label for="easy_t_hide_other_other_field">Disable Display</label>
					<p class="description">If checked, the <?php echo get_option('easy_t_other_other_field_label', 'Location Reviewed / Product Reviewed / Item Reviewed'); ?> field in the form will not be displayed.</p>
					</td>
				</tr>
			</table>
		</fieldset>
		
		<fieldset>
			<legend><?php echo get_option('easy_t_category_field_label', 'Category'); ?> Field</legend>			
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="easy_t_category_field_label">Label</label></th>
					<td><input type="text" name="easy_t_category_field_label" id="easy_t_category_field_label" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?> value="<?php echo get_option('easy_t_category_field_label', 'Category'); ?>" />
					<p class="description">This is the label of the <?php echo get_option('easy_t_category_field_label', 'Category'); ?> field in the form, which defaults to "Category".  This field matches the Testimonial Categories inside WordPress.</p>
					</td>
				</tr>
			</table>
						
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="easy_t_category_field_description">Description</label></th>
					<td><textarea name="easy_t_category_field_description" id="easy_t_category_field_description" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?>><?php echo get_option('easy_t_category_field_description', 'Please select the Category that best matches your Testimonial.'); ?></textarea>
					<p class="description">This is the description below the <?php echo get_option('easy_t_category_field_label', 'Category'); ?> field in the form, a Select menu.</p>
					</td>
				</tr>
			</table>
			
			<table class="form-table">
				<tr valign="top">
					<td><input type="checkbox" name="easy_t_hide_category_field" id="easy_t_hide_category_field" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?> value="1" <?php if(get_option('easy_t_hide_category_field')){ ?> checked="CHECKED" <?php } ?>/>
					<label for="easy_t_hide_category_field">Disable Display</label>
					<p class="description">If checked, the <?php echo get_option('easy_t_category_field_label', 'Category'); ?> field in the form will not be displayed.</p>
					</td>
				</tr>
			</table>
		</fieldset>
		
		<fieldset>
			<legend><?php echo get_option('easy_t_rating_field_label', 'Your Rating'); ?> Field</legend>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="easy_t_rating_field_label">Label</label></th>
					<td><input type="text" name="easy_t_rating_field_label" id="easy_t_rating_field_label" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?> value="<?php echo get_option('easy_t_rating_field_label', 'Your Rating'); ?>" />
					<p class="description">This is the label of the <?php echo get_option('easy_t_rating_field_label', 'Your Rating'); ?> Field in the form, which defaults to "Your Rating".</p>
					</td>
				</tr>
			</table>
						
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="easy_t_rating_field_description">Description</label></th>
					<td><textarea name="easy_t_rating_field_description" id="easy_t_rating_field_description" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?>><?php echo get_option('easy_t_rating_field_description', '1 - 5 out of 5, where 5/5 is the best and 1/5 is the worst.'); ?></textarea>
					<p class="description">This is the description below the <?php echo get_option('easy_t_rating_field_label', 'Your Rating'); ?> Field in the form.</p>
					</td>
				</tr>
			</table>
						
			<table class="form-table">
				<tr valign="top">
					<td><input type="checkbox" name="easy_t_use_rating_field" id="easy_t_use_rating_field" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?> value="1" <?php if(get_option('easy_t_use_rating_field')){ ?> checked="CHECKED" <?php } ?>/>
					<label for="easy_t_use_rating_field">Enable Ratings</label>
					<p class="description">If checked, users will be allowed to add a 1 - 5 out of 5 rating along with their Testimonial.</p>
					</td>
				</tr>
			</table>
		</fieldset>
		
		<fieldset>
			<legend><?php echo get_option('easy_t_body_content_field_label', 'Your Testimonial'); ?> Field</legend>			
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="easy_t_body_content_field_label">Label</label></th>
					<td><input type="text" name="easy_t_body_content_field_label" id="easy_t_body_content_field_label" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?> value="<?php echo get_option('easy_t_body_content_field_label', 'Your Testimonial'); ?>" />
					<p class="description">This is the label of the <?php echo get_option('easy_t_body_content_field_label', 'Your Testimonial'); ?> field in the form, a textarea, which defaults to "Your Testimonial".  Contents of this field will be passed through to the Body field inside WordPress.</p>
					</td>
				</tr>
			</table>
						
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="easy_t_body_content_field_description">Description</label></th>
					<td><textarea name="easy_t_body_content_field_description" id="easy_t_body_content_field_description" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?>><?php echo get_option('easy_t_body_content_field_description', 'Please enter your Testimonial.'); ?></textarea>
					<p class="description">This is the description below the <?php echo get_option('easy_t_body_content_field_label', 'Your Testimonial'); ?> field in the form, a textarea.</p>
					</td>
				</tr>
			</table>
		</fieldset>
		
		<fieldset>
			<legend><?php echo get_option('easy_t_image_field_label', 'Your Image'); ?> Field</legend>
		
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="easy_t_image_field_label">Label</label></th>
					<td><input type="text" name="easy_t_image_field_label" id="easy_t_image_field_label" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?> value="<?php echo get_option('easy_t_image_field_label', 'Your Image'); ?>" />
					<p class="description">This is the label of the <?php echo get_option('easy_t_image_field_label', 'Your Image'); ?> Field in the form, which defaults to "Your Image".</p>
					</td>
				</tr>
			</table>
						
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="easy_t_image_field_description">Description</label></th>
					<td><textarea name="easy_t_image_field_description" id="easy_t_image_field_description" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?>><?php echo get_option('easy_t_image_field_description', 'You can select and upload 1 image along with your Testimonial.  Depending on the website\'s settings, this image may be cropped or resized.  Allowed file types are .gif, .jpg, .png, and .jpeg.'); ?></textarea>
					<p class="description">This is the description below the <?php echo get_option('easy_t_image_field_label', 'Your Image'); ?> Field in the form.</p>
					</td>
				</tr>
			</table>
						
			<table class="form-table">
				<tr valign="top">
					<td><input type="checkbox" name="easy_t_use_image_field" id="easy_t_use_image_field" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?> value="1" <?php if(get_option('easy_t_use_image_field')){ ?> checked="CHECKED" <?php } ?>/>
					<label for="easy_t_use_image_field">Enable Image Field</label>
					<p class="description">If checked, users will be allowed to upload 1 image along with their Testimonial.</p>
					</td>
				</tr>
			</table>
		</fieldset>		
				
		<fieldset>
			<legend><?php echo get_option('easy_t_submit_button_label', 'Submit Testimonial'); ?> Field</legend>			
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="easy_t_submit_button_label">Submit Button Label</label></th>
					<td><input type="text" name="easy_t_submit_button_label" id="easy_t_submit_button_label" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?> value="<?php echo get_option('easy_t_submit_button_label', 'Submit Testimonial'); ?>" />
					<p class="description">This is the label of the submit button at the bottom of the form.</p>
					</td>
				</tr>
			</table>
		</fieldset>
	<?php 
	}
	
	function output_author_settings(){
		?>
		<h3 id="submission-authoring-options">Submission Authoring Options</h3>
					
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="easy_t_testimonial_author">Testimonial Author</label></th>
				<td>
					<?php
						$current_author = get_option('easy_t_testimonial_author', 1);
						
						$author_dropdown_atts = array(
							'name' => 'easy_t_testimonial_author',
							'id' => 'easy_t_testimonial_author',
							'selected' => $current_author
						);
						
						wp_dropdown_users($author_dropdown_atts);
					?>
					<p class="description">Select a desired WordPress user to have Testimonials authored as.  This defaults to the site administrator.</p>
				</td>
			</tr>
		</table>
		<?php
	}
	
	function output_notification_settings(){
		?>
		<h3 id="submission-notification-options">Notification Options</h3>
						
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="easy_t_submit_success_message">Submission Success Message</label></th>
				<td><textarea name="easy_t_submit_success_message" id="easy_t_submit_success_message" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?>><?php echo get_option('easy_t_submit_success_message', 'Thank You For Your Submission!'); ?></textarea>
				<p class="description">This is the text that appears after a successful submission.</p>
				</td>
			</tr>
		</table>
					
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="easy_t_submit_success_redirect_url">Submission Success Redirect URL</label></th>
				<td><input type="text" name="easy_t_submit_success_redirect_url" id="easy_t_submit_success_redirect_url" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?> value="<?php echo get_option('easy_t_submit_success_redirect_url', ''); ?>"/>
				<p class="description">If you want the user to be taken to a specific URL on your site after submitting their Testimonial, enter it into this field.  If the field is empty, they will stay on the same page and see the Success Message, instead.</p>
				</td>
			</tr>
		</table>
		
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="easy_t_submit_notification_address">Submission Success Notification E-Mail Address</label></th>
				<td><input type="text" name="easy_t_submit_notification_address" id="easy_t_submit_notification_address" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?> value="<?php echo get_option('easy_t_submit_notification_address'); ?>" />
				<p class="description">If set, we will attempt to send an e-mail notification to this address upon a succesfull submission.  If not set, submission notifications will be sent to the site's Admin E-mail address.  You can include multiple, comma-separated e-mail addresses here.</p>
				</td>
			</tr>
		</table>
		
		<table class="form-table">
			<tr valign="top">
				<td><input type="checkbox" name="easy_t_submit_notification_include_testimonial" id="easy_t_submit_notification_include_testimonial" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?> value="1" <?php if(get_option('easy_t_submit_notification_include_testimonial')){ ?> checked="CHECKED" <?php } ?>/>
				<label for="easy_t_submit_notification_include_testimonial">Include Testimonial In Notification E-mail</label>
				<p class="description">If checked, the notification e-mail will include the submitted Testimonial.</p>
				</td>
			</tr>
		</table>
		<?php
	}
	
	function output_error_settings(){
		?>
		<h3 id="error-messages">Error Messages</h3>
		
		<fieldset>
			<legend>Error Messages</legend>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="easy_t_general_error">General Testimonial Submission Error</label></th>
					<td><textarea name="easy_t_general_error" id="easy_t_general_error" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?>><?php echo get_option('easy_t_general_error', 'There was an error with your submission.  Please check the fields and try again.'); ?></textarea>
					<p class="description">This is the general error message displayed on the submission form when there is an error processing the submission.  This will be accompanied by more specific error messages about what went wrong.</p>
					</td>
				</tr>
			</table>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="easy_t_title_field_error">Title Field Error</label></th>
					<td><textarea name="easy_t_title_field_error" id="easy_t_title_field_error" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?>><?php echo get_option('easy_t_title_field_error', 'Please give ' . strtolower(get_option('easy_t_body_content_field_label','your testimonial')) . ' a ' . strtolower(get_option('easy_t_title_field_label','title')) . '.'); ?></textarea>
					<p class="description">This is the error message displayed when a user doesn't give their Testimonial a Title.</p>
					</td>
				</tr>
			</table>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="easy_t_body_field_error">Body Field Error</label></th>
					<td><textarea name="easy_t_body_field_error" id="easy_t_body_field_error" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?>><?php echo get_option('easy_t_body_field_error', 'Please enter ' . strtolower(get_option('easy_t_body_content_field_label','your testimonial')) . '.'); ?></textarea>
					<p class="description">This is the error message displayed when a Testimonial is not entered into the Body field.</p>
					</td>
				</tr>
			</table>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="easy_t_captcha_field_error">Captcha Field Error</label></th>
					<td><textarea name="easy_t_captcha_field_error" id="easy_t_captcha_field_error" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?>><?php echo get_option('easy_t_captcha_field_error', 'Captcha did not match.'); ?></textarea>
					<p class="description">This is the error message displayed when the Captcha test was not passed.</p>
					</td>
				</tr>
			</table>
		</fieldset>
		<?php
	}
	
	function output_spam_settings(){
		?>
		<h3 id="spam-prevention-captcha">Spam Prevention</h3>
					
		<table class="form-table">
			<tr valign="top">
				<td>
					<input type="checkbox" name="easy_t_use_captcha" id="easy_t_use_captcha" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?> value="1" <?php if(get_option('easy_t_use_captcha')){ ?> checked="CHECKED" <?php } ?>/>
					<label for="easy_t_use_captcha">Enable Captcha on Submission Form</label>
					<p class="description">This is useful if you are having SPAM problems. Requires a valid reCAPTCHA API Key and Secret Key to be entered above, or a compatible Captcha plugin to be installed (such as <a href="https://wordpress.org/plugins/really-simple-captcha/" target="_blank">Really Simple Captcha</a>). </p>
				</td>
			</tr>
		</table>
		
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="easy_t_captcha_field_label">"Captcha" Field Label</label></th>
				<td><input type="text" name="easy_t_captcha_field_label" id="easy_t_captcha_field_label" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?> value="<?php echo get_option('easy_t_captcha_field_label', 'Captcha'); ?>" />
				<p class="description">This is the label of the Capthca field in the form, which defaults to "Captcha".  Contents of this field will be passed through to the Captcha function inside WordPress.</p>
				</td>
			</tr>
		</table>
					
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="easy_t_captcha_field_description">"Captcha" Field Description</label></th>
				<td><textarea name="easy_t_captcha_field_description" id="easy_t_captcha_field_description" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?>><?php echo get_option('easy_t_captcha_field_description', 'Please enter the text that you see above here.'); ?></textarea>
				<p class="description">This is the description below the Captcha field in the form.</p>
				</td>
			</tr>
		</table>

		<?php
			$recaptcha_portal_link = sprintf('(<a href="%s">%s</a>)', 'https://www.google.com/recaptcha/admin', 'Get Yours Here');
		?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="easy_t_recaptcha_api_key">reCAPTCHA API Key</label></th>
				<td>
					<input type="text" name="easy_t_recaptcha_api_key" id="easy_t_recaptcha_api_key" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?> value="<?php echo get_option('easy_t_recaptcha_api_key', ''); ?>" />
					<p class="description">To use Google's reCAPTCHA service, please enter your <strong>reCAPTCHA API Key</strong> here. <?php echo $recaptcha_portal_link; ?></p>
				</td>
			</tr>
		</table>

		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="easy_t_recaptcha_secret_key">reCAPTCHA Secret Key</label></th>
				<td>
					<input type="text" name="easy_t_recaptcha_secret_key" id="easy_t_recaptcha_secret_key" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?> value="<?php echo get_option('easy_t_recaptcha_secret_key', ''); ?>" />
					<p class="description">To use Google's reCAPTCHA service, please enter your <strong>reCAPTCHA Secret Key</strong> here. <?php echo $recaptcha_portal_link; ?></p>
				</td>
			</tr>
		</table>

		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="easy_t_recaptcha_lang">reCAPTCHA Language</label></th>
				<td>
					<select name="easy_t_recaptcha_lang" id="easy_t_recaptcha_lang" <?php if(!$this->config->is_pro): ?>disabled="disabled"<?php endif; ?>>
						<option value="">(Not Specified)</option>
						<?php
						$current_lang = get_option('easy_t_recaptcha_lang', '');
						foreach ($this->get_recaptcha_languages() as $label => $val) {
							$sel_attr = (strcmp($current_lang, $val) == 0 ? 'selected="selected"' : '');
							printf( '<option value="%s" %s>%s</option>', htmlentities($val), $sel_attr, htmlentities($label) );
						} ?>
					</select>						
				</td>
			</tr>
		</table>
		<?php
	}
}