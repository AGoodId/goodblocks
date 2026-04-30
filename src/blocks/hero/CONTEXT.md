# `goodblocks/hero` — Context

> Captured 2026-04-29 inför Sprint A: report-foundation-blocks.
> Strukturell + semantisk kontext härledd från koden; filosofisk från intervjun med Mats.

---

## 1. Module Manifest (Strukturell kontext)

**Modul:** `goodblocks/hero` — full-bredd hero-sektion med bakgrundsmedia (bild/video), rubrik, ingress, knapp, overlay och scroll-triggade animationer.

### Dependencies (uppströms)

| Beroende | Typ | Anmärkning |
|---|---|---|
| WordPress 6.4+ Gutenberg | Plattform | `registerBlockType`, `useBlockProps`, `InspectorControls`, `RichText`, `MediaUpload` |
| `@wordpress/i18n`, `@wordpress/components`, `@wordpress/block-editor`, `@wordpress/blocks` | NPM (wp.*) | Frontend WordPress-paket |
| `../../shared` (`getPositionClassName`) | Lokal modul | **Inte audited här — potentiell dark code** |
| WordPress PHP-API | Server | `wp_kses_post`, `esc_url`, `esc_attr`, `get_block_wrapper_attributes` |
| `block.json` schema (apiVersion 3) | Konfiguration | Standard Gutenberg block-format |

### Dependents (nedströms)

| Beroende | Typ | Anmärkning |
|---|---|---|
| `goodblocks.php` | Plugin-rot | Registrerar via slug-loop i `goodblocks_register_blocks()` |
| `webpack.config.js` | Build | Entry: `blocks/hero/index` (ingen separat `view.js`-entry idag) |
| Användarsidors `post_content` | DB | `<!-- wp:goodblocks/hero {...} -->`-markers i `wp_posts` |
| `inc/showcase.php` | Style guide | **NOT YET REGISTERED** — hero saknas i showcase-filtret |
| Framtida Sprint A view.js | Build | Kommer läggas till för fade-up + split-words + scroll-trigger |

### Dataflöde

| Operation | Källa | Destination |
|---|---|---|
| Läs | `$attributes` från block-instans | `render.php` output |
| Läs | `backgroundMedia.url` | `<video src>` / `background-image` CSS |
| Skriv | Inga DB-skrivningar | — |
| Skriv (editor) | `setAttributes` i edit.js | block-instans → `post_content` vid spara |

### Delade resurser

Inga. Hero är självförsörjande — delar inte Swiper-bundle (testimonials gör), REST-routes (search-autocomplete gör), eller några transients/options.

### Deployment

Del av `goodblocks`-plugin via WordPress plugin-livscykel. Distribueras som zip via GitHub Releases, hämtas via `GoodBlocks_GitHub_Updater`.

### Ägare

AGoodId — **ingen formell CODEOWNERS-fil i repot**. Känt gap (se `dark-code-audit` 2026-04-29).

---

## 2. Behavioral Contracts (Semantisk kontext)

Hero är ett Gutenberg-block — dess "gränssnitt" är block-API:et: attribut in, HTML ut.

### `render.php` — server-side render

| Egenskap | Värde |
|---|---|
| Idempotent | Ja, ren funktion av `$attributes` |
| Failure modes | Graceful degradation: saknad `backgroundMedia` → ingen video; saknad `rubrik` → tom `<h2>` döljs; **legacy animation-värden** (`ingen`/`standard`/`wild`/`from-right`/`from-left`) → mappas tyst till `none` (post-PURGE) |
| Performance | ~1ms/instans, inga DB-queries utöver block-parsing |
| Sidoeffekter | Inga |
| Retry-säkerhet | Säker — idempotent |
| Datakänslighet | `rubrik`/`text`/`button` kan innehålla användarinput-HTML — saneras via `wp_kses_post` på frontend, `esc_html` per-tecken i animation-loopen (legacy, försvinner i PURGE) |

### `edit.js` — editor-side render

| Egenskap | Värde |
|---|---|
| Idempotent | Ja, React-komponent |
| Failure modes | Saknad `backgroundMedia` → ingen preview-video; React error boundaries fångar fel |
| Performance | Direkt (inga remote calls) |
| Sidoeffekter | `setAttributes` uppdaterar attribut, persistas till `post_content` vid spara |
| Datakänslighet | Samma som render — `RichText`-content lagras i attribut |

### `block.json` attribut (input-kontrakt)

Se `block.json` för aktuella attribut. Post-PURGE (Sprint A FAS 1):

| Attribut | Typ | Default | Kontrakt |
|---|---|---|---|
| `animation` | enum | `none` | `none` \| `fade-up` \| `split-words`. Legacy-värden mappas tyst |
| `height` | string | `100svh` | CSS height-värde — se Decision D3 |
| `rubrik` | string | `""` | RichText, sanitiseras via `wp_kses_post` |
| `text` | string | `""` | RichText, sanitiseras via `wp_kses_post` |
| `button` | string | `""` | RichText, knapp-text |
| `backgroundMedia` | object\|null | `null` | `{ id, url, type: 'image'\|'video', mime, alt }` |
| `dimRatio` | number | `0` | 0–100, overlay-opacitet |
| `overlayColor` | string | `#000000` | Hex-färg för overlay |
| `contentPosition` | string | `"center center"` | Block alignment matrix-värde |
| `reverseFlow` | boolean | `false` | Byter ordning på rubrik/text |
| `scrollArrow` | boolean | `false` | Visar scroll-indikator (Sprint A: animeras + klickbar) |

---

## 3. Decision Log (Filosofisk kontext)

### D1 — Egen `goodblocks/hero` istället för `core/cover` + pattern

- **Beslut:** Eget block trots att `core/cover` täcker grundfunktionalitet
- **Datum:** Pre-2026 (importerat från agoodblocks vid migrering)
- **Kontext:** Animation-features (text-animation, scroll-trigger) som `core/cover` inte har
- **Alternativ förkastade:**
  - `core/cover` + tema-specifik animation-CSS — bryter brand-konsistens mellan kunder
  - `core/group` + pattern — saknar dedikerade animation-attribut i editor-UI
- **Konsekvenser:** Eget underhåll, dubblerar cover-funktionalitet, men ger redaktör-vänligt animation-UI
- **Varning vid omvändning:** Att migrera till `core/cover` skulle bryta animation-features och kräva DB-migration av alla befintliga `wp:goodblocks/hero`-block i kunders `post_content`

### D2 — PURGE av legacy animations och odokumenterade konventioner

- **Beslut:** Radera `standard`/`wild`/`from-right`/`from-left`-animationer + `||br||`-token + `*`-pulse-konvention
- **Datum:** 2026-04-29 (Sprint A Fas 0)
- **Kontext:** `dark-code-audit` identifierade trasig animation-motor (CSS-keyframes saknades för 4 av 5 animationer) + odokumenterade redaktörs-konventioner
- **Alternativ förkastade:**
  - **KEEP**: Acceptera dark code-skuld (motverkar Sprint A:s mål)
  - **FIX**: Lägg till saknade `@keyframes` + dokumentera konventionerna (mer arbete utan motiv — animationerna fungerade ändå inte synligt)
- **Konsekvenser:** Ren grund för Sprint A:s nya animationer; bounded regression (inga HTML-träffar på legacy-värden hittades på golfhallen + densiq)
- **Varning vid omvändning:** Att återinföra legacy-animationer kräver tre koordinerade ändringar (block.json-enum + CSS-keyframes + render-logik) — partiella återinföringar producerar samma trasiga tillstånd som tidigare

### D3 — Default `height: 100svh` (inte `100vh`)

- **Beslut:** `svh` (small viewport height) som default för hero-höjd
- **Datum:** Pre-2026
- **Kontext:** Mobile browser chrome (URL-fält, navigationsknappar) klipper `100vh`-content. `svh` använder den minsta stabila höjden — content klipps aldrig.
- **Alternativ förkastade:**
  - `100vh` — klipps på mobil när browser chrome är synligt
  - `auto` — kollapsar utan content
  - Explicit `px` — bryter responsivitet
- **Konsekvenser:** Hero fyller alltid synlig viewport utan att klippas. På desktop blir det aldrig större än synligt — säker default.
- **Varning vid omvändning:** Byte till `100vh` återinför mobile-clipping-buggen. Subtilt fel som inte syns i desktop-test.

### D4 — Graceful degradation för legacy data, ingen DB-migration

- **Beslut:** PURGE använder runtime-mappning av legacy-värden, inte DB-mutationer
- **Datum:** 2026-04-29
- **Kontext:** Undvika riskabel post_content-migration; SSH-DB-audit gav 0 träffar på legacy-värden i HTML på huvudsajter
- **Alternativ förkastade:**
  - Block deprecations API — komplext för dynamic blocks (server-render)
  - `wp db query`-migration — riskabel, kräver SSH-tillgång
- **Konsekvenser:** Befintliga hero-instanser fortsätter renderas tyst med `none`-animation. Datat helar sig själv när redaktörer sparar om sidor.
- **Varning vid omvändning:** Strikt validering av `animation`-värdet (t.ex. throw på okänt värde) skulle bryta alla befintliga block instantly. Behåll alltid fallback-grenen.

### D5 — agoodsite-fse-koppling: KNOWN UNKNOWN

- **Beslut:** Ingen formell dokumentation av tema-beroenden
- **Datum:** Captured 2026-04-29
- **Reasoning unknown** — ursprunglig författare/intent ej dokumenterad. Block importerat från `agoodblocks` per `CLAUDE.md` namespace-historik.
- **Treat as load-bearing:** TwentyTwentyFour-test i Sprint A FAS 2 (acceptanskriterium #5) ska avslöja om något bryter utan agoodsite-fse-tokens
- **Varning:** Antag inte att hero "bara fungerar" på godtyckligt tema utan verifiering. Om TT4-testet avslöjar dolda beroenden — dokumentera dem som D6+

### D6 — Hero saknas i `inc/showcase.php` (uppmärksammat 2026-04-29)

- **Beslut:** Hero har historiskt inte registrerats i agoodsite-fse style guide
- **Datum:** Captured 2026-04-29 — inte ett medvetet beslut, mer ett gap
- **Kontext:** `inc/showcase.php` registrerar 8 block men inte hero. Sannolikt glömt vid showcase-implementationen.
- **Konsekvenser:** Hero syns inte i style guide, redaktörer ser det bara via block-väljaren
- **Varning:** Vid Sprint A FAS 1 — överväg att lägga till hero i showcase. Inte i scope men billigt tillägg.

---

## Known Unknowns

Saker som inte är dokumenterade men borde vara, listade så framtida arbete kan upptäcka dem:

- **`../../shared/getPositionClassName`** — denna funktion är inte audited. Kan innehålla egna magic strings eller dark code.
- **agoodsite-fse-tema-tokens** — D5 ovan
- **Originalförfattare av animation-loop** — den som skrev `% 8`/`% 16`-magin är okänd; kunskapen försvann med personen
- **Avsedd UX för `*`-pulse-konventionen** — om någon kund instruerats att skriva `*ord*` i rubrik vet vi inte
