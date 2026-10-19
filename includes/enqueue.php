<?php
/**
 * Front-end assets: registered on every page, only enqueued where the
 * shortcode is actually rendered.
 *
 * @package IF_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register (not enqueue) the stylesheet and script.
 */
function if_reviews_register_assets() {

	if ( wp_style_is( 'if-reviews-style', 'registered' ) ) {
		return;
	}

	wp_register_style(
		'if-reviews-style',
		IF_REVIEWS_URL . 'assets/css/reviews.css',
		array(),
		if_reviews_asset_version( 'assets/css/reviews.css' )
	);

	wp_register_script(
		'if-reviews-script',
		IF_REVIEWS_URL . 'assets/js/reviews.js',
		array(),
		if_reviews_asset_version( 'assets/js/reviews.js' ),
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'if_reviews_register_assets', 5 );

/**
 * Enqueue the assets plus the settings-driven inline CSS. Safe to call
 * more than once and from inside the shortcode (late styles are printed
 * in the footer by WordPress).
 */
function if_reviews_enqueue_assets() {

	static $done = false;

	if ( $done ) {
		return;
	}

	$done = true;

	if_reviews_register_assets();

	wp_enqueue_style( 'if-reviews-style' );
	wp_add_inline_style( 'if-reviews-style', if_reviews_build_dynamic_css() );
	wp_enqueue_script( 'if-reviews-script' );
}

/**
 * When the current post's content contains the shortcode, enqueue early
 * so the stylesheet lands in <head> and the widget never flashes unstyled.
 * Shortcodes placed elsewhere (widgets, templates) are picked up by the
 * fallback call inside the shortcode handler.
 */
function if_reviews_maybe_enqueue_assets() {

	if ( ! is_singular() ) {
		return;
	}

	$post = get_post();

	if ( $post instanceof WP_Post && has_shortcode( $post->post_content, 'if_reviews' ) ) {
		if_reviews_enqueue_assets();
	}
}
add_action( 'wp_enqueue_scripts', 'if_reviews_maybe_enqueue_assets' );

/**
 * Build the CSS variable block and the avatar colour cycle from settings.
 *
 * @return string
 */
function if_reviews_build_dynamic_css() {

	$options = if_reviews_get_options();

	$css  = '.if-reviews-wrap{';
	$css .= '--if-accent-color:' . esc_attr( $options['accent_color'] ) . ';';
	$css .= '--if-card-bg-color:' . esc_attr( $options['card_bg_color'] ) . ';';
	$css .= '--if-border-color:' . esc_attr( $options['border_color'] ) . ';';
	$css .= '--if-border-radius:' . absint( $options['border_radius'] ) . 'px;';
	$css .= '--if-text-color:' . esc_attr( $options['text_color'] ) . ';';
	$css .= '--if-meta-color:' . esc_attr( $options['meta_color'] ) . ';';
	$css .= '}';

	// Avatar colour cycle, generated to match however many colours were set.
	$colors = array_values( array_filter( array_map( 'trim', explode( ',', (string) $options['avatar_colors'] ) ) ) );
	$total  = count( $colors );

	foreach ( $colors as $index => $hex ) {

		$hex = ltrim( $hex, '#' );

		if ( ! ctype_xdigit( $hex ) || ! in_array( strlen( $hex ), array( 3, 6 ), true ) ) {
			continue;
		}

		$css .= sprintf(
			'.if-reviews-wrap .if-review-card:nth-child(%1$dn+%2$d) .if-avatar{background:linear-gradient(135deg,#%3$s,%4$s);color:%5$s;}',
			$total,
			$index + 1,
			$hex,
			if_reviews_darken_hex( $hex, 0.15 ),
			if_reviews_contrast_text( $hex )
		);
	}

	return $css;
}
