# Optional image thumbnails

Liljevalchs task53662 / PR121, 27 November 2026. Local candidate only; not
installed on staging, assigned to published content, or released.

The staging original gallery12 is1200×600 plus a72px thumbnail strip on desktop.
Three100×60 thumbnails select the corresponding slide. At390px viewport the
actual page container is350px and the gallery is350×175; thumbnails are hidden.
After the observed click on thumbnail3 settled, public slide3 was active.

New optional `showThumbnails` applies only when strictly true and layout=below.
Its Swedish editor toggle is Visa miniatyrbilder. It uses the existing Swiper
pagination, replacing dots with native buttons containing decorative images.
Button names use the translated existing Go to slide index message. Source
URLs are assigned through DOM properties, not interpolated into HTML. Missing
images/video posters fall back to the slide number. No library media is changed.
The strip is72px high,100×60 per button, with active outline and focus treatment.
Long strips scroll horizontally. On mobile the strip is hidden; swipe, keyboard
and existing arrow navigation remain. Overlay sliders ignore the option.

Local built-Swiper browser fixture verifies desktop1200×672, three named buttons,
click3 selects3/3, Enter on2 selects2/3 and aria-current follows. A16-slide strip
can reach its last and first buttons without losing the leading items. Initial
custom pagination lacked names; explicit labels fixed this before final test.
The existing below overflow-visible behavior exposed slide rows outside the
page. Only the opted-in thumbnail mode now clips its own row. Isolated3- and16-
slide fixtures each have document width390 at viewport390 after the fix; the
gallery is390×195 and thumbnails display:none. Next selects2/3. Legacy below
overflow is unchanged and is not claimed fixed by this candidate.

Serialization tests prove14 older layouts unchanged plus false/absent/invalid
thumbnail values and overlay=true remain byte-identical. Existing mobile and
desktop padding tests pass. JS lint and build pass. SCSS reports the same six
pre-existing issues at82,88,125,133,257,258; none in the new rules. Three Swedish
catalogs remain identical and retain142 prior entries. Both generated PHP asset
files passed actual serverPHP8.3 -n -l via stdin; no WP/DB/bootstrap/installation.
Local server stopped, viewport reset and test tabs closed.

Separate exact-head review, staging package/preflight, native Redaktör toggle
save/reload/reset and real Liljevalchs+ container/theme comparison remain. No
full visual equivalence or launch readiness follows from the local fixture.
