<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * AppzCoder Shortcode Tinymce
 *
 * @author Sohel Amin
*/
class Shortcode_Tinymce {
    /**
     * Constructor function.
     *
     * @access public
     * @return void
     */
    public function __construct() {
        add_action( 'admin_init', [ $this, 'shortcode_button' ] );
        add_action( 'admin_footer', [ $this, 'get_shortcodes' ] );
    }

    /**
     * Create a shortcode button for tinymce
     *
     * @return void
     */
    public function shortcode_button() {
        if ( current_user_can( 'edit_posts' ) && current_user_can( 'edit_pages' ) ) {
            add_filter( 'mce_external_plugins', [ $this, 'add_buttons' ] );
            add_filter( 'mce_buttons', [ $this, 'register_buttons' ] );
        }
    }

    /**
     * Add new javascript to the plugin script array
     *
     * @param  array $plugin_array
     *
     * @return array
     */
    public function add_buttons( $plugin_array ) {
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
    public function register_buttons( $buttons ) {
        array_push( $buttons, 'separator', 'pushortcodes' );

        return $buttons;
    }

    /**
     * Add shortcode JS to the page
     *
     * @return void
     */
    public function get_shortcodes() {
        global $shortcode_tags;

        echo '<script type="text/javascript">
        var shortcodes_button = [];';

        $count = 0;

        foreach( $shortcode_tags as $tag => $code ) {
            echo "shortcodes_button[{$count}] = '{$tag}';";
            $count++;
        }

        echo '</script>';
    }
}
