<?php
/**
 * Plugin Name: YouTube Video to WP Post
 * Plugin URI: http://www.appzcoder.com
 * Description: A wordpress plugin which is simply allow you to import your YouTube video feed as a post within a selective time.
 * Version: 1.4
 * Author: Sohel Amin
 * Author URI: http://www.sohelamin.com
 * License: GPL2
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * YouTube Video to WP Post
 *
 * @author Sohel Amin
*/
class YouTube_Video_To_WP_Post {
    /**
     * Instance of this class.
     *
     * @var static
     */
    protected static $instance;

    /**
     * Constructor function.
     *
     * @access public
     * @return void
     */
    public function __construct() {
        // Active or deactive hook upon plugin activation/deactivation
        register_activation_hook( __FILE__, array( $this, 'activate_youtube_video_to_wp_post' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate_youtube_video_to_wp_post' ) );
        // Adding new schedule
        add_filter( 'cron_schedules', array( $this, 'ac_add_new_cron_schedule' ) );
        // Adding custom hook
        add_action( 'ac_custom_youtube_video_import', array( $this, 'ac_do_youtube_video_import' ) );
        // Adding meta box
        add_action( 'add_meta_boxes', function() {
            add_meta_box( 'ac_post_youtube_video_id', 'Youtube Video ID', array( $this, 'ac_youtube_video_id' ), 'post', 'normal', 'high' );
        });
        // Saving meta box data
        add_action( 'save_post', function( $id ) {
            if ( isset( $_POST['ac_youtube_video_id'] ) ){
                update_post_meta(
                    $id,
                    'ac_youtube_video_id',
                    strip_tags( $_POST['ac_youtube_video_id'] )
                );
            }
        });
        // Adding shortcode for preview the youtube video on frontend
        add_shortcode( 'ac_show_youtube_video', array( $this, 'ac_do_shortcode_func' ) );

        // Instantiate settings page
        if ( is_admin() ) {
            require_once dirname( __FILE__ ) . '/includes/class-youtube-video-to-wp-post-settings.php';
            YouTube_Video_To_WP_Post_Settings::init();
            require_once dirname( __FILE__ ) . '/includes/class-youtube-video-to-wp-post-shortcode-tinymce.php';
        }

        // Adding settings link on plugin page
        $plugin = plugin_basename(__FILE__);
        add_filter("plugin_action_links_$plugin", array($this, 'ac_plugin_settings_link') );
    }

    /**
     * Instantiate the class as an object.
     *
     * @return static
     */
    public static function init() {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Setting up cronjob time period.
     *
     * @param array $schedules
     *
     * @return array
     */
    public function ac_add_new_cron_schedule( $schedules )  {
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
     * Activate the plugin.
     */
    public function activate_youtube_video_to_wp_post( ) {
        $this->ac_register_cronjob_schedule();
    }

    /**
     * Deactivate the plugin.
     */
    public function deactivate_youtube_video_to_wp_post( ) {
        $this->ac_destroy_cronjob_schedule();
    }

    /**
     * Calling cronjob schedule.
     *
     * @param array $recurrence
     * @param boolean $opt_update
     *
     * @return void
     */
    public function ac_register_cronjob_schedule( $recurrence = 'every_thirty_min', $opt_update = true ) {
        if ( ! wp_next_scheduled( 'ac_custom_youtube_video_import' ) ) {
            if( $recurrence && $opt_update == true ) {
                update_option( 'ac_cron_job_schedule', $recurrence );
            }

            wp_schedule_event( time(), $recurrence, 'ac_custom_youtube_video_import' );
        }
    }

    /**
     * Destroy the schedule.
     *
     * @return void
     */
    public function ac_destroy_cronjob_schedule() {
        if ( false !== ( $time = wp_next_scheduled( 'ac_custom_youtube_video_import' ) ) ) {
           wp_clear_scheduled_hook( 'ac_custom_youtube_video_import' );
        }
    }

    /**
     * Video importing via youtube gdata api.
     *
     * @return boolean
     */
    public function ac_do_youtube_video_import() {
        require_once dirname( __FILE__ ) . '/includes/Youtube.php';
        $youtube_id = get_option('ac_youtube_user_id');
        //$youtube_id = 'UCnllbLq1u_SxHXzkauxZsPw';

        $youtube = new Youtube(array('key' => 'AIzaSyDDefsgXEZu57wYgABF7xEURClu4UAzyB8'));

        // Search only Videos in a given channel, Return an array of PHP objects
        $videos_list = $youtube->searchChannelVideos('', $youtube_id, 50, 'date');

        $categories_list = array(
            1 => 'Film & Animation',
            2 => 'Autos & Vehicles',
            10 => 'Music',
            15 => 'Pets & Animals',
            17 => 'Sports',
            18 => 'Short Movies',
            19 => 'Travel & Events',
            20 => 'Gaming',
            21 => 'Videoblogging',
            22 => 'People & Blogs',
            23 => 'Comedy',
            24 => 'Entertainment',
            25 => 'News & Politics',
            26 => 'Howto & Style',
            27 => 'Education',
            28 => 'Science & Technology',
            29 => 'Nonprofits & Activism',
            30 => 'Movies',
            31 => 'Anime/Animation',
            32 => 'Action/Adventure',
            33 => 'Classics',
            34 => 'Comedy',
            35 => 'Documentary',
            36 => 'Drama',
            37 => 'Family',
            38 => 'Foreign',
            39 => 'Horror',
            40 => 'Sci-Fi/Fantasy',
            42 => 'Shorts',
            43 => 'Shows',
            44 => 'Trailers',
        );

        foreach ($videos_list as $list) {
            $videoInfo = $youtube->getVideoInfo($list->id->videoId);

            $videos['title'] = $list->snippet->title;
            $videos['video_id'] = $list->id->videoId;
            $videos['description'] = $list->snippet->description;
            $videos['view_count'] = $videoInfo->statistics->viewCount;
            $videos['date'] = date( 'Y-m-d H:i:s', strtotime( $list->snippet->publishedAt ) );
            $videos['category'] = $categories_list[$videoInfo->snippet->categoryId];
            $videos['keyword'] = '';

            $video_array[] = $videos;
        }

        $result = $this->ac_insert_post( $video_array );

        return $result;
    }

    /**
     * For XML object to array conversion.
     *
     * @param object $result
     *
     * @return array
     */
    function ac_xml2array ( $result, $out = array () ) {
        foreach ( (array) $result as $index => $node )
            $out[$index] = ( is_object ( $node ) ) ? $this->ac_xml2array ( $node ) : $node;

        return $out;
    }

    /**
     * Custom meta for video id.
     *
     * @param array $post
     *
     * @return void
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
     * Saving the video feed into post.
     *
     * @param array $data
     *
     * @return void
     */
    function ac_insert_post( $data ) {
        // Getting all posts
        foreach($data as $insert_post) {
            // Query for the meta key and meta value
            global $wpdb;

            $meta_key = 'ac_youtube_video_id';
            $insert_post_video_id = $insert_post['video_id'];
            $table_name = $wpdb->prefix . 'postmeta';
            $result = $wpdb->get_results( "SELECT meta_value FROM  $table_name WHERE meta_key='$meta_key' AND meta_value='$insert_post_video_id'" );

            if ( count( $result ) == 0 ) {
                // Getting category id
                $category_id = get_cat_ID( $insert_post['category'] );
                // Create post object
                $user_id = get_current_user_id();
                $my_post = array(
                  'post_title'    => $insert_post['title'],
                  'post_content'  => $insert_post['description'],
                  'post_status'   => 'publish',
                  'post_author'   => 1, // Change the author id
                  'post_category' => array( $category_id )
                );

                // Insert the post into the database
                $post_id = wp_insert_post( $my_post );

                update_post_meta( $post_id, 'ac_youtube_video_id', $insert_post_video_id );
            }
        }
    }

    /**
     * Shortcode creating function.
     *
     * @param array $stts
     *
     * @return void
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
     * Add settings link on plugin page.
     *
     * @param array $links
     *
     * @return array
     */
    function ac_plugin_settings_link( $links ) {
        $settings_link = '<a href="admin.php?page=youtube-video-to-wp-post-settings">Settings</a>';

        array_unshift( $links, $settings_link );

        return $links;
    }
}

// Instantiate plugin main class
YouTube_Video_To_WP_Post::init();
