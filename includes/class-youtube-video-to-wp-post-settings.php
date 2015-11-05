<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Settings Page
 *
 * @author Sohel Amin
*/
class YouTube_Video_To_WP_Post_Settings {
    /**
     * Instance of this class.
     *
     * @var static
     */
    protected static $instance;

	public $youtube_video_to_wp_post;
	
	/**
	 * Constructor function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		// create custom plugin settings menu
		add_action( 'admin_menu', array($this, 'ac_create_menu') );
	}

    /**
     * Initiate the class as an object.
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
     * Creating option menu
     *
     * @return void
     */
	function ac_create_menu() {
		// create new top-level menu
		add_menu_page( 'Youtube Video to WP Post Plugin Settings', 'Youtube Video to WP Post Settings', 'manage_options', 'youtube-video-to-wp-post-settings', array($this, 'ac_display_settings_page'), plugins_url('/../assets/images/icon.png', __FILE__) );
		// call register settings function
		add_action( 'admin_init', array($this, 'ac_register_settings') );
		// update cronjob schedule upon update the option
		add_filter( 'pre_update_option_ac_cron_job_schedule', array($this,'ac_cron_job_re_schedule'), 10, 2 );		
	}

    /**
     * Register settings fields
     *
     * @return void
     */
	function ac_register_settings() {
		// register our settings
		register_setting( 'ac-youtube-video-2-wp-post-settings-group', 'ac_youtube_user_id' );
		register_setting( 'ac-youtube-video-2-wp-post-settings-group', 'ac_cron_job_schedule' );
	}

    /**
     * Display options fields
     *
     * @return void
     */
	function ac_display_settings_page() {
	?>
	<div class="wrap">
		<h2><?php _e( 'Youtube Video to WP Post', 'youtube-video-to-wp-post' ); ?></h2>
		<form method="post" action="options.php">
			<?php settings_fields( 'ac-youtube-video-2-wp-post-settings-group' ); ?>
			<?php do_settings_sections( 'ac-youtube-video-2-wp-post-settings-group' ); ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Youtube User ID:</th>
					<td><input type="text" name="ac_youtube_user_id" value="<?php echo get_option( 'ac_youtube_user_id' ); ?>" /> <a target="_blank" href="https://support.google.com/youtube/answer/3250431?hl=en"><strong>?</strong></a></td>
				</tr>
				<tr valign="top">
					<th scope="row">Select Interval:</th>
					<td>
						<?php
							$schedules = array(
								'every_thirty_min'	=>	'Every 30 Minutes',
								'hourly'			=>	'Once Hourly',
								'twicedaily'		=>	'Twice Daily',
								'daily'				=>	'Once Daily',
								'weekly'			=>	'Once Weekly',
								'monthly'			=>	'Once a month'
							);
						?>
						<select name="ac_cron_job_schedule">
						<?php 
							foreach( $schedules as $key => $value ):?>
							<option value="<?php echo $key; ?>" <?php echo get_option( 'ac_cron_job_schedule' ) == $key ? 'selected' : ''; ?>><?php echo $value; ?></option>
						<?php endforeach; ?>	
						</select>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php 
	}

    /**
     * Callback function for ac_cron_job_schedule field also it will destroy and re-schedule cronjob while calling.
     *
     * @param  string  $new_value, $old_value
     *
     * @return void
     */
	function ac_cron_job_re_schedule( $new_value, $old_value ) {
		$this->youtube_video_to_wp_post = new YouTube_Video_To_WP_Post();
		// Destroy the existing schedule
		$this->youtube_video_to_wp_post->ac_destroy_cronjob_schedule();
		// Setting up the user defined schedule
		$this->youtube_video_to_wp_post->ac_register_cronjob_schedule( $new_value, false );

		return $new_value;
	}

}
