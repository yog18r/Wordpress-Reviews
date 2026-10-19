<?php
/**
 * Options, sanitisation and small formatting helpers.
 *
 * @package IF_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The option name everything is stored under: one array, one row in
 * wp_options, easy to export/import.
 */
define( 'IF_REVIEWS_OPTION_KEY', 'if_reviews_options' );

/**
 * Default settings.
 *
 * Appearance defaults match the original 1.x design exactly, so sites
 * upgrading see no visual change until they open the settings page.
 *
 * @return array
 */
function if_reviews_default_options() {

	return array(
		// Appearance.
		'accent_color'  => '#f5a623',
		'card_bg_color' => '#ffffff',
		'border_color'  => '#d6d6d6',
		'border_radius' => 14,
		'text_color'    => '#4b5563',
		'meta_color'    => '#6b7280',
		'avatar_colors' => '4285f4,34a853,ea4335,fbbc04',

		// Display (defaults for the shortcode; each can be overridden
		// per shortcode with an attribute of the same name).
		'show_header'       => 1,
		'header_title'      => '',
		'show_source_icons' => 1,
		'layout'            => 'grid',
		'columns'           => 3,
		'limit'             => 0,
		'show_more'         => 1,
		'order'             => 'newest',
		// 0 = off; otherwise reviews rated below this many stars are hidden
		// from the cards (they still count in the header).
		'min_rating'        => 0,
		// 0 = always show full text; otherwise characters before "Read more".
		'excerpt_length'    => 0,
		'show_review_links' => 1,

		// "Leave us a review" buttons. Off by default; one row per platform.
		'cta_enabled' => 0,
		'cta_heading' => '',
		'cta_new_tab' => 1,
		'cta_links'   => array(),

		// Data.
		'delete_on_uninstall' => 0,
	);
}

/**
 * Current settings merged with defaults, so fields added in later
 * versions always have a sane fallback.
 *
 * @return array
 */
function if_reviews_get_options() {

	$saved = get_option( IF_REVIEWS_OPTION_KEY, array() );

	if ( ! is_array( $saved ) ) {
		$saved = array();
	}

	return wp_parse_args( $saved, if_reviews_default_options() );
}

/**
 * Layouts the shortcode understands.
 *
 * @return array<string, string> slug => label
 */
function if_reviews_get_layouts() {

	return array(
		'grid'     => __( 'Grid', 'if-reviews' ),
		'list'     => __( 'List', 'if-reviews' ),
		'carousel' => __( 'Carousel', 'if-reviews' ),
	);
}

/**
 * Sort orders the shortcode understands.
 *
 * @return array<string, string> slug => label
 */
function if_reviews_get_orders() {

	return array(
		'newest'  => __( 'Newest first', 'if-reviews' ),
		'oldest'  => __( 'Oldest first', 'if-reviews' ),
		'highest' => __( 'Highest rating first', 'if-reviews' ),
		'random'  => __( 'Random', 'if-reviews' ),
	);
}

/**
 * Normalise a checkbox / shortcode boolean.
 *
 * Accepts 1/0, true/false, "yes"/"no", "on"/"off".
 *
 * @param mixed $value Raw value.
 * @return int 1 or 0
 */
function if_reviews_to_bool( $value ) {

	if ( is_bool( $value ) ) {
		return $value ? 1 : 0;
	}

	$value = strtolower( trim( (string) $value ) );

	return in_array( $value, array( '1', 'true', 'yes', 'on' ), true ) ? 1 : 0;
}

/**
 * Sanitise settings on save (Settings API callback).
 *
 * @param mixed $input Raw submitted values.
 * @return array
 */
function if_reviews_sanitize_options( $input ) {

	$defaults = if_reviews_default_options();
	$output   = array();
	$input    = is_array( $input ) ? $input : array();

	/* Appearance ------------------------------------------------ */

	$color_fields = array( 'accent_color', 'card_bg_color', 'border_color', 'text_color', 'meta_color' );

	foreach ( $color_fields as $field ) {

		$value = isset( $input[ $field ] ) ? sanitize_hex_color( $input[ $field ] ) : '';

		$output[ $field ] = $value ? $value : $defaults[ $field ];
	}

	$output['border_radius'] = isset( $input['border_radius'] )
		? max( 0, min( 60, absint( $input['border_radius'] ) ) )
		: $defaults['border_radius'];

	// Avatar colours: comma-separated hex list, each validated,
	// empty/invalid entries dropped.
	$avatar_colors = array();

	if ( ! empty( $input['avatar_colors'] ) ) {

		foreach ( explode( ',', (string) $input['avatar_colors'] ) as $hex ) {

			$hex = sanitize_hex_color( '#' . ltrim( trim( $hex ), '#' ) );

			if ( $hex ) {
				$avatar_colors[] = ltrim( $hex, '#' );
			}
		}
	}

	$output['avatar_colors'] = ! empty( $avatar_colors )
		? implode( ',', $avatar_colors )
		: $defaults['avatar_colors'];

	/* Display --------------------------------------------------- */

	// Checkboxes are absent from $input when unchecked.
	$output['show_header']       = isset( $input['show_header'] ) ? if_reviews_to_bool( $input['show_header'] ) : 0;
	$output['show_source_icons'] = isset( $input['show_source_icons'] ) ? if_reviews_to_bool( $input['show_source_icons'] ) : 0;
	$output['show_more']         = isset( $input['show_more'] ) ? if_reviews_to_bool( $input['show_more'] ) : 0;

	$output['header_title'] = isset( $input['header_title'] )
		? sanitize_text_field( $input['header_title'] )
		: '';

	$output['layout'] = ( isset( $input['layout'] ) && array_key_exists( $input['layout'], if_reviews_get_layouts() ) )
		? $input['layout']
		: $defaults['layout'];

	$output['order'] = ( isset( $input['order'] ) && array_key_exists( $input['order'], if_reviews_get_orders() ) )
		? $input['order']
		: $defaults['order'];

	$output['columns'] = isset( $input['columns'] )
		? max( 1, min( 4, absint( $input['columns'] ) ) )
		: $defaults['columns'];

	$output['limit'] = isset( $input['limit'] )
		? max( 0, min( 500, absint( $input['limit'] ) ) )
		: $defaults['limit'];

	$min_rating = isset( $input['min_rating'] ) ? absint( $input['min_rating'] ) : 0;

	$output['min_rating'] = ( $min_rating >= 2 && $min_rating <= 5 ) ? $min_rating : 0;

	$output['excerpt_length'] = isset( $input['excerpt_length'] )
		? max( 0, min( 5000, absint( $input['excerpt_length'] ) ) )
		: $defaults['excerpt_length'];

	// Anything under 40 characters would be silly; treat it as "off".
	if ( $output['excerpt_length'] > 0 && $output['excerpt_length'] < 40 ) {
		$output['excerpt_length'] = 0;
	}

	$output['show_review_links'] = isset( $input['show_review_links'] ) ? if_reviews_to_bool( $input['show_review_links'] ) : 0;

	/* "Leave us a review" buttons -------------------------------- */

	$output['cta_enabled'] = isset( $input['cta_enabled'] ) ? if_reviews_to_bool( $input['cta_enabled'] ) : 0;
	$output['cta_new_tab'] = isset( $input['cta_new_tab'] ) ? if_reviews_to_bool( $input['cta_new_tab'] ) : 0;
	$output['cta_heading'] = isset( $input['cta_heading'] ) ? sanitize_text_field( $input['cta_heading'] ) : '';

	$links = array();

	if ( ! empty( $input['cta_links'] ) && is_array( $input['cta_links'] ) ) {

		$sources = if_reviews_get_sources();

		foreach ( $input['cta_links'] as $key => $row ) {

			$key = sanitize_key( $key );

			if ( 'direct' === $key || ! isset( $sources[ $key ] ) || ! is_array( $row ) ) {
				continue;
			}

			$url  = if_reviews_sanitize_url( isset( $row['url'] ) ? $row['url'] : '' );
			$text = sanitize_text_field( isset( $row['text'] ) ? $row['text'] : '' );

			if ( '' === $url && '' === $text ) {
				continue;
			}

			$links[ $key ] = array(
				'url'  => $url,
				'text' => $text,
			);
		}
	}

	$output['cta_links'] = $links;

	/* Data ------------------------------------------------------ */

	$output['delete_on_uninstall'] = isset( $input['delete_on_uninstall'] ) ? if_reviews_to_bool( $input['delete_on_uninstall'] ) : 0;

	return $output;
}

/**
 * Darken a hex colour by a fraction. Used to build a two-tone gradient
 * from a single avatar colour.
 *
 * @param string $hex     Hex colour, with or without '#'.
 * @param float  $percent 0..1 amount to darken.
 * @return string Hex colour with '#'.
 */
function if_reviews_darken_hex( $hex, $percent ) {

	$hex = ltrim( (string) $hex, '#' );

	if ( strlen( $hex ) === 3 ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}

	if ( strlen( $hex ) !== 6 || ! ctype_xdigit( $hex ) ) {
		return '#000000';
	}

	$rgb = array_map( 'hexdec', str_split( $hex, 2 ) );

	foreach ( $rgb as &$channel ) {
		$channel = (int) max( 0, min( 255, round( $channel * ( 1 - $percent ) ) ) );
	}
	unset( $channel );

	return '#' . implode(
		'',
		array_map(
			function ( $c ) {
				return str_pad( dechex( $c ), 2, '0', STR_PAD_LEFT );
			},
			$rgb
		)
	);
}

/**
 * Pick white or dark text for a given background colour, based on
 * perceived brightness.
 *
 * @param string $hex Hex colour, with or without '#'.
 * @return string
 */
function if_reviews_contrast_text( $hex ) {

	$hex = ltrim( (string) $hex, '#' );

	if ( strlen( $hex ) === 3 ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}

	if ( strlen( $hex ) !== 6 || ! ctype_xdigit( $hex ) ) {
		return '#ffffff';
	}

	list( $r, $g, $b ) = array_map( 'hexdec', str_split( $hex, 2 ) );

	$brightness = ( ( $r * 299 ) + ( $g * 587 ) + ( $b * 114 ) ) / 1000;

	return $brightness > 150 ? '#3f3f3f' : '#ffffff';
}

/**
 * Generate reviewer initials from a name.
 *
 *   Alex T    -> AT
 *   Sam Lee   -> SL
 *   Jo        -> JO
 *
 * @param string $name Reviewer name.
 * @return string
 */
function if_reviews_get_initials( $name ) {

	$name = trim( (string) $name );

	if ( '' === $name ) {
		return '';
	}

	$words = preg_split( '/\s+/u', $name );

	if ( count( $words ) === 1 ) {
		return mb_strtoupper( mb_substr( $words[0], 0, 2 ) );
	}

	$first = $words[0];
	$last  = $words[ count( $words ) - 1 ];

	return mb_strtoupper( mb_substr( $first, 0, 1 ) . mb_substr( $last, 0, 1 ) );
}

/**
 * Back-compat alias for the 1.x function name.
 *
 * @param string $name Reviewer name.
 * @return string
 */
function if_get_review_initials( $name ) {
	return if_reviews_get_initials( $name );
}

/**
 * Format a stored Y-m-d review date for display ("August 2026").
 *
 * @param string $date Stored date string.
 * @return string
 */
function if_reviews_format_date( $date ) {

	$date = trim( (string) $date );

	if ( '' === $date ) {
		return '';
	}

	$dt = date_create_immutable_from_format( '!Y-m-d', $date, wp_timezone() );

	if ( ! $dt ) {
		return $date;
	}

	/**
	 * Filter the PHP date format used for review dates.
	 *
	 * @param string $format Default 'F Y' (e.g. "August 2026").
	 */
	$format = apply_filters( 'if_reviews_date_format', 'F Y' );

	return wp_date( $format, $dt->getTimestamp(), wp_timezone() );
}

/**
 * Whether a rating falls under the "hide reviews rated below" setting.
 *
 * @param int        $rating  1..5.
 * @param array|null $options Plugin options (loaded if omitted).
 * @return bool
 */
function if_reviews_is_auto_hidden( $rating, $options = null ) {

	if ( null === $options ) {
		$options = if_reviews_get_options();
	}

	$min = isset( $options['min_rating'] ) ? (int) $options['min_rating'] : 0;

	return $min > 0 && (int) $rating < $min;
}

/**
 * Split review text for "Read more": the first part ends on a word
 * boundary near $length characters; the rest is revealed on demand.
 *
 * @param string $text   Full text.
 * @param int    $length Characters to show initially (0 = never split).
 * @return array{0:string,1:string} [visible, remainder]; remainder '' when not split.
 */
function if_reviews_split_text( $text, $length ) {

	$text   = (string) $text;
	$length = (int) $length;

	// Don't bother splitting when the remainder would be tiny.
	if ( $length <= 0 || mb_strlen( $text ) <= $length + 40 ) {
		return array( $text, '' );
	}

	$cut = $length;

	// Walk back to the last whitespace, but not past half the length.
	$slice = mb_substr( $text, 0, $length );

	if ( preg_match( '/^(.*)\s\S*$/su', $slice, $m ) && mb_strlen( $m[1] ) >= $length / 2 ) {
		$cut = mb_strlen( $m[1] );
	}

	return array(
		rtrim( mb_substr( $text, 0, $cut ) ),
		mb_substr( $text, $cut ),
	);
}

/**
 * Validate and normalise a review URL. Only http(s) is accepted.
 *
 * @param string $url Raw URL.
 * @return string Clean URL or ''.
 */
function if_reviews_sanitize_url( $url ) {

	$url = esc_url_raw( trim( (string) $url ), array( 'http', 'https' ) );

	return $url ? $url : '';
}

/**
 * Asset version string: file modification time when available so
 * browsers pick up changes immediately, else the plugin version.
 *
 * @param string $relative_path Path relative to the plugin root.
 * @return string
 */
function if_reviews_asset_version( $relative_path ) {

	$path = IF_REVIEWS_PATH . ltrim( $relative_path, '/' );

	return file_exists( $path ) ? (string) filemtime( $path ) : IF_REVIEWS_VERSION;
}
