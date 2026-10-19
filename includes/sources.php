<?php
/**
 * Review source registry and badge icons.
 *
 * A "source" records where a review was originally left (Google,
 * Facebook, direct feedback, ...). It is stored per review, shown as a
 * small badge on the card, and summarised honestly in the widget header.
 *
 * @package IF_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * All known review sources.
 *
 * Icons are simple, single-colour SVG marks drawn for this plugin. They
 * are deliberately not the platforms' official logos; the text label does
 * the identifying. Each icon inherits `currentColor`.
 *
 * Add your own with the `if_reviews_sources` filter:
 *
 *     add_filter( 'if_reviews_sources', function ( $sources ) {
 *         $sources['tripadvisor'] = array(
 *             'label' => 'Tripadvisor',
 *             'icon'  => '', // optional inline SVG
 *         );
 *         return $sources;
 *     } );
 *
 * @return array<string, array{label:string, description?:string, icon?:string}>
 */
function if_reviews_get_sources() {

	$sources = array(
		'direct'        => array(
			'label'       => __( 'Direct', 'if-reviews' ),
			'description' => __( 'Collected directly, e.g. by email, a form, or in person. No badge is shown.', 'if-reviews' ),
			'icon'        => '',
		),
		'google'        => array(
			'label'       => 'Google',
			'description' => __( 'A review left on your Google Business Profile.', 'if-reviews' ),
			'icon'        => if_reviews_icon_letter( 'G' ),
		),
		'facebook'      => array(
			'label'       => 'Facebook',
			'description' => __( 'A recommendation or review left on your Facebook page.', 'if-reviews' ),
			'icon'        => if_reviews_icon_letter( 'f' ),
		),
		'trustpilot'    => array(
			'label'       => 'Trustpilot',
			'description' => __( 'A review left on Trustpilot.', 'if-reviews' ),
			'icon'        => if_reviews_icon_star(),
		),
		'yelp'          => array(
			'label'       => 'Yelp',
			'description' => __( 'A review left on Yelp.', 'if-reviews' ),
			'icon'        => if_reviews_icon_letter( 'Y' ),
		),
		'productreview' => array(
			'label'       => 'ProductReview.com.au',
			'description' => __( 'A review left on ProductReview.com.au.', 'if-reviews' ),
			'icon'        => if_reviews_icon_letter( 'P' ),
		),
		'other'         => array(
			'label'       => __( 'Other', 'if-reviews' ),
			'description' => __( 'Any other platform. Enter its name in the custom label field.', 'if-reviews' ),
			'icon'        => if_reviews_icon_bubble(),
		),
	);

	/**
	 * Filter the list of review sources.
	 *
	 * @param array $sources Source definitions keyed by slug.
	 */
	return apply_filters( 'if_reviews_sources', $sources );
}

/**
 * Get one source definition, falling back to "direct" for unknown keys.
 *
 * @param string $key Source slug.
 * @return array{key:string, label:string, description:string, icon:string}
 */
function if_reviews_get_source( $key ) {

	$sources = if_reviews_get_sources();
	$key     = sanitize_key( (string) $key );

	if ( '' === $key || ! isset( $sources[ $key ] ) ) {
		$key = isset( $sources['direct'] ) ? 'direct' : (string) array_key_first( $sources );
	}

	$defaults = array(
		'key'         => $key,
		'label'       => ucfirst( $key ),
		'description' => '',
		'icon'        => '',
	);

	return array_merge( $defaults, (array) $sources[ $key ], array( 'key' => $key ) );
}

/**
 * Resolve the source for a specific review, honouring the custom label
 * on "Other".
 *
 * @param int $post_id Review post ID.
 * @return array{key:string, label:string, description:string, icon:string}
 */
function if_reviews_get_review_source( $post_id ) {

	$key    = get_post_meta( $post_id, '_if_source', true );
	$source = if_reviews_get_source( $key ? $key : 'direct' );

	if ( 'other' === $source['key'] ) {

		$custom = trim( (string) get_post_meta( $post_id, '_if_source_label', true ) );

		if ( '' !== $custom ) {
			$source['label'] = $custom;
		}
	}

	return $source;
}

/**
 * Whether a source should display a badge on review cards.
 *
 * "Direct" reviews get no badge: the header already states the mix of
 * sources, and a wall of identical "Direct" badges adds nothing.
 *
 * @param array $source Source definition from if_reviews_get_source().
 * @return bool
 */
function if_reviews_source_has_badge( $source ) {

	/**
	 * Filter whether a given source shows a badge on cards.
	 *
	 * @param bool  $show   Default: everything except "direct".
	 * @param array $source Source definition.
	 */
	return (bool) apply_filters( 'if_reviews_source_has_badge', 'direct' !== $source['key'], $source );
}

/**
 * Allowed SVG markup for source icons (used with wp_kses so icons added
 * via the filter are still constrained to safe elements).
 *
 * @return array
 */
function if_reviews_svg_kses() {

	$presentation = array(
		'fill'            => true,
		'stroke'          => true,
		'stroke-width'    => true,
		'stroke-linecap'  => true,
		'stroke-linejoin' => true,
		'opacity'         => true,
	);

	return array(
		'svg'     => array_merge(
			$presentation,
			array(
				'class'       => true,
				'viewbox'     => true,
				'width'       => true,
				'height'      => true,
				'aria-hidden' => true,
				'focusable'   => true,
				'role'        => true,
				'xmlns'       => true,
			)
		),
		'g'       => $presentation,
		'path'    => array_merge( $presentation, array( 'd' => true ) ),
		'circle'  => array_merge( $presentation, array( 'cx' => true, 'cy' => true, 'r' => true ) ),
		'rect'    => array_merge( $presentation, array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true ) ),
		'polygon' => array_merge( $presentation, array( 'points' => true ) ),
		'line'    => array_merge( $presentation, array( 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true ) ),
		'text'    => array_merge(
			$presentation,
			array(
				'x'           => true,
				'y'           => true,
				'text-anchor' => true,
				'font-family' => true,
				'font-size'   => true,
				'font-weight' => true,
			)
		),
	);
}

/**
 * Sanitised icon markup for a source, or '' when it has none.
 *
 * @param array $source Source definition.
 * @return string
 */
function if_reviews_source_icon_html( $source ) {

	if ( empty( $source['icon'] ) ) {
		return '';
	}

	return wp_kses( $source['icon'], if_reviews_svg_kses() );
}

/* ------------------------------------------------------------------
 * Icon drawings
 * ---------------------------------------------------------------- */

/**
 * A single letter inside a thin circle.
 *
 * @param string $letter One or two characters.
 * @return string
 */
function if_reviews_icon_letter( $letter ) {

	return '<svg class="if-source-icon" viewBox="0 0 20 20" width="16" height="16" aria-hidden="true" focusable="false">'
		. '<circle cx="10" cy="10" r="9" fill="none" stroke="currentColor" stroke-width="1.6"/>'
		. '<text x="10" y="14.3" text-anchor="middle" font-family="system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif" font-size="11.5" font-weight="700" fill="currentColor">'
		. esc_html( $letter )
		. '</text>'
		. '</svg>';
}

/**
 * A plain five-point star.
 *
 * @return string
 */
function if_reviews_icon_star() {

	return '<svg class="if-source-icon" viewBox="0 0 20 20" width="16" height="16" aria-hidden="true" focusable="false">'
		. '<path d="M10 1.8l2.5 5.3 5.7.7-4.2 4 1.1 5.7L10 14.7l-5.1 2.8 1.1-5.7-4.2-4 5.7-.7z" fill="currentColor"/>'
		. '</svg>';
}

/**
 * A speech bubble outline.
 *
 * @return string
 */
function if_reviews_icon_bubble() {

	return '<svg class="if-source-icon" viewBox="0 0 20 20" width="16" height="16" aria-hidden="true" focusable="false">'
		. '<path d="M3 3h14a1.5 1.5 0 0 1 1.5 1.5v8A1.5 1.5 0 0 1 17 14h-6l-4.5 3.5V14H3a1.5 1.5 0 0 1-1.5-1.5v-8A1.5 1.5 0 0 1 3 3z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>'
		. '</svg>';
}
