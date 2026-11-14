<?php
/**
 * Optional import from Google Business Profile.
 *
 * The site owner connects their own Google Cloud project (one that Google
 * has approved for the Business Profile APIs) with OAuth. The plugin then
 * reads the reviews of one business location and keeps a local copy as
 * ordinary reviews with the source "Google".
 *
 * Nothing here runs unless the owner sets it up; the plugin makes no
 * request to Google otherwise. Each imported review stores Google's review
 * ID, so later syncs update rather than duplicate, and reviews that were
 * entered by hand before connecting are matched on the first run and
 * linked instead of being imported twice.
 *
 * @package IF_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const IF_REVIEWS_GOOGLE_OPTION   = 'if_reviews_google';
const IF_REVIEWS_GOOGLE_CRON     = 'if_reviews_google_sync_event';
const IF_REVIEWS_GOOGLE_SCOPE    = 'https://www.googleapis.com/auth/business.manage';
const IF_REVIEWS_GOOGLE_AUTH     = 'https://accounts.google.com/o/oauth2/v2/auth';
const IF_REVIEWS_GOOGLE_TOKEN    = 'https://oauth2.googleapis.com/token';
const IF_REVIEWS_GOOGLE_REVOKE   = 'https://oauth2.googleapis.com/revoke';
const IF_REVIEWS_GOOGLE_ACCOUNTS = 'https://mybusinessaccountmanagement.googleapis.com/v1/accounts';
const IF_REVIEWS_GOOGLE_LOCATION = 'https://mybusinessbusinessinformation.googleapis.com/v1/';
const IF_REVIEWS_GOOGLE_REVIEWS  = 'https://mybusiness.googleapis.com/v4/';

/* ------------------------------------------------------------------
 * Option
 * ---------------------------------------------------------------- */

/**
 * Default connection settings.
 *
 * @return array
 */
function if_reviews_google_defaults() {
	return array(
		'client_id'       => '',
		'client_secret'   => '',
		'refresh_token'   => '',
		'access_token'    => '',
		'access_expires'  => 0,
		'account'         => '',
		'location'        => '',
		'location_title'  => '',
		'auto_sync'       => 0,
		'needs_reconnect' => 0,
		'first_sync_done' => 0,
		'skipped'         => array(),
		'last_sync'       => 0,
		'last_result'     => array(),
		'key_changed'     => 0,
	);
}

/**
 * Settings that are private and therefore stored encrypted.
 *
 * @return string[]
 */
function if_reviews_google_private_keys() {
	return array( 'client_id', 'client_secret', 'refresh_token', 'access_token', 'account', 'location' );
}

/**
 * Credentials defined in wp-config.php, which take precedence and are
 * never written to the database.
 *
 * @return array Subset of client_id / client_secret.
 */
function if_reviews_google_constants() {
	$out = array();
	if ( defined( 'IF_REVIEWS_GOOGLE_CLIENT_ID' ) && '' !== (string) IF_REVIEWS_GOOGLE_CLIENT_ID ) {
		$out['client_id'] = (string) IF_REVIEWS_GOOGLE_CLIENT_ID;
	}
	if ( defined( 'IF_REVIEWS_GOOGLE_CLIENT_SECRET' ) && '' !== (string) IF_REVIEWS_GOOGLE_CLIENT_SECRET ) {
		$out['client_secret'] = (string) IF_REVIEWS_GOOGLE_CLIENT_SECRET;
	}
	return $out;
}

/**
 * Current connection settings, decrypted.
 *
 * If a private value cannot be decrypted (the site's security keys have
 * changed since it was saved) it comes back empty and key_changed is set,
 * so the page can ask the owner to enter the secret and reconnect.
 *
 * @return array
 */
function if_reviews_google_get() {
	$saved = get_option( IF_REVIEWS_GOOGLE_OPTION, array() );
	$g     = wp_parse_args( is_array( $saved ) ? $saved : array(), if_reviews_google_defaults() );

	foreach ( if_reviews_google_private_keys() as $key ) {
		$plain = if_reviews_decrypt( $g[ $key ] );
		if ( false === $plain ) {
			$plain            = '';
			$g['key_changed'] = 1;
		}
		$g[ $key ] = $plain;
	}

	return array_merge( $g, if_reviews_google_constants() );
}

/**
 * Merge values into the connection settings and save them, encrypting the
 * private ones.
 *
 * @param array $changes Keys to overwrite.
 * @return array The new settings, decrypted.
 */
function if_reviews_google_update( $changes ) {
	$new   = array_merge( if_reviews_google_get(), $changes );
	$store = $new;

	foreach ( if_reviews_google_private_keys() as $key ) {
		$store[ $key ] = if_reviews_encrypt( $new[ $key ] );
	}

	// Credentials from wp-config.php stay out of the database.
	foreach ( array_keys( if_reviews_google_constants() ) as $key ) {
		$store[ $key ] = '';
	}

	update_option( IF_REVIEWS_GOOGLE_OPTION, $store, false );
	return array_merge( $new, if_reviews_google_constants() );
}

/**
 * Encrypt values saved by versions before 2.4.3.
 */
function if_reviews_google_migrate_encryption() {
	$saved = get_option( IF_REVIEWS_GOOGLE_OPTION );

	if ( ! is_array( $saved ) || ! if_reviews_crypto_available() ) {
		return;
	}

	foreach ( if_reviews_google_private_keys() as $key ) {
		if ( ! empty( $saved[ $key ] ) && ! if_reviews_is_encrypted( $saved[ $key ] ) ) {
			if_reviews_google_update( array() );
			return;
		}
	}
}
add_action( 'admin_init', 'if_reviews_google_migrate_encryption' );

/**
 * Whether a refresh token is stored and a location chosen.
 *
 * @return bool
 */
function if_reviews_google_is_connected() {
	$g = if_reviews_google_get();
	return '' !== $g['refresh_token'] && '' !== $g['account'] && '' !== $g['location'];
}

/**
 * The redirect URI the owner registers in Google Cloud.
 *
 * @return string
 */
function if_reviews_google_redirect_uri() {
	return admin_url( 'admin-post.php?action=if_reviews_google_callback' );
}

/**
 * URL of the Google import page.
 *
 * @param array $args Extra query args.
 * @return string
 */
function if_reviews_google_page_url( $args = array() ) {
	return add_query_arg( array_merge( array( 'post_type' => 'if_review', 'page' => 'if-reviews-google' ), $args ), admin_url( 'edit.php' ) );
}

/* ------------------------------------------------------------------
 * Admin notices carried across redirects
 * ---------------------------------------------------------------- */

/**
 * Queue a notice for the current user.
 *
 * @param string $type    success|error|warning|info.
 * @param string $message Text.
 */
function if_reviews_google_notice( $type, $message ) {
	set_transient( 'if_reviews_google_notice_' . get_current_user_id(), array( 'type' => $type, 'message' => $message ), 300 );
}

/**
 * Print and clear the queued notice.
 */
function if_reviews_google_print_notice() {
	$key    = 'if_reviews_google_notice_' . get_current_user_id();
	$notice = get_transient( $key );

	if ( ! is_array( $notice ) ) {
		return;
	}

	delete_transient( $key );
	printf(
		'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
		esc_attr( in_array( $notice['type'], array( 'success', 'error', 'warning', 'info' ), true ) ? $notice['type'] : 'info' ),
		esc_html( $notice['message'] )
	);
}

/* ------------------------------------------------------------------
 * Menu and page
 * ---------------------------------------------------------------- */

/**
 * Add "Google import" under Reviews.
 */
function if_reviews_google_add_page() {
	add_submenu_page(
		'edit.php?post_type=if_review',
		__( 'Google Business Profile import', 'if-reviews' ),
		__( 'Google import', 'if-reviews' ),
		'manage_options',
		'if-reviews-google',
		'if_reviews_google_render_page'
	);
}
add_action( 'admin_menu', 'if_reviews_google_add_page', 12 );

/**
 * Warnings about how private settings are protected on this site.
 *
 * @param array $g Connection settings.
 */
function if_reviews_google_print_security_notices( $g ) {

	if ( $g['key_changed'] ) {
		echo '<div class="notice notice-error inline"><p>' . esc_html__( 'The saved Google connection could not be decrypted, usually because this site\'s security keys in wp-config.php have changed. Enter the Client ID and Client Secret again, then connect. Your reviews are not affected.', 'if-reviews' ) . '</p></div>';
	}

	if ( ! if_reviews_crypto_available() ) {
		echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'This server cannot encrypt data (PHP\'s sodium functions are missing), so the Google credentials are stored unencrypted. Ask your host to enable the sodium extension.', 'if-reviews' ) . '</p></div>';
	} elseif ( 'database' === if_reviews_crypto_key_source() ) {
		echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'This site\'s wp-config.php has no security keys of its own, so the key that encrypts your Google credentials is kept in the database beside them. Add keys from api.wordpress.org/secret-key/1.1/salt/ to wp-config.php, or define IF_REVIEWS_ENCRYPTION_KEY there. You will need to enter the Client Secret and connect again afterwards.', 'if-reviews' ) . '</p></div>';
	}
}

/**
 * Render the page.
 */
function if_reviews_google_render_page() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$g         = if_reviews_google_get();
	$connected = if_reviews_google_is_connected();
	$has_creds = '' !== $g['client_id'] && '' !== $g['client_secret'];
	$admin_post = admin_url( 'admin-post.php' );
	?>
	<div class="wrap if-google-wrap">
		<h1><?php esc_html_e( 'Google Business Profile import', 'if-reviews' ); ?></h1>

		<?php if_reviews_google_print_notice(); ?>
		<?php if_reviews_google_print_security_notices( $g ); ?>

		<p style="max-width: 760px;">
			<?php esc_html_e( 'Copy the reviews from one Google Business Profile location into this site and keep them up to date. This is optional and uses your own Google Cloud project: the plugin never talks to Google unless you connect it here, and nothing is shared with anyone else.', 'if-reviews' ); ?>
		</p>

		<?php
		// Preview of a pending sync, if one was just prepared.
		if ( isset( $_GET['preview'] ) && if_reviews_google_render_preview() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only flag; the plan itself is in a per-user transient.
			echo '</div>';
			return;
		}
		?>

		<?php if ( $connected ) : ?>
			<div class="card" style="max-width: 760px; padding: 16px 20px;">
				<h2 style="margin-top: 0;"><?php esc_html_e( 'Connected', 'if-reviews' ); ?></h2>
				<p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: business location name */
							__( 'Location: %s', 'if-reviews' ),
							'' !== $g['location_title'] ? $g['location_title'] : $g['location']
						)
					);
					?>
				</p>
				<?php if ( $g['needs_reconnect'] ) : ?>
					<div class="notice notice-error inline">
						<p><?php esc_html_e( 'Google no longer accepts this site\'s connection, so syncing has stopped. Reconnect to carry on; your reviews, location and settings are kept.', 'if-reviews' ); ?></p>
						<form method="post" action="<?php echo esc_url( $admin_post ); ?>" style="margin: 0 0 10px;">
							<input type="hidden" name="action" value="if_reviews_google_connect">
							<?php wp_nonce_field( 'if_reviews_google_connect' ); ?>
							<?php submit_button( __( 'Reconnect', 'if-reviews' ), 'primary', 'submit', false ); ?>
						</form>
					</div>
				<?php endif; ?>
				<?php if_reviews_google_render_last_result( $g ); ?>

				<div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-top: 12px;">
					<form method="post" action="<?php echo esc_url( $admin_post ); ?>">
						<input type="hidden" name="action" value="if_reviews_google_preview">
						<?php wp_nonce_field( 'if_reviews_google_preview' ); ?>
						<?php submit_button( __( 'Preview sync', 'if-reviews' ), 'primary', 'submit', false ); ?>
					</form>
					<?php if ( $g['first_sync_done'] ) : ?>
						<form method="post" action="<?php echo esc_url( $admin_post ); ?>">
							<input type="hidden" name="action" value="if_reviews_google_sync">
							<?php wp_nonce_field( 'if_reviews_google_sync' ); ?>
							<?php submit_button( __( 'Sync now', 'if-reviews' ), 'secondary', 'submit', false ); ?>
						</form>
					<?php endif; ?>
					<form method="post" action="<?php echo esc_url( $admin_post ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Disconnect from Google? Imported reviews stay on this site; they just stop updating.', 'if-reviews' ) ); ?>');">
						<input type="hidden" name="action" value="if_reviews_google_disconnect">
						<?php wp_nonce_field( 'if_reviews_google_disconnect' ); ?>
						<?php submit_button( __( 'Disconnect', 'if-reviews' ), 'link-delete', 'submit', false ); ?>
					</form>
				</div>
				<p class="description" style="margin-top: 10px;">
					<?php esc_html_e( 'Preview shows what a sync would do before anything changes. Use it the first time, and whenever you have added reviews by hand since.', 'if-reviews' ); ?>
				</p>

				<form method="post" action="<?php echo esc_url( $admin_post ); ?>" style="margin-top: 16px; border-top: 1px solid #dcdcde; padding-top: 12px;">
					<input type="hidden" name="action" value="if_reviews_google_settings">
					<?php wp_nonce_field( 'if_reviews_google_settings' ); ?>
					<label>
						<input type="checkbox" name="auto_sync" value="1" <?php checked( $g['auto_sync'] ); ?>>
						<?php esc_html_e( 'Sync automatically once a day', 'if-reviews' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'New reviews are imported, edited reviews are updated, and reviews that have disappeared from Google are flagged in the Reviews list. Reviews you hide, pin or categorise keep those settings.', 'if-reviews' ); ?></p>
					<?php submit_button( __( 'Save', 'if-reviews' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>

		<?php elseif ( $has_creds && '' !== $g['refresh_token'] ) : ?>
			<?php if_reviews_google_render_location_picker(); ?>

		<?php else : ?>
			<h2><?php esc_html_e( 'Step 1: Google Cloud project', 'if-reviews' ); ?></h2>
			<p style="max-width: 760px;">
				<?php esc_html_e( 'Google must approve your project before it can read reviews. Setup takes about an hour, plus a wait of days to weeks for the approval. You only do it once per site.', 'if-reviews' ); ?>
			</p>
			<details class="if-help if-setup-guide" style="max-width: 760px; margin-bottom: 16px;" <?php echo $has_creds ? '' : 'open'; ?>>
				<summary><?php esc_html_e( 'How to get a Client ID and Client Secret (six steps)', 'if-reviews' ); ?></summary>
				<ol>
					<li>
						<strong><?php esc_html_e( 'Check you qualify.', 'if-reviews' ); ?></strong>
						<?php esc_html_e( 'Google requires a Business Profile that is verified, at least 60 days old and lists this website. Do every step below signed in to the Google account that owns or manages that profile. For the least risk, connect with an account that is a Manager of the profile rather than its Owner: if the connection were ever misused, a Manager cannot remove the owner or transfer the profile.', 'if-reviews' ); ?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Create a Cloud project.', 'if-reviews' ); ?></strong>
						<?php echo wp_kses_post( __( 'At <a href="https://console.cloud.google.com/" target="_blank" rel="noopener">console.cloud.google.com</a> create a new project (any name). Note the <em>project number</em> shown on its dashboard.', 'if-reviews' ) ); ?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Ask Google for Business Profile API access.', 'if-reviews' ); ?></strong>
						<?php echo wp_kses_post( __( 'Fill in <a href="https://support.google.com/business/contact/api_default" target="_blank" rel="noopener">Google\'s access request form</a>, choosing "Application for Basic API Access", with the project number and the email address that owns or manages the profile. Google checks each request by hand, which takes a few days to a few weeks. To check progress, open "IAM & Admin → Quotas" in the project: 0 for the Business Profile APIs means not yet approved, 300 means approved.', 'if-reviews' ) ); ?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Enable three APIs.', 'if-reviews' ); ?></strong>
						<?php esc_html_e( 'Open "APIs & Services → Library" and enable My Business Account Management API and My Business Business Information API. The third, Google My Business API, only appears in the Library once Google has approved your request; enable it then.', 'if-reviews' ); ?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Set up the consent screen so the connection does not expire.', 'if-reviews' ); ?></strong>
						<?php esc_html_e( 'Open "Google Auth Platform" (older consoles call it "OAuth consent screen"). Enter an app name and your email address for support and contact. Under "Data access", add the scope https://www.googleapis.com/auth/business.manage. For the audience, choose the option that matches your account:', 'if-reviews' ); ?>
						<ul>
							<li><?php echo wp_kses_post( __( '<strong>Google Workspace account</strong> (your email is on your own domain): choose <strong>Internal</strong>. That is all: there is no warning screen and the connection does not expire.', 'if-reviews' ) ); ?></li>
							<li><?php echo wp_kses_post( __( '<strong>Personal Google account</strong> (gmail.com): choose <strong>External</strong>, then on the Audience page click <strong>Publish app</strong> and confirm. If you leave the app in "Testing", Google ends the connection after 7 days and you must reconnect every week. Publishing needs no review by Google for your own use. You will see a "Google hasn\'t verified this app" screen once, when you connect: click Advanced → Go to (your app name).', 'if-reviews' ) ); ?></li>
						</ul>
					</li>
					<li>
						<strong><?php esc_html_e( 'Create the credentials.', 'if-reviews' ); ?></strong>
						<?php esc_html_e( 'Under "Clients" (or "Credentials → Create credentials → OAuth client ID") choose "Web application", and paste the redirect URI shown below into "Authorised redirect URIs", exactly as shown. Google then shows a Client ID and Client Secret: copy both into the fields below and click Save and continue.', 'if-reviews' ); ?>
					</li>
				</ol>
				<h4 style="margin: 16px 0 6px;"><?php esc_html_e( 'If your Google account is managed by a Workspace administrator', 'if-reviews' ); ?></h4>
				<p class="description" style="max-width: 720px;">
					<?php esc_html_e( 'Two Workspace policies can block the connection: an API controls setting that blocks unapproved apps, and a Google Cloud session policy that forces a new sign-in every few hours (often 16). Marking this app as trusted fixes both. An administrator does this in the Admin console:', 'if-reviews' ); ?>
				</p>
				<ol>
					<li><?php esc_html_e( 'Security → Access and data control → API controls → "Manage Third-Party App Access" → "Add app" → search by the Client ID from step 6 → select it → set access to Trusted for the organisation (or the unit the connecting account belongs to) → save.', 'if-reviews' ); ?></li>
					<li><?php esc_html_e( 'Security → Access and data control → Google Cloud session control: if "Require reauthentication" is on, tick "Exempt trusted apps" and save. (This plugin does not use Google Cloud scopes, so the policy should not apply to it, but the exemption removes any doubt.)', 'if-reviews' ); ?></li>
				</ol>
				<p class="description"><?php esc_html_e( 'If the connection ever stops working, this page tells you and shows a Reconnect button. No reviews are lost.', 'if-reviews' ); ?></p>
				<h4 style="margin: 16px 0 6px;"><?php esc_html_e( 'Keeping the connection safe', 'if-reviews' ); ?></h4>
				<ul style="list-style: disc; margin-left: 1.4em;">
					<li><?php esc_html_e( 'Do not add other people to the Cloud project: anyone on it can see the Client Secret.', 'if-reviews' ); ?></li>
					<li><?php esc_html_e( 'Now and then, check myaccount.google.com/permissions for the account you connected, and remove the app there when a site is retired. Disconnecting here does the same.', 'if-reviews' ); ?></li>
				</ul>
			</details>

			<form method="post" action="<?php echo esc_url( $admin_post ); ?>">
				<input type="hidden" name="action" value="if_reviews_google_credentials">
				<?php wp_nonce_field( 'if_reviews_google_credentials' ); ?>
				<table class="form-table" role="presentation" style="max-width: 760px;">
					<tr>
						<th scope="row"><?php esc_html_e( 'Redirect URI', 'if-reviews' ); ?></th>
						<td>
							<code style="user-select: all;"><?php echo esc_html( if_reviews_google_redirect_uri() ); ?></code>
							<p class="description"><?php esc_html_e( 'Paste this into the OAuth client\'s authorised redirect URIs, exactly as shown.', 'if-reviews' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="if_google_client_id"><?php esc_html_e( 'Client ID', 'if-reviews' ); ?></label></th>
						<td>
							<?php if ( defined( 'IF_REVIEWS_GOOGLE_CLIENT_ID' ) && '' !== (string) IF_REVIEWS_GOOGLE_CLIENT_ID ) : ?>
								<p><?php esc_html_e( 'Set in wp-config.php (IF_REVIEWS_GOOGLE_CLIENT_ID).', 'if-reviews' ); ?></p>
							<?php else : ?>
								<input type="text" id="if_google_client_id" name="client_id" class="large-text code" value="<?php echo esc_attr( $g['client_id'] ); ?>" autocomplete="off">
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="if_google_client_secret"><?php esc_html_e( 'Client Secret', 'if-reviews' ); ?></label></th>
						<td>
							<?php if ( defined( 'IF_REVIEWS_GOOGLE_CLIENT_SECRET' ) && '' !== (string) IF_REVIEWS_GOOGLE_CLIENT_SECRET ) : ?>
								<p><?php esc_html_e( 'Set in wp-config.php (IF_REVIEWS_GOOGLE_CLIENT_SECRET).', 'if-reviews' ); ?></p>
							<?php else : ?>
								<input type="password" id="if_google_client_secret" name="client_secret" class="large-text code" value="" placeholder="<?php echo '' !== $g['client_secret'] ? esc_attr__( 'Saved. Leave blank to keep it.', 'if-reviews' ) : ''; ?>" autocomplete="new-password">
							<?php endif; ?>
							<p class="description"><?php esc_html_e( 'The Client ID, Client Secret and Google connection are stored encrypted, with a key from this site\'s wp-config.php, so a copy of the database alone cannot reveal them. To keep the ID and secret out of the database entirely, define IF_REVIEWS_GOOGLE_CLIENT_ID and IF_REVIEWS_GOOGLE_CLIENT_SECRET in wp-config.php.', 'if-reviews' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button( $has_creds ? __( 'Save', 'if-reviews' ) : __( 'Save and continue', 'if-reviews' ), 'secondary', 'submit', false ); ?>
			</form>

			<h2 style="margin-top: 32px;"><?php esc_html_e( 'Step 2: Connect', 'if-reviews' ); ?></h2>
			<?php if ( $has_creds ) : ?>
				<p style="max-width: 760px;"><?php esc_html_e( 'Google will ask you to sign in as the profile\'s owner or manager and allow this site to read its reviews. If you see "Google hasn\'t verified this app", that is your own app: click Advanced, then "Go to" your app name.', 'if-reviews' ); ?></p>
				<form method="post" action="<?php echo esc_url( $admin_post ); ?>">
					<input type="hidden" name="action" value="if_reviews_google_connect">
					<?php wp_nonce_field( 'if_reviews_google_connect' ); ?>
					<?php submit_button( __( 'Connect to Google', 'if-reviews' ), 'primary', 'submit', false ); ?>
				</form>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'Save a Client ID and Client Secret first.', 'if-reviews' ); ?></p>
			<?php endif; ?>
		<?php endif; ?>

		<hr style="margin: 28px 0; max-width: 760px;">
		<h2><?php esc_html_e( 'How the sync treats your reviews', 'if-reviews' ); ?></h2>
		<ul style="max-width: 760px; list-style: disc; margin-left: 1.4em;">
			<li><?php esc_html_e( 'On the first sync, reviews already on this site with the source "Google" are matched to your profile by reviewer name, rating and text. You approve each match in the preview, and matched reviews are linked, not imported again.', 'if-reviews' ); ?></li>
			<li><?php esc_html_e( 'Once a review is linked or imported it is tracked by Google\'s review ID, so later edits on either side never create a copy. "Sync now" and the daily sync import new Google reviews, and link one automatically when it closely matches an unlinked review here. Use "Preview sync" if you want to check first.', 'if-reviews' ); ?></li>
			<li><?php esc_html_e( 'Linked reviews take Google\'s version of the reviewer name, rating, text and date, now and whenever the reviewer edits them. Hidden, pinned and category settings are yours and are never touched.', 'if-reviews' ); ?></li>
			<li><?php esc_html_e( 'A review that disappears from Google is kept here and marked "No longer on Google" in the Reviews list. You decide whether to keep, hide or delete it.', 'if-reviews' ); ?></li>
			<li><?php esc_html_e( 'Every review on the profile is imported, including low ratings. Hide any you do not want shown; they still count in the summary header, like reviews entered by hand.', 'if-reviews' ); ?></li>
		</ul>
	</div>
	<?php
}

/**
 * Show the result of the last sync.
 *
 * @param array $g Settings.
 */
function if_reviews_google_render_last_result( $g ) {

	if ( empty( $g['last_sync'] ) ) {
		echo '<p class="description">' . esc_html__( 'Not synced yet.', 'if-reviews' ) . '</p>';
		return;
	}

	$r = wp_parse_args( (array) $g['last_result'], array( 'imported' => 0, 'updated' => 0, 'linked' => 0, 'missing' => 0, 'total' => 0, 'error' => '' ) );

	echo '<p>' . esc_html(
		sprintf(
			/* translators: %s: date and time */
			__( 'Last sync: %s.', 'if-reviews' ),
			wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $g['last_sync'] )
		)
	) . ' ';

	if ( '' !== $r['error'] ) {
		echo '<span style="color:#b32d2e">' . esc_html( $r['error'] ) . '</span>';
	} else {
		echo esc_html(
			sprintf(
				/* translators: 1: total on Google, 2: imported, 3: updated, 4: linked, 5: no longer on Google */
				__( '%1$d reviews on Google: %2$d imported, %3$d updated, %4$d linked to existing reviews, %5$d no longer on Google.', 'if-reviews' ),
				(int) $r['total'],
				(int) $r['imported'],
				(int) $r['updated'],
				(int) $r['linked'],
				(int) $r['missing']
			)
		);
	}

	echo '</p>';
}

/**
 * Location chooser, shown after OAuth when the account has several locations.
 */
function if_reviews_google_render_location_picker() {

	$locations = get_transient( 'if_reviews_google_locations_' . get_current_user_id() );

	if ( ! is_array( $locations ) ) {
		$locations = if_reviews_google_list_locations();

		if ( is_wp_error( $locations ) ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html( $locations->get_error_message() ) . '</p></div>';
			if_reviews_google_render_disconnect_only();
			return;
		}

		set_transient( 'if_reviews_google_locations_' . get_current_user_id(), $locations, 600 );
	}

	if ( empty( $locations ) ) {
		echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Google returned no business locations for this account. Sign in with an account that owns or manages the profile.', 'if-reviews' ) . '</p></div>';
		if_reviews_google_render_disconnect_only();
		return;
	}
	?>
	<h2><?php esc_html_e( 'Step 3: Choose the location', 'if-reviews' ); ?></h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="if_reviews_google_location">
		<?php wp_nonce_field( 'if_reviews_google_location' ); ?>
		<select name="location" style="min-width: 360px;">
			<?php foreach ( $locations as $index => $loc ) : ?>
				<option value="<?php echo esc_attr( $index ); ?>">
					<?php echo esc_html( $loc['title'] . ( '' !== $loc['address'] ? ' — ' . $loc['address'] : '' ) ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php submit_button( __( 'Use this location', 'if-reviews' ), 'primary', 'submit', false ); ?>
	</form>
	<?php
	if_reviews_google_render_disconnect_only();
}

/**
 * A lone disconnect button for half-finished states.
 */
function if_reviews_google_render_disconnect_only() {
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 12px;">
		<input type="hidden" name="action" value="if_reviews_google_disconnect">
		<?php wp_nonce_field( 'if_reviews_google_disconnect' ); ?>
		<?php submit_button( __( 'Start again', 'if-reviews' ), 'link-delete', 'submit', false ); ?>
	</form>
	<?php
}

/* ------------------------------------------------------------------
 * Form handlers
 * ---------------------------------------------------------------- */

/**
 * Shared guard for every handler.
 *
 * @param string $action Nonce action.
 */
function if_reviews_google_guard( $action ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do that.', 'if-reviews' ) );
	}
	check_admin_referer( $action );
}

/**
 * Save Client ID and Client Secret.
 */
function if_reviews_google_handle_credentials() {
	if_reviews_google_guard( 'if_reviews_google_credentials' );

	$id     = isset( $_POST['client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['client_id'] ) ) : '';
	$secret = isset( $_POST['client_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['client_secret'] ) ) : '';

	// The saved secret is never sent back to the page, so a blank field keeps it.
	if ( '' === $secret ) {
		$secret = if_reviews_google_get()['client_secret'];
	}

	// Values defined in wp-config.php win.
	$constants = if_reviews_google_constants();
	$id        = isset( $constants['client_id'] ) ? $constants['client_id'] : $id;
	$secret    = isset( $constants['client_secret'] ) ? $constants['client_secret'] : $secret;

	if_reviews_google_update( array( 'client_id' => $id, 'client_secret' => $secret, 'key_changed' => 0 ) );

	if ( '' === $id || '' === $secret ) {
		if_reviews_google_notice( 'warning', __( 'Both the Client ID and the Client Secret are needed before connecting.', 'if-reviews' ) );
	} else {
		if_reviews_google_notice( 'success', __( 'Credentials saved. Now connect to Google.', 'if-reviews' ) );
	}

	wp_safe_redirect( if_reviews_google_page_url() );
	exit;
}
add_action( 'admin_post_if_reviews_google_credentials', 'if_reviews_google_handle_credentials' );

/**
 * Send the owner to Google's consent screen.
 */
function if_reviews_google_handle_connect() {
	if_reviews_google_guard( 'if_reviews_google_connect' );

	$g = if_reviews_google_get();

	if ( '' === $g['client_id'] || '' === $g['client_secret'] ) {
		if_reviews_google_notice( 'error', __( 'Save a Client ID and Client Secret first.', 'if-reviews' ) );
		wp_safe_redirect( if_reviews_google_page_url() );
		exit;
	}

	$state = wp_generate_password( 32, false, false );
	set_transient( 'if_reviews_google_state_' . get_current_user_id(), $state, 600 );

	$url = add_query_arg(
		array_map(
			'rawurlencode',
			array(
			'client_id'              => $g['client_id'],
			'redirect_uri'           => if_reviews_google_redirect_uri(),
			'response_type'          => 'code',
			'scope'                  => IF_REVIEWS_GOOGLE_SCOPE,
			'access_type'            => 'offline',
			'prompt'                 => 'consent',
			'include_granted_scopes' => 'true',
			'state'                  => $state,
			)
		),
		IF_REVIEWS_GOOGLE_AUTH
	);

	wp_redirect( $url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- external OAuth endpoint by design.
	exit;
}
add_action( 'admin_post_if_reviews_google_connect', 'if_reviews_google_handle_connect' );

/**
 * Google sends the owner back here with a code (or an error).
 */
function if_reviews_google_handle_callback() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do that.', 'if-reviews' ) );
	}

	$state_key = 'if_reviews_google_state_' . get_current_user_id();
	$expected  = get_transient( $state_key );
	delete_transient( $state_key );

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- the OAuth state parameter is the CSRF token here.
	$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
	$code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
	$error = isset( $_GET['error'] ) ? sanitize_text_field( wp_unslash( $_GET['error'] ) ) : '';
	// phpcs:enable

	if ( ! $expected || ! hash_equals( (string) $expected, $state ) ) {
		if_reviews_google_notice( 'error', __( 'The sign-in could not be verified. Please try connecting again.', 'if-reviews' ) );
		wp_safe_redirect( if_reviews_google_page_url() );
		exit;
	}

	if ( '' !== $error ) {
		/* translators: %s: error code from Google, such as access_denied */
		if_reviews_google_notice( 'error', sprintf( __( 'Google did not grant access (%s).', 'if-reviews' ), sanitize_key( $error ) ) );
		wp_safe_redirect( if_reviews_google_page_url() );
		exit;
	}

	if ( '' === $code ) {
		if_reviews_google_notice( 'error', __( 'The sign-in could not be verified. Please try connecting again.', 'if-reviews' ) );
		wp_safe_redirect( if_reviews_google_page_url() );
		exit;
	}

	$g     = if_reviews_google_get();
	$token = if_reviews_google_token_request(
		array(
			'code'          => $code,
			'client_id'     => $g['client_id'],
			'client_secret' => $g['client_secret'],
			'redirect_uri'  => if_reviews_google_redirect_uri(),
			'grant_type'    => 'authorization_code',
		)
	);

	if ( is_wp_error( $token ) ) {
		if_reviews_google_notice( 'error', $token->get_error_message() );
		wp_safe_redirect( if_reviews_google_page_url() );
		exit;
	}

	if ( empty( $token['refresh_token'] ) ) {
		if_reviews_google_notice( 'error', __( 'Google did not give this site lasting access. Remove the app at myaccount.google.com/permissions, then click Connect to Google again.', 'if-reviews' ) );
		wp_safe_redirect( if_reviews_google_page_url() );
		exit;
	}

	if_reviews_google_update(
		array(
			'refresh_token'   => $token['refresh_token'],
			'access_token'    => $token['access_token'],
			'access_expires'  => time() + (int) $token['expires_in'],
			'needs_reconnect' => 0,
		)
	);

	// Reconnecting: keep the location and everything synced so far.
	if ( '' !== $g['location'] ) {
		/* translators: %s: business location name */
		if_reviews_google_notice( 'success', sprintf( __( 'Reconnected to "%s". Syncing will carry on as before.', 'if-reviews' ), '' !== $g['location_title'] ? $g['location_title'] : $g['location'] ) );
		wp_safe_redirect( if_reviews_google_page_url() );
		exit;
	}

	// Pick the location now when there is only one.
	$locations = if_reviews_google_list_locations();

	if ( ! is_wp_error( $locations ) && 1 === count( $locations ) ) {
		if_reviews_google_select_location( $locations[0] );
		/* translators: %s: business location name */
		if_reviews_google_notice( 'success', sprintf( __( 'Connected to "%s". Click Preview sync to see what the first sync would do.', 'if-reviews' ), $locations[0]['title'] ) );
	} else {
		if ( ! is_wp_error( $locations ) ) {
			set_transient( 'if_reviews_google_locations_' . get_current_user_id(), $locations, 600 );
		}
		if_reviews_google_notice( 'success', __( 'Signed in to Google. Choose the location to import from.', 'if-reviews' ) );
	}

	wp_safe_redirect( if_reviews_google_page_url() );
	exit;
}
add_action( 'admin_post_if_reviews_google_callback', 'if_reviews_google_handle_callback' );
add_action( 'admin_post_nopriv_if_reviews_google_callback', 'auth_redirect' );

/**
 * Store the chosen location.
 */
function if_reviews_google_handle_location() {
	if_reviews_google_guard( 'if_reviews_google_location' );

	$locations = get_transient( 'if_reviews_google_locations_' . get_current_user_id() );
	$index     = isset( $_POST['location'] ) ? (int) $_POST['location'] : -1;

	if ( ! is_array( $locations ) || ! isset( $locations[ $index ] ) ) {
		if_reviews_google_notice( 'error', __( 'That location list has expired. Please choose again.', 'if-reviews' ) );
		delete_transient( 'if_reviews_google_locations_' . get_current_user_id() );
	} else {
		if_reviews_google_select_location( $locations[ $index ] );
		delete_transient( 'if_reviews_google_locations_' . get_current_user_id() );
		/* translators: %s: business location name */
		if_reviews_google_notice( 'success', sprintf( __( 'Connected to "%s". Click Preview sync to see what the first sync would do.', 'if-reviews' ), $locations[ $index ]['title'] ) );
	}

	wp_safe_redirect( if_reviews_google_page_url() );
	exit;
}
add_action( 'admin_post_if_reviews_google_location', 'if_reviews_google_handle_location' );

/**
 * Persist a location choice.
 *
 * @param array $loc One entry from if_reviews_google_list_locations().
 */
function if_reviews_google_select_location( $loc ) {
	if_reviews_google_update(
		array(
			'account'         => $loc['account'],
			'location'        => $loc['location'],
			'location_title'  => $loc['title'],
			'first_sync_done' => 0,
			'skipped'         => array(),
			'last_sync'       => 0,
			'last_result'     => array(),
		)
	);
}

/**
 * Save the auto-sync switch.
 */
function if_reviews_google_handle_settings() {
	if_reviews_google_guard( 'if_reviews_google_settings' );

	$auto = ! empty( $_POST['auto_sync'] ) ? 1 : 0;
	if_reviews_google_update( array( 'auto_sync' => $auto ) );
	if_reviews_google_schedule( $auto );

	if_reviews_google_notice( 'success', $auto ? __( 'Automatic sync is on. It runs about once a day when the site has visitors, starting after you have applied your first preview.', 'if-reviews' ) : __( 'Automatic sync is off.', 'if-reviews' ) );
	wp_safe_redirect( if_reviews_google_page_url() );
	exit;
}
add_action( 'admin_post_if_reviews_google_settings', 'if_reviews_google_handle_settings' );

/**
 * Ask Google to revoke a refresh token. Failures are ignored: the token is
 * forgotten here either way, and the owner can remove access in their account.
 *
 * @param string $token Refresh token.
 */
function if_reviews_google_revoke( $token ) {
	if ( '' === (string) $token ) {
		return;
	}
	wp_remote_post(
		IF_REVIEWS_GOOGLE_REVOKE,
		array( 'timeout' => 15, 'body' => array( 'token' => $token ) )
	);
}

/**
 * Revoke and forget the connection. Credentials and reviews stay.
 */
function if_reviews_google_handle_disconnect() {
	if_reviews_google_guard( 'if_reviews_google_disconnect' );

	$g = if_reviews_google_get();

	if_reviews_google_revoke( $g['refresh_token'] );

	if_reviews_google_update(
		array(
			'refresh_token'   => '',
			'access_token'    => '',
			'access_expires'  => 0,
			'account'         => '',
			'location'        => '',
			'location_title'  => '',
			'auto_sync'       => 0,
			'needs_reconnect' => 0,
			'first_sync_done' => 0,
		)
	);
	if_reviews_google_schedule( 0 );
	delete_transient( 'if_reviews_google_locations_' . get_current_user_id() );
	delete_transient( 'if_reviews_google_plan_' . get_current_user_id() );

	if_reviews_google_notice( 'success', __( 'Disconnected. Imported reviews remain on this site.', 'if-reviews' ) );
	wp_safe_redirect( if_reviews_google_page_url() );
	exit;
}
add_action( 'admin_post_if_reviews_google_disconnect', 'if_reviews_google_handle_disconnect' );

/**
 * Fetch reviews and build a plan for the owner to approve.
 */
function if_reviews_google_handle_preview() {
	if_reviews_google_guard( 'if_reviews_google_preview' );

	$reviews = if_reviews_google_fetch_reviews();

	if ( is_wp_error( $reviews ) ) {
		if_reviews_google_notice( 'error', $reviews->get_error_message() );
		wp_safe_redirect( if_reviews_google_page_url() );
		exit;
	}

	$plan = if_reviews_google_build_plan( $reviews );
	set_transient( 'if_reviews_google_plan_' . get_current_user_id(), $plan, HOUR_IN_SECONDS );

	wp_safe_redirect( if_reviews_google_page_url( array( 'preview' => 1 ) ) );
	exit;
}
add_action( 'admin_post_if_reviews_google_preview', 'if_reviews_google_handle_preview' );

/**
 * Apply the approved plan.
 */
function if_reviews_google_handle_apply() {
	if_reviews_google_guard( 'if_reviews_google_apply' );

	$key  = 'if_reviews_google_plan_' . get_current_user_id();
	$plan = get_transient( $key );

	if ( ! is_array( $plan ) || empty( $plan['reviews'] ) ) {
		if_reviews_google_notice( 'error', __( 'The preview has expired. Click Preview sync to run it again.', 'if-reviews' ) );
		wp_safe_redirect( if_reviews_google_page_url() );
		exit;
	}

	$decisions = array();

	if ( isset( $_POST['decision'] ) && is_array( $_POST['decision'] ) ) {
		foreach ( wp_unslash( $_POST['decision'] ) as $review_id => $decision ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised per element below.
			$decisions[ sanitize_text_field( (string) $review_id ) ] = sanitize_text_field( (string) $decision );
		}
	}

	$result = if_reviews_google_apply_plan( $plan, $decisions );
	delete_transient( $key );

	if_reviews_google_record_result( $result );
	if_reviews_google_notice( 'success', if_reviews_google_describe_result( $result ) );
	wp_safe_redirect( if_reviews_google_page_url() );
	exit;
}
add_action( 'admin_post_if_reviews_google_apply', 'if_reviews_google_handle_apply' );

/**
 * Sync without a preview (new reviews are imported, strong matches linked).
 */
function if_reviews_google_handle_sync() {
	if_reviews_google_guard( 'if_reviews_google_sync' );

	$result = if_reviews_google_run_sync();

	if ( is_wp_error( $result ) ) {
		if_reviews_google_notice( 'error', $result->get_error_message() );
	} else {
		if_reviews_google_notice( 'success', if_reviews_google_describe_result( $result ) );
	}

	wp_safe_redirect( if_reviews_google_page_url() );
	exit;
}
add_action( 'admin_post_if_reviews_google_sync', 'if_reviews_google_handle_sync' );

/* ------------------------------------------------------------------
 * Preview table
 * ---------------------------------------------------------------- */

/**
 * Render the pending plan. Returns false when there is none.
 *
 * @return bool
 */
function if_reviews_google_render_preview() {

	$plan = get_transient( 'if_reviews_google_plan_' . get_current_user_id() );

	if ( ! is_array( $plan ) ) {
		echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'The preview has expired. Click Preview sync to run it again.', 'if-reviews' ) . '</p></div>';
		return false;
	}

	$reviews   = $plan['reviews'];
	$matches   = $plan['matches'];
	$existing  = $plan['existing'];
	$unmatched = $plan['unmatched_existing'];

	$counts = array( 'import' => 0, 'link' => 0, 'update' => 0, 'same' => 0, 'skip' => 0 );

	foreach ( $reviews as $r ) {
		$counts[ $r['default'] ]++;
	}
	?>
	<h2><?php esc_html_e( 'Preview', 'if-reviews' ); ?></h2>
	<p style="max-width: 760px;">
		<?php
		echo esc_html(
			sprintf(
				/* translators: 1: total, 2: to import, 3: to link, 4: to update, 5: unchanged */
				__( '%1$d reviews on Google. Proposed: import %2$d as new reviews, link %3$d to reviews already on this site, update %4$d that changed on Google, leave %5$d unchanged.', 'if-reviews' ),
				count( $reviews ),
				$counts['import'],
				$counts['link'],
				$counts['update'],
				$counts['same']
			)
		);
		?>
		<?php esc_html_e( 'Check the matches, change any action, then apply.', 'if-reviews' ); ?>
	</p>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="if_reviews_google_apply">
		<?php wp_nonce_field( 'if_reviews_google_apply' ); ?>

		<?php submit_button( __( 'Apply', 'if-reviews' ), 'primary', 'submit', false ); ?>
		<a class="button" href="<?php echo esc_url( if_reviews_google_page_url() ); ?>" style="margin-left: 6px;"><?php esc_html_e( 'Cancel', 'if-reviews' ); ?></a>

		<table class="widefat striped if-google-preview" style="margin-top: 12px;">
			<thead>
				<tr>
					<th style="width: 40%;"><?php esc_html_e( 'On Google', 'if-reviews' ); ?></th>
					<th style="width: 32%;"><?php esc_html_e( 'On this site', 'if-reviews' ); ?></th>
					<th><?php esc_html_e( 'Action', 'if-reviews' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
			// Every Google review on the site not yet linked, so any of them can be linked by hand.
			$linkable = array();
			foreach ( $existing as $post_id => $e ) {
				if ( '' === $e['google_id'] ) {
					$linkable[ $post_id ] = $e;
				}
			}
			uasort(
				$linkable,
				function ( $a, $b ) {
					return strcasecmp( $a['name'], $b['name'] );
				}
			);
			?>
			<?php foreach ( $reviews as $r ) : ?>
				<?php
				$candidates = isset( $matches[ $r['id'] ] ) ? $matches[ $r['id'] ] : array();
				$default    = $r['default'];
				$linked     = isset( $r['linked_post'] ) ? (int) $r['linked_post'] : 0;
				$others     = array_diff_key( $linkable, array_flip( wp_list_pluck( $candidates, 'post_id' ) ) );
				?>
				<tr>
					<td>
						<strong><?php echo esc_html( $r['name'] ); ?></strong>
						<span class="if-admin-stars" aria-hidden="true"><?php echo esc_html( str_repeat( '★', $r['rating'] ) . str_repeat( '☆', 5 - $r['rating'] ) ); ?></span>
						<span class="description"><?php echo esc_html( if_reviews_format_date( $r['date'] ) ); ?></span>
						<div class="description"><?php echo esc_html( if_reviews_google_excerpt( $r['text'] ) ); ?></div>
					</td>
					<td>
						<?php if ( $linked && isset( $existing[ $linked ] ) ) : ?>
							<?php if_reviews_google_render_existing_cell( $existing[ $linked ], 'update' === $default ? __( 'Linked, changed on Google', 'if-reviews' ) : __( 'Linked', 'if-reviews' ) ); ?>
						<?php elseif ( ! empty( $candidates ) ) : ?>
							<?php
							$best = $candidates[0];
							if_reviews_google_render_existing_cell(
								$existing[ $best['post_id'] ],
								$best['strong'] ? __( 'Looks like the same review', 'if-reviews' ) : __( 'Possibly the same review', 'if-reviews' )
							);
							?>
						<?php else : ?>
							<span class="if-admin-hidden">&mdash;</span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $linked ) : ?>
							<?php echo esc_html( 'update' === $default ? __( 'Update from Google', 'if-reviews' ) : __( 'No change', 'if-reviews' ) ); ?>
						<?php else : ?>
							<select name="decision[<?php echo esc_attr( $r['id'] ); ?>]">
								<option value="import" <?php selected( 'import', $default ); ?>><?php esc_html_e( 'Import as a new review', 'if-reviews' ); ?></option>
								<?php foreach ( $candidates as $c ) : ?>
									<?php $e = $existing[ $c['post_id'] ]; ?>
									<option value="link:<?php echo esc_attr( $c['post_id'] ); ?>" <?php selected( 'link' === $default && $c === $candidates[0] ); ?>>
										<?php
										echo esc_html(
											sprintf(
												/* translators: 1: reviewer name, 2: review date */
												__( 'Link to existing: %1$s (%2$s)', 'if-reviews' ),
												$e['name'],
												if_reviews_format_date( $e['date'] )
											)
										);
										?>
									</option>
								<?php endforeach; ?>
								<?php if ( ! empty( $others ) ) : ?>
									<optgroup label="<?php esc_attr_e( 'Link to another Google review on this site', 'if-reviews' ); ?>">
										<?php foreach ( $others as $post_id => $e ) : ?>
											<option value="link:<?php echo esc_attr( $post_id ); ?>">
												<?php
												echo esc_html(
													sprintf(
														/* translators: 1: reviewer name, 2: star rating, 3: review date */
														__( '%1$s, %2$d stars (%3$s)', 'if-reviews' ),
														$e['name'],
														$e['rating'],
														'' !== $e['date'] ? if_reviews_format_date( $e['date'] ) : __( 'no date', 'if-reviews' )
													)
												);
												?>
											</option>
										<?php endforeach; ?>
									</optgroup>
								<?php endif; ?>
								<option value="skip" <?php selected( 'skip', $default ); ?>><?php esc_html_e( 'Skip (do not import)', 'if-reviews' ); ?></option>
							</select>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( ! empty( $unmatched ) ) : ?>
			<h3 style="margin-top: 24px;"><?php esc_html_e( 'Google reviews on this site that were not found on the profile', 'if-reviews' ); ?></h3>
			<p class="description" style="max-width: 760px;"><?php esc_html_e( 'These keep their source "Google" and are left alone. They may have been removed from Google, or entered under a different name; you can link one above by choosing it in the Action column.', 'if-reviews' ); ?></p>
			<ul style="list-style: disc; margin-left: 1.4em;">
				<?php foreach ( $unmatched as $post_id ) : ?>
					<?php $e = $existing[ $post_id ]; ?>
					<li>
						<a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>"><?php echo esc_html( $e['name'] ); ?></a>,
						<?php echo esc_html( if_reviews_format_date( $e['date'] ) ); ?>,
						<?php echo esc_html( if_reviews_google_excerpt( $e['text'], 90 ) ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<p style="margin-top: 16px;">
			<?php submit_button( __( 'Apply', 'if-reviews' ), 'primary', 'submit', false ); ?>
			<a class="button" href="<?php echo esc_url( if_reviews_google_page_url() ); ?>" style="margin-left: 6px;"><?php esc_html_e( 'Cancel', 'if-reviews' ); ?></a>
		</p>
	</form>
	<?php
	return true;
}

/**
 * One existing review, summarised for the preview.
 *
 * @param array  $e     Existing review summary.
 * @param string $label Short status label.
 */
function if_reviews_google_render_existing_cell( $e, $label ) {
	?>
	<em class="description"><?php echo esc_html( $label ); ?></em><br>
	<a href="<?php echo esc_url( get_edit_post_link( $e['id'] ) ); ?>"><?php echo esc_html( $e['name'] ); ?></a>
	<span class="if-admin-stars" aria-hidden="true"><?php echo esc_html( str_repeat( '★', $e['rating'] ) . str_repeat( '☆', 5 - $e['rating'] ) ); ?></span>
	<span class="description"><?php echo esc_html( if_reviews_format_date( $e['date'] ) ); ?></span>
	<div class="description"><?php echo esc_html( if_reviews_google_excerpt( $e['text'] ) ); ?></div>
	<?php
}

/**
 * Shorten text for tables.
 *
 * @param string $text   Text.
 * @param int    $length Characters.
 * @return string
 */
function if_reviews_google_excerpt( $text, $length = 160 ) {
	$text = preg_replace( '/\s+/u', ' ', trim( (string) $text ) );

	if ( '' === $text ) {
		return __( '(rating only, no text)', 'if-reviews' );
	}

	return mb_strlen( $text ) > $length ? mb_substr( $text, 0, $length - 1 ) . '…' : $text;
}

/* ------------------------------------------------------------------
 * Google API
 * ---------------------------------------------------------------- */

/**
 * POST to the token endpoint.
 *
 * @param array $body Form fields.
 * @return array|WP_Error Decoded response.
 */
function if_reviews_google_token_request( $body ) {

	$response = wp_remote_post(
		IF_REVIEWS_GOOGLE_TOKEN,
		array(
			'timeout' => 20,
			'body'    => $body,
		)
	);

	if ( is_wp_error( $response ) ) {
		/* translators: %s: error message */
		return new WP_Error( 'if_google_http', sprintf( __( 'Could not reach Google: %s', 'if-reviews' ), $response->get_error_message() ) );
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	$code = (int) wp_remote_retrieve_response_code( $response );

	if ( 200 !== $code || ! is_array( $data ) || empty( $data['access_token'] ) ) {
		$detail = is_array( $data ) && ! empty( $data['error'] ) ? $data['error'] . ( ! empty( $data['error_description'] ) ? ': ' . $data['error_description'] : '' ) : (string) $code;
		/* translators: %s: error detail from Google */
		return new WP_Error( 'if_google_token', sprintf( __( 'Google rejected the sign-in (%s). Check the Client ID, Client Secret and redirect URI.', 'if-reviews' ), $detail ), array( 'detail' => $detail ) );
	}

	return $data;
}

/**
 * A valid access token, refreshing when needed.
 *
 * @return string|WP_Error
 */
function if_reviews_google_access_token() {

	$g = if_reviews_google_get();

	if ( '' === $g['refresh_token'] ) {
		return new WP_Error( 'if_google_not_connected', __( 'Not connected to Google.', 'if-reviews' ) );
	}

	if ( '' !== $g['access_token'] && (int) $g['access_expires'] > time() + 60 ) {
		return $g['access_token'];
	}

	$token = if_reviews_google_token_request(
		array(
			'refresh_token' => $g['refresh_token'],
			'client_id'     => $g['client_id'],
			'client_secret' => $g['client_secret'],
			'grant_type'    => 'refresh_token',
		)
	);

	if ( is_wp_error( $token ) ) {
		$detail = $token->get_error_data();
		if ( is_array( $detail ) && isset( $detail['detail'] ) && false !== strpos( $detail['detail'], 'invalid_grant' ) ) {
			if_reviews_google_update( array( 'needs_reconnect' => 1, 'access_token' => '', 'access_expires' => 0 ) );
			return new WP_Error( 'if_google_reconnect', __( 'Google no longer accepts this site\'s connection (it may have expired or been revoked). Click Reconnect on the Google import page.', 'if-reviews' ) );
		}
		return $token;
	}

	if_reviews_google_update(
		array(
			'access_token'    => $token['access_token'],
			'access_expires'  => time() + (int) $token['expires_in'],
			'needs_reconnect' => 0,
		)
	);

	return $token['access_token'];
}

/**
 * Authenticated GET returning decoded JSON.
 *
 * @param string $url   Endpoint.
 * @param array  $query Query args.
 * @return array|WP_Error
 */
function if_reviews_google_api_get( $url, $query = array() ) {

	// Account and location names come from Google; refuse anything odd in the path.
	if ( ! preg_match( '#^https://[a-z]+\.googleapis\.com/v\d+/[A-Za-z0-9_/-]+$#', $url ) ) {
		return new WP_Error( 'if_google_bad_url', __( 'Unexpected account or location name from Google.', 'if-reviews' ) );
	}

	$token = if_reviews_google_access_token();

	if ( is_wp_error( $token ) ) {
		return $token;
	}

	$response = wp_remote_get(
		add_query_arg( array_map( 'rawurlencode', $query ), $url ),
		array(
			'timeout' => 30,
			'headers' => array( 'Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json' ),
		)
	);

	if ( is_wp_error( $response ) ) {
		/* translators: %s: error message */
		return new WP_Error( 'if_google_http', sprintf( __( 'Could not reach Google: %s', 'if-reviews' ), $response->get_error_message() ) );
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$data = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 200 !== $code ) {
		$message = is_array( $data ) && isset( $data['error']['message'] ) ? $data['error']['message'] : '';

		if ( 403 === $code && '' === $message ) {
			$message = __( 'Permission denied. Check that the APIs are enabled and the project has been approved for Business Profile access.', 'if-reviews' );
		}

		/* translators: 1: HTTP status, 2: message from Google */
		return new WP_Error( 'if_google_api', sprintf( __( 'Google returned an error (%1$d): %2$s', 'if-reviews' ), $code, $message ) );
	}

	return is_array( $data ) ? $data : array();
}

/**
 * Every location the signed-in account can manage.
 *
 * @return array|WP_Error List of [account, location, title, address].
 */
function if_reviews_google_list_locations() {

	$accounts = if_reviews_google_api_get( IF_REVIEWS_GOOGLE_ACCOUNTS, array( 'pageSize' => 20 ) );

	if ( is_wp_error( $accounts ) ) {
		return $accounts;
	}

	$out = array();

	foreach ( (array) ( isset( $accounts['accounts'] ) ? $accounts['accounts'] : array() ) as $account ) {

		if ( empty( $account['name'] ) ) {
			continue;
		}

		$page_token = '';
		$pages      = 0;

		do {
			$query = array( 'readMask' => 'name,title,storefrontAddress', 'pageSize' => 100 );

			if ( '' !== $page_token ) {
				$query['pageToken'] = $page_token;
			}

			$locations = if_reviews_google_api_get( IF_REVIEWS_GOOGLE_LOCATION . $account['name'] . '/locations', $query );

			if ( is_wp_error( $locations ) ) {
				return $locations;
			}

			foreach ( (array) ( isset( $locations['locations'] ) ? $locations['locations'] : array() ) as $loc ) {

				if ( empty( $loc['name'] ) ) {
					continue;
				}

				$address = '';

				if ( ! empty( $loc['storefrontAddress'] ) && is_array( $loc['storefrontAddress'] ) ) {
					$parts = array();
					foreach ( array( 'addressLines', 'locality', 'administrativeArea' ) as $field ) {
						if ( empty( $loc['storefrontAddress'][ $field ] ) ) {
							continue;
						}
						$parts[] = is_array( $loc['storefrontAddress'][ $field ] ) ? implode( ', ', $loc['storefrontAddress'][ $field ] ) : $loc['storefrontAddress'][ $field ];
					}
					$address = implode( ', ', $parts );
				}

				$out[] = array(
					'account'  => sanitize_text_field( $account['name'] ),
					'location' => sanitize_text_field( $loc['name'] ),
					'title'    => sanitize_text_field( isset( $loc['title'] ) ? $loc['title'] : $loc['name'] ),
					'address'  => sanitize_text_field( $address ),
				);
			}

			$page_token = isset( $locations['nextPageToken'] ) ? (string) $locations['nextPageToken'] : '';
			$pages++;
		} while ( '' !== $page_token && $pages < 20 );
	}

	return $out;
}

/**
 * All reviews of the connected location, normalised.
 *
 * @return array|WP_Error Each: id, name, rating, text, date (Y-m-d), updated (string).
 */
function if_reviews_google_fetch_reviews() {

	$g = if_reviews_google_get();

	if ( ! if_reviews_google_is_connected() ) {
		return new WP_Error( 'if_google_not_connected', __( 'Not connected to Google.', 'if-reviews' ) );
	}

	$stars = array( 'ONE' => 1, 'TWO' => 2, 'THREE' => 3, 'FOUR' => 4, 'FIVE' => 5 );
	$out   = array();
	$token = '';
	$pages = 0;

	do {
		$query = array( 'pageSize' => 50 );

		if ( '' !== $token ) {
			$query['pageToken'] = $token;
		}

		$data = if_reviews_google_api_get( IF_REVIEWS_GOOGLE_REVIEWS . $g['account'] . '/' . $g['location'] . '/reviews', $query );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		foreach ( (array) ( isset( $data['reviews'] ) ? $data['reviews'] : array() ) as $review ) {

			if ( empty( $review['reviewId'] ) ) {
				continue;
			}

			$rating = isset( $review['starRating'], $stars[ $review['starRating'] ] ) ? $stars[ $review['starRating'] ] : 0;

			if ( $rating < 1 ) {
				continue;
			}

			$name = isset( $review['reviewer']['displayName'] ) ? sanitize_text_field( $review['reviewer']['displayName'] ) : '';

			if ( '' === $name ) {
				$name = __( 'A Google user', 'if-reviews' );
			}

			$created = isset( $review['createTime'] ) ? strtotime( $review['createTime'] ) : false;
			$updated = isset( $review['updateTime'] ) ? (string) $review['updateTime'] : ( isset( $review['createTime'] ) ? (string) $review['createTime'] : '' );

			$out[] = array(
				'id'      => sanitize_text_field( $review['reviewId'] ),
				'name'    => $name,
				'rating'  => $rating,
				'text'    => sanitize_textarea_field( isset( $review['comment'] ) ? $review['comment'] : '' ),
				'date'    => $created ? wp_date( 'Y-m-d', $created ) : '',
				'updated' => sanitize_text_field( $updated ),
			);
		}

		$token = isset( $data['nextPageToken'] ) ? (string) $data['nextPageToken'] : '';
		$pages++;
	} while ( '' !== $token && $pages < 200 );

	/**
	 * Filter the normalised reviews fetched from Google before matching.
	 *
	 * @param array $out Reviews.
	 */
	return apply_filters( 'if_reviews_google_fetched_reviews', $out );
}

/* ------------------------------------------------------------------
 * Matching
 * ---------------------------------------------------------------- */

/**
 * Summaries of every local review with the source "Google".
 *
 * @return array Keyed by post ID: id, name, rating, text, date, google_id, updated.
 */
function if_reviews_google_local_reviews() {

	$ids = get_posts(
		array(
			'post_type'      => 'if_review',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- admin-only, bounded by the review count.
				array( 'key' => '_if_source', 'value' => 'google' ),
			),
		)
	);

	update_meta_cache( 'post', $ids );

	$out = array();

	foreach ( $ids as $id ) {
		$out[ $id ] = array(
			'id'        => (int) $id,
			'name'      => (string) get_post_meta( $id, '_if_reviewer_name', true ),
			'rating'    => (int) get_post_meta( $id, '_if_rating', true ),
			'text'      => (string) get_post_meta( $id, '_if_review_text', true ),
			'date'      => (string) get_post_meta( $id, '_if_review_date', true ),
			'google_id' => (string) get_post_meta( $id, '_if_google_review_id', true ),
			'updated'   => (string) get_post_meta( $id, '_if_google_updated', true ),
		);
	}

	return $out;
}

/**
 * Lower-case, single-spaced, punctuation-free text for comparison.
 *
 * @param string $value Text.
 * @return string
 */
function if_reviews_google_normalise( $value ) {
	$value = mb_strtolower( (string) $value );
	$value = preg_replace( '/[^\p{L}\p{N}\s]/u', '', $value );
	return trim( preg_replace( '/\s+/u', ' ', $value ) );
}

/**
 * Do two reviewer names refer to the same person?
 *
 * Exact after normalising, or the same first name with one side's
 * surname abbreviated ("Jane Smith" and "Jane S.").
 *
 * @param string $a Name.
 * @param string $b Name.
 * @return bool
 */
function if_reviews_google_names_match( $a, $b ) {

	$a = if_reviews_google_normalise( $a );
	$b = if_reviews_google_normalise( $b );

	if ( '' === $a || '' === $b ) {
		return false;
	}

	if ( $a === $b ) {
		return true;
	}

	$pa = explode( ' ', $a );
	$pb = explode( ' ', $b );

	if ( $pa[0] !== $pb[0] ) {
		return false;
	}

	if ( 1 === count( $pa ) || 1 === count( $pb ) ) {
		return true; // "Jane" and "Jane Smith".
	}

	$la = end( $pa );
	$lb = end( $pb );

	return 0 === strpos( $la, $lb ) || 0 === strpos( $lb, $la );
}

/**
 * How alike two reviews are, 0..1.
 *
 * @param array $google Normalised Google review.
 * @param array $local  Local summary.
 * @return float
 */
function if_reviews_google_similarity( $google, $local ) {

	if ( ! if_reviews_google_names_match( $google['name'], $local['name'] ) ) {
		return 0.0;
	}

	$gt = if_reviews_google_normalise( $google['text'] );
	$lt = if_reviews_google_normalise( $local['text'] );

	$days = null;

	if ( '' !== $google['date'] && '' !== $local['date'] ) {
		$days = abs( ( strtotime( $google['date'] ) - strtotime( $local['date'] ) ) / DAY_IN_SECONDS );
	}

	if ( '' !== $gt && '' !== $lt ) {

		if ( $gt === $lt ) {
			$score = 1.0;
		} elseif ( ( mb_strlen( $gt ) >= 20 && false !== mb_strpos( $lt, $gt ) ) || ( mb_strlen( $lt ) >= 20 && false !== mb_strpos( $gt, $lt ) ) ) {
			$score = 0.92;
		} else {
			similar_text( mb_substr( $gt, 0, 1500 ), mb_substr( $lt, 0, 1500 ), $percent );
			$score = $percent / 100;
			if ( $score < 0.6 ) {
				$score = 0.0;
			}
		}
	} elseif ( '' === $gt && '' === $lt ) {
		// Rating-only reviews: name, rating and a close date is all there is.
		// Without a close date it is still worth offering as a possible match.
		$score = ( null !== $days && $days <= 45 ) ? 0.86 : 0.65;
	} else {
		$score = 0.3; // One has text, the other does not.
	}

	if ( $score <= 0 ) {
		return 0.0;
	}

	if ( $google['rating'] !== $local['rating'] ) {
		$score = min( $score, 0.55 );
	}

	if ( null !== $days ) {
		if ( $days <= 45 ) {
			$score = min( 1.0, $score + 0.04 );
		} elseif ( $days > 400 ) {
			$score -= 0.15;
		}
	}

	return max( 0.0, $score );
}

/**
 * Compare the fetched reviews with what is on the site.
 *
 * @param array $google_reviews From if_reviews_google_fetch_reviews().
 * @return array {
 *   reviews:            Google reviews with 'default' (import|link|update|same|skip) and 'linked_post'.
 *   matches:            Google ID => list of [post_id, score, strong], best first.
 *   existing:           Local summaries keyed by post ID.
 *   unmatched_existing: Local post IDs with no link and no candidate.
 * }
 */
function if_reviews_google_build_plan( $google_reviews ) {

	$g        = if_reviews_google_get();
	$skipped  = array_flip( (array) $g['skipped'] );
	$existing = if_reviews_google_local_reviews();
	$by_gid   = array();
	$unlinked = array();

	foreach ( $existing as $post_id => $e ) {
		if ( '' !== $e['google_id'] ) {
			$by_gid[ $e['google_id'] ] = $post_id;
		} else {
			$unlinked[ $post_id ] = $e;
		}
	}

	// Score every (Google review, unlinked local review) pair.
	$pairs = array();

	foreach ( $google_reviews as $gi => $review ) {

		if ( isset( $by_gid[ $review['id'] ] ) ) {
			continue;
		}

		foreach ( $unlinked as $post_id => $e ) {
			$score = if_reviews_google_similarity( $review, $e );
			if ( $score >= 0.5 ) {
				$pairs[] = array( 'gi' => $gi, 'post_id' => $post_id, 'score' => $score );
			}
		}
	}

	usort(
		$pairs,
		function ( $a, $b ) {
			return $b['score'] <=> $a['score'];
		}
	);

	// Greedy: each local review is offered as the best candidate once.
	$matches     = array();
	$claimed     = array();
	$claimed_gi  = array();

	foreach ( $pairs as $pair ) {
		$gid = $google_reviews[ $pair['gi'] ]['id'];

		if ( isset( $claimed[ $pair['post_id'] ] ) && $claimed[ $pair['post_id'] ] !== $gid ) {
			continue;
		}

		if ( ! isset( $matches[ $gid ] ) ) {
			$matches[ $gid ] = array();
		}

		if ( count( $matches[ $gid ] ) >= 3 ) {
			continue;
		}

		$matches[ $gid ][] = array(
			'post_id' => $pair['post_id'],
			'score'   => $pair['score'],
			'strong'  => $pair['score'] >= 0.85,
		);

		if ( ! isset( $claimed_gi[ $gid ] ) && $pair['score'] >= 0.85 ) {
			$claimed[ $pair['post_id'] ] = $gid;
			$claimed_gi[ $gid ]          = $pair['post_id'];
		}
	}

	$reviews = array();

	foreach ( $google_reviews as $review ) {

		$review['linked_post'] = 0;

		if ( isset( $by_gid[ $review['id'] ] ) ) {
			$post_id               = $by_gid[ $review['id'] ];
			$review['linked_post'] = $post_id;
			$review['default']     = ( $existing[ $post_id ]['updated'] !== $review['updated'] ) ? 'update' : 'same';
		} elseif ( isset( $skipped[ $review['id'] ] ) ) {
			$review['default'] = 'skip';
		} elseif ( isset( $matches[ $review['id'] ] ) && $matches[ $review['id'] ][0]['strong'] ) {
			$review['default'] = 'link';
		} else {
			$review['default'] = 'import';
		}

		$reviews[] = $review;
	}

	$offered = array();
	foreach ( $matches as $list ) {
		foreach ( $list as $c ) {
			$offered[ $c['post_id'] ] = true;
		}
	}

	$unmatched_existing = array();
	foreach ( $unlinked as $post_id => $e ) {
		if ( ! isset( $offered[ $post_id ] ) ) {
			$unmatched_existing[] = $post_id;
		}
	}

	return array(
		'reviews'            => $reviews,
		'matches'            => $matches,
		'existing'           => $existing,
		'unmatched_existing' => $unmatched_existing,
	);
}

/* ------------------------------------------------------------------
 * Applying changes
 * ---------------------------------------------------------------- */

/**
 * Carry out a plan, with the owner's per-review decisions.
 *
 * @param array $plan      From if_reviews_google_build_plan().
 * @param array $decisions Google ID => import | skip | link:<post_id>. Missing IDs use the plan's default.
 * @return array Counts: total, imported, updated, linked, skipped, missing.
 */
function if_reviews_google_apply_plan( $plan, $decisions = array() ) {

	$g        = if_reviews_google_get();
	$skipped  = array_flip( (array) $g['skipped'] );
	$existing = $plan['existing'];
	$result   = array( 'total' => count( $plan['reviews'] ), 'imported' => 0, 'updated' => 0, 'linked' => 0, 'skipped' => 0, 'missing' => 0, 'error' => '' );
	$seen     = array();
	$taken    = array();

	foreach ( $plan['reviews'] as $review ) {

		$seen[ $review['id'] ] = true;

		if ( ! empty( $review['linked_post'] ) ) {
			if ( 'update' === $review['default'] ) {
				if_reviews_google_write_review( (int) $review['linked_post'], $review );
				$result['updated']++;
			}
			continue;
		}

		$decision = isset( $decisions[ $review['id'] ] ) ? $decisions[ $review['id'] ] : $review['default'];

		if ( 'link' === $decision ) {
			$decision = isset( $plan['matches'][ $review['id'] ][0] ) ? 'link:' . $plan['matches'][ $review['id'] ][0]['post_id'] : 'import';
		}

		if ( 'skip' === $decision ) {
			$skipped[ $review['id'] ] = true;
			$result['skipped']++;
			continue;
		}

		unset( $skipped[ $review['id'] ] );

		if ( 0 === strpos( $decision, 'link:' ) ) {
			$post_id = (int) substr( $decision, 5 );

			// Only reviews offered in the preview can be linked, each once.
			if ( isset( $existing[ $post_id ] ) && '' === $existing[ $post_id ]['google_id'] && ! isset( $taken[ $post_id ] ) && 'if_review' === get_post_type( $post_id ) ) {
				$taken[ $post_id ] = true;
				if_reviews_google_write_review( $post_id, $review );
				$result['linked']++;
				continue;
			}

			$decision = 'import';
		}

		if ( 'import' === $decision ) {
			$post_id = wp_insert_post(
				wp_slash(
					array(
					'post_type'   => 'if_review',
					'post_status' => 'publish',
					'post_title'  => $review['name'],
					'meta_input'  => array(
						'_if_reviewer_name'      => $review['name'],
						'_if_review_date'        => $review['date'],
						'_if_rating'             => $review['rating'],
						'_if_review_text'        => $review['text'],
						'_if_source'             => 'google',
						'_if_source_label'       => '',
						'_if_review_url'         => '',
						'_if_display_on_website' => '1',
						'_if_pinned'             => '0',
						'_if_google_review_id'   => $review['id'],
						'_if_google_updated'     => $review['updated'],
					),
					)
				),
				true
			);

			if ( ! is_wp_error( $post_id ) ) {
				$result['imported']++;
			}
		}
	}

	// Flag linked reviews that Google no longer returns; clear the flag on ones it does.
	foreach ( $existing as $post_id => $e ) {

		if ( '' === $e['google_id'] && ! isset( $taken[ $post_id ] ) ) {
			continue;
		}

		if ( isset( $seen[ $e['google_id'] ] ) || isset( $taken[ $post_id ] ) ) {
			delete_post_meta( $post_id, '_if_google_missing' );
		} else {
			update_post_meta( $post_id, '_if_google_missing', '1' );
			$result['missing']++;
		}
	}

	if_reviews_google_update( array( 'skipped' => array_keys( $skipped ), 'first_sync_done' => 1 ) );

	return $result;
}

/**
 * Overwrite a local review with Google's current version, keeping local choices.
 *
 * @param int   $post_id Review post.
 * @param array $review  Normalised Google review.
 */
function if_reviews_google_write_review( $post_id, $review ) {

	update_post_meta( $post_id, '_if_reviewer_name', wp_slash( $review['name'] ) );
	update_post_meta( $post_id, '_if_rating', $review['rating'] );
	update_post_meta( $post_id, '_if_review_text', wp_slash( $review['text'] ) );
	update_post_meta( $post_id, '_if_source', 'google' );
	update_post_meta( $post_id, '_if_google_review_id', $review['id'] );
	update_post_meta( $post_id, '_if_google_updated', $review['updated'] );
	delete_post_meta( $post_id, '_if_google_missing' );

	if ( '' !== $review['date'] ) {
		update_post_meta( $post_id, '_if_review_date', $review['date'] );
	}

	$post = get_post( $post_id );

	if ( $post && ( '' === trim( $post->post_title ) || $post->post_title !== $review['name'] ) ) {
		wp_update_post( array( 'ID' => $post_id, 'post_title' => $review['name'] ) );
	}
}

/**
 * Fetch and apply in one go, using the plan's defaults (strong matches link, the rest import).
 *
 * @return array|WP_Error Result counts.
 */
function if_reviews_google_run_sync() {

	$reviews = if_reviews_google_fetch_reviews();

	if ( is_wp_error( $reviews ) ) {
		if_reviews_google_record_result( array( 'error' => $reviews->get_error_message() ) );
		return $reviews;
	}

	$plan   = if_reviews_google_build_plan( $reviews );
	$result = if_reviews_google_apply_plan( $plan, array() );

	if_reviews_google_record_result( $result );

	return $result;
}

/**
 * Remember when and how the last sync went.
 *
 * @param array $result Counts, or ['error' => message].
 */
function if_reviews_google_record_result( $result ) {
	if_reviews_google_update(
		array(
			'last_sync'   => time(),
			'last_result' => wp_parse_args( $result, array( 'total' => 0, 'imported' => 0, 'updated' => 0, 'linked' => 0, 'skipped' => 0, 'missing' => 0, 'error' => '' ) ),
		)
	);
}

/**
 * One sentence about a result.
 *
 * @param array $result Counts.
 * @return string
 */
function if_reviews_google_describe_result( $result ) {
	return sprintf(
		/* translators: 1: imported, 2: updated, 3: linked, 4: skipped, 5: no longer on Google */
		__( 'Sync finished: %1$d imported, %2$d updated, %3$d linked to existing reviews, %4$d skipped, %5$d no longer on Google.', 'if-reviews' ),
		(int) $result['imported'],
		(int) $result['updated'],
		(int) $result['linked'],
		(int) $result['skipped'],
		(int) $result['missing']
	);
}

/* ------------------------------------------------------------------
 * Scheduled sync
 * ---------------------------------------------------------------- */

/**
 * Turn the daily event on or off.
 *
 * @param bool $on Whether to schedule.
 */
function if_reviews_google_schedule( $on ) {

	$next = wp_next_scheduled( IF_REVIEWS_GOOGLE_CRON );

	if ( $on && ! $next ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', IF_REVIEWS_GOOGLE_CRON );
	} elseif ( ! $on && $next ) {
		wp_clear_scheduled_hook( IF_REVIEWS_GOOGLE_CRON );
	}
}

/**
 * The daily run.
 */
function if_reviews_google_cron_sync() {

	$g = if_reviews_google_get();

	if ( ! $g['auto_sync'] || ! if_reviews_google_is_connected() || ! $g['first_sync_done'] ) {
		return;
	}

	if_reviews_google_run_sync();
}
add_action( IF_REVIEWS_GOOGLE_CRON, 'if_reviews_google_cron_sync' );

/**
 * Deactivation: drop the scheduled event (settings and reviews stay).
 */
function if_reviews_google_deactivate() {
	wp_clear_scheduled_hook( IF_REVIEWS_GOOGLE_CRON );
}
register_deactivation_hook( IF_REVIEWS_FILE, 'if_reviews_google_deactivate' );

/* ------------------------------------------------------------------
 * Admin list and edit screen hints
 * ---------------------------------------------------------------- */

/**
 * Note synced and missing reviews in the Source column.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function if_reviews_google_column_note( $column, $post_id ) {

	if ( 'if_source' !== $column || '' === (string) get_post_meta( $post_id, '_if_google_review_id', true ) ) {
		return;
	}

	if ( '1' === get_post_meta( $post_id, '_if_google_missing', true ) ) {
		echo '<br><span class="if-admin-warn" title="' . esc_attr__( 'The last sync did not find this review on your Google Business Profile. It may have been removed by the reviewer or by Google.', 'if-reviews' ) . '">' . esc_html__( 'No longer on Google', 'if-reviews' ) . '</span>';
	} else {
		echo '<br><span class="if-admin-hidden">' . esc_html__( 'Synced', 'if-reviews' ) . '</span>';
	}
}
add_action( 'manage_if_review_posts_custom_column', 'if_reviews_google_column_note', 11, 2 );

/**
 * Tell the editor that some fields will be overwritten by the next sync.
 *
 * @param WP_Post $post Review being edited.
 */
function if_reviews_google_edit_notice( $post ) {

	if ( ! $post || 'if_review' !== $post->post_type || '' === (string) get_post_meta( $post->ID, '_if_google_review_id', true ) ) {
		return;
	}

	$missing = '1' === get_post_meta( $post->ID, '_if_google_missing', true );
	?>
	<div class="notice notice-info inline" style="margin: 0 0 16px;">
		<p>
			<?php
			if ( $missing ) {
				esc_html_e( 'This review was imported from Google but the last sync no longer found it on your profile. It is kept as it was; hide or delete it if you prefer.', 'if-reviews' );
			} else {
				esc_html_e( 'This review is synced from Google. The reviewer name, rating, text and date are replaced with Google\'s current version at each sync, so edits to those fields here will not last. Showing, hiding, pinning and categories are kept.', 'if-reviews' );
			}
			?>
		</p>
	</div>
	<?php
}
add_action( 'edit_form_after_title', 'if_reviews_google_edit_notice' );

/**
 * Styles for the Google import page.
 *
 * @param string $hook Current admin page hook.
 */
function if_reviews_google_assets( $hook ) {

	if ( 'if_review_page_if-reviews-google' !== $hook ) {
		return;
	}

	wp_add_inline_style(
		'common',
		'.if-google-wrap .if-admin-stars{color:#f5a623;letter-spacing:1px;font-size:14px;margin:0 6px}'
		. '.if-google-wrap .if-admin-hidden{color:#a7aaad}'
		. '.if-google-wrap .if-help summary{cursor:pointer;color:#2271b1}'
		. '.if-google-wrap .if-help summary:hover{color:#135e96}'
		. '.if-google-wrap .if-help ol{margin:10px 0 0 1.4em}'
		. '.if-google-wrap .if-help li{margin-bottom:8px}'
		. '.if-google-wrap .if-setup-guide{background:#fff;border:1px solid #dcdcde;border-radius:4px;padding:12px 16px}'
		. '.if-google-wrap .if-setup-guide[open] summary{margin-bottom:6px}'
		. '.if-google-wrap .if-setup-guide ol ul{list-style:disc;margin:6px 0 0 1.2em}'
		. '.if-google-wrap .if-setup-guide ol ul li{margin-bottom:6px}'
		. '.if-google-wrap .if-google-preview td{vertical-align:top}'
		. '.if-google-wrap .if-google-preview select{max-width:100%}'
	);
}
add_action( 'admin_enqueue_scripts', 'if_reviews_google_assets' );
