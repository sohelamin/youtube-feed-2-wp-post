<?php

	// Updating the option when form submitted
	if( isset( $_POST['ac_youtube_user_id'] ) ) {	
		$variable = $_POST['ac_youtube_user_id'];
		update_option( 'ac_youtube_user_id', $variable );	
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
				<th scope="row">Youtube User ID</th>
				<td><input type="text" name="ac_youtube_user_id" value="<?php echo get_option( 'ac_youtube_user_id' ); ?>" /></td>
			</tr>
		</table>    
		<?php submit_button(); ?>
	</form>
	</div>
	<?php 
	} 
	?>