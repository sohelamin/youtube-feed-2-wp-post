<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Settings Page
 *
 * @author Sohel Amin
*/
class Admin_Menu {
    /**
     * Constructor function.
     *
     * @access public
     * @return void
     */
    public function __construct() {
        // Add menu
        add_action( 'admin_menu', [ $this, 'admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    /**
     * Add menu items
     *
     * @return void
     */
    public function admin_menu() {
        add_menu_page( 'YouTube Video to WP Post', 'YouTube Video to WP Post', 'manage_options', 'youtube-video-to-wp-post', [ $this, 'plugin_page' ], plugins_url( '/../assets/images/icon.png', __FILE__ ) );
        add_submenu_page( 'youtube-video-to-wp-post', __( 'General', 'youtube-feed-2-wp-post' ), __( 'General', 'youtube-feed-2-wp-post' ), 'manage_options', 'youtube-video-to-wp-post', [ $this, 'plugin_page' ] );
        add_submenu_page( 'youtube-video-to-wp-post', __( 'Settings', 'youtube-feed-2-wp-post' ), __( 'Settings', 'youtube-feed-2-wp-post' ), 'manage_options', 'youtube-video-to-wp-post-settings', [ $this, 'settings_page' ] );
    }

    /**
     * Display the plugin main page
     *
     * @return void
     */
    public function plugin_page() {
        $action = isset( $_GET['action'] ) ? $_GET['action'] : 'dashboard';

        $mailchimp_settings_url = admin_url( 'admin.php?page=youtube-video-to-wp-post-settings' );

        if ( $action == 'disconnect' ) {
            delete_option( 'yt2wp_youtube_api_key' );
        }

        $api_key = yt2wp_get_api_key();

        if ( ! $api_key ) {
            ?>
            <div class="wrap">
                <h2><?php _e( 'YouTube Video to WP Post', 'youtube-feed-2-wp-post' ); ?></h2>
                <p><?php _e( 'You\'re not connected with youtube yet. Click on below button to configure.', 'youtube-feed-2-wp-post' ); ?></p>
                <a href="<?php echo $mailchimp_settings_url ?>"><button class="button-secondary">Configure</button></a>
            </div>
            <?php
        } else {
            include YT2WP_VIEWS . '/dashboard.php';
        }
    }

    /**
     * Display the settings page
     *
     * @return void
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h2><?php _e( 'YouTube Video to WP Post', 'youtube-video-to-wp-post' ); ?></h2>

            <form method="post" action="options.php">
                <?php settings_fields( 'yt2wp-settings-group' ); ?>
                <?php do_settings_sections( 'yt2wp-settings-group' ); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e( 'API Key', 'youtube-feed-2-wp-post' ); ?></th>
                        <td>
                            <?php
                                $api_key = yt2wp_get_api_key();
                                if ( ! $api_key ) {
                            ?>
                                <input type="text" name="yt2wp_youtube_api_key" class="regular-text" placeholder="Your YouTube API key" value="<?php echo get_option( 'yt2wp_youtube_api_key', null ); ?>" />
                                <p class="description"><a target="_blank" href="https://developers.google.com/youtube/registering_an_application#Create_API_Keys">Get your API key here.</a></p>
                            <?php
                                } else {
                            ?>
                                <input type="hidden" name="yt2wp_youtube_api_key" value="<?php echo get_option( 'yt2wp_youtube_api_key', null ); ?>" />
                                <strong><span class="green">Connected</span></strong>
                                <p><a href="<?php echo admin_url( 'admin.php?page=youtube-video-to-wp-post&action=disconnect' ); ?>">Disconnect</a></p>
                            <?php
                                }
                            ?>
                        </td>
                    </tr>
                    <?php
                    if ( $api_key ) {
                        $yt2wp_auto_import = get_option( 'yt2wp_auto_import', 0 );
                    ?>
                    <tr valign="top">
                        <th scope="row">
                            <label for="yt2wp_auto_import"><?php _e( 'Auto Import', 'youtube-feed-2-wp-post' ); ?></label>
                        </th>
                        <td>
                            <select name="yt2wp_auto_import" id="yt2wp_auto_import">
                                <option value="0" <?php echo ( $yt2wp_auto_import == 0 ) ? 'selected' : ''; ?>>Off</option>
                                <option value="1" <?php echo ( $yt2wp_auto_import == 1 ) ? 'selected' : ''; ?>>On</option>
                            </select>
                            <p class="description"><?php _e( 'Allow to auto import YouTube video as post.', 'youtube-feed-2-wp-post' ); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e( 'Interval Time', 'youtube-feed-2-wp-post' ); ?></th>
                        <td>
                            <?php
                                $schedules = [
                                    'hourly'           =>  'Once Hourly',
                                    'twicedaily'       =>  'Twice Daily',
                                    'daily'            =>  'Once Daily',
                                    'weekly'           =>  'Once Weekly',
                                    'monthly'          =>  'Once a month'
                                ];
                            ?>
                            <select name="yt2wp_cron_job_schedule">
                            <?php
                                foreach( $schedules as $key => $value ):?>
                                <option value="<?php echo $key; ?>" <?php echo get_option( 'yt2wp_cron_job_schedule' ) == $key ? 'selected' : ''; ?>><?php echo $value; ?></option>
                            <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e( 'Set interval time to automatically import videos.', 'youtube-feed-2-wp-post' ); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e( 'YouTube User ID', 'youtube-feed-2-wp-post' ); ?></th>
                        <td>
                            <input type="text" name="yt2wp_youtube_user_id" value="<?php echo get_option( 'yt2wp_youtube_user_id', null ); ?>" /> <a title="Click here for help." target="_blank" href="https://support.google.com/youtube/answer/3250431?hl=en"><strong>?</strong></a>
                            <p class="description"><?php _e( 'Enter your YouTube User ID or Channel ID.', 'youtube-feed-2-wp-post' ); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e( 'Post Category', 'youtube-feed-2-wp-post' ); ?></th>
                        <td>
                            <select name="yt2wp_post_category">
                                <option value="" selected="selected"><?php _e( '&mdash; Select Category &mdash;', 'youtube-feed-2-wp-post' ); ?></option>
                                <?php
                                $categories = get_categories();
                                foreach ( $categories as $category ) {
                                ?>
                                    <option value="<?php echo $category->cat_ID; ?>" <?php echo get_option( 'yt2wp_post_category' ) == $category->cat_ID ? 'selected' : ''; ?>><?php _e( $category->name, 'youtube-feed-2-wp-post' ); ?></option>
                                <?php
                                }
                                ?>
                            </select>
                            <p class="description"><?php _e( 'Set default category to import videos.', 'youtube-feed-2-wp-post' ); ?></p>
                        </td>
                    </tr>
                    <?php
                    }
                    ?>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register settings fields
     *
     * @return void
     */
    public function register_settings() {
        // register settings
        register_setting( 'yt2wp-settings-group', 'yt2wp_youtube_api_key' );
        register_setting( 'yt2wp-settings-group', 'yt2wp_auto_import' );
        register_setting( 'yt2wp-settings-group', 'yt2wp_youtube_user_id' );
        register_setting( 'yt2wp-settings-group', 'yt2wp_cron_job_schedule' );
        register_setting( 'yt2wp-settings-group', 'yt2wp_post_category' );
    }
}
