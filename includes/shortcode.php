<?php
/**
 * The [if_reviews] shortcode: data gathering, summary header and layouts.
 *
 * @package IF_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load every published review as a plain array.
 *
 * All reviews are returned, including hidden ones, because the summary
 * header must describe the complete set. Callers filter for display.
 *
 * @return array[] Each: id, name, initials, date, timestamp, rating, text, display, source.
 */
function if_reviews_get_reviews() {

	$ids = get_posts(
		array(
			'post_type'              => 'if_review',
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		)
	);

	if ( empty( $ids ) ) {
		return array();
	}

	// One query for all the meta instead of one per field per review.
	update_meta_cache( 'post', $ids );

	$reviews = array();

	foreach ( $ids as $id ) {

		$rating = (int) get_post_meta( $id, '_if_rating', true );

		if ( $rating < 1 || $rating > 5 ) {
			$rating = 5;
		}

		$display = get_post_meta( $id, '_if_display_on_website', true );
		$date    = (string) get_post_meta( $id, '_if_review_date', true );
		$name    = (string) get_post_meta( $id, '_if_reviewer_name', true );

		$timestamp = '' !== $date ? strtotime( $date ) : false;

		if ( false === $timestamp ) {
			$timestamp = (int) get_post_timestamp( $id );
		}

		$reviews[] = array(
			'id'        => $id,
			'name'      => $name,
			'initials'  => if_reviews_get_initials( $name ),
			'date'      => $date,
			'timestamp' => (int) $timestamp,
			'rating'    => $rating,
			'text'      => (string) get_post_meta( $id, '_if_review_text', true ),
			// Reviews created before the checkbox existed stay visible.
			'display'   => ( '' === $display || '1' === $display ),
			'source'    => if_reviews_get_review_source( $id ),
		);
	}

	/**
	 * Filter the loaded reviews before stats and display.
	 *
	 * @param array[] $reviews Review arrays.
	 */
	return apply_filters( 'if_reviews_reviews', $reviews );
}

/**
 * Count, average and source breakdown for a set of reviews.
 *
 * @param array[] $reviews Review arrays.
 * @return array{count:int, average:float, sources:array}
 */
function if_reviews_calculate_stats( $reviews ) {

	$count   = count( $reviews );
	$sum     = 0;
	$sources = array();

	foreach ( $reviews as $review ) {

		$sum += $review['rating'];

		$source = $review['source'];
		$group  = $source['key'];

		// "Other" with different custom labels are different sources.
		if ( 'other' === $group ) {
			$group .= ':' . $source['label'];
		}

		if ( ! isset( $sources[ $group ] ) ) {
			$sources[ $group ] = array(
				'source' => $source,
				'count'  => 0,
			);
		}

		$sources[ $group ]['count']++;
	}

	// Most common source first.
	uasort(
		$sources,
		function ( $a, $b ) {
			return $b['count'] <=> $a['count'];
		}
	);

	return array(
		'count'   => $count,
		'average' => $count > 0 ? $sum / $count : 0.0,
		'sources' => $sources,
	);
}

/**
 * Sort reviews for display.
 *
 * @param array[] $reviews Review arrays.
 * @param string  $order   newest | oldest | highest | random.
 * @return array[]
 */
function if_reviews_sort_reviews( $reviews, $order ) {

	switch ( $order ) {

		case 'random':
			shuffle( $reviews );
			break;

		case 'oldest':
			usort(
				$reviews,
				function ( $a, $b ) {
					return ( $a['timestamp'] <=> $b['timestamp'] ) ?: ( $a['id'] <=> $b['id'] );
				}
			);
			break;

		case 'highest':
			usort(
				$reviews,
				function ( $a, $b ) {
					return ( $b['rating'] <=> $a['rating'] )
						?: ( $b['timestamp'] <=> $a['timestamp'] )
						?: ( $b['id'] <=> $a['id'] );
				}
			);
			break;

		case 'newest':
		default:
			usort(
				$reviews,
				function ( $a, $b ) {
					return ( $b['timestamp'] <=> $a['timestamp'] ) ?: ( $b['id'] <=> $a['id'] );
				}
			);
			break;
	}

	return $reviews;
}

/**
 * Shortcode handler.
 *
 * @param array|string $atts Shortcode attributes.
 * @return string
 */
function if_reviews_shortcode( $atts = array() ) {

	$options = if_reviews_get_options();

	$atts = shortcode_atts(
		array(
			'layout'      => $options['layout'],
			'columns'     => $options['columns'],
			'limit'       => $options['limit'],
			'show_more'   => $options['show_more'],
			'order'       => $options['order'],
			'source'      => '',
			'show_header' => $options['show_header'],
			'title'       => $options['header_title'],
		),
		$atts,
		'if_reviews'
	);

	$layout      = array_key_exists( $atts['layout'], if_reviews_get_layouts() ) ? $atts['layout'] : 'grid';
	$order       = array_key_exists( $atts['order'], if_reviews_get_orders() ) ? $atts['order'] : 'newest';
	$columns     = max( 1, min( 4, absint( $atts['columns'] ) ) );
	$limit       = max( 0, absint( $atts['limit'] ) );
	$show_more   = if_reviews_to_bool( $atts['show_more'] );
	$show_header = if_reviews_to_bool( $atts['show_header'] );
	$title       = sanitize_text_field( $atts['title'] );

	$source_filter = array_filter( array_map( 'sanitize_key', explode( ',', (string) $atts['source'] ) ) );

	$reviews = if_reviews_get_reviews();

	if ( ! empty( $source_filter ) ) {
		$reviews = array_values(
			array_filter(
				$reviews,
				function ( $review ) use ( $source_filter ) {
					return in_array( $review['source']['key'], $source_filter, true );
				}
			)
		);
	}

	if ( empty( $reviews ) ) {
		return '';
	}

	$stats = if_reviews_calculate_stats( $reviews );

	$displayed = array_values(
		array_filter(
			$reviews,
			function ( $review ) {
				return $review['display'];
			}
		)
	);

	$displayed = if_reviews_sort_reviews( $displayed, $order );

	$total_displayed   = count( $displayed );
	$initially_visible = ( $limit > 0 ) ? min( $limit, $total_displayed ) : $total_displayed;
	$use_show_more     = ( 'carousel' !== $layout && $limit > 0 && $show_more && $total_displayed > $limit );

	if ( $limit > 0 && ! $use_show_more ) {
		$displayed = array_slice( $displayed, 0, $limit );
	}

	if_reviews_enqueue_assets();

	static $instance = 0;
	$instance++;

	$id      = 'if-reviews-' . $instance;
	$classes = array(
		'if-reviews-wrap',
		'if-layout-' . $layout,
		'if-columns-' . $columns,
	);

	ob_start();
	?>

	<div id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">

		<?php
		if ( $show_header ) {
			if_reviews_render_header( $stats, $title, $options );
		}
		?>

		<?php if ( ! empty( $displayed ) ) : ?>

			<div class="if-cards-outer">

				<?php if ( 'carousel' === $layout ) : ?>
					<button type="button" class="if-carousel-btn if-carousel-prev" aria-controls="<?php echo esc_attr( $id ); ?>-track" aria-label="<?php esc_attr_e( 'Previous reviews', 'if-reviews' ); ?>">
						<svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M12.5 4l-6 6 6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</button>
				<?php endif; ?>

				<div
					id="<?php echo esc_attr( $id ); ?>-track"
					class="if-cards"
					<?php if ( 'carousel' === $layout ) : ?>
						role="region"
						aria-label="<?php esc_attr_e( 'Reviews', 'if-reviews' ); ?>"
						tabindex="0"
					<?php endif; ?>
				>
					<?php
					foreach ( $displayed as $index => $review ) {
						if_reviews_render_card( $review, $options, $index >= $initially_visible );
					}
					?>
				</div>

				<?php if ( 'carousel' === $layout ) : ?>
					<button type="button" class="if-carousel-btn if-carousel-next" aria-controls="<?php echo esc_attr( $id ); ?>-track" aria-label="<?php esc_attr_e( 'Next reviews', 'if-reviews' ); ?>">
						<svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M7.5 4l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</button>
				<?php endif; ?>

			</div>

			<?php if ( $use_show_more ) : ?>
				<p class="if-show-more-wrap">
					<button type="button" class="if-show-more" data-step="<?php echo (int) $limit; ?>">
						<?php
						/**
						 * Filter the "Show more" button label.
						 *
						 * @param string $label Default "Show more reviews".
						 */
						echo esc_html( apply_filters( 'if_reviews_show_more_label', __( 'Show more reviews', 'if-reviews' ) ) );
						?>
					</button>
				</p>
			<?php endif; ?>

		<?php endif; ?>

	</div>

	<?php
	return ob_get_clean();
}
add_shortcode( 'if_reviews', 'if_reviews_shortcode' );

/**
 * Summary header: title, average, stars and an honest description of
 * where the reviews came from.
 *
 * @param array  $stats   From if_reviews_calculate_stats().
 * @param string $title   Custom title, or '' for automatic.
 * @param array  $options Plugin options.
 */
function if_reviews_render_header( $stats, $title, $options ) {

	$sources = $stats['sources'];
	$count   = (int) $stats['count'];
	$average = (float) $stats['average'];

	$single          = null;
	$single_platform = false;

	if ( 1 === count( $sources ) ) {
		$first           = reset( $sources );
		$single          = $first['source'];
		$single_platform = ( 'direct' !== $single['key'] );
	}

	if ( '' !== $title ) {
		$heading = $title;
	} elseif ( $single_platform ) {
		/* translators: %s: platform name, e.g. Google */
		$heading = sprintf( __( '%s Reviews', 'if-reviews' ), $single['label'] );
	} else {
		$heading = __( 'Reviews', 'if-reviews' );
	}

	$icon = ( $single_platform && $options['show_source_icons'] ) ? if_reviews_source_icon_html( $single ) : '';

	if ( count( $sources ) > 1 ) {

		$labels = array();

		foreach ( $sources as $entry ) {
			$labels[] = ( 'direct' === $entry['source']['key'] )
				? __( 'direct feedback', 'if-reviews' )
				: $entry['source']['label'];
		}

		$meta = wp_sprintf(
			/* translators: 1: number of reviews, 2: list of sources, e.g. "Google, Facebook, and direct feedback" */
			_n( '%1$s review from %2$l', '%1$s reviews from %2$l', $count, 'if-reviews' ),
			number_format_i18n( $count ),
			$labels
		);

	} else {

		/* translators: %s: number of reviews */
		$meta = sprintf( _n( '%s review', '%s reviews', $count, 'if-reviews' ), number_format_i18n( $count ) );
	}
	?>

	<div class="if-reviews-header">

		<?php if ( '' !== $icon ) : ?>
			<span class="if-header-icon"><?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitised with wp_kses in if_reviews_source_icon_html(). ?></span>
		<?php endif; ?>

		<div class="if-header-body">
			<p class="if-reviews-title"><?php echo esc_html( $heading ); ?></p>
			<div class="if-reviews-summary">
				<span class="if-reviews-score"><?php echo esc_html( number_format_i18n( $average, 1 ) ); ?></span>
				<?php if_reviews_render_stars( $average, 'if-stars', 1 ); ?>
			</div>
			<p class="if-reviews-meta"><?php echo esc_html( $meta ); ?></p>
		</div>

	</div>

	<?php
}

/**
 * Star row with a fractional fill (4.8 shows 96% of the stars coloured).
 *
 * @param float  $value    0..5.
 * @param string $class    CSS class(es) for the wrapper.
 * @param int    $decimals Decimals in the accessible label.
 */
function if_reviews_render_stars( $value, $class = 'if-stars', $decimals = 1 ) {

	$value   = max( 0, min( 5, (float) $value ) );
	$percent = round( $value / 5 * 100, 1 );

	/* translators: %s: rating, e.g. 4.8 */
	$label = sprintf( __( 'Rated %s out of 5', 'if-reviews' ), number_format_i18n( $value, $decimals ) );
	?>
	<span class="<?php echo esc_attr( $class ); ?>" role="img" aria-label="<?php echo esc_attr( $label ); ?>"><span class="if-stars-base" aria-hidden="true">★★★★★</span><span class="if-stars-fill" aria-hidden="true" style="width:<?php echo esc_attr( $percent ); ?>%">★★★★★</span></span>
	<?php
}

/**
 * One review card.
 *
 * @param array $review  Review array.
 * @param array $options Plugin options.
 * @param bool  $hidden  Render with the `hidden` attribute (revealed by "Show more").
 */
function if_reviews_render_card( $review, $options, $hidden = false ) {

	$source = $review['source'];
	$badge  = if_reviews_source_has_badge( $source );
	$date   = if_reviews_format_date( $review['date'] );
	?>

	<article class="if-review-card"<?php echo $hidden ? ' hidden' : ''; ?>>

		<div class="if-card-top">

			<div class="if-avatar" aria-hidden="true"><?php echo esc_html( $review['initials'] ); ?></div>

			<div class="if-card-meta">

				<p class="if-reviewer-name"><?php echo esc_html( $review['name'] ); ?></p>

				<p class="if-review-date">
					<?php if ( '' !== $date ) : ?>
						<span class="if-review-date-text"><?php echo esc_html( $date ); ?></span>
					<?php endif; ?>

					<?php if ( $badge ) : ?>
						<span class="if-source-badge if-source-<?php echo esc_attr( $source['key'] ); ?>">
							<span class="if-sr-only"><?php esc_html_e( 'Source:', 'if-reviews' ); ?></span>
							<?php
							if ( $options['show_source_icons'] ) {
								echo if_reviews_source_icon_html( $source ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitised with wp_kses.
							}
							?>
							<span class="if-source-label"><?php echo esc_html( $source['label'] ); ?></span>
						</span>
					<?php endif; ?>
				</p>

			</div>

		</div>

		<div class="if-card-stars">
			<?php if_reviews_render_stars( $review['rating'], 'if-stars if-stars-small', 0 ); ?>
		</div>

		<p class="if-review-text"><?php echo esc_html( $review['text'] ); ?></p>

	</article>

	<?php
}
