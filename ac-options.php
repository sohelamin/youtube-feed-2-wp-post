<?php
	require('ac-yf2wp.php');
	
	// Updating the option when form submitted
	if( isset( $_POST['ac_youtube_user_id'] ) ) {	
		$ac_youtube_user_id = $_POST['ac_youtube_user_id'];
		$ac_cron_job_schedule = $_POST['ac_cron_job_schedule'];
		update_option( 'ac_youtube_user_id', $ac_youtube_user_id );
		update_option( 'ac_cron_job_schedule', $ac_cron_job_schedule );
		// Destroy the existing schedule
		$youtube_feed_2_wp_post->ac_yf2wp_deactivate();
		// Setting up the user defined schedule
		$youtube_feed_2_wp_post->ac_yf2wp_activate( $ac_cron_job_schedule );
	}
	// create custom plugin settings menu
	add_action( 'admin_menu', 'ac_youtube_feed_2_wp_post_create_menu' );	

	function ac_youtube_feed_2_wp_post_create_menu() {
		// create new top-level menu
		add_menu_page( 'Youtube Feed 2 WP Post Plugin Settings', 'Youtube Feed 2 WP Post Settings', 'administrator', __FILE__, 'ac_youtube_feed_2_wp_post_settings_page',plugins_url('/images/icon.png', __FILE__) );
		// call register settings function
		add_action( 'admin_init', 'register_ac_youtube_feed_2_wp_post_settings' );
	}

	function register_ac_youtube_feed_2_wp_post_settings() {
		// register our settings
		register_setting( 'ac-youtube-feed-2-wp-post-settings-group', 'ac_youtube_user_id' );
	}

	function ac_youtube_feed_2_wp_post_settings_page() {
	?>
	<div class="wrap">
	<h2>Youtube Feed 2 WP Post</h2>
	<form method="post" action="">
		<?php settings_fields( 'ac-youtube-feed-2-wp-post-settings-group' ); ?>
		<?php do_settings_sections( 'ac-youtube-feed-2-wp-post-settings-group' ); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">Youtube User ID:</th>
				<td><input type="text" name="ac_youtube_user_id" value="<?php echo get_option( 'ac_youtube_user_id' ); ?>" /> <a target="_blank" href="https://support.google.com/youtube/answer/3250431?hl=en"><strong>?</strong></a></td>
			</tr>
			<tr valign="top">
				<th scope="row">Select Interval:</th>
				<td><!--<input type="text" name="ac_cron_job_schedule" value="<?php echo get_option( 'ac_cron_job_schedule' ); ?>" />-->
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
	?>
