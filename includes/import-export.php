<?php
/**
 * Reviews → Import / Export: reviews in and out as CSV.
 *
 * @package IF_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CSV columns, in export order, with a description for the help table.
 *
 * @return array<string, string>
 */
function if_reviews_csv_columns() {

	return array(
		'reviewer_name' => __( 'Reviewer name. Required.', 'if-reviews' ),
		'review_date'   => __( 'Date, preferably YYYY-MM-DD. DD/MM/YYYY and "August 2026" also work. Optional.', 'if-reviews' ),
		'rating'        => __( 'Required. 1 to 5, or a fraction such as 4/5 or 8/10, which is converted to stars.', 'if-reviews' ),
		'review_text'   => __( 'The review, exactly as written. Line breaks are kept.', 'if-reviews' ),
		'source'        => __( 'google, facebook, trustpilot, yelp, productreview, direct or other. A platform name such as "Google" also works; an unknown name becomes "Other" with that name as its label. Blank means direct.', 'if-reviews' ),
		'source_label'  => __( 'Platform name shown on the badge when source is "other". Optional.', 'if-reviews' ),
		'review_url'    => __( 'Link to the original review. Optional.', 'if-reviews' ),
		'categories'    => __( 'Category names separated by | (vertical bar), e.g. Mortgages|Insurance. Created if they do not exist. Optional.', 'if-reviews' ),
		'pinned'        => __( 'yes to pin the review to the top. Blank means no.', 'if-reviews' ),
		'display'       => __( 'yes or no. Blank means yes.', 'if-reviews' ),
		'status'        => __( 'publish or draft. Blank means publish.', 'if-reviews' ),
	);
}

/**
 * Header aliases accepted on import (normalised: lower-case, underscores).
 *
 * @return array<string, string> alias => canonical column
 */
function if_reviews_csv_aliases() {

	return array(
		'name'          => 'reviewer_name',
		'reviewer'      => 'reviewer_name',
		'author'        => 'reviewer_name',
		'date'          => 'review_date',
		'stars'         => 'rating',
		'score'         => 'rating',
		'text'          => 'review_text',
		'review'        => 'review_text',
		'comment'       => 'review_text',
		'body'          => 'review_text',
		'platform'      => 'source',
		'url'           => 'review_url',
		'link'          => 'review_url',
		'visible'       => 'display',
		'show'          => 'display',
		'shown'         => 'display',
		'category'      => 'categories',
		'tags'          => 'categories',
		'service'       => 'categories',
		'pin'           => 'pinned',
		'featured'      => 'pinned',
	);
}

/* ------------------------------------------------------------------
 * Menu and page
 * ---------------------------------------------------------------- */

/**
 * Add "Import / Export" under the Reviews menu.
 */
function if_reviews_add_import_export_page() {

	add_submenu_page(
		'edit.php?post_type=if_review',
		__( 'Import / Export Reviews', 'if-reviews' ),
		__( 'Import / Export', 'if-reviews' ),
		'manage_options',
		'if-reviews-import-export',
		'if_reviews_render_import_export_page'
	);
}
add_action( 'admin_menu', 'if_reviews_add_import_export_page', 11 );

/**
 * Render the page.
 */
function if_reviews_render_import_export_page() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$result_key = 'if_reviews_import_result_' . get_current_user_id();
	$result     = get_transient( $result_key );

	if ( $result ) {
		delete_transient( $result_key );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Import / Export Reviews', 'if-reviews' ); ?></h1>

		<?php if ( is_array( $result ) ) : ?>
			<?php if ( ! empty( $result['fatal'] ) ) : ?>
				<div class="notice notice-error"><p><?php echo esc_html( $result['fatal'] ); ?></p></div>
			<?php else : ?>
				<div class="notice notice-success">
					<p>
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: imported count, 2: duplicate count, 3: error count */
								__( 'Import finished: %1$d reviews imported, %2$d skipped as duplicates, %3$d rows with errors.', 'if-reviews' ),
								(int) $result['imported'],
								(int) $result['duplicates'],
								(int) $result['error_count']
							)
						);
						?>
					</p>
					<?php if ( ! empty( $result['errors'] ) ) : ?>
						<ul style="margin-left:1.5em;list-style:disc;">
							<?php foreach ( $result['errors'] as $line ) : ?>
								<li><?php echo esc_html( $line ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Export', 'if-reviews' ); ?></h2>
		<p><?php esc_html_e( 'Download every review, including hidden ones and drafts, as a UTF-8 CSV file. Useful as a backup, for editing in a spreadsheet, or for moving to another site.', 'if-reviews' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="if_reviews_export">
			<?php wp_nonce_field( 'if_reviews_export' ); ?>
			<?php submit_button( __( 'Download CSV', 'if-reviews' ), 'secondary', 'submit', false ); ?>
		</form>

		<hr style="margin: 28px 0;">

		<h2><?php esc_html_e( 'Import', 'if-reviews' ); ?></h2>
		<p>
			<?php esc_html_e( 'Upload a CSV with a header row. Only reviewer_name and rating are required. Each row becomes a new review; nothing existing is modified.', 'if-reviews' ); ?>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=if_reviews_csv_template' ), 'if_reviews_csv_template' ) ); ?>"><?php esc_html_e( 'Download a template', 'if-reviews' ); ?></a>
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
			<input type="hidden" name="action" value="if_reviews_import">
			<?php wp_nonce_field( 'if_reviews_import' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="if_reviews_csv"><?php esc_html_e( 'CSV file', 'if-reviews' ); ?></label></th>
					<td><input type="file" id="if_reviews_csv" name="if_reviews_csv" accept=".csv,text/csv,text/plain" required></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Duplicates', 'if-reviews' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="skip_duplicates" value="1" checked>
							<?php esc_html_e( 'Skip rows that match an existing review (same name, date and text)', 'if-reviews' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Lets you re-import a corrected file without creating copies.', 'if-reviews' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Import reviews', 'if-reviews' ) ); ?>
		</form>

		<h3><?php esc_html_e( 'Columns', 'if-reviews' ); ?></h3>
		<table class="widefat striped" style="max-width: 900px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Header', 'if-reviews' ); ?></th>
					<th><?php esc_html_e( 'Content', 'if-reviews' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( if_reviews_csv_columns() as $column => $help ) : ?>
					<tr>
						<td><code><?php echo esc_html( $column ); ?></code></td>
						<td><?php echo esc_html( $help ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description" style="max-width: 900px;">
			<?php esc_html_e( 'Column order does not matter, and common alternatives such as "name", "date", "stars", "text" and "platform" are recognised. Ratings above 5 are read as a mark out of 10 (or out of 100 above 10). Ambiguous dates such as 07/05/2025 are read day first.', 'if-reviews' ); ?>
		</p>
	</div>
	<?php
}

/* ------------------------------------------------------------------
 * Export
 * ---------------------------------------------------------------- */

/**
 * Stream all reviews as CSV.
 */
function if_reviews_handle_export() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to export reviews.', 'if-reviews' ) );
	}

	check_admin_referer( 'if_reviews_export' );

	if_reviews_send_csv_headers( 'if-reviews-' . wp_date( 'Y-m-d' ) . '.csv' );

	$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

	if_reviews_write_csv( $out );

	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	exit;
}
add_action( 'admin_post_if_reviews_export', 'if_reviews_handle_export' );

/**
 * Write every review as CSV rows to an open stream.
 *
 * @param resource $out Writable stream.
 */
function if_reviews_write_csv( $out ) {

	$ids = get_posts(
		array(
			'post_type'      => 'if_review',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		)
	);

	update_meta_cache( 'post', $ids );

	fputcsv( $out, array_keys( if_reviews_csv_columns() ), ',', '"', '\\' );

	foreach ( $ids as $id ) {

		$display = get_post_meta( $id, '_if_display_on_website', true );
		$source  = (string) get_post_meta( $id, '_if_source', true );
		$terms   = get_the_terms( $id, 'if_review_category' );

		$row = array(
			'reviewer_name' => get_post_meta( $id, '_if_reviewer_name', true ),
			'review_date'   => get_post_meta( $id, '_if_review_date', true ),
			'rating'        => (int) get_post_meta( $id, '_if_rating', true ),
			'review_text'   => get_post_meta( $id, '_if_review_text', true ),
			'source'        => '' === $source ? 'direct' : $source,
			'source_label'  => get_post_meta( $id, '_if_source_label', true ),
			'review_url'    => get_post_meta( $id, '_if_review_url', true ),
			'categories'    => is_array( $terms ) ? implode( '|', wp_list_pluck( $terms, 'name' ) ) : '',
			'pinned'        => ( '1' === get_post_meta( $id, '_if_pinned', true ) ) ? 'yes' : 'no',
			'display'       => ( '' === $display || '1' === $display ) ? 'yes' : 'no',
			'status'        => get_post_status( $id ),
		);

		fputcsv( $out, array_map( 'if_reviews_csv_cell', $row ), ',', '"', '\\' );
	}
}

/**
 * Download a one-row template.
 */
function if_reviews_handle_csv_template() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do that.', 'if-reviews' ) );
	}

	check_admin_referer( 'if_reviews_csv_template' );

	if_reviews_send_csv_headers( 'if-reviews-template.csv' );

	$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

	fputcsv( $out, array_keys( if_reviews_csv_columns() ), ',', '"', '\\' );
	fputcsv( $out, array( 'Alex T', '2026-08-14', '5', 'Clear advice and quick to respond.', 'google', '', 'https://maps.app.goo.gl/example', 'Mortgages', 'yes', 'yes', 'publish' ), ',', '"', '\\' );
	fputcsv( $out, array( 'Sam Lee', '2026-06-02', '8/10', 'Very helpful through a complicated process.', 'Tripadvisor', '', '', 'Insurance|Mortgages', '', 'yes', 'publish' ), ',', '"', '\\' );

	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	exit;
}
add_action( 'admin_post_if_reviews_csv_template', 'if_reviews_handle_csv_template' );

/**
 * Headers for a CSV download, with a UTF-8 BOM so Excel reads accents.
 *
 * @param string $filename Download name.
 */
function if_reviews_send_csv_headers( $filename ) {

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
	echo "\xEF\xBB\xBF";
}

/**
 * Neutralise spreadsheet formula injection: a cell starting with =, +, -
 * or @ would otherwise be executed by Excel or Sheets when opened.
 *
 * @param mixed $value Cell value.
 * @return string
 */
function if_reviews_csv_cell( $value ) {

	$value = (string) $value;

	if ( '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
		return "'" . $value;
	}

	return $value;
}

/* ------------------------------------------------------------------
 * Import
 * ---------------------------------------------------------------- */

/**
 * Handle the upload, create reviews, store a summary, redirect back.
 */
function if_reviews_handle_import() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to import reviews.', 'if-reviews' ) );
	}

	check_admin_referer( 'if_reviews_import' );

	$result = if_reviews_import_csv_upload(
		isset( $_FILES['if_reviews_csv'] ) ? $_FILES['if_reviews_csv'] : null, // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- validated in the function.
		! empty( $_POST['skip_duplicates'] )
	);

	set_transient( 'if_reviews_import_result_' . get_current_user_id(), $result, 5 * MINUTE_IN_SECONDS );

	wp_safe_redirect( admin_url( 'edit.php?post_type=if_review&page=if-reviews-import-export&imported=1' ) );
	exit;
}
add_action( 'admin_post_if_reviews_import', 'if_reviews_handle_import' );

/**
 * Validate an uploaded CSV and import it.
 *
 * @param array|null $file            One entry of $_FILES.
 * @param bool       $skip_duplicates Skip rows matching existing reviews.
 * @return array Summary: fatal | imported, duplicates, error_count, errors.
 */
function if_reviews_import_csv_upload( $file, $skip_duplicates ) {

	if ( ! is_array( $file ) || ! isset( $file['error'] ) || UPLOAD_ERR_OK !== (int) $file['error'] ) {
		return array( 'fatal' => __( 'No file was uploaded, or the upload failed. Check the file size limit of your server.', 'if-reviews' ) );
	}

	if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
		return array( 'fatal' => __( 'The upload could not be read.', 'if-reviews' ) );
	}

	if ( (int) $file['size'] > 5 * MB_IN_BYTES ) {
		return array( 'fatal' => __( 'The file is larger than 5 MB. Split it into smaller files.', 'if-reviews' ) );
	}

	$type = wp_check_filetype(
		sanitize_file_name( (string) $file['name'] ),
		array(
			'csv' => 'text/csv',
			'txt' => 'text/plain',
		)
	);

	if ( empty( $type['ext'] ) ) {
		return array( 'fatal' => __( 'Only .csv files can be imported.', 'if-reviews' ) );
	}

	$handle = fopen( $file['tmp_name'], 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

	if ( ! $handle ) {
		return array( 'fatal' => __( 'The file could not be opened.', 'if-reviews' ) );
	}

	$result = if_reviews_import_csv_stream( $handle, $skip_duplicates );

	fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

	return $result;
}

/**
 * Import reviews from an open CSV stream.
 *
 * @param resource $handle          Readable stream positioned at the start.
 * @param bool     $skip_duplicates Skip rows matching existing reviews.
 * @return array Summary.
 */
function if_reviews_import_csv_stream( $handle, $skip_duplicates ) {

	// Header line: strip a UTF-8 BOM, detect a semicolon-separated file.
	$first = fgets( $handle );

	if ( false === $first ) {
		return array( 'fatal' => __( 'The file is empty.', 'if-reviews' ) );
	}

	$first     = preg_replace( '/^\xEF\xBB\xBF/', '', $first );
	$delimiter = ( substr_count( $first, ';' ) > substr_count( $first, ',' ) ) ? ';' : ',';
	$headers   = str_getcsv( $first, $delimiter, '"', '\\' );
	$columns   = array();
	$aliases   = if_reviews_csv_aliases();
	$known     = array_keys( if_reviews_csv_columns() );

	foreach ( $headers as $index => $header ) {

		$key = strtolower( trim( (string) $header ) );
		$key = preg_replace( '/[\s\-]+/', '_', $key );

		if ( isset( $aliases[ $key ] ) ) {
			$key = $aliases[ $key ];
		}

		if ( in_array( $key, $known, true ) && ! isset( $columns[ $key ] ) ) {
			$columns[ $key ] = $index;
		}
	}

	if ( ! isset( $columns['reviewer_name'], $columns['rating'] ) ) {
		return array( 'fatal' => __( 'The header row must include at least "reviewer_name" and "rating". Download the template to see the expected format.', 'if-reviews' ) );
	}

	$existing = $skip_duplicates ? if_reviews_existing_fingerprints() : array();
	$sources  = if_reviews_get_sources();

	$imported   = 0;
	$duplicates = 0;
	$errors     = array();
	$line       = 1;
	$max_rows   = 5000;

	while ( ( $row = fgetcsv( $handle, 0, $delimiter, '"', '\\' ) ) !== false ) {

		$line++;

		if ( $line - 1 > $max_rows ) {
			$errors[] = sprintf(
				/* translators: %d: maximum rows */
				__( 'Stopped after %d rows. Import the rest in a second file.', 'if-reviews' ),
				$max_rows
			);
			break;
		}

		// Blank line.
		if ( 1 === count( $row ) && ( null === $row[0] || '' === trim( (string) $row[0] ) ) ) {
			continue;
		}

		$get = function ( $column ) use ( $row, $columns ) {
			if ( ! isset( $columns[ $column ] ) || ! isset( $row[ $columns[ $column ] ] ) ) {
				return '';
			}
			return if_reviews_csv_uncell( (string) $row[ $columns[ $column ] ] );
		};

		$name = sanitize_text_field( $get( 'reviewer_name' ) );

		if ( '' === $name ) {
			/* translators: %d: CSV line number */
			$errors[] = sprintf( __( 'Line %d: missing reviewer name.', 'if-reviews' ), $line );
			continue;
		}

		$rating = if_reviews_parse_rating( $get( 'rating' ) );

		if ( $rating < 1 ) {
			/* translators: 1: CSV line number, 2: the rating value */
			$errors[] = sprintf( __( 'Line %1$d: could not read the rating "%2$s".', 'if-reviews' ), $line, $get( 'rating' ) );
			continue;
		}

		$raw_date = $get( 'review_date' );
		$date     = if_reviews_parse_date( $raw_date );

		if ( '' !== trim( $raw_date ) && '' === $date ) {
			/* translators: 1: CSV line number, 2: the date value */
			$errors[] = sprintf( __( 'Line %1$d: date "%2$s" not understood; imported without a date.', 'if-reviews' ), $line, $raw_date );
		}

		$text = sanitize_textarea_field( $get( 'review_text' ) );

		list( $source_key, $source_label ) = if_reviews_resolve_source( $get( 'source' ), $sources );

		$custom_label = sanitize_text_field( $get( 'source_label' ) );

		if ( 'other' === $source_key && '' !== $custom_label ) {
			$source_label = $custom_label;
		}

		$url     = if_reviews_sanitize_url( $get( 'review_url' ) );
		$display = if_reviews_parse_yes_no( $get( 'display' ), true ) ? '1' : '0';
		$pinned  = if_reviews_parse_yes_no( $get( 'pinned' ), false ) ? '1' : '0';
		$status  = ( 'draft' === strtolower( trim( $get( 'status' ) ) ) ) ? 'draft' : 'publish';

		$category_names = array_values(
			array_filter(
				array_map( 'sanitize_text_field', array_map( 'trim', preg_split( '/[|;]/', $get( 'categories' ) ) ) )
			)
		);

		if ( $skip_duplicates ) {

			$fingerprint = if_reviews_review_fingerprint( $name, $date, $text );

			if ( isset( $existing[ $fingerprint ] ) ) {
				$duplicates++;
				continue;
			}

			$existing[ $fingerprint ] = true;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'if_review',
				'post_status' => $status,
				'post_title'  => $name,
				'meta_input'  => array(
					'_if_reviewer_name'      => $name,
					'_if_review_date'        => $date,
					'_if_rating'             => $rating,
					'_if_review_text'        => $text,
					'_if_source'             => $source_key,
					'_if_source_label'       => 'other' === $source_key ? $source_label : '',
					'_if_review_url'         => $url,
					'_if_display_on_website' => $display,
					'_if_pinned'             => $pinned,
				),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			/* translators: 1: CSV line number, 2: error message */
			$errors[] = sprintf( __( 'Line %1$d: %2$s', 'if-reviews' ), $line, $post_id->get_error_message() );
			continue;
		}

		if ( ! empty( $category_names ) ) {
			// Names are matched to existing terms or created.
			wp_set_object_terms( $post_id, $category_names, 'if_review_category' );
		}

		$imported++;
	}

	$error_count = count( $errors );

	if ( $error_count > 20 ) {
		$errors   = array_slice( $errors, 0, 20 );
		$errors[] = sprintf(
			/* translators: %d: number of further messages */
			__( '…and %d more.', 'if-reviews' ),
			$error_count - 20
		);
	}

	return array(
		'imported'    => $imported,
		'duplicates'  => $duplicates,
		'error_count' => $error_count,
		'errors'      => $errors,
	);
}

/**
 * Undo if_reviews_csv_cell(): drop a protective leading apostrophe.
 *
 * @param string $value Cell value.
 * @return string
 */
function if_reviews_csv_uncell( $value ) {

	if ( strlen( $value ) > 1 && "'" === $value[0] && in_array( $value[1], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
		return substr( $value, 1 );
	}

	return $value;
}

/**
 * Fingerprints of every existing review, for duplicate detection.
 *
 * @return array<string, true>
 */
function if_reviews_existing_fingerprints() {

	$ids = get_posts(
		array(
			'post_type'      => 'if_review',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	update_meta_cache( 'post', $ids );

	$fingerprints = array();

	foreach ( $ids as $id ) {
		$fingerprints[ if_reviews_review_fingerprint(
			get_post_meta( $id, '_if_reviewer_name', true ),
			get_post_meta( $id, '_if_review_date', true ),
			get_post_meta( $id, '_if_review_text', true )
		) ] = true;
	}

	return $fingerprints;
}

/**
 * Stable hash of the fields that identify a review.
 *
 * @param string $name Reviewer name.
 * @param string $date Y-m-d or ''.
 * @param string $text Review text.
 * @return string
 */
function if_reviews_review_fingerprint( $name, $date, $text ) {

	$normalise = function ( $value ) {
		return mb_strtolower( preg_replace( '/\s+/u', ' ', trim( (string) $value ) ) );
	};

	return md5( $normalise( $name ) . '|' . trim( (string) $date ) . '|' . $normalise( $text ) );
}

/**
 * Read a rating in any of the common spellings and return 1..5 (0 = unreadable).
 *
 *   5, 4.0, 4/5, 8/10, 4 out of 5, 80/100, ★★★★
 *   Bare numbers above 5 are read as out of 10; above 10 as out of 100.
 *
 * @param string $raw Cell value.
 * @return int
 */
function if_reviews_parse_rating( $raw ) {

	$raw = trim( (string) $raw );

	if ( '' === $raw ) {
		return 0;
	}

	$stars = preg_match_all( '/[★⭐]/u', $raw );

	if ( $stars > 0 ) {
		return max( 1, min( 5, $stars ) );
	}

	$raw = str_replace( ',', '.', $raw );

	if ( preg_match( '#^(\d+(?:\.\d+)?)\s*(?:/|of|out of)\s*(\d+(?:\.\d+)?)#i', $raw, $m ) ) {

		$denominator = (float) $m[2];

		if ( $denominator <= 0 ) {
			return 0;
		}

		$value = (float) $m[1] / $denominator * 5;

	} elseif ( preg_match( '/^(\d+(?:\.\d+)?)/', $raw, $m ) ) {

		$value = (float) $m[1];

		if ( $value > 10 ) {
			$value = $value / 20;
		} elseif ( $value > 5 ) {
			$value = $value / 2;
		}
	} else {
		return 0;
	}

	return max( 1, min( 5, (int) round( $value ) ) );
}

/**
 * Read a date in the common formats and return Y-m-d ('' if unreadable).
 * Ambiguous numeric dates are read day-first.
 *
 * @param string $raw Cell value.
 * @return string
 */
function if_reviews_parse_date( $raw ) {

	$raw = trim( (string) $raw );

	if ( '' === $raw ) {
		return '';
	}

	$formats = array(
		'Y-m-d', 'Y/m/d', 'Y.m.d',
		'd/m/Y', 'j/n/Y', 'd-m-Y', 'j-n-Y', 'd.m.Y', 'j.n.Y',
		'Y-m', 'm/Y', 'n/Y',
		'F Y', 'M Y', 'j F Y', 'd F Y', 'j M Y', 'd M Y', 'F j, Y', 'M j, Y',
		'Y-m-d H:i:s', 'Y-m-d\TH:i:s', 'Y-m-d\TH:i:sP', 'Y-m-d\TH:i:s.uP',
	);

	foreach ( $formats as $format ) {

		$dt = DateTimeImmutable::createFromFormat( '!' . $format, $raw );

		if ( ! $dt ) {
			continue;
		}

		// Reject partial matches such as "2026-08-14 something".
		$errors = DateTimeImmutable::getLastErrors();

		if ( $errors && ( $errors['warning_count'] > 0 || $errors['error_count'] > 0 ) ) {
			continue;
		}

		return $dt->format( 'Y-m-d' );
	}

	$timestamp = strtotime( $raw );

	return $timestamp ? gmdate( 'Y-m-d', $timestamp ) : '';
}

/**
 * Map a CSV source value to a known key, or to "other" with a label.
 *
 * @param string $raw     Cell value.
 * @param array  $sources From if_reviews_get_sources().
 * @return array{0:string,1:string} [key, label]
 */
function if_reviews_resolve_source( $raw, $sources ) {

	$raw = trim( (string) $raw );

	if ( '' === $raw ) {
		return array( 'direct', '' );
	}

	$key = sanitize_key( $raw );

	if ( isset( $sources[ $key ] ) ) {
		return array( $key, '' );
	}

	foreach ( $sources as $source_key => $source ) {
		if ( 0 === strcasecmp( $source['label'], $raw ) ) {
			return array( $source_key, '' );
		}
	}

	return array( 'other', sanitize_text_field( $raw ) );
}

/**
 * Read yes/no style values.
 *
 * @param string $raw     Cell value.
 * @param bool   $default Value for blank cells.
 * @return bool
 */
function if_reviews_parse_yes_no( $raw, $default ) {

	$raw = strtolower( trim( (string) $raw ) );

	if ( '' === $raw ) {
		return $default;
	}

	return in_array( $raw, array( '1', 'y', 'yes', 'true', 'on', 'show', 'shown', 'visible' ), true );
}
