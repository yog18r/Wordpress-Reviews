<?php
/**
 * The "Reviews" block for the block editor. A dynamic block: the editor
 * shows a live server-rendered preview, and the front end reuses the
 * shortcode renderer, so both always match.
 *
 * @package IF_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the block from blocks/reviews/block.json.
 */
function if_reviews_register_block() {

	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	register_block_type(
		IF_REVIEWS_PATH . 'blocks/reviews',
		array(
			'render_callback' => 'if_reviews_render_block',
		)
	);
}
// After if_reviews_register_assets() (init, 5) so the style handle exists.
add_action( 'init', 'if_reviews_register_block', 20 );

/**
 * Render the block: translate attributes into shortcode attributes.
 *
 * Empty / default attribute values are not passed, so the shortcode falls
 * back to Reviews → Display Settings exactly as it does for [if_reviews].
 *
 * @param array $attributes Block attributes.
 * @return string
 */
function if_reviews_render_block( $attributes ) {

	$atts = array();

	if ( ! empty( $attributes['layout'] ) ) {
		$atts['layout'] = $attributes['layout'];
	}

	if ( ! empty( $attributes['columns'] ) ) {
		$atts['columns'] = (int) $attributes['columns'];
	}

	if ( isset( $attributes['limit'] ) && (int) $attributes['limit'] >= 0 ) {
		$atts['limit'] = (int) $attributes['limit'];
	}

	if ( ! empty( $attributes['showMore'] ) ) {
		$atts['show_more'] = $attributes['showMore'];
	}

	if ( ! empty( $attributes['order'] ) ) {
		$atts['order'] = $attributes['order'];
	}

	if ( ! empty( $attributes['source'] ) ) {
		$atts['source'] = $attributes['source'];
	}

	if ( ! empty( $attributes['showHeader'] ) ) {
		$atts['show_header'] = $attributes['showHeader'];
	}

	if ( ! empty( $attributes['title'] ) ) {
		$atts['title'] = $attributes['title'];
	}

	if ( ! empty( $attributes['showButtons'] ) ) {
		$atts['buttons'] = $attributes['showButtons'];
	}

	$html = if_reviews_shortcode( $atts );

	if ( '' === $html ) {

		// Say why the preview is empty in the editor; render nothing on the site.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return '<p class="if-reviews-empty">'
				. esc_html__( 'Nothing to show yet: there are no published reviews matching these settings. Add reviews under Reviews in the admin menu.', 'if-reviews' )
				. '</p>';
		}

		return '';
	}

	return sprintf( '<div %s>%s</div>', get_block_wrapper_attributes(), $html );
}

/**
 * The editor preview needs the stylesheet plus the settings-driven
 * variables, just like the front end.
 */
function if_reviews_block_editor_assets() {

	if_reviews_register_assets();

	wp_enqueue_style( 'if-reviews-style' );
	wp_add_inline_style( 'if-reviews-style', if_reviews_build_dynamic_css() );
}
add_action( 'enqueue_block_editor_assets', 'if_reviews_block_editor_assets' );
