<?php
/**
 * Test-only helpers, loaded as a must-use plugin by tests/run.sh.
 * Never shipped: tests/ is excluded from the release zip.
 */

// Capture the token sent to Google's revoke endpoint instead of sending it.
add_filter(
	'pre_http_request',
	function ( $pre, $args, $url ) {
		if ( 0 === strpos( $url, 'https://oauth2.googleapis.com/revoke' ) ) {
			update_option( 'ifr_test_revoked', array( 'url' => $url, 'body' => isset( $args['body'] ) ? $args['body'] : array() ) );
			return array( 'headers' => array(), 'body' => '{}', 'response' => array( 'code' => 200, 'message' => 'OK' ), 'cookies' => array(), 'filename' => null );
		}
		return $pre;
	},
	5,
	3
);

// wp-config.php constants, switched on per test phase.
if ( getenv( 'IFR_ENCRYPTION_KEY' ) ) {
	define( 'IF_REVIEWS_ENCRYPTION_KEY', getenv( 'IFR_ENCRYPTION_KEY' ) );
}
if ( getenv( 'IFR_CONSTANT_CREDENTIALS' ) ) {
	define( 'IF_REVIEWS_GOOGLE_CLIENT_ID', 'const-id.apps.googleusercontent.com' );
	define( 'IF_REVIEWS_GOOGLE_CLIENT_SECRET', 'CONST-SECRET' );
}
