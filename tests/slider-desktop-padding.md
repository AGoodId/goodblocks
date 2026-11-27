# Optional large-screen text flow

AGoodApp53662 / GoodBlocks PR121. Initial build installed on staging; the
native placeholder follow-up below is not installed yet. No published content
converted.

Slider accepts optional desktopTextPadding (four integer pixel insets0–800).
Only a complete valid overlay value emits its marker/four CSS vars. Missing,
malformed, inactive or out-of-range data preserves saved legacy HTML exactly.
Four native controls and Reset are independent of the existing mobile option.
Three identical Swedish catalogs contain the new title/help/reset strings.

At769px and above, browsers supporting inline-size containers use the slide's
own width to retain its selected image proportion/height cap as a minimum.
The text stays in normal flex flow; content plus insets can grow past that
minimum. Its existing horizontal text alignment and maximum text width remain.
The editor's active slide uses flex only in this validated opt-in mode; its
default block display and hidden inactive slides are retained elsewhere.
No frontend JavaScript, requests, routes or database writers were changed.

Verification:

- 14 historical layout/ratio save combinations are byte-identical. Existing
 mobile/capped saves are also byte-identical to084f4647. Invalid desktop data
 and below-caption layout keep previous HTML; valid zero insets and combined
 mobile/desktop vars serialize independently.
- Editor callback/default/reset tests pass for both independent sets of insets.
 Targeted JS lint and editor SCSS lint pass. Frontend SCSS has the same six
 pre-existing findings on unchanged lines82/88/125/133/257/258; the new rules
 introduce none. npm build passes, with source and generated assets retained.
- Independent review found that the first catalog merge lost the slide
 editor's two existing screen-reader link strings. They are restored in the
 shared union. slider-catalogs.cjs now verifies all142 previous per-handle
 message entries against084f4647 as well as the new desktop/help strings and
 the identical resulting catalogs. This correction has not been installed.
- Real Chrome loads the built frontend CSS/Swiper in geometric-image fixtures.
 At1920px/document1920, legacy compact remains600px with textTop261.609375.
 Desktop320/20/270/20 gives666.78125px, textTop320, textLeft380, width1160.
 Short34px/51px heading with350/20/0/20 retains600px and textTop449.5.
 These reproduce the earlier measured two source geometries; the fixture
 uses Georgia and geometric images, not the site's font/media/runtime.
- Within a900px content area, the short case is450px/textTop374.5. With zero
 vertical insets, ratios16:9/4:3/3:1/3:2/2:1 yield506.25/600/300/600/450px.
 No-ratio50vh gives540px at1080px viewport height. Editor fixture matches
 all11 cases and exposes exactly one slide in each.
- At390px/document390, compact legacy/custom/contained cases all remain
321.5625px with88/20/80/20 padding and textLeft20. Desktop vars have no mobile
 effect. Real Next1/2→2/2 and Previous2/2→1/2 preserve height/position after
 transitions. Intermediate resize/transition coordinates were not accepted
 as settled geometry. All geometric images loaded.
- Generated index.asset.php passes actual server PHP8.3-l via stdin. Only the
 seven expected Slider metadata/editor/CSS assets changed; view JS unchanged.
- An initial browser read used cached old CSS; versioned fixture URLs corrected
 it. A later viewport call still targeted the other open tab; actual innerWidth
 exposed this, and final measurements used the selected remaining fixture.
 Both temporary viewports were reset, both tabs closed and loopback server
 stopped. No customer HTTP traffic or database was involved.

Still required before use: independent code review, bounded staging asset
installation, actual native
Editor insert/change/save/reload/Reset and runtime visual comparison using the
real source images/fonts. This does not establish full seven-slider parity,
thumbnails/media/link identity or authorize published replacement/deactivation.

## Native test and empty-field follow-up

ba0e42a was installed on staging through the ten-file package595611e8…,
installer2e9139d6…. Actual read-only preflight, ten after hashes, ten preserve
hashes, exact private before-backups and one complete payload-bound manifest
passed. No WP/database invocation or published content conversion by installer.

Existing Editor Test Testredaktörsson used disposable page35679, Astra full
width/no sidebar. Two previously native-validated v9 examples (five images)
were inserted. Actual save notice and full reload accepted the old HTML with
the new build. Swedish desktop panel/help/four controls/Reset appeared.
Native350/20/0/20 and320/20/270/20 were entered and a second actual save notice
observed. Full reload of those new values and frontend remain pending.

This real editor exposed a missing fixture case: Core's empty RichText p
contains a zero-width marker and data-rich-text-placeholder span. Its40.796875px
line shifted the short heading to421.09375 rather than449.5 while the Slider
parent was selected. The corrective opt-in desktop CSS hides empty native
placeholder fields only while their Slide is unselected. Selecting that Slide
reveals the editable field again; nonempty fields, legacy sliders and mobile
are unaffected. Metadata version2026.11.27.3 refreshes stylesheet URLs.

The editor browser fixture now models Core's placeholder span and a selectable
Slide. Built CSS gives600px/textTop449.5 for the short case and666.78125px/
textTop320 for the compact one. Selecting exposes contenteditable placeholder;
deselecting restores display:none and the exact compact geometry. Build,
14legacy serializations and unchanged six-old-SCSS-findings check pass.
The existing generated PHP asset is unchanged from the checked build.
Both temporary servers/tabs were closed; native draft35679 remains for the
post-install save/reload/Reset and Trash steps, not for publication.
