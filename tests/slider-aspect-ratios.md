# Source image proportions — 26 November 2026

The seven staging Smart Slider image sequences use either 1200×600 (2:1) or
1200×800 (3:2). The replacement previously offered neither format. The native
aspect selector now includes both. Frontend/editor styles implement them and all
five no-aspect/vh fallback selectors exclude them. Existing defaults are unchanged.
Parent block metadata version 2026.11.26.3 refreshes the CSS URLs. The new editor
formats also set the image frame ratio in the text-below layout.

Actual local Chrome tests with compiled CSS and loaded SVG images:

| Context | 2:1 | 3:2 |
| --- | --- | --- |
| Frontend, 900 px content width | 900×450, min-height 0 | 900×600, min-height 0 |
| Editor CSS, 900 px width | 900×450 | 900×600 |
| Editor CSS, actual 390 px viewport | 350×175 | 350×233.328125 |
| Frontend, actual 390 px viewport | 350×271.09375 | 350×271.09375 |

Both mobile documents have width 390. Frontend deliberately retains the existing
content-sized mobile layout so text and controls fit; desktop ratios are not
forced over mobile text. An attempted reading of the other inactive tab was
still desktop and was not counted as mobile evidence. A fresh active frontend
tab was resized and read instead. Viewport restored, tabs and local server closed.

Separate review found that text-below editor mode initially ignored the 2:1
selection: a 1200×800 image remained 900×600. This was reproduced in Chrome before
fixing the two new below-frame rules. After rebuilding, both cover and contain
produced image frames 900×450 / 900×600 on desktop and 350×175 / 350×233.328125 in
an actual 390 px viewport. A longer caption begins at or below the image bottom in
all eight cases; document width is 390 on mobile. The fixture accepts `?editor&below`
and `?editor&below&contain` to repeat these checks.

Build, targeted JS lint and compiled Swiper accessibility regression pass.
Targeted slider style lint still reports six existing specificity/indentation
findings. No PHP or saved parent serialization function changed.

`tests/slider-aspect-ratios-browser.html` exercises compiled frontend CSS;
`?editor` additionally loads compiled editor CSS. This is not an actual WordPress
selector/save/reload test.

The seven parent-slider build files from 1783d50 were subsequently installed only
on staging. Preflight verified their expected predecessors from 42eae3d. The
reviewed installer retained helpers.php and created a durable private backup
before atomic writes/full readback. Payload SHA256:
`e9e7c9aba3519639083be791a04c68a943ad24d7a0497a09deefd336d3e7d81d`.
The installer reports installed=true, files=7 and database_written=false. Nine
filesystem installer tests pass for this variant and both older variants.
Native WordPress selector/save/reload subsequently passed with the existing Editor
in disposable, unpublished draft35635. 2:1 below persisted after save confirmation
and full reload: editor1200×600, frontend840×420, actual390px frontend350×175 with
document width390. 3:2 overlay persisted after a second save/reload: editor1200×800,
frontend840×560 and actual390px frontend350×182.578125 with the existing content-
sized mobile behavior. Images actually loaded and editor CSS URLs have version.3.
The following local-caption/focal-point combined test also passed, then native
WordPress Trash confirmed one page moved with Undo for35635. The draft was never
published; own test tabs and temporary viewport settings were cleaned up.
No public content was converted; original Smart Slider/CVP remain active.
