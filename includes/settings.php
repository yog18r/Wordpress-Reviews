<?php
/**
 * Reviews → Display Settings screen (Settings API).
 *
 * @package IF_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add "Display Settings" under the Reviews menu.
 */
function if_reviews_add_settings_page() {

	add_submenu_page(
		'edit.php?post_type=if_review',
		__( 'Reviews Display Settings', 'if-reviews' ),
		__( 'Display Settings', 'if-reviews' ),
		'manage_options',
		'if-reviews-settings',
		'if_reviews_render_settings_page'
	);
}
add_action( 'admin_menu', 'if_reviews_add_settings_page' );

/**
 * Register the option, sections and fields.
 */
function if_reviews_register_settings() {

	register_setting(
		'if_reviews_settings_group',
		IF_REVIEWS_OPTION_KEY,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'if_reviews_sanitize_options',
			'default'           => if_reviews_default_options(),
		)
	);

	/* Display ------------------------------------------------------ */

	add_settings_section(
		'if_reviews_display_section',
		__( 'Display', 'if-reviews' ),
		function () {
			echo '<p>' . esc_html__( 'Defaults for the [if_reviews] shortcode and the Reviews block. Most can be changed for a single shortcode or block; see the shortcode reference below.', 'if-reviews' ) . '</p>';
		},
		'if-reviews-settings'
	);

	$display_fields = array(
		'show_header'       => array(
			'type'        => 'checkbox',
			'label'       => __( 'Summary header', 'if-reviews' ),
			'text'        => __( 'Show the overall rating, review count and sources above the cards', 'if-reviews' ),
			'description' => __( 'The header counts every published review, including hidden ones.', 'if-reviews' ),
		),
		'header_title'      => array(
			'type'        => 'text',
			'label'       => __( 'Header title', 'if-reviews' ),
			'placeholder' => __( 'Leave blank for automatic', 'if-reviews' ),
			'description' => __( 'Blank means automatic: "Google Reviews" when every review came from Google, "Reviews" otherwise. Set your own, e.g. "What our clients say".', 'if-reviews' ),
		),
		'show_source_icons' => array(
			'type'        => 'checkbox',
			'label'       => __( 'Source icons', 'if-reviews' ),
			'text'        => __( 'Show a small icon next to the source name on badges and in the header', 'if-reviews' ),
			'description' => __( 'The source name is always shown as text; this only toggles the icon.', 'if-reviews' ),
		),
		'layout'            => array(
			'type'        => 'select',
			'label'       => __( 'Layout', 'if-reviews' ),
			'choices'     => if_reviews_get_layouts(),
			'description' => __( 'Grid for a handful of reviews, list for long text, carousel when you have many.', 'if-reviews' ),
		),
		'columns'           => array(
			'type'        => 'number',
			'label'       => __( 'Grid columns', 'if-reviews' ),
			'min'         => 1,
			'max'         => 4,
			'description' => __( 'Maximum columns on wide screens (grid layout only). Collapses automatically on smaller screens.', 'if-reviews' ),
		),
		'limit'             => array(
			'type'        => 'number',
			'label'       => __( 'Reviews to show', 'if-reviews' ),
			'min'         => 0,
			'max'         => 500,
			'description' => __( '0 shows every visible review, loaded 24 at a time (a "Show more" button in grids and lists; the carousel loads more as visitors scroll). With a limit, grids and lists show that many and add a "Show more" button for the rest; the carousel stops at the limit.', 'if-reviews' ),
		),
		'show_more'         => array(
			'type'        => 'checkbox',
			'label'       => __( '"Show more" button', 'if-reviews' ),
			'text'        => __( 'Let visitors reveal the remaining reviews when a limit is set', 'if-reviews' ),
			'description' => __( 'Untick to cut off strictly at the limit.', 'if-reviews' ),
		),
		'order'             => array(
			'type'    => 'select',
			'label'   => __( 'Order', 'if-reviews' ),
			'choices' => if_reviews_get_orders(),
		),
		'carousel_autoplay' => array(
			'type'        => 'number',
			'label'       => __( 'Carousel autoplay (seconds)', 'if-reviews' ),
			'min'         => 0,
			'max'         => 60,
			'description' => __( '0 turns autoplay off. Otherwise the carousel moves on one card every this many seconds. It pauses on hover or focus, has a pause button, and never runs for visitors who prefer reduced motion.', 'if-reviews' ),
		),
		'excerpt_length'    => array(
			'type'        => 'number',
			'label'       => __( '"Read more" after (characters)', 'if-reviews' ),
			'min'         => 0,
			'max'         => 5000,
			'description' => __( '0 shows the full text. Otherwise longer reviews are cut at the nearest word, with a "Read more" button. Minimum 40; try 280.', 'if-reviews' ),
		),
		'show_import_label' => array(
			'type'        => 'checkbox',
			'label'       => __( 'Imported reviews', 'if-reviews' ),
			'text'        => __( 'Show "Imported from Google" on reviews synced from your Google Business Profile', 'if-reviews' ),
			'description' => __( 'A plain statement of where the review came from, for reviews brought in by the Google import. Off by default.', 'if-reviews' ),
		),
		'show_review_links' => array(
			'type'        => 'checkbox',
			'label'       => __( 'Review links', 'if-reviews' ),
			'text'        => __( 'Show a "View on Google" style link on reviews that have a URL', 'if-reviews' ),
			'description' => __( 'Set the URL on each review. Links open in a new tab and let visitors check the original.', 'if-reviews' ),
		),
		'min_rating'        => array(
			'type'        => 'select',
			'label'       => __( 'Automatically hide low ratings', 'if-reviews' ),
			'choices'     => array(
				0 => __( 'Off: show every review you have not hidden yourself', 'if-reviews' ),
				2 => __( 'Hide reviews rated below 2 stars', 'if-reviews' ),
				3 => __( 'Hide reviews rated below 3 stars', 'if-reviews' ),
				4 => __( 'Hide reviews rated below 4 stars', 'if-reviews' ),
				5 => __( 'Hide reviews rated below 5 stars', 'if-reviews' ),
			),
			'description' => __( 'Reviews hidden this way still count toward the rating and review count in the header, exactly like reviews you hide by hand.', 'if-reviews' ),
		),
	);

	foreach ( $display_fields as $key => $field ) {
		$field['key'] = $key;
		add_settings_field( $key, $field['label'], 'if_reviews_render_field', 'if-reviews-settings', 'if_reviews_display_section', $field );
	}

	/* "Leave us a review" buttons ---------------------------------- */

	add_settings_section(
		'if_reviews_cta_section',
		__( '"Leave us a review" buttons', 'if-reviews' ),
		function () {
			echo '<p>' . esc_html__( 'Optional buttons under the reviews that take visitors straight to your review page on each platform. Off by default. Add a link for each platform you want a button for. To place the buttons elsewhere, use the [if_reviews_buttons] shortcode.', 'if-reviews' ) . '</p>';
		},
		'if-reviews-settings'
	);

	$cta_fields = array(
		'cta_enabled' => array(
			'type'        => 'checkbox',
			'label'       => __( 'Show buttons', 'if-reviews' ),
			'text'        => __( 'Show the buttons under the reviews', 'if-reviews' ),
			'description' => __( 'Only platforms with a link below get a button. To override for one shortcode, add buttons="yes" or buttons="no"; in the block, use the "Leave us a review" buttons option.', 'if-reviews' ),
		),
		'cta_heading' => array(
			'type'        => 'text',
			'label'       => __( 'Heading above the buttons', 'if-reviews' ),
			'placeholder' => __( 'Had a good experience? Leave us a review.', 'if-reviews' ),
			'description' => __( 'Leave blank to use the text shown in grey. Enter a single hyphen (-) for no heading.', 'if-reviews' ),
		),
		'cta_new_tab' => array(
			'type'  => 'checkbox',
			'label' => __( 'Open in a new tab', 'if-reviews' ),
			'text'  => __( 'Open the review page in a new browser tab so visitors keep your site open', 'if-reviews' ),
		),
		'cta_links'   => array(
			'type'  => 'cta_links',
			'label' => __( 'Platforms', 'if-reviews' ),
		),
	);

	foreach ( $cta_fields as $key => $field ) {
		$field['key'] = $key;
		add_settings_field( $key, $field['label'], 'if_reviews_render_field', 'if-reviews-settings', 'if_reviews_cta_section', $field );
	}

	/* Appearance --------------------------------------------------- */

	add_settings_section(
		'if_reviews_appearance_section',
		__( 'Appearance', 'if-reviews' ),
		function () {
			echo '<p>' . esc_html__( 'Colours and shape of the reviews display. Changes apply site-wide when you save; the preview shows them as you go.', 'if-reviews' ) . '</p>';
			if_reviews_render_style_preview();
		},
		'if-reviews-settings'
	);

	$options = if_reviews_get_options();
	$choices = if_reviews_style_choices();

	$style_fields = array(
		'color_scheme' => array(
			'type'        => 'select',
			'label'       => __( 'Colour scheme', 'if-reviews' ),
			'choices'     => $choices['color_scheme'],
			'description' => __( 'Dark suits dark themes and dark page sections; it uses its own card colours and keeps your star and accent colours. "Match the visitor\'s device" follows their light or dark setting, so only use it if your theme does the same.', 'if-reviews' ),
		),
		'card_style'   => array(
			'type'    => 'select',
			'label'   => __( 'Card style', 'if-reviews' ),
			'choices' => $choices['card_style'],
		),
		'hover_lift'   => array(
			'type' => 'checkbox',
			'label' => __( 'Hover effect', 'if-reviews' ),
			'text'  => __( 'Lift cards slightly when the mouse is over them', 'if-reviews' ),
		),
		'density'      => array(
			'type'        => 'select',
			'label'       => __( 'Spacing', 'if-reviews' ),
			'choices'     => $choices['density'],
			'description' => __( 'Compact suits sidebars and footers.', 'if-reviews' ),
		),
		'text_size'    => array(
			'type'    => 'select',
			'label'   => __( 'Text size', 'if-reviews' ),
			'choices' => $choices['text_size'],
		),
	);

	foreach ( $style_fields as $key => $field ) {
		$field['key'] = $key;
		add_settings_field( $key, $field['label'], 'if_reviews_render_field', 'if-reviews-settings', 'if_reviews_appearance_section', $field );
	}

	$star_contrast = if_reviews_contrast_ratio( '' !== $options['star_color'] ? $options['star_color'] : $options['accent_color'], $options['card_bg_color'] );

	add_settings_field(
		'star_color',
		__( 'Star colour', 'if-reviews' ),
		'if_reviews_render_field',
		'if-reviews-settings',
		'if_reviews_appearance_section',
		array(
			'key'         => 'star_color',
			'type'        => 'color',
			'description' => __( 'Blank uses the accent colour. Gold stars with a brand-coloured accent is a common combination.', 'if-reviews' )
				. ( $star_contrast < 3
					/* translators: %s: contrast ratio, e.g. 2.1 */
					? ' ' . sprintf( __( 'Note: the stars contrast with the card background at only %s:1; 3:1 or more is easier to see.', 'if-reviews' ), number_format_i18n( $star_contrast, 1 ) )
					: '' ),
		)
	);

	$color_fields = array(
		'accent_color'  => array( __( 'Accent colour', 'if-reviews' ), __( 'Used for the stars (unless a star colour is set), highlights and the current page number.', 'if-reviews' ) ),
		'card_bg_color' => array( __( 'Card background colour', 'if-reviews' ), '' ),
		'border_color'  => array( __( 'Card border colour', 'if-reviews' ), '' ),
		'text_color'    => array( __( 'Review text colour', 'if-reviews' ), '' ),
		'meta_color'    => array( __( 'Meta text colour', 'if-reviews' ), __( 'Used for the review count line, dates and source badges.', 'if-reviews' ) ),
	);

	foreach ( $color_fields as $key => $field ) {
		add_settings_field(
			$key,
			$field[0],
			'if_reviews_render_field',
			'if-reviews-settings',
			'if_reviews_appearance_section',
			array(
				'key'         => $key,
				'type'        => 'color',
				'description' => $field[1],
			)
		);
	}

	add_settings_field(
		'border_radius',
		__( 'Card corner radius (px)', 'if-reviews' ),
		'if_reviews_render_field',
		'if-reviews-settings',
		'if_reviews_appearance_section',
		array(
			'key'         => 'border_radius',
			'type'        => 'number',
			'min'         => 0,
			'max'         => 60,
			'description' => __( '0 for square corners; 14 is the default.', 'if-reviews' ),
		)
	);

	add_settings_field(
		'avatar_colors',
		__( 'Avatar colours', 'if-reviews' ),
		'if_reviews_render_field',
		'if-reviews-settings',
		'if_reviews_appearance_section',
		array(
			'key'  => 'avatar_colors',
			'type' => 'avatar',
		)
	);

	/* Data --------------------------------------------------------- */

	add_settings_section(
		'if_reviews_data_section',
		__( 'Data', 'if-reviews' ),
		function () {
			$url = admin_url( 'edit.php?post_type=if_review&page=if-reviews-import-export' );
			echo '<p>' . wp_kses_post(
				sprintf(
					/* translators: %s: link to the Import / Export screen */
					__( 'To add many reviews at once or take a backup, use <a href="%s">Import / Export</a>.', 'if-reviews' ),
					esc_url( $url )
				)
			) . '</p>';
		},
		'if-reviews-settings'
	);

	add_settings_field(
		'delete_on_uninstall',
		__( 'On uninstall', 'if-reviews' ),
		'if_reviews_render_field',
		'if-reviews-settings',
		'if_reviews_data_section',
		array(
			'key'         => 'delete_on_uninstall',
			'type'        => 'checkbox',
			'text'        => __( 'Delete all reviews when the plugin is deleted', 'if-reviews' ),
			'description' => __( 'Off by default so an accidental uninstall cannot destroy your reviews. Settings are always removed on uninstall.', 'if-reviews' ),
		)
	);
}
add_action( 'admin_init', 'if_reviews_register_settings' );

/**
 * Generic field renderer.
 *
 * @param array $args Field definition (key, type, and type-specific extras).
 */
function if_reviews_render_field( $args ) {

	$options  = if_reviews_get_options();
	$defaults = if_reviews_default_options();
	$key      = $args['key'];
	$value    = isset( $options[ $key ] ) ? $options[ $key ] : '';
	$name     = IF_REVIEWS_OPTION_KEY . '[' . $key . ']';
	$id       = 'if-reviews-' . str_replace( '_', '-', $key );

	switch ( $args['type'] ) {

		case 'checkbox':
			?>
			<label for="<?php echo esc_attr( $id ); ?>">
				<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( (int) $value, 1 ); ?>>
				<?php echo esc_html( isset( $args['text'] ) ? $args['text'] : '' ); ?>
			</label>
			<?php
			break;

		case 'text':
			?>
			<input type="text" class="regular-text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( isset( $args['placeholder'] ) ? $args['placeholder'] : '' ); ?>">
			<?php
			break;

		case 'number':
			?>
			<input type="number" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" min="<?php echo esc_attr( isset( $args['min'] ) ? $args['min'] : 0 ); ?>" max="<?php echo esc_attr( isset( $args['max'] ) ? $args['max'] : '' ); ?>" step="1" style="width: 90px;">
			<?php
			break;

		case 'select':
			?>
			<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>">
				<?php foreach ( $args['choices'] as $choice => $label ) : ?>
					<option value="<?php echo esc_attr( $choice ); ?>" <?php selected( $value, $choice ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php
			break;

		case 'color':
			?>
			<input type="text" class="if-reviews-color-picker" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" data-default-color="<?php echo esc_attr( $defaults[ $key ] ); ?>">
			<?php
			break;

		case 'cta_links':
			$links = is_array( $value ) ? $value : array();
			?>
			<table class="widefat striped if-cta-table" style="max-width: 900px;">
				<thead>
					<tr>
						<th style="width: 22%;"><?php esc_html_e( 'Platform', 'if-reviews' ); ?></th>
						<th style="width: 28%;"><?php esc_html_e( 'Button text', 'if-reviews' ); ?></th>
						<th><?php esc_html_e( 'Link to your review page', 'if-reviews' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( if_reviews_get_sources() as $source_key => $source ) : ?>
						<?php
						if ( 'direct' === $source_key ) {
							continue;
						}

						$row_text = isset( $links[ $source_key ]['text'] ) ? $links[ $source_key ]['text'] : '';
						$row_url  = isset( $links[ $source_key ]['url'] ) ? $links[ $source_key ]['url'] : '';
						$row_name = $name . '[' . $source_key . ']';
						$row_id   = $id . '-' . $source_key;
						$default  = ( 'other' === $source_key )
							? __( 'Leave a review', 'if-reviews' )
							/* translators: %s: platform name */
							: sprintf( __( 'Review us on %s', 'if-reviews' ), $source['label'] );
						?>
						<tr>
							<td>
								<label for="<?php echo esc_attr( $row_id ); ?>-url" style="display:inline-flex;align-items:center;gap:6px;font-weight:600;">
									<?php echo if_reviews_source_icon_html( if_reviews_get_source( $source_key ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitised with wp_kses. ?>
									<?php echo esc_html( $source['label'] ); ?>
								</label>
							</td>
							<td>
								<input type="text" class="widefat" id="<?php echo esc_attr( $row_id ); ?>-text" name="<?php echo esc_attr( $row_name ); ?>[text]" value="<?php echo esc_attr( $row_text ); ?>" placeholder="<?php echo esc_attr( $default ); ?>">
							</td>
							<td>
								<input type="url" class="widefat" id="<?php echo esc_attr( $row_id ); ?>-url" name="<?php echo esc_attr( $row_name ); ?>[url]" value="<?php echo esc_attr( $row_url ); ?>" placeholder="https://">
								<details class="if-help">
									<summary><?php esc_html_e( 'Where do I find this link?', 'if-reviews' ); ?></summary>
									<p><?php echo esc_html( if_reviews_cta_help( $source_key ) ); ?></p>
								</details>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p class="description">
				<?php esc_html_e( 'Leave the link blank for any platform you do not want a button for. Leave the button text blank to use the text shown in grey.', 'if-reviews' ); ?>
			</p>
			<?php
			break;

		case 'avatar':
			$colors    = if_reviews_get_avatar_colors( $options );
			$list_name = IF_REVIEWS_OPTION_KEY . '[avatar_colors_list][]';
			?>
			<div class="if-swatches" id="<?php echo esc_attr( $id ); ?>">
				<div class="if-swatch-list">
					<?php foreach ( $colors as $hex ) : ?>
						<div class="if-swatch-row">
							<input type="text" class="if-reviews-color-picker" name="<?php echo esc_attr( $list_name ); ?>" value="#<?php echo esc_attr( $hex ); ?>">
							<button type="button" class="button-link if-swatch-remove" aria-label="<?php esc_attr_e( 'Remove this colour', 'if-reviews' ); ?>"><?php esc_html_e( 'Remove', 'if-reviews' ); ?></button>
						</div>
					<?php endforeach; ?>
				</div>
				<template class="if-swatch-template">
					<div class="if-swatch-row">
						<input type="text" class="if-reviews-color-picker" name="<?php echo esc_attr( $list_name ); ?>" value="">
						<button type="button" class="button-link if-swatch-remove" aria-label="<?php esc_attr_e( 'Remove this colour', 'if-reviews' ); ?>"><?php esc_html_e( 'Remove', 'if-reviews' ); ?></button>
					</div>
				</template>
				<p style="margin:8px 0 0;">
					<button type="button" class="button if-swatch-add"><?php esc_html_e( 'Add colour', 'if-reviews' ); ?></button>
				</p>
			</div>
			<p class="description">
				<?php esc_html_e( 'Each reviewer gets a colour based on their name, so the same person always has the same colour. If two neighbouring avatars would match, one takes the next colour in the list. Avatar text is set light or dark automatically. Remove every colour to restore the defaults.', 'if-reviews' ); ?>
			</p>
			<?php
			break;
	}

	if ( ! empty( $args['description'] ) ) {
		echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
	}
}

/**
 * The settings page itself, plus a shortcode reference.
 */
function if_reviews_render_settings_page() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Reviews Display Settings', 'if-reviews' ); ?></h1>

		<form method="post" action="options.php" id="if-reviews-settings-form">
			<?php
			settings_fields( 'if_reviews_settings_group' );
			do_settings_sections( 'if-reviews-settings' );
			submit_button();
			?>
		</form>

		<hr>

		<h2><?php esc_html_e( 'Shortcode reference', 'if-reviews' ); ?></h2>
		<p>
			<?php esc_html_e( 'Place the shortcode in any page, post, widget or block. Attributes override the defaults above for that one instance.', 'if-reviews' ); ?>
		</p>
		<table class="widefat striped" style="max-width: 900px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Example', 'if-reviews' ); ?></th>
					<th><?php esc_html_e( 'What it does', 'if-reviews' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr><td><code>[if_reviews]</code></td><td><?php esc_html_e( 'All visible reviews with the defaults above.', 'if-reviews' ); ?></td></tr>
				<tr><td><code>[if_reviews layout="carousel"]</code></td><td><?php esc_html_e( 'Horizontal carousel with previous/next buttons. Also: grid, list.', 'if-reviews' ); ?></td></tr>
				<tr><td><code>[if_reviews columns="2"]</code></td><td><?php esc_html_e( 'Grid with up to two columns (1-4).', 'if-reviews' ); ?></td></tr>
				<tr><td><code>[if_reviews limit="6"]</code></td><td><?php esc_html_e( 'Show six, with a "Show more" button for the rest.', 'if-reviews' ); ?></td></tr>
				<tr><td><code>[if_reviews limit="6" show_more="no"]</code></td><td><?php esc_html_e( 'Show six and stop.', 'if-reviews' ); ?></td></tr>
				<tr><td><code>[if_reviews source="google"]</code></td><td><?php esc_html_e( 'Only reviews from one source. The header then describes that source alone. Comma-separate several: "google,facebook".', 'if-reviews' ); ?></td></tr>
				<tr><td><code>[if_reviews order="highest"]</code></td><td><?php esc_html_e( 'Sort order: newest, oldest, highest, random.', 'if-reviews' ); ?></td></tr>
				<tr><td><code>[if_reviews show_header="no"]</code></td><td><?php esc_html_e( 'Cards only, no summary header.', 'if-reviews' ); ?></td></tr>
				<tr><td><code>[if_reviews title="What our clients say"]</code></td><td><?php esc_html_e( 'Custom header title for this instance.', 'if-reviews' ); ?></td></tr>
				<tr><td><code>[if_reviews category="mortgages"]</code></td><td><?php esc_html_e( 'Only reviews in one category (by slug; comma-separate several). The header then describes that subset.', 'if-reviews' ); ?></td></tr>
				<tr><td><code>[if_reviews paginate="12"]</code></td><td><?php esc_html_e( 'Numbered pages of twelve instead of a "Show more" button. Use one paginated shortcode per page.', 'if-reviews' ); ?></td></tr>
				<tr><td><code>[if_reviews layout="carousel" autoplay="6"]</code></td><td><?php esc_html_e( 'Carousel that moves on every six seconds (0 to disable).', 'if-reviews' ); ?></td></tr>
				<tr><td><code>[if_reviews scheme="dark"]</code></td><td><?php esc_html_e( 'Colour scheme for this instance: light, dark or auto (match the visitor\'s device).', 'if-reviews' ); ?></td></tr>
				<tr><td><code>[if_reviews card_style="outlined"]</code></td><td><?php esc_html_e( 'Card style for this instance: shadow, outlined, flat or minimal.', 'if-reviews' ); ?></td></tr>
				<tr><td><code>[if_reviews density="compact"]</code></td><td><?php esc_html_e( 'Spacing for this instance: compact, comfortable or spacious.', 'if-reviews' ); ?></td></tr>
				<tr><td><code>[if_review id="42"]</code></td><td><?php esc_html_e( 'One review on its own as a featured quote. The ID is in the address bar when editing the review.', 'if-reviews' ); ?></td></tr>
				<tr><td><code>[if_reviews_rating]</code></td><td><?php esc_html_e( 'Inline badge: "★★★★★ 4.8 from 120 reviews". Accepts source and category; stars="no" or count="no" trims it.', 'if-reviews' ); ?></td></tr>
				<tr><td><code>[if_reviews buttons="yes"]</code></td><td><?php esc_html_e( 'Force the "Leave us a review" buttons on (or "no" to hide them) for this instance.', 'if-reviews' ); ?></td></tr>
				<tr><td><code>[if_reviews_buttons]</code></td><td><?php esc_html_e( 'The "Leave us a review" buttons on their own, anywhere. Add heading="..." for a different heading or heading="-" for none.', 'if-reviews' ); ?></td></tr>
			</tbody>
		</table>

		<h3 style="margin-top:24px;"><?php esc_html_e( 'Source keys', 'if-reviews' ); ?></h3>
		<p>
			<?php
			$keys = array();
			foreach ( if_reviews_get_sources() as $key => $source ) {
				$keys[] = '<code>' . esc_html( $key ) . '</code> (' . esc_html( $source['label'] ) . ')';
			}
			echo wp_kses_post( implode( ', ', $keys ) );
			?>
		</p>
	</div>
	<?php
}

/**
 * Load the WordPress colour picker only on our settings screen.
 *
 * @param string $hook Current admin page hook.
 */
function if_reviews_settings_assets( $hook ) {

	if ( 'if_review_page_if-reviews-settings' !== $hook ) {
		return;
	}

	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );

	// The live preview uses the real front-end stylesheet.
	if_reviews_register_assets();
	wp_enqueue_style( 'if-reviews-style' );
	wp_add_inline_style( 'if-reviews-style', if_reviews_build_dynamic_css() );

	wp_add_inline_style(
		'common',
		'.if-cta-table td{vertical-align:top}'
		. '.if-cta-table .if-source-icon{width:16px;height:16px;color:#646970}'
		. '.if-help{margin-top:6px}'
		. '.if-help summary{cursor:pointer;font-size:12px;color:#2271b1}'
		. '.if-help summary:hover{color:#135e96}'
		. '.if-help p{margin:4px 0 0;font-size:12px;color:#646970;max-width:520px}'
		. '.if-swatch-row{display:flex;align-items:flex-start;gap:12px;margin:0 0 8px}'
		. '.if-swatch-row .wp-picker-container{flex:0 0 auto}'
		. '.if-swatch-remove{margin-top:6px;color:#b32d2e}'
		. '.if-swatch-remove:hover{color:#8a1f20}'
		. '.if-style-preview{max-width:760px;margin:12px 0 4px;padding:20px;border:1px solid #dcdcde;border-radius:6px;background:#fff}'
		. '.if-style-preview.if-preview-dark{background:#111827;color:#f3f4f6}'
		. '.if-style-preview-bar{display:flex;flex-wrap:wrap;gap:12px;align-items:center;margin:0 0 14px}'
	);

	$js = <<<'JS'
jQuery(function ($) {
	function initPickers(scope) {
		$(scope).find('.if-reviews-color-picker').each(function () {
			if (!$(this).closest('.wp-picker-container').length) {
				$(this).wpColorPicker();
			}
		});
	}

	initPickers(document);

	/* Live preview and presets ---------------------------------- */

	var preview = $('#if-reviews-preview');
	var field = function (key) { return $('[name="if_reviews_options[' + key + ']"]'); };
	var pickerVars = {
		accent_color: '--if-accent-color',
		star_color: '--if-star-color',
		card_bg_color: '--if-card-bg-color',
		border_color: '--if-border-color',
		text_color: '--if-text-color',
		meta_color: '--if-meta-color'
	};
	var classFields = {
		color_scheme: ['if-scheme-', 'light'],
		card_style: ['if-card-', 'shadow'],
		density: ['if-density-', 'comfortable'],
		text_size: ['if-text-', 'default']
	};

	function setVar(name, value) {
		var el = preview.get(0);
		if (!el) { return; }
		if (value) { el.style.setProperty(name, value); } else { el.style.removeProperty(name); }
	}

	function refresh() {
		if (!preview.length) { return; }
		var scheme = field('color_scheme').val();
		var dark = scheme === 'dark' || (scheme === 'auto' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
		// The dark scheme brings its own card colours (as on the site), so only
		// the star and accent colours are applied from the fields.
		$.each(pickerVars, function (key, cssVar) {
			var cardColour = key !== 'accent_color' && key !== 'star_color';
			setVar(cssVar, dark && cardColour ? '' : field(key).val());
		});
		setVar('--if-border-radius', (parseInt(field('border_radius').val(), 10) || 0) + 'px');
		$.each(classFields, function (key, info) {
			var value = field(key).val();
			preview.removeClass(function (i, cls) {
				return (cls.match(new RegExp('(^|\\s)' + info[0] + '\\S+', 'g')) || []).join(' ');
			});
			if (value && value !== info[1]) { preview.addClass(info[0] + value); }
		});
		preview.toggleClass('if-hover-lift', field('hover_lift').is(':checked'));
		$('.if-style-preview').toggleClass('if-preview-dark', dark);
	}

	// Colour pickers report changes through their own callbacks.
	$('.if-reviews-color-picker').each(function () {
		var input = $(this);
		if (input.closest('.if-swatches').length) { return; }
		input.wpColorPicker('option', 'change', function (event, ui) {
			input.val(ui.color.toString());
			refresh();
		});
		input.wpColorPicker('option', 'clear', function () {
			input.val('');
			refresh();
		});
	});
	$('#if-reviews-settings-form').on('change input', 'select, input', refresh);

	var presets = {
		classic: { color_scheme: 'light', card_style: 'shadow', density: 'comfortable', text_size: 'default', hover_lift: false, border_radius: 14, accent_color: '#f5a623', star_color: '', card_bg_color: '#ffffff', border_color: '#d6d6d6', text_color: '#4b5563', meta_color: '#6b7280' },
		minimal: { color_scheme: 'light', card_style: 'minimal', density: 'comfortable', text_size: 'default', hover_lift: false, border_radius: 0, accent_color: '#1f2937', star_color: '#f5a623', card_bg_color: '#ffffff', border_color: '#e5e7eb', text_color: '#374151', meta_color: '#6b7280' },
		outlined: { color_scheme: 'light', card_style: 'outlined', density: 'comfortable', text_size: 'default', hover_lift: false, border_radius: 8, accent_color: '#2563eb', star_color: '#f5a623', card_bg_color: '#ffffff', border_color: '#cbd5e1', text_color: '#334155', meta_color: '#64748b' },
		dark: { color_scheme: 'dark', card_style: 'flat', density: 'comfortable', text_size: 'default', hover_lift: false, border_radius: 14, accent_color: '#fbbf24', star_color: '', card_bg_color: '#ffffff', border_color: '#d6d6d6', text_color: '#4b5563', meta_color: '#6b7280' },
		compact: { color_scheme: 'light', card_style: 'outlined', density: 'compact', text_size: 'small', hover_lift: false, border_radius: 8, accent_color: '#f5a623', star_color: '', card_bg_color: '#ffffff', border_color: '#d6d6d6', text_color: '#4b5563', meta_color: '#6b7280' }
	};

	$('#if-reviews-preset-apply').on('click', function () {
		var preset = presets[$('#if-reviews-preset').val()];
		if (!preset) { return; }
		$.each(preset, function (key, value) {
			var input = field(key);
			if (input.is(':checkbox')) {
				input.prop('checked', !!value);
			} else if (input.hasClass('if-reviews-color-picker')) {
				if (value) {
					input.wpColorPicker('color', value);
				} else {
					input.val('');
					input.closest('.wp-picker-container').find('.wp-color-result').css('background-color', '');
				}
			} else {
				input.val(value);
			}
		});
		refresh();
		$('#if-reviews-preset-note').prop('hidden', false);
	});

	refresh();

	$('.if-swatches').each(function () {
		var box = $(this);
		var list = box.find('.if-swatch-list');
		var template = box.find('.if-swatch-template');

		box.on('click', '.if-swatch-add', function () {
			var row = $(template.html());
			list.append(row);
			initPickers(row);
			row.find('.wp-color-result').trigger('click');
		});

		box.on('click', '.if-swatch-remove', function () {
			$(this).closest('.if-swatch-row').remove();
		});
	});
});
JS;

	wp_add_inline_script( 'wp-color-picker', $js );
}
add_action( 'admin_enqueue_scripts', 'if_reviews_settings_assets' );

/**
 * "Settings" link on the Plugins list page.
 *
 * @param array $links Existing action links.
 * @return array
 */
function if_reviews_plugin_action_links( $links ) {

	$settings_url = admin_url( 'edit.php?post_type=if_review&page=if-reviews-settings' );

	array_unshift( $links, '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'if-reviews' ) . '</a>' );

	return $links;
}
add_filter( 'plugin_action_links_' . IF_REVIEWS_PLUGIN_BASENAME, 'if_reviews_plugin_action_links' );

/**
 * Live preview and style presets at the top of the Appearance section.
 * Rendered with the real card markup and front-end stylesheet.
 */
function if_reviews_render_style_preview() {

	$options = if_reviews_get_options();
	$samples = array(
		array(
			'id'           => 0,
			'name'         => __( 'Alex Turner', 'if-reviews' ),
			'initials'     => 'AT',
			'date'         => gmdate( 'Y-m-d', time() - 20 * DAY_IN_SECONDS ),
			'timestamp'    => time(),
			'rating'       => 5,
			'text'         => __( 'Clear advice from start to finish, and always quick to get back to us. Highly recommended.', 'if-reviews' ),
			'url'          => '',
			'display'      => true,
			'pinned'       => false,
			'categories'   => array(),
			'source'       => array( 'key' => 'google', 'label' => 'Google' ),
			'synced'       => true,
			'avatar_index' => 0,
		),
		array(
			'id'           => 0,
			'name'         => __( 'Sam Lee', 'if-reviews' ),
			'initials'     => 'SL',
			'date'         => gmdate( 'Y-m-d', time() - 75 * DAY_IN_SECONDS ),
			'timestamp'    => time(),
			'rating'       => 4,
			'text'         => __( 'Very helpful through a complicated process.', 'if-reviews' ),
			'url'          => '',
			'display'      => true,
			'pinned'       => false,
			'categories'   => array(),
			'source'       => array( 'key' => 'direct', 'label' => __( 'Direct', 'if-reviews' ) ),
			'synced'       => false,
			'avatar_index' => 1,
		),
	);
	$stats   = if_reviews_calculate_stats( $samples );
	$classes = array_merge( array( 'if-reviews-wrap', 'if-layout-grid', 'if-columns-2' ), if_reviews_style_classes( $options ) );
	?>
	<div class="if-style-preview-bar">
		<label for="if-reviews-preset"><strong><?php esc_html_e( 'Start from a style', 'if-reviews' ); ?></strong></label>
		<select id="if-reviews-preset">
			<option value="classic"><?php esc_html_e( 'Classic (soft shadow)', 'if-reviews' ); ?></option>
			<option value="minimal"><?php esc_html_e( 'Minimal (dividers)', 'if-reviews' ); ?></option>
			<option value="outlined"><?php esc_html_e( 'Outlined', 'if-reviews' ); ?></option>
			<option value="dark"><?php esc_html_e( 'Dark', 'if-reviews' ); ?></option>
			<option value="compact"><?php esc_html_e( 'Compact (sidebars)', 'if-reviews' ); ?></option>
		</select>
		<button type="button" class="button" id="if-reviews-preset-apply"><?php esc_html_e( 'Apply to the fields below', 'if-reviews' ); ?></button>
		<span id="if-reviews-preset-note" class="description" hidden><?php esc_html_e( 'Applied to the preview. Save Changes to use it on your site.', 'if-reviews' ); ?></span>
	</div>
	<div class="if-style-preview<?php echo 'dark' === $options['color_scheme'] ? ' if-preview-dark' : ''; ?>" aria-label="<?php esc_attr_e( 'Preview', 'if-reviews' ); ?>">
		<div id="if-reviews-preview" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
			<?php if_reviews_render_header( $stats, '', $options ); ?>
			<div class="if-cards-outer">
				<div class="if-cards">
					<?php
					foreach ( $samples as $sample ) {
						if_reviews_render_card( $sample, array_merge( $options, array( 'show_import_label' => 1 ) ) );
					}
					?>
				</div>
			</div>
		</div>
	</div>
	<?php
}
