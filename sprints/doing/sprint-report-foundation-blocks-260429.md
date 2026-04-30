---
priority: 2
status: doing
start: 2026-04-29
tags: [sprint, feature]
---

# Sprint A: report-foundation-blocks

> Detta är **Sprint A** av en två-sprintssekvens. Sprint B (`sprint-report-story-card-260429.md` i backlog) hanterar `goodblocks/story-card` separat eftersom det är en arkitektoniskt tyngre fråga (InnerBlocks + disclosure + no-JS + a11y).

## Bakgrund

densiqgroup.com/sustainability-2025/ är byggd med rena core-block (cover, columns, heading) — fungerar idag men editorn är bräcklig och samma mönster upprepas i varje sektion. Vi vill kunna ersätta sidan med GoodBlocks-block och samtidigt bygga ett **återanvändbart bibliotek av long-form/storytelling-block** för framtida hållbarhetsrapporter, kundberättelser och liknande long-form-sidor på andra kunder.

Sprint A fokuserar på de tre tydligast avgränsade blocken — hero-animation, section-header, kpi-grid — som ger snabb nytta utan arkitektoniska oklarheter. Story-card ligger i Sprint B.

Blocken ska fungera oavsett tema. Showcase integreras med agoodsite-fse där det finns, men ingen tema-koppling i kärnan.

---

## Mål (Outcome)

- `goodblocks/hero` har text-animation (fade-up / split-words) + scroll-indikator
- Två nya block: `goodblocks/section-header`, `goodblocks/kpi-grid`
- **Tema-oberoende**: blocken fungerar på vilken WordPress-sajt som helst med GoodBlocks aktivt, kan stylesättas via tema/CSS-variabler
- Showcase-registrering integrerar med agoodsite-fse där filtret finns
- Sprint B (story-card) kan starta utan att blockas av Sprint A:s scope

### Arkitekturregler (gäller alla nya/utökade block)

- `block.json` med `apiVersion: 3`
- Server-render för dynamiska block där det behövs; om `save.js` används får det bara spara minimal wrapper / `InnerBlocks.Content`, inte stora HTML-attribut
- Innehållstunga delar använder **InnerBlocks / core-block** — inte stora HTML-payloads i attribut (gäller särskilt Sprint B)
- Fungerar utan ACF / SCF
- CSS scopas under `.wp-block-goodblocks-*`-wrappern
- **Neutrala defaults utan brand-opinion**: `font: inherit`, `font-weight: inherit/normal`, ingen hårdkodad `font-family`, ingen `letter-spacing`. Bara storlek (`clamp()`), spacing och färger. Temat äger typografin.
- **CSS-variabler har rimliga fallback-värden** (currentColor, system fonts om explicita) — tema-oberoendet får inte vara svagare än hur blocket ser ut på TwentyTwentyFour
- **Inga globala WP-taggar/facetter i blocken** — block kan rendera generiska labels/facets om de matas in via attribut, men ingen taxonomilogik
- **Mobile-first responsiv design**: alla layouts börjar i 1-kolumn mobil → bryter upp i N-kolumner desktop. Display-typografi använder `clamp()` för fluid skalning. Inga horisontella scroll-buggar på små skärmar.

---

## Scope

### Inkluderat

- **`goodblocks/hero`** — utöka befintligt block med:
  - `animation` attribut: `none` / `fade-up` / `split-words`
  - `showScrollIndicator` toggle, klickbar pil med smooth-scroll till nästa sektion
  - Pure CSS + IntersectionObserver, ingen GSAP
- **`goodblocks/section-header`** (nytt) — display-typografi-sektion:
  - Stor display-text + valfri kicker/nummer + valfri ingress
  - `alignment` (left/center) + `numberPosition` (before/after/none) som attribut
  - Alternerande färgteman via CSS-variabler (`--section-bg`, `--section-fg`)
  - **Inga hårdkodade typografival** — temat äger font-family/weight/letter-spacing
- **`goodblocks/kpi-grid`** (nytt) — 1–6 KPI-tiles i rad:
  - Per tile: stabilt `id` (för reorder utan repeater-jitter), `value` (string), `label` (string), valfritt `prefix`/`suffix`
  - Responsiv layout (1 kolumn mobil → N kolumner desktop)
- **Showcase-registrering** — lägg till två nya block (section-header, kpi-grid) i `inc/showcase.php` med rimliga `live`-konfigs
- **Migration-anteckning** — i sprint-filen: lista vilka core-block-mönster på densiq-sidan som ersätts av Sprint A:s block

### Exkluderat

- `goodblocks/story-card` — **Sprint B**
- Navigation / header (hör hemma i temat)
- Faktisk sajt-byggnation av densiqgroup.com/sustainability-2025 (separat task efter sprintsekvensen)
- GSAP-baserade animationer (pure CSS + IntersectionObserver räcker)
- PDF-export av rapporten
- Översättning EN→SV
- ACF-beroenden
- Stora HTML-payloads i block-attribut
- Tema-specifika hårdkodningar (inkl. font-family, letter-spacing)
- ASR-specifik navigation, sök-overlay, filterlogik och kapitelstruktur — **hanteras av `densiq-asr-2025` tills vidare**
- Global tagg-/taxonomilogik

---

## Beslut (lockade innan Fas 1)

- **Section-header — neutrala defaults, ingen brand-opinion**: Tillåtna defaults är `font: inherit`, `font-weight: inherit/normal`, `line-height`, `font-size: clamp(...)`. Inget hårdkodat font-family, ingen letter-spacing. Temat eller per-sajt-CSS äger brand-typografin.
- **KPI-grid** stöder 1–6 tiles, varje tile har stabilt `id`-attribut för reorder
- **Section-header** har `alignment` och `numberPosition` som attribut — inga hårdkodade "nummer efter titel"
- **Animation** i hero görs med pure CSS + IntersectionObserver, inte GSAP
- **Tema-koppling**: GoodBlocks förblir tema-agnostiskt; agoodsite-fse-integration är opt-in via filter
- **Story-card flyttas till Sprint B** — den är arkitektoniskt tyngsta biten och förtjänar dedikerad uppmärksamhet (InnerBlocks + disclosure + no-JS + a11y)
- **Hero har `buttonUrl` + `buttonTarget`-attribut** (tillagt 2026-04-29 efter input från Codex). Knapp med URL → `<a>`; utan URL → `<button>`. Default-rendering måste vara semantiskt korrekt
- **200-tecken-regeln reformulerad** (efter input från Codex): "inga långa body-textfält i attribut", korta intro-fält (≤ 300 tecken riktmärke) är OK; body-content i InnerBlocks (Sprint B-mönster)

### Strategiskt beslut: parallell GoodBlocks-version, inte ersättning av live densiq-sida (2026-04-29)

Tre Claude-instanser (densiq-claude, codex, denna) konvergerar på följande plan efter att densiq-claude flaggat scope-/timing-risker:

| Fas | Vad | Var | När |
|---|---|---|---|
| 0 | Säkra textjusteringar via Plan B / REST | densiq:s live-sida | Pågår — densiq-claude:s arbete |
| 1 | Sprint A v1.12.0 (denna sprint) | GoodBlocks-repo | Pågår, oberoende av densiq:s timing |
| 2 | Bygg `densiq.com/sustainability-2025-goodblocks/` (draft/private/noindex) | densiq-asr-repo | Efter v1.12.0 ute |
| 3 | Konvertera 2–3 representativa sektioner med GoodBlocks som proof-of-concept | densiq | Validera arkitekturen empiriskt |
| 4 | Beslut: migrera resten eller justera spec | — | Baserat på proof-of-concept |
| 5 | Sprint B + ev. Sprint C/D för saknade block (story-module, bar-chart, before-after, countdown, KPI-strip i story) | GoodBlocks-repo | Endast om steg 4 säger ja |

**Sprint A motiveras av två oberoende mål:**
1. Foundation library för andra kunder med long-form-rapporter (gäller oavsett densiq:s timing)
2. Möjliggör densiq-migration när/om steg 3 validerar arkitekturen

**Live densiq-sidan ersätts INTE av Sprint A:s leverans.** DNS-flip från staging görs först när proof-of-concept har visat att rebuild ger faktisk vinst.

---

## Återanvändning

- **densiq-asr-2025** — befintlig ASR-navigation, sök-overlay, filter och kapitelstruktur återanvänds som de är från det repot. GoodBlocks-sprintsekvensen rör inte den koden
- **`goodblocks/hero`** — vi utökar, inte bygger nytt. Animation kan återanvända IntersectionObserver-mönster om det finns på något annat ställe (kolla `view.js`-filer i andra block)
- **CSS-variabler** — följ befintligt mönster från `testimonials/style.scss` (CSS-variabler i wrappern, currentColor som default)
- **block.json-struktur** — kopiera mönstret från `testimonials/block.json` (apiVersion 3, render: file, viewScript)

---

## Migration-anteckning (densiq-sidan → GoodBlocks-block)

| densiq-mönster | Ersätts av (Sprint A) |
|---|---|
| `core/cover` med video + display-text | `goodblocks/hero` med animation + scroll-indicator |
| `core/heading` (display) + `core/columns` med kicker/nummer | `goodblocks/section-header` |
| `core/columns` med 3 stat-tiles (siffra + label) | `goodblocks/kpi-grid` |
| `core/columns` med rubrik + text + valfri "Read more"-länk | `goodblocks/story-card` (**Sprint B**) |
| `core/quote` (pull quotes) | Behåll `core/quote` — inget GoodBlocks-block behövs |
| `core/social-links` | Behåll `core/social-links` |
| ASR-specifik navigation, sök-overlay, filterlogik, kapitelstruktur | Hanteras av `densiq-asr-2025` — utanför GoodBlocks scope |

---

## Handoff

> Kopiera och klistra in som första meddelande när du byter Claude-instans.

```
Vi har en aktiv sprint i detta repo.

Läs först:
1. `sprints/doing/sprint-report-foundation-blocks-260429.md` — sprintplanen (Sprint A)
2. `CLAUDE.md` — konventioner för repot
3. `sprints/backlog/sprint-report-story-card-260429.md` — Sprint B (informativt; rör inte koden)

Starta med Fas 0. Regler:
- Ingen kod ändras utan godkänd spec
- Markera tasks klara löpande (- [ ] → - [x])
- Kör comprehension gate innan merge av AI-genererad kod
- Inga stora HTML-payloads i block-attribut
- Inga hårdkodade font-family eller letter-spacing i defaults

Är du redo?
```

---

## FAS 0 — FÖRSTÅ & SPECIFICERA

> Ingen implementationskod skrivs förrän Fas 0 är klar.

### 0A. Förståelseinfrastruktur

- [x] `/dark-code-audit` på `src/blocks/hero/` — HIGH-risk hotspots identifierade, PURGE-väg vald (sub-spec) <!-- brian:id=tsk_8bebc9b6 gh:https://github.com/AGoodId/goodblocks/issues/28 -->
- [x] `/context-layer` på `src/blocks/hero/` — `src/blocks/hero/CONTEXT.md` skapad med 6 decisions + known unknowns <!-- brian:id=tsk_94b5c410 gh:https://github.com/AGoodId/goodblocks/issues/29 -->
- [x] `/comprehension-gate` på `src/blocks/hero/` — REVIEW REQUIRED, 3 frågor besvarade (Q1 stängd via shared-audit, Q3 verifieras i FAS 2, Q6 → release notes) <!-- brian:id=tsk_3dfd9c5e gh:https://github.com/AGoodId/goodblocks/issues/30 -->

### 0B. Återanvändningskoll

- [x] Genomsök `src/blocks/*/view.js` efter IntersectionObserver + toggle-state — image-compare har cleanest IO-mönster med fallback, testimonials har data-state-pattern <!-- brian:id=tsk_1c4e4cb3 gh:https://github.com/AGoodId/goodblocks/issues/31 -->
- [x] Beslut: hero=utökas (PURGE+lägg till), section-header/kpi-grid=nya, showcase.php=utökas. IO-mönster återanvänds från image-compare; getPositionClassName från src/shared.js är säker <!-- brian:id=tsk_21659629 gh:https://github.com/AGoodId/goodblocks/issues/32 -->

### 0C. Spec

- [x] **Problemspec:** Mappning från densiq-mönster till Sprint A-block dokumenterad i `sprints/reference/report-foundation-blocks-spec-260429.md` (sektion 4. Visuella exempel) <!-- brian:id=tsk_288c423d gh:https://github.com/AGoodId/goodblocks/issues/33 -->
- [x] **Lösningsspec:** Komplett spec i `sprints/reference/report-foundation-blocks-spec-260429.md` — block.json-attribut, HTML-struktur, CSS-variabler, defaults för alla tre block. Uppdaterad efter input från densiq-claude + codex (buttonUrl, reformulerad textfält-regel) <!-- brian:id=tsk_d7417fc6 gh:https://github.com/AGoodId/goodblocks/issues/34 -->
- [x] **Antaganden:** Dokumenterade i `sprints/reference/sprint-a-test-plan-260429.md` (WP 6.4+, PHP 8.0+, modern browsers, IntersectionObserver med fallback, inga GSAP/ACF, tema-test på TT4) <!-- brian:id=tsk_4f5ee9c2 gh:https://github.com/AGoodId/goodblocks/issues/35 -->
- [x] **Acceptanskriterier:** 13 AC listade nedan — verifieras av tester i `sprints/reference/sprint-a-test-plan-260429.md` <!-- brian:id=tsk_c850dafa gh:https://github.com/AGoodId/goodblocks/issues/36 -->

  **Förslag på acceptanskriterier:**
  1. `goodblocks/hero` (utökad) + två nya block (`section-header`, `kpi-grid`) syns/fungerar under kategorin "goodblocks"
  2. `goodblocks/hero` har animation-attribut + scroll-indikator-toggle som fungerar i frontend
  3. `goodblocks/section-header` renderar display-text + valfri kicker/nummer med korrekt alignment och numberPosition
  4. `goodblocks/kpi-grid` stöder 1–6 tiles, responsiv layout, attribut för value/label/prefix/suffix per tile
  5. Inget block kräver agoodsite-fse-temat för att fungera (testat på TwentyTwentyFour)
  6. Inga block-attribut innehåller HTML-strängar > 200 tecken
  7. `npm run lint` passerar utan nya errors
  8. `npm run build` lyckas utan errors
  9. Showcase-registrering lägger till båda nya block i `inc/showcase.php`
  10. **Alla block är fullt responsiva**: testas i 320 / 768 / 1024 / 1440px viewports utan horisontell scroll, utan trasig layout, med läsbar typografi
  11. **Touch-vänligt**: klickbara element (scroll-pil, KPI-länkar om de finns) är minst 44×44px på mobil
  12. **Defaults utan tema**: alla block ser rimliga ut på TwentyTwentyFour utan extra CSS-overrides (verifieras manuellt)
  13. **Inga brand-opinions i defaults**: section-header har varken hårdkodat font-family eller letter-spacing — verifieras genom kodgranskning av `style.scss`

### 0D. Tester definieras från spec

- [x] **Enhet:** Konkreta testfall definierade i `sprints/reference/sprint-a-test-plan-260429.md` (Hero: 18 fall, Section-header: 11 fall, KPI-grid: 10 fall) <!-- brian:id=tsk_db89236a gh:https://github.com/AGoodId/goodblocks/issues/37 -->
- [x] **Integration:** Editor → save → render.php → frontend-DOM → view.js-flöde dokumenterade per block i test-plan (3 hero-scenarier, 2 section-header, 2 kpi-grid) <!-- brian:id=tsk_f0286f4e gh:https://github.com/AGoodId/goodblocks/issues/38 -->
- [x] **E2E / manuellt:** Testsida-byggnation + 4 viewports + no-JS-test + a11y-test specificerat i test-plan <!-- brian:id=tsk_5393a6c2 gh:https://github.com/AGoodId/goodblocks/issues/39 -->
- [x] **Regression:** Lista över befintliga block + build/lint-kommandon i test-plan <!-- brian:id=tsk_958e050f gh:https://github.com/AGoodId/goodblocks/issues/40 -->

### 0E. Pre-mortem

- [x] Kör pre-mortem — risker identifierade: <!-- brian:id=tsk_ee35b21f gh:https://github.com/AGoodId/goodblocks/issues/41 -->
  1. **Story-card-komplexitet** (InnerBlocks + disclosure + no-JS + a11y) blandas i samma sprint som tre välavgränsade block → kvalitet på tester och responsivitet offras
  2. **Tema-oberoende blir illusoriskt** — section-header utan typografi-defaults blir anemiskt på TT4; med defaults blir det DENSIQ-specifikt
  3. **Sprint-scope för stort** — 4 block × 5 filer + showcase + responsivitet i 4 viewports + comprehension gate × 4 = realistiskt 2–3 sprints arbete
- [x] Beslut: scope/spec justerad enligt pre-mortem: <!-- brian:id=tsk_bfac9384 gh:https://github.com/AGoodId/goodblocks/issues/42 -->
  - **Story-card flyttad till Sprint B** (eliminerar risk #1, halverar surface area)
  - **Section-header — neutrala defaults locked** (eliminerar risk #2; temat äger brand)
  - **Sprint A liten nog att leverera med kvalitet** (mitigerar risk #3)

---

## FAS 1 — IMPLEMENTATION

> Ge Claude spec + tester, inte problemet.
> Markera AI-genererade tasks med 🤖.

- [x] 🤖 **(1a) PURGE av hero** — block.json: tog bort `backgroundType`+`positionClass`, ändrat default `"ingen"`→`"none"`. edit.js: tog bort `backgroundColor`-destrukturering, `imageType`-mellansteg, ersatte 5-värdes-SelectControl med 1 `none`, tog bort `getPositionClassName` från setAttributes, bytte `<a className="btn">` → `<button type="button">`. render.php: tog bort 28-radig title-loop med magic numbers + `||br||` + `*`-pulse, lade till legacy-mappning, deriverar position direkt, scroll-arrow blev `<button type="button">` med `aria-label`. style.scss: tog bort `.from-right/.from-left/.inline-block`, gav `.hero-block__scroll-arrow` button-styling med 44×44 touch-target. editor.scss: tog bort `height: 50vh` + `font-size: 80px`-overrides. Build ✅, lint ✅. **Comprehension-gate krävs innan 1b startar.** <!-- brian:id=tsk_5ba1e921 gh:https://github.com/AGoodId/goodblocks/issues/43 -->
- [x] 🤖 **(1b) Lägg till nya hero-animationer** — edit.js: SelectControl utökad med `fade-up` + `split-words`. block.json: `viewScript: file:./view.js`. view.js (nytt): IntersectionObserver-trigger för `.is-in-view`, split-words via `hero-block__word`-spans med `--word-index`-stagger, scroll-arrow click → `scrollIntoView({behavior: 'smooth'})`, prefers-reduced-motion respekterat (skippar splittning + använder `behavior: 'auto'`). style.scss: 3 CSS-variabler (`--hero-fade-distance`, `--hero-fade-duration`, `--hero-split-stagger`), keyframes för fade-up + split-words gated på `.is-in-view`, `@media (prefers-reduced-motion: reduce)`-fallback. webpack: ny entry `blocks/hero/view`. Build ✅ (1.14 KiB minified), lint ✅ <!-- brian:id=tsk_23c9c9c5 gh:https://github.com/AGoodId/goodblocks/issues/58 -->

- [x] 🤖 Skapa `src/blocks/section-header/` — block.json (apiVersion 3, 6 attribut med enums), render.php (60 rader, kicker before/after/none, alignment left/center, theme-klass), edit.js (RichText för kicker+title+lead, InspectorControls för alignment/numberPosition/theme), style.scss med 10 CSS-variabler, light/dark/accent-teman med tema-token-fallbacks. **Inga `font-family` eller `letter-spacing`.** Build ✅, lint ✅ <!-- brian:id=tsk_c8431650 gh:https://github.com/AGoodId/goodblocks/issues/44 -->
- [x] 🤖 Skapa `src/blocks/kpi-grid/` — block.json med items-array + columns + theme (alla med enums), render.php (defensiv item-validering, cap 6, columns_resolved, tomma tiles filtreras bort), edit.js (full repeater med add/remove/move-up/move-down, max 6 tiles, stabilt id auto-genererat, live-preview matchar frontend), style.scss (responsiv grid 1→2→N kolumner på 640/960px, 3 teman med fallback-tokens, **inga font-family/letter-spacing**). Build ✅, lint ✅ <!-- brian:id=tsk_504dc3f2 gh:https://github.com/AGoodId/goodblocks/issues/45 -->
- [x] 🤖 Uppdatera `inc/showcase.php` — registrerade `goodblocks/hero` (live=false), `goodblocks/section-header` (live=true, 3 configs: light/dark/accent) och `goodblocks/kpi-grid` (live=true, 2 configs: 3-tile prefix/suffix-mix + 6-tile accent). Stänger även D6 i CONTEXT.md (hero-gap i showcase) <!-- brian:id=tsk_e060ab15 gh:https://github.com/AGoodId/goodblocks/issues/47 -->
- [x] 🤖 Uppdatera `webpack.config.js` + `goodblocks.php` — entries och slug-array verifierade synka för alla tre nya block (hero/view + section-header + kpi-grid) <!-- brian:id=tsk_763b4c20 gh:https://github.com/AGoodId/goodblocks/issues/48 -->

---

## FAS 2 — KÖRNING, VERIFIERING & EVAL

> Fas 2 kör testerna som definierades i Fas 0 — den definierar dem inte.

### 2A. Kör testerna

- [ ] Enhetstester gröna — animation-trigger, KPI-rendering, section-header alignment/numberPosition <!-- brian:id=tsk_408c60cf gh:https://github.com/AGoodId/goodblocks/issues/49 -->
- [ ] Integrationstester gröna — editor → render.php → view.js-flöde för alla tre block <!-- brian:id=tsk_894fcb26 gh:https://github.com/AGoodId/goodblocks/issues/50 -->
- [ ] E2E-scenario verifierat: testsida med tre block i sekvens, animationer triggar, responsivt i 4 viewports, både på agoodsite-fse och TT4 <!-- brian:id=tsk_d575648b gh:https://github.com/AGoodId/goodblocks/issues/51 -->
- [ ] Regressionstester gröna — befintliga block fungerar, `npm run build`/`lint` utan nya fel <!-- brian:id=tsk_2a28db9e gh:https://github.com/AGoodId/goodblocks/issues/52 -->

### 2B. Comprehension Gate

- [ ] `/comprehension-gate` på `src/blocks/{section-header,kpi-grid,hero}/` — verdict: CLEAR / REVIEW / HOLD <!-- brian:id=tsk_2d00c99e gh:https://github.com/AGoodId/goodblocks/issues/53 -->

### 2C. Eval — Uppfylldes specen?

- [ ] Alla 13 acceptanskriterier uppfyllda (gå igenom 0C punkt för punkt) <!-- brian:id=tsk_5312ed84 gh:https://github.com/AGoodId/goodblocks/issues/55 -->
- [ ] Inga ospecificerade AI-tillägg utanför scope (inga GSAP-paket, ingen ACF, inga stora HTML-attribut, inga brand-opinions i defaults) <!-- brian:id=tsk_b2de3e0c gh:https://github.com/AGoodId/goodblocks/issues/56 -->
- [ ] Antaganden fortfarande giltiga? Tema-oberoendet verifierat på minst ett annat tema än agoodsite-fse? <!-- brian:id=tsk_33b92f7f gh:https://github.com/AGoodId/goodblocks/issues/57 -->

---

## Definition of Done

- [ ] Fas 0 komplett — spec och tester definierade innan implementation
- [ ] Alla Fas 1-tasks klara
- [ ] Tester gröna (2A)
- [ ] Comprehension gate: CLEAR (2B)
- [ ] Alla 13 acceptanskriterier uppfyllda (2C)
- [ ] `npm run lint` — 0 errors
- [ ] `npm run build` — successful
- [ ] Inget nytt block-attribut innehåller HTML-strängar > 200 tecken
- [ ] Tema-oberoendet verifierat (testat på TwentyTwentyFour)
- [ ] Responsivitet verifierad i 320 / 768 / 1024 / 1440px viewports
- [ ] Touch-targets ≥ 44×44px på mobil för alla interaktiva element
- [ ] Section-header har varken hårdkodat font-family eller letter-spacing (kodgranskat)
- [ ] Sprint B kan starta — Sprint A ger inga blockerande beroenden

---

## Lärdomar

[Fylls i när sprints stängs]
