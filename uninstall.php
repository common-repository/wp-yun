<?php
/**
 * @package   wp-yun
 * @author    Saeed Vasheghani Farahani <info@yun.ir>
 * @license   GPL-2.0+
 * @link      http://wordpress.org/plugins/wp-yun
 * @copyright 2020 Saeed Vasheghani Farahani <info@yun.ir>
 */

if (!defined('WP_UNINSTALL_PLUGIN'))
    die;


/**
 * Some people just don't know how cool this plugin is. When they realize
 * it and come back later, let's make sure they have to start all over.
 *
 * @return void
 */
function wp_yun_uninstall() {
    // Delete associated options
    delete_option('wp-yun-options');

    // Grab all posts with an attached shortlink
    $posts = get_posts('numberposts=-1&post_type=any&meta_key=_wpyun');

    // And remove our meta information from them
    foreach ($posts as $post) {
        delete_post_meta($post->ID, WPYUN_META_KEY);
    }

}

// G'bye!
wp_yun_uninstall();
