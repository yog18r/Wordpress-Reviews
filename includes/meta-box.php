<?php
/**
 * The "Review Details" meta box and its save handler.
 *
 * @package IF_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the meta box on the Review editor.
 */
function if_add_review_meta_boxes() {

	add_meta_box(
		'if_review_details',
		__( 'Review Details', 'if-reviews' ),
		'if_review_details_callback',
		'if_review',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'if_add_review_meta_boxes' );

/**
 * Render the fields.
 *
 * @param WP_Post $post The review being edited.
 */
function if_review_details_callback( $post ) {

	wp_nonce_field( 'if_save_review_details', 'if_review_details_nonce' );

	$reviewer_name = get_post_meta( $post->ID, '_if_reviewer_name', true );
	$review_date   = get_post_meta( $post->ID, '_if_review_date', true );
	$rating        = (int) get_post_meta( $post->ID, '_if_rating', true );
	$review_text   = get_post_meta( $post->ID, '_if_review_text', true );
	$display       = get_post_meta( $post->ID, '_if_display_on_website', true );
	$source_key    = get_post_meta( $post->ID, '_if_source', true );
	$source_label  = get_post_meta( $post->ID, '_if_source_label', true );
	$review_url    = get_post_meta( $post->ID, '_if_review_url', true );
	$options       = if_reviews_get_options();

	// Reviews created before these fields existed keep sensible values.
	if ( '' === $display ) {
		$display = '1';
	}
	if ( '' === $source_key ) {
		$source_key = 'direct';
	}
	if ( $rating < 1 || $rating > 5 ) {
		$rating = 5;
	}

	$sources = if_reviews_get_sources();
	?>

	<div class="if-review-fields" style="max-width: 800px;">

		<?php if ( if_reviews_is_auto_hidden( $rating, $options ) ) : ?>
			<div class="notice notice-warning inline" style="margin: 0 0 16px;">
				<p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: minimum star rating from settings */
							__( 'This review is hidden from the website automatically because Display Settings hides reviews rated below %d stars. It still counts toward the overall rating.', 'if-reviews' ),
							(int) $options['min_rating']
						)
					);
					?>
				</p>
			</div>
		<?php endif; ?>

		<div class="if-field">
			<label for="if_reviewer_name">
				<strong><?php esc_html_e( 'Reviewer name', 'if-reviews' ); ?></strong>
			</label>
			<input
				type="text"
				id="if_reviewer_name"
				name="if_reviewer_name"
				value="<?php echo esc_attr( $reviewer_name ); ?>"
				placeholder="<?php esc_attr_e( 'e.g. Alex T', 'if-reviews' ); ?>"
				style="width: 100%; max-width: 500px;"
			>
			<p class="description">
				<?php esc_html_e( 'Shown on the card exactly as typed. Avatar initials are generated from it automatically. Use the same form the reviewer used publicly, e.g. first name and an initial.', 'if-reviews' ); ?>
			</p>
		</div>

		<div class="if-field">
			<label for="if_review_date">
				<strong><?php esc_html_e( 'Review date', 'if-reviews' ); ?></strong>
			</label>
			<input
				type="date"
				id="if_review_date"
				name="if_review_date"
				value="<?php echo esc_attr( $review_date ); ?>"
			>
			<p class="description">
				<?php esc_html_e( 'Displayed as month and year, e.g. "August 2026".', 'if-reviews' ); ?>
			</p>
		</div>

		<div class="if-field">
			<label for="if_rating">
				<strong><?php esc_html_e( 'Rating', 'if-reviews' ); ?></strong>
			</label>
			<select id="if_rating" name="if_rating">
				<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
					<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $rating, $i ); ?>>
						<?php
						/* translators: %d: star rating 1-5 */
						echo esc_html( sprintf( _n( '%d star', '%d stars', $i, 'if-reviews' ), $i ) );
						?>
					</option>
				<?php endfor; ?>
			</select>
		</div>

		<div class="if-field">
			<label for="if_source">
				<strong><?php esc_html_e( 'Source', 'if-reviews' ); ?></strong>
			</label>
			<select id="if_source" name="if_source">
				<?php foreach ( $sources as $key => $source ) : ?>
					<option
						value="<?php echo esc_attr( $key ); ?>"
						data-description="<?php echo esc_attr( isset( $source['description'] ) ? $source['description'] : '' ); ?>"
						<?php selected( $source_key, $key ); ?>
					>
						<?php echo esc_html( $source['label'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<p class="description" id="if_source_description">
				<?php
				$selected = isset( $sources[ $source_key ]['description'] ) ? $sources[ $source_key ]['description'] : '';
				echo esc_html( $selected );
				?>
			</p>
		</div>

		<div class="if-field" id="if_source_label_field" <?php echo ( 'other' === $source_key ) ? '' : 'hidden'; ?>>
			<label for="if_source_label">
				<strong><?php esc_html_e( 'Platform name', 'if-reviews' ); ?></strong>
			</label>
			<input
				type="text"
				id="if_source_label"
				name="if_source_label"
				value="<?php echo esc_attr( $source_label ); ?>"
				placeholder="<?php esc_attr_e( 'e.g. Tripadvisor', 'if-reviews' ); ?>"
				style="width: 100%; max-width: 300px;"
			>
			<p class="description">
				<?php esc_html_e( 'Shown on the badge when Source is "Other".', 'if-reviews' ); ?>
			</p>
		</div>

		<div class="if-field">
			<label for="if_review_url">
				<strong><?php esc_html_e( 'Link to the original review (optional)', 'if-reviews' ); ?></strong>
			</label>
			<input
				type="url"
				id="if_review_url"
				name="if_review_url"
				value="<?php echo esc_attr( $review_url ); ?>"
				placeholder="https://"
				style="width: 100%; max-width: 600px;"
			>
			<p class="description">
				<?php esc_html_e( 'When set, the card shows a "View on Google" style link so visitors can check the original. On Google, open the review, click the three-dot menu and choose "Share review" to get its link.', 'if-reviews' ); ?>
			</p>
		</div>

		<div class="if-field">
			<label for="if_review_text">
				<strong><?php esc_html_e( 'Review text', 'if-reviews' ); ?></strong>
			</label>
			<textarea
				id="if_review_text"
				name="if_review_text"
				rows="8"
				placeholder="<?php esc_attr_e( 'Paste the review text here, exactly as the reviewer wrote it.', 'if-reviews' ); ?>"
				style="width: 100%;"
			><?php echo esc_textarea( $review_text ); ?></textarea>
		</div>

		<hr style="margin: 25px 0;">

		<div class="if-field">
			<label for="if_display_on_website" style="display:flex;align-items:center;gap:8px;">
				<input
					type="checkbox"
					id="if_display_on_website"
					name="if_display_on_website"
					value="1"
					<?php checked( $display, '1' ); ?>
				>
				<strong style="margin:0;"><?php esc_html_e( 'Display this review on the website', 'if-reviews' ); ?></strong>
			</label>
			<p class="description" style="margin-left:24px;">
				<?php esc_html_e( 'Untick to hide this review from the cards. It stays saved here and still counts toward the overall rating and review count shown in the header, so the summary remains truthful.', 'if-reviews' ); ?>
			</p>
		</div>

	</div>

	<?php
}

/**
 * Small amount of JS for the meta box: show the right source description
 * and reveal the platform-name field for "Other".
 *
 * @param string $hook Current admin page hook.
 */
function if_reviews_meta_box_script( $hook ) {

	if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
		return;
	}

	$screen = get_current_screen();

	if ( ! $screen || 'if_review' !== $screen->post_type ) {
		return;
	}

	$js = <<<'JS'
(function () {
	var select = document.getElementById('if_source');
	var description = document.getElementById('if_source_description');
	var labelField = document.getElementById('if_source_label_field');
	if (!select) { return; }
	function sync() {
		var option = select.options[select.selectedIndex];
		if (description) { description.textContent = option ? (option.getAttribute('data-description') || '') : ''; }
		if (labelField) { labelField.hidden = (select.value !== 'other'); }
	}
	select.addEventListener('change', sync);
	sync();
})();
JS;

	wp_add_inline_script( 'post', $js );
}
add_action( 'admin_enqueue_scripts', 'if_reviews_meta_box_script' );


/* ------------------------------------------------------------------
 * Save
 * ---------------------------------------------------------------- */

/**
 * Save the meta box fields.
 *
 * @param int $post_id Review post ID.
 */
function if_save_review_details( $post_id ) {

	if ( ! isset( $_POST['if_review_details_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['if_review_details_nonce'] ) );

	if ( ! wp_verify_nonce( $nonce, 'if_save_review_details' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( 'if_review' !== get_post_type( $post_id ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	/* Reviewer name ------------------------------------------------ */

	$reviewer_name = '';

	if ( isset( $_POST['if_reviewer_name'] ) ) {
		$reviewer_name = sanitize_text_field( wp_unslash( $_POST['if_reviewer_name'] ) );
		update_post_meta( $post_id, '_if_reviewer_name', $reviewer_name );
	}

	/* Review date (Y-m-d or empty) --------------------------------- */

	if ( isset( $_POST['if_review_date'] ) ) {

		$review_date = sanitize_text_field( wp_unslash( $_POST['if_review_date'] ) );

		if ( '' !== $review_date && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $review_date ) ) {
			$review_date = '';
		}

		update_post_meta( $post_id, '_if_review_date', $review_date );
	}

	/* Rating 1-5 --------------------------------------------------- */

	if ( isset( $_POST['if_rating'] ) ) {
		$rating = max( 1, min( 5, (int) $_POST['if_rating'] ) );
		update_post_meta( $post_id, '_if_rating', $rating );
	}

	/* Source ------------------------------------------------------- */

	if ( isset( $_POST['if_source'] ) ) {

		$source = sanitize_key( wp_unslash( $_POST['if_source'] ) );

		if ( ! array_key_exists( $source, if_reviews_get_sources() ) ) {
			$source = 'direct';
		}

		update_post_meta( $post_id, '_if_source', $source );
	}

	if ( isset( $_POST['if_source_label'] ) ) {
		update_post_meta( $post_id, '_if_source_label', sanitize_text_field( wp_unslash( $_POST['if_source_label'] ) ) );
	}

	/* Link to the original review ---------------------------------- */

	if ( isset( $_POST['if_review_url'] ) ) {
		update_post_meta( $post_id, '_if_review_url', if_reviews_sanitize_url( wp_unslash( $_POST['if_review_url'] ) ) );
	}

	/* Review text -------------------------------------------------- */

	if ( isset( $_POST['if_review_text'] ) ) {
		update_post_meta( $post_id, '_if_review_text', sanitize_textarea_field( wp_unslash( $_POST['if_review_text'] ) ) );
	}

	/* Display on website ------------------------------------------- */

	update_post_meta( $post_id, '_if_display_on_website', isset( $_POST['if_display_on_website'] ) ? '1' : '0' );

	/* Convenience: use the reviewer name as the title when none given */

	$post = get_post( $post_id );

	if ( $post && '' === trim( $post->post_title ) && '' !== $reviewer_name ) {

		remove_action( 'save_post_if_review', 'if_save_review_details' );

		wp_update_post(
			array(
				'ID'         => $post_id,
				'post_title' => $reviewer_name,
			)
		);

		add_action( 'save_post_if_review', 'if_save_review_details' );
	}
}
add_action( 'save_post_if_review', 'if_save_review_details' );
