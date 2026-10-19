=== IF Reviews ===
Contributors: yog18r
Tags: reviews, testimonials, google reviews, shortcode, star rating
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show the reviews your business has already received, on your own site, with per-review source badges, an honest summary header and grid, list or carousel layouts.

== Description ==

IF Reviews is a small, dependency-free way to display customer reviews on a WordPress site. You enter each review by hand in wp-admin: the reviewer's name, the date, a star rating, the review text, and where it was originally left (Google, Facebook, Trustpilot, Yelp, ProductReview.com.au, directly to you, or any other platform). One shortcode then renders them as a card grid, a list or a carousel, with the overall rating, review count and a plain statement of where the reviews came from at the top.

**IF Reviews is a manual import tool.** It does not connect to Google, Facebook or any other service. Nothing is fetched and nothing is sent. Every review on your site is one you typed or pasted in yourself, which also means it keeps working if a platform changes its API, its pricing or its terms.

= Use it honestly =

The plugin is built around one principle: the summary your visitors see should be true. The tools are arranged so that the easy path is also the honest one.

* **Add every review you receive, including the unfavourable ones.** The overall rating and review count in the header are calculated from all published reviews, not just the ones on display.
* **Hide, don't delete.** Each review has a "Display this review on the website" checkbox. Untick it to keep a review off the cards. It stays in WordPress and still counts toward the average and the count, so a 4.6 remains a 4.6 whether or not the two-star review is visible.
* **Label sources accurately.** Only mark a review as a Google review if it was left on Google. If every review came from Google the header reads "Google Reviews"; if they came from several places it says so, for example "24 reviews from Google, Facebook, and direct feedback".
* **Copy, don't edit.** Paste review text exactly as the reviewer wrote it, and use the same form of their name they used publicly.

Consumer protection law in many countries, including the Australian Consumer Law, the United States FTC rule on consumer reviews and the United Kingdom's Digital Markets, Competition and Consumers Act, treats fabricated, edited or selectively presented reviews as misleading conduct. IF Reviews gives you the means to present reviews properly. Using it truthfully is your responsibility.

= Features =

* A **Reviews** screen in wp-admin with reviewer name, review date, star rating, source, review text and a show/hide toggle.
* **Per-review source** with a small badge on each card. Built in: Google, Facebook, Trustpilot, Yelp, ProductReview.com.au, Direct and Other (with your own label). Developers can add more with a filter.
* **Honest summary header**: average rating with fractionally filled stars, total count, and an automatic description of the sources. Set your own title if you prefer ("What our clients say").
* **Three layouts**: grid (1 to 4 columns), list, and a touch-friendly carousel with previous/next buttons.
* **Built for many reviews**: show a limited number with a "Show more" button, sort by newest, oldest, highest or random, filter by source, and manage the admin list with sortable columns, a source filter and bulk actions.
* **Styling without CSS**: accent, card, border and text colours, corner radius and a cycling avatar colour palette, all from a settings screen. Avatar text colour is picked automatically for contrast.
* **Lightweight and scoped**: one small stylesheet and one small script, loaded only on pages that render the shortcode. No jQuery, no external requests, no tracking.
* **Automatic updates** from the project's GitHub releases, shown on the Plugins screen like any other plugin.
* **Translation-ready** (text domain `if-reviews`) and accessible: star ratings have text equivalents, badges announce their source, and the carousel and "Show more" work with a keyboard.

= Sources and badges =

Each review records where it was left. On the card the source appears as a small badge next to the date, for example "August 2026 · Google". Reviews marked "Direct" show no badge, because the header already states the mix of sources and a wall of identical badges adds nothing.

The icons are simple single-colour marks drawn for this plugin, not the platforms' official logos; the text label is what identifies the source. You can turn the icons off in Reviews → Display Settings and keep the text.

= Shortcode =

Add `[if_reviews]` to any page, post, widget or block. Defaults come from Reviews → Display Settings, and each can be overridden per instance:

* `[if_reviews layout="carousel"]` — `grid` (default), `list` or `carousel`.
* `[if_reviews columns="2"]` — maximum grid columns, 1 to 4. Collapses automatically on smaller screens.
* `[if_reviews limit="6"]` — show six and add a "Show more" button for the rest.
* `[if_reviews limit="6" show_more="no"]` — show six and stop.
* `[if_reviews source="google"]` — one source only; the header then describes that source alone. Several: `source="google,facebook"`.
* `[if_reviews order="highest"]` — `newest` (default), `oldest`, `highest` or `random`.
* `[if_reviews show_header="no"]` — cards only.
* `[if_reviews title="What our clients say"]` — custom header title.

The settings screen shows this reference with the list of source keys.

= Updates =

The plugin checks the project's GitHub releases and offers new versions on the Plugins and Updates screens, exactly like a plugin from the WordPress.org directory. No account or token is needed. To opt out, add `add_filter( 'if_reviews_update_checker_enabled', '__return_false' );` to a small plugin or your theme.

= For developers =

* `if_reviews_sources` — add or rename sources. Each entry has a `label` and optional `description` and inline SVG `icon`.
* `if_reviews_source_has_badge` — decide which sources show a badge on cards.
* `if_reviews_reviews` — filter the loaded reviews before stats and display.
* `if_reviews_date_format` — PHP date format for review dates (default `F Y`).
* `if_reviews_show_more_label` — label of the "Show more" button.
* `if_reviews_update_checker_enabled` — disable GitHub update checks.

Reviews are stored as the `if_review` post type with meta keys `_if_reviewer_name`, `_if_review_date` (Y-m-d), `_if_rating` (1-5), `_if_review_text`, `_if_source`, `_if_source_label` and `_if_display_on_website` ('1' or '0'). Settings live in one option, `if_reviews_options`.

Source code, issues and releases: https://github.com/yog18r/Wordpress-Reviews

== Installation ==

1. Download `if-reviews.zip` from the latest release at https://github.com/yog18r/Wordpress-Reviews/releases
2. In wp-admin go to Plugins → Add New → Upload Plugin, choose the zip and activate it.
3. Go to **Reviews → Add New** and enter your first review: name, date, rating, source and text.
4. Optionally adjust colours and layout defaults under **Reviews → Display Settings**.
5. Add `[if_reviews]` to a page.

Future versions appear on the Plugins screen automatically.

== Frequently Asked Questions ==

= Does this pull reviews from Google or anywhere else automatically? =

No. Every review is entered manually. The plugin makes no network requests of its own apart from checking GitHub for plugin updates. If you need live sync, this is not the plugin for you; the appeal here is that it is small, under your control and keeps working regardless of what the platforms do.

= How do I hide a bad review without affecting the rating? =

Edit the review and untick "Display this review on the website". It disappears from the cards but remains in WordPress and still counts toward the average and the review count in the header. That is deliberate: hiding a review from view is a presentation choice; removing it from the maths would make the summary untrue. You can also hide or show many at once with the bulk actions on the Reviews list.

= Why does the header say "Reviews" and not "Google Reviews"? =

The header names a platform only when every review in that shortcode came from it. Reviews created before version 2.0 have no source recorded and are treated as "Direct". Select them on the Reviews list and use the bulk action "Set source: Google" (or whichever applies).

= Can I show only my Google reviews in one place and everything elsewhere? =

Yes: `[if_reviews source="google"]` on one page and `[if_reviews]` on another. Each instance calculates its own header from the reviews it includes.

= I have dozens of reviews. What is the best layout? =

A carousel keeps the page short: `[if_reviews layout="carousel"]`. If you prefer a grid, cap it and let visitors expand: `[if_reviews limit="6"]`. Both can be set as defaults in Display Settings.

= Can I change the colours to match my site? =

Yes. Reviews → Display Settings has colour pickers for the stars, card background, border and text, a corner-radius field and a comma-separated list of avatar colours that cycles across the cards.

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

= 2.0.0 =
The header no longer shows a Google logo, and says "Google Reviews" only when every review is marked as a Google review. After updating, go to Reviews, select your existing reviews and use the bulk action "Set source: Google" (or the platform that applies). Existing reviews, settings and your shortcodes otherwise carry over unchanged.
