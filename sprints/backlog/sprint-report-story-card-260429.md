---
priority: 2
status: backlog
start: TBD (efter Sprint A)
tags: [sprint, feature]
---

# Sprint B: report-story-card

> Detta är **Sprint B** av en två-sprintssekvens. Sprint A (`sprint-report-foundation-blocks-260429.md`) levererar hero-animation, section-header och kpi-grid. Sprint B fokuserar uteslutande på `goodblocks/story-card` eftersom det är den arkitektoniskt tyngsta biten (InnerBlocks + disclosure + no-JS + a11y + editor UX).

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
  - Attribut: `kicker`, `title`, `excerpt`, `mediaId`, `mediaUrl`, `mediaAlt`, `actionUrl`, `actionLabel`, `labels` (string-array, generiska facets)
  - **Expanderbart innehåll via InnerBlocks** med template + allowedBlocks
  - Disclosure: `<details>` + `<summary>`-mönster
  - Smooth animation som progressive enhancement (`interpolate-size`)
  - Action-button är generisk
  - Inga hårdkodade font-family eller letter-spacing
- **Showcase-registrering** — lägg till story-card i `inc/showcase.php`
- **Webpack/registrering** — uppdatera `webpack.config.js` + `goodblocks.php`
- **a11y-verifiering** — keyboard navigation, screen reader, focus management

### Exkluderat

- ASR-specifika story-mönster (filter/facetter, kategorisystem) — hanteras av `densiq-asr-2025`
- CPT/post_tag-modellering — om kunder vill bryta ut stories till en CPT är det deras tema/plugin
- PDF-export
- Animation av media (parallax, scroll-fade) — eventuellt egen sprint senare
- Anpassad share-funktion utöver generisk action-button

---

## Beslut (lockade innan Fas 1)

- **Disclosure-mekanism**: `<details>`/`<summary>` — **inte** JS-toggleat `display: none`. Native semantics, keyboard, no-JS, screen reader stödjs out-of-the-box.
- **Smooth animation**: Progressive enhancement via `interpolate-size: allow-keywords` (Chrome/Edge 129+). Andra browsers får instant open/close. Detta är medvetet — vi prioriterar a11y över animation.
- **Inner content via InnerBlocks** — template + allowedBlocks, inte HTML-attribut
- **Action-button är generisk** — URL + label, inte rapport-specifik share
- **Defaults utan brand-opinion** — samma regel som Sprint A: inget hårdkodat font-family/letter-spacing

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

- [ ] `/dark-code-audit` på `src/blocks/card-feature/` (referens-mönster för InnerBlocks) <!-- brian:id=tsk_58d63111 -->
- [ ] `/context-layer` på Sprint A:s nya block — så story-card harmoniserar med dem <!-- brian:id=tsk_5fba2ffb -->

### 0B. Återanvändningskoll

- [ ] Genomsök `src/blocks/*/edit.js` efter befintliga InnerBlocks-templates med allowedBlocks <!-- brian:id=tsk_013d0e6a -->
- [ ] Genomsök efter `<details>`-mönster i repot eller annan disclosure-implementation <!-- brian:id=tsk_988f4eb5 -->
- [ ] Besluta vad som återanvänds: card-feature InnerBlocks-mönster, testimonials block.json-struktur, Sprint A:s CSS-variabler <!-- brian:id=tsk_8def375a -->

### 0C. Spec

- [ ] **Problemspec:** Vilka exakta story-mönster på densiq-sidan ska story-card ersätta? Inkludera HTML-struktur, animationer, expand-beteende, share-knappar <!-- brian:id=tsk_734d077f -->
- [ ] **Lösningsspec:** Datamodell (alla attribut), InnerBlocks-template + allowedBlocks-lista, HTML-struktur i render.php, CSS-variabler, defaults <!-- brian:id=tsk_f2917074 -->
- [ ] **`<details>`-arkitektur:** Hur strukturerar vi DOM:en för att `<summary>` och `<details>`-content interagerar med InnerBlocks-rendering? Lös: ska summary vara fast text eller redigerbar? <!-- brian:id=tsk_5b49635f -->
- [ ] **Antaganden:** WP 6.4+, IntersectionObserver, `interpolate-size` som progressive enhancement (Chrome/Edge 129+), inga GSAP, inga ACF <!-- brian:id=tsk_9fe5908c -->
- [ ] **Acceptanskriterier:** Konkreta påståenden (se förslag nedan) <!-- brian:id=tsk_606bae89 -->

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

- [ ] **Enhet:** InnerBlocks-template returnerar förväntade allowedBlocks; render.php genererar korrekt `<details>`-struktur; action-button rendereras bara när URL+label finns <!-- brian:id=tsk_8b5a7272 -->
- [ ] **Integration:** Block sparat i editor med inner blocks → render.php → frontend visar korrekt struktur, expand fungerar <!-- brian:id=tsk_2ac62557 -->
- [ ] **E2E / manuellt:** Skapa story-card med inner blocks → spara → öppna frontend → klicka summary → innehåll syns → tabba genom innehåll → stäng → JS-disabled-test (devtools): innehåll fortfarande tillgängligt <!-- brian:id=tsk_55a18c99 -->
- [ ] **a11y / manuellt:** VoiceOver eller NVDA-test av disclosure-state; tangentbordsnavigation; focus management <!-- brian:id=tsk_8d942c14 -->
- [ ] **Regression:** Sprint A:s block + befintliga block fungerar fortfarande <!-- brian:id=tsk_00c9d724 -->

### 0E. Pre-mortem

- [ ] Kör pre-mortem — lista riskerna: <!-- brian:id=tsk_89ad7dd9 -->
  1. [Fyll i efter pre-mortem]
  2. [Fyll i efter pre-mortem]
  3. [Fyll i efter pre-mortem]
- [ ] Beslut: förändras scope eller spec baserat på riskerna? <!-- brian:id=tsk_13c66f52 -->

---

## FAS 1 — IMPLEMENTATION

> Ge Claude spec + tester, inte problemet.
> Markera AI-genererade tasks med 🤖.

- [ ] 🤖 Skapa `src/blocks/story-card/` — block.json (alla attribut, render:file), render.php med `<details>`/`<summary>` och InnerBlocks-content via `$content`, edit.js med InspectorControls + InnerBlocks-template + allowedBlocks, view.js (vad som behövs för progressive enhancement), style.scss med CSS-variabler **utan font-family eller letter-spacing** <!-- brian:id=tsk_2d6e8303 -->
- [ ] 🤖 Implementera smooth-expand med `interpolate-size: allow-keywords` som progressiv enhancement; instant fallback i browsers utan stöd <!-- brian:id=tsk_5d9e52bc -->
- [ ] 🤖 a11y-pass: verifiera focus management, aria-attribut (om utöver native `<details>`), keyboard navigation <!-- brian:id=tsk_1b70cfc4 -->
- [ ] 🤖 Uppdatera `inc/showcase.php` — registrera story-card med help_key och rimlig live-config (eller live=false om InnerBlocks-rendering är problematisk i style guide) <!-- brian:id=tsk_13b88dd7 -->
- [ ] 🤖 Uppdatera `webpack.config.js` (entry points) + `goodblocks.php` (block-slug-array) <!-- brian:id=tsk_18c82310 -->

---

## FAS 2 — KÖRNING, VERIFIERING & EVAL

> Fas 2 kör testerna som definierades i Fas 0 — den definierar dem inte.

### 2A. Kör testerna

- [ ] Enhetstester gröna <!-- brian:id=tsk_3081289a -->
- [ ] Integrationstester gröna — editor → render.php → frontend disclosure-flöde <!-- brian:id=tsk_844815a7 -->
- [ ] E2E-scenario verifierat — inkl. JS-disabled-test <!-- brian:id=tsk_e6f88513 -->

### 2B. Comprehension Gate

- [ ] `/comprehension-gate` på `src/blocks/story-card/` — verdict: CLEAR / REVIEW / HOLD
- [ ] Kan du förklara hur InnerBlocks lagrar och renderar inner content i en disclosure-struktur, och varför `<details>` valdes över JS-toggle?

### 2C. Eval — Uppfylldes specen?

- [ ] Alla 15 acceptanskriterier uppfyllda
- [ ] Inga ospecificerade AI-tillägg utanför scope
- [ ] Antaganden fortfarande giltiga? a11y-test gjort på riktig screen reader?

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
