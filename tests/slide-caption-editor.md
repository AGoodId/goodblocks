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
no blocking data-flow or compatibility issue. Actual staging installation and
caption edit/save/reload remain to be verified.

Before installation, native media details for the selected image
Heavens_Redigerade-scaled-1 (attachment35512) were read: its library caption is
empty. It was not edited. That value is the independent UI comparison for the
later local-caption trial in unpublished draft35635.
