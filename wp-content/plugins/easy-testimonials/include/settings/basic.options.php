<?php
class easyTestimonialBasicOptions extends easyTestimonialOptions{
	var $tabs;
	var $config;
	
	function __construct($config){			
		//call register settings function
		add_action( 'admin_init', array($this, 'register_settings'));	
		
		//assign config
		$this->config = $config;
			
		//if the flush cache now button has been clicked
		if (isset($_GET['flush-cache-now']) && $_GET['flush-cache-now'] == 'true'){
			//go ahead and add the testimonials, too
			add_action('admin_init', array($this, 'easy_t_clear_cache') );
		}
	}
	
	function register_settings(){		
		//register our settings
		
		/* Basic options */
		register_setting( 'easy-testimonials-settings-group', 'easy_t_custom_css' );
		register_setting( 'easy-testimonials-settings-group', 'easy_t_disable_cycle2' );
		register_setting( 'easy-testimonials-settings-group', 'easy_t_use_cycle_fix' );
		register_setting( 'easy-testimonials-settings-group', 'easy_t_apply_content_filter' );
		register_setting( 'easy-testimonials-settings-group', 'easy_t_avada_filter_override' );
		register_setting( 'easy-testimonials-settings-group', 'easy_t_show_in_search' );
		register_setting( 'easy-testimonials-settings-group', 'easy_t_cache_buster', array($this, 'easy_t_bust_options_cache') );
		register_setting( 'easy-testimonials-settings-group', 'easy_t_allow_tags' );
		
		/* Item Reviewed */
		register_setting( 'easy-testimonials-settings-group', 'easy_t_use_global_item_reviewed' );
		register_setting( 'easy-testimonials-settings-group', 'easy_t_global_item_reviewed' );
		
		/* Shortcodes */
		register_setting( 'easy-testimonials-settings-group', 'ezt_testimonials_shortcode' );
		register_setting( 'easy-testimonials-settings-group', 'ezt_single_testimonial_shortcode' );
		register_setting( 'easy-testimonials-settings-group', 'ezt_submit_testimonial_shortcode' );
		register_setting( 'easy-testimonials-settings-group', 'ezt_cycle_testimonial_shortcode' );
		register_setting( 'easy-testimonials-settings-group', 'ezt_random_testimonial_shortcode' );
		register_setting( 'easy-testimonials-settings-group', 'ezt_testimonials_count_shortcode' );
		register_setting( 'easy-testimonials-settings-group', 'ezt_testimonials_grid_shortcode' );
		
		/* Pro registration */
		register_setting( 'easy-testimonials-settings-group', 'easy_t_registered_name' );
		register_setting( 'easy-testimonials-settings-group', 'easy_t_registered_url' );
		register_setting( 'easy-testimonials-settings-group', 'easy_t_registered_key' );
		
		/* Cache */
		register_setting( 'easy-testimonials-settings-group', 'easy_t_cache_time' );
		register_setting( 'easy-testimonials-settings-group', 'easy_t_cache_enabled' );
		
		/* Review Markup */		
		register_setting( 'easy-testimonials-settings-group', 'easy_t_output_schema_markup' );
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
			'header_label' => 'Basic Settings',
			'settings_field_key' => 'easy-testimonials-settings-group', // can be an array			
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
			'basic_options', // section id, used in url fragment
			'Basic Options', // section label
			array($this, 'output_basic_options'), // display callback
			array(
				'class' => 'extra_li_class', // extra classes to add sidebar menu <li> and tab wrapper <div>
				'icon' => 'gear' // icons here: http://fontawesome.io/icons/
			)
		);
		$this->tabs->add_tab(
			'compatibility_options', // section id, used in url fragment
			'Compatibility Options', // section label
			array($this, 'output_compatibility_options'), // display callback
			array(
				'class' => 'extra_li_class', // extra classes to add sidebar menu <li> and tab wrapper <div>
				'icon' => 'check-square-o' // icons here: http://fontawesome.io/icons/
			)
		);
		$this->tabs->add_tab(
			'itemreviewed_options', // section id, used in url fragment
			'Item Reviewed Options', // section label
			array($this, 'output_itemreviewed_options'), // display callback
			array(
				'class' => 'extra_li_class', // extra classes to add sidebar menu <li> and tab wrapper <div>
				'icon' => 'tag' // icons here: http://fontawesome.io/icons/
			)
		);
		$this->tabs->add_tab(
			'shortcode_options', // section id, used in url fragment
			'Shortcode Options', // section label
			array($this, 'output_shortcode_options'), // display callback
			array(
				'class' => 'extra_li_class', // extra classes to add sidebar menu <li> and tab wrapper <div>
				'icon' => 'code' // icons here: http://fontawesome.io/icons/
			)
		);
		$this->tabs->add_tab(
			'cache_options', // section id, used in url fragment
			'Cache Options', // section label
			array($this, 'output_cache_options'), // display callback
			array(
				'class' => 'extra_li_class', // extra classes to add sidebar menu <li> and tab wrapper <div>
				'icon' => 'rocket' // icons here: http://fontawesome.io/icons/
			)
		);
		$this->tabs->add_tab(
			'registration_options', // section id, used in url fragment
			'Registration Options', // section label
			array($this, 'output_registration_options'), // display callback
			array(
				'class' => 'extra_li_class', // extra classes to add sidebar menu <li> and tab wrapper <div>
				'icon' => 'key' // icons here: http://fontawesome.io/icons/
			)
		);
		
		$this->tabs->display();
	}
	
	function output_basic_options(){
		?>
		<h3>Basic Options</h3>
			
		<p>Use the below options to control various bits of output.</p>
		
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="easy_t_custom_css">Custom CSS</a></th>
				<td><textarea name="easy_t_custom_css" id="easy_t_custom_css"><?php echo get_option('easy_t_custom_css', ''); ?></textarea>
				<p class="description">Input any Custom CSS you want to use here.  The plugin will work without you placing anything here - this is useful in case you need to edit any styles for it to work with your theme, though.<br/> For a list of available classes, click <a href="https://goldplugins.com/documentation/easy-testimonials-documentation/html-css-information-for-easy-testimonials/" target="_blank">here</a>.</p></td>
			</tr>
		</table>
		
		<table class="form-table">
			<tr valign="top">
				<td><input type="checkbox" name="easy_t_show_in_search" id="easy_t_show_in_search" value="1" <?php if(get_option('easy_t_show_in_search', true)){ ?> checked="CHECKED" <?php } ?>/>
				<label for="easy_t_show_in_search">Show in Search</label>
				<p class="description">If checked, we will Show your Testimonials in the public site search in WordPress.</p>
				</td>
			</tr>
		</table>
		
		<table class="form-table">
			<tr valign="top">
				<td><input type="checkbox" name="easy_t_allow_tags" id="easy_t_allow_tags" value="1" <?php if(get_option('
		easy_t_allow_tags', true)){ ?> checked="CHECKED" <?php } ?>/>
				<label for="easy_t_allow_tags">Allow HTML Tags in Testimonials</label>				
				<p class="description">If checked, HTML tags will be rendered inside testimonials.  If unchecked, HTML will be stripped from output.</p>
				</td>
			</tr>
		</table>
		<?php
	}
	
	function output_compatibility_options(){
		?>
		<h3 id="compatibility_options">Compatibility Options</h3>
		<p class="description">Use these fields to troubleshoot suspected compatibility issues with your Theme or other Plugins.</p>
		<table class="form-table">
			<tr valign="top">
				<td><input type="checkbox" name="easy_t_disable_cycle2" id="easy_t_disable_cycle2" value="1" <?php if(get_option('easy_t_disable_cycle2', false)){ ?> checked="CHECKED" <?php } ?>/>
				<label for="easy_t_disable_cycle2">Disable Cycle2 Output</label>
				<p class="description">If checked, we won't include the Cycle2 JavaScript file.  If you suspect you are having JavaScript compatibility issues with our plugin, please try checking this box.</p>
				</td>
			</tr>
		</table>
		
		<table class="form-table">
			<tr valign="top">
				<td><input type="checkbox" name="easy_t_use_cycle_fix" id="easy_t_use_cycle_fix" value="1" <?php if(get_option('easy_t_use_cycle_fix', false)){ ?> checked="CHECKED" <?php } ?>/>
				<label for="easy_t_use_cycle_fix">Use Cycle Fix</label>				
				<p class="description">If checked, we will try and trigger Cycle2 a different way.  If you suspect you are having JavaScript compatibility issues with our plugin, please try checking this box.  NOTE: If you have Disable Cycle2 Output checked, this box will have no effect.</p>
				</td>
			</tr>
		</table>
		
		<table class="form-table">
			<tr valign="top">
				<td><input type="checkbox" name="easy_t_apply_content_filter" id="easy_t_apply_content_filter" value="1" <?php if(get_option('easy_t_apply_content_filter', true)){ ?> checked="CHECKED" <?php } ?>/>
				<label for="easy_t_apply_content_filter">Apply The Content Filter</label>
				<p class="description">If checked, we will apply the content filter to Testimonial content.  Use this if you are experiencing problems with other plugins applying their shortcodes, etc, to your Testimonial content.</p>
				</td>
			</tr>
		</table>
		
		<?php
			/* Avada Check */
			$my_theme = wp_get_theme();
			$additional_message = "";
			if( strpos( $my_theme->get('Name'), "Avada" ) === 0 ) {
				// looks like we are using Avada! 
				// make sure we have avada compatibility enabled. If not, show a warning!
				if(!get_option('easy_t_avada_filter_override', false)){
					$additional_classes = "has_avada";
					$additional_message = "We have detected that you are using the Avada theme.  Please enable this option to ensure compatibility.";
				}
			}
		?>
		
		<table class="form-table <?php echo $additional_classes; ?>">
			<tr valign="top">
				<td><input type="checkbox" name="easy_t_avada_filter_override" id="easy_t_avada_filter_override" value="1" <?php if(get_option('easy_t_avada_filter_override', false)){ ?> checked="CHECKED" <?php } ?>/>
				<?php if(strlen($additional_message)>0){ echo "<p class='error'><strong>$additional_message</strong></p>";}?>
				<label for="easy_t_avada_filter_override">Override Avada Blog Post Content Filter on Testimonials</label>
				<p class="description">If checked, we will attempt to prevent the Avada blog layouts from overriding our Testimonial themes.  If you are having issues getting your themes to display when viewing Testimonial Categories in the Avada theme, try toggling this option.</p>
				</td>
			</tr>
		</table>
		<?php
	}
	
	function output_itemreviewed_options(){
		?>
		<h3>Item Reviewed Options</h3>		
		<table class="form-table">
			<tr valign="top">				
				<td><input type="checkbox" name="easy_t_output_schema_markup" id="easy_t_output_schema_markup" value="1" <?php if(get_option('
		easy_t_output_schema_markup', true)){ ?> checked="CHECKED" <?php } ?>/>
				<label for="easy_t_output_schema_markup">Output Review Markup</label>
				<p class="description">If checked, Schema.org review markup will be output using <a href="http://json-ld.org" target="_blank">JSON-LD</a>. This will allow search engines like Google and Bing crawl your data, improving your website's SEO.</p>
				</td>
			</tr>
		</table>
		
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="easy_t_global_item_reviewed">Global Item Reviewed</label></th>
				<td><input type="text" name="easy_t_global_item_reviewed" id="easy_t_global_item_reviewed" value="<?php echo get_option('easy_t_global_item_reviewed', ''); ?>" />
				<p class="description">If nothing is set on the individual Testimonial, this will be used as the itemReviewed value for the Testimonial.  This is so people, and Search Engines, know what your Testimonials are all about!</p>
				</td>
			</tr>
			<tr valign="top">
				<td><input type="checkbox" name="easy_t_use_global_item_reviewed" id="easy_t_use_global_item_reviewed" value="1" <?php if(get_option('easy_t_use_global_item_reviewed', false)){ ?> checked="CHECKED" <?php } ?>/>
				<label for="easy_t_use_global_item_reviewed">Use Global Item Reviewed</label>
				<p class="description">If checked, and an individual Testimonial does not have a value for the Item being Reviewed, we will use the Global Item Reviewed setting instead.</p>
				</td>
			</tr>
		</table>
		<?php
	}
	
	function output_shortcode_options(){
		?>
			<h3>Shortcode Options</h3>
			<p class="description">Use these fields to control our registered shortcodes.  If you are experiencing issues where our shortcodes do not display at all, you can try changing them here.</p>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="ezt_random_testimonial_shortcode">Random Testimonial Shortcode</label></th>
					<td><input type="text" name="ezt_random_testimonial_shortcode" id="ezt_random_testimonial_shortcode" value="<?php echo get_option('ezt_random_testimonial_shortcode', 'random_testimonial'); ?>" />
					<p class="description">This is the shortcode for displaying random testimonials.  If you suspect you are having compatibility issues with shortcodes already registered by your theme or other plugins, try changing this value and any corresponding shortcodes you are using on your site.</p>
					</td>
				</tr>
			</table>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="ezt_single_testimonial_shortcode">Single Testimonial Shortcode</label></th>
					<td><input type="text" name="ezt_single_testimonial_shortcode" id="ezt_single_testimonial_shortcode" value="<?php echo get_option('ezt_single_testimonial_shortcode', 'single_testimonial'); ?>" />
					<p class="description">This is the shortcode for displaying a single testimonial.  If you suspect you are having compatibility issues with shortcodes already registered by your theme or other plugins, try changing this value and any corresponding shortcodes you are using on your site.</p>
					</td>
				</tr>
			</table>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="ezt_testimonials_shortcode">Testimonials List Shortcode</label></th>
					<td><input type="text" name="ezt_testimonials_shortcode" id="ezt_testimonials_shortcode" value="<?php echo get_option('ezt_testimonials_shortcode', 'testimonials'); ?>" />
					<p class="description">This is the shortcode for displaying a list of testimonials.  If you suspect you are having compatibility issues with shortcodes already registered by your theme or other plugins, try changing this value and any corresponding shortcodes you are using on your site.</p>
					</td>
				</tr>
			</table>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="ezt_cycle_testimonial_shortcode">Testimonials Cycle Shortcode</label></th>
					<td><input type="text" name="ezt_cycle_testimonial_shortcode" id="ezt_cycle_testimonial_shortcode" value="<?php echo get_option('ezt_cycle_testimonial_shortcode', 'testimonials_cycle'); ?>" />
					<p class="description">This is the shortcode for displaying cycled testimonials.  If you suspect you are having compatibility issues with shortcodes already registered by your theme or other plugins, try changing this value and any corresponding shortcodes you are using on your site.</p>
					</td>
				</tr>
			</table>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="ezt_submit_testimonial_shortcode">Testimonial Submission Form Shortcode</label></th>
					<td><input type="text" name="ezt_submit_testimonial_shortcode" id="ezt_submit_testimonial_shortcode" value="<?php echo get_option('ezt_submit_testimonial_shortcode', 'submit_testimonial'); ?>" />
					<p class="description">This is the shortcode for displaying the testimonial submission form.  If you suspect you are having compatibility issues with shortcodes already registered by your theme or other plugins, try changing this value and any corresponding shortcodes you are using on your site.</p>
					</td>
				</tr>
			</table>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="ezt_testimonials_count_shortcode">Testimonials Count Shortcode</label></th>
					<td><input type="text" name="ezt_testimonials_count_shortcode" id="ezt_testimonials_count_shortcode" value="<?php echo get_option('ezt_testimonials_count_shortcode', 'testimonials_count'); ?>" />
					<p class="description">This is the shortcode for displaying the count of Testimonials.  If you suspect you are having compatibility issues with shortcodes already registered by your theme or other plugins, try changing this value and any corresponding shortcodes you are using on your site.</p>
					</td>
				</tr>
			</table>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="ezt_testimonials_grid_shortcode">Testimonials Grid Shortcode</label></th>
					<td><input type="text" name="ezt_testimonials_grid_shortcode" id="ezt_testimonials_grid_shortcode" value="<?php echo get_option('ezt_testimonials_grid_shortcode', 'testimonials_grid'); ?>" />
					<p class="description">This is the shortcode for displaying the grid of Testimonials.  If you suspect you are having compatibility issues with shortcodes already registered by your theme or other plugins, try changing this value and any corresponding shortcodes you are using on your site.</p>
					</td>
				</tr>
			</table>
		<?php
	}
	
	function output_registration_options(){
		?>
		<h3>Pro Registration</h3>			
		<?php if($this->config->is_pro): ?>	
		<p class="easy_testimonials_registered">✓ Easy Testimonials Pro is registered and activated. Thank you!</p>
		<?php else: ?>
		<p class="easy_testimonials_not_registered">Easy Testimonials Pro is not activated. You will not be able to use the Pro features until you activate the plugin. <br /><br /><a class="button" href="https://goldplugins.com/our-plugins/easy-testimonials-details/upgrade-to-easy-testimonials-pro/?utm_campaign=registration&utm_source=easy_testimonials_settings" target="_blank">Click Here To Upgrade To Pro</a> <br /> <br /><em>When you upgrade, you'll unlock powerful new features including over 75 professionally designed themes, advanced styling options, and a Testimonial Submission form.</em></p>
		<?php endif; ?>	

		<?php if(!$this->config->is_pro): ?><p>If you have purchased Easy Testimonials Pro, please complete the following fields to activate additional features such as Front-End Testimonial Submission.</p><?php endif; ?>

		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="easy_t_registered_name">Email Address</label></th>
				<td><input type="text" name="easy_t_registered_name" id="easy_t_registered_name" value="<?php echo get_option('easy_t_registered_name'); ?>" />
				<p class="description">This is the e-mail address that you used when you registered the plugin.</p>
				</td>
			</tr>
		</table>
			
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="easy_t_registered_key">API Key</label></th>
				<td><input type="password" name="easy_t_registered_key" id="easy_t_registered_key" value="<?php echo get_option('easy_t_registered_key'); ?>" autocomplete="off" />
				<p class="description">This is the API Key that you received after registering the plugin.</p>
				</td>
			</tr>
		</table>
		<?php
	}
	
	function output_cache_options(){
		?>
		<h3>Cache Options</h3>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="easy_t_cache_time">Cache Time</label></th>
				<td><input type="text" name="easy_t_cache_time" id="easy_t_cache_time" value="<?php echo get_option('easy_t_cache_time', 900); ?>" />
				<p class="description">The time, in seconds, to keep items in the cache. The default value is 15 minutes (900 seconds.)</p>
				</td>
			</tr>
			<tr valign="top">
				<td><input type="checkbox" name="easy_t_cache_enabled" id="easy_t_cache_enabled" value="1" <?php if(get_option('easy_t_cache_enabled', true)){ ?> checked="CHECKED" <?php } ?>/>
				<label for="easy_t_cache_enabled">Use Caching</label>
				<p class="description">To disable caching, uncheck this option.  This is good for in development websites.</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="easy_t_cache_enabled">Flush Cache Now</label></th>
				<td>			
					<p class="submit">
						<a href="?page=easy-testimonials-settings&flush-cache-now=true" class="button-primary" title="<?php _e('Click to Flush Cache Now', 'easy-testimonials') ?>"><?php _e('Click to Flush Cache Now', 'easy-testimonials') ?></a>
					</p>
				</td>
			</tr>
		</table>	
		<?php
	}
}