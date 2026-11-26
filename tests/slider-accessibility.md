# Slider regression checks

Run from the repository root after installing the existing lockfile with
`npm ci --ignore-scripts --legacy-peer-deps`:

```sh
npm run build
node tests/slide-serialization.cjs
node tests/slide-editor-contract.cjs
node tests/slider-accessibility.cjs
php tests/php/slider-translations-smoke.php
```

The serialization regression compares twelve combinations against the actual
saved slide component at baseline commit `a38bd2b`. An absent new `mediaAlt`
attribute must preserve old markup. The new alternative text must be escaped.

The editor component contract renders the selected Slide against the matrix
control export observed in installed WordPress. A negative regression proves
the former nonexistent stable export fails. Actual WordPress insertion remains
the authoritative integration check.

The frontend regression executes the compiled webpack bundle, including real
Swiper, in JSDOM. It covers separate navigation/pagination instances, accessible
link names in the below layout, image alternative text preservation, single-slide
behavior, actual autoplay pause/play, focus pause, arrow keys, inactive slide
state through loop reordering, and initial/changed reduced-motion preference.
It also consumes the bundled Swedish catalog through the real WordPress i18n
library and checks actual generated controls, pagination labels and alt-field text.
The PHP check covers the exact three native handles, post-registration priority,
catalog shape and synchronized editor/frontend catalogs without a WP/DB bootstrap.

JSDOM does not prove visual layout, browser tab order/inert support, touch gestures,
screen-reader behavior, or WordPress editor insertion and save/reload. Those
require a real browser and an editor session before replacing a site's sliders.
Video playback and its independent controls are outside these image-slider tests.

This branch changes neither plugin versions nor release configuration. It does
not authorize a shared release or the removal of existing site sliders.
