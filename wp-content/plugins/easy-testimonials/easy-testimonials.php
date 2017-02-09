<?php
/*
Plugin Name: Easy Testimonials
Plugin URI: https://goldplugins.com/our-plugins/easy-testimonials-details/
Description: Easy Testimonials - Provides custom post type, shortcode, sidebar widget, and other functionality for testimonials.
Author: Gold Plugins
Version: 2.1.4
Author URI: https://goldplugins.com
Text Domain: easy-testimonials

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
along with Easy Testimonials .  If not, see <http://www.gnu.org/licenses/>.
*/
global $easy_t_footer_css_output;

require_once( plugin_dir_path( __FILE__ ) . "include/config.php" );
require_once( plugin_dir_path( __FILE__ ) . "include/lib/lib.php" );
require_once( plugin_dir_path( __FILE__ ) . "include/lib/BikeShed/bikeshed.php" );
require_once( plugin_dir_path( __FILE__ ) . "include/lib/GP_Media_Button/gold-plugins-media-button.class.php" );
require_once( plugin_dir_path( __FILE__ ) . "include/lib/GP_Janus/gp-janus.class.php" );
require_once( plugin_dir_path( __FILE__ ) . "include/lib/GP_Aloha/gp_aloha.class.php" );
require_once( plugin_dir_path( __FILE__ ) . "include/lib/GP_Sajak/gp_sajak.class.php" );
require_once( plugin_dir_path( __FILE__ ) . "include/lib/gp-testimonial-importer.class.php" );
require_once( plugin_dir_path( __FILE__ ) . "include/lib/gp-testimonial-exporter.class.php" );
require_once( plugin_dir_path( __FILE__ ) . "include/lib/gp-testimonial.class.php" );
require_once( plugin_dir_path( __FILE__ ) . "include/lib/gp-testimonial-form.class.php" );
require_once( plugin_dir_path( __FILE__ ) . "include/lib/ik-custom-post-type.php" );
require_once( plugin_dir_path( __FILE__ ) . "include/lib/easy_testimonials_custom_columns.php" );
require_once( plugin_dir_path( __FILE__ ) . "include/settings/testimonial.options.php" );	
require_once( plugin_dir_path( __FILE__ ) . "include/maintenance/maintenance.php" );	

class easyTestimonials
{	
	var $shortcodes;
	var $plugin;
	var $config;
	var $options;
	var $media_buttons;
	
	function __construct(){		
		//catch weird cases when things don't exist and just exit
		if( !class_exists('easyTestimonialsConfig') ){
			return false;
		}
		
		//load config
		$this->config = new easyTestimonialsConfig();
		
		//load options
		$this->options = new easyTestimonialOptions($this->config);
		
		//register shortcodes
		$this->add_shortcodes();	
			
		//create media button object for use with editor widgets
		$this->media_buttons = new Gold_Plugins_Media_Button('Testimonials', 'testimonial');	
		
		//for use in upgrade, settings links
		$this->plugin = plugin_basename(__FILE__);
		
		//only load Aloha and Janus on Admin screens
		if( is_admin() ){			
			// load Janus
			new GP_Janus();
			
			// load Aloha
			$aloha_page_title = $this->config->is_pro
								? __('Welcome To Easy Testimonials Pro')
								: __('Welcome To Easy Testimonials');
			$aloha_config = array(
				'menu_label' => __('About Plugin'),
				'page_title' => $aloha_page_title,
				'top_level_menu' => 'easy-testimonials-settings',
			);
			
			$this->Aloha = new GP_Aloha($aloha_config);
			add_filter( 'gp_aloha_welcome_page_content_easy-testimonials-settings', array($this, 'get_welcome_template') );
		}

		//add editor widgets
		add_action( 'admin_init', array($this, 'add_media_buttons') );
		
		//add JS
		add_action( 'wp_enqueue_scripts', array($this, 'easy_testimonials_setup_js'), 9999 );
				
		// add Google web fonts if needed
		add_action( 'wp_enqueue_scripts', array($this, 'enqueue_webfonts') );

		//add CSS
		add_action( 'wp_enqueue_scripts', array($this, 'easy_testimonials_setup_css' ) );

		//add Custom CSS
		add_action( 'wp_head', array($this, 'easy_testimonials_setup_custom_css') );

		//register sidebar widgets
		add_action( 'widgets_init', array($this, 'easy_testimonials_register_widgets' ) );

		//setup custom post type
		add_action( 'init', array($this, 'easy_testimonials_setup_testimonials') );
		
		//add admin scripts
		add_action( 'admin_enqueue_scripts', array($this, 'easy_testimonials_admin_init') );
		
		//check for conflicts
		add_action( 'admin_enqueue_scripts', array($this, 'easy_testimonials_conflict_check') );
		
		//load text domain for translation purposes
		add_action( 'plugins_loaded', array($this, 'easy_t_load_textdomain') );

		//add custom columns to testimonial category list
		add_filter( 'manage_edit-easy-testimonial-category_columns', array($this, 'easy_t_cat_column_head'), 10 );  
		add_action( 'manage_easy-testimonial-category_custom_column', array($this, 'easy_t_cat_columns_content'), 10, 3 ); 
		
		//add our custom links for Settings and Support to various places on the Plugins page
		add_filter( "plugin_action_links_{$this->plugin}", array($this, 'add_settings_link_to_plugin_action_links' ));
		add_filter( 'plugin_row_meta', array($this, 'add_custom_links_to_plugin_description'), 10, 2 );	

		//add query var for paging
		add_filter( 'query_vars', array($this, 'easy_t_add_pagination_query_var' ));

		//run activation steps
		register_activation_hook( __FILE__, array($this, 'easy_testimonials_activation_hook' ));
		
		// first override blog post content function by avada to prevent it running on testimonials
		// then apply our own content filter instead
		if(get_option('easy_t_avada_filter_override', false)){
			//attach our custom content filter to their action that will use our styling
			add_action('avada_blog_post_content', array($this, 'easy_t_avada_content_filter') );
		}
		
		//link hello t action to import function, for use via cron job
		//the cron job is scheduled inside the import-export.options class
		add_action('hello_t_subscription', array($this->options->import_export_settings_page, 'add_hello_t_testimonials') );

		//when you deactivate easy testimonials, disable the hello t cron job.
		register_deactivation_hook( __FILE__, array($this->options->import_export_settings_page, 'hello_t_cron_deactivate') );
		
		//setup submission form
		if ($this->config->is_pro){
			$atts = array(
				'media_buttons' => $this->media_buttons,
				'config' => $this->config
			);
			
			$testimonial_submission_form = new GP_TestimonialForm( $atts );
		}					
		
		//set content filter flag when primary the_content call is made (ie, the one in which our initial testimonials shortcode lay)
		//this may be causing problems with us rendering shortcodes inside our plugin correctly, and may also be redundant
		//as we already have another internal check to prevent us from applying this filter repeatedly with our plugin
		//making this note to indicate the problem we are trying to solve by removing this line
		//add_filter('the_content', array($this, 'set_content_flag'), 1 );

		//override content filter on single testimonial pages 
		//to load the proper HTML structure and content for displaying a testimonial
		global $gp_testimonial_class;
		$gp_testimonial_class = new GP_Testimonial(false, $this->config);
		add_filter( 'the_content', array($gp_testimonial_class, 'single_testimonial_content_filter'), 10 );
		
		//clean broken images from testimonials, preventing occasional fatal errors on edit screens
		add_action( 'load-post.php', array($this,'fix_testimonial_featured_images') );
		
		// setup custom columns on View All Testimonials screen
		$ezt_testionials_cols = new Easy_Testimonials_Custom_Columns();
	}
	
	function get_welcome_template()
	{
		$base_path = plugin_dir_path( __FILE__ );
		$template_path = $base_path . '/include/content/welcome.php';
		$is_pro = $this->config->is_pro;
		$content = file_exists($template_path)
				   ? include($template_path)
				   : '';
		return $content;
	}
	
	//checks for a WP_Error on this testimonial's featured image
	//if there is an error, we unset the image to prevent edit screen from breaking
	function fix_testimonial_featured_images()
	{
		//if there is a post
		if ( !empty($_GET['post']) ){
			// Get the post object
			$post = get_post($_GET['post']);
			//and its a testimonial
			if( !empty($post->post_type) && $post->post_type == "testimonial" ){
				// If the post has a bad featured image, remove the meta
				if ( is_wp_error(get_post_thumbnail_id($post->ID)) ) {
					delete_post_meta($post->ID, '_thumbnail_id');
				}
			}
		}
	}
	
	//add media buttons
	function add_media_buttons(){		
		// add media buttons to admin
		$cur_post_type = ( isset($_GET['post']) ? get_post_type(intval($_GET['post'])) : '' );
		if( is_admin() && ( empty($_REQUEST['post_type']) || $_REQUEST['post_type'] !== 'testimonial' ) && ($cur_post_type !== 'testimonial') )
		{					
			$this->media_buttons->add_button('Single Testimonial', $this->shortcodes['single_testimonial_shortcode'], 'singletestimonialwidget', 'testimonial');
			$this->media_buttons->add_button('Random Testimonial', $this->shortcodes['random_testimonial_shortcode'], 'randomtestimonialwidget', 'testimonial');
			$this->media_buttons->add_button('List of Testimonials',  $this->shortcodes['testimonials_shortcode'], 'listtestimonialswidget', 'testimonial');
			$this->media_buttons->add_button('Grid of Testimonials',  $this->shortcodes['testimonials_grid_shortcode'], 'testimonialsgridwidget', 'testimonial');
			$this->media_buttons->add_button('Testimonial Cycle',  $this->shortcodes['testimonials_cycle_shortcode'], 'cycledtestimonialwidget', 'testimonial');
		}
	}
	
	//setup the plugins shortcodes
	function add_shortcodes(){		
		//build array of shortcodes to register		
		$this->shortcodes['random_testimonial_shortcode'] = get_option('ezt_random_testimonial_shortcode', 'random_testimonial');
		$this->shortcodes['single_testimonial_shortcode'] = get_option('ezt_single_testimonial_shortcode', 'single_testimonial');
		$this->shortcodes['testimonials_shortcode'] = get_option('ezt_testimonials_shortcode', 'testimonials');
		$this->shortcodes['testimonials_cycle_shortcode'] = get_option('ezt_cycle_testimonial_shortcode', 'testimonials_cycle');
		$this->shortcodes['testimonials_count_shortcode'] = get_option('ezt_testimonials_count_shortcode', 'testimonials_count');
		$this->shortcodes['testimonials_grid_shortcode'] = get_option('ezt_testimonials_grid_shortcode', 'testimonials_grid');

		//TODO: check for shortcode conflicts
		$this->easy_testimonials_shortcode_checker($this->shortcodes);

		//create shortcodes
		add_shortcode( $this->shortcodes['random_testimonial_shortcode'], array($this, 'outputRandomTestimonial') );
		add_shortcode( $this->shortcodes['single_testimonial_shortcode'], array($this, 'outputSingleTestimonial') );
		add_shortcode( $this->shortcodes['testimonials_shortcode'], array($this, 'outputTestimonials') );
		add_shortcode( $this->shortcodes['testimonials_cycle_shortcode'], array($this, 'outputTestimonialsCycle') );
		add_shortcode( $this->shortcodes['testimonials_count_shortcode'], array($this, 'outputTestimonialsCount') );
		add_shortcode( 'output_all_themes', array($this, 'outputAllThemes') );
		add_shortcode( 'easy_t_search_testimonials', array($this, 'easy_t_search_form_shortcode') );
		add_shortcode( $this->shortcodes['testimonials_grid_shortcode'], array($this, 'easy_t_testimonials_grid_shortcode') );
	}

	//setup JS
	function easy_testimonials_setup_js() {
		$disable_cycle2 = get_option('easy_t_disable_cycle2');
		$use_cycle_fix = get_option('easy_t_use_cycle_fix');

		// register the recaptcha script, but only enqueue it later, when/if we see the submit_testimonial shortcode
		$recaptcha_lang = get_option('easy_t_recaptcha_lang', '');
		$recaptcha_js_url = 'https://www.google.com/recaptcha/api.js' . ( !empty($recaptcha_lang) ? '?hl='.urlencode($recaptcha_lang) : '' );
		wp_register_script(
				'g-recaptcha',
				$recaptcha_js_url
		);

		// register the grid-height script, but only enqueue it later, when/if we see the testimonials_grid shortcode with the auto_height option on
		wp_register_script(
				'easy-testimonials-grid',
				plugins_url('include/assets/js/easy-testimonials-grid.js', __FILE__),
				array( 'jquery' )
		);
		
		// pro scripts for pro users
		if($this->config->is_pro){ 	
			if(!$disable_cycle2){
				wp_enqueue_script(
					'gp_cycle2',
					plugins_url('include/assets/js/jquery.cycle2.pro.min.js', __FILE__),
					array( 'jquery' ),
					false,
					true
				);  
			} else {
				//if we aren't including our cycle2, we want to use 
				//the old method of adding the advanced transitions
				wp_enqueue_script(
					'easy-testimonials',
					plugins_url('include/assets/js/easy-testimonials.js', __FILE__),
					array( 'jquery' ),
					false,
					true
				);
			}
			
			wp_enqueue_script(
				'rateit',
				plugins_url('include/assets/js/jquery.rateit.min.js', __FILE__),
				array( 'jquery' ),
				false,
				true
			);
		} else {		
			if(!$disable_cycle2){
				wp_enqueue_script(
					'gp_cycle2',
					plugins_url('include/assets/js/jquery.cycle2.min.js', __FILE__),
					array( 'jquery' ),
					false,
					true
				);  
			}
		}
			
		if($use_cycle_fix){
			wp_enqueue_script(
				'easy-testimonials-cycle-fix',
				plugins_url('include/assets/js/easy-testimonials-cycle-fix.js', __FILE__),
				array( 'jquery' ),
				false,
				true
			);
		}
	}

	//add Testimonial CSS to header
	function easy_testimonials_setup_css() {
		wp_register_style( 'easy_testimonial_style', plugins_url('include/assets/css/style.css', __FILE__) );
		
		$cache_key = '_easy_t_testimonial_style';
		
		$style = get_option('testimonials_style', '');

		// enqueue the base style unless "no_style" has been specified
		if($style != 'no_style') {
			wp_enqueue_style( 'easy_testimonial_style' );
		}

		// enqueue Pro CSS files
		if($this->config->is_pro) {
			$this->easy_t_register_pro_themes();
		}	
	}

	function easy_t_register_pro_themes(){
		//five star ratings
		wp_register_style( 'easy_testimonial_rateit_style', plugins_url('include/assets/css/rateit.css', __FILE__) );
		wp_enqueue_style( 'easy_testimonial_rateit_style' );
		
		//register and enqueue pro style
		wp_register_style( 'easy_testimonials_pro_style', plugins_url('include/assets/css/easy_testimonials_pro.css', __FILE__) );
		wp_enqueue_style( 'easy_testimonials_pro_style' );
	}

	//add Custom CSS
	function easy_testimonials_setup_custom_css() {
		//use this to track if css has been output
		global $easy_t_footer_css_output;
		
		if($easy_t_footer_css_output){
			return;
		} else {
			echo '<style type="text/css" media="screen">' . get_option('easy_t_custom_css') . "</style>";
			$easy_t_footer_css_output = true;
		}
	}

	//display Testimonial Count
	//$category is the slug of the category you want a count from
	//if nothing is passed, displays count of all testimonials
	//$status is the status of the testimonials to be included in the count
	//defaults to published testimonials only
	//if $aggregate_rating is set to true, this will output the aggregate rating markup for the counted testimonials
	function easy_testimonials_count($category = '', $status = 'publish', $show_aggregate_rating = false){
		$tax_query = array();	
		
		//if a category slug was passed
		//only count testimonials within that category
		if(strlen($category)>0){
			$tax_query = array(
				array(
					'taxonomy' => 'easy-testimonial-category',
					'field' => 'slug',
					'terms' => $category
				)
			);
		}
		
		$args = array (
			'post_type' => 'testimonial',
			'tax_query' => $tax_query,
			'post_status' => $status,
			'nopaging' => true
		);
			
		$count_query = $this->get_testimonials_loop( $args );
		
		//if the option to show aggregate rating is toggling
		//construct and return the aggregate rating output
		//instead of just returning the numerical count
		if($show_aggregate_rating){		
			
			//calculate average review value
			$total_rating = 0;
			$total_rated_testimonials = 0;//only want to divide by the number of testimonials with actual ratings
			
			//TBD: allow control over item rating is displayed about
			$item_reviewed = get_option('easy_t_global_item_reviewed','');
			
			foreach ($count_query->posts as $testimonial){
				$testimonial_rating = get_post_meta($testimonial->ID, '_ikcf_rating', true);
				
				if(intval($testimonial_rating) > 0){				
					$total_rated_testimonials ++;
					$total_rating += $testimonial_rating;
				}
			}

			$average_rating = $total_rating / $total_rated_testimonials;
			
			$output = '
				<div class="easy_t_aggregate_rating_wrapper" itemscope itemtype="http://schema.org/Product">
					<span class="easy_t_aggregate_rating_item" itemprop="name">' . $item_reviewed . '</span>
					<div class="easy_t_aggregate_rating" itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating">Rated <span class="easy_t_aggregate_rating_top_count" itemprop="ratingValue">' . round($average_rating, 2) . '</span>/5 based on <span itemprop="reviewCount" class="easy_t_aggregate_rating_review_count" >' . $total_rated_testimonials . '</span> customer reviews</div>		
				</div>
			';
			
			return apply_filters('easy_t_aggregate_rating', $output, $count_query);
		}
		
		//if we are down here, we aren't doing an aggregate rating
		//so return the count
		return apply_filters('easy_t_testimonials_count', $count_query->found_posts, $count_query);
	}

	//shortcode mapping function for easy_testimonials_count
	//accepts three attributes, category and status and show_aggregate_rating
	function outputTestimonialsCount($atts){
		//load shortcode attributes into an array
		extract( shortcode_atts( array(
			'category' => '',
			'status' => 'publish',
			'show_aggregate_rating' => false
		), $atts ) );
		
		$output = $this->easy_testimonials_count($category, $status, $show_aggregate_rating);
		
		return $output;
	}

	//load proper language pack based on current language
	function easy_t_load_textdomain() {
		$plugin_dir = basename(dirname(__FILE__));
		load_plugin_textdomain( 'easy-testimonials', false, $plugin_dir . 'include/languages' );
	}

	//setup custom post type for testimonials
	function easy_testimonials_setup_testimonials()
	{
		//setup post type for testimonials
		$postType = array(
			'name' => 'Testimonial',
			'plural' =>'Testimonials',
			'slug' => 'testimonial',
			'exclude_from_search' => !get_option('easy_t_show_in_search', true),
			'supports' => array('title','editor','thumbnail','excerpt','custom-fields')
		);
		$fields = array(); 
		$fields[] = array('name' => 'client', 'title' => 'Client Name', 'description' => "Name of the Client giving the testimonial.  Appears below the Testimonial.", 'type' => 'text');
		$fields[] = array('name' => 'email', 'title' => 'E-Mail Address', 'description' => "The client's e-mail address.  This field is used to check for a Gravatar, if that option is enabled in your settings.", 'type' => 'text'); 
		$fields[] = array('name' => 'position', 'title' => 'Position / Web Address / Other', 'description' => "The information that appears below the client's name.", 'type' => 'text');  
		$fields[] = array('name' => 'other', 'title' => 'Location Reviewed / Product Reviewed / Item Reviewed', 'description' => "The information that appears below the second custom field, Position / Web Address / Other.  Display of this field is required for proper structured data output.", 'type' => 'text');  
		$fields[] = array('name' => 'rating', 'title' => 'Rating', 'description' => "The client's rating, if submitted along with their testimonial.  This can be displayed below the client's position, or name if the position is hidden, or it can be displayed above the testimonial text.", 'type' => 'text');  
		$myCustomType = new ikTestimonialsCustomPostType($postType, $fields);
		register_taxonomy( 'easy-testimonial-category', 'testimonial', array( 'hierarchical' => true, 'label' => __('Testimonial Category', 'easy-testimonials'), 'rewrite' => array('slug' => 'testimonial-category', 'with_front' => true) ) ); 
		
		//load list of current posts that have featured images	
		$supportedTypes = get_theme_support( 'post-thumbnails' );
		
		//none set, add them just to our type
		if( $supportedTypes === false ){
			add_theme_support( 'post-thumbnails', array( 'testimonial' ) );       
			//for the testimonial thumb images    
		}
		//specifics set, add ours to the array
		elseif( is_array( $supportedTypes ) ){
			$supportedTypes[0][] = 'testimonial';
			add_theme_support( 'post-thumbnails', $supportedTypes[0] );
			//for the testimonial thumb images
		}
		//if neither of the above hit, the theme in general supports them for everything.  that includes us!
		
		add_image_size( 'easy_testimonial_thumb', 50, 50, true );
			
		add_action( 'admin_menu', array($this, 'easy_t_add_meta_boxes')); // add our custom meta boxes
	}

	function easy_t_add_meta_boxes(){
		add_meta_box( 'testimonial_shortcodes', 'Shortcodes', array($this, 'easy_t_display_shortcodes_meta_box'), 'testimonial', 'side', 'default' );
	}
	 

	//this is the heading of the new column we're adding to the testimonial category list
	function easy_t_cat_column_head($defaults) {  
		$defaults = array_slice($defaults, 0, 2, true) +
		array("single_shortcode" => "Shortcode") +
		array_slice($defaults, 2, count($defaults)-2, true);
		return $defaults;  
	}  

	//this content is displayed in the testimonial category list
	function easy_t_cat_columns_content($value, $column_name, $tax_id) {  

		$category = get_term_by('id', $tax_id, 'easy-testimonial-category');
		
		return "<textarea>[testimonials category='{$category->slug}']</textarea>"; 
	}

	//load a testimonials loop
	//has some object caching
	function get_testimonials_loop($atts = array()){
		//only use cache if enabled (defaults to true)		
		if( $this->config->cache_enabled ){
			$cache_key = "easy_t_object_cache_" . md5(serialize($atts));
			
			// Get any existing copy of our transient data
			if ( false === ($testimonials_loop = get_transient($cache_key)) ){
				// It wasn't there, so regenerate the data and save the transient
				$testimonials_loop = new WP_Query($atts);
				set_transient( $cache_key, $testimonials_loop, $this->config->cache_time );
			} 
		} else {
			$testimonials_loop = new WP_Query($atts);
		}
		
		return $testimonials_loop;
	}
	
	//load testimonials into an array and output a random one
	function outputRandomTestimonial($atts){
		//load shortcode attributes into an array
		$atts = shortcode_atts( array(
			'testimonials_link' => get_option('testimonials_link'),
			'count' => 1,
			'word_limit' => false,
			'body_class' => 'testimonial_body',
			'author_class' => 'testimonial_author',
			'show_title' => 0,
			'short_version' => false,
			'use_excerpt' => false,
			'category' => '',
			'show_thumbs' => get_option('testimonials_image'),
			'show_rating' => false,
			'theme' => get_option('testimonials_style', 'default_style'),
			'show_date' => false,
			'show_other' => false,
			'width' => false,
			'hide_view_more' => 0
		), $atts );
		
		ob_start();
		
		
		//load testimonials
		$testimonials_loop = $this->get_testimonials_loop( array( 'post_type' => 'testimonial','posts_per_page' => $atts['count'], 'easy-testimonial-category' => $atts['category'], 'orderby' => 'rand') );
		
		//
		
		$testimonials = $testimonials_loop->get_posts();
		
		//loop through and output testimonials
		foreach($testimonials as $testimonial){
			$data = array(
				'testimonial' => $testimonial,
				'atts' => $atts
			);
			
			$testimonial = new GP_Testimonial($data, $this->config);
			
			//output the testimonials HTML
			$testimonial->render();
		}
		
		wp_reset_postdata();
		
		//capture the content from the output buffer`
		$content = ob_get_contents();
		ob_end_clean();
		
		//return the content, with the filter applied
		return apply_filters('easy_t_random_testimonials_html', $content);
	}

	//output specific testimonial
	function outputSingleTestimonial($atts){ 
		//load shortcode attributes into an array
		$atts = shortcode_atts( array(
			'testimonials_link' => get_option('testimonials_link'),
			'show_title' => 0,
			'body_class' => 'testimonial_body',
			'author_class' => 'testimonial_author',
			'id' => '',
			'use_excerpt' => false,
			'show_thumbs' => get_option('testimonials_image'),
			'short_version' => false,
			'word_limit' => false,
			'show_rating' => false,
			'theme' => get_option('testimonials_style', 'default_style'),
			'show_date' => false,
			'show_other' => false,
			'width' => false,
			'hide_view_more' => 0
		), $atts );
						
		//load testimonials
		$testimonial = get_post( $atts['id'], OBJECT );
		
		//prep content for return
		$content = "";
		
		//return nothing if a bad ID is passed (no testimonial found)
		if(!empty($testimonial)){		
			//create a new testimonial object by passing in the current testimonial			
			$data = array(
				'testimonial' => $testimonial,
				'atts' => $atts
			);
			
			$testimonial = new GP_Testimonial($data, $this->config);
			
			ob_start();
			
			//output the testimonials HTML
			$testimonial->render();		
			$content = ob_get_contents();
			
			ob_end_clean();
		}
		
		return apply_filters( 'easy_t_single_testimonial_html', $content);
	}

	//output all testimonials
	function outputTestimonials($atts){ 
		
		//load shortcode attributes into an array
		$atts = shortcode_atts( array(	
			'testimonials_link' => '',//get_option('testimonials_link'),
			'show_title' => 0,
			'count' => -1,
			'body_class' => 'testimonial_body',
			'author_class' => 'testimonial_author',
			'id' => '',
			'use_excerpt' => false,
			'category' => '',
			'show_thumbs' => get_option('testimonials_image'),
			'short_version' => false,
			'orderby' => 'date',//'none','ID','author','title','name','date','modified','parent','rand','menu_order'
			'order' => 'ASC',//'DESC'
			'show_rating' => false,
			'paginate' => false,
			'testimonials_per_page' => 10,
			'theme' => get_option('testimonials_style', 'default_style'),
			'show_date' => false,
			'show_other' => false,
			'width' => false,
			'hide_view_more' => true
		), $atts );
		
		extract($atts);
				
		//if a bad value is passed for count, set it to -1 to load all testimonials
		//if $paginate is set to "all", this shortcode was made from a widget 
		//and we need to set the count to -1 to load all testimonials
		if(!is_numeric($count) || $paginate == "all"){
			$count = -1;
		}
		
		//if we are paging the testimonials, set the $count to the number of testimonials per page
		//sometimes $paginate is set, but is set to "all" (from the Widget) -
		//this indicates that we want to show every testimonial and not page them
		if($paginate && $paginate != "all"){
			$count = $testimonials_per_page;
		}
		
		$i = 0;
		
		//build query args for wp_query
		//query args
		$args = array( 'post_type' => 'testimonial','posts_per_page' => $count, 'easy-testimonial-category' => $category, 'orderby' => $orderby, 'order' => $order);
		
		// handle paging
		$nopaging = ($testimonials_per_page <= 0);

		$testimonial_page = 1;
		if ( get_query_var('testimonial_page') ) {
			$testimonial_page = get_query_var('testimonial_page');
		}	
		$paged = $testimonial_page;
		
		if (!$nopaging && $paginate && $paginate != "all") {
			//if $nopaging is false and $paginate is true, or max (but not "all"), then $testimonials_per_page is greater than 0 and the user is trying to paginate them
			//sometimes paginate is true, or 1, or max -- they all indicate the same thing.  "max" comes from the widget, true or 1 come from the shortcode / old instructions
			$args['posts_per_page'] = $testimonials_per_page;
			$args['paged'] = $paged;
		}
		
		ob_start();
		
		//load testimonials
		$testimonials_loop = $this->get_testimonials_loop($args);
		
		$testimonials = $testimonials_loop->get_posts();
		
		foreach($testimonials as $this_testimonial){
			//create a new testimonial object by passing in the current testimonial			
			$data = array(
				'testimonial' => $this_testimonial,
				'atts' => $atts
			);
			
			$testimonial = new GP_Testimonial($data, $this->config);
			
			//output the testimonials HTML
			$testimonial->render();
		}
		
		//output the pagination links, if instructed to do so
		//TBD: make all labels controllable via settings
		if($paginate){
			$pagination_link_template = $this->get_pagination_link_template('testimonial_page');
			
			?>
			<div class="easy_t_pagination">                               
				<?php
				echo paginate_links( array(
					'base' => $pagination_link_template,
					'format' => '?testimonial_page=%#%',
					'current' => max( 1, $paged ),
					'total' => $testimonials_loop->max_num_pages
				) );
				?>
			</div>  
			<?php
		}
		
		wp_reset_postdata();
		
		$content = ob_get_contents();
		ob_end_clean();	
		
		return apply_filters('easy_t_testimonials_html', $content);
	}

	function easy_t_add_pagination_query_var($query_vars)
	{
		$query_vars[] = 'testimonial_page';		
		return $query_vars;
	}	

	/* 
	 * Returns an URL template that can be passed as the 'base' param 
	 * to WP's paginate_links function
	 * 
	 * Note: This function is based on WordPress' get_pagenum_link. 
	 * It allows the query string argument to changed from 'paged'
	 */
	function get_pagination_link_template( $arg = 'testimonial_page' )
	{
		$request = remove_query_arg( $arg );
		
		$home_root = parse_url(home_url());
		$home_root = ( isset($home_root['path']) ) ? $home_root['path'] : '';
		$home_root = preg_quote( $home_root, '|' );

		$request = preg_replace('|^'. $home_root . '|i', '', $request);
		$request = preg_replace('|^/+|', '', $request);

		$base = trailingslashit( get_bloginfo( 'url' ) );

		$result = add_query_arg( $arg, '%#%', $base . $request );
		$result = apply_filters( 'easy_t_get_pagination_link_template', $result );
		
		return esc_url_raw( $result );
	}	

	/*
	 * Displays a grid of testimonials, with the requested number of columns
	 *
	 * @param array $atts Shortcode options. These include the [testimonial]
						  shortcode attributes, which are passed through.
	 *
	 * @return string HTML representing the grid of testimonials.
	 */
	function easy_t_testimonials_grid_shortcode($atts)
	{
		// load shortcode attributes into an array
		// note: these are mostly the same attributes as [testimonials] shortcode
		$atts = shortcode_atts( array(
			'testimonials_link' => '',//get_option('testimonials_link'),
			'show_title' => 0,
			'count' => -1,
			'body_class' => 'testimonial_body',
			'author_class' => 'testimonial_author',
			'id' => '',
			'ids' => '', // i've heard it both ways
			'use_excerpt' => false,
			'category' => '',
			'show_thumbs' => NULL,
			'short_version' => false,
			'orderby' => 'date',//'none','ID','author','title','name','date','modified','parent','rand','menu_order'
			'order' => 'ASC',//'DESC'
			'show_rating' => false,
			'paginate' => false,
			'testimonials_per_page' => 10,
			'theme' => get_option('testimonials_style', 'default_style'),
			'show_date' => false,
			'show_other' => false,
			'width' => false,
			'cols' => 3, // 1-10
			'grid_width' => false,
			'grid_spacing' => false,
			'grid_class' => '',
			'cell_width' => false,
			'responsive' => true,
			'equal_height_rows' => false,
			'hide_view_more' => 0
		), $atts );
		
		extract( $atts );
		
		// allow ids or id to be passed in
		if ( empty($id) && !empty($ids) ) {
			$id = $ids;
		}
				
		//if a bad value is passed for count, set it to -1 to load all testimonials
		//if $paginate is set to "all", this shortcode was made from a widget 
		//and we need to set the count to -1 to load all testimonials
		if(!is_numeric($count) || $paginate == "all"){
			$count = -1;
		}
		
		//if we are paging the testimonials, set the $count to the number of testimonials per page
		//sometimes $paginate is set, but is set to "all" (from the Widget) -
		//this indicates that we want to show every testimonial and not page them
		if($paginate && $paginate != "all"){
			$count = $testimonials_per_page;
		}
		
		$testimonials_output = '';
		$col_counter = 1;
		$row_counter = 0;
		
		if ($equal_height_rows) {
			wp_enqueue_script('easy-testimonials-grid');
		}
		
		if ( empty($rows) ) {
			$rows  = -1;
		}
		
		// make sure $cols is between 1 and 10
		$cols = max( 1, min( 10, intval($cols) ) );
		
		// create CSS for cells (will be same on each cell)
		$cell_style_attr = '';
		$cell_css_rules = array();

		if ( !empty($grid_spacing) && intval($grid_spacing) > 0 ) {
			$coefficient = intval($grid_spacing) / 2;
			$unit = ( strpos($grid_spacing, '%') !== false ) ? '%' : 'px';
			$cell_margin = $coefficient . $unit;			
			$cell_css_rules[] = sprintf('margin-left: %s', $cell_margin);
			$cell_css_rules[] = sprintf('margin-right: %s', $cell_margin);			
		}

		if ( !empty($cell_width) && intval($cell_width) > 0 ) {
			$cell_css_rules[] = sprintf('width: %s', $cell_width);
		}

		$cell_style_attr = !empty($cell_css_rules) ? sprintf('style="%s"', implode(';', $cell_css_rules) ) : '';
		
		// combine the rules into a re-useable opening <div> tag to be used for each cell
		$cell_div_start = sprintf('<div class="easy_testimonials_grid_cell" %s>', $cell_style_attr);
		
		// grab all requested testimonials and build one cell (in HTML) for each
		// note: using WP_Query instead of get_posts in order to respect pagination
		//    	 more info: http://wordpress.stackexchange.com/a/191934
		$args = array(
			'post_type' => 'testimonial',
			'posts_per_page' => $count,
			'easy-testimonial-category' => $category,
			'orderby' => $orderby,
			'order' => $order
		);
		
		// handle paging
		$nopaging = ($testimonials_per_page <= 0);
		$paged = !empty($_REQUEST['testimonial_page']) && intval($_REQUEST['testimonial_page']) > 0 ? intval($_REQUEST['testimonial_page']) : 1;
		if (!$nopaging && $paginate && $paginate != "all") {
			//if $nopaging is false and $paginate is true, or max (but not "all"), then $testimonials_per_page is greater than 0 and the user is trying to paginate them
			//sometimes paginate is true, or 1, or max -- they all indicate the same thing.  "max" comes from the widget, true or 1 come from the shortcode / old instructions
			$args['posts_per_page'] = $testimonials_per_page;
			$args['paged'] = $paged;
		}
		
		// restrict to specific posts if requested
		if ( !empty($id) ) {
			$args['post__in'] = array_map('intval', explode(',', $id));
		}
		
		$testimonials_loop = $this->get_testimonials_loop($args);
		
		$testimonials = $testimonials_loop->get_posts();
		
		$in_row = false;
		foreach( $testimonials as $this_testimonial ) {

			if ($col_counter == 1) {
				$in_row = true;
				$row_counter++;
				$testimonials_output .= sprintf('<div class="easy_testimonials_grid_row easy_testimonials_grid_row_%d">', $row_counter);
			}
					
			$testimonials_output .= $cell_div_start;
		
			//create a new testimonial object by passing in the current testimonial			
			$data = array(
				'testimonial' => $this_testimonial,
				'atts' => $atts
			);
			
			$testimonial = new GP_Testimonial($data, $this->config);
			
			//load output into variable to concatenate
			ob_start();
			$testimonial->render();
			$ob_content = ob_get_contents();
			ob_end_clean();
		
			$testimonials_output .= $ob_content;
			
			$testimonials_output .= '</div>';

			if ($col_counter == $cols) {
				$in_row = false;
				$testimonials_output .= '</div><!--easy_testimonials_grid_row-->';
				$col_counter = 1;
			} else {
				$col_counter++;
			}
		} // endwhile;
		
		// close any half finished rows
		if ($in_row) {
			$testimonials_output .= '</div><!--easy_testimonials_grid_row-->';
		}
		
		//output the pagination links, if instructed to do so
		//TBD: make all labels controllable via settings
		if($paginate){
			$pagination_link_template = $this->get_pagination_link_template('testimonial_page');
			
			$testimonials_output .= '<div class="easy_t_pagination">';                           
			$testimonials_output .= paginate_links(array(
										'base' => $pagination_link_template,
										'format' => '?testimonial_page=%#%',
										'current' => max( 1, $paged ),
										'total' => $testimonials_loop->max_num_pages
									));
			$testimonials_output .= '</div>  ';
		}
		
		// restore globals to their original values (i.e, $post and friends)
		wp_reset_postdata();
			
		// setup the grid's CSS, insert the grid of testimonials (the cells) 
		// into the grid, add a clearing div, and return the whole thing
		$grid_classes = array(
			'easy_testimonials_grid',
			'easy_testimonials_grid_' . $cols
		);
		
		if ($responsive) {
			$grid_classes[] = 'easy_testimonials_grid_responsive';
		}
		
		if ($equal_height_rows) {
			$grid_classes[] = 'easy_testimonials_grid_equal_height_rows';
		}	

		// add any grid classes specified by the user
		if ( !empty($grid_class) ) {
			$grid_classes = array_merge( $grid_classes, explode(' ', $grid_class) );
		}
		
		// combine all classes into an class attribute
		$grid_class_attr = sprintf( 'class="%s"', implode(' ', $grid_classes) );
		
		// add all style rules for the grid (currently, only specifies width)
		$grid_css_rules = array();
		if ( !empty($grid_width) && intval($grid_width) > 0 ) {
			$grid_css_rules[] = sprintf('width: %s', $grid_width);
		}
		
		// combine all CSS rules into an HTML style attribute
		$grid_style_attr = sprintf( 'style="%s"', implode(';', $grid_css_rules) );
			
		// add classes and CSS rules to the grid, insert cells, return result
		$grid_template = '<div %s %s>%s</div>';
		$grid_html = sprintf($grid_template, $grid_class_attr, $grid_style_attr, $testimonials_output);
		return $grid_html;
	}

	//output a single testimonial for each theme_array
	//useful for demoing all of the themes or testing compatibility on a given website
	//output all testimonials
	function outputAllThemes($atts){ 			
		//load shortcode attributes into an array
		$atts = shortcode_atts( array(	
			'testimonials_link' => '',//get_option('testimonials_link'),
			'show_title' => 0,
			'count' => 1,
			'body_class' => 'testimonial_body',
			'author_class' => 'testimonial_author',
			'id' => '',
			'use_excerpt' => false,
			'category' => '',
			'show_thumbs' => get_option('testimonials_image'),
			'short_version' => false,
			'orderby' => 'date',//'none','ID','author','title','name','date','modified','parent','rand','menu_order'
			'order' => 'ASC',//'DESC'
			'show_rating' => false,
			'paginate' => false,
			'testimonials_per_page' => 10,
			'theme' => get_option('testimonials_style', 'default_style'),
			'show_date' => false,
			'show_other' => false,
			'show_free_themes' => false,
			'width' => false
		), $atts );
				
		extract($atts);
				
		ob_start();
				
		//load testimonials
		$testimonials_loop = $this->get_testimonials_loop(array( 'post_type' => 'testimonial','posts_per_page' => $count, 'easy-testimonial-category' => $category, 'orderby' => $orderby, 'order' => $order, 'paged' => get_query_var( 'paged' )));

		$testimonials = $testimonials_loop->get_posts();
		
		if($show_free_themes){
			$free_theme_array = $this->config->free_theme_array;
			
			foreach($free_theme_array as $theme_slug => $theme_name){	
				
				$atts['theme'] = $theme_slug;

				foreach($testimonials as $this_testimonial){
					//create a new testimonial object by passing in the current testimonial			
					$data = array(
						'testimonial' => $this_testimonial,
						'atts' => $atts
					);
					
					$testimonial = new GP_Testimonial($data, $this->config);

					//output the testimonials HTML
					$testimonial->render();				
				}
					
				wp_reset_postdata();
			}
		}
		
		$pro_theme_array = $this->config->pro_theme_array;
		
		foreach($pro_theme_array as $theme_set => $theme_set_array){
			foreach($theme_set_array as $theme_slug => $theme_name){
				$atts['theme'] = $theme_slug;

				foreach($testimonials as $this_testimonial){
					//create a new testimonial object by passing in the current testimonial			
					$data = array(
						'testimonial' => $this_testimonial,
						'atts' => $atts
					);
					
					$testimonial = new GP_Testimonial($data, $this->config);

					//output the testimonials HTML
					$testimonial->render();				
				}	
				
				wp_reset_postdata();
			}
		}
		
		$content = ob_get_contents();
		ob_end_clean();	
		
		return apply_filters('easy_t_testimonials_html', $content);
	}

	//output all testimonials for use in JS widget
	function outputTestimonialsCycle($atts){ 	
		//load shortcode attributes into an array
		$atts = shortcode_atts( array(
			'testimonials_link' => get_option('testimonials_link'),
			'show_title' => 0,
			'count' => -1,
			'transition' => 'scrollHorz',
			'show_thumbs' => get_option('testimonials_image'),
			'timer' => '2000',
			'container' => false,//deprecated, use auto_height instead
			'use_excerpt' => false,
			'auto_height' => false,
			'category' => '',
			'body_class' => 'testimonial_body',
			'author_class' => 'testimonial_author',
			'random' => '',
			'orderby' => 'date',//'none','ID','author','title','name','date','modified','parent','rand','menu_order'
			'order' => 'ASC',//'DESC'
			'pager' => false,
			'show_pager_icons' => false,
			'show_rating' => false,
			'testimonials_per_slide' => 1,
			'theme' => get_option('testimonials_style', 'default_style'),
			'show_date' => false,
			'show_other' => false,
			'pause_on_hover' => false,
			'prev_next' => false,
			'width' => false,
			'paused' => false,
			'display_pagers_above' => false,
			'hide_view_more' => 0,
			'show_log' => ( defined('WP_DEBUG') && true === WP_DEBUG ) ? 1 : 0
		), $atts );

		extract($atts);
				
		if(!is_numeric($count)){
			$count = -1;
		}
		
		ob_start();
		
		$i = 0;
		
		if(!$this->config->is_pro && !in_array($transition, array('fadeOut','fade','scrollHorz'))){
			$transition = 'fadeout';
		}
		
		//use random WP query to be sure we aren't just randomly sorting a chronologically queried set of testimonials
		//this prevents us from just randomly ordering the same 5 testimonials constantly!
		if($random){
			$orderby = "rand";
		}

		//determine if autoheight is set to container or to calculate
		//not sure why i did this so backwards to begin with!  oh well...
		if($container){
			$container = "container";
		}
		if($auto_height == "calc"){
			$container = "calc";
		} else if($auto_height == "container"){
			$container = "container";
		}
		
		//generate a random number to have a unique wrapping class on each slideshow
		//this should prevent controls that effect more than one slideshow on a page
		$target = rand();
		
		//use the width for the slideshow wrapper, to keep the previous/next buttons and pager icons within the desired layout
		$width = $width ? 'style="width: ' . $width . '"' : 'style="width: ' . get_option('easy_t_width','') . '"';
		
		//load testimonials
		$testimonials_loop = $this->get_testimonials_loop(array( 'post_type' => 'testimonial','posts_per_page' => $count, 'orderby' => $orderby, 'order' => $order, 'easy-testimonial-category' => $category));
		//for tracking number of testimonials in this loop
		$count = $testimonials_loop->post_count;

		$testimonials = $testimonials_loop->get_posts();
		
		?>
		<div class="easy-t-slideshow-wrap <?php echo "easy-t-{$target}";?>" <?php echo $width; ?>>
		
			<?php //only display cycle controls if there is more than one testimonial ?>
			<?php if($display_pagers_above && $count > 1): ?>
			<div class="easy-t-cycle-controls">				
				<?php if($prev_next):?><div class="cycle-prev easy-t-cycle-prev"><?php echo get_option('easy_t_previous_text', '<< Prev'); ?></div><?php endif; ?>
				<?php if($pager || $show_pager_icons ): ?>
					<div class="easy-t-cycle-pager"></div>
				<?php endif; ?>
				<?php if($prev_next):?><div class="cycle-next easy-t-cycle-next"><?php echo get_option('easy_t_next_text', 'Next >>'); ?></div><?php endif; ?>			
			</div>	
			<?php endif; ?>
				
			<?php
				//thanks, wpgaijin
				//https://wordpress.org/support/topic/still-got-bugs
				$data_cycle_array = array(
					'data_cycle_fx'             => 'data-cycle-fx="'. $transition .'"',
					'data_cycle_timeout'        => 'data-cycle-timeout="'. $timer . '"',
					'data_cycle_slides'         => 'data-cycle-slides="div.testimonial_slide"',
					'data_cycle_auto_height'    => ( $container ) ? 'data-cycle-auto-height="' . $container .'"' : '',
					'data_cycle_random'         => ( $random ) ? 'data-cycle-random="true"' : '',
					'data_cycle_pause_on_hover' => ( $pause_on_hover ) ? 'data-cycle-pause-on-hover="true"' : '',
					'data_cycle_paused'         => ( $paused ) ? 'data-cycle-paused="true"' : '',
					'data_cycle_prev'           => ( $prev_next ) ? 'data-cycle-prev=".easy-t-' . $target .' .easy-t-cycle-prev"' : '',
					'data_cycle_next'           => ( $prev_next ) ? 'data-cycle-next=".easy-t-' . $target .' .easy-t-cycle-next"' : '',
					'data_cycle_pager'          => ( $pager || $show_pager_icons ) ? 'data-cycle-pager=".easy-t-'. $target .' .easy-t-cycle-pager"' : '',
					'data-cycle-log'			=> ( !$show_log ) ? 'data-cycle-log="false"' : '',
					'data-cycle-fix-carousel'	=> ( $transition == "carousel" ) ? 'data-cycle-fix-carousel="1"' : ''
				);
				
				if ($transition == "carousel"){		
					$data_cycle_array['data-cycle-fix-carousel-visible'] = 'data-cycle-carousel-visible="'.$testimonials_per_slide.'"';
					$data_cycle_array['data-cycle-fix-carousel-fluid'] = 'data-cycle-carousel-fluid="true"';
				}

				$data_cycle = implode( ' ', $data_cycle_array );
				$data_cycle = rtrim( $data_cycle );
				?>
				<div class="cycle-slideshow" <?php echo $data_cycle; ?>>
			<?php
			
			$counter = 0;			

			foreach($testimonials as $this_testimonial){
				//hide all but the first slide
				if($counter == 0){
					$testimonial_display = '';
				} else {
					$testimonial_display = 'style="display:none;"';
				}
				
				//create slide div
				//if this is a carousel, bypass the wrapping multiple testimonials in one slide step
				if($counter%$testimonials_per_slide == 0 || $transition == "carousel"){
					echo "<div {$testimonial_display} class=\"testimonial_slide\">";
				}
				
				$counter ++;
				
				//create a new testimonial object by passing in the current testimonial			
				$data = array(
					'testimonial' => $this_testimonial,
					'atts' => $atts
				);
				
				$testimonial = new GP_Testimonial($data, $this->config);

				//output the testimonials HTML
				$testimonial->render();				
				
				//close slide
				//if this is a carousel, bypass the wrapping multiple testimonials in one slide step
				if($counter%$testimonials_per_slide == 0 || $transition == "carousel"){
					echo "</div>";
				}
			}
			
			wp_reset_postdata();
			
			?>
			</div>
			
			<?php //only display cycle controls if there is more than one testimonial ?>
			<?php if(!$display_pagers_above && $count > 1): ?>
			<div class="easy-t-cycle-controls">				
				<?php if($prev_next):?><div class="cycle-prev easy-t-cycle-prev"><?php echo get_option('easy_t_previous_text', '<< Prev'); ?></div><?php endif; ?>
				<?php if($pager || $show_pager_icons ): ?>
					<div class="easy-t-cycle-pager"></div>
				<?php endif; ?>
				<?php if($prev_next):?><div class="cycle-next easy-t-cycle-next"><?php echo get_option('easy_t_next_text', 'Next >>'); ?></div><?php endif; ?>			
			</div>	
			<?php endif; ?>
			
		</div><!-- end slideshow wrap --><?php
		
		$content = ob_get_contents();
		ob_end_clean();	
		
		return apply_filters( 'easy_t_testimonials_cyle_html', $content);
	}

	//things to do on plugin activation
	function easy_testimonials_activation_hook() {
		//flush rewrite rules
		$this->easy_testimonials_setup_testimonials();
		flush_rewrite_rules();
				
		// make sure the welcome screen gets seen again
		$this->Aloha->reset_welcome_screen();	
	}

	//register any widgets here
	function easy_testimonials_register_widgets() {
		include('include/widgets/random_testimonial_widget.php');
		include('include/widgets/single_testimonial_widget.php');
		include('include/widgets/testimonial_cycle_widget.php');
		include('include/widgets/testimonial_list_widget.php');
		include('include/widgets/testimonial_grid_widget.php');
		include('include/widgets/submit_testimonial_widget.php');

		register_widget( 'randomTestimonialWidget' );
		register_widget( 'cycledTestimonialWidget' );
		register_widget( 'listTestimonialsWidget' );
		register_widget( 'singleTestimonialWidget' );
		register_widget( 'submitTestimonialWidget' );
		register_widget( 'TestimonialsGridWidget' );
	}

	function easy_testimonials_admin_init($hook)
	{	
		//RWG: only enqueue scripts and styles on Easy T admin pages or widgets page
		$screen = get_current_screen();
		
		if ( strpos($hook,'easy-testimonials') !== false || 
			 $screen->id === "widgets" || 
			(function_exists('is_customize_preview') && is_customize_preview()) )
		{
			wp_register_style( 'easy_testimonials_admin_stylesheet', plugins_url('include/assets/css/admin_style.css', __FILE__) );
			wp_enqueue_style( 'easy_testimonials_admin_stylesheet' );
			wp_enqueue_script(
				'easy-testimonials-admin',
				plugins_url('include/assets/js/easy-testimonials-admin.js', __FILE__),
				array( 'jquery' ),
				false,
				true
			); 
			wp_enqueue_script(
				'gp-admin_v2',
				plugins_url('include/assets/js/gp-admin_v2.js', __FILE__),
				array( 'jquery' ),
				false,
				true
			);	
		}
		
		//RWG: include pro styles on Theme Selection screen, for preview purposes
		if(strpos($hook,'easy-testimonials-style-settings')!==false){
			//basic styling
			wp_register_style( 'easy_testimonial_style', plugins_url('include/assets/css/style.css', __FILE__) );
			wp_enqueue_style( 'easy_testimonial_style' );
			
			//register and enqueue pro themes for preview purposes
			$this->easy_t_register_pro_themes();;
		}
		
		// also include some styles on *all* admin pages
		wp_register_style( 'easy_testimonials_admin_stylesheet_global', plugins_url('include/assets/css/admin_style_global.css', __FILE__) );
		wp_enqueue_style( 'easy_testimonials_admin_stylesheet_global' );		
	}
	
	//check for installed plugins with known conflicts
	//if any are found, display appropriate messaging with suggested steps
	//currently only checks for woothemes testimonials
	function easy_testimonials_conflict_check($hook_suffix){
	
		/* Avada Check */
		$my_theme = wp_get_theme();
		if( strpos( $my_theme->get('Name'), "Avada" ) === 0 ) {
			// looks like we are using Avada! 
			// make sure we have avada compatibility enabled. If not, show a warning!
			if(!get_option('easy_t_avada_filter_override', false)){
				add_action('admin_notices', array($this, 'easy_t_avada_admin_notice') );
			}
		}

		// only run the rest of the checks on Easy Testimonials pages
		if (strpos($hook_suffix,'easy-testimonials') === false) {
			return;
		}

		/* WooThemes Testimonials Check */
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );	
		$woothemes_testimonials = "testimonials-by-woothemes/woothemes-testimonials.php";
		
		if(is_plugin_active($woothemes_testimonials)){//woothemes testimonials found		
			add_action('admin_notices', array($this, 'easy_t_woothemes_testimonials_admin_notice') );
		}
		
		/* WP Engine Check */
		if( class_exists( 'WpeCommon' ) ){
			add_action('admin_notices', array($this, 'easy_t_wpengine_admin_notice') );
		}
	}

	//output warning message about wpengine conflicts
	function easy_t_wpengine_admin_notice(){
		echo '<div class="gp_error fade"><p>';
		echo '<strong>ALERT:</strong> We have detected that this site us running on WP Engine.<br/><br/>  Random Testimonials will not work if you have disabled Random SQL Queries under your WP Engine Options.';
		echo "</p></div>";
	}

	//output warning message about woothemes testimonials conflicts
	function easy_t_woothemes_testimonials_admin_notice(){
		echo '<div class="gp_error fade"><p>';
		echo '<strong>ALERT:</strong> We have detected that Testimonials by WooThemes is installed.<br/><br/>  This plugin has known conflicts with Easy Testimonials. To prevent any issues, we recommend deactivating Testimonials by WooThemes while using Easy Testimonials.';
		echo "</p></div>";
	}

	//output warning message about avada conflicts
	function easy_t_avada_admin_notice() {
		echo '<div class="gp_error fade"><p>';
		echo '<strong>ALERT:</strong> Easy Testimonials has detected that Avada by Theme Fusion is installed.<br/><br/>  To ensure compatibility, please <a href="?page=easy-testimonials-settings#tab-compatibility_options">visit our Compatibility Options</a> on the Basic Settings tab and verify that "Override Avada Blog Post Content Filter on Testimonials" is checked.';
		echo "</p></div>";
	}

	//add an inline link to the settings page, before the "deactivate" link
	function add_settings_link_to_plugin_action_links($links) { 
	  $settings_link = '<a href="admin.php?page=easy-testimonials-settings">Settings</a>';
	  array_unshift($links, $settings_link); 
	  return $links; 
	}

	// add inline links to our plugin's description area on the Plugins page
	function add_custom_links_to_plugin_description($links, $file) { 
	 
		/** Check if the plugin file name matches the passed $file name */
		if ( $file == $this->plugin )
		{		
			$new_links['settings_link'] = '<a href="admin.php?page=easy-testimonials-settings">Settings</a>';
			$new_links['support_link'] = '<a href="https://goldplugins.com/contact/?utm-source=plugin_menu&utm_campaign=support&utm_banner=bananaphone" target="_blank">Get Support</a>';
				
			if(!$this->config->is_pro){
				$new_links['upgrade_to_pro'] = '<a href="https://goldplugins.com/our-plugins/easy-testimonials-details/upgrade-to-easy-testimonials-pro/?utm_source=plugin_menu&utm_campaign=upgrade" target="_blank">Upgrade to Pro</a>';
			}
			
			$links = array_merge( $links, $new_links);
		}
		return $links; 
	}
		
	/* Displays a meta box with the shortcodes to display the current testimonial */
	function easy_t_display_shortcodes_meta_box() {
		global $post;
		echo "<strong>To display this testimonial</strong>, add this shortcode to any post or page:<br />";	
		$ex_shortcode = sprintf('[single_testimonial id="%d"]', $post->ID);	
		printf('<textarea class="gp_highlight_code">%s</textarea>', $ex_shortcode);
	}

	/* Styling Functions */

	function list_required_google_fonts()
	{
		// check each typography setting for google fonts, and build a list
		$option_keys = array(
			'easy_t_body_font_family',
			'easy_t_author_font_family',
			'easy_t_position_font_family',
			'easy_t_date_font_family',
			'easy_t_rating_font_family'		
		);  
		$fonts = array();
		foreach ($option_keys as $option_key) {
			$option_value = get_option($option_key);
			if (strpos($option_value, 'google:') !== FALSE) {
				$option_value = str_replace('google:', '', $option_value);
				
				//only add the font to the array if it was in fact a google font
				$fonts[$option_value] = $option_value;				
			}
		}
		return $fonts;
	}
		
	// Enqueue any needed Google Web Fonts
	function enqueue_webfonts()
	{
		$cache_key = '_easy_t_webfont_str';
		$font_str = get_transient($cache_key);
		if ($font_str == false) {
			$font_list = $this->list_required_google_fonts();
			if ( !empty($font_list) ) {
				$font_list_encoded = array_map('urlencode', $font_list);
				$font_str = implode('|', $font_list_encoded);
			} else {
				$font_str = 'x';
			}
			set_transient($cache_key, $font_str);		
		}
		
		//don't register this unless a font is set to register
		if(strlen($font_str)>2){
			$protocol = is_ssl() ? 'https:' : 'http:';
			$font_url = $protocol . '//fonts.googleapis.com/css?family=' . $font_str;
			wp_register_style( 'easy_testimonials_webfonts', $font_url);
			wp_enqueue_style( 'easy_testimonials_webfonts' );
		}
	}

	//checks for registered shortcodes and displays alert on settings screen if there are any current conflicts
	function easy_testimonials_shortcode_checker(array $atts){
		//TBD
	}

	//search form shortcode
	function easy_t_search_form_shortcode()
	{
		add_filter('get_search_form', array($this, 'easy_t_restrict_search_to_custom_post_type'), 10);
		$search_html = get_search_form();
		remove_filter('get_search_form', array($this, 'easy_t_restrict_search_to_custom_post_type'));
		return $search_html;
	}

	function easy_t_restrict_search_to_custom_post_type($search_html)
	{
		$post_type = 'testimonial';
		$hidden_input = sprintf('<input type="hidden" name="post_type" value="%s">', $post_type);
		$replace_with = $hidden_input . '</form>';
		return str_replace('</form>', $replace_with, $search_html);
	}

	/* Avada Compatibility */	
	//make our own version of the avada blog post content function that doesn't run if the current post type is a testimonial
	//since avada uses !function_exists correctly, our function will be declared first and will win!
	function avada_render_blog_post_content() {
		global $post;
		if($post->post_type != "testimonial"){
			if ( is_search() && Avada()->settings->get( 'search_excerpt' ) ) {
				return;
			}
			echo fusion_get_post_content();
		}
	}
	
	//make our own version of the avada post title function that doesn't run if the current post type is a testimonial
	function avada_render_post_title( $post_id = '', $linked = TRUE, $custom_title = '', $custom_size = '2' ) {
		global $post;
		if($post->post_type != "testimonial"){
			$entry_title_class = '';

			// Add the entry title class if rich snippets are enabled
			if ( ! Avada()->settings->get( 'disable_date_rich_snippet_pages' ) ) {
				$entry_title_class = ' class="entry-title"';
			}

			// If we have a custom title, use it
			if ( $custom_title ) {
				$title = $custom_title;
			// Otherwise get post title
			} else {
				$title = get_the_title( $post_id );
			}

			// If the post title should be linked at the markup
			if ( $linked ) {
				$link_target = '';
				if( fusion_get_page_option( 'link_icon_target', $post_id ) == 'yes' ||
					fusion_get_page_option( 'post_links_target', $post_id ) == 'yes' ) {
					$link_target = ' target="_blank"';
				}

				$title = sprintf( '<a href="%s"%s>%s</a>', get_permalink( $post_id ), $link_target, $title );
			}

			// Setup the HTML markup of the post title
			$html = sprintf( '<h%s%s>%s</h%s>', $custom_size, $entry_title_class, $title, $custom_size );

			return $html;
		}
	}
	
	function set_content_flag($content)
	{
		$this->config->set_content_flag(true);
		return $content;
	}

	function easy_t_avada_content_filter(){
		global $post;
		
		if($post->post_type == 'testimonial'){
			the_content();
		}
	}

}//end easyTestimonials

// create an instance of easyTestimonials
if ( !isset($easy_testimonials) ) {
	$easy_testimonials = new easyTestimonials();
}

// create an instance of BikeShed that we can use later
if (is_admin()) {
	global $EasyT_BikeShed;
	$EasyT_BikeShed = new Easy_Testimonials_GoldPlugins_BikeShed();
}