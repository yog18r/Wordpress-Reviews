<?php
/**
 * Plugin Name:       IF Reviews
 * Plugin URI:        https://github.com/yog18r/Wordpress-Reviews
 * Description:       Manually curated customer reviews with per-review source badges, an honest summary header, grid / list / carousel layouts and an [if_reviews] shortcode.
 * Version:           2.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            yog18r
 * Author URI:        https://github.com/yog18r
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       if-reviews
 * Domain Path:       /languages
 * Update URI:        https://github.com/yog18r/Wordpress-Reviews
 *
 * IF Reviews is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 2 of the License, or (at your
 * option) any later version. See LICENSE for the full text.
 *
 * Function and hook names and post meta keys are kept stable across
 * versions so upgrading in place is always safe: existing reviews and
 * settings carry over.
 *
 * @package IF_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'IF_REVIEWS_VERSION', '2.0.0' );
define( 'IF_REVIEWS_FILE', __FILE__ );
define( 'IF_REVIEWS_PATH', plugin_dir_path( __FILE__ ) );
define( 'IF_REVIEWS_URL', plugin_dir_url( __FILE__ ) );
define( 'IF_REVIEWS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'IF_REVIEWS_GITHUB_URL', 'https://github.com/yog18r/Wordpress-Reviews/' );

// Order matters: helpers and sources are used by everything after them.
require_once IF_REVIEWS_PATH . 'includes/helpers.php';
require_once IF_REVIEWS_PATH . 'includes/sources.php';
require_once IF_REVIEWS_PATH . 'includes/settings.php';
require_once IF_REVIEWS_PATH . 'includes/enqueue.php';
require_once IF_REVIEWS_PATH . 'includes/post-type.php';
require_once IF_REVIEWS_PATH . 'includes/meta-box.php';
require_once IF_REVIEWS_PATH . 'includes/shortcode.php';
require_once IF_REVIEWS_PATH . 'includes/updater.php';

/**
 * Load translations from /languages.
 */
function if_reviews_load_textdomain() {
	load_plugin_textdomain( 'if-reviews', false, dirname( IF_REVIEWS_PLUGIN_BASENAME ) . '/languages' );
}
add_action( 'init', 'if_reviews_load_textdomain' );
