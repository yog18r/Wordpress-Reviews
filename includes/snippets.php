<?php
/**
 * The smaller shortcodes: one featured review, and an inline rating badge.
 * Both are also available as blocks (see block.php).
 *
 * @package IF_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * [if_review id="42"] : one review on its own as a highlighted quote.
 *
 * Respects the review's visibility: a hidden or auto-hidden review renders
 * nothing, so a featured review can never slip past the normal rules.
 *
 * @param array|string $atts Shortcode attributes.
 * @return string
 */
function if_review_single_shortcode( $atts = array() ) {

	$atts = shortcode_atts(
		array(
			'id' => 0,
		),
		$atts,
		'if_review'
	);

	$id = absint( $atts['id'] );

	if ( ! $id ) {
		return '';
	}

	$post = get_post( $id );

	if ( ! $post || 'if_review' !== $post->post_type || 'publish' !== $post->post_status ) {
		return '';
	}

	$options = if_reviews_get_options();
	$review  = if_reviews_build_review( $id, $options );

	if ( ! $review['display'] ) {
		return '';
	}

	if_reviews_enqueue_assets();

	ob_start();
	?>
	<div class="if-reviews-wrap if-featured">
		<?php if_reviews_render_card( $review, $options, false, 'if-featured-card' ); ?>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'if_review', 'if_review_single_shortcode' );

/**
 * [if_reviews_rating] : "★★★★★ 4.8 from 120 reviews" as an inline badge.
 *
 * Attributes: source, category (same as [if_reviews]); stars="no" hides
 * the stars; count="no" hides the "from N reviews" text.
 *
 * @param array|string $atts Shortcode attributes.
 * @return string
 */
function if_reviews_rating_shortcode( $atts = array() ) {

	$atts = shortcode_atts(
		array(
			'source'   => '',
			'category' => '',
			'stars'    => 'yes',
			'count'    => 'yes',
		),
		$atts,
		'if_reviews_rating'
	);

	$stats = if_reviews_get_stats(
		if_reviews_parse_list( $atts['source'] ),
		if_reviews_parse_list( $atts['category'], 'sanitize_title' )
	);

	if ( empty( $stats['count'] ) ) {
		return '';
	}

	$show_stars = if_reviews_to_bool( $atts['stars'] );
	$show_count = if_reviews_to_bool( $atts['count'] );

	if_reviews_enqueue_assets();

	$value = '<span class="if-rating-value">' . esc_html( number_format_i18n( $stats['average'], 1 ) ) . '</span>';

	if ( $show_count ) {
		// One sentence, so translators can order the rating and count freely.
		$text = '<span class="if-rating-count">' . sprintf(
			/* translators: 1: average rating, e.g. 4.8; 2: number of reviews */
			esc_html( _n( '%1$s from %2$s review', '%1$s from %2$s reviews', $stats['count'], 'if-reviews' ) ),
			$value,
			esc_html( number_format_i18n( $stats['count'] ) )
		) . '</span>';
	} else {
		$text = $value;
	}

	ob_start();
	?>
	<span class="if-reviews-wrap if-rating-badge"><?php
	if ( $show_stars ) {
		if_reviews_render_stars( $stats['average'], 'if-stars if-stars-small', 1 );
	}
	echo $text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts above.
	?></span>
	<?php
	return trim( ob_get_clean() );
}
add_shortcode( 'if_reviews_rating', 'if_reviews_rating_shortcode' );
