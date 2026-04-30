---
type: test-plan
related: sprints/doing/sprint-report-foundation-blocks-260429.md
created: 2026-04-29
---

# Sprint A: Test Plan — report-foundation-blocks

> Konkreta testfall för 0D. Acceptanskriterier (0C punkt 4) verifieras av dessa tester. Tester körs i FAS 2A — definieras nu så de kan ges till AI tillsammans med spec i FAS 1.

## Antaganden (0C.3)

- **WordPress:** ≥ 6.4 (Gutenberg apiVersion 3, full block.json-stöd)
- **PHP:** ≥ 8.0
- **Browser-baseline:** Modern evergreen — Chrome/Edge/Firefox senaste 2 versioner, Safari 16+
- **IntersectionObserver:** finns i alla mål-browsers; feature-detection + fallback i view.js för säkerhets skull
- **CSS-features:** `clamp()`, `color-mix()`, `:has()` — supportade i alla mål-browsers
- **Inga externa beroenden:** ingen GSAP, ingen ACF/SCF, inga ytterligare npm-paket utöver `@wordpress/*` som redan finns
- **Tema-stöd:** verifieras på TwentyTwentyFour (default-tema utan brand-opinions); agoodsite-fse-koppling är known unknown (D5 i CONTEXT.md)
- **Inga DB-migrationer:** legacy hero-data (animation: ingen/standard/wild/from-right/from-left) hanteras via runtime-mappning i render.php

---

## Enhetstester (0D.1)

### Hero (post-PURGE)

| Test | Input | Förväntat |
|---|---|---|
| Animation enum-validering | `animation: "standard"` (legacy) | Mappas till `"none"`, `<div class="...hero-block--none">` |
| Animation enum-validering | `animation: "fade-up"` | `<div class="...hero-block--fade-up">` |
| Animation enum-validering | `animation: "split-words"` | `<div class="...hero-block--split-words">` |
| Animation enum-validering | `animation: "okänt"` | Mappas till `"none"` (defensiv) |
| backgroundMedia null | `backgroundMedia: null` | Inget `<video>`-element renderas |
| backgroundMedia video | `{ type: "video", url: "...", mime: "video/mp4" }` | `<video class="hero-block__video">` med `<source>` |
| dimRatio 0 | `dimRatio: 0` | Overlay opacity 0 |
| dimRatio 100 | `dimRatio: 100` | Overlay opacity 1 |
| Knapp utan URL | `button: "Click", buttonUrl: ""` | `<button type="button">` |
| Knapp med URL | `button: "Click", buttonUrl: "/path"` | `<a class="btn" href="/path">` |
| Knapp med target=_blank | `buttonTarget: "_blank"` | `<a target="_blank" rel="noopener noreferrer">` |
| Tom knapptext | `button: ""` | Ingen knapp renderas |
| Scroll-arrow false | `scrollArrow: false` | Inget `<button class="hero-block__scroll-arrow">` |
| Scroll-arrow true | `scrollArrow: true` | `<button class="hero-block__scroll-arrow" aria-label="Scroll down">` |
| Position class | `contentPosition: "top left"` | `.hero-block__content` har `.is-position-top-left` |
| Position class okänd | `contentPosition: "okänt"` | `.hero-block__content` får ingen position-klass (`getPositionClassName` returnerar `''`) |
| reverseFlow | `reverseFlow: true` | `.hero-block__text` har `.reverse-flow` |
| Höjd | `height: "75vh"` | inline-style innehåller `height: 75vh` |

### Section-header

| Test | Input | Förväntat |
|---|---|---|
| Alignment left | `alignment: "left"` | `.is-aligned-left` på root |
| Alignment center | `alignment: "center"` | `.is-aligned-center` på root |
| Kicker before | `kicker: "01", numberPosition: "before"` | `<span class="section-header__kicker">` FÖRE `<h2>` |
| Kicker after | `kicker: "01", numberPosition: "after"` | `<span>` EFTER `<h2>` med klass `--after` |
| Kicker none | `kicker: "01", numberPosition: "none"` | Ingen kicker renderas |
| Kicker tom | `kicker: "", numberPosition: "before"` | Ingen kicker renderas (falsy guard) |
| Tema light | `theme: "light"` | `.section-header--light` |
| Tema dark | `theme: "dark"` | `.section-header--dark` |
| Tema accent | `theme: "accent"` | `.section-header--accent` |
| Tom title | `title: ""` | Inget `<h2>` renderas |
| Tom lead | `lead: ""` | Ingen `<p class="section-header__lead">` |

### KPI-grid

| Test | Input | Förväntat |
|---|---|---|
| 1 tile | `items: [{ id: "a", value: "5", label: "x" }]` | 1 `.kpi-grid__tile`, klass `kpi-grid--cols-1` |
| 3 tiles | `items: [3 items], columns: "auto"` | 3 tiles, klass `kpi-grid--cols-3` |
| 6 tiles | `items: [6 items], columns: "auto"` | 6 tiles, klass `kpi-grid--cols-6` |
| 7 tiles (overflow) | `items: [7 items]` | Capped till 6 i edit.js (UI hindrar add); render.php tar emot exakt vad som sparas |
| Explicit columns | `items: [3], columns: "5"` | klass `kpi-grid--cols-5` (3 tiles, 2 tomma cells per redaktörens val) |
| Prefix + value + suffix | `{ prefix: "−", value: "71", suffix: "%" }` | 3 separata `<span>`: `__prefix`, `__number`, `__suffix` |
| Bara value | `{ value: "5" }` | Bara `__number`-span, ingen prefix/suffix |
| Tom label | `{ value: "5", label: "" }` | Ingen `__label`-div |
| Stabilt id | items reordas | id på varje tile bevaras (React-key + data-attribut) |
| Tema | `theme: "dark"` | `.kpi-grid--dark` |

---

## Integrationstester (0D.2)

> Editor → save → render.php → frontend-DOM → view.js initialiserar interaktivitet.

### Hero

1. **Skapa hero med fade-up + scroll-arrow + button-länk**
   - Editor: lägg till hero, sätt animation=fade-up, scrollArrow=true, button="Read more", buttonUrl="/about"
   - Spara post → kontrollera att `post_content` innehåller korrekt block-comment-JSON med alla attribut
   - Visa frontend → `<div class="...hero-block--fade-up">`, `<button class="hero-block__scroll-arrow">`, `<a href="/about">Read more</a>`
   - Scrolla in viewport → IntersectionObserver triggar `.is-in-view`-klass
   - Klick på scroll-arrow → smooth-scroll till nästa sektion

2. **Hero med video-bakgrund**
   - Lägg till hero, välj video från mediebibliotek
   - Spara → kontrollera `backgroundMedia.type === "video"` i sparade attribut
   - Frontend → `<video autoplay muted loop playsinline>` renderas, video spelar

3. **Legacy-hero (post-uppdatering)**
   - Manuellt skriv post_content med `"animation":"standard"` (gammalt värde)
   - Visa frontend → renderas som `.hero-block--none`, ingen "block invalid"-varning i editor

### Section-header

1. **Skapa section-header med kicker före titel**
   - Editor: kicker="01", title="Strategy", lead="Built into how we work", alignment=left, numberPosition=before, theme=dark
   - Spara → frontend renderar `<span class="section-header__kicker">01</span>` FÖRE `<h2>Strategy</h2>`
   - `.section-header--dark` ger mörk bakgrund + ljus text

2. **Section-header utan kicker**
   - Lämna kicker tom, sätt numberPosition=before
   - Frontend: ingen kicker-span renderas (defensiv guard)

### KPI-grid

1. **3 tiles med prefix/suffix-mix**
   - Editor: lägg till 3 items, första `{value:"71", prefix:"−", suffix:"%"}`, andra `{value:"2025"}`, tredje `{value:"5", suffix:"yrs"}`
   - Spara → kontrollera items i post_content med korrekta id (auto-genererade)
   - Frontend: 3 `.kpi-grid__tile`, första har 3 spans, andra har 1, tredje har 2

2. **Reorder-test**
   - Lägg till 3 tiles, dra andra till första position
   - Spara → kontrollera att id-fälten är intakta (inte regenererade)
   - Reopen post → ordning bevarad

---

## E2E / manuella tester (0D.3)

> Verifiera i fyra viewports: 320 / 768 / 1024 / 1440px. Test både på agoodsite-fse OCH TwentyTwentyFour.

1. **Bygg en testsida** med:
   - Hero (fade-up, scroll-arrow, video-bakgrund)
   - Section-header (theme=dark, kicker=01, lead)
   - KPI-grid (3 tiles)
   - Section-header (theme=accent, no kicker)
   - KPI-grid (6 tiles)

2. **För varje viewport:**
   - [ ] Ingen horisontell scroll
   - [ ] Hero fyller viewport utan att klippas (`100svh` fungerar)
   - [ ] Hero animation triggar vid scroll-in
   - [ ] Scroll-arrow är klickbar med touch (≥ 44×44px)
   - [ ] Section-header text läsbar
   - [ ] KPI-tiles staplade på mobil, breddade på desktop
   - [ ] Tema-färgväxlingar fungerar (light → dark → accent → light...)

3. **No-JS-test** (DevTools → disable JS):
   - [ ] Hero-rubrik synlig (animation som progressive enhancement)
   - [ ] Scroll-arrow är synlig men icke-klickbar (acceptabelt)
   - [ ] Knapp med URL fungerar (det är ett `<a>`)
   - [ ] Section-header + KPI-grid renderas oförändrat

4. **A11y-test:**
   - [ ] Hero scroll-arrow har `aria-label="Scroll down"`
   - [ ] Knapp har semantisk korrekt element (`<a href>` om URL, annars `<button type="button">`)
   - [ ] Tab-navigation når alla interaktiva element
   - [ ] Skärmläsare läser kicker, title, lead i logisk ordning

---

## Regression (0D.4)

Befintliga block ska fortsätta fungera oförändrat:

- [ ] `goodblocks/testimonials` — Swiper-karusell, autoplay, pausknapp
- [ ] `goodblocks/search-autocomplete` — autocomplete, suggestedLinks
- [ ] `goodblocks/event-list` — list + grid layout, all-day events
- [ ] `goodblocks/masonry-query` — masonry-rendering
- [ ] `goodblocks/image-compare` — slider med IntersectionObserver-tease
- [ ] Övriga block (slider, hero som det var pre-PURGE i alla MALAR — viktigast)

Kommandon:
- [ ] `npm run lint` — 0 nya errors
- [ ] `npm run build` — successful, ingen ny webpack-warning
- [ ] PHP-syntax på alla nya/ändrade filer

---

## Vad som INTE testas i Sprint A

- Pixel-perfekt visuell matchning mot densiq-sidan (det är densiq-claude:s ansvar i deras egen proof-of-concept)
- Animationskvalitet (mjukhet, timing) — subjektivt, justeras iterativt
- A11y-tester med riktig screen reader (sparas till Sprint B där `<details>`/InnerBlocks gör det mer kritiskt)
- Performance-budget (om ny CSS/JS lägger > 50KB sammanlagt — flaggas men inte blockerande)
