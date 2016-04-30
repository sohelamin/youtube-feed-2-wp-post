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
                'youtube_user_id': form.find( "input[name=youtube_user_id]" ).val(),
                'post_category': form.find( "select[name=post_category]" ).val(),
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
                        responseDiv.html( '<span>Successfully imported all videos.</span>' );
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
 * Get youtube videos
 *
 * @param  $args
 *
 * @return array
 */
function yt2wp_get_youtube_videos( $args = [] ) {
    $defaults = [
        'number'        => 50,
        'nextPageToken' => null,
        'orderby'       => 'date',
        'count'         => false,
    ];

    $args = wp_parse_args( $args, $defaults );

    $youtube_id = get_option( 'yt2wp_youtube_user_id', null );
    $api_key    = get_option( 'yt2wp_youtube_api_key', null );

    $youtube = new Youtube( [ 'key' => $api_key ] );

    if ( $args['count'] ) {
        // Search only Videos in a given channel, Return an array of PHP objects
        $videos_list = $youtube->searchChannelVideos('', $youtube_id, 1, $args['orderby'] );

        return $videos_list['info']['totalResults'];
    }

    $params = array(
        'q' => '',
        'type' => 'video',
        'channelId' => $youtube_id,
        'part' => 'id, snippet',
        'maxResults' => $args['number'],
        'order' => $args['orderby'],
    );

    // Search only Videos in a given channel, Return an array of PHP objects
    $videos_list = $youtube->paginateResults( $params, $args['nextPageToken'] );

    $videos_array = [];

    if ( ! empty( $videos_list ) ) {
        $videos_array['nextPageToken'] = $videos_list['info']['nextPageToken'];

        foreach ( $videos_list['results'] as $list ) {
            $videoInfo = $youtube->getVideoInfo( $list->id->videoId );

            $video['title']       = $list->snippet->title;
            $video['video_id']    = $list->id->videoId;
            $video['description'] = '[yt2wp_show_youtube_video width="640" height="360"]'; // Shortcode

            $video['view_count']  = $videoInfo->statistics->viewCount;
            $video['date']        = date( 'Y-m-d H:i:s', strtotime( $list->snippet->publishedAt ) );
            $video['keyword']     = '';

            $videos_array[] = $video;
        }
    }

    return $videos_array;
}

/**
 * Video importing via youtube gdata api.
 *
 * @return boolean
 */
function yt2wp_do_youtube_video_import() {
    $videos_array = yt2wp_get_youtube_videos();

    $category_id = get_option( 'yt2wp_post_category', null);

    if ( ! empty( $category_id ) ) {
        $videos_array = array_map( function( $item ) use ( $category_id ) {
            $item['category'] = $category_id;

            return $item;
        }, $videos_array );
    }

    $result = yt2wp_insert_post( $videos_array );

    return $result;
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
    foreach( $data as $insert_post ) {
        // Query for the meta key and meta value
        global $wpdb;

        $meta_key = 'yt2wp_youtube_video_id';
        $insert_post_video_id = $insert_post['video_id'];
        $table_name = $wpdb->prefix . 'postmeta';
        $result = $wpdb->get_results( "SELECT meta_value FROM  $table_name WHERE meta_key='$meta_key' AND meta_value='$insert_post_video_id'" );

        if ( count( $result ) == 0 ) {
            // Create post object
            $user_id = get_current_user_id();
            $my_post = [
              'post_title'    => $insert_post['title'],
              'post_content'  => $insert_post['description'],
              'post_status'   => 'publish',
              'post_author'   => $user_id,
            ];

            if ( ! empty( $insert_post['category'] ) ) {
                $my_post['post_category'] = [ $insert_post['category'] ];
            }

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
        'width'    => '640',
        'height'   => '360',
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

/**
 * Handle import video ajax request.
 *
 * @return void
 */
function yt2wp_import_ajax_handler() {

    if ( ! isset( $_REQUEST['_wpnonce'] ) && ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'yt2wp-import-nonce' ) ) {
        die( 'Security check' );
    }

    $api_key = yt2wp_get_api_key();

    $category_id     = $_POST['post_category'];
    $youtube_user_id = $_POST['youtube_user_id'];

    $limit = 50; // Limit to sync per request

    $attempt = get_option( 'yt2wp_import_attempt', 1 );
    update_option( 'yt2wp_import_attempt', $attempt + 1 );

    $total_items = yt2wp_get_youtube_videos( ['count' => true] );

    $nextPageToken = get_option( 'yt2wp_youtube_next_page_token', null );

    $videos_array = yt2wp_get_youtube_videos( [ 'number' => $limit, 'nextPageToken' => $nextPageToken ] );

    update_option( 'yt2wp_youtube_next_page_token', $videos_array['nextPageToken'] );

    if ( ! empty( $category_id ) ) {
        $videos_array = array_map( function( $item ) use ( $category_id ) {
            $item['category'] = $category_id;

            return $item;
        }, $videos_array );
    }

    yt2wp_insert_post( $videos_array );

    // re-calculate stats
    if ( $total_items <= ( $attempt * $limit ) ) {
        $left = 0;
    } else {
        $left = $total_items - ( $attempt * $limit );
    }

    if ( $left === 0 ) {
        delete_option( 'yt2wp_import_attempt' );
    }

    wp_send_json_success( [ 'left' => $left, 'message' => sprintf( __( '%d left to import out of %d.', 'youtube-feed-2-wp-post' ), $left, $total_items ) ] );
}
