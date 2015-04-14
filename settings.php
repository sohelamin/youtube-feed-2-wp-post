<?php
/**
 * Settings Page
 *
 * @author Sohel Amin
*/
class AppzCoder_YouTube_Video_Settings {

	public $appzcoder_youtube_video_to_wp_post;
    /**
     * Class constructor
     */	
	public function __construct() {
		// create custom plugin settings menu
		add_action( 'admin_menu', array($this, 'ac_youtube_video_2_wp_post_create_menu') );
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
	}

    /**
     * Creating option menu
     *
     * @return Void
     */
	function ac_youtube_video_2_wp_post_create_menu() {
		// create new top-level menu
		add_menu_page( 'Youtube Video 2 WP Post Plugin Settings', 'Youtube Video 2 WP Post Settings', 'administrator', __FILE__, array($this, 'ac_youtube_video_2_wp_post_settings_page'), plugins_url('/images/icon.png', __FILE__) );
		// call register settings function
		add_action( 'admin_init', array($this, 'register_ac_youtube_video_2_wp_post_settings') );
		// update cronjob schedule upon update the option
		add_filter( 'pre_update_option_ac_cron_job_schedule', array($this,'ac_cron_job_re_schedule'), 10, 2 );		
	}

    /**
     * Register settings fields
     *
     * @return Void
     */
	function register_ac_youtube_video_2_wp_post_settings() {
		// register our settings
		register_setting( 'ac-youtube-video-2-wp-post-settings-group', 'ac_youtube_user_id' );
		register_setting( 'ac-youtube-video-2-wp-post-settings-group', 'ac_cron_job_schedule' );
	}

    /**
     * Display options fields
     *
     * @return HTML
     */
	function ac_youtube_video_2_wp_post_settings_page() {
	?>
	<div class="wrap">
		<h2>Youtube Video 2 WP Post</h2>
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
     * @param  String  $new_value, $old_value
     *
     * @return Void
     */
	function ac_cron_job_re_schedule( $new_value, $old_value ) {
		$this->appzcoder_youtube_video_to_wp_post = new AppzCoder_YouTube_Video_To_WP_Post();
		// Destroy the existing schedule
		$this->appzcoder_youtube_video_to_wp_post->ac_yf2wp_deactivate();
		// Setting up the user defined schedule
		$this->appzcoder_youtube_video_to_wp_post->ac_yf2wp_activate( $new_value, false );
		return $new_value;
	}

}
