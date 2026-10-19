<?php
/**
 * Automatic updates from GitHub Releases.
 *
 * Uses the Plugin Update Checker library (MIT) bundled under
 * vendor/plugin-update-checker. When a new release is published on
 * GitHub, WordPress shows the update on the Plugins screen like any
 * other plugin.
 *
 * @package IF_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Boot the update checker.
 *
 * Runs on `init` rather than at include time so the plugin stays usable
 * even if the library is removed, and so the behaviour can be filtered.
 */
function if_reviews_init_update_checker() {

	/**
	 * Filter whether GitHub update checks are enabled.
	 *
	 * Return false to disable, e.g. on a site that manages this plugin
	 * through its own deployment pipeline.
	 *
	 * @param bool $enabled Default true.
	 */
	if ( ! apply_filters( 'if_reviews_update_checker_enabled', true ) ) {
		return;
	}

	$loader = IF_REVIEWS_PATH . 'vendor/plugin-update-checker/plugin-update-checker.php';

	if ( ! file_exists( $loader ) ) {
		return;
	}

	require_once $loader;

	if ( ! class_exists( '\YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
		return;
	}

	$checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		IF_REVIEWS_GITHUB_URL,
		IF_REVIEWS_FILE,
		'if-reviews'
	);

	// Releases are cut from main; this is the fallback when no release exists.
	$checker->setBranch( 'main' );

	// Prefer the if-reviews.zip built by the release workflow over the raw
	// repository archive, so the installed folder is always "if-reviews".
	$api = $checker->getVcsApi();

	if ( $api && method_exists( $api, 'enableReleaseAssets' ) ) {
		$api->enableReleaseAssets( '/if-reviews\.zip/i' );
	}
}
add_action( 'init', 'if_reviews_init_update_checker' );
