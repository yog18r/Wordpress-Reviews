<?php
/**
 * Blocks for the block editor. All three are dynamic: the editor shows a
 * live server-rendered preview and the front end reuses the shortcode
 * renderers, so both always match.
 *
 *  - if-reviews/reviews  : the widget ([if_reviews])
 *  - if-reviews/featured : one review as a quote ([if_review])
 *  - if-reviews/rating   : the inline badge ([if_reviews_rating])
 *
 * @package IF_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the blocks from their block.json files.
 */
function if_reviews_register_block() {

	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	register_block_type( IF_REVIEWS_PATH . 'blocks/reviews', array( 'render_callback' => 'if_reviews_render_block' ) );
	register_block_type( IF_REVIEWS_PATH . 'blocks/featured', array( 'render_callback' => 'if_reviews_render_featured_block' ) );
	register_block_type( IF_REVIEWS_PATH . 'blocks/rating', array( 'render_callback' => 'if_reviews_render_rating_block' ) );
}
// After if_reviews_register_assets() (init, 5) so the style handle exists.
add_action( 'init', 'if_reviews_register_block', 20 );

/**
 * Whether this render is the editor's preview request.
 *
 * @return bool
 */
function if_reviews_is_editor_preview() {
	return defined( 'REST_REQUEST' ) && REST_REQUEST;
}

/**
 * A note shown only in the editor when a block has nothing to render.
 *
 * @param string $message Plain text.
 * @return string
 */
function if_reviews_editor_notice( $message ) {
	return '<p class="if-reviews-empty">' . esc_html( $message ) . '</p>';
}

/**
 * Render the Reviews block: translate attributes into shortcode attributes.
 *
 * Empty / default attribute values are not passed, so the shortcode falls
 * back to Reviews → Display Settings exactly as it does for [if_reviews].
 *
 * @param array $attributes Block attributes.
 * @return string
 */
function if_reviews_render_block( $attributes ) {

	$atts = array();

	foreach ( array( 'layout', 'order', 'source', 'category', 'title' ) as $key ) {
		if ( ! empty( $attributes[ $key ] ) ) {
			$atts[ $key ] = $attributes[ $key ];
		}
	}

	if ( ! empty( $attributes['columns'] ) ) {
		$atts['columns'] = (int) $attributes['columns'];
	}

	if ( isset( $attributes['limit'] ) && (int) $attributes['limit'] >= 0 ) {
		$atts['limit'] = (int) $attributes['limit'];
	}

	if ( ! empty( $attributes['paginate'] ) ) {
		$atts['paginate'] = (int) $attributes['paginate'];
	}

	if ( isset( $attributes['autoplay'] ) && (int) $attributes['autoplay'] >= 0 ) {
		$atts['autoplay'] = (int) $attributes['autoplay'];
	}

	if ( ! empty( $attributes['showMore'] ) ) {
		$atts['show_more'] = $attributes['showMore'];
	}

	if ( ! empty( $attributes['showHeader'] ) ) {
		$atts['show_header'] = $attributes['showHeader'];
	}

	if ( ! empty( $attributes['showButtons'] ) ) {
		$atts['buttons'] = $attributes['showButtons'];
	}

	$html = if_reviews_shortcode( $atts );

	if ( '' === $html ) {
		return if_reviews_is_editor_preview()
			? if_reviews_editor_notice( __( 'Nothing to show yet: there are no published reviews matching these settings. Add reviews under Reviews in the admin menu.', 'if-reviews' ) )
			: '';
	}

	return sprintf( '<div %s>%s</div>', get_block_wrapper_attributes(), $html );
}

/**
 * Render the Featured Review block.
 *
 * @param array $attributes Block attributes.
 * @return string
 */
function if_reviews_render_featured_block( $attributes ) {

	$id = isset( $attributes['reviewId'] ) ? absint( $attributes['reviewId'] ) : 0;

	if ( ! $id ) {
		return if_reviews_is_editor_preview()
			? if_reviews_editor_notice( __( 'Choose a review in the block settings on the right.', 'if-reviews' ) )
			: '';
	}

	$html = if_review_single_shortcode( array( 'id' => $id ) );

	if ( '' === $html ) {
		return if_reviews_is_editor_preview()
			? if_reviews_editor_notice( __( 'That review is hidden, unpublished or no longer exists, so nothing will be shown.', 'if-reviews' ) )
			: '';
	}

	return sprintf( '<div %s>%s</div>', get_block_wrapper_attributes(), $html );
}

/**
 * Render the Rating Badge block.
 *
 * @param array $attributes Block attributes.
 * @return string
 */
function if_reviews_render_rating_block( $attributes ) {

	$atts = array(
		'source'   => isset( $attributes['source'] ) ? $attributes['source'] : '',
		'category' => isset( $attributes['category'] ) ? $attributes['category'] : '',
		'stars'    => empty( $attributes['showStars'] ) ? 'no' : 'yes',
		'count'    => empty( $attributes['showCount'] ) ? 'no' : 'yes',
	);

	$html = if_reviews_rating_shortcode( $atts );

	if ( '' === $html ) {
		return if_reviews_is_editor_preview()
			? if_reviews_editor_notice( __( 'No published reviews match these settings yet.', 'if-reviews' ) )
			: '';
	}

	return sprintf( '<div %s>%s</div>', get_block_wrapper_attributes(), $html );
}

/**
 * Editor assets: the stylesheet with the settings-driven variables, and the
 * list of reviews for the Featured Review block's picker.
 */
function if_reviews_block_editor_assets() {

	if_reviews_register_assets();

	wp_enqueue_style( 'if-reviews-style' );
	wp_add_inline_style( 'if-reviews-style', if_reviews_build_dynamic_css() );

	$ids = get_posts(
		array(
			'post_type'      => 'if_review',
			'post_status'    => 'publish',
			'posts_per_page' => 500,
			'fields'         => 'ids',
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		)
	);

	update_meta_cache( 'post', $ids );

	$list = array();

	foreach ( $ids as $id ) {

		$name   = get_post_meta( $id, '_if_reviewer_name', true );
		$rating = (int) get_post_meta( $id, '_if_rating', true );
		$date   = if_reviews_format_date( get_post_meta( $id, '_if_review_date', true ) );
		$shown  = get_post_meta( $id, '_if_display_on_website', true );
		$shown  = ( '' === $shown || '1' === $shown ) && ! if_reviews_is_auto_hidden( $rating );

		$label = trim( $name ) !== '' ? $name : get_the_title( $id );
		$label .= ' · ' . str_repeat( '★', max( 1, min( 5, $rating ) ) );

		if ( '' !== $date ) {
			$label .= ' · ' . $date;
		}

		if ( ! $shown ) {
			$label .= ' · ' . __( '(hidden)', 'if-reviews' );
		}

		$list[] = array(
			'value' => $id,
			'label' => $label,
		);
	}

	wp_add_inline_script(
		'if-reviews-featured-editor-script',
		'window.ifReviewsEditorData = ' . wp_json_encode( array( 'reviews' => $list ) ) . ';',
		'before'
	);
}
add_action( 'enqueue_block_editor_assets', 'if_reviews_block_editor_assets' );
