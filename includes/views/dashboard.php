<div class="wrap">
    <h2><?php _e( 'YouTube Video to WP Post', 'youtube-feed-2-wp-post' ); ?></h2>
    <?php
        delete_option( 'yt2wp_import_attempt' );
        delete_option( 'yt2wp_youtube_next_page_token' );

        $categories = get_categories();
    ?>

    <form action="" method="post" id="yt2wp_import_form">
        <table class="form-table">
            <tbody>
                <tr valign="top">
                    <th scope="row"><?php _e( 'YouTube User ID', 'youtube-feed-2-wp-post' ); ?></th>
                    <td>
                        <input type="text" name="youtube_user_id" value="<?php echo get_option( 'yt2wp_youtube_user_id', null ); ?>" /> <a title="Click here for help." target="_blank" href="https://support.google.com/youtube/answer/3250431?hl=en"><strong>?</strong></a>
                        <p class="description"><?php _e( 'You can set custom YouTube User ID or Channel ID to import.', 'youtube-feed-2-wp-post' ); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'Post Category', 'youtube-feed-2-wp-post' ); ?></th>
                    <td>
                        <select name="post_category">
                            <option value="" selected="selected"><?php _e( '&mdash; Select Category &mdash;', 'youtube-feed-2-wp-post' ); ?></option>
                            <?php
                            foreach ( $categories as $category ) {
                            ?>
                                <option value="<?php echo $category->cat_ID; ?>"><?php _e( $category->name, 'youtube-feed-2-wp-post' ); ?></option>
                            <?php
                            }
                            ?>
                        </select>

                        <p class="description"><?php _e( 'Select post category to import.', 'youtube-feed-2-wp-post' ); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>
        <div id="response_div"></div>

        <?php wp_nonce_field( 'yt2wp-import-nonce' ); ?>
        <input type="submit" name="submit_yt2wp_import" class="button button-primary" value="<?php esc_attr_e( 'Import', 'youtube-feed-2-wp-post' ); ?>">
        <span class="import-loader" style="display: none;"></span>
    </form>
</div>