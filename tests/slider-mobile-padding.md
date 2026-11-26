# Optional mobile text padding

AGoodApp53662 / GoodBlocks PR121. Local candidate only; not installed on staging.

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

This does not establish WordPress native insertion/save/reload, translation,
staging asset installation or interaction with the site's stronger typography
CSS. The site companion CSS must use these variables rather than fixed
padding, with a fresh cache version. Native redactor and actual Swiper controls
remain to be tested on staging before any content conversion.

Original Svenskt Tenn's mobile391.953125px uses only30px total space below the
text; the candidate retains80px for visible controls and is therefore50px
taller when preserving the original170px top inset and310px text width.
No full visual parity is claimed. Desktop spacing, other source device sizing,
gallery thumbnails, media identities and conflicting links remain open.
