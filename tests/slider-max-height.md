# Optional overlay image height cap, 26 November 2026

The original Liljevalchs Vårsalongen slider renders at 1920×600 in a 1920px
viewport despite its stored 1200×600 canvas. Merely copying the 2:1 aspect ratio
would produce a 960px-high full-width replacement. This change adds an optional
editor height cap instead of changing defaults for existing consumers.

`maxImageHeight` accepts integer pixel values from 100 through 1600. It applies
only to overlay layouts with an aspect ratio. Missing/invalid/0 values, below
layouts and no-aspect layouts preserve the prior saved HTML. The Swedish editor
label is Bildens maxhöjd (px); 0 means unlimited. The existing mobile text layout
at max-width768px deliberately removes the cap so long text remains readable.

Validation:

- `npm run build` and targeted JS lint passed. Generated assets are committed.
- `node tests/slider-serialization.cjs`: 14 legacy combinations are byte-identical;
  nine invalid values per combination preserve markup; valid boundary values and
  inactive below/no-aspect cases passed. A separate reviewer also compared 84
  older/invalid combinations in memory and found no remaining blocker.
- `node tests/slider-accessibility.cjs` passed with the new build.
- Parent stylelint still reports the same six baseline findings; no added
  findings after moving the mobile reset into the existing768px rule.
- The exact generated index.asset.php bytes, SHA256
  `52e30028dd4a25828177e672ff87a5473caa27ae11a1395c5a7ab95bd1f29d33`,
  passed PHP8.3 `-l` through stdin on the existing server, without file/DB writes
  or WordPress bootstrap. The first attempt hit Python3.6's unsupported
  capture_output argument; using explicit PIPE arguments passed.

Actual local Chrome with the built CSS in slider-max-height-browser.html:

- At1920px, uncapped2:1 frame/image height960; caps100/600/800 produced exactly
  100/600/800 in frontend and editor. All fixture images loaded.
- At768px, every frontend fixture, including cap100, grew with its editorial
  text to309.71875px, computed max-height none, document width768.
- At390px, every frontend fixture grew to338.265625px, computed max-height none,
  document width390. No horizontal overflow.
- Viewport restored; owned local browser tab closed; temporary local server stopped.

The eight-file scoped package was subsequently installed on staging after
independent exact-byte review, passing preflight, private file backup and readback.
Existing helpers.php was preserved; no database/activation/release was involved.

Actual existing Editor account then inserted Slider into disposable draft35646,
chose2:1, maximum600, native Full width and an existing Media image35512. Saving
and full reload retained600 and the CSS variable; editor frame1640×600.
The default Astra page sidebar initially constrained frontend to840×420. Using
the existing Astra container Full width and No sidebar options, then saving and
reloading, produced1920×600 atx0/document1920. At390px the image was loaded,
390×182.578125 atx0/document390, computed wrapper max-height none. Native Trash
confirmation and Undo ids35646 verified cleanup. No library metadata changed.

The remaining real-template/content comparisons are separate gates. No published
slider, plugin activation, release or production configuration was changed.
