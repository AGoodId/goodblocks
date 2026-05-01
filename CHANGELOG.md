# Changelog

Alla noterbara ändringar dokumenteras här.
Format baserat på [Keep a Changelog](https://keepachangelog.com/sv/1.0.0/).
Versioner följer [Semantic Versioning](https://semver.org/).

Genereras automatiskt från [conventional commits](https://www.conventionalcommits.org/).

---

## [Unreleased]

## [1.13.0-rc.6] — 2026-05-01

### Special visual slot for story-card

> Sjätte RC. Gör story-card användbart för fler av DENSIQ:s storymoduler genom att tillåta HTML-baserad visualisering i högerspalten, inte bara bild/video.

### Added
- `visualHtml`-attribut på `goodblocks/story-card` för server-renderad visual-slot i storyns sekundärkolumn.
- Gör det möjligt att bära över befintliga ASR-visualer som countdowns, stat-panels och before/after-kompositioner utan att först bygga separata block för varje variant.

### Fixed
- `story-card` kan nu rendera en meningsfull högerspalt även när modulen saknar bild/video men har strukturerad visual-HTML.
- text-only-fallet får en renare enkelkolumnslayout i stället för att riskera en tom högerspalt.

## [1.13.0-rc.5] — 2026-05-01

### Anchor reliability fix

> Femte RC. Liten men viktig server-side-fix för story-card när block används som ersättare för befintliga storymoduler med deeplinks.

### Fixed
- `goodblocks/story-card` sätter nu `id` explicit från blockets `anchor`-attribut i `render.php`, i stället för att enbart förlita sig på att wrapper-attributen alltid injicerar ankaret automatiskt.
- Gör blocket säkrare för DENSIQ-migrering där befintliga story-id:n behöver följa med till den blockbaserade sidan.

## [1.13.0-rc.4] — 2026-05-01

### Etapp 2b: DENSIQ-aligned layout polish

> Fjärde RC. Tajtar story-card mot DENSIQ:s faktiska story-module-geometri i stället för att bara se “generellt editorial” ut.

### Changed
- **Default och reverse följer nu rakare DENSIQ-grid** med 50/50-kolumner, tydligare kolumnpadding och markerad skiljelinje mellan text och media.
- **Disclosure-raden kalibrerad mot ASR-mönstret**: mindre, mer självsäker “Read more”-behandling med versaler, tätare tracking och bättre relation till actions-raden.
- **Body-typografi för disclosure** tajtad med tydligare nivåer för `h2`/`h3`/`h4`, så utvikt innehåll känns mer som rapportmodul än standard-WP-text.
- **Bg-full närmare originalstrukturen** med 50/50-inner grid och tydligare textpadding ovanpå bakgrundsmedia.

### Fixed
- Mobilcollapse följer nu DENSIQ närmare: text först, media efter, renare paddings och stabilare mediahöjder i default/split/bg-full.
- `story-card` använder nu samma maxbreddslogik som DENSIQ:s story-modules (`1600px`) i stället för en friare shell-bredd.

## [1.13.0-rc.3] — 2026-05-01

### Etapp 2: visual fidelity-pass och layoutpolish

> Tredje RC. Fokus på proportioner, full-width-beteende och att få story-card att läsa mer som editorial-modul än generiskt kort.

### Changed
- **Default-layout omkalibrerad** med bredare editorial-shell, tydligare text/media-proportion och starkare vertikal rytm.
- **Full align-beteende förbättrat** så blocket kan bära en riktig full-width story-yta i stället för att kännas inträngt i contentkolumnen.
- **Typografin tajtad**: rubriken balanseras bättre i boxen och ingressen får lugnare radlängd.
- **Media-ytan stärkt** i `default` och `reverse` med mer konsekvent höjd och `object-fit: cover` på desktop.
- **Action/disclosure-spacing** integrerad tydligare i kompositionen.

### Fixed
- `split-right` har nu explicit ordning för text/media på desktop.
- `bg-full` får preview av bakgrundsmedia i editorn, så layouten går att bedöma utan frontend-gissning.
- Mobil-layouten släpper textens maxbredd renare och minskar glapp mellan text- och mediaflöde.

## [1.13.0-rc.2] — 2026-05-01

### Etapp 2: alla 5 layouts + visual fidelity-pass (default)

> Andra RC. Default-layout omkalibrerad för editorial-känsla; reverse, split-left, split-right, bg-full implementerade.
> Smoke-test på fse.agoodsite.se/asr-test innan v1.13.0 final.

### Changed
- **Default-layout: full-width by default** (`supports.default.align: "full"`). Block ska kännas som editorial-modul, inte centrerat kort i content-area.
- **Tighter container proportions** matchar DENSIQ:s `--module-pad`/`--pad-x`-skala (`clamp(56px, 6vw, 96px)` block, `clamp(24px, 5vw, 80px)` inline).
- **Title line-height 1.0** (super tight, premium-känsla) — tidigare 1.05.
- **Kolumn-proportions:** default 7fr/5fr (text större), reverse 5fr/7fr — matchar DENSIQ-stories.
- **Disclosure-styling integrerad** — ingen bordered button-look. Bara text + chevron med `border-top` mot body. Plus-ikon roterar 45° till × när öppen.
- **Vertical rhythm omkalibrerad:** tight (0.75rem) inom header, base (1.5rem) mellan major sections, loose (2.5rem) till disclosure.
- **Text-max-width:** 32rem (~640px) för optimal läsbarhet inom större container.
- **Bg-full:** min-height `clamp(480px, 70vh, 720px)` för teatralisk känsla, inte bara 60vh.

### Added (Etapp 2)
- `goodblocks/story-card`: layouts `reverse`, `split-left`, `split-right`, `bg-full` är nu implementerade
- Mobile-collapse-breakpoint: 768px (matchar DENSIQ)
- Split-layouts: media får `min-height: clamp(360px, 50vw, 560px)` med `object-fit: cover` på desktop
- Bg-full: `<article>::after`-overlay för text-läsbarhet, alla text-element forced-white oberoende av theme



> **Pre-release for smoke-testing on fse.agoodsite.se before v1.13.0 final.**
>
> Etablerar nytt arkitekturmönster i GoodBlocks: **dynamic block + InnerBlocks**. Story-card är första blocket som kombinerar `render: file:./render.php` med `<InnerBlocks.Content />` i save.js. Måste smoke-testas på riktig WP-instans innan layouts utöver default byggs ut.

### Added (Etapp 1 — default layout)
- `goodblocks/story-card` (NEW) — long-form story-block med disclosure (`<details>`/`<summary>`):
  - 15 attribut: `layout`, `theme`, `kicker`, `title`, `excerpt`, `mediaId`/`mediaUrl`/`mediaAlt`/`mediaType`, `actionUrl`/`actionLabel`/`actionTarget`, `labels`, `summaryLabel`, `openByDefault`
  - InnerBlocks med template + allowedBlocks (heading, paragraph, list, image, quote, kpi-grid)
  - Native `<details>`/`<summary>` för disclosure — keyboard, screen reader, no-JS-fallback fungerar gratis
  - Smooth-expand via `interpolate-size: allow-keywords` (Chrome/Edge 129+); instant fallback i andra browsers
  - `supports.anchor: true` för deep-links via Gutenberg-built-in
  - Tema-varianter: light/dark/accent med `--wp--preset--*`-fallback
  - **Default-layout** fullt implementerad. Reverse/split-left/split-right/bg-full visas som val i SelectControl men har inte fullständig CSS — implementeras i Etapp 2

### Architecture notes
- **NEW PATTERN: dynamic block + InnerBlocks.** Story-card är första GoodBlocks-blocket där `save.js` returnerar `<InnerBlocks.Content />` (för persistens) samtidigt som `render.php` echo:ar `$content` (för server-render). Mönstret ska smoke-testas innan utbyggnad.
- **`interpolate-size`** scoped till `.wp-block-goodblocks-story-card` (inte `:root`) för att undvika global påverkan.
- **Editor-preview** använder `<div>` runt InnerBlocks (inte `<details>`) så redaktör inte kan toggla bort innehåll. Frontend renderar med native `<details>` via render.php.

### Smoke-test innan v1.13.0 final
1. Skapa story-card i editorn på fse.agoodsite.se
2. Lägg in heading + paragraph + `goodblocks/kpi-grid` som InnerBlocks
3. Spara, ladda om editorn — inner blocks ska bevaras, ingen "block invalid"
4. Frontend: `$content` ska renderas inom `<details>` body, summary togglar open/close
5. Anchor: `<article id="...">` får WP-anchor och `#anchor` scrollar rätt

### Known limitations (Etapp 1)
- Layouts utöver `default` har inte fullständig CSS — välj endast `default` för smoke-test
- Smoke-test på fse.agoodsite.se kräver **manuell zip-upload** (RC är prerelease, GitHub Updater ser den inte)


## [1.12.0] — 2026-04-30

### Added
- `goodblocks/section-header` — display-rubrik med kicker/lead, alignment + numberPosition, light/dark/accent-teman. Tema-agnostiska defaults (inga hårdkodade font-family eller letter-spacing).
- `goodblocks/kpi-grid` — 1–6 KPI-tiles med stabilt id, value/label/prefix/suffix per tile, columns auto/2-6, mobile-first responsiv layout (640/960px breakpoints), light/dark/accent-teman.
- `goodblocks/hero` — text-animation `fade-up` + `split-words` (CSS-only, IntersectionObserver-trigger), klickbar scroll-pil med smooth-scroll. Respekterar `prefers-reduced-motion`.
- Showcase-registrering för 3 nya block + hero i `inc/showcase.php` (5 live-config-exempel).

### Changed
- `goodblocks/hero` PURGE: tog bort 4 trasiga legacy-animationer (`standard`, `wild`, `from-right`, `from-left`), 2 oanvända attribut (`backgroundType`, `positionClass`), odokumenterade konventioner (`||br||`-token för radbrytning, `*`-pulse-effekt). Legacy animation-värden mappas tyst till `none` via render.php (graceful degradation, ingen DB-migration). Editor-knapp ändrad från `<a>` till `<button type="button">` för konsistens med frontend. Scroll-pil bytt från `<span>` till `<button>` (klickbar + tangentbordsåtkomlig + 44×44 touch-target).

### Fixed
- Hero `height: 100svh` får nu `var(--wp-admin--admin-bar--height, 0px)` med fallback — förhindrar att `calc()` blir ogiltigt och kollapsar höjden till `auto` när admin-bar inte finns på utloggat frontend.
- Hero animationer tål no-JS: `fade-up`/`split-words` är gated på `.goodblocks-js`-klass som JS lägger till på `<html>`. Utan JS renderas hero-text synligt direkt (inget opacity:0).

### Known limitations
- `goodblocks/hero` har inga aktiva URL-attribut för CTA-knappen (`buttonUrl`/`buttonTarget` är specade men inte implementerade i v1.12.0). Knappen renderas som `<button type="button">` utan länkbeteende — får sin funktion via custom JS eller utökas i v1.13.
