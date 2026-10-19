<?php
/**
 * Runs when the plugin is deleted from the Plugins screen.
 *
 * Always removes the plugin's settings. Reviews themselves are only
 * removed when "Delete all reviews when the plugin is uninstalled" was
 * ticked in Reviews → Display Settings, so a careless uninstall cannot
 * destroy years of collected feedback.
 *
 * @package IF_Reviews
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$if_reviews_options = get_option( 'if_reviews_options', array() );

if ( is_array( $if_reviews_options ) && ! empty( $if_reviews_options['delete_on_uninstall'] ) ) {

	$if_reviews_ids = get_posts(
		array(
			'post_type'      => 'if_review',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	foreach ( $if_reviews_ids as $if_reviews_id ) {
		wp_delete_post( $if_reviews_id, true );
	}
}

delete_option( 'if_reviews_options' );

// Update-checker housekeeping (safe if the keys do not exist).
delete_option( 'external_updates-if-reviews' );
delete_site_option( 'external_updates-if-reviews' );
