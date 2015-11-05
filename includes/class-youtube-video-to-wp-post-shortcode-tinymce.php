<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * AppzCoder Shortcode Tinymce
 *
 * @author Sohel Amin
*/
class YouTube_Video_To_WP_Post_Shortcode_Tinymce {
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
        add_action( 'admin_init', array($this, 'ac_shortcode_button') );
        add_action( 'admin_footer', array($this, 'ac_get_shortcodes') );
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
     * Create a shortcode button for tinymce
     *
     * @return void
     */
    public function ac_shortcode_button() {
        if( current_user_can('edit_posts') && current_user_can('edit_pages') ) {
            add_filter( 'mce_external_plugins', array($this, 'ac_add_buttons') );
            add_filter( 'mce_buttons', array($this, 'ac_register_buttons') );
        }
    }

    /**
     * Add new javascript to the plugin script array
     *
     * @param  array $plugin_array
     *
     * @return array
     */
    public function ac_add_buttons( $plugin_array ) {
        $plugin_array['pushortcodes'] = plugin_dir_url( __FILE__ ) . '../js/shortcode-tinymce-button.js';

        return $plugin_array;
    }

    /**
     * Add new button to tinymce
     *
     * @param  array $buttons
     *
     * @return array
     */
    public function ac_register_buttons( $buttons ) {
        array_push( $buttons, 'separator', 'pushortcodes' );

        return $buttons;
    }

    /**
     * Add shortcode JS to the page
     *
     * @return void
     */
    public function ac_get_shortcodes() {
        global $shortcode_tags;

        echo '<script type="text/javascript">
        var shortcodes_button = new Array();';

        $count = 0;

        foreach($shortcode_tags as $tag => $code) {
            echo "shortcodes_button[{$count}] = '{$tag}';";
            $count++;
        }

        echo '</script>';
    }
}

YouTube_Video_To_WP_Post_Shortcode_Tinymce::init();
