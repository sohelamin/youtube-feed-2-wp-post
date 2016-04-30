<?php
/**
 * Plugin Name: YouTube Video to WP Post
 * Plugin URI: http://www.appzcoder.com
 * Description: Import YouTube Video as WordPress Post.
 * Version: 1.5
 * Author: Sohel Amin
 * Author URI: http://www.sohelamin.com
 * License: GPL2
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

class Appzcoder_YouTube_Video_To_WP_Post {
    /**
     * Instance of this class.
     *
     * @var static
     */
    protected static $instance;

    /**
     * Class constructor.
     */
    public function __construct() {
        // load the plugin
        add_action( 'init', [ $this, 'plugin_init' ] );

        register_activation_hook( __FILE__, [ $this, 'plugin_activate' ] );
        register_deactivation_hook( __FILE__, [ $this, 'plugin_deactivate' ] );
    }

    /**
     * Instantiate the class as an object.
     *
     * @return static
     */
    public static function init() {
        if ( null === static::$instance ) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Plugin activation hook.
     *
     * @return void
     */
    function plugin_activate() {
        // Activation code here...
    }

    /**
     * Plugin deactivation hook.
     *
     * @return void
     */
    function plugin_deactivate() {
        wp_clear_scheduled_hook( 'yt2wp_youtube_video_import' );
    }

    /**
     * Init the plugin.
     *
     * @return void
     */
    public function plugin_init() {
        // Define constants
        define( 'YT2WP_FILE', __FILE__ );
        define( 'YT2WP_PATH', dirname( YT2WP_FILE ) );
        define( 'YT2WP_INCLUDES', YT2WP_PATH . '/includes' );
        define( 'YT2WP_VIEWS', YT2WP_INCLUDES . '/views' );
        define( 'YT2WP_URL', plugins_url( '', YT2WP_FILE ) );
        define( 'YT2WP_ASSETS', YT2WP_URL . '/assets' );

        // Includes required files
        include YT2WP_INCLUDES . '/functions.php';
        include YT2WP_INCLUDES . '/class-admin-menu.php';
        include YT2WP_INCLUDES . '/class-youtube.php';

        // Instantiate classes
        new Admin_Menu();

        // Action hooks
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'admin_footer', 'yt2wp_enqueue_js' );

        add_action( 'yt2wp_youtube_video_import', 'yt2wp_do_youtube_video_import' );
        // Adding meta box
        add_action( 'add_meta_boxes', function() {
            add_meta_box( 'yt2wp_post_youtube_video_id', 'Youtube Video ID', 'yt2wp_youtube_video_id', 'post', 'normal', 'high' );
        } );
        // Saving meta box data
        add_action( 'save_post', function( $id ) {
            if ( isset( $_POST['yt2wp_youtube_video_id'] ) ) {
                update_post_meta(
                    $id,
                    'yt2wp_youtube_video_id',
                    strip_tags( $_POST['yt2wp_youtube_video_id'] )
                );
            }
        } );

        // Adding shortcode for preview the youtube video on frontend
        add_shortcode( 'yt2wp_show_youtube_video', 'yt2wp_do_shortcode_func' );

        add_action( 'wp_ajax_yt2wp_import', 'yt2wp_import_ajax_handler' );

        // Filter hooks
        add_filter( 'cron_schedules', 'yt2wp_add_new_cron_schedule' );

        $plugin = plugin_basename( __FILE__ );
        add_filter( 'plugin_action_links_' . $plugin, 'yt2wp_plugin_settings_link' );

        add_action( 'update_option_yt2wp_auto_import', 'yt2wp_set_cron_job_schedule', 10, 3 );
        add_action( 'update_option_yt2wp_cron_job_schedule', 'yt2wp_set_cron_job_schedule', 10, 3 );
        add_action( 'update_option_yt2wp_youtube_user_id', 'yt2wp_set_cron_job_schedule', 10, 3 );
    }

    /**
     * Enqueue scripts.
     */
    public function enqueue_scripts() {
        // styles
        wp_enqueue_style( 'appzcoder-yt2wp-styles', YT2WP_ASSETS . '/css/style.css', false );
    }
}

Appzcoder_YouTube_Video_To_WP_Post::init();
