# Slide focal point — 26 November 2026

Staging's Smart Slider source uses noncentral background positions, including
48% 38% and 50% 55%. The replacement needs to preserve them. `goodblocks/slide`
now has an optional `focalPoint` attribute and the native WordPress
[FocalPointPicker](https://developer.wordpress.org/block-editor/reference-guides/components/focal-point-picker/)
for cover-fitted images. The Swedish label is “Bildens fokuspunkt”.

Valid finite x/y coordinates in 0–1 become the same inherited CSS custom property
in editor and save output. Image/video CSS applies it as object-position. Missing
or invalid values emit no new property; centered legacy defaults remain. Metadata
version 2026.11.26.2 changes the CSS URLs as well as the rebuilt editor script.

Verified:

- `node tests/slide-serialization.cjs`: 12 legacy serializations byte-identical;
  focal coordinates persist for image/video; invalid values preserve old markup;
  edge coordinates and decimal percentage normalization pass.
- `node tests/slide-editor-contract.cjs`: native picker receives existing/default
  values, and onChange updates the attribute. This is a component contract test,
  not an actual WordPress save/reload test.
- Build, targeted JS lint, metadata JSON parse and diff whitespace checks pass.
- Actual Chrome at localhost with compiled frontend CSS: all three 400×100 SVGs
  load in 200×200 cover-fitted image boxes. Default is centered (half red/blue),
  x=0 is fully red and x=1 fully blue, confirmed visually and via computed
  object-position values. Browser tab and local server closed after the check.
- Separate code reviewer found no remaining blocker after the metadata cache
  version correction. Reviewer performed code/test reading only.

Targeted style lint has nine findings in untouched legacy lines (indentation and
named white colors); baseline source was checked separately. No broad cleanup.

The eight compiled/catalog files from commit 257c6df were installed only on
staging with expected-before hashes, durable private file backup, atomic writes
and full readback. Payload SHA256:
`0b70079b29abc6483c176fce84f0c1313f4a4f377577d0b304eb0e0b3276adbc`.
The existing helpers.php stayed unchanged. No WordPress bootstrap, database
operation, public content conversion or plugin deactivation was performed by
the installer.

The existing staging Editor subsequently inserted a Slider through the native
inserter in disposable draft 35633, selected an existing library image and set
48/38 using the Swedish native focus-point number controls. After “Utkast sparat.”
and a full reload, both controls and the loaded editor image retained 48% 38%.
Editor CSS URLs include ver=2026.11.26.2. The native frontend preview retained the
image, heading/body and computed object-position. At 390 px its image was loaded,
350 × 243.84375 px, with no document horizontal overflow. The draft was never
published and was moved to recoverable Trash; the page list confirmed one page
moved with Undo for ID35633. Media library metadata was not edited. Temporary
viewport and own tabs were cleaned up. This closes that bounded editor workflow,
not the conversion of public Smart Slider contexts.

A later combined below-layout/caption trial exposed that frontend conversion
replaced the saved slide with a figure and lost its explicit focus CSS variable.
The compiled Swiper test reproduced this (expected48%38%, actual empty). The
conversion now copies only that saved variable when present. The rebuilt compiled
test passes for both a saved focal point and a legacy slide without it. Save
markup is unchanged. Parent metadata version2026.11.26.5 refreshes frontend JS.
This follow-up has not yet been installed or verified in the actual preview.

Use `tests/slide-focal-point-browser.html` through a local HTTP server to repeat
the compiled CSS visual check.
