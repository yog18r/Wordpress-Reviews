<?php
/**
 * The "Review" custom post type and its admin list screen.
 *
 * @package IF_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the post type.
 */
function if_register_reviews_post_type() {

	$labels = array(
		'name'               => _x( 'Reviews', 'post type general name', 'if-reviews' ),
		'singular_name'      => _x( 'Review', 'post type singular name', 'if-reviews' ),
		'menu_name'          => _x( 'Reviews', 'admin menu', 'if-reviews' ),
		'name_admin_bar'     => _x( 'Review', 'add new on admin bar', 'if-reviews' ),
		'add_new'            => __( 'Add New', 'if-reviews' ),
		'add_new_item'       => __( 'Add New Review', 'if-reviews' ),
		'edit_item'          => __( 'Edit Review', 'if-reviews' ),
		'new_item'           => __( 'New Review', 'if-reviews' ),
		'view_item'          => __( 'View Review', 'if-reviews' ),
		'all_items'          => __( 'All Reviews', 'if-reviews' ),
		'search_items'       => __( 'Search Reviews', 'if-reviews' ),
		'not_found'          => __( 'No reviews found.', 'if-reviews' ),
		'not_found_in_trash' => __( 'No reviews found in Trash.', 'if-reviews' ),
	);

	register_post_type(
		'if_review',
		array(
			'labels'            => $labels,
			'description'       => __( 'Customer reviews displayed by the [if_reviews] shortcode.', 'if-reviews' ),

			// Reviews have no public pages of their own.
			'public'            => false,
			'has_archive'       => false,
			'rewrite'           => false,
			'exclude_from_search' => true,

			// ...but do have an admin screen.
			'show_ui'           => true,
			'show_in_menu'      => true,
			'show_in_admin_bar' => true,
			'show_in_rest'      => false,

			// Only the title; every other field lives in the meta box.
			'supports'          => array( 'title' ),
			'menu_icon'         => 'dashicons-star-filled',
			'capability_type'   => 'post',
			'map_meta_cap'      => true,
		)
	);
}
add_action( 'init', 'if_register_reviews_post_type' );


/* ------------------------------------------------------------------
 * Admin list columns
 * ---------------------------------------------------------------- */

/**
 * Add Reviewer / Rating / Source / Review date / Shown columns.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function if_reviews_admin_columns( $columns ) {

	$new = array();

	foreach ( $columns as $key => $label ) {

		if ( 'date' === $key ) {
			$new['if_reviewer']    = __( 'Reviewer', 'if-reviews' );
			$new['if_rating']      = __( 'Rating', 'if-reviews' );
			$new['if_source']      = __( 'Source', 'if-reviews' );
			$new['if_review_date'] = __( 'Review date', 'if-reviews' );
			$new['if_shown']       = __( 'Shown', 'if-reviews' );
		}

		$new[ $key ] = $label;
	}

	return $new;
}
add_filter( 'manage_if_review_posts_columns', 'if_reviews_admin_columns' );

/**
 * Render the custom column cells.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function if_reviews_admin_column_content( $column, $post_id ) {

	switch ( $column ) {

		case 'if_reviewer':
			echo esc_html( get_post_meta( $post_id, '_if_reviewer_name', true ) );
			break;

		case 'if_rating':
			$rating = (int) get_post_meta( $post_id, '_if_rating', true );

			if ( $rating < 1 || $rating > 5 ) {
				echo '&mdash;';
				break;
			}

			echo '<span class="if-admin-stars" aria-hidden="true">'
				. esc_html( str_repeat( '★', $rating ) . str_repeat( '☆', 5 - $rating ) )
				. '</span>';
			echo '<span class="screen-reader-text">'
				/* translators: %d: star rating 1-5 */
				. esc_html( sprintf( _n( '%d star', '%d stars', $rating, 'if-reviews' ), $rating ) )
				. '</span>';
			break;

		case 'if_source':
			$source = if_reviews_get_review_source( $post_id );
			echo esc_html( $source['label'] );
			break;

		case 'if_review_date':
			echo esc_html( if_reviews_format_date( get_post_meta( $post_id, '_if_review_date', true ) ) );
			break;

		case 'if_shown':
			$shown  = get_post_meta( $post_id, '_if_display_on_website', true );
			$shown  = ( '' === $shown || '1' === $shown );
			$rating = (int) get_post_meta( $post_id, '_if_rating', true );

			if ( if_reviews_is_auto_hidden( $rating ) ) {
				echo '<span class="if-admin-hidden" title="' . esc_attr__( 'Hidden by the minimum rating in Display Settings', 'if-reviews' ) . '">' . esc_html__( 'Auto-hidden', 'if-reviews' ) . '</span>';
			} elseif ( ! $shown ) {
				echo '<span class="if-admin-hidden">' . esc_html__( 'Hidden', 'if-reviews' ) . '</span>';
			} else {
				esc_html_e( 'Yes', 'if-reviews' );
			}
			break;
	}
}
add_action( 'manage_if_review_posts_custom_column', 'if_reviews_admin_column_content', 10, 2 );

/**
 * Make rating and review date sortable.
 *
 * @param array $columns Sortable columns.
 * @return array
 */
function if_reviews_admin_sortable_columns( $columns ) {

	$columns['if_rating']      = 'if_rating';
	$columns['if_review_date'] = 'if_review_date';

	return $columns;
}
add_filter( 'manage_edit-if_review_sortable_columns', 'if_reviews_admin_sortable_columns' );

/**
 * Source filter dropdown above the list table.
 *
 * @param string $post_type Current post type.
 */
function if_reviews_admin_source_filter( $post_type ) {

	if ( 'if_review' !== $post_type ) {
		return;
	}

	// Read-only filter on a GET list screen: no nonce needed.
	$current = isset( $_GET['if_source'] ) ? sanitize_key( wp_unslash( $_GET['if_source'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	?>
	<label for="if-filter-source" class="screen-reader-text"><?php esc_html_e( 'Filter by source', 'if-reviews' ); ?></label>
	<select name="if_source" id="if-filter-source">
		<option value=""><?php esc_html_e( 'All sources', 'if-reviews' ); ?></option>
		<?php foreach ( if_reviews_get_sources() as $key => $source ) : ?>
			<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current, $key ); ?>>
				<?php echo esc_html( $source['label'] ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php
}
add_action( 'restrict_manage_posts', 'if_reviews_admin_source_filter' );

/**
 * Apply sorting and the source filter to the admin list query.
 *
 * @param WP_Query $query The query.
 */
function if_reviews_admin_list_query( $query ) {

	if ( ! is_admin() || ! $query->is_main_query() || 'if_review' !== $query->get( 'post_type' ) ) {
		return;
	}

	$orderby = $query->get( 'orderby' );

	if ( 'if_rating' === $orderby ) {
		$query->set( 'meta_key', '_if_rating' );
		$query->set( 'orderby', 'meta_value_num' );
	} elseif ( 'if_review_date' === $orderby ) {
		$query->set( 'meta_key', '_if_review_date' );
		$query->set( 'orderby', 'meta_value' );
	}

	$source = isset( $_GET['if_source'] ) ? sanitize_key( wp_unslash( $_GET['if_source'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( '' !== $source ) {

		$meta_query = (array) $query->get( 'meta_query' );

		if ( 'direct' === $source ) {
			// Reviews saved before sources existed have no meta: treat as direct.
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'   => '_if_source',
					'value' => 'direct',
				),
				array(
					'key'     => '_if_source',
					'compare' => 'NOT EXISTS',
				),
			);
		} else {
			$meta_query[] = array(
				'key'   => '_if_source',
				'value' => $source,
			);
		}

		$query->set( 'meta_query', $meta_query );
	}
}
add_action( 'pre_get_posts', 'if_reviews_admin_list_query' );

/* ------------------------------------------------------------------
 * Bulk actions: re-tag the source or toggle visibility for many
 * reviews at once (handy after upgrading from 1.x, where every
 * review was implicitly a Google review).
 * ---------------------------------------------------------------- */

/**
 * Add the bulk actions to the dropdown.
 *
 * @param array $actions Existing bulk actions.
 * @return array
 */
function if_reviews_bulk_actions( $actions ) {

	foreach ( if_reviews_get_sources() as $key => $source ) {
		/* translators: %s: source name, e.g. Google */
		$actions[ 'if_source_' . $key ] = sprintf( __( 'Set source: %s', 'if-reviews' ), $source['label'] );
	}

	$actions['if_show'] = __( 'Show on website', 'if-reviews' );
	$actions['if_hide'] = __( 'Hide from website', 'if-reviews' );

	return $actions;
}
add_filter( 'bulk_actions-edit-if_review', 'if_reviews_bulk_actions' );

/**
 * Apply a bulk action. WordPress has already verified the bulk nonce.
 *
 * @param string $redirect Redirect URL.
 * @param string $action   Action key.
 * @param int[]  $post_ids Selected post IDs.
 * @return string
 */
function if_reviews_handle_bulk_actions( $redirect, $action, $post_ids ) {

	$changed = 0;

	if ( 0 === strpos( $action, 'if_source_' ) ) {

		$source = substr( $action, strlen( 'if_source_' ) );

		if ( ! array_key_exists( $source, if_reviews_get_sources() ) ) {
			return $redirect;
		}

		foreach ( $post_ids as $post_id ) {
			if ( current_user_can( 'edit_post', $post_id ) ) {
				update_post_meta( $post_id, '_if_source', $source );
				$changed++;
			}
		}

	} elseif ( 'if_show' === $action || 'if_hide' === $action ) {

		foreach ( $post_ids as $post_id ) {
			if ( current_user_can( 'edit_post', $post_id ) ) {
				update_post_meta( $post_id, '_if_display_on_website', 'if_show' === $action ? '1' : '0' );
				$changed++;
			}
		}

	} else {
		return $redirect;
	}

	return add_query_arg( 'if_bulk_changed', $changed, remove_query_arg( 'if_bulk_changed', $redirect ) );
}
add_filter( 'handle_bulk_actions-edit-if_review', 'if_reviews_handle_bulk_actions', 10, 3 );

/**
 * Confirmation notice after a bulk action.
 */
function if_reviews_bulk_notice() {

	if ( ! isset( $_GET['if_bulk_changed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
		return;
	}

	$screen = get_current_screen();

	if ( ! $screen || 'edit-if_review' !== $screen->id ) {
		return;
	}

	$count = absint( $_GET['if_bulk_changed'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	printf(
		'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
		/* translators: %d: number of reviews */
		esc_html( sprintf( _n( '%d review updated.', '%d reviews updated.', $count, 'if-reviews' ), $count ) )
	);
}
add_action( 'admin_notices', 'if_reviews_bulk_notice' );

/**
 * Small amount of admin CSS for the list table and meta box.
 *
 * @param string $hook Current admin page hook.
 */
function if_reviews_admin_styles( $hook ) {

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen || 'if_review' !== $screen->post_type ) {
		return;
	}

	$css = '.if-admin-stars{color:#f5a623;letter-spacing:1px;font-size:15px}'
		. '.if-admin-hidden{color:#a7aaad}'
		. '.column-if_rating{width:110px}.column-if_shown{width:80px}'
		. '.if-review-fields .if-field{margin:0 0 18px}'
		. '.if-review-fields label strong{display:block;margin-bottom:4px}'
		. '.if-review-fields .description{margin-top:4px}';

	wp_add_inline_style( 'common', $css );
}
add_action( 'admin_enqueue_scripts', 'if_reviews_admin_styles' );
