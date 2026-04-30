---
type: test-plan
related: sprints/doing/sprint-report-story-card-260429.md
spec: sprints/reference/story-card-spec-260430.md
created: 2026-04-30
---

# Sprint B: Test Plan — story-card

> Konkreta testfall för 0D. Acceptanskriterier (spec sektion 7) verifieras av dessa tester. Tester körs i FAS 2A.

## Testmiljöer

| Miljö | Tema | Syfte |
|---|---|---|
| **fse.agoodsite.se** | agoodsite-fse | Primary acceptance |
| **TwentyTwentyFour (lokalt)** | TT4 | Tema-oberoende-validering |
| **densiq draft (sustainability-2025-goodblocks)** | agoodtheme_densiq | Real-world story-konvertering (codex) |

---

## 1. Unit / Static Checks

> Körs via `grep`/inspection. Inga externa körningar.

### 1.1 `block.json` (story-card-spec sektion 1)

```bash
# Sanity grep
cat src/blocks/story-card/block.json | jq '.apiVersion, .render, .supports.anchor, .viewScript'
# Expected:
#   3
#   "file:./render.php"
#   true
#   null  ← inget viewScript-fält
```

| Test | Expected |
|---|---|
| `apiVersion === 3` | ✅ |
| `render === "file:./render.php"` | ✅ |
| `supports.anchor === true` | ✅ |
| `supports.html === false` | ✅ |
| `supports.align === ["wide","full"]` | ✅ |
| `viewScript` saknas helt | ✅ |
| `attributes.layout.enum` innehåller `["default","reverse","split-left","split-right","bg-full"]` | ✅ |
| `attributes.theme.enum` innehåller `["light","dark","accent"]` | ✅ |
| `attributes.mediaType.enum` innehåller `["image","video"]` | ✅ |
| `attributes.actionTarget.enum` innehåller `["_self","_blank"]` | ✅ |
| Alla 15 attribut (inkl. `openByDefault`, `summaryLabel`) finns | ✅ |

### 1.2 `save.js`

```bash
grep -E "InnerBlocks\.Content|export default" src/blocks/story-card/save.js
```

| Test | Expected |
|---|---|
| Importerar `InnerBlocks` från `@wordpress/block-editor` | ✅ |
| Default-export är funktion som returnerar `<InnerBlocks.Content />` | ✅ |
| INTE `null`-return (annars persistas inga inner blocks) | ✅ |

### 1.3 `edit.js`

```bash
grep -E "allowedBlocks|template|templateLock|fontSize|textColor|_shared|data-animate" src/blocks/story-card/edit.js
```

| Test | Expected |
|---|---|
| `<InnerBlocks>` används med `allowedBlocks={ ALLOWED_BLOCKS }` | ✅ |
| `template={ TEMPLATE }` definieras | ✅ |
| `templateLock={ false }` (inte true, inte saknas) | ✅ |
| `ALLOWED_BLOCKS` innehåller `'goodblocks/kpi-grid'` | ✅ |
| Inga `fontSize: 'lg'`/`textColor: 'text-muted'` i template | ✅ |
| Inga `data-animate`-attribut | ✅ |
| Inga `from '../_shared/'`-imports | ✅ |

### 1.4 `style.scss`

```bash
grep -E "font-family|letter-spacing|text-transform: uppercase|@use.*_shared" src/blocks/story-card/style.scss
```

| Test | Expected |
|---|---|
| Inga `font-family:`-deklarationer (utöver `inherit`) | ✅ |
| Inga `letter-spacing:`-deklarationer (utöver explicit `0`) | ✅ |
| Inga `text-transform: uppercase` i defaults | ✅ |
| Inga `@use "../_shared/..."` | ✅ |
| 25+ CSS-variabler med `--story-card-*`-prefix | ✅ |
| Tema-fallback-pattern: `var(--wp--preset--*, #fallback)` | ✅ |

---

## 2. PHP / render.php Checks

### 2.1 PHP-syntax och WP_DEBUG-noticefritt

```bash
php -l src/blocks/story-card/render.php
# Expected: "No syntax errors detected"
```

Kör med `WP_DEBUG=true` och `WP_DEBUG_LOG=true` på testmiljö → inga warnings/notices i `/wp-content/debug.log` när story-card renderar.

### 2.2 Tomma attribut hanteras

| Input | Förväntat output |
|---|---|
| Inga attribut alls (alla defaults) | Render returnerar tomt (title saknas) ELLER renderar minimal struktur utan notices |
| Bara `title` satt | `<article>` med `<h3>`, ingen kicker/excerpt/labels/disclosure/media renderas |
| `mediaType: "image"` men `mediaUrl: ""` | Ingen `<figure>`/`<img>` renderas (inte tom `<img src="">`) |
| `actionUrl` satt men `actionLabel` tom | Ingen `<a>` renderas (kräver båda) |
| `labels: []` | Ingen `<ul class="story-card__labels">` |
| Inga inner blocks | Ingen `<details>` renderas |

### 2.3 Enum-fallbacks

| Input | Render-värde |
|---|---|
| `layout: "okänt-värde"` | Mappas till `"default"` |
| `theme: "rosa"` | Mappas till `"light"` |
| `mediaType: "audio"` | Mappas till `"image"` |
| `actionTarget: "_top"` | Mappas till `"_self"` |

### 2.4 `get_block_wrapper_attributes` används

```bash
grep "get_block_wrapper_attributes" src/blocks/story-card/render.php
```

| Test | Expected |
|---|---|
| `get_block_wrapper_attributes()` anropas exakt 1 gång | ✅ |
| Returvärdet skrivs ut i `<article>`-wrapper | ✅ |
| WordPress-genererat `id`-attribut (från `supports.anchor`) hamnar på wrapper | ✅ (verifieras i frontend-test 4.2) |

### 2.5 Disclosure-villkor

| Input | Förväntad disclosure-rendering |
|---|---|
| Inner blocks med innehåll | `<details>` renderas |
| Inga inner blocks (`$content` tom/whitespace) | INGEN `<details>` renderas |
| `openByDefault: false` (default) | `<details>` utan `open`-attribut |
| `openByDefault: true` | `<details open>` |
| `summaryLabel: ""` | Default text "Read more" (via `__()`) |
| `summaryLabel: "Visa hela"` | Renderas som "Visa hela" |

### 2.6 `$content`-eskapning

```bash
grep -A 2 "echo \$content" src/blocks/story-card/render.php
```

| Test | Expected |
|---|---|
| `echo $content` görs ENDAST inom `<div class="story-card__body">` | ✅ |
| `phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped` följer | ✅ |
| `$content` echo:as INTE i andra delar av template (inte i kicker/title/excerpt) | ✅ |

### 2.7 XSS-suspicion-test

Skapa story-card-instans med:
- `title: "<script>alert(1)</script>"`
- `kicker: "<img onerror=alert(1)>"`
- `actionUrl: "javascript:alert(1)"`

Frontend → ingen alert. Förväntat:
- `wp_kses_post` strippar `<script>` från title
- `wp_kses_post` strippar `onerror` från img
- `esc_url()` neutraliserar `javascript:`-protokoll → href blir tom eller `#`

---

## 3. Editor Integration

> Manuellt i WP-admin på testmiljö.

### 3.1 Skapa-och-spara-flöde

1. Skapa ny sida → lägg till `goodblocks/story-card`
2. Verifiera: block-väljaren visar story-card under "GoodBlocks"-kategori
3. Block läggs in med default-template (h3 + paragraph) i disclosure-area
4. Fyll i: kicker "01", title "Test Story", excerpt "Test ingress", action URL + label, summary "Visa mer", välj theme=Dark
5. Lägg in inner blocks: en `goodblocks/kpi-grid` med 3 tiles
6. Spara post
7. Reload editor → alla attribut bevarade, kpi-grid-instansen är intakt med sina items

### 3.2 Layout-växling

För varje `layout`-värde (default, reverse, split-left, split-right, bg-full):
- Välj layout via Inspector → editor-preview uppdateras
- Spara → reload → layout-värdet bevaras
- För `bg-full`: media renderas som bakgrund i editor-preview

### 3.3 InnerBlocks-template

1. Skapa story-card → default-template (h3 + paragraph) syns
2. Ta bort h3 → block ska tillåta detta (`templateLock: false`)
3. Lägg till `goodblocks/kpi-grid` → tillåtet
4. Försök lägga till `core/columns` → BLOCKERAS (inte i `allowedBlocks`)

### 3.4 Anchor-fält (Inspector)

1. Öppna story-card → Inspector → Avancerat-panel
2. "HTML-ankare"-fält finns (Gutenberg-built-in)
3. Skriv "test-anchor" → spara → reload editor → värdet bevaras

### 3.5 Media-attribut

1. Klicka media-upload → välj bild → mediaId, mediaUrl, mediaAlt sätts
2. Editor-preview visar bilden i layout-rätt position
3. Byt till video → välj video från mediabibliotek → mediaType=video
4. Editor-preview visar `<video>` (kan vara stillbild om autoplay inte funkar i editor)

### 3.6 Labels

1. Lägg till 3 labels: "climate", "operations", "supply chain"
2. Editor-preview renderar dem som chips
3. Spara → reload → labels bevarade

---

## 4. Frontend

### 4.1 Alla 5 layouts renderar korrekt

För varje layout, skapa testsida med:
- title, excerpt, action, summary
- 1 inner block (paragraph)
- En image som media

Frontend-verifiering per layout:

| Layout | Förväntat |
|---|---|
| `default` | Text vänster, media höger på desktop ≥960px; staplade på mobil |
| `reverse` | Media vänster, text höger på desktop ≥960px; **text först på mobil** (läsbarhet) |
| `split-left` | Media fullbleed vänster (object-fit:cover), text höger med padding på desktop ≥768px; staplade mobil |
| `split-right` | Speglad split-left |
| `bg-full` | Media som bakgrund (absolute), text overlay på top, läsbar med `<linear-gradient>`-overlay |

### 4.2 Anchor / deeplink

1. Sätt anchor "story-1" på en story-card
2. Spara, visa frontend
3. URL: `https://site/page/#story-1`
4. Browser scrollar till story-card automatiskt
5. Verifiera: `<article id="story-1">` finns i HTML
6. Om openByDefault är false: disclosure är fortfarande stängd vid scroll (inget auto-open)

### 4.3 No-JS test

1. DevTools → Settings → Disable JavaScript
2. Reload sida med story-card med inner blocks + disclosure
3. Klicka summary → `<details>` toggles open/close (native)
4. Tab-navigera → summary är tabbbar
5. Action-link fungerar (det är ett `<a>`-element)
6. Verifiera: ingenting är permanent osynligt
7. Notera: smooth-expand fungerar inte utan JS — accepterad (instant toggle)

### 4.4 Responsive (320 / 768 / 1024 / 1440)

DevTools responsive-mode för varje layout:

| Viewport | default | reverse | split-left | split-right | bg-full |
|---|---|---|---|---|---|
| 320 | 1 col, text→media | 1 col, text→media | 1 col, stack | 1 col, stack | full-bleed bg, text overlay |
| 768 | 1 col | 1 col | 2 col break | 2 col break | full-bleed bg |
| 1024 | 1 col | 1 col | 2 col | 2 col | full-bleed bg |
| 1440 | 2 col | 2 col reverse | 2 col | 2 col | full-bleed bg |

Krav per viewport:
- Ingen horisontell scroll
- Action-knapp ≥ 44×44px touch-target
- Summary ≥ 44×44px touch-target
- Text läsbar (inte under 16px på mobil)

### 4.5 Smooth-expand i Chrome 129+

1. Öppna story-card-frontend i Chrome 129+
2. Klicka summary → body expanderar **smooth** (inte instant)
3. I Chrome <129 eller Firefox/Safari → instant open/close (acceptabelt)
4. Med `prefers-reduced-motion: reduce` aktiverat → ALLTID instant

### 4.6 `openByDefault`-test

1. Skapa story-card med `openByDefault: true`
2. Frontend → `<details open>` renderas
3. Body-content syns från start utan klick
4. Klick på summary → stänger disclosure (native toggle)

---

## 5. A11y

### 5.1 Keyboard navigation

1. Tab in på sidan → fokus når summary
2. Visuellt fokus-indikator syns på summary (browsers default ELLER vår CSS via `:focus-visible`)
3. Space eller Enter → togglar `<details>` open/close
4. Tab in i disclosure-body när öppen → fokus går till första interaktiva element
5. Shift+Tab tillbaka till summary → fungerar
6. Tab vidare till action-link → fokus där (separerad från disclosure)

### 5.2 Screen reader

Test i VoiceOver (macOS) eller NVDA (Windows):
1. Fokus på summary → läser "Read more, button, collapsed" (eller motsv.)
2. Aktivera (VO+space / NVDA+enter) → läser "expanded"
3. Body-content läses upp i läsordning
4. Action-link läses som "Read more, link" — separat från disclosure
5. Labels läses som lista: "List, 3 items, climate, operations, supply chain"

### 5.3 ARIA-validering

Inga manuella `aria-expanded`/`aria-controls` på summary/details — native semantics gör jobbet. Verifiera med devtools accessibility-tree:

| Element | Förväntat role |
|---|---|
| `<article class="story-card">` | `article` |
| `<details>` | `group` (native) |
| `<summary>` | `button` (native, expanded-state-aware) |
| `<a class="story-card__action">` | `link` |
| `<ul class="story-card__labels">` | `list` |

### 5.4 Action-link separation

| Test | Förväntat |
|---|---|
| Klick på story-card-text-area (inte summary, inte action) | Inget händer (story-card är inte länk) |
| Klick på summary | Disclosure togglas |
| Klick på action-link | Navigerar till URL |

Detta verifierar locked decision: ingen helkorts-`<a>`-wrapper.

### 5.5 Labels är inte interaktiva

| Test | Förväntat |
|---|---|
| Tab in i `<ul class="story-card__labels">` | INGEN av `<li>`-element är fokuserbara |
| Klick på `<li>` | Inget händer (inte knapp, inte länk) |
| Screen reader läser labels | "List item: climate" — inte "button: climate" |

---

## 6. Regression

### 6.1 Sprint A-block fungerar fortfarande

På testmiljö: skapa testsida med:
- `goodblocks/hero` (med animation: split-words + scrollArrow)
- `goodblocks/section-header`
- `goodblocks/kpi-grid`
- `goodblocks/story-card` (ny i Sprint B)

Verifiera:
- Hero-animation triggar
- Section-header rendererar med valt theme
- KPI-grid med tiles renderar
- Story-card disclosure fungerar
- Inga JS-error i console
- Inga PHP-warnings i debug.log

### 6.2 Andra GoodBlocks fungerar

Snabb-test i editor:
- `goodblocks/testimonials` — Swiper-karusell laddar
- `goodblocks/search-autocomplete` — sökfält fungerar
- `goodblocks/event-list` — events renderas
- `goodblocks/masonry-query` — masonry-grid bygger upp

### 6.3 Build + lint

```bash
npm run build      # → "compiled successfully"
npm run lint:js    # → 0 errors (3 pre-existing warnings OK)
```

### 6.4 Showcase-registrering

1. Aktivera agoodsite-fse-tema (har showcase-filtret)
2. Öppna style guide
3. Verifiera: story-card visas med 5 layout-configs (om vi registrerade dem)
4. Live-rendering av configs fungerar (eller visar `live: false`-note om InnerBlocks-rendering är problematisk)

### 6.5 InnerBlocks-persistens efter pluginuppdatering

1. På testmiljö: skapa story-card med inner blocks, spara
2. Inaktivera GoodBlocks → reaktivera (simulerar update)
3. Reload editor → inner blocks bevarade (testar att deactivate-hook inte bryter dem)
4. Frontend → renderar fortfarande korrekt

---

## 7. Coverage-tabell (acceptanskriterium → test)

| AC # | Spec-AC | Täcks av test |
|---|---|---|
| 1 | Block syns i editor | 3.1 |
| 2 | Editor sparar/laddar InnerBlocks | 3.1 |
| 3 | Frontend renderar `$content` | 4.1 + 2.5 |
| 4 | No-JS disclosure fungerar | 4.3 |
| 5 | Keyboard navigation | 5.1 |
| 6 | Screen reader | 5.2 |
| 7 | Anchor deep-link | 4.2 |
| 8 | kpi-grid kan nestas | 3.1 |
| 9 | Smooth-expand i Chrome 129+ | 4.5 |
| 10 | Alla 5 layouts × 4 viewports | 4.1 + 4.4 |
| 11 | Action-button kräver URL+label | 2.2 |
| 12 | Inga `data-animate` | 1.4 + 1.3 |
| 13 | Inga tema-presets | 1.3 |
| 14 | Ingen `_shared/`-import | 1.3 + 1.4 |
| 15 | Tema-oberoende på TT4 | 4.1 (kör på TT4) |
| 16 | Touch-targets ≥ 44×44 | 4.4 |
| 17 | Inga font-family/letter-spacing/uppercase i defaults | 1.4 |
| 18 | Lint + build passerar | 6.3 |

---

## 8. Vad detta INTE testar

- Pixel-perfekt visuell matchning mot densiq-sustainability-2025 (codex äger den valideringen)
- Performance-budget (om CSS+JS-bundle ökar > 50KB)
- IE/legacy browsers (utanför mål-baseline)
- I18n full coverage (bara att `__()` används där det ska)
- Hover-effekter (story-card har inga, ingen test behövs)
