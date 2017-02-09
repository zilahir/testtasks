<?php
//load
class GP_Testimonial{
	var $testimonial;
	var $atts;
	var $config;
	var $cache_key;
	
	//$data can be false when this is used from the single testimonial content filter
	function __construct($data = false, $config = array()){	
		//setup data
		//if testimonial data is empty, setup a blank testimonial object	
		$this->testimonial = !empty($data['testimonial']) ? $data['testimonial'] : new stdClass;
		
		//store config data
		$this->config = $config;
		
		//setup atts
		$this->atts = $this->merge_default_attributes($data['atts']);
				
		//setup our custom excerpts
		add_filter('get_the_excerpt', array($this, 'easy_t_fix_testimonial_excerpts') );
		add_filter('excerpt_length', array($this, 'easy_t_excerpt_length') );
		add_filter('easy_testimonials_the_content', array($this, 'easy_testimonials_the_content_filter') );
		
		//if we have a testimonial, create a cache key
		if( isset($this->testimonial->ID) ){
			$this->cache_key =	"easy_t_" . $this->testimonial->ID . 
								md5(serialize($this->atts) . 
								$this->config->typography_cache_key);
		}
		
		//add any declared default atts from our theme
		//add_filter('easy_testimonials_default_attributes' , array($this, 'load_theme_atts'));
	}
	
	//renders this testimonial
	//uses transient caching
	function render(){
		$output = "";
		
		//if enabled, use cache
		if( $this->config->cache_enabled ){
			// Get any existing copy of our transient data
			if ( false === ($output = get_transient($this->cache_key)) ){
				// It wasn't there, so regenerate the data and save the transient
				$output .= $this->easy_t_get_single_testimonial_html();
				set_transient( $this->cache_key, $output, $this->config->cache_time );
			} 
		} else {
			$output .= $this->easy_t_get_single_testimonial_html();
		}
		
		echo $output;
	}
	
	//using the current theme set in the testimonial object
	//load the theme's header information
	//and set any relevant attributes
	function load_theme_atts(){
		//load currently selected theme
		$theme = $this->atts['theme'];
				
		//translate to the registered name
		//our registered theme names have a specific pattern they follow from the option value
		//easy_testimonials_$theme_style
		$theme = $theme . "_style";
		
		echo "theme: " . $theme;
				
		//load file data from top of css file
		$file_data = get_file_data($this->config->dir_path . $theme, $this->atts);
		
		return $file_data;
	}
	
	//TODO: change custom CSS box to output CSS this way: https://codex.wordpress.org/Function_Reference/wp_add_inline_style
	
	function merge_default_attributes($atts){
		$defaults = array(	
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
			'hide_view_more' => true,
			'meta_data_position' => get_option('meta_data_position') ? "above" : "below",
			'output_schema_markup' => get_option('easy_t_output_schema_markup', true)
		);
		
		//will be empty coming from the single content filter
		if(!is_array($atts)){
			$atts = array();
		}
		
		$merged_atts = array_merge($defaults, $atts);
		
		return apply_filters('easy_testimonials_default_attributes' , $merged_atts);
	}
	
	//runs when viewing a single testimonial's page (ie, you clicked on the continue reading link from the excerpt)
	function single_testimonial_content_filter($content){
		global $easy_t_in_widget;
		global $post;
				
		// Save the post data in a variable before resetting it. It *shouldn't* matter,
		// but some plugins might be depending on the global $post being left in whatever
		// state it was when we got here
		$old_post = $post;
		wp_reset_postdata();
		
		//not running in a widget, is running in a single view or archive view such as category, tag, date, the post type is a testimonial
		if ( empty($easy_t_in_widget) && (is_singular() || is_archive()) && get_post_type( $post->ID ) == 'testimonial' ) {				
			//stored needed values for reference
			$this->testimonial->ID = $post->ID;
			
			//build and return the single testimonial html		
			$content = $this->easy_t_get_single_testimonial_html( true );
		}

		// restore post data to its previous, possibly borked, form
		$post = $old_post;
		
		return $content;
	}

	//passed an array of acceptable shortcode attributes
	//this function will build a string of classes representing the chosen attributes
	//returns string ready for echoing as classes
	function easy_t_build_classes_from_atts($atts = array()){
		$class_string = "";
			
		foreach ($atts as $key => $value){
			$class_string .= " " . $value . "_" . $key;
		}
		
		return $class_string;
	}
	
	function easy_t_get_the_excerpt( $post_id )
	{		
		//preserve the old post data for other plugins/themes/etc.
		global $post;  
		$save_post = $post;
	  
		//run our own excerpt function that trims the excerpt without applying the content filter
		$post = get_post($post_id);
		
		if ( !empty($post->post_excerpt) ) {
			$excerpt_more = $this->easy_t_excerpt_more( '' , $post );
			$post_excerpt = apply_filters( 'easy_t_get_the_excerpt', $post->post_excerpt . $excerpt_more, $post );
		} else {
			$post_excerpt = '';
		}
		$output = $this->easy_t_trim_excerpt($post_excerpt , $post);
	  
		//reset global postdata to saved postdata
		$post = $save_post;
	  
		return $output;
	}	
	
	/* excerpt update 4.14 */
	/* Keep the extra info we've added with the_content filter from appearing in the excerpt*/
	//moved to construct 2.0
	//add_filter('get_the_excerpt', 'easy_t_fix_testimonial_excerpts');
	function easy_t_fix_testimonial_excerpts($excerpt)
	{
		global $post;
	
		$post = get_post();
		
		// if not a testimonial, move on
		if ( empty( $post ) || $post->post_type !== 'testimonial' ) {
			return $excerpt;
		}
		
		return wp_trim_words($excerpt, 20);
	}

	/**
	 * Our own version of wp_trim_excerpt that:
	*    1) can be run on any post (instead of only the global)
	*    2) doesn't run the_content filter
	*
	*  Else all is the same (runs all the normal filters, etc).
	*
	*  @param	$text	Excerpt, which will likely be empty. If empty, 
	*					it wil be generated in the normal way, except 
	*					without running the_content filter.
	*
	*  @param	$post	The post to use for the excerpt. If not provided, 
	*					global $post is used
	*
	*  @return	string	The excerpt (after wp_trim_excerpt has been applied).
	*
	*/
	function easy_t_trim_excerpt( $text = '', $post = false ) {
		if (!$post) {
			$post = get_post();
		}
		
		$raw_excerpt = $text;
		if ( '' == $text ) {			
			$text = $post->post_content;

			$text = strip_shortcodes( $text );

			/** This filter is documented in wp-includes/post-template.php */
			//$text = apply_filters( 'the_content', $text );
			$text = str_replace(']]>', ']]&gt;', $text);

			/**
			 * Filter the number of words in an excerpt.
			 *
			 * @since 2.7.0
			 *
			 * @param int $number The number of words. Default 55.
			 */
			$excerpt_length = apply_filters( 'excerpt_length', 55 );
			/**
			 * Filter the string in the "more" link displayed after a trimmed excerpt.
			 *
			 * @since 2.9.0
			 *
			 * @param string $more_string The string shown within the more link.
			 */
			add_filter( 'excerpt_more', array($this, 'easy_t_excerpt_more'), 9999, 2 );
			$excerpt_more = $this->easy_t_excerpt_more( '' , $post );
			$excerpt_more = apply_filters( 'excerpt_more', $excerpt_more );
			
			$text = wp_trim_words( $text, $excerpt_length, $excerpt_more );
			remove_filter( 'excerpt_more', array($this, 'easy_t_excerpt_more'), 9999 );
		}		
		
		/**	
		 * Filter the trimmed excerpt string.
		 *
		 * @since 2.8.0
		 *
		 * @param string $text        The trimmed text.
		 * @param string $raw_excerpt The text prior to trimming.
		 */
		return apply_filters( 'wp_trim_excerpt', $text, $raw_excerpt );
	}
	
	/* add customized continue reading link to testimonials, if set */
	function easy_t_excerpt_more( $more, $the_post = false ) {
		global $post;
		
		if ( empty($the_post) ) {
			$the_post = $post;
		}

		if(get_option('easy_t_link_excerpt_to_full', false)){
			return ' <a class="more-link" href="' . get_permalink( $the_post->ID ) . '">' . get_option('easy_t_excerpt_text') . '</a>';
		} else {
			return ' ' . get_option('easy_t_excerpt_text');
		}			
	}
	
	//checks to see if this is a testimonial
	//if it is, loads custom excerpt length and uses it
	//otherwise use current wordpress setting
	function easy_t_excerpt_length( $length ) {
		global $post;
		
		//if this is a testimonial, use our customization
		if($post->post_type == 'testimonial'){
			return get_option('easy_t_excerpt_length',55);
		}
		
		return $length;
	}
	
	//passed a string
	//finds a matching theme or loads the theme currently selected on the options page
	//returns appropriate class name string to match theme
	//if return_theme_base is true, returns the base string of the theme (without the style modifier)
	function easy_t_get_theme_class($theme_string, $return_theme_base = false){	
		$the_theme = get_option('testimonials_style', 'default_style');
		
		//if the theme string is passed
		if(strlen($theme_string)>2){
			//if the theme string is valid
			if(in_array($theme_string, $this->config->theme_array)){			
				//if returning theme base for pro themes, go ahead and do so now
				if( $return_theme_base ){
					//loop through the pro theme array
					foreach( $pro_theme_array as $pro_theme_base => $this_pro_theme_array ) {
						//if a matching key to our specific pro theme is found
						if(isset($this_pro_theme_array[$theme_string])){
							//return the base string of that pro theme, from the array
							return $pro_theme_base;
						}
					}
				}
				
				//use the theme string
				$the_theme = $theme_string;
			}
		}
		
		//remove style from the middle of our theme options and place it as a prefix
		//matching our CSS files
		$the_theme = str_replace('-style', '', $the_theme);
		$the_theme = "style-" . $the_theme;
		
		return $the_theme;
	}
	
	/*
	 * Assemble the json-ld review markup for an individual testimonial
	 * TBD: support for type of and image of item reviewed
	 */
	 function output_jsonld_markup($testimonial){			
		/* json ld example:
		<script type="application/ld+json">
			{
			  "@context": "http://schema.org/",
			  "@type": "Review",
			  "itemReviewed": {
				"@type": "Restaurant",
				"image": "http://www.example.com/seafood-restaurant.jpg",
				"name": "Legal Seafood"
			  },
			  "reviewRating": {
				"@type": "Rating",
				"ratingValue": "4"
			  },
			  "name": "A good seafood place.",
			  "author": {
				"@type": "Person",
				"name": "Bob Smith"
			  },
			  "reviewBody": "The seafood is great.",
			  "publisher": {
				"@type": "Organization",
				"name": "Washington Times"
			  }
			}
		</script>
		*/
		
		//prevent errors from unset rating		
		if( empty($testimonial['num_stars']) ){
			$testimonial['num_stars'] = 5;
		}
		
		ob_start();
		?>
		<script type="application/ld+json">
			{
			  "@context": "http://schema.org/",
			  "@type": "Review",
			  "itemReviewed": {
				"name": "<?php echo $this->easy_t_clean_html($testimonial['other']); ?>"
			  },
			  "reviewRating": {
				"@type": "Rating",
				"ratingValue": "<?php echo $testimonial['num_stars']; ?>"
			  },
			  "name": "<?php echo get_the_title($this->testimonial->ID); ?>",
			  "author": {
				"@type": "Person",
				"name": "<?php echo $this->easy_t_clean_html($testimonial['client']); ?>"
			  },
			  "reviewBody": "<?php echo strip_tags($testimonial['content']); ?>"
			}
		</script>
		<?php
		$content = ob_get_contents();
		ob_end_clean();
		
		return $content;
	 }
	
	/*
	 *  Assemble the html for the testimonials metadata taking into account current options
	 */
	function easy_testimonials_build_metadata_html($testimonial, $author_class, $show_date, $show_rating, $show_other)
	{
		$date_css = $this->easy_testimonials_build_typography_css('easy_t_date_');
		$position_css = $this->easy_testimonials_build_typography_css('easy_t_position_');
		$client_css = $this->easy_testimonials_build_typography_css('easy_t_author_');
		$other_css = $this->easy_testimonials_build_typography_css('easy_t_other_');
		//only build the stars CSS, ie the font color only
		//as the rating displayed by the metadata function is only ever stars
		$rating_css = $this->easy_testimonials_build_typography_css('easy_t_rating_', 'stars');
		
		//set the following variables to true if the option to display the associated item is true 
		//and the associated item has content in it 
		//(preventing outputting blank items that insert whitespace)
		$show_the_client = (strlen($testimonial['client'])>0) ? true : false;
		$show_the_position = (strlen($testimonial['position'])>0) ? true : false;
		$show_the_other = (strlen($testimonial['other'])>0 && $show_other) ? true : false;
		$show_the_date = (strlen($testimonial['date'])>0 && $show_date) ? true : false;
		$show_the_rating = (strlen($testimonial['num_stars'])>0 && ($show_rating == "stars")) ? true : false;
		
		?>
		<p class="<?php echo $author_class; ?>">
			<?php //if any of the items have data and are set to be displayed, construct the html ?>
			<?php if($show_the_client || $show_the_position || $show_the_other || $show_the_date || $show_rating == "stars" ): ?>
			<cite>
				<?php if($show_the_client): ?>
					<span class="testimonial-client" style="<?php echo $client_css; ?>"><?php echo $this->easy_t_clean_html($testimonial['client']);?></span>
				<?php endif; ?>
				<?php if($show_the_position): ?>
					<span class="testimonial-position" style="<?php echo $position_css; ?>"><?php echo $this->easy_t_clean_html($testimonial['position']);?></span>
				<?php endif; ?>
				<?php if($show_the_other): ?>
					<span class="testimonial-other" style="<?php echo $other_css; ?>"><?php echo $this->easy_t_clean_html($testimonial['other']);?></span>
				<?php endif; ?>
				<?php if($show_the_date): ?>
					<span class="date" style="<?php echo $date_css; ?>"><?php echo $this->easy_t_clean_html($testimonial['date']);?></span>
				<?php endif; ?>
				<?php if($show_the_rating): ?>
					<?php if(strlen($testimonial['num_stars'])>0): ?>
					<span class="stars">
					<?php			
						$x = 5; //total available stars
						//output dark stars for the filled in ones
						for($i = 0; $i < $testimonial['num_stars']; $i ++){
							echo '<span class="dashicons dashicons-star-filled" style="' . $rating_css . '"></span>';
							$x--; //one less star available
						}
						//fill out the remaining empty stars
						for($i = 0; $i < $x; $i++){
							echo '<span class="dashicons dashicons-star-filled empty"></span>';
						}
					?>			
					</span>	
					<?php endif; ?>
				<?php endif; ?>
			</cite>
			<?php endif; ?>					
		</p>	
	<?php
	}
	
	/*
	 * Assemble the HTML for the Testimonial Image taking into account current options
	 */		
	function build_testimonial_image($postid){
		//load image size settings
		$testimonial_image_size = get_option('easy_t_image_size');
		if(strlen($testimonial_image_size) < 2){
			$testimonial_image_size = "easy_testimonial_thumb";		
			$width = 50;
			$height = 50;
		} else {		
			//one of the default sizes, load using get_option
			if( in_array( $testimonial_image_size, array( 'thumbnail', 'medium', 'large' ) ) ){
				$width = get_option( $testimonial_image_size . '_size_w' );
				$height = get_option( $testimonial_image_size . '_size_h' );
			//size added by theme, user, or plugin
			//load using additional image sizes global
			}else{
				global $_wp_additional_image_sizes;
				
				if( isset( $_wp_additional_image_sizes ) && isset( $_wp_additional_image_sizes[ $testimonial_image_size ] ) ){
					$width = $_wp_additional_image_sizes[ $testimonial_image_size ]['width'];
					$height = $_wp_additional_image_sizes[ $testimonial_image_size ]['height'];
				}
			}
		}
		
		//use whichever of the two dimensions is larger
		$size = ($width > $height) ? $width : $height;

		//load testimonial's featured image
		// we are suppressing error output as there is an issue causing image imports that fail
		// to place a WP_Error object as the image, which causes a lot of hullaballoo
		// this has been fixed in the importer / exporter, this remains for legacy testimonials
		$image = @get_the_post_thumbnail($postid, $testimonial_image_size);
		
		//if no featured image is set
		if (strlen($image) < 2){ 
			//if use mystery man is set
			if (get_option('easy_t_mystery_man', 1)){
				//check and see if gravatars are enabled
				if(get_option('easy_t_gravatar', 1)){
					//if so, set image path to match desired gravatar with the mystery man as a fallback
					$client_email = get_post_meta($postid, '_ikcf_email', true); 
					$gravatar = md5(strtolower(trim($client_email)));
					$mystery_man = urlencode( $this->config->url_path . 'assets/img/mystery_man.png' );
					
					$image = '<img class="attachment-'.$testimonial_image_size.' wp-post-image easy_testimonial_gravatar" alt="default gravatar" src="//www.gravatar.com/avatar/' . $gravatar . '?d=' . $mystery_man . '&s=' . $size . '" />';
				} else {
					//if not, just use the mystery man
					$image = '<img class="attachment-'.$testimonial_image_size.' wp-post-image easy_testimonial_mystery_man" alt="default image" src="' . $this->config->url_path . 'assets/img/mystery_man.png' . '" />';
				}
			//else if gravatar is set
			} else if(get_option('easy_t_gravatar', 1)){
				//if set, set image path to match gravatar without using the mystery man as a fallback
				$client_email = get_post_meta($postid, '_ikcf_email', true); 
				$gravatar = md5(strtolower(trim($client_email)));
				$mystery_man = urlencode( $this->config->url_path . 'assets/img/mystery_man.png' );
				
				$image = '<img class="attachment-'.$testimonial_image_size.' wp-post-image easy_testimonial_gravatar" alt="user gravatar" src="//www.gravatar.com/avatar/' . $gravatar . '?s=' . $size . '" />';
			}
		}
		
		return $image;
	}
	
	/*
	* Builds a CSS string corresponding to the values of a typography setting
	*
	* @param $prefix The prefix for the settings. We'll append font_name,
	* font_size, etc to this prefix to get the actual keys
	*
	* @returns string The completed CSS string, with the values inlined
	*/
	function easy_testimonials_build_typography_css($prefix, $extra = '')
	{
		$css_rule_template = ' %s: %s;';
		$output = '';
		if (!$this->config->is_pro) {
			return $output;
		}
		/*
		* Font Family
		*/
		$option_val = get_option($prefix . 'font_family', '');
		if (!empty($option_val)) {
			// strip off 'google:' prefix if needed
			$option_val = str_replace('google:', '', $option_val);
			// wrap font family name in quotes
			$option_val = '\'' . $option_val . '\'';
			$output .= sprintf($css_rule_template, 'font-family', $option_val);
		}
		/*
		* Font Size
		*/
		$option_val = get_option($prefix . 'font_size', '');
		if (!empty($option_val)) {
			// append 'px' if needed
			if ( is_numeric($option_val) ) {
				$option_val .= 'px';
			}
			$output .= sprintf($css_rule_template, 'font-size', $option_val);
		}
		/*
		* Font Style - add font-style and font-weight rules
		* NOTE: in this special case, we are adding 2 rules!
		*/
		$option_val = get_option($prefix . 'font_style', '');
		// Convert the value to 2 CSS rules, font-style and font-weight
		// NOTE: we lowercase the value before comparison, for simplification
		switch(strtolower($option_val))
		{
			case 'regular':
				// not bold not italic
				$output .= sprintf($css_rule_template, 'font-style', 'normal');
				$output .= sprintf($css_rule_template, 'font-weight', 'normal');
			break;
			case 'bold':
				// bold, but not italic
				$output .= sprintf($css_rule_template, 'font-style', 'normal');
				$output .= sprintf($css_rule_template, 'font-weight', 'bold');
			break;
			case 'italic':
				// italic, but not bold
				$output .= sprintf($css_rule_template, 'font-style', 'italic');
				$output .= sprintf($css_rule_template, 'font-weight', 'normal');
			break;
			case 'bold italic':
				// bold and italic
				$output .= sprintf($css_rule_template, 'font-style', 'italic');
				$output .= sprintf($css_rule_template, 'font-weight', 'bold');
			break;
			default:
				// empty string or other invalid value, ignore and move on
			break;
		}
		/*
		* Font Color
		* RWG: Moved this after other options so that, for Stars display 
		*      we can empty $output and start over with just the font color
		*      preventing the user from accidentally doing crazy things with their stars
		*/
		//RWG: if this is the Rating and extra is set to Stars, only apply the chosen color (ie, wipe out the output string and start anew -- this prevents the user from accidentally breaking their stars display)
		if($prefix == "easy_t_rating_" && $extra == "stars"){
			$output = "";
		}
		$option_val = get_option($prefix . 'font_color', '');
		if (!empty($option_val)) {
			$output .= sprintf($css_rule_template, 'color', $option_val);
		}
		
		// return the completed CSS string
		return trim($output);
	}
	
	/*
	 * Generates and returns the HTML for a given testimonial, 
	 * considering the shortcode attributes provided.
	 *
	 * @param integer $postid The post ID of the testimonial
	 * @param array $atts The shortcode attributes to use for build this testimonial
	 *
	 * @return string The HTML output for this testimonial
	 */
	function easy_t_get_single_testimonial_html($is_single = false)
	{	
		global $post;
	
		$postid = $this->testimonial->ID;
		
		//if this is being loaded from the single post view
		//then we already have the post data setup (we are in The Loop)
		//so skip this step
		if(!$is_single){
			setup_postdata( $this->testimonial );
		}
		
		//empty array to place all the testimonial data
		$testimonial = array();
		
		$testimonial['date'] = get_the_date('M. j, Y', $postid);
		
		if($this->atts['use_excerpt'] && !$is_single){
			$testimonial['content'] = $this->easy_t_get_the_excerpt( $this->testimonial->ID );
		} else {				
			$testimonial['content'] = get_post_field('post_content', $this->testimonial->ID);
		}
		
		//apply our content filter, if flag set
		if( get_option('easy_t_apply_content_filter', false) || $is_single ){			
			$testimonial['content'] = $this->easy_testimonials_the_content_filter( $testimonial['content'] );
		} else {
			$testimonial['content'] = wpautop( $testimonial['content'] );
		}
		
		$testimonial['id'] = $this->testimonial->ID;
		
		//load rating
		//if set, append english text to it
		$testimonial['rating'] = get_post_meta($this->testimonial->ID, '_ikcf_rating', true); 
		$testimonial['num_stars'] = ''; //reset num stars (Thanks Steve@IntegrityConsultants!)
		if(strlen($testimonial['rating'])>0){	
			$rating_css = $this->easy_testimonials_build_typography_css('easy_t_rating_');
		
			$testimonial['num_stars'] = $testimonial['rating'];
			$testimonial['rating'] = '<p class="easy_t_ratings" itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating" style="' . $rating_css . '"><meta itemprop="worstRating" content = "1"/><span itemprop="ratingValue" >' . htmlentities($testimonial['rating']) . '</span>/<span itemprop="bestRating">5</span> Stars.</p>';
		}	
		
		//if nothing is set for the short content, use the long content
		if(strlen($testimonial['content']) < 2){
			$testimonial['content'] = $post->post_excerpt;
			
			if($this->atts['use_excerpt']){
				if($testimonial['content'] == ''){
					$testimonial['content'] = wp_trim_excerpt($post->post_content);
				}
			} else {				
				$testimonial['content'] = $post->post_content;
			}
		}
			
		if(strlen($this->atts['show_rating'])>2){
			if($this->atts['show_rating'] == "before"){
				$testimonial['content'] = $testimonial['rating'] . ' ' . $testimonial['content'];
			}
			if($this->atts['show_rating'] == "after"){
				$testimonial['content'] =  $testimonial['content'] . ' ' . $testimonial['rating'];
			}
		}
		
		if ($this->atts['show_thumbs']) {		
			$testimonial['image'] = $this->build_testimonial_image($this->testimonial->ID);
		}
		
		$testimonial['client'] = get_post_meta($this->testimonial->ID, '_ikcf_client', true); 	
		$testimonial['position'] = get_post_meta($this->testimonial->ID, '_ikcf_position', true); 
		$testimonial['other'] = get_post_meta($this->testimonial->ID, '_ikcf_other', true); 	

		//if this testimonial doesn't have a value for the item being reviewed
		//and if the use global item reviewed setting is checked
		//use the global item reviewed value in for the current testimonial
		if( (strlen($testimonial['other'])<2) && get_option('easy_t_use_global_item_reviewed',false) ){
			$testimonial['other'] = get_option('easy_t_global_item_reviewed','');
		}
	 
		//load a list of of easy testimonial categories associated with this testimonial
		//loop through list and build a string of category slugs
		//we will append these to the wrapping HTML of the single testimonial for advanced customization
		$terms = wp_get_object_terms( $this->testimonial->ID, 'easy-testimonial-category');
		$term_list = '';
		foreach($terms as $term){
			$term_list .= "easy-t-category-" . $term->slug . " ";
		}
	 
		//build attribute based classes for extra customization options
		$atts_for_classes = array(
			'thumbs' => ($this->atts['show_thumbs']) ? 'show' : 'hide',
			'title' => ($this->atts['show_title']) ? 'show' : 'hide',
			'date' => ($this->atts['show_date']) ? 'show' : 'hide',
			'rating' => $this->atts['show_rating'],
			'other' => ($this->atts['show_other']) ? 'show' : 'hide'
		);
		$attribute_classes = $this->easy_t_build_classes_from_atts($atts_for_classes);
		
		//add the category slugs to the list of classes to output
		//make sure to include the extra space so we aren't butting classes up against each other
		$attribute_classes .= " " . $term_list;
	 
		//get classes for current theme
		$output_theme = $this->easy_t_get_theme_class($this->atts['theme']);
		
		//get css from our typography settings
		$testimonial_body_css = $this->easy_testimonials_build_typography_css('easy_t_body_');	
		
		//get width from our width option or shortcode attribute (if set)
		$width_value = !empty($this->atts['width']) ? $this->atts['width'] : get_option('easy_t_width','');		
		//only output width style if a width is set
		$width_style = !empty($width_value) ? 'style="width: ' . $width_value . '"' : '';
		
		//if the "Show View More Testimonials Link" option is checked
		//and the hide_view_more attribute is not set
		//then set $show_view_more to true
		//else set to false
		$show_view_more = (get_option('easy_t_show_view_more_link',false) && !$this->atts['hide_view_more']) ? true : false;
		
		//last chance to customize attributes before rendering template
		extract( apply_filters('easy_t_display_attributes' , $this->atts) );
		
		//render single testimonial template
		ob_start();
		
		include( $this->config->dir_path . "templates/single_testimonial.php" );	
		
		$output = ob_get_contents();
		
		ob_end_clean();
		
		wp_reset_postdata();
		
		//apply filter with the current output, the current testimonial array, the current attributes, and the current testimonial's ID
		return apply_filters('easy_t_get_single_testimonial_html', $output, $testimonial, $this->atts, $this->testimonial->ID);
	}	
	
	//check to see if HTML is allowed in testimonials
	//if so, leave $html unfiltered
	//otherwise, run wp_strip_all_tags on $html
	//return $html
	function easy_t_clean_html( $html = "" ){
		if( !get_option('easy_t_allow_tags', true) ){
			$html = wp_strip_all_tags( $html );
		}
		
		return $html;
	}
	
	function easy_testimonials_the_content_filter($content)
	{			
		//remove our special content filter before applying the default content filter, to prevent recursion
		//this global is here because you have to remove class based filters using the same instance that added them.
		global $gp_testimonial_class;
		remove_filter( 'the_content', array($gp_testimonial_class, 'single_testimonial_content_filter') );
		
		//remove the pagebuilder filter that is causing infinite recursion
		if( function_exists('siteorigin_panels_filter_content') ){
			remove_filter( 'the_content', 'siteorigin_panels_filter_content' );
		}
		
		$content = apply_filters( 'the_content', $content, 9999 );
		//now that we are done, re-add our special content filter
		add_filter( 'the_content', array($gp_testimonial_class, 'single_testimonial_content_filter'), 10 );
		
		//re-add the pagebuilder filter now that we are done
		if( function_exists('siteorigin_panels_filter_content') ){
			add_filter( 'the_content', 'siteorigin_panels_filter_content' );
		}
		
		return $content;
	}
	
}// end class Testimonial