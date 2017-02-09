<?php
class GP_TestimonialForm{	
	var $media_buttons;
	var $config;

	function __construct($atts){
		//dashboard widget for pro users
		add_action( 'wp_dashboard_setup', array($this, 'easy_t_add_dashboard_widget'));	

		//add admin dashboard widgets
		$this->setup_dashboard();	
					
		//register form shortcode
		add_shortcode( get_option('ezt_submit_testimonial_shortcode', 'submit_testimonial'), array($this, 'submitTestimonialForm') );
		
		//store media buttons object for editor widget output
		$this->media_buttons = !empty($atts['media_buttons']) ? $atts['media_buttons'] : false;
		$this->config = $atts['config'];
		
		// add media buttons to admin
		$this->add_media_buttons();
	}
	
	//add media buttons
	function add_media_buttons(){		
		if($this->media_buttons){
			// add media buttons to admin
			$cur_post_type = ( isset($_GET['post']) ? get_post_type(intval($_GET['post'])) : '' );
			if( is_admin() && ( empty($_REQUEST['post_type']) || $_REQUEST['post_type'] !== 'testimonial' ) && ($cur_post_type !== 'testimonial') )
			{
				$this->media_buttons->add_button('Testimonial Form',  get_option('ezt_submit_testimonial_shortcode', 'submit_testimonial'), 'submittestimonialwidget', 'testimonial');
			}
		}
	}
	
	//setup dashboard
	function setup_dashboard(){		
		//dashboard widget ajax functionality 
		add_action('admin_head', array($this, 'easy_t_action_javascript'));
		add_action('wp_ajax_easy_t_action', array($this, 'easy_t_action_callback'));
	}

	/**
	 * Add a widget to the dashboard.
		*
	 * This function is hooked into the 'wp_dashboard_setup' action below.
	 */
	function easy_t_add_dashboard_widget() {
		//only show for editors and administrators
		if( current_user_can('editor') || current_user_can('administrator') ) {
			wp_add_dashboard_widget(
				'easy_t_submissions_dashboard_widget',         // Widget slug.
				'Easy Testimonials Pro - Recent Submissions',         // Title.
				array($this, 'easy_t_output_dashboard_widget') // Display function.
			);	
		}
	}

	/**
	 * Create the function to output the contents of our Dashboard Widget.
	 */
	function easy_t_output_dashboard_widget()
	{		
		$recent_submissions = '';
		
		$recent_submissions = get_posts('post_type=testimonial&posts_per_page=10&post_status=pending');
		
		if (is_array($recent_submissions)) {
			//also output a panel of stats (ie, # of pending submissions)
			
			echo '<table id="easy_t_recent_submissions" class="widefat">';
			echo '<thead>';
			echo '<tr>';
			echo '<th>Date</th>';
			echo '<th>Summary</th>';
			echo '<th>Rating</th>';
			echo '<th>Action</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
			foreach($recent_submissions as $i => $submission)
			{
				$row_class = ($i % 2 == 0) ? 'alternate' : '';
				echo '<tr class="'.$row_class.'">';
				
				$action_url = get_admin_url() . "post.php?post=$submission->ID&action=edit";
				$action_links = '<p><a href="'.$action_url.'" class="edit_testimonial" id="'.$submission->ID.'" title="Edit Testimonial"><span class="dashicons dashicons-edit"></span>Edit</a></p>';
				$action_links .= '<p><a class="approve_testimonial" id="'.$submission->ID.'" title="Approve Testimonial"><span class="dashicons dashicons-yes"></span>Approve</a></p>';
				$action_links .= '<p><a class="trash_testimonial" id="'.$submission->ID.'" title="Trash Testimonial"><span class="dashicons dashicons-no"></span>Trash</a></p>';
				
				$rating = get_post_meta($submission->ID, '_ikcf_rating', true); 
				$rating = !empty($rating) ? $rating . "/5" : "No Rating";
				
				$friendly_time = date('Y-m-d H:i:s', strtotime($submission->post_date));
				printf ('<td>%s</td>', htmlentities($friendly_time));
				
				printf ('<td>%s</td>', wp_trim_words($submission->post_content, 25));
				printf ('<td>%s</td>', htmlentities($rating));
				printf ('<td class="action_links">%s</td>', $action_links);

				echo '</tr>';				
			}
			echo '</tbody>';
			echo '</table>';
			
			$view_all_testimonials_url= '/wp-admin/edit.php?post_type=testimonial';
			$link_text = 'View All Testimonials';
			printf ('<p class="view_all_testimonials"><a href="%s">%s &raquo;</a></p>', $view_all_testimonials_url, $link_text);
		}
	}	

	//admin ajax yang for dashboard widget
	function easy_t_action_javascript($action) {
		?>
		<script type="text/javascript" >
		jQuery(document).ready(function($) {
			jQuery('.action_links a').on('click', function() {
				var $this = jQuery(this);
				var	data = {action: 'easy_t_action', my_action: $this.attr('class'), my_postid: $this.attr('id')};
				
				if($this.attr('class') != "edit_testimonial"){//no ajax on edit, take visitor to edit screen instead
					jQuery.post(ajaxurl, data, function(response) {
						if($this.attr('class') == "approve_testimonial"){
							$this.parent().parent().html("<p>Approved!</p>").parent().addClass("updated");
						} else if($this.attr('class') == "trash_testimonial"){
							$this.parent().parent().html("<p>Trashed!</p>").parent().addClass("updated");
						}
					});
					
					return false;
				}
			});
		 });
		 </script>
		 <?php
	}

	function easy_t_action_callback() {
		$action = $_POST['my_action'];
		$id = $_POST['my_postid'];
		$response = "";
		
		switch($action) {
				case 'approve_testimonial':
					$testimonial = array(
						'ID' => $id,
						'post_status' => 'publish'
					);
					
					$response = wp_update_post($testimonial);//returns 0 if error, otherwise ID of the updated testimonial
		 
					if($response != 0){
						echo $response;
					} else {
						//error, do something
					}
				break;

				case 'trash_testimonial':				
					$response = wp_trash_post($id);//returns false if error
					
					if(!$response){
						//error, do something
					} else {
						echo $id;
					}
				break;
		 }
		 
		 die();
	}
	//end admin ajax yang for dashboard widget
		
	// End Dashboard Widget Yang
	
	

	function easy_t_send_notification_email($submitted_testimonial = array()){
		//get e-mail address from post meta field
		//TBD: logic to use comma-separated e-mail addresses
		$email_addresses = explode(",", get_option('easy_t_submit_notification_address', get_bloginfo('admin_email')));
	 
		$subject = "New Easy Testimonial Submission on " . get_bloginfo('name');
		
		//see if option is set to include testimonial in e-mail
		if(get_option('easy_t_submit_notification_include_testimonial')){ //option is set, build message containing testimonial
			$body = "You have received a new submission with Easy Testimonials on your site, " . get_bloginfo('name') . ".  Login to approve or trash it! \r\n\r\n";		
			
			$body .= "Title: {$submitted_testimonial['post']['post_title']} \r\n";
			$body .= "Body: {$submitted_testimonial['post']['post_content']} \r\n";
			$body .= "Name: {$submitted_testimonial['the_name']} \r\n";
			$body .= "Position/Web Address/Other: {$submitted_testimonial['the_other']} \r\n";
			$body .= "Location/Product Reviewed/Other: {$submitted_testimonial['the_other_other']} \r\n";
			$body .= "Rating: {$submitted_testimonial['the_rating']} \r\n";
		} else { //option isn't set, use default message
			$body = "You have received a new submission with Easy Testimonials on your site, " . get_bloginfo('name') . ".  Login and see what they had to say!";
		}
	 
		//use this to set the From address of the e-mail
		$headers = 'From: ' . get_bloginfo('name') . ' <'.get_bloginfo('admin_email').'>' . "\r\n";
		
		//loop through available e-mail addresses and fire off the e-mails!
		foreach($email_addresses as $email_address){
			if(wp_mail($email_address, $subject, $body, $headers)){
				//mail sent!
			} else {
				//failure!
			}
		}
	}
		
	function easy_t_check_captcha() {		
		if ( !class_exists('ReallySimpleCaptcha') && !$this->easy_testimonials_use_recaptcha() ) {
			// captcha's cannot possibly be checked, so return true
			return true;
		} else {
			$captcha_correct = false; // false until proven correct		
		}
		
		// look for + verify a reCAPTCHA first
		if ( !empty($_POST["g-recaptcha-response"]) ) 
		{
			if ( !class_exists('GP_ReCaptcha') ) {
				require_once ('gp-recaptcha.class.php');
			}
			$secret = get_option('easy_t_recaptcha_secret_key', '');
			$response = null;
			if ( !empty($secret)  )
			{
				$reCaptcha = new GP_ReCaptcha($secret);
				$response = $reCaptcha->verifyResponse(
					$_SERVER["REMOTE_ADDR"],
					$_POST["g-recaptcha-response"]
				);
				$captcha_correct = ($response != null && $response->success);
			}
		}
		else if ( !empty ($_POST['captcha_prefix']) && class_exists('ReallySimpleCaptcha') )
		{
			$captcha = new ReallySimpleCaptcha();
			// This variable holds the CAPTCHA image prefix, which corresponds to the correct answer
			$captcha_prefix = $_POST['captcha_prefix'];
			// This variable holds the CAPTCHA response, entered by the user
			$captcha_code = $_POST['captcha_code'];
			// This variable will hold the result of the CAPTCHA validation. Set to 'false' until CAPTCHA validation passes
			$captcha_correct = false;
			// Validate the CAPTCHA response
			$captcha_check = $captcha->check( $captcha_prefix, $captcha_code );
			// Set to 'true' if validation passes, and 'false' if validation fails
			$captcha_correct = $captcha_check;
			// clean up the tmp directory
			$captcha->remove($captcha_prefix);
			$captcha->cleanup();			
		}
		
		return $captcha_correct;
	}	
		
	function easy_t_outputCaptcha()
	{
		if ( $this->easy_testimonials_use_recaptcha() ) {
			?>
				<div class="g-recaptcha" data-sitekey="<?php echo htmlentities(get_option('easy_t_recaptcha_api_key', '')); ?>"></div>
				<br />		
			<?php
		}
		else if ( class_exists('ReallySimpleCaptcha') )
		{
			// Instantiate the ReallySimpleCaptcha class, which will handle all of the heavy lifting
			$captcha = new ReallySimpleCaptcha();
			 
			// ReallySimpleCaptcha class option defaults.
			// Changing these values will hav no impact. For now, these are here merely for reference.
			// If you want to configure these options, see "Set Really Simple CAPTCHA Options", below
			$captcha_defaults = array(
				'chars' => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789',
				'char_length' => '4',
				'img_size' => array( '72', '24' ),
				'fg' => array( '0', '0', '0' ),
				'bg' => array( '255', '255', '255' ),
				'font_size' => '16',
				'font_char_width' => '15',
				'img_type' => 'png',
				'base' => array( '6', '18'),
			);
			 
			/**************************************
			* All configurable options are below  *
			***************************************/
			 
			//Set Really Simple CAPTCHA Options
			$captcha->chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
			$captcha->char_length = '4';
			$captcha->img_size = array( '100', '50' );
			$captcha->fg = array( '0', '0', '0' );
			$captcha->bg = array( '255', '255', '255' );
			$captcha->font_size = '16';
			$captcha->font_char_width = '15';
			$captcha->img_type = 'png';
			$captcha->base = array( '6', '18' );
			 
			/********************************************************************
			* Nothing else to edit.  No configurable options below this point.  *
			*********************************************************************/
			 
			// Generate random word and image prefix
			$captcha_word = $captcha->generate_random_word();
			$captcha_prefix = mt_rand();
			// Generate CAPTCHA image
			$captcha_image_name = $captcha->generate_image($captcha_prefix, $captcha_word);
			// Define values for CAPTCHA fields
			$captcha_image_url =  get_bloginfo('wpurl') . '/wp-content/plugins/really-simple-captcha/tmp/';
			$captcha_image_src = $captcha_image_url . $captcha_image_name;
			$captcha_image_width = $captcha->img_size[0];
			$captcha_image_height = $captcha->img_size[1];
			$captcha_field_size = $captcha->char_length;
			// Output the CAPTCHA fields
			?>
			<div class="easy_t_field_wrap">
				<img src="<?php echo $captcha_image_src; ?>"
				 alt="captcha"
				 width="<?php echo $captcha_image_width; ?>"
				 height="<?php echo $captcha_image_height; ?>" /><br/>
				<label for="captcha_code"><?php echo get_option('easy_t_captcha_field_label','Captcha'); ?></label><br/>
				<input id="captcha_code" name="captcha_code"
				 size="<?php echo $captcha_field_size; ?>" type="text" />
				<p class="easy_t_description"><?php echo get_option('easy_t_captcha_field_description','Enter the value in the image above into this field.'); ?></p>
				<input id="captcha_prefix" name="captcha_prefix" type="hidden"
				 value="<?php echo $captcha_prefix; ?>" />
			</div>
			<?php
		}
	}

	//handle file upload for image in front end submission form
	function easy_t_upload_user_file( $file = array(), $post_id ) {
		
		require_once( ABSPATH . 'wp-admin/includes/admin.php' );
		
		$file_return = wp_handle_upload( $file, array('test_form' => false ) );
		
		// Set an array containing a list of acceptable formats
		$allowed_file_types = array('image/jpg','image/jpeg','image/gif','image/png');
		
		if( isset( $file_return['error'] ) || isset( $file_return['upload_error_handler'] ) ) {
			return false;
		} else {
		
			//only uploaded file types that are allowed
			if(in_array($file_return['type'], $allowed_file_types)) {
			
				$filename = $file_return['file'];
				
				$attachment = array(
					'post_mime_type' => $file_return['type'],
					'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
					'post_content' => '',
					'post_status' => 'inherit',
					'guid' => $file_return['url']
				);
				
				$attachment_id = wp_insert_attachment( $attachment, $file_return['url'] );
				
				require_once (ABSPATH . 'wp-admin/includes/image.php' );
				$attachment_data = wp_generate_attachment_metadata( $attachment_id, $filename );
				wp_update_attachment_metadata( $attachment_id, $attachment_data );
				
				if( 0 < intval( $attachment_id ) ) {
					//make this the testimonial's featured image
					set_post_thumbnail( $post_id, $attachment_id );
					
					return $attachment_id;
				}
			} else {
				return false;
			}
		}
		
		return false;
	}

	function easy_testimonials_use_recaptcha()
	{
		return ( 
			get_option('easy_t_use_captcha', 0)
			&& strlen( get_option('easy_t_recaptcha_api_key', '') ) > 0
			&& strlen( get_option('easy_t_recaptcha_secret_key', '') ) > 0
		);
	}
		
	//submit testimonial shortcode
	function submitTestimonialForm($atts){
		//default new testimonial title
		$default_new_testimonial_title = __( "New Testimonial" ) . " - " . date( "F j, Y, g:i a", current_time('timestamp', 0) );
	
		//load shortcode attributes into an array
		$atts = shortcode_atts( array(
			'submit_to_category' => false,
			'testimonial_author_id' => get_option('easy_t_testimonial_author', 1),
		), $atts );
		
		extract($atts);

		// enqueue reCAPTCHA JS if needed
		if( $this->easy_testimonials_use_recaptcha() ) {
			wp_enqueue_script('g-recaptcha');			
		}
		ob_start();
		
		// process form submissions
		$inserted = false;
	   
		if( 'POST' == $_SERVER['REQUEST_METHOD'] && !empty( $_POST['action'] ) && $_POST['action'] == "post_testimonial" ) {
			if($this->config->is_pro){  
				$do_not_insert = false;
				
				//title field is displayed and a value is entered, so use it
				if ( !empty($_POST['the-title']) ) {
					$title =  wp_strip_all_tags($_POST['the-title']);
				//title field is not displayed, no value is entered, create a default entry
				} else if( get_option('easy_t_hide_title_field', 0) ) {
					//if the client name is not empty, use it
					//otherwise use the default convention
					$title = !empty($_POST['the-name']) ? $_POST['the-name'] : $default_new_testimonial_title;
				//title field is displayed, no value is entered, throw an error so the user knows they need to fill it out
				} else {
					$title_error = '<p class="easy_t_error">' . get_option('easy_t_title_field_error','Please give ' . strtolower(get_option('easy_t_body_content_field_label','your testimonial')) . ' a ' . strtolower(get_option('easy_t_title_field_label','title')) . '.') . '</p>';
					$do_not_insert = true;
				}
			   
				if ( !empty($_POST['the-body']) ) {
					$body = $_POST['the-body'];
				} else {
					$body_error = '<p class="easy_t_error">' . get_option('easy_t_body_field_error', 'Please enter ' . strtolower(get_option('easy_t_body_content_field_label','your testimonial')) . '.') . '</p>';
					$do_not_insert = true;
				}			
				
				if( get_option('easy_t_use_captcha',0) ){ 
					$correct = $this->easy_t_check_captcha(); 
					if(!$correct){
						$captcha_error = '<p class="easy_t_error">' . get_option('easy_t_captcha_field_error', 'Captcha did not match.') . '</p>';
						$do_not_insert = true;
					}
				}
				
				if(isset($captcha_error) || isset($body_error) || isset($title_error)){
					echo '<p class="easy_t_error">' . get_option('easy_t_general_error', 'There was an error with your submission.  Please check the fields and try again.') . '</p>';
				}
			   
				if(!$do_not_insert){
					//snag custom fields
					$the_other = isset($_POST['the-other']) ? $_POST['the-other'] : '';
					$the_other_other = isset($_POST['the-other-other']) ? $_POST['the-other-other'] : '';
					$the_name = isset($_POST['the-name']) ? $_POST['the-name'] : '';
					$the_rating = isset($_POST['the-rating']) ? $_POST['the-rating'] : '';
					$the_email = isset($_POST['the-email']) ? $_POST['the-email'] : '';
					$the_category = isset($_POST['the-category']) ? $_POST['the-category'] : "";
					
					$tags = array();
				   
					$post = array(
						'post_title'    => $title,
						'post_content'  => $body,
						'post_category' => array(),  // custom taxonomies too, needs to be an array
						'tags_input'    => $tags,
						'post_status'   => 'pending',
						'post_type'     => 'testimonial',
						'post_author' 	=> $testimonial_author_id
					);
				
					$new_id = wp_insert_post($post);
					
					//set the testimonial category
					//TBD: handle multiple categories (should just be an array of term id's)
					
					//load the term id by the passed slug
					//this prevents someone from passing in a slug of their own creation and having that create a newly corresponding category
					//instead, it will load the id of the desired term and add that,
					//if no matching term is found, we just don't add this testimonial to a category!
					/* 
						Warning: string vs integer confusion! Field values, including term_id are returned in string format. Before further use, typecast numeric values to actual integers, otherwise WordPress will mix up term_ids and slugs which happen to have only numeric characters! 
					*/
					$testimonial_category_id = get_term_by('slug', $the_category, 'easy-testimonial-category');
					if( isset($testimonial_category_id->term_id) ){
						wp_set_object_terms($new_id, (int)$testimonial_category_id->term_id, 'easy-testimonial-category');
					}
				   
					//set the custom fields
					update_post_meta( $new_id, '_ikcf_client', $the_name );
					update_post_meta( $new_id, '_ikcf_position', $the_other );
					update_post_meta( $new_id, '_ikcf_other', $the_other_other );
					update_post_meta( $new_id, '_ikcf_rating', $the_rating );
					update_post_meta( $new_id, '_ikcf_email', $the_email );
				   
				   //collect info for notification e-mail
				   $submitted_testimonial = array(
						'post' => $post,
						'the_name' => $the_name,
						'the_other' => $the_other,
						'the_other_other' => $the_other_other,
						'the_rating' => $the_rating,
						'the_email' => $the_email
				   );
				   
					$inserted = true;
					
					//if the user has submitted a photo with their testimonial, handle the upload
					if( ! empty( $_FILES ) ) {
						foreach( $_FILES as $file ) {
							if( is_array( $file ) ) {
								$attachment_id = $this->easy_t_upload_user_file( $file, $new_id );
							}
						}
					}
				}
			} else {
				echo "You must have a valid key to perform this action.";
			}
		}       
	   
		$content = '';
	   
		if($this->config->is_pro){ 		
			if($inserted){
				$redirect_url = get_option('easy_t_submit_success_redirect_url','');
				$this->easy_t_send_notification_email($submitted_testimonial);
				if(strlen($redirect_url) > 2){
					echo '<script type="text/javascript">window.location.replace("'.$redirect_url.'");</script>';
				} else {					
					echo '<p class="easy_t_submission_success_message">' . get_option('easy_t_submit_success_message','Thank You For Your Submission!') . '</p>';
				}
			} else { ?>
			<!-- New Post Form -->
			<div id="postbox">
				<form id="new_post" class="easy-testimonials-submission-form" name="new_post" method="post" enctype="multipart/form-data" >
					<?php if(!get_option('easy_t_hide_title_field',false)): ?>
					<div class="easy_t_field_wrap <?php if(isset($title_error)){ echo "easy_t_field_wrap_error"; }//if a title wasn't entered add the wrap error class ?>">
						<?php if(isset($title_error)){ echo $title_error; }//if a title wasn't entered display a message ?>
						<label for="the-title"><?php echo get_option('easy_t_title_field_label','Title'); ?></label>
						<input type="text" id="the-title" value="<?php echo ( !empty($_POST['the-title']) ? htmlentities($_POST['the-title']) : ''); ?>" tabindex="1" size="20" name="the-title" />
						<p class="easy_t_description"><?php echo get_option('easy_t_title_field_description','Please give your Testimonial a Title.  *Required'); ?></p>
					</div>
					<?php endif; ?>
					<?php if(!get_option('easy_t_hide_name_field',false)): ?>
					<div class="easy_t_field_wrap">
						<label for="the-name"><?php echo get_option('easy_t_name_field_label','Name'); ?></label>
						<input type="text" id="the-name" value="<?php echo ( !empty($_POST['the-name']) ? htmlentities($_POST['the-name']) : ''); ?>" tabindex="2" size="20" name="the-name" />
						<p class="easy_t_description"><?php echo get_option('easy_t_name_field_description','Please enter your Full Name.'); ?></p>
					</div>
					<?php endif; ?>
					<?php if(!get_option('easy_t_hide_email_field',false)): ?>
					<div class="easy_t_field_wrap">
						<label for="the-email"><?php echo get_option('easy_t_email_field_label','Your E-Mail Address'); ?></label>
						<input type="text" id="the-email" value="<?php echo ( !empty($_POST['the-email']) ? htmlentities($_POST['the-email']) : ''); ?>" tabindex="2" size="20" name="the-email" />
						<p class="easy_t_description"><?php echo get_option('easy_t_email_field_description','Please enter your e-mail address.  This information will not be publicly displayed.'); ?></p>
					</div>
					<?php endif; ?>
					<?php if(!get_option('easy_t_hide_position_web_other_field',false)): ?>
					<div class="easy_t_field_wrap">
						<label for="the-other"><?php echo get_option('easy_t_position_web_other_field_label','Position / Web Address / Other'); ?></label>
						<input type="text" id="the-other" value="<?php echo ( !empty($_POST['the-other']) ? htmlentities($_POST['the-other']) : ''); ?>" tabindex="3" size="20" name="the-other" />
						<p class="easy_t_description"><?php echo get_option('easy_t_position_web_other_field_description','Please enter your Job Title or Website address.'); ?></p>
					</div>
					<?php endif; ?>
					<?php if(!get_option('easy_t_hide_other_other_field',false)): ?>
					<div class="easy_t_field_wrap">
						<label for="the-other-other"><?php echo get_option('easy_t_other_other_field_label','Location / Product Reviewed / Other'); ?></label>
						<input type="text" id="the-other-other" value="<?php echo ( !empty($_POST['the-other-other']) ? htmlentities($_POST['the-other-other']) : ''); ?>" tabindex="3" size="20" name="the-other-other" />
						<p class="easy_t_description"><?php echo get_option('easy_t_other_other_field_description','Please enter your the name of the item you are Reviewing.');?>
					</div>
					<?php endif; ?>
					<?php //RWG: if set, add a hidden input for the submit_to_category value and hide the choice from the user ?>
					<?php if( isset($submit_to_category) && strlen($submit_to_category) > 2 ){ ?>
						<input type="hidden" id="the-category" name="the-category" value="<?php echo $submit_to_category; ?>" />
					<?php } else { ?>
					<?php $testimonial_categories = get_terms( 'easy-testimonial-category', 'orderby=title&hide_empty=0' ); ?>
					<?php if( !empty($testimonial_categories) && !get_option('easy_t_hide_category_field',false) ): ?>
					<div class="easy_t_field_wrap">
						<label for="the-category"><?php echo get_option('easy_t_category_field_label','Category'); ?></label>
						<select id="the-category" name="the-category">
							<?php
							foreach($testimonial_categories as $cat) {
								$sel_attr = ( !empty($_POST['the-category']) && $_POST['the-category'] == $cat->slug) ? 'selected="selected"' : '';
								printf('<option value="%s" %s>%s</option>', $cat->slug, $sel_attr, $cat->name);
							}
							?>
						</select>
						<p class="easy_t_description"><?php echo get_option('easy_t_category_field_description','Please select the Category that best matches your Testimonial.'); ?></p>
					</div>
					<?php endif; ?>
					<?php }//end check for sc attribute ?>
					<?php if(get_option('easy_t_use_rating_field',false)): ?>
					<div class="easy_t_field_wrap">
						<label for="the-rating"><?php echo get_option('easy_t_rating_field_label','Your Rating'); ?></label>
						<select id="the-rating" class="the-rating" tabindex="4" size="20" name="the-rating" >
							<?php 
							foreach(range(1, 5) as $rating) {
								$sel_attr = ( !empty($_POST['the-rating']) && $_POST['the-rating'] == $rating) ? 'selected="selected"' : '';
								printf('<option value="%d" %s>%d</option>', $rating, $sel_attr, $rating);
							}
							?>
						</select>
						<div class="rateit" data-rateit-backingfld=".the-rating" data-rateit-min="0"></div>
						<p class="easy_t_description"><?php echo get_option('easy_t_rating_field_description','1 - 5 out of 5, where 5/5 is the best and 1/5 is the worst.'); ?></p>
					</div>
					<?php endif; ?>
					<div class="easy_t_field_wrap <?php if(isset($body_error)){ echo "easy_t_field_wrap_error"; }//if a testimonial wasn't entered add the wrap error class ?>">
						<?php if(isset($body_error)){ echo $body_error; }//if a testimonial wasn't entered display a message ?>
						<label for="the-body"><?php echo get_option('easy_t_body_content_field_label','Your Testimonial'); ?></label>
						<textarea id="the-body" name="the-body" cols="50" tabindex="5" rows="6"><?php echo ( !empty($_POST['the-body']) ? htmlentities($_POST['the-body']) : ''); ?></textarea>
						<p class="easy_t_description"><?php echo get_option('easy_t_body_content_field_description','Please enter your Testimonial.  *Required'); ?></p>
					</div>							
					<?php if(get_option('easy_t_use_image_field',false)): ?>
					<div class="easy_t_field_wrap">
						<label for="the-image"><?php echo get_option('easy_t_image_field_label','Testimonial Image'); ?></label>
						<input type="file" id="the-image" value="" tabindex="6" size="20" name="the-image" />
						<p class="easy_t_description"><?php echo get_option('easy_t_image_field_description','You can select and upload 1 image along with your Testimonial.  Depending on the website\'s settings, this image may be cropped or resized.  Allowed file types are .gif, .jpg, .png, and .jpeg.'); ?></p>
					</div>
					<?php endif; ?>
					
					<?php 
						if( get_option('easy_t_use_captcha',0) ) {
							?><div class="easy_t_field_wrap <?php if(isset($captcha_error)){ echo "easy_t_field_wrap_error"; }//if a captcha wasn't correctly entered add the wrap error class ?>"><?php
							//if a captcha was entered incorrectly (or not at all) display message
							if(isset($captcha_error)){ echo $captcha_error; }
							$this->easy_t_outputCaptcha();
							?></div><?php
						}
					?>
					
					<div class="easy_t_field_wrap"><input type="submit" value="<?php echo get_option('easy_t_submit_button_label','Submit Testimonial'); ?>" tabindex="7" id="submit" name="submit" /></div>
					<input type="hidden" name="action" value="post_testimonial" />
					<?php wp_nonce_field( 'new-post' ); ?>
				</form>
			</div>
			<!--// New Post Form -->
			<?php }
		   
			$content = ob_get_contents();
			ob_end_clean(); 
		}
	   
		return apply_filters('easy_t_submission_form', $content);
	}
}