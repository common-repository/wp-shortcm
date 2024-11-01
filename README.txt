=== Plugin Name ===
Contributors: delayedinsanity, chipbennett
Tags: shortlink, short, link, shortcm, url, shortener, social, media, twitter, share
Requires at least: 3.9
Tested up to: 6.0.2
Stable tag: 2.4.0
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Use short.io generated shortlinks for all your WordPress posts and pages, including custom post types.


== Description ==

WP-Short.io is the easiest way to generate short links for your WordPress posts. It is a timesaving integration since you don't need to copy a long link and manually shorten it on Short.io. 

Specify an API key (you'll find it in your Short.io account), identify the post types to create short links for, and forget about it! WP-Short.io automatically shortens URLs for WordPress posts.

After you finish writing a striking post, share it with your friends on social media. 

Now, instead of a long URL, a short and automatically generated link is already waiting for you. Just copy and send it!

Feature Requests are welcome via https://feedback.short.cm/


== Installation ==

= Add New Plugin =

1. From the WordPress Dashboard, opt for Plugins > Add.

2. Search for WP-Short.io in the search bar.

3. Select Install > OK > Activate Plugin.

4. This will return you to the WordPress Plugins page. Find WP-Short.io in the list and click Settings to configure.

5. Enter your API key. For this, go to your Short.io account, choose "Integrations & API" and copy the API key!


== Frequently Asked Questions ==

= After installation, do I need to update all my posts for short links to be created? =

No. The first time a shortlink is requested for a particular post, WP Short.io will automatically generate one.

= What happens if I change a posts permalink? =

WP Short.io will verify the shortlink when it's requested and update as necessary all on its own.

= Can I include the shortlink directly in a post? =

Sure can! Just use our handy dandy shortcode `[wpshortcm]` and shazam! The shortcode accepts all the same arguments as the_shortlink(). You can also set "post_id" directly if you wish.

= How do I include a shortlink using PHP? =

`<?php wpshortcm_shortlink(); // shortcode shweetness. ?>`

*(You don't have to include the php comment, but you can if you want.)*

== Upgrade Notice ==

= 2.3.2 =
Minor fixes, including a typo in the main callback. Also disables previously generated shortlinks after the fact for unselected post types.

= 2.3.4 =
"Documentation update"

== Changelog ==

= 1.0.1 =
* API URL updated

= 1.0.0 =
* Initial release
