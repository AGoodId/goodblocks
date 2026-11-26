# Slider script translations

The three `goodblocks-sv_SE-<handle>.json` files are native WordPress Jed catalogs
for the slider frontend and slider/slide editors. They contain the same compact
catalog so loading one of these scripts does not replace another's translations.
Keep all three files in sync when updating the catalog.

`goodblocks_register_slider_translations()` sets the bundled path after blocks
register their scripts. WordPress resolves the locale at script-print time.
Other locales fall back to normal
WordPress language packs or the English source messages.

The catalogs live under `inc` because the existing release packaging includes
`inc` and `build`. No release workflow change or public release is required by
this source change.
