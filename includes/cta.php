<?php
/**
 * "Leave us a review" buttons: one per platform the site owner links,
 * shown under the widget or anywhere via [if_reviews_buttons].
 *
 * @package IF_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The buttons to show: every configured platform with a link.
 *
 * @param array|null $options Plugin options (loaded if omitted).
 * @return array[] Each: key, label, url, text, icon.
 */
function if_reviews_get_cta_buttons( $options = null ) {

	if ( null === $options ) {
		$options = if_reviews_get_options();
	}

	$buttons = array();
	$links   = isset( $options['cta_links'] ) && is_array( $options['cta_links'] ) ? $options['cta_links'] : array();
	$sources = if_reviews_get_sources();

	// Keep the registry's order (Google first), not the order they were saved.
	foreach ( $sources as $key => $source ) {

		if ( 'direct' === $key || empty( $links[ $key ]['url'] ) ) {
			continue;
		}

		$text = isset( $links[ $key ]['text'] ) ? trim( $links[ $key ]['text'] ) : '';

		if ( '' === $text ) {
			$text = ( 'other' === $key )
				? __( 'Leave a review', 'if-reviews' )
				/* translators: %s: platform name, e.g. Google */
				: sprintf( __( 'Review us on %s', 'if-reviews' ), $source['label'] );
		}

		$buttons[] = array(
			'key'   => $key,
			'label' => $source['label'],
			'url'   => $links[ $key ]['url'],
			'text'  => $text,
			'icon'  => isset( $source['icon'] ) ? $source['icon'] : '',
		);
	}

	/**
	 * Filter the "Leave us a review" buttons before rendering.
	 *
	 * @param array[] $buttons Buttons (key, label, url, text, icon).
	 * @param array   $options Plugin options.
	 */
	return apply_filters( 'if_reviews_cta_buttons', $buttons, $options );
}

/**
 * Where to find each platform's direct "write a review" link. Shown as a
 * small disclosure under the link field in Display Settings.
 *
 * @param string $key Source key.
 * @return string
 */
function if_reviews_cta_help( $key ) {

	$help = array(
		'google'        => __( 'Sign in to Google Business Profile (business.google.com), or search for your business on Google while signed in, and choose "Ask for reviews" (sometimes "Get more reviews"). Copy the link it shows. It looks like https://g.page/r/XXXXXXXX/review and opens the review form directly.', 'if-reviews' ),
		'facebook'      => __( 'Open your Facebook Page and click its Reviews tab (on some Pages it is called Recommendations). Copy the address from the browser bar. It looks like https://www.facebook.com/YourPageName/reviews. If the tab is missing, enable reviews in the Page settings under Templates and tabs.', 'if-reviews' ),
		'trustpilot'    => __( 'Use https://www.trustpilot.com/evaluate/yourdomain.com, replacing yourdomain.com with the domain shown on your Trustpilot profile. That address opens the review form directly.', 'if-reviews' ),
		'yelp'          => __( 'Open your business page on Yelp, click "Write a review", and copy the address from the browser bar. It looks like https://www.yelp.com/writeareview/biz/XXXXXXXX.', 'if-reviews' ),
		'productreview' => __( 'Open your listing on ProductReview.com.au, click "Write a review", and copy the address from the browser bar.', 'if-reviews' ),
		'other'         => __( 'Paste the address of the page where a customer writes a review on that platform, and give the button its own text.', 'if-reviews' ),
	);

	$text = isset( $help[ $key ] ) ? $help[ $key ] : __( 'Paste the address of the page where a customer writes a review on this platform.', 'if-reviews' );

	/**
	 * Filter the help text for a platform's review link.
	 *
	 * @param string $text Help text.
	 * @param string $key  Source key.
	 */
	return apply_filters( 'if_reviews_cta_help', $text, $key );
}

/**
 * Print the buttons block. Does nothing when no platform has a link.
 *
 * @param array  $options Plugin options.
 * @param string $heading Heading override; '' uses the setting or the default.
 */
function if_reviews_render_cta( $options, $heading = '' ) {

	$buttons = if_reviews_get_cta_buttons( $options );

	if ( empty( $buttons ) ) {
		return;
	}

	if ( '' === $heading ) {
		$heading = ( '' !== trim( (string) $options['cta_heading'] ) )
			? $options['cta_heading']
			: __( 'Had a good experience? Leave us a review.', 'if-reviews' );
	}

	$new_tab = ! empty( $options['cta_new_tab'] );
	?>

	<div class="if-review-cta">

		<?php if ( '-' !== $heading ) : ?>
			<p class="if-cta-heading"><?php echo esc_html( $heading ); ?></p>
		<?php endif; ?>

		<div class="if-cta-buttons">
			<?php foreach ( $buttons as $button ) : ?>
				<a
					class="if-cta-button if-cta-<?php echo esc_attr( $button['key'] ); ?>"
					href="<?php echo esc_url( $button['url'] ); ?>"
					<?php echo $new_tab ? 'target="_blank" rel="noopener"' : ''; ?>
				>
					<?php
					if ( ! empty( $options['show_source_icons'] ) && '' !== $button['icon'] ) {
						echo wp_kses( $button['icon'], if_reviews_svg_kses() );
					}
					?>
					<span><?php echo esc_html( $button['text'] ); ?></span>
					<?php if ( $new_tab ) : ?>
						<span class="if-sr-only"> <?php esc_html_e( '(opens in a new tab)', 'if-reviews' ); ?></span>
					<?php endif; ?>
				</a>
			<?php endforeach; ?>
		</div>

	</div>

	<?php
}

/**
 * [if_reviews_buttons heading="..."] : the buttons on their own, for a
 * footer, a thank-you page or an email signature page. Shows whenever at
 * least one platform has a link, independent of the widget toggle.
 * heading="-" hides the heading.
 *
 * @param array|string $atts Shortcode attributes.
 * @return string
 */
function if_reviews_buttons_shortcode( $atts = array() ) {

	$atts = shortcode_atts(
		array(
			'heading' => '',
		),
		$atts,
		'if_reviews_buttons'
	);

	$options = if_reviews_get_options();

	if ( empty( if_reviews_get_cta_buttons( $options ) ) ) {
		return '';
	}

	if_reviews_enqueue_assets();

	ob_start();
	echo '<div class="if-reviews-wrap if-buttons-only">';
	if_reviews_render_cta( $options, sanitize_text_field( $atts['heading'] ) );
	echo '</div>';

	return ob_get_clean();
}
add_shortcode( 'if_reviews_buttons', 'if_reviews_buttons_shortcode' );
