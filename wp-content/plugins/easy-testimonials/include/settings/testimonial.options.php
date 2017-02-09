<?php
/*
This file is part of Easy Testimonials.

Easy Testimonials is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Easy Testimonials is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with The Easy Testimonials.  If not, see <http://www.gnu.org/licenses/>.
*/
require_once('basic.options.php');
require_once('display.options.php');
require_once('theme.options.php');
require_once('submission-form.options.php');
require_once('import-export.options.php');
require_once('shortcode-generator.options.php');

class easyTestimonialOptions
{	
	var $config;
	var $basic_settings_page;
	var $display_settings_page;
	var $theme_settings_page;
	var $submission_form_settings_page;
	var $shortcode_generator_page;
	var $import_export_settings_page;
	var $messages = array();
	
	function __construct($config){
		//load config
		$this->config = $config;
		
		//instantiate Sajak so we get our JS and CSS enqueued
		new GP_Sajak();
		
		//may be running in non WP mode (for example from a notification)
		if(function_exists('add_action')){
			//setup our classes
			$this->basic_settings_page = new easyTestimonialBasicOptions($this->config);
			$this->display_settings_page = new easyTestimonialDisplayOptions($this->config);
			$this->theme_settings_page = new easyTestimonialThemeOptions($this->config);
			$this->import_export_settings_page = new easyTestimonialImportExportOptions($this->config);
			$this->shortcode_generator_page = new easyTestimonialShortcodeGeneratorOptions($this->config);
			//TODO: move this to Pro plugin
			if($this->config->is_pro){
				$this->submission_form_settings_page = new easyTestimonialSubmissionFormOptions($this->config);
			}
			
			//add a menu item
			add_action('admin_menu', array($this, 'add_admin_menu_items'));	
			
			//call register settings function
			add_action( 'admin_init', array($this, 'register_settings'));	
		}
	}
	
	function add_admin_menu_items(){
		$title = "Easy Testimonials Settings";
		$page_title = "Easy Testimonials Settings";
		$top_level_slug = "easy-testimonials-settings";
		
		//create new top-level menu
		add_menu_page( $page_title, $title, 'administrator', $top_level_slug , array($this->basic_settings_page, 'render_settings_page') );
		
		$submenu_pages = array(
			//basic options page
			array(
				'top_level_slug' => $top_level_slug,
				'page_title' => 'Basic Settings',
				'menu_title' => 'Basic Settings',
				'role' => 'administrator',
				'slug' => $top_level_slug,
				'callback' => array($this->basic_settings_page, 'render_settings_page'),
				'hide_in_menu' => false
			),
			//display options page
			array(
				'top_level_slug' => $top_level_slug,
				'page_title' => 'Display Settings',
				'menu_title' => 'Display Settings',
				'role' => 'administrator',
				'slug' => 'easy-testimonials-display-settings',
				'callback' => array($this->display_settings_page, 'render_settings_page'),
				'hide_in_menu' => true
			),
			//theme options page
			array(
				'top_level_slug' => $top_level_slug,
				'page_title' => 'Theme Settings',
				'menu_title' => 'Theme Settings',
				'role' => 'administrator',
				'slug' => 'easy-testimonials-style-settings',
				'callback' => array($this->theme_settings_page, 'render_settings_page'),
				'hide_in_menu' => true
			),
			//shortcode generator page
			array(
				'top_level_slug' => $top_level_slug,
				'page_title' => 'Shortcode Generator',
				'menu_title' => 'Shortcode Generator',
				'role' => 'administrator',
				'slug' => 'easy-testimonials-shortcode-generator',
				'callback' => array($this->shortcode_generator_page, 'render_settings_page'),
				'hide_in_menu' => false
			),
			//import export page
			array(
				'top_level_slug' => $top_level_slug,
				'page_title' => 'Import & Export Testimonials',
				'menu_title' => 'Import & Export Testimonials',
				'role' => 'administrator',
				'slug' => 'easy-testimonials-import-export',
				'callback' => array($this->import_export_settings_page, 'render_settings_page'),
				'hide_in_menu' => false
			),
			//help and instructions page
			array(
				'top_level_slug' => $top_level_slug,
				'page_title' => 'Help & Instructions',
				'menu_title' => 'Help & Instructions',
				'role' => 'administrator',
				'slug' => 'easy-testimonials-help',//'https://goldplugins.com/documentation/easy-testimonials-documentation/?utm_src=admin_menu_item',
				'callback' => array($this, 'render_help_page'),//null,
				'hide_in_menu' => false
			),
		);
		
		$submenu_pages = apply_filters("easy_t_admin_submenu_pages", $submenu_pages);
		
		//add submenu items
		foreach ($submenu_pages as $submenu_page){
			add_submenu_page( $submenu_page['top_level_slug'], $submenu_page['page_title'], $submenu_page['menu_title'], $submenu_page['role'], $submenu_page['slug'], $submenu_page['callback'] );
		}
	}
	
	//output the help page
	function render_help_page(){		
		//load additional label string based upon pro status
		$pro_string = $this->config->is_pro ? "" : " (Pro)";
		
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
			'header_label' => 'Help &amp; Instructions',
			'settings_field_key' => 'easy-testimonials-help-settings-group', // can be an array	
			'show_save_button' => false, // hide save buttons for all panels   		
			'extra_buttons_header' => $extra_buttons, // extra header buttons
			'extra_buttons_footer' => $extra_buttons // extra footer buttons
		) );
		
		$this->settings_page_top(false);
	
		$tabs->add_tab(
			'help', // section id, used in url fragment
			'Help Center', // section label
			array($this, 'output_help_page'), // display callback
			array(
				'class' => 'extra_li_class', // extra classes to add sidebar menu <li> and tab wrapper <div>
				'icon' => 'life-buoy' // icons here: http://fontawesome.io/icons/
			)
		);
	
		$tabs->add_tab(
			'contact', // section id, used in url fragment
			'Contact Support' . $pro_string, // section label
			array($this, 'output_contact_page'), // display callback
			array(
				'class' => 'extra_li_class', // extra classes to add sidebar menu <li> and tab wrapper <div>
				'icon' => 'envelope-o' // icons here: http://fontawesome.io/icons/
			)
		);
		
		$tabs->display();
		
		$this->settings_page_bottom();
	}
	
	function output_contact_page(){
		if($this->config->is_pro){		
			//load all plugins on site
			$all_plugins = get_plugins();
			//load current theme object
			$the_theme = wp_get_theme();
			//load current easy t options
			$the_options = $this->load_all_options();
			//load wordpress area
			global $wp_version;
			
			$site_data = array(
				'plugins'	=> $all_plugins,
				'theme'		=> $the_theme,
				'wordpress'	=> $wp_version,
				'options'	=> $the_options
			);
			
			$current_user = wp_get_current_user();
			?>
			<h3>Contact Support</h3>
			<p>Would you like personalized support? Use the form below to submit a request!</p>
			<p>If you aren't able to find a helpful answer in our Help Center, go ahead and send us a support request!</p>
			<p>Please be as detailed as possible, including links to example pages with the issue present and what steps you've taken so far.  If relevant, include any shortcodes or functions you are using.</p>
			<p>Thanks!</p>
			<div class="gp_support_form_wrapper">
				<div class="gp_ajax_contact_form_message"></div>
				
				<div data-gp-ajax-form="1" data-ajax-submit="1" class="gp-ajax-form" method="post" action="https://goldplugins.com/tickets/galahad/catch.php">
					<div style="display: none;">
						<textarea name="your-details" class="gp_galahad_site_details">
							<?php
								echo htmlentities(json_encode($site_data));
							?>
						</textarea>
						
					</div>
					<div class="form_field">
						<label>Your Name (required)</label>
						<input type="text" aria-invalid="false" aria-required="true" size="40" value="<?php echo (!empty($current_user->display_name) ?  $current_user->display_name : ''); ?>" name="your_name">
					</div>
					<div class="form_field">
						<label>Your Email (required)</label>
						<input type="email" aria-invalid="false" aria-required="true" size="40" value="<?php echo (!empty($current_user->user_email) ?  $current_user->user_email : ''); ?>" name="your_email"></span>
					</div>
					<div class="form_field">
						<label>URL where problem can be seen:</label>
						<input type="text" aria-invalid="false" aria-required="false" size="40" value="" name="example_url">
					</div>
					<div class="form_field">
						<label>Your Message</label>
						<textarea aria-invalid="false" rows="10" cols="40" name="your_message"></textarea>
					</div>
					<div class="form_field">
						<input type="hidden" name="include_wp_info" value="0" />
						<label for="include_wp_info">
							<input type="checkbox" id="include_wp_info" name="include_wp_info" value="1" />Include information about my WordPress environment (server information, installed plugins, theme, and current version)
						</label>
					</div>					
					<p><em>Sending this data will allow the Gold Plugins can you help much more quickly. We strongly encourage you to include it.</em></p>
					<input type="hidden" name="registered_email" value="<?php echo htmlentities(get_option('easy_t_registered_name')); ?>" />
					<input type="hidden" name="site_url" value="<?php echo htmlentities(site_url()); ?>" />
					<input type="hidden" name="challenge" value="<?php echo substr(md5(sha1('bananaphone' . get_option('easy_t_registered_key') )), 0, 10); ?>" />
					<div class="submit_wrapper">
						<input type="submit" class="button submit" value="Send">			
					</div>
				</div>
			</div>
			<?php
		} else {
			?>
			<h3>Contact Support</h3>
			<p>Would you like personalized support? Upgrade to Pro today to receive hands on support and access to all of our Pro features!</p>
			<p><a class="button upgrade" href="https://goldplugins.com/special-offers/upgrade-to-easy-testimonials-pro/?utm_source=easy_testimonials_freep&utm_campaign=galahad_support_tab&utm_content=learn_more_button_1">Click Here To Learn More</a></p>			
			<?php
		}
	}
	
	function output_help_page(){
		?>
		<h3>Help Center</h3>
		<div class="help_box">
			<h4>Have a Question?  Check out our FAQs!</h4>
			<p>Our FAQs contain answers to our most frequently asked questions.  This is a great place to start!</p>
			<p><a class="easy_t_support_button" target="_blank" href="https://goldplugins.com/documentation/easy-testimonials-documentation/faqs/?utm_source=help_page">Click Here To Read FAQs</a></p>
		</div>
		<div class="help_box">
			<h4>Looking for Instructions? Check out our Documentation!</h4>
			<p>For a good start to finish explanation of how to add Testimonials and then display them on your site, check out our Documentation!</p>
			<p><a class="easy_t_support_button" target="_blank" href="https://goldplugins.com/documentation/easy-testimonials-documentation/?utm_source=help_page">Click Here To Read Our Docs</a></p>
		</div>
		<?php		
	}
	
	//loads all options
	//builds array of options matching our prefix
	//returns our array
	private function load_all_options(){
		$my_options = array();
		$all_options = wp_load_alloptions();
		
		$patterns = array(
			'testimonials_link',
			'testimonials_image',
			'meta_data_position',
			'ezt_(.*)',
			'testimonials_style',
			'easy_t_(.*)',
		);
		
		foreach ( $all_options as $name => $value ) {
			if ( $this->preg_match_array( $name, $patterns ) ) {
				$my_options[ $name ] = $value;
			}
		}
		
		return $my_options;
	}
	
	function preg_match_array( $candidate, $patterns )
	{
		foreach ($patterns as $pattern) {
			$p = sprintf('#%s#i', $pattern);
			if ( preg_match($p, $candidate, $matches) == 1 ) {
				return true;
			}
		}
		return false;
	}
	
	//function to produce tabs on admin screen
	function easy_t_admin_tabs($current = 'homepage' ) {
	
		$tabs = array( 	'easy-testimonials-settings' => __('Basic', 'easy-testimonials'), 
						'easy-testimonials-display-settings' => __('Display', 'easy-testimonials'),
						'easy-testimonials-style-settings' => __('Themes', 'easy-testimonials')
					);
		
		//allow additional tabs to be insterted
		$tabs = apply_filters('easy_t_admin_tabs', $tabs);
		
		echo '<div id="icon-themes" class="icon32"><br></div>';
		echo '<h2 class="nav-tab-wrapper">';
			foreach( $tabs as $tab => $name ){
				$class = ( $tab == $current ) ? ' nav-tab-active' : '';
				echo "<a class='nav-tab$class' href='?page=$tab'>$name</a>";
			}
		echo '</h2>';
	}
	
	function register_settings(){						
	}	
	
	/* Utility Functions */
	
	// don't allow captchas to be enabled unless reCAPTCHA settings are present
	// or Really Simply Captcha is installed
	function enable_captcha_callback($val)
	{
		$can_use_recaptcha = !empty($_POST['easy_t_recaptcha_api_key'])
							 && !empty($_POST['easy_t_recaptcha_secret_key']);
						
		if ( !class_exists('ReallySimpleCaptcha') && !$can_use_recaptcha ) {
			return 0;
		} else {
			return $val;
		}
	}
	
	//output top of settings page
	function settings_page_top($show_tabs = true){
		$title = "Easy Testimonials Settings";
		
		if( isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true' ){
			$this->messages[] = "Easy Testimonials Settings Updated.";
		}
		
		global $pagenow;
	?>
	<script type="text/javascript">
	jQuery(function () {
		if (typeof(gold_plugins_init_coupon_box) == 'function') {
			gold_plugins_init_coupon_box();
		}
	});
	</script>
	<?php if($this->config->is_pro): ?>	
	<div class="wrap easy_testimonials_admin_wrap">
	<?php else: ?>
	<div class="wrap easy_testimonials_admin_wrap not-pro">
	<?php endif; ?>
	<?php
		if( !empty($this->messages) ){
			foreach($this->messages as $message){
				echo '<div id="messages" class="gp_updated fade">';
				echo '<p>' . $message . '</p>';
				echo '</div>';
			}
			
			$this->messages = array();
		}
	?>
        <div id="icon-options-general" class="icon32"></div>
		<?php
		
		if($show_tabs){
			$this->get_and_output_current_tab($pagenow);
		}
	}
	
	//builds the bottom of the settings page
	//includes the signup form, if not pro
	function settings_page_bottom(){
		if(!$this->config->is_pro): ?>		
			<?php $this->output_sidebar_coupon_form(); ?>
		<?php endif; ?>
		</div>
		<?php
	}
	
	function output_sidebar_coupon_form()
	{
		$current_user = wp_get_current_user();
		?>
		<div id="signup_wrapper">
			<div class="topper purple">
				<h3><span>Upgrade To</span> Easy Testimonials Pro!</h3>
				<p class="pitch">When you upgrade, you'll instantly gain access to the Submit Testimonial Form, all slideshow transitions, over 75 professionally designed themes, Import &amp; Export functions, personalized support, and more!</p>
				<a class="upgrade_link" href="https://goldplugins.com/our-plugins/easy-testimonials-details/?utm_source=cpn_box&utm_campaign=upgrade&utm_banner=learn_more" title="Learn More">Click Here To Learn More &raquo;</a>
			</div>
			<div id="mc_embed_signup">
				<div class="save_now">
					<h3>Save 10% Now!</h3>
					<p class="pitch">Subscribe to our newsletter now, and we’ll send you a coupon for 10% off your upgrade to the Pro version.</p>
				</div>
				<form action="https://goldplugins.com/atm/atm.php?u=403e206455845b3b4bd0c08dc&amp;id=a70177def0" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
					<div class="fields_wrapper">
						<label for="mce-NAME">Your Name (optional)</label>
						<input type="text" value="<?php echo (!empty($current_user->display_name) ?  $current_user->display_name : ''); ?>" name="NAME" class="name" id="mce-NAME" placeholder="Your Name">
						<label for="mce-EMAIL">Your Email</label>
						<input type="email" value="<?php echo (!empty($current_user->user_email) ?  $current_user->user_email : ''); ?>" name="EMAIL" class="email" id="mce-EMAIL" placeholder="email address" required>
						<!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
						<div style="position: absolute; left: -5000px;"><input type="text" name="b_403e206455845b3b4bd0c08dc_6ad78db648" tabindex="-1" value=""></div>
					</div>
					<div class="clear"><input type="submit" value="<?php _e('Send My Coupon', 'easy-testimonials'); ?>" name="subscribe" id="mc-embedded-subscribe" class="smallBlueButton"></div>
					<p class="secure"><img src="<?php echo $this->config->url_path . "assets/img/lock.png"; ?>" alt="Lock" width="16px" height="16px" />We respect your privacy.</p>
					
					<input type="hidden" id="mc-upgrade-plugin-name" value="Easy Testimonials Pro" />
					<input type="hidden" id="mc-upgrade-link-per" value="https://goldplugins.com/purchase/easy-testimonials-pro/single?promo=newsub10" />
					<input type="hidden" id="mc-upgrade-link-biz" value="https://goldplugins.com/purchase/easy-testimonials-pro/business?promo=newsub10" />
					<input type="hidden" id="mc-upgrade-link-dev" value="https://goldplugins.com/purchase/easy-testimonials-pro/developer?promo=newsub10" />
					
					<div class="customer_testimonial">
							<div class="stars">
								<span class="dashicons dashicons-star-filled"></span>
								<span class="dashicons dashicons-star-filled"></span>
								<span class="dashicons dashicons-star-filled"></span>
								<span class="dashicons dashicons-star-filled"></span>
								<span class="dashicons dashicons-star-filled"></span>
							</div>
							<p class="customer_testimonial_title"><strong>Easy Testimonials was by far the best</strong></p>
							“I looked at several testimonial plugins, and Easy Testimonials was by far the best, most user friendly and customizable plugin I found (and a reasonable price).”
							<p class="author">&mdash; Greg Campisi</p>
					</div>
					<input type="hidden" id="gold_plugins_already_subscribed" name="gold_plugins_already_subscribed" value="0" />
				</form>
				<div class="gp_logo">
					<a href="https://goldplugins.com/?utm_source=easy_testimonials_coupon_box&utm_campaign=gp_logo" target="_blank">
						<img src="<?php echo $this->config->url_path . "assets/img/logo.png"; ?>">
					</a>
				</div>
			</div>		
			<p class="u_to_p"><a href="https://goldplugins.com/our-plugins/easy-testimonials-details/upgrade-to-easy-testimonials-pro/?utm_source=newsletter_signup_bottom_rm_banners">Upgrade to Easy Testimonials Pro now</a> to remove banners like this one.</p>
			<?php $this->output_hello_t_banner(); ?>
			<div style="clear:right;"></div>
		</div>	
		<?php			
	}
	
	function output_hello_t_banner()
	{
		echo '<div class="sidebar_hello_t hello_t_banner" style="padding-top:1px; padding-left: 30px;">'; 
		echo '<h3><strong>Need more Testimonials?</strong></h3>
				<p>Then try <strong>Hello Testimonials!</strong>  Our new system automatically collects testimonials from your customers.</p>
				<p>Easy Testimonials users receive a free 14-day trial!</p>
				<p><a class="smallBlueButton" href="http://hellotestimonials.com/p/welcome-easy-testimonials-users/" title="Click Here To LEarn More">Click Here To Learn More</a></p>
				<br/>';
		echo "</div>";
		echo '<p class="u_to_p u_to_p_main_col"><a href="https://goldplugins.com/our-plugins/easy-testimonials-details/upgrade-to-easy-testimonials-pro/?utm_source=themes">Upgrade to Easy Testimonials Pro now</a> to remove banners like this one.</p>';				
	}
	
	function get_and_output_current_tab($pagenow){
		$tab = $_GET['page'];
		
		$this->easy_t_admin_tabs($tab); 
				
		return $tab;
	}
		
	function get_recaptcha_languages()	
	{
		// from: https://developers.google.com/recaptcha/docs/language
		return array(
			'Arabic' => 'ar',
			'Bengali' => 'bn',
			'Bulgarian' => 'bg',
			'Catalan' => 'ca',
			'Chinese (Simplified)' => 'zh-CN',
			'Chinese (Traditional)' => 'zh-TW',
			'Croatian' => 'hr',
			'Czech' => 'cs',
			'Danish' => 'da',
			'Dutch' => 'nl',
			'English (UK)' => 'en-GB',
			'English (US)' => 'en',
			'Estonian' => 'et',
			'Filipino' => 'fil',
			'Finnish' => 'fi',
			'French' => 'fr',
			'French (Canadian)' => 'fr-CA',
			'German' => 'de',
			'Gujarati' => 'gu',
			'German (Austria)' => 'de-AT',
			'German (Switzerland)' => 'de-CH',
			'Greek' => 'el',
			'Hebrew' => 'iw',
			'Hindi' => 'hi',
			'Hungarain' => 'hu',
			'Indonesian' => 'id',
			'Italian' => 'it',
			'Japanese' => 'ja',
			'Kannada' => 'kn',
			'Korean' => 'ko',
			'Latvian' => 'lv',
			'Lithuanian' => 'lt',
			'Malay' => 'ms',
			'Malayalam' => 'ml',
			'Marathi' => 'mr',
			'Norwegian' => 'no',
			'Persian' => 'fa',
			'Polish' => 'pl',
			'Portuguese' => 'pt',
			'Portuguese (Brazil)' => 'pt-BR',
			'Portuguese (Portugal)' => 'pt-PT',
			'Romanian' => 'ro',
			'Russian' => 'ru',
			'Serbian' => 'sr',
			'Slovak' => 'sk',
			'Slovenian' => 'sl',
			'Spanish' => 'es',
			'Spanish (Latin America)' => 'es-419',
			'Swedish' => 'sv',
			'Tamil' => 'ta',
			'Telugu' => 'te',
			'Thai' => 'th',
			'Turkish' => 'tr',
			'Ukrainian' => 'uk',
			'Urdu' => 'ur',
			'Vietnamese' => 'vi',
		);

	}
	
	function easy_t_excerpt_text($val){
		//if nothing set, default to Continue Reading
		if(strlen($val)<1){
			return "Continue Reading";
		} else {
			return $val;
		}
	}
	
	function easy_t_excerpt_length($val){
		//if nothing set, default to 55
		if(strlen($val)<1){
			return 55;
		} else {
			return intval($val);
		}
	}
	
	function typography_input($name, $label, $description)
	{
		global $EasyT_BikeShed;
		$options = array();
		$options['name'] = $name;
		$options['label'] = $label;
		$options['description'] = $description;
		$options['google_fonts'] = true;
		$options['default_color'] = '';
		$options['values'] = $this->get_typography_values($name);		
		$options['disabled'] = !$this->config->is_pro; // typography inputs are Pro only
		$EasyT_BikeShed->typography( $options );
	}
	
	//from http://codex.wordpress.org/Function_Reference/get_intermediate_image_sizes
	function easy_t_output_image_options(){
		global $_wp_additional_image_sizes;
		$sizes = array();
		foreach( get_intermediate_image_sizes() as $s ){
			$sizes[ $s ] = array( 0, 0 );
			if( in_array( $s, array( 'thumbnail', 'medium', 'large' ) ) ){
				$sizes[ $s ][0] = get_option( $s . '_size_w' );
				$sizes[ $s ][1] = get_option( $s . '_size_h' );
			}else{
				if( isset( $_wp_additional_image_sizes ) && isset( $_wp_additional_image_sizes[ $s ] ) )
					$sizes[ $s ] = array( $_wp_additional_image_sizes[ $s ]['width'], $_wp_additional_image_sizes[ $s ]['height'], );
			}
		}

		$current_size = get_option('easy_t_image_size');
		
		foreach( $sizes as $size => $atts ){
			$disabled = '';
			$selected = '';
			$register = '';
			
			if($current_size == $size){
				$selected = 'selected="SELECTED"';
				$disabled = '';
				$register = '';
			}
			echo "<option value='".$size."' ".$disabled . " " . $selected.">" . ucwords(str_replace("-", " ", str_replace("_", " ", $size))) . ' ' . implode( 'x', $atts ) . $register . "</option>";
		}
	}
	
	function get_typography_values($pattern, $default_value = '')
	{
		$keys = array();
		$values = array();
		$keys[] = 'font_size';
		$keys[] = 'font_family';
		$keys[] = 'font_style';
		$keys[] = 'font_color';
		foreach($keys as $key) {			
			$option_key = str_replace('*', $key, $pattern);
			$values[$key] = get_option($option_key, $default_value);
		}
		return $values;
	}
	
	function easy_t_bust_options_cache()
	{
		delete_transient('_easy_t_webfont_str');
		delete_transient('_easy_t_testimonial_style');
		
		//this should flush our frontend cache.
		add_action('admin_init', array($this, 'easy_t_clear_cache') );
	}	
	
	//some functions for theme output
	function get_theme_group_label($theme_group)
	{
		reset($theme_group);
		$first_key = key($theme_group);
		$group_label = $theme_group[$first_key];
		if ( ($dash_pos = strpos($group_label, ' -')) !== FALSE && ($avatar_pos = strpos($group_label, 'Avatar')) === FALSE ) {
			$group_label = substr($group_label, 0, $dash_pos);
		}
		return $group_label;
	}
	
	//load all easy_t transients
	//fix the cache keys for delete_transient function
	//loop through cached items and delete them
	function easy_t_clear_cache(){
		//initialize counter
		$counter = 0;
	
		global $wpdb;
		$sql = "SELECT `option_name` AS `name`, `option_value` AS `value`
				FROM  $wpdb->options
				WHERE `option_name` LIKE '%transient_easy_t%'
				ORDER BY `option_name`";

		$results = $wpdb->get_results( $sql );
		$transients = array();
		
		//loop through found transients and try to delete them
		foreach ( $results as $result )
		{
			//remove _transient_ from the transient key name
			$cache_key = str_replace("_transient_", "", $result->name);
			
			//delete the transient
			$success = delete_transient($cache_key);
			
			//keep track of how many we've deleted
			$counter ++;
			
		}
		
		//let them know what you did!
		if(empty($results)){
			$this->messages[] = "No cached items to flush.";
		} else {
			//pluralize if deleting more than one item
			$string = "item";
			
			if( $counter > 1 ){
				$string = "items";
			}
			$this->messages[] = "Successfully flushed {$counter} {$string} from the cache.";
		}
	}
} // end class
?>