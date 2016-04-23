=== Plugin Name ===
Contributors: sohelamin
Tags: youtube, post, youtube video, appzcoder, sohelamin
Requires at least: 3.8
Tested up to: 4.5
Stable tag: 4.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A wordpress plugin which is simply allow you to import your YouTube video feed as a post within a selective time.

== Description ==

Features:

*	Anyone can import their YouTube video feed as post.
*	Post can be searchable or manageable to use anywhere of the wordpress blog.
*	User can select a custom schedule import the video from YouTube.
*	Short Code supported.


== Installation ==

1. Upload `youtube-video-2-wp-post` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Put your YouTube user's/channel's id in plugin's setting page.
4. Select the interval time when you want to update the feed automatically.
5. Put the Short Code [ac_show_youtube_video width="420" height="345"] inside your post or put the code `<?php echo do_shortcode( '[ac_show_youtube_video width="420" height="345"]' ); ?>` to your template page where the post loop exist.

== Screenshots ==

1. `/assets/screenshot-1.png`
2. `/assets/screenshot-2.png`
3. `/assets/screenshot-3.png`

== Changelog ==

= 1.5 =
* Updated.

= 1.4 =
* Re-factored codes.
* Fixes minor bugs.

= 1.3 =
* YouTube Data API (v3) added instead of deprecated v2.

= 1.2 =
* Shortcode support on editor.
* Code re-factored.

= 1.1 =
* Short code added for showing the video on a post or inside the post loop.
* User can select the schedule from plugin's admin page.
* Some additional Cron Job Schedule added.

= 1.0 =
* Initial Release.
