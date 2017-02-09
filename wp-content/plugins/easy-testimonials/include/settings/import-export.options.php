<?php
class easyTestimonialImportExportOptions extends easyTestimonialOptions{
	var $tabs;
	var $config;
	var $exporter;
	
	function __construct($config){		
		
		//call register settings function
		add_action( 'admin_init', array($this, 'register_settings'));	
		
		//assign config
		$this->config = $config;
		
		//handle any changes in hello t status
		//also handle any "import now" commands
		$this->process_hello_testimonials_options();		
		
		//setup exporter
		$this->exporter = new TestimonialsPlugin_Exporter();
		
		//process exports for pro users
		if($this->config->is_pro){
			add_action( 'admin_init', array($this, 'process_export'));	
		}
	}
	
	function process_export(){		
		//look for request to process export
		$process_export = false;
		if( isset($_GET['process_export']) ){
			//if request is set to true, process export now
			if( $_GET['process_export'] ){
				$this->exporter->process_export();
			}
		}
	}
	
	function register_settings(){		
		//register our settings	
		
		/* Import / Export */
		register_setting( 'easy-testimonials-import-export-settings-group', 'easy_t_hello_t_json_url' );		
		register_setting( 'easy-testimonials-import-export-settings-group', 'easy_t_hello_t_enable_cron' );	
		register_setting( 'easy-testimonials-import-export-settings-group', 'easy_t_cache_buster', array($this, 'easy_t_bust_options_cache') );
		
		/* Hello T */
		register_setting( 'easy-testimonials-private-settings-group', 'easy_t_hello_t_last_time' );		
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
			'header_label' => 'Import &amp; Export Testimonials',
			'settings_field_key' => 'easy-testimonials-import-export-settings-group', // can be an array			
			'extra_buttons_header' => $extra_buttons, // extra header buttons
			'extra_buttons_footer' => $extra_buttons // extra footer buttons
		) );		
		
		$this->settings_page_top(false);
		$this->setup_basic_tabs($tabs);
		$this->settings_page_bottom();
	}
	
	function output_hello_testimonials_options(){		
		?>							
			<h3>Hello Testimonials</h3>	
			<p><strong>Want to learn more about Hello Testimonials? <a href="http://hellotestimonials.com/p/welcome-easy-testimonials-users/">Click Here!</a></strong></p>
			
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="easy_t_hello_t_json_url">Hello Testimonials JSON Feed URL</label></th>
					<td><textarea name="easy_t_hello_t_json_url" id="easy_t_hello_t_json_url" rows=1 ><?php echo get_option('easy_t_hello_t_json_url'); ?></textarea>
					<p class="description">This is the JSON URL you copied from the Custom Integrations page inside Hello Testimonials.</p>
					</td>
				</tr>
			</table>
			
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="easy_t_hello_t_enable_cron">Enable Hello Testimonials Integration</label></th>
					<td><input type="checkbox" name="easy_t_hello_t_enable_cron" id="easy_t_hello_t_enable_cron" value="1" <?php if(get_option('easy_t_hello_t_enable_cron', 0)){ ?> checked="CHECKED" <?php } ?>/>
					<p class="description">If checked, new Testimonials will be loaded from your Hello Testimonials account and automatically added to your Testimonials list.</p>
					</td>
				</tr>
			</table>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="easy_t_hello_t_enable_cron">Import From Hello Testimonials Now</label></th>
					<td>
						<p class="submit">
							<a href="?page=easy-testimonials-import-export&run-cron-now=true" class="button-primary" title="<?php _e('Import Now', 'easy-testimonials') ?>"><?php _e('Import Now', 'easy-testimonials') ?></a>
						</p>
						<p class="description">If clicked, we will process any new testimonials available in Hello Testimonials now.</p>
					</td>
				</tr>
			</table>
		<?php 
	}
	
	function output_testimonial_importer(){
		?>
		<h3>Import Testimonials</h3>	
		<?php 
		if(!$this->config->is_pro){
			?>
			<p class="easy_testimonials_not_registered"><strong>These features require Easy Testimonials Pro.</strong>&nbsp;&nbsp;&nbsp;<a class="button" target="blank" href="https://goldplugins.com/our-plugins/easy-testimonials-details/upgrade-to-easy-testimonials-pro/?utm_source=easy_testimonials_import&utm_campaign=upgrade&utm_banner=display_options">Upgrade Now To Enable</a></p>
			<?php
		} else {
			//CSV Importer
			$importer = new TestimonialsPlugin_Importer($this);
			$importer->csv_importer();
		}
	}
	
	function output_testimonial_exporter(){
		?>
		<h3>Export Testimonials</h3>
		<?php 
		if(!$this->config->is_pro){
			?>
			<p class="easy_testimonials_not_registered"><strong>These features require Easy Testimonials Pro.</strong>&nbsp;&nbsp;&nbsp;<a class="button" target="blank" href="https://goldplugins.com/our-plugins/easy-testimonials-details/upgrade-to-easy-testimonials-pro/?utm_source=easy_testimonials_import&utm_campaign=upgrade&utm_banner=display_options">Upgrade Now To Enable</a></p>
			<?php
		} else {
			//CSV Exporter
			$this->exporter->output_form();
		}	
	}
	
	function setup_basic_tabs($tabs){	
		$this->tabs = $tabs;
		
		//load additional label string based upon pro status
		$pro_string = $this->config->is_pro ? "" : " (Pro)";
	
		$this->tabs->add_tab(
			'hello_testimonials_options', // section id, used in url fragment
			'Hello Testimonials', // section label
			array($this, 'output_hello_testimonials_options'), // display callback
			array(
				'class' => 'extra_li_class', // extra classes to add sidebar menu <li> and tab wrapper <div>
				'icon' => 'exchange' // icons here: http://fontawesome.io/icons/
			)
		);
	
		$this->tabs->add_tab(
			'importer', // section id, used in url fragment
			'Import Testimonials' . $pro_string, // section label
			array($this, 'output_testimonial_importer'), // display callback
			array(
				'class' => 'extra_li_class', // extra classes to add sidebar menu <li> and tab wrapper <div>
				'icon' => 'arrow-left', // icons here: http://fontawesome.io/icons/
				'show_save_button' => false // hide the save button on this tab only
			)
		);
	
		$this->tabs->add_tab(
			'exporter', // section id, used in url fragment
			'Export Testimonials' . $pro_string, // section label
			array($this, 'output_testimonial_exporter'), // display callback
			array(
				'class' => 'extra_li_class', // extra classes to add sidebar menu <li> and tab wrapper <div>
				'icon' => 'arrow-right', // icons here: http://fontawesome.io/icons/
				'show_save_button' => false // hide the save button on this tab only
			)
		);
		
		$this->tabs->display();
	}
	
	function process_hello_testimonials_options(){
		//schedule cron if enabled
		if(get_option('easy_t_hello_t_enable_cron', 0)){
			//and if the cron job hasn't already been scheduled
			if(!wp_get_schedule('hello_t_subscription')){
				//schedule the cron job
				$this->hello_t_cron_activate();
			}
			
			//if the run cron now button has been clicked
			if (isset($_GET['run-cron-now']) && $_GET['run-cron-now'] == 'true'){
				//go ahead and add the testimonials, too
				add_action('admin_init', array($this, 'add_hello_t_testimonials') );
			}
		} else {
			//else if the cron job option has been unchecked
			//clear the scheduled job
			$this->hello_t_cron_deactivate();
			
			//if the run cron now button has been clicked
			if (isset($_GET['run-cron-now']) && $_GET['run-cron-now'] == 'true'){				
				$this->messages[] = 'Hello Testimonials Integration is disabled!  Please enable to Import Testimonials.';
			}
		}
	}
	
	//open up the json
	//determine which testimonials are new, or assume we have loaded only new testimonials
	//parse object and insert new testimonials
	function add_hello_t_testimonials(){	
		$the_time = time();
		
		$json_url = get_option('easy_t_hello_t_json_url', '');
		if ( empty($json_url) ) {
			return;
		}
		
		$url = $json_url . "?last=" . get_option('easy_t_hello_t_last_time', 0);		
		$response = wp_remote_get( $url, array('sslverify' => false ));
				
		if ( is_wp_error($response) ) {
			// invalid URL, show error message
			$this->messages[] = '<strong>Error:</strong> the Hello Testimonials JSON URL you entered could not be reached. Please check the URL in your Hello Testimonials settings, or try again in a few minutes.';
			return;
		}		
		
		if( !empty($response) && !empty($response['body']) ) {
			$response = json_decode($response['body']);
			
			if(isset($response->testimonials)){
				$testimonial_author_id = get_option('easy_t_testimonial_author', 1);
				
				foreach($response->testimonials as $testimonial){							
					//look for a testimonial with the same HTID
					//if not found, insert this one
					$args = array(
						'post_type' => 'testimonial',
						'meta_query' => array(
							array(
								'key' => '_ikcf_htid',
								'value' => $testimonial->id,
							)
						)
					 );
					$postslist = get_posts( $args );
					
					//if this is empty, a match wasn't found and therefore we are safe to insert
					if(empty($postslist)){				
						//insert the testimonials
						
						//defaults
						$the_name = isset( $testimonial->name ) ? $testimonial->name : '';
						$the_rating = isset( $testimonial->rating ) ? $testimonial->rating : 5;
						$the_position = isset( $testimonial->position ) ? $testimonial->position : '';
						$the_item_reviewed = isset( $testimonial->item_reviewed ) ? $testimonial->item_reviewed : '';
						$the_email = isset( $testimonial->email ) ? $testimonial->email : '';
						
						$tags = array();
					   
						$post = array(
							'post_title'    => $testimonial->name,
							'post_content'  => $testimonial->body,
							'post_category' => array(1),  // custom taxonomies too, needs to be an array
							'tags_input'    => $tags,
							'post_status'   => 'publish',
							'post_type'     => 'testimonial',
							'post_date'		=> $testimonial->publish_time,
							'post_author' 	=> $testimonial_author_id
						);
					
						$new_id = wp_insert_post($post);
					   
						update_post_meta( $new_id,	'_ikcf_client',		$the_name );
						update_post_meta( $new_id,	'_ikcf_rating',		$the_rating );
						update_post_meta( $new_id,	'_ikcf_htid',		$testimonial->id );
						update_post_meta( $new_id,	'_ikcf_position',	$the_position );
						update_post_meta( $new_id,	'_ikcf_other',		$the_item_reviewed );
						update_post_meta( $new_id,	'_ikcf_email',		$the_email );
					   
						$inserted = true;
						
						//update the last inserted id
						update_option( 'easy_t_hello_t_last_time', $the_time );
					}
				}
			}
		}
		
		//all done, so say something letting them know.
		$this->messages[] = 'Success!  Your Testimonials have been imported!';
	}

	function hello_t_nag_ignore() {
		global $current_user;
		$user_id = $current_user->ID;
		/* If user clicks to ignore the notice, add that to their user meta */
		if ( isset($_GET['hello_t_nag_ignore']) && '0' == $_GET['hello_t_nag_ignore'] ) {
			 add_user_meta($user_id, 'hello_t_nag_ignore', 'true', true);
		}
	}

	//activate the cron job
	function hello_t_cron_activate(){
		wp_schedule_event( time(), 'hourly', 'hello_t_subscription');
	}

	//deactivate the cron job when the plugin is deactivated
	function hello_t_cron_deactivate(){
		wp_clear_scheduled_hook('hello_t_subscription');
	}
}