<?php
/**
 * @package   wp-bitly
 * @author    Mark Waterous <mark@watero.us>
 * @license   GPL-2.0+
 * @link      http://wordpress.org/plugins/wp-bitly
 * @copyright 2014 Mark Waterous
 */

if (!defined('WP_UNINSTALL_PLUGIN'))
    die;


/**
 * Some people just don't know how cool this plugin is. When they realize
 * it and come back later, let's make sure they have to start all over.
 *
 * @return void
 */
function wpshortcm_uninstall() {
    // Delete associated options
    delete_option('wpshortcm-options');

    // Grab all posts with an attached shortlink
    $posts = get_posts('numberposts=-1&post_type=any&meta_key=_wpshortcm');

    // And remove our meta information from them
    // @TODO benchmark this against deleting it with a quick SQL query. Probably quicker, any conflict?
    foreach ($posts as $post)
        delete_post_meta($post->ID, '_shortcm');

}

// G'bye!
wpshortcm_uninstall();
