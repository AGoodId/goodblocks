# Changelog

Alla noterbara ändringar dokumenteras här.
Format baserat på [Keep a Changelog](https://keepachangelog.com/sv/1.0.0/).
Versioner följer [Semantic Versioning](https://semver.org/).

Genereras automatiskt från [conventional commits](https://www.conventionalcommits.org/).

---

## [Unreleased]

## [1.13.0-rc.1] — 2026-05-01

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

