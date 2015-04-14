<?php
/**
 * Plugin Name: YouTube Video to WP Post
 * Plugin URI: http://www.appzcoder.com
 * Description: A wordpress plugin which is simply allow you to import your YouTube video feed as a post within a selective time.
 * Version: 1.2
 * Author: Sohel Amin
 * Author URI: http://www.sohelamin.com
 * License: GPL2
 */

// Disallowed to call this file directly through url
if ( !defined( 'ABSPATH' ) ) exit;
/**
 * YouTube Video to WP Post
 *
 * @author Sohel Amin
*/
class AppzCoder_YouTube_Video_To_WP_Post {


    /**
     * Class constructor
     */	
	public function __construct() {
		// Active or deactive hook upon plugin activation/deactivation
		register_activation_hook( __FILE__, array( $this, 'ac_yf2wp_activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'ac_yf2wp_deactivate' ) );			
		// Adding new schedule
		add_filter( 'cron_schedules', array( $this, 'ac_add_new_cron_schedule' ) );	
		// Adding custom hook
		add_action( 'ac_custom_youtube_video_import', array( $this, 'ac_do_youtube_video_import' ) );
		// Adding meta box
		add_action( 'add_meta_boxes', function() {
			add_meta_box( 'ac_post_youtube_video_id', 'Youtube Video ID', array( $this, 'ac_youtube_video_id' ), 'post', 'normal', 'high' );
		});
		// Saving meta box data
		add_action( 'save_post', function($id) {
			if ( isset($_POST['ac_youtube_video_id'])){
				update_post_meta(
					$id,
					'ac_youtube_video_id',
					strip_tags($_POST['ac_youtube_video_id'])
				);
			}
		});	
		// Adding shortcode for preview the youtube video on frontend
		add_shortcode( 'ac_show_youtube_video', array( $this, 'ac_do_shortcode_func' ) );	

		// Intiate settings page
		if ( is_admin() ) {
			require_once dirname( __FILE__ ) . '/settings.php';
			new AppzCoder_YouTube_Video_Settings();
			require_once dirname( __FILE__ ) . '/inc/class-appzcoder-shortcode-tinymce.php';
		}
		
		// Adding settings link on plugin page
		$plugin = plugin_basename(__FILE__); 
		add_filter("plugin_action_links_$plugin", array($this, 'ac_plugin_settings_link') );
	}

    /**
     * Initiate the class as an object.
     *
     * @return Object
     */	
	public static function init() {
		static $instance = false;
		if ( ! $instance ) {
			$instance = new self();
		}
		return $instance;
	}

    /**
     * Setting up cronjob time period
     *
     * @param Array $schedules
     *
     * @return Array
     */		
	public function ac_add_new_cron_schedule( $schedules )	{
	   $schedules['every_thirty_min'] = array ( 
			'interval' => 1800,
			'display'  => __('Every 30 Minutes')
		);
		$schedules['weekly'] = array(
			'interval' => 604800,
			'display' => __('Once Weekly')
		);
		$schedules['monthly'] = array(
			'interval' => 2635200,
			'display' => __('Once a month')
		);
		return $schedules;
	}	

    /**
     * Calling cronjob schedule
     *
     * @param Array $recurrence
     *
     * @return Void
     */			
	public function ac_yf2wp_activate( $recurrence = 'every_thirty_min', $opt_update = true ) {				
		if ( ! wp_next_scheduled( 'ac_custom_youtube_video_import' ) ) {
			if( $recurrence && $opt_update == true ) {
				update_option( 'ac_cron_job_schedule', $recurrence );
			}
			wp_schedule_event( time(), $recurrence, 'ac_custom_youtube_video_import' );
		}
	}

    /**
     * Destroy the schedule
     *
     * @return Void
     */			
	public function ac_yf2wp_deactivate() {
		if ( false !== ( $time = wp_next_scheduled( 'ac_custom_youtube_video_import' ) ) ) {
		   wp_clear_scheduled_hook( 'ac_custom_youtube_video_import' );
		}
	}
	
	/**
     * Video importing via youtube gdata api
     *
     * @return Boolean
     */	
	public function ac_do_youtube_video_import() {
		
		$youtube_id = get_option('ac_youtube_user_id'); 
		//$youtube_id = 'UCnllbLq1u_SxHXzkauxZsPw'; 
		// set feed URL
		$feedURL = 'http://gdata.youtube.com/feeds/api/users/' .$youtube_id. '/uploads?v=2';
		
		// read feed into SimpleXML object
		$sxml = simplexml_load_file( $feedURL );
		
		$video_array = array();
		// iterate over entries in feed
		foreach ( $sxml->entry as $entry ) {

			// get nodes in media: namespace for media information
			$media = $entry->children('http://search.yahoo.com/mrss/');
			// get video player URL
			$attrs = $media->group->player->attributes();
			$watch = $attrs['url']; 
			// get video player id
			$yt = $media->children( 'http://gdata.youtube.com/schemas/2007' );
			$youtubeid = $yt->videoid;		  
			// get video thumbnail
			$attrs = $media->group->thumbnail[0]->attributes();
			$thumbnail = $attrs['url']; 
			
			// get video published date
			$date = date('Y-m-d H:i:s', strtotime($entry->published));	
			
			// get <yt:duration> node for video length
			$yt = $media->children('http://gdata.youtube.com/schemas/2007');
			$attrs = $yt->duration->attributes();
			$length = $attrs['seconds']; 

			// get <yt:stats> node for viewer statistics
			$yt = $entry->children('http://gdata.youtube.com/schemas/2007');
			$attrs = $yt->statistics->attributes();
			$viewCount = $attrs['viewCount']; 

			// get <gd:rating> node for video ratings
			$gd = $entry->children('http://schemas.google.com/g/2005'); 
			if ( $gd->rating ) {
				$attrs = $gd->rating->attributes();
				$rating = $attrs['average']; 
			} else {
				$rating = 0; 
			} 
		
			$videos['title'] = $this->ac_xml2array( $media->group->title );
			$videos['video_id'] = $this->ac_xml2array( $youtubeid);
			$videos['description'] = $this->ac_xml2array( $media->group->description );
			$videos['view_count'] = $this->ac_xml2array( $viewCount );
			$videos['date'] = $date;
			$videos['category'] = $this->ac_xml2array( $media->group->category );
			$videos['keyword'] = $this->ac_xml2array( $media->group->keyword );
			
			$video_array[] = $videos;
		}
		$result = $this->ac_insert_post( $video_array );
		return $result;
	}

    /**
     * For XML Object to Array conversion
     *
     * @param Object $result
     *
     * @return Array
     */			
	function ac_xml2array ( $result, $out = array () ) {
		foreach ( (array) $result as $index => $node )
			$out[$index] = ( is_object ( $node ) ) ? $this->ac_xml2array ( $node ) : $node;
		return $out;
	}
	
    /**
     * Custom meta for video id
     *
     * @param Array $post
     *
     * @return Void
     */			
	function ac_youtube_video_id( $post )
	{		
		$video_id = get_post_meta($post->ID, 'ac_youtube_video_id', true);
		?>
		<p>
			<label for="ac_youtube_video_id">Youtube Video ID: </label>
			<input type="text" name="ac_youtube_video_id" id="ac_youtube_video_id" value="<?php echo esc_attr($video_id); ?>" />
		</p>
		<?php
	}				

    /**
     * Saving the video feed into post
     *
     * @param Array $data
     *
     * @return Void
     */			
	function ac_insert_post( $data ) {		
		// Getting all posts
		foreach($data as $insert_post) {		
			// Query for the meta key and meta value	
			global $wpdb;
			$meta_key = 'ac_youtube_video_id';
			$insert_post_video_id = $insert_post['video_id'][0];
			$table_name = $wpdb->prefix . 'postmeta';
			$result = $wpdb->get_results( "SELECT meta_value FROM  $table_name WHERE meta_key='$meta_key' AND meta_value='$insert_post_video_id'" );
			if ( count($result)==0 ) {				
				// Getting category id
				$category_id = get_cat_ID( $insert_post['category'][0] );
				// Create post object
				$user_id = get_current_user_id();
				$my_post = array(
				  'post_title'    => $insert_post['title'][0],
				  'post_content'  => $insert_post['description'][0],
				  'post_status'   => 'publish',
				  'post_author'   => 1,
				  'post_category' => array( $category_id ) 
				);
				// Insert the post into the database
				$post_id = wp_insert_post( $my_post );					
				update_post_meta( $post_id, 'ac_youtube_video_id', $insert_post_video_id );
			}
		}		
	}
	
    /**
     * Shortcode creating function
     *
     * @param Array $stts
     *
     * @return Void
     */			
	function ac_do_shortcode_func( $atts ) {
		$a = shortcode_atts( array(
			'width' => '420',
			'height' => '315',
			'video_id' => '',
		), $atts );
		$youtube_video_width = $a['width'];
		$youtube_video_height = $a['height'];			
		$youtube_video_id = get_post_meta( get_the_ID(), 'ac_youtube_video_id', true );	
		if ( $youtube_video_id != '' ) {
			return '<iframe src="http://www.youtube.com/embed/'.$youtube_video_id.'?rel=0" frameborder="0" allowfullscreen width="'.$youtube_video_width.'" height="'.$youtube_video_height.'"></iframe>';
		} else {
			return '<h3>Sorry there is no video within the post!</h3>';
		}	
	}

    /**
     * Add settings link on plugin page
     *
     * @param Array $links
     *
     * @return Array
     */		
	function ac_plugin_settings_link( $links ) { 
		$settings_link = '<a href="admin.php?page=youtube-feed-2-wp-post/settings.php">Settings</a>'; 
		array_unshift( $links, $settings_link ); 
		return $links; 
	}


}

AppzCoder_YouTube_Video_To_WP_Post::init();
