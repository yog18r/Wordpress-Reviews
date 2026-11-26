# Translations

WordPress loads translations for the `if-reviews` text domain by itself. Put
`if-reviews-{locale}.mo` files (for example `if-reviews-de_DE.mo`) in
`wp-content/languages/plugins/`; translations made on translate.wordpress.org
arrive there automatically.

To generate a fresh template:

```bash
wp i18n make-pot . languages/if-reviews.pot --exclude=vendor
```
