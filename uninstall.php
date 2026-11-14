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

	global $wpdb;

	// Every review in any state, including the trash and auto-drafts.
	$if_reviews_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'if_review'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- one-off cleanup on uninstall, constant query.

	foreach ( $if_reviews_ids as $if_reviews_id ) {
		wp_delete_post( (int) $if_reviews_id, true );
	}

	// And the review categories (the plugin is not loaded here, so register
	// the taxonomy just long enough to delete its terms).
	if ( ! taxonomy_exists( 'if_review_category' ) ) {
		register_taxonomy( 'if_review_category', 'if_review' );
	}

	$if_reviews_terms = get_terms(
		array(
			'taxonomy'   => 'if_review_category',
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);

	if ( is_array( $if_reviews_terms ) ) {
		foreach ( $if_reviews_terms as $if_reviews_term ) {
			wp_delete_term( (int) $if_reviews_term, 'if_review_category' );
		}
	}
}

// Revoke the Google connection, if any, so the token stops working.
require_once __DIR__ . '/includes/crypto.php';

$if_reviews_google = get_option( 'if_reviews_google', array() );
$if_reviews_token  = is_array( $if_reviews_google ) && ! empty( $if_reviews_google['refresh_token'] ) ? if_reviews_decrypt( $if_reviews_google['refresh_token'] ) : '';

if ( is_string( $if_reviews_token ) && '' !== $if_reviews_token ) {
	wp_remote_post(
		'https://oauth2.googleapis.com/revoke',
		array( 'timeout' => 10, 'body' => array( 'token' => $if_reviews_token ) )
	);
}

delete_option( 'if_reviews_options' );
delete_option( 'if_reviews_google' );
delete_option( 'if_reviews_version' );
wp_clear_scheduled_hook( 'if_reviews_google_sync_event' );

// Short-lived data (Google sign-in state, location lists, previews, notices,
// import results), stored per user.
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_if\\_reviews\\_%' OR option_name LIKE '\\_transient\\_timeout\\_if\\_reviews\\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- one-off cleanup on uninstall, constant query.

delete_transient( 'if_reviews_review_cache' );
delete_transient( 'if_reviews_stats_cache' );

// Update-checker housekeeping (safe if the keys do not exist).
delete_option( 'external_updates-if-reviews' );
delete_site_option( 'external_updates-if-reviews' );
