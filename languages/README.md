# Translations

Place `if-reviews-{locale}.mo` / `.po` files in this folder, for example
`if-reviews-de_DE.mo`. The plugin loads them with `load_plugin_textdomain()`
using the text domain `if-reviews`.

To generate a fresh template:

```bash
wp i18n make-pot . languages/if-reviews.pot --exclude=vendor
```
