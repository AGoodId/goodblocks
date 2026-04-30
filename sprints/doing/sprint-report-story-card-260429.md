---
priority: 2
status: doing
start: 2026-04-30
tags: [sprint, feature]
---

# Sprint B: report-story-card

> Detta är **Sprint B** av en två-sprintssekvens. Sprint A (`sprint-report-foundation-blocks-260429.md`) levererade hero-animation, section-header och kpi-grid i v1.12.0. Sprint B fokuserar uteslutande på `goodblocks/story-card` eftersom det är den arkitektoniskt tyngsta biten (InnerBlocks + disclosure + no-JS + a11y + editor UX + 5 layouts).
>
> **Aktiverad 2026-04-30** efter Codex:s empiriska test mot densiqgroup.com/sustainability-2025/: Sprint A-blocken funkar för KPI/foundation men story-delen är blockerande för rent blockbaserad rebuild. Codex bekräftade kraven — speciellt 5 layouts (`default`, `default-reverse`, `split-left`, `split-right`, `bg-full`) och stable `anchorId`.

## Bakgrund

`goodblocks/story-card` är blocket som ersätter densiq-sidans story-mönster: rubrik + ingress + media + valfri action-button + expanderbart innehåll ("Read more"). Det är också det block som arkitektoniskt skiljer sig mest från andra GoodBlocks-block: dess innehåll måste vara **InnerBlocks** (inte HTML-attribut) och dess expand-mekanik måste fungera utan JS.

Story-card är inte "ett block till" utan ett arkitekturbeslut: gör vi rätt här bygger vi bort den typ av skörhet som ASR-sidan har idag (stora HTML-payloads, ACF-beroenden, JS-only disclosure). Gör vi fel återintroducerar vi samma problem.

---

## Mål (Outcome)

- `goodblocks/story-card` finns och fungerar i Gutenberg-editorn
- Expanderbart innehåll lagras som **InnerBlocks** — inga stora HTML-attribut
- Disclosure (Read more / Read less) använder native `<details>`/`<summary>` med smooth animation som progressiv enhancement
- No-JS-användare ser fortfarande allt innehåll (kan öppna/stänga via `<details>`)
- Action-button är generisk (URL + label) — inte rapport-specifik
- Tema-oberoende, ingen brand-opinion i defaults
- Showcase-registrerad i `inc/showcase.php`

### Arkitekturregler

Samma som Sprint A, plus:

- **Disclosure: native `<details>`/`<summary>`** — inte JS-toggleat `display: none`
- **Smooth animation av expand som progressive enhancement** — om `interpolate-size: allow-keywords` finns används det, annars instant open/close. Accessibility och no-JS-fallback prioriteras över perfekt animation.
- **InnerBlocks med template + allowedBlocks** — redaktör kan lägga till core-block (heading, paragraph, image, list) i det expanderade innehållet, men inte godtyckliga block

---

## Scope

### Inkluderat

- **`goodblocks/story-card`** (nytt):
  - Attribut:
    - `anchorId` (string, auto-genererad om tom) — för deep-links från search palette + chapter anchors
    - `kicker`, `title`, `excerpt` (RichText)
    - `mediaId`, `mediaUrl`, `mediaType` (image/video), `mediaAlt`
    - `actionUrl`, `actionLabel`, `actionTarget` (_self/_blank) — generisk action-button
    - `labels` (string-array, generiska facets — story-card äger ingen filter-state)
    - `layout` enum: `default` | `default-reverse` | `split-left` | `split-right` | `bg-full`
    - `theme` enum: `light` | `dark` | `accent`
  - **Expanderbart innehåll via InnerBlocks** med template + allowedBlocks (heading, paragraph, list, image, kpi-grid)
  - Disclosure: `<details>` + `<summary>`-mönster
  - Smooth animation som progressive enhancement (`interpolate-size`)
  - Inga hårdkodade font-family eller letter-spacing
- **5 layouts:**
  - `default` — text vänster, valfri media höger
  - `default-reverse` — media vänster, text höger (CSS-flip)
  - `split-left` — fullbleed media vänster (object-fit: cover, ingen padding-runt)
  - `split-right` — fullbleed media höger
  - `bg-full` — text + valfri visual ovanpå fullbleed bakgrund (image eller video)
- **Showcase-registrering** — lägg till story-card i `inc/showcase.php` med flera layout-config-exempel
- **Webpack/registrering** — uppdatera `webpack.config.js` + `goodblocks.php`
- **a11y-verifiering** — keyboard navigation, screen reader, focus management

### Exkluderat

- **Visualer som separata block** (`stat-counter`, `bar-chart`, `before-after`, `countdown-extended`) — Sprint C/D
- `goodblocks/kpi-grid` används redan inuti story-card via InnerBlocks (Sprint A-leverans)
- ASR-specifika story-mönster (filter/facetter, kategorisystem som global state) — hanteras av `densiq-asr-2025`
- CPT/post_tag-modellering — om kunder vill bryta ut stories till en CPT är det deras tema/plugin
- PDF-export
- Animation av media (parallax, scroll-fade) — eventuellt egen sprint senare
- Anpassad share-funktion utöver generisk action-button (kunder kan implementera Web Share API i tema-JS)
- Brand-typografi (DENSIQ Roboto, letter-spacing etc.) — temat eller per-sajt-CSS äger

---

## Beslut (lockade innan Fas 1)

- **Disclosure-mekanism**: `<details>`/`<summary>` — **inte** JS-toggleat `display: none`. Native semantics, keyboard, no-JS, screen reader stödjs out-of-the-box.
- **Smooth animation**: Progressive enhancement via `interpolate-size: allow-keywords` (Chrome/Edge 129+). Andra browsers får instant open/close. Detta är medvetet — vi prioriterar a11y över animation.
- **Inner content via InnerBlocks** — template + allowedBlocks, inte HTML-attribut
- **Action-button är generisk** — URL + label + target, inte rapport-specifik share
- **Defaults utan brand-opinion** — samma regel som Sprint A: inget hårdkodat font-family/letter-spacing
- **5 layouts från start (Codex-input 2026-04-30):** alla 5 layouts byggs i Sprint B; Codex testade Sprint A på densiq och bekräftade att `default` ensamt inte räcker för rebuild. Risk: ~2x scope, men varje layout är primärt CSS-variation (samma underliggande arkitektur).
- **`anchorId`-attribut (Codex-input 2026-04-30):** stable id för deep-links och chapter-anchors. Auto-genereras i edit.js om tomt; redaktör kan override:a manuellt. Workaround för Gutenberg-bug där `block.id` strippas vid spara (känd från densiq-asr-2025).
- **Visualerna är Sprint C/D, inte Sprint B:** `stat-counter`, `bar-chart`, `before-after`, `countdown-extended` är separata block. Story-card använder InnerBlocks så dessa kan nestas in när de finns.
- **Brand-CSS är kundens ansvar:** GoodBlocks levererar neutral baseline; densiq-asr-2025-temat (eller motsvarande hos andra kunder) lägger till brand-overrides på `.story-card__title` etc. Detta är vad Sprint A:s feedback från Codex bekräftade.

### Locked decisions efter dark-code-audit av card-feature (2026-04-30)

- **Anchor-strategi:** Använd `supports.anchor: true` (Gutenberg-konvention) — **skippa custom `anchorId`**. Story-card render.php respekterar blockets `id`-attribut korrekt så navigation/deeplinks fungerar. Tar bort dubbla-id-risken som card-feature-pattern annars skulle introducera.
- **Animation:** Inga `data-animate`-attribut. Enda animation i scope är `<details>` smooth disclosure via `interpolate-size` progressive enhancement. Scroll/fade/media-animationer är out-of-scope (Sprint C+ om det behövs).
- **InnerBlocks-template:** Inga tema-presets (`fontSize`, `textColor` etc.) i template. Bara block-types och eventuellt placeholder-text. Visuell styling ligger i blockets CSS-variabler eller sajtens tema.
- **Länk/disclosure:** Story-card wrappar **aldrig** allt i `<a>`. Action-link/share är separata kontroller. Disclosure är alltid native `<details>`/`<summary>`.
- **`_shared`-import:** Hoppa över `../_shared/`-imports i Sprint B tills modulen är separat auditerad. Story-card får egna scoped CSS-variabler (matchar testimonials/section-header/kpi-grid-pattern från Sprint A).

---

## Återanvändning

- **Sprint A:s leveranser** — story-card ska kunna ligga sida vid sida med section-header och kpi-grid utan layout-konflikter
- **InnerBlocks-mönster** — `card-feature/index.js` har redan ett InnerBlocks-mönster med template + allowedBlocks; återanvänd det
- **block.json-struktur** — kopiera mönstret från `testimonials/block.json` (apiVersion 3, render: file, viewScript)
- **CSS-variabler-mönster** — följ `testimonials/style.scss`

---

## Handoff

> Kopiera och klistra in som första meddelande när du byter Claude-instans.

```
Vi har en aktiv sprint i detta repo.

Läs först:
1. `sprints/doing/sprint-report-story-card-260429.md` — sprintplanen (Sprint B)
2. `CLAUDE.md` — konventioner för repot
3. `sprints/done/sprint-report-foundation-blocks-260429.md` — Sprint A som föregår denna

Starta med Fas 0. Regler:
- Ingen kod ändras utan godkänd spec
- Markera tasks klara löpande (- [ ] → - [x])
- Kör comprehension gate innan merge av AI-genererad kod
- Disclosure ska använda <details>/<summary> — INTE JS-toggleat display:none
- Innehåll måste vara tillgängligt utan JS
- Inga stora HTML-payloads i block-attribut

Är du redo?
```

---

## FAS 0 — FÖRSTÅ & SPECIFICERA

> Ingen implementationskod skrivs förrän Fas 0 är klar.

### 0A. Förståelseinfrastruktur

- [x] `/dark-code-audit` på `src/blocks/card-feature/` — MEDIUM risk, 8 hotspots identifierade. 5 lock-beslut till 0C: anchor-strategi (`supports.anchor`), ingen `data-animate`, inga tema-presets i template, ingen `<a>`-wrapper, ingen `_shared/`-import. Verdict: SAFE som referens med explicita avsteg. <!-- brian:id=tsk_58d63111 gh:https://github.com/AGoodId/goodblocks/issues/59 -->
- [x] `/context-layer` på Sprint A:s nya block — patterns-cheatsheet sammanställt i `sprints/reference/sprint-a-patterns-for-story-card-260430.md` (8 sektioner: block.json, render.php, CSS, editor-controls, frontend, build/registrering, vad story-card matchar vs. unikt, vad som ska undvikas). <!-- brian:id=tsk_5fba2ffb gh:https://github.com/AGoodId/goodblocks/issues/60 -->

### 0B. Återanvändningskoll

- [x] Genomsök `src/blocks/*/edit.js` efter befintliga InnerBlocks-templates med allowedBlocks — card-feature (klassisk InnerBlocks), slider+media-grid (useInnerBlocksProps); inget block kombinerar idag `render: file:./render.php` med InnerBlocks → story-card pionjärar mönstret <!-- brian:id=tsk_013d0e6a gh:https://github.com/AGoodId/goodblocks/issues/61 -->
- [x] Genomsök efter `<details>`-mönster — **inga träffar** i src/, inc/ eller goodblocks.php; story-card är första block som använder native disclosure <!-- brian:id=tsk_988f4eb5 gh:https://github.com/AGoodId/goodblocks/issues/62 -->
- [x] Beslut om återanvändning — full reuse-matris i `sprints/reference/sprint-a-patterns-for-story-card-260430.md`. Sammanfattning: InnerBlocks-mönster (allowedBlocks/template/templateLock) återanvänds från card-feature; block.json/render.php/CSS-disciplin från Sprint A; `<details>`/`<summary>` byggs nytt; ingen view.js i MVP <!-- brian:id=tsk_8def375a gh:https://github.com/AGoodId/goodblocks/issues/63 -->

### 0C. Spec

- [x] **Problemspec:** Densiq:s story-mönster (densiq-asr-2025/blocks/story-module/render.php, 538 rader) ersätts av story-card med 5 layouts. Detaljerad mapping i `sprints/reference/densiq-story-module-analysis-260429.md`. Visualer (stat-counter/bar-chart/before-after/countdown) är out-of-scope (Sprint C/D). <!-- brian:id=tsk_734d077f gh:https://github.com/AGoodId/goodblocks/issues/64 -->
- [x] **Lösningsspec:** Komplett kontrakt i `sprints/reference/story-card-spec-260430.md` — 15 attribut, InnerBlocks-contract, DOM-struktur, anchor-strategi, 5-layout-CSS, ingen view.js MVP <!-- brian:id=tsk_f2917074 gh:https://github.com/AGoodId/goodblocks/issues/65 -->
- [x] **`<details>`-arkitektur:** Native `<details>`/`<summary>` med `summaryLabel`-attribut (default "Read more"). Summary är inte InnerBlocks (fast text via attribut). Body innehåller `<?php echo $content; ?>` med InnerBlocks. Smooth-expand via `interpolate-size` som progressive enhancement, ingen view.js. <!-- brian:id=tsk_5b49635f gh:https://github.com/AGoodId/goodblocks/issues/66 -->
- [x] **Antaganden:** WP 6.4+, ingen IntersectionObserver behövs (ingen scroll-animation), `interpolate-size: allow-keywords` som progressive enhancement (Chrome/Edge 129+; instant fallback i andra), inga GSAP, inga ACF, inga `_shared/`-imports, ingen view.js i MVP. <!-- brian:id=tsk_9fe5908c gh:https://github.com/AGoodId/goodblocks/issues/67 -->
- [x] **Acceptanskriterier:** 18 AC i `sprints/reference/story-card-spec-260430.md` sektion 7 — täcker InnerBlocks-persistens, no-JS-disclosure, keyboard, screen reader, anchor, kpi-grid-nesting, alla 5 layouts, tema-oberoende på TT4, etc. <!-- brian:id=tsk_606bae89 gh:https://github.com/AGoodId/goodblocks/issues/68 -->

  **Förslag på acceptanskriterier:**
  1. `goodblocks/story-card` syns och fungerar under kategorin "goodblocks"
  2. Editorn kan: skapa story-card, fylla i alla attribut, lägga inner blocks i expanderade innehållet
  3. Disclosure fungerar med `<details>`/`<summary>` — innehåll synligt vid open, dolt vid closed
  4. **No-JS:** med JS avstängt kan användare öppna/stänga och se allt innehåll
  5. **a11y:** keyboard navigation fungerar (Tab till summary, Space/Enter för toggle, Tab in i innehållet)
  6. **a11y:** screen reader-användare hör korrekt state ("expanded"/"collapsed") via native `<details>`-semantik
  7. Smooth animation fungerar i browsers med `interpolate-size`-stöd; instant fallback i andra
  8. Action-button (URL + label) är valfri och renderas bara när satt
  9. Inga block-attribut innehåller HTML-strängar > 200 tecken (allt långt innehåll i InnerBlocks)
  10. Inga hårdkodade font-family eller letter-spacing i defaults
  11. Responsiv i 320 / 768 / 1024 / 1440px viewports
  12. Touch-targets ≥ 44×44px för summary och action-button på mobil
  13. `npm run lint` + `npm run build` passerar
  14. Showcase-registrering: story-card finns i `inc/showcase.php`
  15. Tema-oberoende verifierat på TwentyTwentyFour

### 0D. Tester definieras från spec

- [x] **Enhet/static:** Static checks definierade i `sprints/reference/story-card-test-plan-260430.md` sektion 1 — block.json (5 enums + 15 attribut), save.js (`<InnerBlocks.Content />`), edit.js (allowedBlocks/template/templateLock), style.scss (no font-family, no _shared) <!-- brian:id=tsk_8b5a7272 gh:https://github.com/AGoodId/goodblocks/issues/69 -->
- [x] **Integration:** PHP/render-checks (sektion 2) + editor-integration (sektion 3) — XSS-test, enum-fallbacks, disclosure-villkor, openByDefault, kpi-grid som inner block, anchor-fält i Inspector <!-- brian:id=tsk_2ac62557 gh:https://github.com/AGoodId/goodblocks/issues/70 -->
- [x] **E2E / manuellt:** Frontend-test (sektion 4) — alla 5 layouts × 4 viewports, anchor-deeplink, no-JS-test, smooth-expand i Chrome 129+, openByDefault <!-- brian:id=tsk_55a18c99 gh:https://github.com/AGoodId/goodblocks/issues/71 -->
- [x] **a11y / manuellt:** A11y-test (sektion 5) — keyboard nav, VoiceOver/NVDA, ARIA-tree-validering, action-link separation från disclosure, labels = list (inte buttons) <!-- brian:id=tsk_8d942c14 gh:https://github.com/AGoodId/goodblocks/issues/72 -->
- [x] **Regression:** Sektion 6 — Sprint A-block fungerar, andra GoodBlocks (testimonials/search-autocomplete/event-list/masonry-query), build+lint clean, showcase-registrering, InnerBlocks-persistens efter plugin-toggle <!-- brian:id=tsk_00c9d724 gh:https://github.com/AGoodId/goodblocks/issues/73 -->

### 0E. Pre-mortem

- [x] Pre-mortem — 8 risker identifierade med konkret mitigation: <!-- brian:id=tsk_89ad7dd9 gh:https://github.com/AGoodId/goodblocks/issues/74 -->

  **R1 — Dynamic block + InnerBlocks är nytt pattern i kodbasen** (HIGH)
  - Risk: Story-card är första blocket som kombinerar `render: file:./render.php` med `<InnerBlocks.Content />` i save.js. Om mönstret byggs fel förlorar redaktörer inner-blocks-data vid spara, eller får "block invalid"-warningar.
  - Mitigation: Test 1.2 + 3.1 + 6.5 verifierar persistens. Bygga klart `save.js` FÖRST och testa i editor med en minimal story-card innan resten implementeras. Comprehension-gate efter `save.js` + `render.php`-grundstruktur innan layouter byggs ut.

  **R2 — `<details>` styling/browser variation** (MEDIUM)
  - Risk: Native `<summary>` har olika default-styling per browser (marker/triangle på vänster sida, olika typografi). Vår styling kanske inte täcker alla edge-cases.
  - Mitigation: CSS-reset på `<summary>` i style.scss: `list-style: none; ::marker { display: none; }`. Custom indicator (chevron-svg) som vi äger 100%. Testar i Chrome/Firefox/Safari (Test 4.5).

  **R3 — `supports.anchor` interagerar med `get_block_wrapper_attributes`** (MEDIUM)
  - Risk: Gutenberg's `supports.anchor` lägger `id` på outermost element. Vi använder `<article>` som wrapper — om `get_block_wrapper_attributes()` antar `<div>` kan id hamna fel.
  - Mitigation: Test 4.2 verifierar att `<article id="anchor">` finns på frontend. Om problem uppstår: använd `<div>` som ytterwrapper med `<article>` inuti, eller verifiera att WP hanterar custom-tag korrekt.

  **R4 — Editor-preview mismatch mot frontend** (MEDIUM)
  - Risk: Editor renderar via React + edit.js, frontend via render.php. För 5 layouts kan visuell parity vara svår — särskilt bg-full med video-bakgrund och split-* med fullbleed.
  - Mitigation: Edit-preview ska matcha render.php-DOM-struktur exakt (samma klassnamn, samma element-hierarki). Test 3.2 + 4.1 jämför direkt. Acceptera mindre skillnader i editor-preview (t.ex. ingen video-autoplay i editor).

  **R5 — bg-full + media-layout-komplexitet** (MEDIUM)
  - Risk: bg-full är arkitektoniskt distinkt (absolut-positionerad bakgrund + text-overlay). Om ratio/aspect-ratio-handling är bristfällig blir det otydligt på mobila enheter.
  - Mitigation: bg-full har dedicerad `min-height: clamp(400px, 60vh, 720px)` så bakgrunden alltid har plats. CSS-overlay (linear-gradient) säkerställer text-läsbarhet oavsett bild-ljushet. Test 4.4 verifierar i 4 viewports.

  **R6 — DENSIQ-sidan behöver fler visual-block än Sprint B levererar** (HIGH — confirmed by Codex)
  - Risk: DENSIQ-stories har stat-counter, bar-chart, before-after, countdown — Sprint B levererar bara story-card-container. Codex kommer få 70-80% av målbilden, inte 100%.
  - Mitigation: ACCEPTERAT som scope-gräns. Sprint B är "foundation" för story-rebuild. Visualer planeras i Sprint C/D. DENSIQ kan använda kpi-grid som inner block för enkla siffror; resten ligger kvar som original-HTML eller core/cover tills Sprint C/D bygger dem.

  **R7 — `openByDefault` missbrukas** (LOW)
  - Risk: Redaktör sätter alla story-cards till `openByDefault: true` → disclosure tappar sin poäng (sidan blir lika lång som om disclosure inte fanns).
  - Mitigation: Inspector-control har `help`-text som rekommenderar att använda `openByDefault` sparsamt. Inte tekniskt blockerande — redaktör-konvention. Documentera i kund-onboarding om det blir problem.

  **R8 — Migration från ACF/HTML till story-card tar längre tid än blockimplementationen** (HIGH)
  - Risk: Att bygga story-card är ~600-1200 rader kod. Att konvertera ~20 densiq-story-instanser till story-card-blockmarkup är manuellt arbete för Codex/redaktör. Innehållsmigreringen kan ta 2-3x tiden av implementationen.
  - Mitigation: 
    - Sprint B levererar story-card OCH ger Codex en _exempel-page-mall_ med 1-2 representative stories konverterade. Resten är Codex/redaktörs ansvar.
    - Skript-stöd: dokumentera Gutenberg "Code editor"-paste-pattern så redaktör kan bulk-paste blockmarkup
    - Acceptera att rebuild-projektet på densiq tar längre tid än sprint-arbetet — den långa svansen är okontrollerbar i sprint-scope.

- [x] Beslut efter pre-mortem: scope **inte** justerat. Mitigations adresseras i FAS 1 + FAS 2: <!-- brian:id=tsk_13c66f52 gh:https://github.com/AGoodId/goodblocks/issues/75 -->
  - Comprehension-gate efter `save.js` + `render.php`-grundstruktur (innan layouter) — adresserar R1
  - CSS-reset på `<summary>` + custom chevron-svg — adresserar R2
  - Verifiera anchor i frontend-test 4.2 — adresserar R3
  - Edit-preview ska matcha render.php-DOM exakt — adresserar R4
  - bg-full har min-height + linear-gradient overlay — adresserar R5
  - **R6 dokumenteras i CHANGELOG som "Known scope: visualer i Sprint C/D"** — explicit kund-kommunikation
  - `openByDefault` får help-text i Inspector — adresserar R7
  - **R8 dokumenteras i CHANGELOG som "Implementation klar i v1.13.0; story-migration är client-arbete"** — sätter tydlig förväntan

---

## FAS 1 — IMPLEMENTATION

> Ge Claude spec + tester, inte problemet.
> Markera AI-genererade tasks med 🤖.

- [ ] 🤖 Skapa `src/blocks/story-card/` — block.json (alla attribut inkl. `layout` + `anchorId` + `mediaType`, render:file), render.php med `<details>`/`<summary>` och InnerBlocks-content via `$content`, edit.js med InspectorControls + InnerBlocks-template + allowedBlocks, view.js (vad som behövs för progressive enhancement), style.scss med CSS-variabler **utan font-family eller letter-spacing** <!-- brian:id=tsk_2d6e8303 gh:https://github.com/AGoodId/goodblocks/issues/76 -->
- [ ] 🤖 **Implementera 5 layouts:** `default` / `default-reverse` / `split-left` / `split-right` / `bg-full`. CSS för varje layout-variant, render.php-grenar för olika DOM-strukturer (split-* har `<figure>` utanför `__inner`, bg-full har `<video>`/`<img>` som första child) <!-- brian:id=tsk_eceb31bf gh:https://github.com/AGoodId/goodblocks/issues/77 -->
- [ ] 🤖 Implementera smooth-expand med `interpolate-size: allow-keywords` som progressiv enhancement; instant fallback i browsers utan stöd <!-- brian:id=tsk_5d9e52bc gh:https://github.com/AGoodId/goodblocks/issues/78 -->
- [ ] 🤖 a11y-pass: verifiera focus management, aria-attribut (om utöver native `<details>`), keyboard navigation <!-- brian:id=tsk_1b70cfc4 gh:https://github.com/AGoodId/goodblocks/issues/79 -->
- [ ] 🤖 Uppdatera `inc/showcase.php` — registrera story-card med help_key och rimlig live-config (eller live=false om InnerBlocks-rendering är problematisk i style guide) <!-- brian:id=tsk_13b88dd7 gh:https://github.com/AGoodId/goodblocks/issues/80 -->
- [ ] 🤖 Uppdatera `webpack.config.js` (entry points) + `goodblocks.php` (block-slug-array) <!-- brian:id=tsk_18c82310 gh:https://github.com/AGoodId/goodblocks/issues/81 -->

---

## FAS 2 — KÖRNING, VERIFIERING & EVAL

> Fas 2 kör testerna som definierades i Fas 0 — den definierar dem inte.

### 2A. Kör testerna

- [ ] Enhetstester gröna <!-- brian:id=tsk_3081289a gh:https://github.com/AGoodId/goodblocks/issues/82 -->
- [ ] Integrationstester gröna — editor → render.php → frontend disclosure-flöde <!-- brian:id=tsk_844815a7 gh:https://github.com/AGoodId/goodblocks/issues/83 -->
- [ ] E2E-scenario verifierat — inkl. JS-disabled-test <!-- brian:id=tsk_e6f88513 gh:https://github.com/AGoodId/goodblocks/issues/84 -->

### 2B. Comprehension Gate

- [ ] `/comprehension-gate` på `src/blocks/story-card/` — verdict: CLEAR / REVIEW / HOLD <!-- brian:id=tsk_f0373ebc gh:https://github.com/AGoodId/goodblocks/issues/85 -->
- [ ] Kan du förklara hur InnerBlocks lagrar och renderar inner content i en disclosure-struktur, och varför `<details>` valdes över JS-toggle? <!-- brian:id=tsk_0d8325bb gh:https://github.com/AGoodId/goodblocks/issues/86 -->

### 2C. Eval — Uppfylldes specen?

- [ ] Alla 15 acceptanskriterier uppfyllda <!-- brian:id=tsk_4a09d33f gh:https://github.com/AGoodId/goodblocks/issues/87 -->
- [ ] Inga ospecificerade AI-tillägg utanför scope <!-- brian:id=tsk_9b6a2b0b gh:https://github.com/AGoodId/goodblocks/issues/88 -->
- [ ] Antaganden fortfarande giltiga? a11y-test gjort på riktig screen reader? <!-- brian:id=tsk_bc3d5d25 gh:https://github.com/AGoodId/goodblocks/issues/89 -->

---

## Definition of Done

- [ ] Fas 0 komplett — spec och tester definierade innan implementation
- [ ] Alla Fas 1-tasks klara
- [ ] Tester gröna (2A)
- [ ] Comprehension gate: CLEAR (2B)
- [ ] Alla 15 acceptanskriterier uppfyllda (2C)
- [ ] `npm run lint` — 0 errors
- [ ] `npm run build` — successful
- [ ] No-JS-test: innehåll tillgängligt utan JS
- [ ] a11y-test: keyboard + screen reader fungerar
- [ ] Tema-oberoendet verifierat (TwentyTwentyFour)
- [ ] Responsivitet verifierad i 320 / 768 / 1024 / 1440px viewports

---

## Lärdomar

[Fylls i när sprints stängs]
