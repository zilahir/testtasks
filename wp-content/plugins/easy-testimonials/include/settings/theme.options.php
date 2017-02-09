<?php
class easyTestimonialThemeOptions extends easyTestimonialOptions{
	var $tabs;
	var $config;

	function __construct($config){
		//call register settings function
		add_action( 'admin_init', array($this, 'register_settings'));

		//assign config
		$this->config = $config;
	}

	function register_settings(){
		//register our settings

		/* Theme selection */
		register_setting( 'easy-testimonials-style-settings-group', 'testimonials_style' );
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
			'header_label' => 'Theme Settings',
			'settings_field_key' => 'easy-testimonials-style-settings-group', // can be an array
			'extra_buttons_header' => $extra_buttons, // extra header buttons
			'extra_buttons_footer' => $extra_buttons // extra footer buttons
		) );

		$this->settings_page_top();
		$this->setup_basic_tabs($tabs);
		$this->settings_page_bottom();
	}

	function output_theme_options(){
		$output_free_theme_array = $this->config->free_theme_array;
		$output_pro_theme_array = $this->config->pro_theme_array;

		//check for pro
		$ip = $this->config->is_pro;

		//load currently selected theme
		$theme = get_option('testimonials_style');
		?>

		<h3>Style &amp; Theme Options</h3>
		<p class="description">Select which style you want to use.  If 'No Style' is selected, only your Theme's CSS, and any Custom CSS you've added, will be used.</p>

		<table class="form-table easy_t_options">
			<tr>
				<td>
					<fieldset>
						<legend>Select Your Theme</legend>
						<select name="testimonials_style" id="testimonials_style">
							<optgroup label="Free Themes">
							<?php foreach($output_free_theme_array as $key => $theme_name): ?>
								<option value="<?php echo $key ?>" <?php if($theme == $key): echo 'selected="SELECTED"'; endif; ?>><?php echo htmlentities($theme_name); ?></option>
							<?php endforeach; ?>
							</optgroup>
							<?php foreach($output_pro_theme_array as $group_key => $theme_group): ?>
								<?php $group_label = $this->get_theme_group_label($theme_group); ?>
									<?php if (!$ip): ?>
									<optgroup  label="<?php echo htmlentities($group_label);?> (Pro Required)">
									<?php else: ?>
									<optgroup  label="<?php echo htmlentities($group_label);?>">
									<?php endif; ?>
									<?php foreach($theme_group as $key => $theme_name): ?>
										<?php if (!$ip): ?>
										<option value="<?php echo $key ?>-disabled" <?php if($theme == $key): echo 'selected="SELECTED"'; endif; ?>><?php echo htmlentities($theme_name); ?></option>
										<?php else: ?>
										<option value="<?php echo $key ?>" <?php if($theme == $key): echo 'selected="SELECTED"'; endif; ?>><?php echo htmlentities($theme_name); ?></option>
										<?php endif; ?>
									<?php endforeach; ?>
								</optgroup>
							<?php endforeach; ?>
						</select>
					</fieldset>

					<h4>Preview Selected Theme</h4>
					<p class="description">Please note: your Theme's CSS may impact the appearance.</p>
					<p><strong>Current Saved Theme Selection:</strong>  <?php echo ucwords(str_replace('-', ' - ', str_replace('_',' ', str_replace('-style', '', $theme)))); ?></p>
					<div id="easy_t_preview" class="easy_t_preview">
						<p class="easy_testimonials_not_registered" style="display: none; margin-bottom: 20px;"><a href="https://goldplugins.com/our-plugins/easy-testimonials-details/upgrade-to-easy-testimonials-pro/?utm_source=themes_preview"><?php _e('This Theme Requires Pro! Upgrade to Easy Testimonials Pro now', 'easy-testimonials');?></a> <?php _e('to unlock all 75+ themes!', 'easy-testimonials');?> </p>
						<div class="style-<?php echo str_replace('-style', '', $theme); ?> easy_t_single_testimonial">
							<blockquote itemprop="review" itemscope itemtype="http://schema.org/Review" style="">
								<img class="attachment-easy_testimonial_thumb wp-post-image easy_testimonial_mystery_man" src="<?php echo $this->config->url_path . 'assets/img/mystery_man.png';?>" />
								<p itemprop="name" class="easy_testimonial_title">Support is second to none</p>
								<div class="testimonial_body" itemprop="description">
									<p>Easy Testimonials is just what I have been looking for. A breeze to install, feature rich and simple to use in order to deliver what looks really sophisticated. What’s more, their support is second to none. I had a question with my install and the perfect answer came back in less than an hour.</p>
									<a href="https://goldplugins.com/testimonials/" class="easy_testimonials_read_more_link">Read More Testimonials</a>
								</div>
								<p class="testimonial_author">
									<cite>
										<span class="testimonial-client" itemprop="author" style="">Tom Evans</span>
										<span class="testimonial-position" style="">www.tomevans.co</span>
										<span class="testimonial-other" itemprop="itemReviewed">Easy Testimonials&nbsp;</span>
										<span class="date" itemprop="datePublished" content="Oct. 15, 2015" style="">May 28, 2015&nbsp;</span>
										<span itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating" class="stars">
											<meta itemprop="worstRating" content="1"/>
											<meta itemprop="ratingValue" content="5"/>
											<meta itemprop="bestRating" content="5"/>
											<span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span>
										</span>
									</cite>
								</p>
							</blockquote>
						</div>
						<div class="easy-t-cycle-controls">
							<div class="cycle-prev easy-t-cycle-prev">&lt;&lt; Previous</div>							<div class="easy-t-cycle-pager"><span class="">•</span><span class="">•</span><span class="">•</span><span class="cycle-pager-active">•</span><span class="">•</span></div>
										<div class="cycle-next easy-t-cycle-next">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Next &gt;&gt;</div>
						</div>
					</div>
				</td>
			</tr>
		</table>
		<?php
	}

	function setup_basic_tabs($tabs){
		$this->tabs = $tabs;

		$this->tabs->add_tab(
			'theme_options', // section id, used in url fragment
			'Theme Options', // section label
			array($this, 'output_theme_options'), // display callback
			array(
				'class' => 'extra_li_class', // extra classes to add sidebar menu <li> and tab wrapper <div>
				'icon' => 'paint-brush' // icons here: http://fontawesome.io/icons/
			)
		);

		$this->tabs->display();
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
}
