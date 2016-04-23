<?php

/**
 * YT2WP JavaScript enqueue.
 *
 * @since  1.0
 *
 * @return void
 */
function yt2wp_enqueue_js() { ?>
    <script type="text/javascript" >
    jQuery( document ).ready( function($) {

        responseDiv = $( "div#response_div" );
        $("form#yt2wp_import_form").on( 'submit', function(e) {
            e.preventDefault();

            var form = $(this),
                submit = form.find('input[type=submit]'),
                loader = form.find('.import-loader');
            submit.attr('disabled', 'disabled');
            loader.show();

            var data = {
                'action': 'yt2wp_import',
                'group_id': form.find( "select[name=group_id]" ).val(),
                '_wpnonce': '<?php echo wp_create_nonce( "yt2wp-import-nonce" ); ?>'
            };

            $.post( ajaxurl, data, function(response) {
                if ( response.success ) {
                    responseDiv.html( '<span>' + response.data.message + '</span>' );
                    if ( response.data.left > 0 ) {
                        form.submit();
                        return;
                    } else {
                        submit.removeAttr('disabled');
                        loader.hide();
                        responseDiv.html('');
                    }
                }
            });
        });

    });
    </script> <?php
}

/**
 * Get the API Key.
 *
 * @return string
 */
function yt2wp_get_api_key() {
    return get_option( 'yt2wp_youtube_api_key', null );
}

/**
 * Setting up cronjob time period.
 *
 * @param array $schedules
 *
 * @return array
 */
function yt2wp_add_new_cron_schedule( $schedules )  {
    $schedules['every_thirty_min'] = [
        'interval' => 1800,
        'display'  => __('Every 30 Minutes')
    ];
    $schedules['weekly'] = [
        'interval' => 604800,
        'display'  => __('Once Weekly')
    ];
    $schedules['monthly'] = [
        'interval' => 2635200,
        'display'  => __('Once a month')
    ];

    return $schedules;
}

/**
 * Calling cronjob schedule.
 *
 * @param array $recurrence
 *
 * @return void
 */
function yt2wp_register_cronjob_schedule() {
    if ( ! wp_next_scheduled( 'yt2wp_youtube_video_import' ) ) {
        $auto_import = (int) get_option( 'yt2wp_auto_import', 0 );
        $recurrence  = get_option( 'yt2wp_cron_job_schedule', null );

        if ( $auto_import && isset( $recurrence ) ) {
            wp_schedule_event( time(), $recurrence, 'yt2wp_youtube_video_import' );
        }
    }
}

/**
 * Destroy the schedule.
 *
 * @return void
 */
function yt2wp_destroy_cronjob_schedule() {
    if ( false !== ( $time = wp_next_scheduled( 'yt2wp_youtube_video_import' ) ) ) {
       wp_clear_scheduled_hook( 'yt2wp_youtube_video_import' );
    }
}

/**
 * Video importing via youtube gdata api.
 *
 * @return boolean
 */
function yt2wp_do_youtube_video_import() {
    $youtube_id = get_option( 'yt2wp_youtube_user_id', null );
    $api_key    = get_option( 'yt2wp_youtube_api_key', null );

    $youtube = new Youtube( [ 'key' => $api_key ] );

    // Search only Videos in a given channel, Return an array of PHP objects
    $videos_list = $youtube->searchChannelVideos('', $youtube_id, 50, 'date');

    $categories_list = [
        1  => 'Film & Animation',
        2  => 'Autos & Vehicles',
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
    ];

    foreach ( $videos_list as $list ) {
        $videoInfo = $youtube->getVideoInfo( $list->id->videoId );

        $videos['title']       = $list->snippet->title;
        $videos['video_id']    = $list->id->videoId;
        $videos['description'] = $list->snippet->description;
        $videos['view_count']  = $videoInfo->statistics->viewCount;
        $videos['date']        = date( 'Y-m-d H:i:s', strtotime( $list->snippet->publishedAt ) );
        $videos['category']    = $categories_list[$videoInfo->snippet->categoryId];
        $videos['keyword']     = '';

        $video_array[] = $videos;
    }

    $result = yt2wp_insert_post( $video_array );

    return $result;
}

/**
 * For XML object to array conversion.
 *
 * @param object $result
 *
 * @return array
 */
function yt2wp_xml2array ( $result, $out = [] ) {
    foreach ( (array) $result as $index => $node )
        $out[$index] = ( is_object ( $node ) ) ? yt2wp_xml2array ( $node ) : $node;

    return $out;
}

/**
 * Custom meta for video id.
 *
 * @param array $post
 *
 * @return void
 */
function yt2wp_youtube_video_id( $post )
{
    $video_id = get_post_meta( $post->ID, 'yt2wp_youtube_video_id', true );
    ?>
    <p>
        <label for="yt2wp_youtube_video_id"><?php _e( 'YouTube Video ID: ', 'youtube-feed-2-wp-post' ); ?></label>
        <input type="text" name="yt2wp_youtube_video_id" id="yt2wp_youtube_video_id" value="<?php echo esc_attr( $video_id ); ?>" />
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
function yt2wp_insert_post( $data ) {
    // Getting all posts
    foreach($data as $insert_post) {
        // Query for the meta key and meta value
        global $wpdb;

        $meta_key = 'yt2wp_youtube_video_id';
        $insert_post_video_id = $insert_post['video_id'];
        $table_name = $wpdb->prefix . 'postmeta';
        $result = $wpdb->get_results( "SELECT meta_value FROM  $table_name WHERE meta_key='$meta_key' AND meta_value='$insert_post_video_id'" );

        if ( count( $result ) == 0 ) {
            // Getting category id
            $category_id = get_cat_ID( $insert_post['category'] );
            // Create post object
            $user_id = get_current_user_id();
            $my_post = [
              'post_title'    => $insert_post['title'],
              'post_content'  => $insert_post['description'],
              'post_status'   => 'publish',
              'post_author'   => $user_id,
              'post_category' => [ $category_id ]
            ];

            // Insert the post into the database
            $post_id = wp_insert_post( $my_post );

            update_post_meta( $post_id, 'yt2wp_youtube_video_id', $insert_post_video_id );
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
function yt2wp_do_shortcode_func( $atts ) {
    $a = shortcode_atts( [
        'width'    => '420',
        'height'   => '315',
        'video_id' => '',
    ], $atts );

    $youtube_video_width  = $a['width'];
    $youtube_video_height = $a['height'];
    $youtube_video_id     = get_post_meta( get_the_ID(), 'yt2wp_youtube_video_id', true );

    if ( $youtube_video_id != '' ) {
        return '<iframe src="http://www.youtube.com/embed/' . $youtube_video_id . '?rel=0" frameborder="0" allowfullscreen width="' . $youtube_video_width . '" height="' . $youtube_video_height . '"></iframe>';
    } else {
        return __( '<h3>No videos!</h3>', 'youtube-feed-2-wp-post' );
    }
}

/**
 * Add settings link on plugin page.
 *
 * @param array $links
 *
 * @return array
 */
function yt2wp_plugin_settings_link( $links ) {
    $settings_link = '<a href="' . admin_url( 'admin.php?page=youtube-video-to-wp-post-settings' ) . '">Settings</a>';

    array_unshift( $links, $settings_link );

    return $links;
}

/**
 * Callback function for yt2wp_cron_job_schedule field also it will destroy and re-schedule cronjob while calling.
 *
 * @param  string  $new_value, $old_value
 *
 * @return void
 */
function yt2wp_set_cron_job_schedule( $old_value, $value, $option = null ) {
    $yt2wp_auto_import = (int) get_option( 'yt2wp_auto_import', 0 );

    if ( ! $option == 'yt2wp_auto_import' && ! $yt2wp_auto_import ) {
        return;
    }

    // Destroy the existing schedule
    yt2wp_destroy_cronjob_schedule();

    // Setting up the user defined schedule
    yt2wp_register_cronjob_schedule();

    return;
}
