<?php
class TestimonialsPlugin_Importer
{
	var $root;
	
    public function __construct($root)
    {
		$this->root = $root;
	}
	
	//convert CSV to array
	private function csv_to_array($filename='', $delimiter=','){
		if(!file_exists($filename) || !is_readable($filename))
			return FALSE;

		$header = NULL;
		$data = array();
		
		if (($handle = fopen($filename, 'r')) !== FALSE)
		{
			while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
			{
				if(!$header){
					$header = $row;
				} else {
					$data[] = array_combine($header, $row);
				}
			}
			fclose($handle);
		}
		return $data;
	}
	
	//process data from CSV import
	private function import_testimonials_from_csv($testimonials_file){	
		//increase execution time before beginning import, as this could take a while
		set_time_limit(0);		
		
		//use current timestamp for batch number
		$batch_number = time();
		
		$testimonials = $this->csv_to_array($testimonials_file);
		
		foreach($testimonials as $testimonial){					
			//defaults
			$the_name = $the_body = '';

			if (isset ($testimonial['Title'])) {
				$the_name = $testimonial['Title'];
			}
			
			if (isset ($testimonial['Body'])) {
				$the_body = $testimonial['Body'];
			}	
			
			//look for a testimonial with the title and body
			//if not found, insert this one
			$postslist = get_page_by_title( $the_name, OBJECT, 'testimonial' );
			
			//if this is empty, a match wasn't found and therefore we are safe to insert
			if(empty($postslist)){				
				//insert the testimonials				
				$tags = array();
			   
				$post = array(
					'post_title'    => $the_name,
					'post_content'     => $the_body,
					'post_category' => array(1),  // custom taxonomies too, needs to be an array
					'tags_input'    => $tags,
					'post_status'   => 'publish',
					'post_type'     => 'testimonial',
					'post_author' => get_option('easy_t_testimonial_author', 1)
				);
			
				$new_id = wp_insert_post($post);
			   
				// assign Staff Member Categories if any were specified
				// NOTE: we are using wp_set_object_terms instead of adding a tax_input key to wp_insert_posts, because 
				// it is less likely to fail b/c of permissions and load order (i.e., taxonomy may not have been created yet)
				if (!empty($testimonial['Categories'])) {
					$post_cats = explode(',', $testimonial['Categories']);
					$post_cats = array_map('intval', $post_cats); // sanitize to ints
					wp_set_object_terms($new_id, $post_cats, 'easy-testimonial-category');
				}
			   
				//defaults, in case certain data wasn't in the CSV			
				$client_name = isset($testimonial['Client Name']) ? $testimonial['Client Name'] : "";
				$email = isset($testimonial['E-Mail Address']) ? $testimonial['E-Mail Address'] : "";
				$position_location_other = isset($testimonial['Position / Location / Other']) ? $testimonial['Position / Location / Other'] : "";
				$location_product_other = isset($testimonial['Location / Product / Other']) ? $testimonial['Location / Product / Other'] : "";
				$rating = isset($testimonial['Rating']) ? $testimonial['Rating'] : "";
				$htid = isset($testimonial['HTID']) ? $testimonial['HTID'] : "";
				$featured_image = isset($testimonial['Featured Image']) ? $testimonial['Featured Image'] : "";
			   
				update_post_meta( $new_id, '_ikcf_client', $client_name );
				update_post_meta( $new_id, '_ikcf_email', $email );
				update_post_meta( $new_id, '_ikcf_position', $position_location_other );
				update_post_meta( $new_id, '_ikcf_other', $location_product_other );
				update_post_meta( $new_id, '_ikcf_rating', $rating );
				update_post_meta( $new_id, '_ikcf_htid', $htid );
				update_post_meta( $new_id, '_ikcf_import_batch', $batch_number );
				
				// Look for a photo path on CSV
				// If found, try to import this photo and attach it to this staff member
				$this->import_testimonial_photo($new_id, $featured_image);		
			   
				$inserted = true;
				echo "Successfully imported '{$the_name}'!\n";
			} else { //rejected as duplicate
				echo "Could not import {$the_name}; rejected as Duplicate\n";
			}
		}
	}
	
	function import_testimonial_photo($post_id = '', $photo_source = ''){	
		//used for overriding specific attributes inside media_handle_sideload
		$post_data = array();
		
		//set attributes in override array
		$post_data = array(
			'post_title' => '', //photo title
			'post_content' => '', //photo description
			'post_excerpt' => '', //photo caption
		);
	
		require_once( ABSPATH . 'wp-admin/includes/image.php');
		require_once( ABSPATH . 'wp-admin/includes/media.php' );//need this for media_handle_sideload
		require_once( ABSPATH . 'wp-admin/includes/file.php' );//need this for the download_url function
		
		$desc = ''; // photo description
		
		$picture = urldecode($photo_source);
		
		// Download file to temp location
		$tmp = download_url( $picture);
		
		// Set variables for storage
		// fix file filename for query strings
		preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $picture, $matches);
		$file_array['name'] = isset($matches[0]) ? basename($matches[0]) : basename($picture);
		$file_array['tmp_name'] = $tmp;

		// If error storing temporarily, unlink
		if ( is_wp_error( $tmp ) ) {
			//$error_string = $tmp->get_error_message();
			//echo '<div id="message" class="error"><p>' . $error_string . '</p></div>';
			
			@unlink($file_array['tmp_name']);
			$file_array['tmp_name'] ='';
		}
		
		$id = media_handle_sideload( $file_array, $post_id, $desc, $post_data );

		// If error storing permanently, unlink
		if ( is_wp_error($id) ) {
			//$error_string = $id->get_error_message();
			//echo '<div id="message" class="error"><p>' . $error_string . '</p></div>';
			
			@unlink($file_array['tmp_name']);
		} else {		
			//add as the post thumbnail
			if( !empty($post_id) ){
				add_post_meta($post_id, '_thumbnail_id', $id, true);
			}
		}
	}
	
	//displays fields to allow user to upload and import a CSV of testimonials
	//if a file has been uploaded, this will dispatch the file to the import function
	public function csv_importer(){	
		
		// Load Importer API
		require_once ABSPATH . 'wp-admin/includes/import.php';

		if ( !class_exists( 'WP_Importer' ) ) {
			$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
			if ( file_exists( $class_wp_importer ) )
				require_once $class_wp_importer;
		}		
		
		if(empty($_FILES)){	
			//echo '<form method="POST" action="" enctype="multipart/form-data">';	
			echo "<p>Use the form below to upload your CSV file for importing.</p>";
			echo "<p><strong>Example CSV Data:</strong></p>";
			echo "<p><code>'Title','Body','Client Name','E-Mail Address','Position / Location / Other','Location / Product / Other','Rating','HTID','Featured Image','Categories'</code></p>";
			echo "<p><strong>Please Note:</strong> the first line of the CSV will need to match the text in the above example, for the Import to work.  Featured Image is expecting a path to an accessible image online.  Depending on your server settings, you may need to run the import several times if your script times out.</p>";

			echo '<div class="gp_upload_file_wrapper">';
				ob_start();					
				wp_import_upload_form( add_query_arg('step', '1#tab-importer') );
				$import_form_html = ob_get_contents();
				$import_form_html = str_replace('<form ', '<div data-gp-ajax-form="1" ', $import_form_html);
				$import_form_html = str_replace('</form>', '</div>', $import_form_html);
				
				// must remove this hidden "action" input, or the form will not 
				// save proerly (it will keep going to options.php)
				$import_form_html = str_replace('<input type="hidden" name="action" value="save" />', '', $import_form_html);
				ob_end_clean();					
				echo $import_form_html;
			echo '</div>';
			//echo '</form>';
		} else {
			$file = wp_import_handle_upload();
			
			echo '<h4>Log</h4>';
			echo '<textarea rows="20" class="import_response">';
			
			//if there is an error, output a message containing the error
			if ( isset( $file['error'] ) ) {
				echo "Sorry, there has been an error.\n";
				echo esc_html( $file['error'] ) . "\n";
			// if the file doesn't exists, output a message about it
			} else if ( ! file_exists( $file['file'] ) ) {
				echo "Sorry, there has been an error.\n";
				printf( "The export file could not be found at %s. It is likely that this was caused by a permissions problem.\n", esc_html( $file['file'] ) );
			// otherwise, if there is no error and the file exists, go ahead and process the file
			} else {			
				$fileid = (int) $file['id'];
				$file = get_attached_file($fileid);
				$this->import_testimonials_from_csv($file);
				
				echo "\nTestimonials successfully imported!\n";
			}
			
			echo '</textarea>';//close response
			
			echo '<p><a class="button-primary button" href="/wp-admin/admin.php?page=easy-testimonials-import-export#tab-importer" title="Import More Testimonials">Import More Testimonials</a></p>';
		}
	}
}