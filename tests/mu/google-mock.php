<?php
/**
 * Test-only: fakes Google's OAuth and Business Profile endpoints so the
 * import can be exercised without a real Cloud project.
 * The fixture set is chosen by the option ifr_mock_fixture (1 or 2).
 */

add_filter( 'pre_http_request', function ( $pre, $args, $url ) {

	$log = get_option( 'ifr_mock_log', array() );
	$log[] = ( isset( $args['method'] ) ? $args['method'] : 'GET' ) . ' ' . $url;
	update_option( 'ifr_mock_log', array_slice( $log, -50 ), false );

	$json = function ( $data, $code = 200 ) {
		return array(
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( $data ),
			'response' => array( 'code' => $code, 'message' => 'OK' ),
			'cookies'  => array(),
			'filename' => null,
		);
	};

	if ( 0 === strpos( $url, 'https://oauth2.googleapis.com/token' ) ) {
		$body = isset( $args['body'] ) ? $args['body'] : array();
		if ( isset( $body['grant_type'] ) && 'authorization_code' === $body['grant_type'] ) {
			if ( 'goodcode' !== $body['code'] ) {
				return $json( array( 'error' => 'invalid_grant', 'error_description' => 'Bad code' ), 400 );
			}
			return $json( array( 'access_token' => 'at-1', 'expires_in' => 3599, 'refresh_token' => 'rt-1', 'token_type' => 'Bearer' ) );
		}
		if ( isset( $body['refresh_token'] ) ) {
			if ( get_option( 'ifr_mock_revoked' ) ) {
				return $json( array( 'error' => 'invalid_grant', 'error_description' => 'Token has been expired or revoked.' ), 400 );
			}
			return $json( array( 'access_token' => 'at-' . time(), 'expires_in' => 3599, 'token_type' => 'Bearer' ) );
		}
	}

	if ( 0 === strpos( $url, 'https://oauth2.googleapis.com/revoke' ) ) {
		return $json( array() );
	}

	if ( 0 === strpos( $url, 'https://mybusinessaccountmanagement.googleapis.com/v1/accounts' ) ) {
		return $json( array( 'accounts' => array( array( 'name' => 'accounts/111', 'accountName' => 'Test Co', 'type' => 'PERSONAL' ) ) ) );
	}

	if ( 0 === strpos( $url, 'https://mybusinessbusinessinformation.googleapis.com/v1/accounts/111/locations' ) ) {
		$locs = array(
			array( 'name' => 'locations/222', 'title' => 'Test Co Melbourne', 'storefrontAddress' => array( 'addressLines' => array( '1 Collins St' ), 'locality' => 'Melbourne', 'administrativeArea' => 'VIC' ) ),
		);
		if ( get_option( 'ifr_mock_two_locations' ) ) {
			$locs[] = array( 'name' => 'locations/333', 'title' => 'Test Co Sydney', 'storefrontAddress' => array( 'locality' => 'Sydney' ) );
		}
		return $json( array( 'locations' => $locs ) );
	}

	if ( 0 === strpos( $url, 'https://mybusiness.googleapis.com/v4/accounts/111/locations/222/reviews' ) ) {
		$fixture = (int) get_option( 'ifr_mock_fixture', 1 );
		$mk = function ( $id, $name, $stars, $comment, $created, $updated = null ) {
			return array(
				'reviewId'   => $id,
				'reviewer'   => array( 'displayName' => $name, 'profilePhotoUrl' => '' ),
				'starRating' => $stars,
				'comment'    => $comment,
				'createTime' => $created,
				'updateTime' => $updated ? $updated : $created,
				'name'       => 'accounts/111/locations/222/reviews/' . $id,
			);
		};
		$reviews = array(
			// Exact match of a manual review.
			$mk( 'g-exact', 'Jane Smith', 'FIVE', 'Shannon was fantastic to deal with and explained everything clearly.', '2025-03-04T02:10:00Z' ),
			// Manual copy is truncated; name abbreviated on the site.
			$mk( 'g-trunc', 'Peter Jones', 'FOUR', 'Very professional service from start to finish. Would recommend to anyone looking for honest advice about their finances and future planning.', '2024-11-20T22:00:00Z' ),
			// Rating only, matches a manual rating-only review by name/date.
			$mk( 'g-ratingonly', 'Li Wei', 'FIVE', '', '2025-06-15T09:00:00Z' ),
			// Same name as a manual review but different text and rating: possible match only.
			$mk( 'g-weak', 'Sam Taylor', 'THREE', 'Service was okay but slow to respond at times.', '2025-01-10T00:00:00Z' ),
			// Brand new.
			$mk( 'g-new1', 'Aisha Khan', 'FIVE', 'Brilliant. Patient, thorough and genuinely helpful.', '2025-09-01T05:00:00Z' ),
			$mk( 'g-new2', 'A Google user', 'TWO', 'Did not get back to me.', '2025-09-10T05:00:00Z' ),
		);
		if ( 2 === $fixture ) {
			// Jane edited her review; Sam's review was removed.
			$reviews[0] = $mk( 'g-exact', 'Jane Smith', 'FOUR', 'Shannon was fantastic to deal with and explained everything clearly. Update: a year on, still happy.', '2025-03-04T02:10:00Z', '2026-03-04T02:10:00Z' );
			unset( $reviews[3] );
			$reviews   = array_values( $reviews );
			$reviews[] = $mk( 'g-new3', 'Maria Rossi', 'FIVE', 'Excellent advice, highly recommend.', '2026-01-05T05:00:00Z' );
		}
		// Page in twos to exercise pageToken.
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $q );
		$page  = isset( $q['pageToken'] ) ? (int) substr( $q['pageToken'], 1 ) : 0;
		$chunk = array_slice( $reviews, $page * 2, 2 );
		$out   = array( 'reviews' => $chunk, 'averageRating' => 4.3, 'totalReviewCount' => count( $reviews ) );
		if ( ( $page + 1 ) * 2 < count( $reviews ) ) {
			$out['nextPageToken'] = 'p' . ( $page + 1 );
		}
		return $json( $out );
	}

	return $pre;
}, 10, 3 );
