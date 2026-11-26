# Optional destination labels

An optional per-slide `linkLabel` lets editors describe a destination when the
visible heading is empty or unsuitable as a link name. It appears with the
existing Link control and has Swedish strings in the slide editor catalogue.
It does not change the URL, target or media library metadata.

Only a nonblank string adds `data-link-label` and replaces the saved anchor's
screen-reader text. React escapes the value. Missing, blank and invalid values
keep the previous markup; this avoids invalidating older saved slides.

The frontend also reads the optional label when building below-layout links or
reconstructing an overlay link. It uses an aria-label, not innerHTML. Existing
heading/image-alt/caption/localized View slide fallback behavior remains.

Verified locally:

- Twelve existing image/video/heading/link-target save fixtures are byte-identical.
- Explicit label is trimmed, escaped and used for an otherwise unnamed link.
  Blank, missing and invalid labels preserve default HTML; markup injection is text.
- Editor component contract exposes the value and updates only linkLabel.
- Built frontend with real Swiper uses the explicit label in overlay and below
  layout without changing the destination. Existing control/focus/keyboard/
  reduced-motion/multi-instance and Swedish catalogue checks still pass.
- Targeted JS lint and production build pass. Generated slide/index.asset.php
  and slider/view.asset.php still need server PHP-lint before staging installation.

No plugin release, global updater/version bump, public content migration or
native Editor save/render test is implied by these local checks.
