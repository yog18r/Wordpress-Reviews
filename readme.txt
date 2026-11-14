=== WP Reviews ===
Contributors: yog18r
Donate link: https://github.com/sponsors/yog18r
Tags: reviews, testimonials, google reviews, shortcode, star rating
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show the reviews your business has already received, on your own site: per-review source badges, an honest summary header, grid, list or carousel layouts, CSV import, and a block.

== Description ==

WP Reviews is a small, dependency-free way to display customer reviews on a WordPress site. Add each review in wp-admin, by hand, from a CSV file or with the optional Google import: the reviewer's name, the date, a star rating, the review text, and where it was left (Google, Facebook, Trustpilot, Yelp, ProductReview.com.au, directly to you, or any other platform). One shortcode or block then shows them as a card grid, a list or a carousel, with the overall rating, the review count and a plain statement of where the reviews came from.

**Your reviews live on your own site.** By default the plugin contacts no review platform: you type, paste or import each review, so it keeps working if a platform changes its API, pricing or terms. The one exception is the optional Google Business Profile import, which stays off until you connect it.

= Use it honestly =

The plugin is built around one principle: the summary your visitors see should be true. The tools are arranged so that the easy path is also the honest one.

* **Add every review you receive, including the unfavourable ones.** The overall rating and review count in the header are calculated from all published reviews, not just the ones on display.
* **Hide, don't delete.** Each review has a "Display this review on the website" checkbox. Untick it to keep a review off the cards. It stays in WordPress and still counts toward the average and the count, so a 4.6 remains a 4.6 whether or not the two-star review is visible.
* **Label sources accurately.** Only mark a review as a Google review if it was left on Google. If every review came from Google the header reads "Google Reviews"; if they came from several places it says so, for example "24 reviews from Google, Facebook, and direct feedback".
* **Copy, don't edit.** Paste review text exactly as the reviewer wrote it, and use the same form of their name they used publicly.
* **Link to the original.** Each review can carry a link to where it was left, shown as "View on Google" on the card, so visitors can check it for themselves.

An opt-in setting hides reviews below a chosen star rating automatically. It is off by default and works like hiding by hand: those reviews still count in the header.

Consumer protection law in many countries, including the Australian Consumer Law, the United States FTC rule on consumer reviews and the United Kingdom's Digital Markets, Competition and Consumers Act, treats fabricated, edited or selectively presented reviews as misleading conduct. WP Reviews gives you the means to present reviews properly. Using it truthfully is your responsibility.

= Features =

* A **Reviews** screen in wp-admin with reviewer name, review date, star rating, source, review text and a show/hide toggle.
* **Per-review source** with a small badge on each card. Built in: Google, Facebook, Trustpilot, Yelp, ProductReview.com.au, Direct and Other (with your own label). Developers can add more with a filter.
* **Honest summary header**: average rating with fractionally filled stars, total count, and an automatic description of the sources. Set your own title if you prefer ("What our clients say").
* **Three layouts**: grid (1 to 4 columns), list, and a touch-friendly carousel with previous/next buttons.
* **Built for many reviews**: show a limited number with a "Show more" button, cut long reviews with "Read more" at a length you choose, sort by newest, oldest, highest or random, filter by source, and manage the admin list with sortable columns, a source filter and bulk actions.
* **CSV import and export**: bring in years of reviews from a spreadsheet in one go (ratings such as 8/10 are converted, duplicates are skipped) and download everything as a backup.
* **Optional Google Business Profile import** using your own Google Cloud project: pull every review from one location, match the ones you had already entered by hand, keep edited reviews current and flag the ones Google removes. Off until you connect it.
* **A block for the block editor** with a live preview and all the display options in the sidebar, alongside the `[if_reviews]` shortcode for widgets and templates.
* **Optional link to the original review** on each card, and an opt-in setting to hide reviews below a chosen star rating (they still count in the header).
* **"Leave us a review" buttons** linking straight to your review page on each platform, with built-in guidance on where to find those links. Off until you tick "Show buttons" and add a link.
* **Categories** to group reviews by service or product and show the right ones on each page, **pinned reviews** that stay at the top, and **numbered pagination** for large sets.
* **Featured Review** and **Rating Badge** blocks (and shortcodes) for landing pages, headers and footers.
* **Styling without CSS**: accent, card, border and text colours, corner radius and a cycling avatar colour palette, all from a settings screen. Avatar text colour is picked automatically for contrast.
* **Lightweight and scoped**: one small stylesheet and one small script, loaded only on pages that render the shortcode. No jQuery, no tracking, and no external requests apart from the update check, unless you connect Google.
* **Automatic updates** from the project's GitHub releases, shown on the Plugins screen like any other plugin.
* **Translation-ready** (text domain `if-reviews`) and accessible: star ratings have text equivalents, badges announce their source, and the carousel and "Show more" work with a keyboard.

= Sources and badges =

Each review records where it was left. On the card the source appears as a small badge next to the date, for example "August 2026 · Google". Reviews marked "Direct" show no badge, because the header already states where the reviews came from.

The icons are simple single-colour marks drawn for this plugin, not the platforms' official logos; the text label is what identifies the source. You can turn the icons off in Reviews → Display Settings and keep the text.

= Shortcode =

Add `[if_reviews]` to any page, post, widget or block. Defaults come from Reviews → Display Settings, and each can be overridden per instance:

* `[if_reviews layout="carousel"]` — `grid` (default), `list` or `carousel`.
* `[if_reviews columns="2"]` — maximum grid columns, 1 to 4. Collapses automatically on smaller screens.
* `[if_reviews limit="6"]` — show six and add a "Show more" button for the rest.
* `[if_reviews limit="6" show_more="no"]` — show six and stop.
* `[if_reviews limit="0"]` — every review, loaded 24 at a time: a "Show more" button in grids and lists, and more cards as visitors scroll a carousel. Only the first batch is in the page, so large review collections stay fast.
* `[if_reviews source="google"]` — one source only; the header then describes that source alone. Several: `source="google,facebook"`.
* `[if_reviews order="highest"]` — `newest` (default), `oldest`, `highest` or `random`.
* `[if_reviews show_header="no"]` — cards only.
* `[if_reviews title="What our clients say"]` — custom header title.
* `[if_reviews buttons="yes"]` — force the "Leave us a review" buttons on (or `"no"` to hide them) for this instance.
* `[if_reviews category="mortgages"]` — only reviews in a category (by slug; comma-separate several). The header describes that subset.
* `[if_reviews paginate="12"]` — numbered pages of twelve instead of "Show more". Use one paginated shortcode per page.
* `[if_reviews layout="carousel" autoplay="6"]` — the carousel moves on every six seconds; `0` disables it.
* `[if_review id="42"]` — one review on its own as a featured quote. The ID is in the address bar when editing the review.
* `[if_reviews_rating]` — inline "★★★★★ 4.8 from 120 reviews" badge. Accepts `source` and `category`; `stars="no"` or `count="no"` trims it.
* `[if_reviews_buttons]` — the buttons on their own. `heading="..."` changes the heading; `heading="-"` removes it.

The settings screen shows this reference with the list of source keys.

= Blocks =

In the block editor there are three blocks, all previewed with the same code that renders the front end:

* **Reviews**: the main reviews display. Every option in the sidebar starts at "Default", which follows Reviews → Display Settings; change only what should differ on that page. Supports wide and full alignment.
* **Featured Review**: pick one review from a list to show it on its own as a highlighted quote.
* **Rating Badge**: the compact "4.8 from 120 reviews" line, with optional source and category filters.

= Categories, pinning and pagination =

Under Reviews → Categories, create categories such as "Mortgage advice" or "Insurance" and tick them on each review. Then `[if_reviews category="mortgage-advice"]` (or the block's "Only these categories") shows just those reviews, with a header that describes that subset honestly. Tick "Pin to the top" on a review to keep it first whatever the sort order; the Reviews list has Pin and Unpin bulk actions. For sites with many reviews, `paginate="12"` replaces the "Show more" button with numbered pages that can be linked to and crawled.

= "Leave us a review" buttons =

Reviews → Display Settings → "Leave us a review" buttons. Tick "Show buttons", then paste the link to your review page for each platform you use; the row's "Where do I find this link?" note explains how to get the direct link for Google, Facebook, Trustpilot, Yelp and ProductReview.com.au. Button text defaults to "Review us on Google" and so on, and can be changed per platform. The buttons appear under the reviews, open in a new tab by default, and can also be placed anywhere on their own with `[if_reviews_buttons]`.

= Import and export =

Reviews → Import / Export. Export downloads every review, including hidden ones and drafts, as a CSV file. Import takes a CSV with a header row. Only `reviewer_name` and `rating` are required; the optional columns are `review_date`, `review_text`, `source`, `source_label`, `review_url`, `categories`, `pinned`, `display` and `status`. Download the template from the page to get started. Ratings written as fractions (4/5, 8/10) are converted to stars, platform names are matched to sources, and rows that match an existing review are skipped so a corrected file can be re-imported safely.

= Google Business Profile import =

Reviews → Google import. This is optional, off by default, and uses a Google Cloud project that you own; the plugin never contacts Google otherwise, and nothing passes through anyone else's servers.

Google only lets approved projects read Business Profile reviews, so there is one-off setup on Google's side, and the Google import page walks you through all six steps. You need a verified profile at least 60 days old that lists your website, a Cloud project, Google's approval of an access request (days to weeks), three APIs, a consent screen and an OAuth client.

To keep the connection from expiring, set the consent screen audience to Internal on a Google Workspace account, or to External followed by "Publish app" on a personal Google account (Google ends a Testing app's connection after 7 days). If a Workspace administrator restricts third-party apps, they also mark the app as Trusted in the Admin console; the page gives the exact steps. Once connected, the plugin reads every review for one location.

The first sync is a preview. Reviews already on the site with the source "Google" are matched to the profile by reviewer name, rating and text, and you approve each match before anything changes, so reviews you entered by hand are linked, not duplicated. After that, "Sync now" or the optional daily sync imports new reviews and updates edited ones. Linked reviews take Google's reviewer name, rating, text and date; showing, hiding, pinning and categories stay as you set them. A new Google review that closely matches an unlinked review on the site is linked automatically, so click "Preview sync" first if you want to check. A review that disappears from Google is kept and marked "No longer on Google" in the Reviews list.

All reviews on the profile are imported, low ratings included. Hide any you do not want shown; they still count in the header.

The Client ID, Client Secret and Google connection are stored encrypted (see the FAQ on security).

= Updates =

The plugin checks the project's GitHub releases and offers new versions on the Plugins and Updates screens, exactly like a plugin from the WordPress.org directory. No account or token is needed. It only ever installs the `if-reviews.zip` attached to a published release, never unreleased code from the repository. To opt out, add `add_filter( 'if_reviews_update_checker_enabled', '__return_false' );` to a small plugin or your theme.

= For developers =

* `if_reviews_sources` — add or rename sources. Each entry has a `label` and optional `description` and inline SVG `icon`.
* `if_reviews_source_has_badge` — decide which sources show a badge on cards.
* `if_reviews_reviews` — filter the loaded reviews before stats and display.
* `if_reviews_date_format` — PHP date format for review dates (default `F Y`).
* `if_reviews_show_more_label` — label of the "Show more" button.
* `if_reviews_batch_size` — cards loaded at a time when every review is shown (default 24).
* `if_reviews_crypto_available` — return false to stop the plugin storing private values at all.
* `if_reviews_cta_buttons` — alter the "Leave us a review" buttons.
* `if_reviews_cta_help` — the "Where do I find this link?" help for each platform.
* `if_reviews_google_fetched_reviews` — alter reviews fetched from Google before they are matched.
* `if_reviews_update_checker_enabled` — disable GitHub update checks.

Reviews are stored as the `if_review` post type with meta keys `_if_reviewer_name`, `_if_review_date` (Y-m-d), `_if_rating` (1-5), `_if_review_text`, `_if_source`, `_if_source_label`, `_if_review_url`, `_if_pinned` and `_if_display_on_website` ('1' or '0'), plus the `if_review_category` taxonomy. Settings live in the `if_reviews_options` option and the Google connection in `if_reviews_google`. Google-synced reviews also carry `_if_google_review_id`, `_if_google_updated` and `_if_google_missing`.

Source code, issues and releases: https://github.com/yog18r/Wordpress-Reviews

== Installation ==

1. Download `if-reviews.zip` from the latest release at https://github.com/yog18r/Wordpress-Reviews/releases
2. In wp-admin go to Plugins → Add New Plugin → Upload Plugin, choose the zip and activate it.
3. Go to **Reviews → Add New** and enter your first review: name, date, rating, source and text.
4. Optionally adjust colours and layout defaults under **Reviews → Display Settings**.
5. Add `[if_reviews]` to a page.

Future versions appear on the Plugins screen automatically.

== Frequently Asked Questions ==

= Does this pull reviews from Google or anywhere else automatically? =

Not by default. Every review is entered by hand or imported from a CSV, and the plugin makes no network requests of its own apart from checking GitHub for plugin updates. If you own a Google Business Profile you can optionally connect it (Reviews → Google import) using your own Google Cloud project, after which the plugin reads your reviews from Google's Business Profile API and keeps them current. No other platform is pulled automatically.

= Why does the Google import need a Google Cloud project? Other plugins do not ask for one. =

Plugins that import Google reviews "without an API key" send the request through their own servers and cap the free tier, because that service is what they sell. This plugin runs no service. It uses Google's own Business Profile API, which returns every review but is open only to projects Google has approved. That approval is the one-off setup described on the Google import page, and it means your reviews never pass through a third party.

= Does the plugin add review schema (star ratings in search results)? =

No, deliberately. Google does not show review stars for reviews a business publishes about itself, and that includes reviews copied from Google, Facebook or Trustpilot. Adding the markup anyway would earn nothing and risks a penalty for misleading structured data. The stars beside your business in search results come from your Google Business Profile, not your website. The plugin therefore outputs plain, accessible HTML with no structured data.

= How are my Google credentials kept safe? =

Anything the plugin stores that is not shown publicly, such as the Google Client ID, Client Secret, connection token and account identifiers, is encrypted before it is saved, using a key derived from the security keys in your wp-config.php. A copy of the database on its own, such as a leaked backup or a staging site, cannot reveal them. Reviews are not encrypted, because they are public anyway.

If the server cannot encrypt, the plugin stores nothing private rather than storing it in plain text. If wp-config.php has no security keys of its own (WordPress then keeps them in the database), connecting to Google is turned off until you add them; the Google import page shows how, and anything already saved moves to the new key by itself.

For more control, add these to wp-config.php:

* `define( 'IF_REVIEWS_GOOGLE_CLIENT_ID', '...' );` and `define( 'IF_REVIEWS_GOOGLE_CLIENT_SECRET', '...' );` keep the credentials out of the database entirely.
* `define( 'IF_REVIEWS_ENCRYPTION_KEY', '...' );` (any long random string) sets a dedicated encryption key. Use this if a security plugin changes your WordPress security keys from time to time: when the keys change and the old ones are gone, the saved connection cannot be read and you have to enter the Client Secret and connect again.

Connect with a Google account that is a Manager of the profile rather than its Owner, so a misused connection could never remove the owner or transfer the profile.

= How do I hide a bad review without affecting the rating? =

Edit the review and untick "Display this review on the website". It leaves the cards but still counts toward the average and review count in the header; removing it from the maths would make the summary untrue. To hide or show many at once, use the bulk actions on the Reviews list.

= Why does the header say "Reviews" and not "Google Reviews"? =

The header names a platform only when every review in that shortcode came from it. Reviews created before version 2.0 have no source recorded and are treated as "Direct". Select them on the Reviews list and use the bulk action "Set source: Google" (or whichever applies).

= Can I hide every review below a certain rating automatically? =

Yes. Reviews → Display Settings → "Automatically hide low ratings" is off by default. Turn it on and reviews below your chosen rating leave the cards but still count in the header, like reviews you hide by hand. The Reviews list marks them "Auto-hidden". Be aware that showing only your best reviews beside an average is something consumer regulators look at.

= How do I import reviews from a spreadsheet? =

Reviews → Import / Export. Download the template, fill it in (one review per row, header row kept), and upload it. Only the reviewer name and rating are required. Dates are best as YYYY-MM-DD; ratings may be 1 to 5 or a fraction such as 8/10. Rows matching an existing review are skipped, so you can fix and re-import a file without creating duplicates.

= Can I link each review to the original? =

Yes. Each review has an optional URL field. When set, the card shows a "View on Google" (or Facebook, etc.) link that opens in a new tab. On Google, open the review, use its three-dot menu and choose "Share review" to copy a link. Turn the links off site-wide in Display Settings if you prefer.

= Can I show different reviews on different pages? =

Yes. Give reviews categories (Reviews → Categories) and use `category="slug"` on the shortcode or the block's "Only these categories" field. Each page gets its own subset and its own honest header. The source filter works the same way, and the two can be combined.

= How do I get more reviews? =

Turn on the "Leave us a review" buttons in Display Settings and paste your review links. The Google link in particular (from "Ask for reviews" in Google Business Profile) opens the review form directly, which removes most of the friction. Put `[if_reviews_buttons]` on a thank-you page or in your footer as well.

= Can I show only my Google reviews in one place and everything elsewhere? =

Yes: `[if_reviews source="google"]` on one page and `[if_reviews]` on another. Each instance calculates its own header from the reviews it includes.

= I have dozens of reviews. What is the best layout? =

A carousel keeps the page short: `[if_reviews layout="carousel"]`. If you prefer a grid, cap it and let visitors expand: `[if_reviews limit="6"]`. Both can be set as defaults in Display Settings.

= Can I change the colours to match my site? =

Yes. Reviews → Display Settings has colour pickers for the stars, card background, border and text, a corner-radius field and a list of avatar colour swatches you can add to or remove from. Each reviewer takes a colour from their name, and neighbouring cards are kept from matching.

= What happens to my reviews if I delete the plugin? =

They stay in the database unless you tick "Delete all reviews when the plugin is deleted" in Display Settings before uninstalling. Settings are always removed on uninstall.

= Will this conflict with a plugin of the same name on WordPress.org? =

No. The plugin declares its own update source, so WordPress will never offer an unrelated plugin as an update for it.

== Screenshots ==

1. The shortcode rendered as a grid, with the summary header and source badges.
2. The same reviews as a carousel.
3. The Reviews list in wp-admin with sortable rating and date columns, source filter and bulk actions.
4. The review edit screen.
5. Reviews → Display Settings.

== Changelog ==

= 2.5.0 =
* Faster with large numbers of reviews. Only the first batch of cards is sent with the page; "Show more" and carousels fetch the next batch when needed. Measured with 5,000 reviews: the default widget went from about 480 ms and 8 MB of HTML to about 10 ms and 36 KB.
* Faster: rebuilding the review cache takes a handful of database queries instead of one per review, the cache is about half the size, several widgets on one page share one load, and the rating badge uses cached totals.
* Fixed: reviews added during a CSV import or Google sync could stay invisible for up to a day if a visitor loaded a page mid-import.
* Fixed: random order with page numbers or "Show more" no longer repeats or skips reviews.
* Google import: the preview handles large profiles (one shared, searchable list for linking reviews by hand instead of a copy in every row; faster matching), and your choices are no longer lost on profiles with more than about 1,000 reviews.
* Featured Review block: the review picker is searchable and lists every review, not only the newest 500, and loads only when the block is used.
* Security: the list of Google locations to choose from is stored encrypted; "Delete all reviews" on uninstall now also removes trashed reviews and review categories; updates only accept a release file named exactly if-reviews.zip.
* Accessibility: the carousel's pause button now shows on phones and touching the carousel stops autoplay; focus outlines use the text colour so they are visible; cards are labelled "Review by …" for screen readers; star ratings stay readable in Windows high-contrast mode; the current page number stays readable on dark accent colours.
* Appearance fixes: reviewer names no longer disappear on dark themes; header text follows the page's text colour; cards no longer lift on hover as if clickable; the page-number list no longer gets its own border; right-to-left languages fill stars and scroll the carousel correctly; reviews print cleanly.
* Compatibility: works on servers without PHP's mbstring extension; the stylesheet no longer loads on pages without reviews on older WordPress versions with classic themes; the rating badge is one translatable sentence.

= 2.4.5 =
* Security: the block editor's review picker now lists only reviews shown on the site for Authors and Contributors. Hidden reviews appear only for Editors and Administrators, who can manage reviews anyway.
* Security: updates now come only from the `if-reviews.zip` attached to a published GitHub release. The update checker no longer falls back to a tag's or the main branch's source code.
* Improved: saving an unrelated Google import setting no longer clears credentials that cannot be read at that moment (for example while security keys are being changed). They become usable again as soon as the right key is back.
* Improved: after an update, credentials saved by older versions are encrypted on the next page load of any kind, not only the next visit to wp-admin.
* Improved: if Google cannot be reached when you disconnect, the page says so and links to where you can remove the app yourself.

= 2.4.4 =
* Security: encryption now fails closed. If the server cannot encrypt, the plugin stores nothing private instead of storing it in plain text.
* Security: connecting to Google is turned off on sites whose wp-config.php has no security keys of its own, because the encryption key would then sit in the database beside the data. The Google import page explains how to add keys; an existing connection keeps syncing meanwhile.
* Improved: when you add security keys or a dedicated `IF_REVIEWS_ENCRYPTION_KEY`, saved credentials move to the new key automatically, with no need to connect again.
* Improved: reviews are cached between page views and the cache is cleared whenever a review, its details or its categories change, so pages stay fast on sites with thousands of reviews.
* Improved: in the Google import preview, an unrecognised choice falls back to the suggested action.
* Development: a regression test suite (`tests/run.sh`) runs the plugin in a throwaway WordPress with Google's API mocked, and runs on every push.

= 2.4.3 =
* Security: anything the plugin stores that is not shown publicly is now encrypted. The Google Client ID, Client Secret, connection tokens and account identifiers are sealed with libsodium using a key from your wp-config.php, so a copy of the database alone, such as a leaked backup or a staging site, cannot reveal them. Existing values are encrypted automatically the next time you open an admin page.
* New: optional wp-config.php constants. `IF_REVIEWS_GOOGLE_CLIENT_ID` and `IF_REVIEWS_GOOGLE_CLIENT_SECRET` keep the credentials out of the database entirely, and `IF_REVIEWS_ENCRYPTION_KEY` sets a dedicated encryption key.
* New: the Google import page warns if the server cannot encrypt, or if wp-config.php has no security keys of its own. If the keys change, it asks you to enter the Client Secret and connect again.
* Docs: the setup guide recommends connecting as a Manager of the profile rather than its Owner, keeping the Cloud project private, and checking connected apps from time to time.
* Release pipeline: GitHub Actions are pinned to exact commits.

= 2.4.2 =
* Security: CSV export could be tricked by review text containing a backslash and quote into placing a live spreadsheet formula in the file. Fixed for export and import.
* Security: only Editors and Administrators can now add or edit reviews. Previously Authors and Contributors could too, which let them change the rating shown on the site.
* Security: the Google Client Secret is no longer sent back to the settings page (leave the field blank to keep the saved one), the sign-in callback checks its security token before anything else, and admin notices are shown as plain text.
* Hardening: disconnecting sends the token to Google in the request body, deleting the plugin also revokes the Google connection and clears temporary data, and backslashes in review text are no longer stripped when saving.
* New: a Reconnect button on the Google import page when Google stops accepting the connection. Reconnecting keeps your location, settings and sync history.
* Docs: the readme and in-app help were corrected where they no longer matched the plugin, and shortened throughout.

= 2.4.1 =
* Fixed: in the Google import preview, a Google review the matcher did not pair with an existing review could only be imported or skipped. The Action menu now also lists every other unlinked Google review on the site, so you can link any of them by hand.
* Improved: rating-only reviews with the same reviewer name and rating are now offered as a possible match even when their dates are far apart or the site's copy has no date.

= 2.4.0 =
* New: **Google Business Profile import** (Reviews → Google import). Connect your own, Google-approved Cloud project with OAuth and the plugin reads every review of one location through the Business Profile API. The first sync is a preview in which reviews you had already entered by hand are matched by reviewer name, rating and text and linked instead of duplicated, with each match yours to confirm or change. Later syncs, on demand or once a day, update edited reviews, import new ones and mark reviews that have gone from Google as "No longer on Google" without deleting them. Hidden, pinned and category settings are never overwritten. Off until you connect it; the plugin still makes no request to Google otherwise.
* Docs: the readme now explains that the plugin deliberately emits no review schema, and why.

= 2.3.1 =
* Improved: avatar colours in Display Settings are now a list of swatches with a colour picker, an "Add colour" button and a remove link per colour, instead of a comma-separated text field. Saved data is unchanged.
* Improved: neighbouring avatars no longer share a colour. Each reviewer still takes their colour from their name; where that would match one of the cards immediately before it (one row's worth in a grid), the next colour in the palette is used for that card.

= 2.3.0 =
* New: **review categories**. Tag reviews by service or product under Reviews → Categories, then show a subset with `category="slug"` on the shortcode or block. The header describes that subset. Categories have an admin filter, a list column and a CSV column.
* New: **Featured Review** block and `[if_review id="42"]` shortcode: one review on its own as a highlighted quote.
* New: **Rating Badge** block and `[if_reviews_rating]` shortcode: a compact "★★★★★ 4.8 from 120 reviews" badge for headers, footers and calls to action, with optional source and category filters.
* New: **numbered pagination** with `paginate="12"` (and the block's "Reviews per page"), as an alternative to the "Show more" button for large sets.
* New: **pinned reviews**. Tick "Pin to the top" on a review (or use the bulk action) to keep it first whatever the sort order.
* New: **carousel autoplay** (Display Settings or `autoplay="6"`), with a pause button, pausing on hover and focus, and disabled for visitors who prefer reduced motion.
* Changed: avatar colours are now chosen from the reviewer's name, so the same person gets the same colour everywhere, including after "Show more" and in the featured block.
* Added a plugin icon and banner, shown on update notices and in the "View details" window from this version onwards, and the plugin's own mark in the admin menu.

= 2.2.0 =
* New: **"Leave us a review" buttons**. Display Settings has a new section with one row per platform (Google, Facebook, Trustpilot, Yelp, ProductReview.com.au, Other): paste your review-page link, optionally change the button text, and the buttons appear under the widget. Off by default. Each row explains where to find that platform's direct review link.
* New: `[if_reviews_buttons]` shortcode to place the buttons on their own, plus a `buttons="yes|no"` attribute and a block option to override per instance.
* Changed: renamed to **WP Reviews**. Only the display name changes: the plugin folder (`if-reviews`), the `[if_reviews]` shortcode, the block, the settings and all stored reviews are untouched, and updates continue as before.

= 2.1.0 =
* New: **CSV import and export** under Reviews → Import / Export, with a downloadable template. Ratings such as 4/5 or 8/10 are converted to stars, platform names are matched to sources, common date formats are accepted, and rows matching an existing review are skipped.
* New: a **Reviews block** for the block editor with a live preview and every display option in the sidebar. Options left at "Default" follow Display Settings.
* New: optional **link to the original review** per review, shown as "View on Google" (or the relevant platform) on the card. Can be switched off in Display Settings.
* New: **"Read more"** for long reviews. Set a character length in Display Settings; longer reviews are cut at a word boundary with a button to expand.
* New: opt-in **automatic hiding of low ratings** (Display Settings → "Automatically hide low ratings"). Off by default. Hidden reviews still count toward the header's average and count, and are marked "Auto-hidden" in the Reviews list and on their edit screen.
* Improved: styles load in the head on pages that contain the block, not only the shortcode.

= 2.0.0 =
* New: per-review **Source** (Google, Facebook, Trustpilot, Yelp, ProductReview.com.au, Direct, Other with a custom label), shown as a badge on each card. Developers can add sources with the `if_reviews_sources` filter.
* New: the summary header describes the reviews honestly. It names a platform only when every review came from it, and lists the mix otherwise. The hardcoded Google logo and "Google Reviews" text have been removed.
* New: fractional star fill in the header (4.8 shows 96% of the stars coloured) instead of five fixed stars.
* New: **list** and **carousel** layouts alongside the grid, a `columns` setting (1 to 4), a `limit` with a "Show more" button, sort orders (newest, oldest, highest, random) and a `source` filter, all available as settings defaults and shortcode attributes.
* New: optional custom header title (setting and `title` attribute) and a toggle for source icons.
* New: Reviews list columns for reviewer, rating, source, review date and visibility; sortable rating and date; a source filter; bulk actions to set the source or show/hide many reviews at once.
* New: the review title defaults to the reviewer name when left blank.
* New: automatic updates from GitHub releases via the bundled Plugin Update Checker library (MIT).
* New: uninstall routine. Settings are removed; reviews are removed only if you opt in under Display Settings → Data.
* Improved: styles and script now also load when the shortcode is placed in a widget or template, not only in post content.
* Improved: all strings are translatable (text domain `if-reviews`), and the widget is more accessible (star ratings and badges have text equivalents; carousel and "Show more" are keyboard operable).
* Improved: dates are formatted with the site's timezone and locale.
* Fixed: the readme previously said hidden reviews were excluded from the average. They never were, and still are not; the documentation now matches the behaviour.
* Requires WordPress 6.3 or later.

= 1.1.0 =
* Added a Display Settings screen (Reviews → Display Settings) for accent colour, card background, border colour, corner radius, text colours and avatar colours.
* Avatar colour cycling is generated from the configured palette instead of a fixed set of four.
* Avatar text colour (light/dark) is calculated automatically for contrast.
* Rebranded as IF Reviews as a standalone, portable plugin.

= 1.0.0 =
* Initial release: Reviews custom post type, admin meta box, automatic initials and the `[if_reviews]` shortcode.

== Upgrade Notice ==

= 2.5.0 =
Much faster on sites with many reviews, plus accessibility and appearance fixes. Recommended.

= 2.4.5 =
Small security hardening for the block editor, updates and stored Google credentials. Recommended.

= 2.4.4 =
Hardens how Google credentials are stored and speeds up pages on sites with many reviews. No action needed on most sites.

= 2.4.3 =
Encrypts stored Google credentials and tokens. No action needed; existing values are encrypted automatically.

= 2.4.2 =
Security fixes for CSV export and review permissions, plus a Reconnect button for the Google import. Recommended for all sites.

= 2.4.1 =
Lets you link any existing Google review by hand in the Google import preview.

= 2.4.0 =
Adds an optional Google Business Profile import using your own Google Cloud project. Nothing changes unless you connect it.

= 2.3.1 =
Colour-picker swatches for avatar colours and no more matching avatars side by side. A few avatars may shift to a neighbouring colour once.

= 2.3.0 =
Adds review categories, a Featured Review block, a Rating Badge block, numbered pagination, pinned reviews, carousel autoplay and a plugin icon. Avatar colours now follow the reviewer's name, so some avatars may change colour once. Everything else carries over.

= 2.2.0 =
The plugin is now called WP Reviews, and can show "Leave us a review" buttons (off until you add links in Display Settings). Your reviews, settings, shortcodes and blocks carry over.

= 2.1.0 =
Adds CSV import/export, a block, review links, "Read more" and an optional minimum-rating filter. All off or neutral by default; nothing changes on your site until you use them.

= 2.0.0 =
The header no longer shows a Google logo, and says "Google Reviews" only when every review is marked as a Google review. After updating, go to Reviews, select your existing reviews and use the bulk action "Set source: Google" (or the platform that applies). Existing reviews, settings and your shortcodes otherwise carry over unchanged.
