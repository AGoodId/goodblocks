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
  and slider/view.asset.php subsequently passed actual server PHP8.3 lint.

## Actual staging Editor check

The six-file436be23 package was independently reviewed, installed only on
stage.liljevalchs.se with exact before-hash preflight, durable private backups
and full after-readback. The older stage helper and parent block metadata were
preserved. No release, updater change or database invocation by the installer.

Existing Editor Test Testredaktörsson created disposable page35664. A linked
slide with an empty heading was imported through the native code editor. The
existing pressed Länk toolbar control showed the new Swedish field and help.
Entering Läs om Ylva Snöfrid – Musernas hus, saving, reloading and reopening the
same control confirmed persistence. Native code contained exactly one linkLabel
and one matching data-link-label. Other slide labels and URLs stayed unchanged.

Actual overlay preview exposed that screen-reader link text with the original
Ylva URL and _self target. After changing the parent Layout to Text under bilden
with its native control, saving/reloading and previewing again, the generated
figure link used the same aria-label and destination. At actual390px, document
width was390. Pause and Go to slide1 exposed exactly one accessible link with
the custom name. The untouched second slide retained its legacy fallback.

Native Trash confirmation and Undo ID verified page35664 cleanup; a later
readonly SQL check confirmed page/trash. Viewport reset, test tabs closed and
local source server stopped. Final six file hashes, preserved files and complete
backup manifest were rechecked. This tests the field and both layouts; it does
not claim a full migration, all old links repaired, or a production release.
