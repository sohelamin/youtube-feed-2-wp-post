<?php
/**
 * AppzCoder Shortcode Tinymce
 *
 * @author Sohel Amin
*/
class AppzCoder_Shortcode_Tinymce {
    public function __construct() {
        add_action( 'admin_init', array($this, 'ac_shortcode_button') );
        add_action( 'admin_footer', array($this, 'ac_get_shortcodes') );
    }

    /**
     * Create a shortcode button for tinymce
     *
     * @return Void
     */
    public function ac_shortcode_button() {
        if( current_user_can('edit_posts') && current_user_can('edit_pages') ) {
            add_filter( 'mce_external_plugins', array($this, 'ac_add_buttons') );
            add_filter( 'mce_buttons', array($this, 'ac_register_buttons') );
        }
    }

    /**
     * Add new Javascript to the plugin scrippt array
     *
     * @param  Array $plugin_array
     *
     * @return Array
     */
    public function ac_add_buttons( $plugin_array ) {
        $plugin_array['pushortcodes'] = plugin_dir_url( __FILE__ ) . '../js/shortcode-tinymce-button.js';
        return $plugin_array;
    }

    /**
     * Add new button to tinymce
     *
     * @param  Array $buttons
     *
     * @return Array
     */
    public function ac_register_buttons( $buttons ) {
        array_push( $buttons, 'separator', 'pushortcodes' );
        return $buttons;
    }

    /**
     * Add shortcode JS to the page
     *
     * @return HTML
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
new AppzCoder_Shortcode_Tinymce();
