# Changelog

Alla noterbara ändringar dokumenteras här.
Format baserat på [Keep a Changelog](https://keepachangelog.com/sv/1.0.0/).
Versioner följer [Semantic Versioning](https://semver.org/).

Genereras automatiskt från [conventional commits](https://www.conventionalcommits.org/).

---

## [Unreleased]

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

