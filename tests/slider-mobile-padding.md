# Optional mobile text padding

AGoodApp53662 / GoodBlocks PR121. Local implementation and staging native
verification are separated below.

Slider accepts optional mobileTextPadding with four integer pixel values:
top/right/left0–800, bottom80–800. Bottom reserves the existing control area.
Missing, incomplete, malformed, noninteger or out-of-range values add no saved
attributes/styles. Below-caption layout ignores the option. Four native range
controls preserve the other sides; Reset removes the entire optional value.
CSS variables apply only at768px and below. The editor's intrinsic mobile
layout is enabled only for blocks with validated spacing, preserving legacy
editor geometry. No frontend JavaScript, routes, requests or database writers.

Verification:

- slider-serialization.cjs:14legacy layout/ratio combinations byte-identical
  to the historical save; invalid/inactive max heights unchanged. Valid new
  insets serialized; missing/invalid/inactive padding keeps historical HTML.
- slider-mobile-padding-editor.cjs: four values/callbacks, other sides retained,
  reserved bottom minimum, reset, unset and below behavior pass.
- Targeted JS lint and npm build pass. New editor SCSS lint passes. Frontend
  SCSS retains exactly six existing lint failures, reproduced against HEAD:
  four descending-specificity and two indentation findings, unchanged lines.
- Generated index.asset.php passes actual PHP8.3-l through stdin. Only expected
  Slider metadata/editor JS/CSS/frontend CSS assets changed; view JS unchanged.
- Built CSS in real Chrome, isolated local fixture,1920px: legacy/custom both
 600px high and32px padding; no desktop effect. At390px/document390, legacy
 321.5625px with88/20/80/20padding; custom441.953125px with170/40/80/40padding
  and310px heading width. Editor fixture custom has the same441.953125px height
  and310px width. Legacy editor retains its previous195px height.
- Local fixture contains geometric inline images and no real customer media.
  Viewport restored, own tab closed and loopback server stopped after the test.

This local fixture alone does not establish WordPress native insertion/save/reload, translation,
staging asset installation or interaction with the site's stronger typography
CSS. The site companion CSS must use these variables rather than fixed
padding, with a fresh cache version. Native redactor and actual Swiper controls
remain to be tested on staging before any content conversion.

Original Svenskt Tenn's mobile391.953125px uses only30px total space below the
text; the candidate retains80px for visible controls and is therefore50px
taller when preserving the original170px top inset and310px text width.
No full visual parity is claimed. Desktop spacing, other source device sizing,
gallery thumbnails, media identities and conflicting links remain open.

## Staging native verification 2026-11-27

Installed feature/catalog source8975434b86e400016014191ac72b7c56c0ebfe77 with
site companion0701861. Ten-file package517d19b7aea50f0055fc6a42a7c5375e8a9d0f7b2a92deca01032033c9ca4b22,
installer1cb2076fcb880cdbb708a65a13369cc5b3b16fffceee5fa72fd2abd4c0ac30b3.
Independent preinstall review, nine installer rollback/drift tests, actual
two-file PHP8.3-l, isolated style registration and actual preflight passed.
Actual apply and final readback confirmed10after hashes,8preserve hashes and
one complete private manifest with10applied files. No published content writes.

Native existing Editor Test Testredaktörsson used disposable page35676 with
three Svenskt Tenn images. All six Swedish labels appeared. Four native
number inputs changed to170/40/90/40, minimum bottom80. Actual saved notice,
full reload and reopened panel retained all four values, marker and CSS vars.
No invalid-block notice, images/style/header marker retained.

Actual390px/document390: text top170, bottom361.953125, x40–350; slider451.953125px
high. All images loaded. Previous/Next buttons top395.953125, bottom435.953125,
inside viewport and below text. Pause/Next changed actual active2/3 to3/3.
Site CSS version20261127.2 loaded.

Native Reset, save/full reload removed marker and all four optional vars.
Frontend reload at390px returned88/20/80/20 padding and321.5625px height.
Draft35676 moved to recoverable Trash, undo URL ids35676; viewport reset and
own tabs closed. This proves the optional mobile controls in one concrete
native context, not full visual equivalence or published slider replacement.
