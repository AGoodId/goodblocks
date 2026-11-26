# Local slide captions — 26 November 2026

The actual staging Editor workflow exposed that the text-below layout displayed
the inherited media caption without an editing field. This blocked the requested
editorial control. A native TextareaControl now edits the existing `caption`
attribute in below mode, with Swedish label/help explaining that only this slide
changes. No media API or attachment metadata write is added. Save/frontend code
is unchanged: the caption remains escaped attribute text rendered as text content.
Metadata version2026.11.26.4 refreshes the editor assets.

Build, targeted JS lint, component contract and twelve legacy save serializations
pass. The component test verifies that the existing caption enters the native
control and that onChange sets only the block's caption. Separate review found
no blocking data-flow or compatibility issue.

Before installation, native media details for the selected image
Heavens_Redigerade-scaled-1 (attachment35512) were read: its library caption is
empty. It was not edited. That value is the independent UI comparison for the
local-caption trial in unpublished draft35635.

The four-file candidate was installed only on staging after hash-bound preflight,
private backup and complete readback. PHP8.3 lint passed for the candidate's
generated asset file and the current parent editor asset file. Helpers.php stayed
unchanged; no WordPress/database operation was performed by the installer.

Actual Editor trial: the Swedish Bildtext field accepted a longer local caption
with newline, ampersand and quotes. After save confirmation and full reload,
both the field and editor figcaption retained the exact text. Native media details
for35512 were then reopened and still showed an empty library caption for the
same file URL, proving the local edit did not alter that shared caption.

The native frontend preview retained the exact text. Desktop image840×420 plus
caption76px gave total496px; caption starts at image bottom and ends at slider
bottom. Actual390px frontend had350px width and336.15625px total height; caption
again starts after the image and remains within the slider. Document width390,
image loaded. The same test draft is retained temporarily to verify the subsequent
below-layout focal-point correction before recoverable Trash cleanup. No public
content conversion or Smart Slider/CVP deactivation.
