<?php
class TestimonialsPlugin_Exporter
{	
	public function __construct(){
	}
	
	public function output_form()
	{
		//updated to be used inside sajak
		?>		
		<p>Click the "Export My Testimonials" button below to download a CSV file of your testimonials.</p>
		<input type="hidden" name="_easy_t_do_export" value="_easy_t_do_export" />
		<p class="submit" style="margin-top:0;">
				<a href="?page=easy-testimonials-import-export&process_export=true" class="button-primary" title="<?php _e('Export My Testimonials', 'easy-testimonials') ?>"><?php _e('Export My Testimonials', 'easy-testimonials') ?></a>
			</p>
		<?php
	}
	
	public function process_export($filename = "testimonials-export.csv")
	{		
		//load testimonials
		$args = array(
			'posts_per_page'   => -1,
			'offset'           => 0,
			'category'         => '',
			'category_name'    => '',
			'orderby'          => 'post_date',
			'order'            => 'DESC',
			'include'          => '',
			'exclude'          => '',
			'meta_key'         => '',
			'meta_value'       => '',
			'post_type'        => 'testimonial',
			'post_mime_type'   => '',
			'post_parent'      => '',
			'post_status'      => 'publish',
			'suppress_filters' => true 				
		);
		
		$testimonials = get_posts($args);
		
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header('Content-Description: File Transfer');
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename={$filename}");
		header("Expires: 0");
		header("Pragma: public");
		
		
		// open file handle to STDOUT
		$fh = @fopen( 'php://output', 'w' );
		
		// output the headers first
		fputcsv($fh, array('Title','Body','Client Name', 'E-Mail Address', 'Position / Location / Other','Location / Product / Other','Rating','HTID', 'Featured Image', 'Categories'));
			
		// now output one row for each testimonial
		foreach($testimonials as $testimonial)
		{
			$row = array();
			$row['title'] = $testimonial->post_title;
			$row['body'] = $testimonial->post_content;
			$row['client_name'] = get_post_meta( $testimonial->ID, '_ikcf_client', true);
			$row['email_address'] = get_post_meta( $testimonial->ID, '_ikcf_email', true);
			$row['position_location_other'] = get_post_meta( $testimonial->ID, '_ikcf_position', true);
			$row['location_product_other'] = get_post_meta( $testimonial->ID, '_ikcf_other', true);
			$row['rating'] = get_post_meta( $testimonial->ID, '_ikcf_rating', true);
			$row['htid'] = get_post_meta( $testimonial->ID, '_ikcf_htid', true);
			$row['photo_path'] = $this->get_photo_path( $testimonial->ID );
			$row['categories'] = $this->list_taxonomy_ids( $testimonial->ID, 'easy-testimonial-category' );
			
			fputcsv($fh, $row);
		}
		
		// Close the file handle
		fclose($fh);
		exit();
	}
	
	/* 
	 * Get a comma separated list of IDs representing each term of $taxonomy that $post_id belongs to
	 *
	 * @returns comma separated list of IDs, or empty string if no terms are assigned
	*/
	function list_taxonomy_ids($post_id, $taxonomy)
	{
		$terms = wp_get_post_terms( $post_id, $taxonomy ); // could also pass a 3rd param, $args
		if (is_wp_error($terms)) 
		{
		   $error_string = $terms->get_error_message();
		   echo '<div id="message" class="error"><p>' . $error_string . '</p></div>';
		   die();
			return '';
		}
		else {
			$term_list = array();
			foreach ($terms as $t) {
				$term_list[] = $t->term_id;
			}
			return implode(',', $term_list);
		}
	}
	
	/*
	 * Get the path to the testimonial's photo
	 *
	 * @returns a string representing the path to the photo
	*/
	function get_photo_path($post_id){
		$image_str = "";
		
		if ( has_post_thumbnail( $post_id ) ){
			$the_post_thumbnail_id = get_post_thumbnail_id( $post_id );
			//check to be sure this doesn't have a bad record from back when WP_Error objects
			//were being stored on image fields during CSV imports in some cases
			if( !is_wp_error( $the_post_thumbnail_id ) ){
				$image = wp_get_attachment_image_src( $the_post_thumbnail_id, 'single-post-thumbnail' );
				$image_str = $image[0];
			}
		}
		
		return $image_str;
	}
}