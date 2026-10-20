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
			echo '<p>' . esc_html__( 'Defaults for the [if_reviews] shortcode. Any of these can be overridden on an individual shortcode with an attribute of the same name (see the reference below).', 'if-reviews' ) . '</p>';
		},
		'if-reviews-settings'
	);

	$display_fields = array(
		'show_header'       => array(
			'type'        => 'checkbox',
			'label'       => __( 'Summary header', 'if-reviews' ),
			'text'        => __( 'Show the overall rating, review count and sources above the cards', 'if-reviews' ),
			'description' => __( 'The header is calculated from every published review, including hidden ones, so it always reflects the full set.', 'if-reviews' ),
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
			'description' => __( '0 shows every visible review. With a limit, grid and list layouts add a "Show more" button for the rest; the carousel simply stops at the limit.', 'if-reviews' ),
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
			'description' => __( '0 is off. Otherwise the carousel moves on by one card every so many seconds, pausing while a visitor hovers or focuses it, with a pause button. Visitors who prefer reduced motion never get autoplay.', 'if-reviews' ),
		),
		'excerpt_length'    => array(
			'type'        => 'number',
			'label'       => __( '"Read more" after (characters)', 'if-reviews' ),
			'min'         => 0,
			'max'         => 5000,
			'description' => __( '0 always shows the full text. Otherwise long reviews are cut at a word boundary near this many characters with a "Read more" button (minimum 40; try 280).', 'if-reviews' ),
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
			'description' => __( 'Opt-in. Reviews hidden this way are treated exactly like reviews you hide by hand: they stay in WordPress and still count toward the overall rating and review count in the header, so the summary remains truthful.', 'if-reviews' ),
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
			echo '<p>' . esc_html__( 'Optional buttons under the widget that take visitors straight to your review page on each platform. Off by default. Add a link for every platform you want a button for; the buttons can also be placed on their own with the [if_reviews_buttons] shortcode.', 'if-reviews' ) . '</p>';
		},
		'if-reviews-settings'
	);

	$cta_fields = array(
		'cta_enabled' => array(
			'type'        => 'checkbox',
			'label'       => __( 'Show buttons', 'if-reviews' ),
			'text'        => __( 'Show the buttons under the reviews widget', 'if-reviews' ),
			'description' => __( 'Only platforms with a link below get a button. Override per instance with buttons="yes" or "no" on the shortcode, or the block option.', 'if-reviews' ),
		),
		'cta_heading' => array(
			'type'        => 'text',
			'label'       => __( 'Heading above the buttons', 'if-reviews' ),
			'placeholder' => __( 'Had a good experience? Leave us a review.', 'if-reviews' ),
			'description' => __( 'Blank uses the text shown as the placeholder. Enter a single hyphen (-) for no heading.', 'if-reviews' ),
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
			echo '<p>' . esc_html__( 'Colours and shape of the widget. Changes apply immediately, site-wide.', 'if-reviews' ) . '</p>';
		},
		'if-reviews-settings'
	);

	$color_fields = array(
		'accent_color'  => array( __( 'Star / accent colour', 'if-reviews' ), __( 'Used for the stars.', 'if-reviews' ) ),
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
				<?php esc_html_e( 'Leave the link blank for any platform you do not want a button for. Blank button text uses the placeholder shown.', 'if-reviews' ); ?>
			</p>
			<?php
			break;

		case 'avatar':
			$colors = array_filter( array_map( 'trim', explode( ',', (string) $value ) ) );
			?>
			<input type="text" class="regular-text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="4285f4,34a853,ea4335,fbbc04">
			<div style="margin-top:8px; display:flex; gap:6px; flex-wrap:wrap;">
				<?php foreach ( $colors as $hex ) : ?>
					<span style="display:inline-block;width:24px;height:24px;border-radius:50%;border:1px solid #ccc;background:linear-gradient(135deg,#<?php echo esc_attr( ltrim( $hex, '#' ) ); ?>,<?php echo esc_attr( if_reviews_darken_hex( $hex, 0.15 ) ); ?>);"></span>
				<?php endforeach; ?>
			</div>
			<p class="description">
				<?php esc_html_e( 'Comma-separated hex codes (no # needed). Avatars cycle through these colours in order. Text colour on each avatar is chosen automatically for contrast.', 'if-reviews' ); ?>
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

		<form method="post" action="options.php">
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
				<tr><td><code>[if_reviews paginate="12"]</code></td><td><?php esc_html_e( 'Numbered pages of twelve instead of a "Show more" button. Use one paginated widget per page.', 'if-reviews' ); ?></td></tr>
				<tr><td><code>[if_reviews layout="carousel" autoplay="6"]</code></td><td><?php esc_html_e( 'Carousel that moves on every six seconds (0 to disable).', 'if-reviews' ); ?></td></tr>
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

	wp_add_inline_style(
		'common',
		'.if-cta-table td{vertical-align:top}'
		. '.if-cta-table .if-source-icon{width:16px;height:16px;color:#646970}'
		. '.if-help{margin-top:6px}'
		. '.if-help summary{cursor:pointer;font-size:12px;color:#2271b1}'
		. '.if-help summary:hover{color:#135e96}'
		. '.if-help p{margin:4px 0 0;font-size:12px;color:#646970;max-width:520px}'
	);

	wp_add_inline_script(
		'wp-color-picker',
		'jQuery(function($){ $(".if-reviews-color-picker").wpColorPicker(); });'
	);
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
