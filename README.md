# IF Reviews

A small WordPress plugin for showing the reviews your business has already received, on your own site, under your own control.

You enter each review by hand in wp-admin: reviewer name, date, star rating, review text and where it was originally left (Google, Facebook, Trustpilot, Yelp, ProductReview.com.au, directly to you, or anywhere else). The `[if_reviews]` shortcode renders them as a card grid, a list or a carousel, with the overall rating, review count and a plain statement of where the reviews came from.

**IF Reviews is a manual import tool.** It does not connect to any review platform. Nothing is fetched, nothing is sent, and it keeps working whatever the platforms do with their APIs, pricing or terms.

- Latest release and download: [Releases](https://github.com/yog18r/Wordpress-Reviews/releases)
- WordPress-style readme with the full FAQ and changelog: [readme.txt](readme.txt)

## Use it honestly

The plugin is arranged so the easy path is also the truthful one.

- **Add every review you receive, including the unfavourable ones.** The header's average and count are calculated from all published reviews, not just the visible ones.
- **Hide, don't delete.** Untick "Display this review on the website" to keep a review off the cards. It stays in WordPress and still counts toward the average and the count.
- **Label sources accurately.** The header says "Google Reviews" only when every review in that shortcode is marked as a Google review. Otherwise it lists the mix: "24 reviews from Google, Facebook, and direct feedback".
- **Copy, don't edit.** Paste the review text as written.

Consumer protection law in many countries (the Australian Consumer Law, the US FTC rule on consumer reviews, the UK DMCC Act, among others) treats fabricated, edited or selectively presented reviews as misleading. The plugin gives you the means to present reviews properly. Using it truthfully is your responsibility.

## Features

- **Reviews** screen in wp-admin with name, date, rating, source, text and a show/hide toggle. Sortable columns, a source filter and bulk actions for sites with many reviews.
- **Per-review source badges.** Built in: Google, Facebook, Trustpilot, Yelp, ProductReview.com.au, Direct, Other (custom label). Add more with a filter.
- **Honest summary header** with fractionally filled stars and an automatic description of the sources, or your own title.
- **Three layouts**: grid (1 to 4 columns), list, carousel. Limit the count with a "Show more" button. Sort newest, oldest, highest or random.
- **Styling from a settings screen**: colours, corner radius and a cycling avatar palette with automatic contrast.
- **Lightweight**: one small stylesheet and one small script, loaded only where the shortcode renders. No jQuery, no external requests.
- **Automatic updates** from this repository's releases.
- Translation-ready and accessible.

## Installation

1. Download `if-reviews.zip` from the [latest release](https://github.com/yog18r/Wordpress-Reviews/releases/latest).
2. In wp-admin: Plugins → Add New → Upload Plugin → choose the zip → Activate.
3. Reviews → Add New to enter your first review.
4. Reviews → Display Settings to set colours and layout defaults.
5. Put `[if_reviews]` on a page.

Requires WordPress 6.3+ and PHP 7.4+.

## Shortcode

| Example | Result |
| --- | --- |
| `[if_reviews]` | All visible reviews with the defaults from Display Settings |
| `[if_reviews layout="carousel"]` | `grid` (default), `list` or `carousel` |
| `[if_reviews columns="2"]` | Up to two grid columns (1–4); collapses on small screens |
| `[if_reviews limit="6"]` | Six reviews plus a "Show more" button |
| `[if_reviews limit="6" show_more="no"]` | Six reviews, no button |
| `[if_reviews source="google"]` | One source only; the header describes that source alone. Several: `"google,facebook"` |
| `[if_reviews order="highest"]` | `newest` (default), `oldest`, `highest`, `random` |
| `[if_reviews show_header="no"]` | Cards only |
| `[if_reviews title="What our clients say"]` | Custom header title |

Source keys: `direct`, `google`, `facebook`, `trustpilot`, `yelp`, `productreview`, `other`.

## Updates

The plugin bundles [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) (MIT) and watches this repository's releases. New versions show up on the Plugins screen like any other plugin. To opt out on a site:

```php
add_filter( 'if_reviews_update_checker_enabled', '__return_false' );
```

## For developers

Filters:

| Filter | Purpose |
| --- | --- |
| `if_reviews_sources` | Add or rename sources (`label`, optional `description`, optional inline SVG `icon`) |
| `if_reviews_source_has_badge` | Decide which sources show a badge on cards (default: all except `direct`) |
| `if_reviews_reviews` | Alter the loaded reviews before stats and display |
| `if_reviews_date_format` | PHP date format for review dates (default `F Y`) |
| `if_reviews_show_more_label` | Label of the "Show more" button |
| `if_reviews_update_checker_enabled` | Disable GitHub update checks |

Data model: post type `if_review`; meta `_if_reviewer_name`, `_if_review_date` (Y-m-d), `_if_rating` (1–5), `_if_review_text`, `_if_source`, `_if_source_label`, `_if_display_on_website` (`'1'`/`'0'`); one option `if_reviews_options`.

Layout of the repository:

```
if-reviews.php          plugin bootstrap and constants
includes/helpers.php    options, sanitising, formatting helpers
includes/sources.php    source registry and badge icons
includes/post-type.php  the if_review post type and its admin list
includes/meta-box.php   the Review Details meta box and save handler
includes/settings.php   Reviews → Display Settings
includes/shortcode.php  [if_reviews]: data, header, layouts
includes/enqueue.php    front-end assets and settings-driven CSS
includes/updater.php    GitHub release update checks
assets/                 reviews.css, reviews.js (no dependencies)
vendor/                 Plugin Update Checker (MIT)
uninstall.php           cleanup on delete
readme.txt              WordPress.org-format readme, FAQ and changelog
```

### Releasing a new version

1. Bump the version in three places: the `Version:` header and `IF_REVIEWS_VERSION` in `if-reviews.php`, and `Stable tag:` in `readme.txt`. Add a changelog entry under `== Changelog ==`.
2. Commit, then tag and push:

   ```bash
   git tag v2.0.1 && git push origin main --tags
   ```

3. The [release workflow](.github/workflows/release.yml) checks the three version numbers match the tag, lints the PHP, builds `if-reviews.zip` (everything except the files in `.distignore`) and publishes a GitHub Release with that version's changelog as the notes. Sites running the plugin see the update within twelve hours, or immediately after "Check again" on the Updates screen.

Every push also runs `php -l` on PHP 7.4, 8.1 and 8.3.

## Contributing

Issues and pull requests are welcome. Keep the plugin small: no build step, no framework, no network calls beyond the update check. If you add a source, use a simple single-colour SVG mark rather than a platform's official logo.

## Licence

GPL-2.0-or-later. See [LICENSE](LICENSE).

The bundled Plugin Update Checker library is © Jānis Elsts and released under the MIT licence; see [vendor/plugin-update-checker/license.txt](vendor/plugin-update-checker/license.txt).
