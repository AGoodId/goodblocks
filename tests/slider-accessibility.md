# Slider regression checks

Run from the repository root after installing the existing lockfile with
`npm ci --ignore-scripts --legacy-peer-deps`:

```sh
npm run build
node tests/slide-serialization.cjs
node tests/slider-accessibility.cjs
```

The serialization regression compares twelve combinations against the actual
saved slide component at baseline commit `a38bd2b`. An absent new `mediaAlt`
attribute must preserve old markup. The new alternative text must be escaped.

The frontend regression executes the compiled webpack bundle, including real
Swiper, in JSDOM. It covers separate navigation/pagination instances, accessible
link names in the below layout, image alternative text preservation, single-slide
behavior, actual autoplay pause/play, focus pause, arrow keys, inactive slide
state through loop reordering, and initial/changed reduced-motion preference.

JSDOM does not prove visual layout, browser tab order/inert support, touch gestures,
screen-reader behavior, or WordPress editor insertion and save/reload. Those
require a real browser and an editor session before replacing a site's sliders.
Video playback and its independent controls are outside these image-slider tests.

This branch changes neither plugin versions nor release configuration. It does
not authorize a shared release or the removal of existing site sliders.
