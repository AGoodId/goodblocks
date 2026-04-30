---
type: research-note
related: sprints/backlog/sprint-report-story-card-260429.md
created: 2026-04-29
sources:
  - /Users/matsingerdal/Developer/GitHub/densiq/wp-content/plugins/densiq-asr-2025/blocks/story-module/render.php
  - /Users/matsingerdal/Developer/GitHub/densiq/wp-content/plugins/densiq-asr-2025/fields.php
---

# Research: densiq:s story-module — features, patterns och återanvändbar kod

> **Detta är ett research-dokument, inte en sprint-spec.** Det fångar vad vi vet om problemet idag så att framtida Sprint B/C/D kan planeras med fakta. Ingen kod commitas till nya block baserat på detta dokument — beslut sker först efter proof-of-concept (fas 3 i den fasade planen).

## 1. Översikt

`densiq-asr-2025/blocks/story-module/render.php` är **538 rader** + ~30 ACF-fält. Det är inte "ett block" utan ett komponentsystem som hanterar 5 layouts × 7 visual-typer × valfri body-flexible-content. Hela densiq sustainability-2025-sidan är byggd av ~20 instanser av detta block.

## 2. Feature-inventarie

### 2.1 Layouts (5)

| Värde | Beteende |
|---|---|
| `default` | Text vänster, visual höger |
| `default-reverse` | Visual vänster, text höger (CSS `layout-reverse`-klass) |
| `split-right` | Fullbleed media höger (object-fit cover, oberoende `<figure>`) |
| `split-left` | Fullbleed media vänster |
| `bg-full` | Text + visual ovanpå fullbleed bakgrundsmedia |

### 2.2 Backgrounds (3 typer)

| Typ | Implementation |
|---|---|
| `color` | Solid hex → mappad till CSS-variabel (`--asr-dark/light/accent/black/white`) via `$_asr_bg_palette`-array |
| `image` | `wp_get_attachment_image()` med klassen `.story-bg-media`, `aria-hidden="true"` |
| `video` | `<video autoplay loop muted playsinline>` med klass `.story-bg-media` |

### 2.3 Visuals (7 typer + none)

| Typ | Komplexitet | Notering |
|---|---|---|
| `none` | — | Ingen visual |
| `stat` | Liten | Prefix + STOR siffra + suffix + valfri count-up + footnote |
| `stat-grid` | Medel | 1–N tiles. Mappar 1:1 till Sprint A:s `goodblocks/kpi-grid` |
| `bar-chart` | **Hög** | y-axis-ticks, grid-lines, dashed bars, color per bar, raw-value-labels, tall-mode (320 vs 420px) |
| `before-after` | Liten | Två stora värden + pil emellan |
| `countdown` | Liten | Sekunder till en target-date (Goodblocks har redan ett countdown-block — bör jämföras) |
| `image` | Liten | `<figure>` med caption från attachment-meta |
| `video` | Liten | `<video autoplay loop muted playsinline>` inline |

### 2.4 Body-content (flexible content via ACF)

5 layouts kan staplas i godtycklig ordning:

- `paragraph` — wpautop-text
- `heading_4` — `<h4>`
- `list` — `<ul>` med items
- `body_image` — bild med caption
- `kpi_row` — KPI-strip (separata data-cells med value + label)

Plus en **alternativ wysiwyg-redigerare** (`body_editor`) som tar prio över flexible content om den är ifylld.

### 2.5 Meta + actions

- `eyebrow` — kicker (företag/geografi)
- `tags` — array av filter-taggar; renderas som `.story-tag-chip`-knappar med `data-tag`. Klick triggar globalt filter (en feature som lever i densiq-asr:s view.js, inte i blocket)
- `section` — kapitel-tillhörighet (`data-section`-attribut, läses av navigation)
- `headline` (med `<br>`-stöd via `wp_kses` whitelist)
- `ingress` (med valfri `ingress_large`-flagga)
- `Read more`-knapp (visas bara om body finns)
- `Share`-knapp — copy-to-clipboard + "Copied ✓"-feedback

### 2.6 Stable-ID-system

Densiq-koden har ett **headline-map** (rad 46–70 i render.php) som mappar specifika rubriker till stabila ID:n:

```php
'A sustainable strategy — Built into how we work.' => 'block_asr_ceo-statement',
'Year in Numbers.' => 'block_asr_year-in-numbers',
// ...
```

**Anledning:** Gutenberg strippar `id`-fältet från block-comment-JSON vid spara (känd bugg April 2026), så `$block['id']` blir random `block_<hash>` efter editor-spara. Det bryter deep-links från search-paletten och chapter-anchors.

**Implikation för goodblocks:** Om vi stöter på samma bugg behöver vi en motsvarande stabil-ID-mekanism. Men densiq:s lösning (hardcoded headline-map) skalar inte över kunder. En generisk lösning vore ett `anchorId`-attribut som redaktören sätter manuellt.

## 3. Återanvändbar kod (med rad-referenser)

### 3.1 Direkt kopierbart (rebrand-klassnamn, behåll struktur)

| Densiq-källa | Goodblocks-destination | Rader | Anmärkning |
|---|---|---|---|
| `stat-grid`-loop, render.php:179–201 | `goodblocks/kpi-grid/render.php` | ~22 | Mappar direkt till Sprint A:s spec; behöver `data-id` per tile (vi har stable id, densiq har inte) |
| `stat`-rendering, render.php:162–177 | `goodblocks/stat-counter` (Sprint C) | ~15 | Prefix + count-up + suffix + footnote |
| `bar-chart`, render.php:203–257 | `goodblocks/bar-chart` (Sprint C) | ~55 | Komplex render proven i prod — värd att lyfta |
| `before-after`, render.php:259–276 | `goodblocks/before-after` (Sprint C) | ~17 | Simple men korrekt |

### 3.2 Mönster värda att kopiera (idé, inte exakt kod)

| Mönster | Källa (rad) | Värde |
|---|---|---|
| Background-palette → CSS-variabel | render.php:112–122 | Behåller designtokens centralt — ändra `--gb-*` på ett ställe = ändra alla block |
| `<br>`-whitelist via `wp_kses` | render.php:78–81 | 3 rader. Behåller manuella radbrytningar utan att tillåta annan HTML |
| `$_lazy_attr` (preview eager, prod lazy) | render.php:12 | 1 rad. Standardmönster |
| `densiq_asr_visual_caption()` helper | helper.php (annan fil) | Värd att flytta till `goodblocks/inc/helpers.php` |

### 3.3 Patterns att lära av men INTE kopiera

| Mönster | Källa | Varför inte kopiera |
|---|---|---|
| `$block['data']`-läsning | render.php:11 | ACF-specifik — vi har flat attributes |
| `get_field()`-fallbacks | hela filen | ACF-specifika |
| `body_*_acf_fc_layout`-flex-rendering | render.php:373–422 | ACF flexible content; vi använder InnerBlocks istället |
| `$_meta_prefixes` post_meta-fallback | render.php:335–365 | Workaround för ACF + Gutenberg-bug — bara kopiera om vi hittar samma bug |
| Hardcoded `$_headline_map` | render.php:46–70 | Densiq-content-specifikt; ersätts med generiskt `anchorId`-attribut |
| Klassnamn (`.story-module`, `.asr-*`) | hela CSS:n | Behöver ny BEM scopad till `wp-block-goodblocks-*` |
| Tags som global filter-state | render.php + view.js | Densiq-specifik UX. Goodblocks story-card tar emot generiska labels men äger inte filter-systemet |

## 4. Implikationer för framtida sprintplanering

> **Notera: Inga beslut tas baserat på detta dokument. Det är observation, inte plan.**

Givet vad render.php gör är "Sprint B story-card" som ursprungligen specat **för litet**. Det vi tidigare kallade story-card är egentligen 5–6 separata komponenter:

1. **`goodblocks/story-card`** — container med 5 layouts + bg-handling + meta + actions + InnerBlocks för body
2. **`goodblocks/stat-counter`** — det som densiq kallar `visual_type: stat`
3. **`goodblocks/bar-chart`** — densiqs bar-chart med y-axis
4. **`goodblocks/before-after`** — två-värden-jämförelse
5. **`goodblocks/kpi-grid`** — Sprint A levererar redan
6. **`goodblocks/countdown`** — befintligt; ev. utöka med densiq:s sekunder-till-date-mönster

**story-card:s inner content via InnerBlocks** kan matcha densiqs body-flexible-content om vi tillåter:
- `core/heading`
- `core/paragraph`
- `core/list`
- `core/image`
- `goodblocks/kpi-grid` (KPI-strip)
- `goodblocks/stat-counter`
- `goodblocks/bar-chart`
- `goodblocks/before-after`

Det matchar densiq-claudes uppskattning på "Sprint B + C + D, 3–4 fokusveckor".

## 5. Open questions för proof-of-concept att besvara

Frågor som densiq-claude:s proof-of-concept (fas 3 i fasade planen) bör svara på innan Sprint B-spec låses:

1. **InnerBlocks vs. flexible content**: Klarar Gutenberg's InnerBlocks-template + allowedBlocks-mönster densiq:s body-content lika smidigt som ACF flexible content gjorde? Konsekvens om nej: behöver vi egen "block-flexible"-wrapper?

2. **Stable ID utan headline-map**: Räcker ett `anchorId`-attribut redaktören sätter manuellt, eller behöver vi auto-generation?

3. **Tags som filter-system**: Kommer kunder förvänta sig densiq:s globala filter-funktion, eller räcker det att story-card renderar labels som data-attribut för temat att hantera?

4. **5 layouts: nödvändiga eller överskattade?**: I proof-of-concept med 2–3 sektioner — använder vi verkligen alla 5? Kanske `default`/`reverse`/`bg-full` räcker?

5. **bg-full + visual samtidigt**: Densiqs `bg-full` har BÅDE bakgrundsmedia OCH visual i story-right. Är det avsiktligt eller en vidvinklad design?

6. **Visuals: separata block eller en nested stat-block med varianter?**: Är `goodblocks/stat-counter`, `goodblocks/bar-chart`, `goodblocks/before-after` separata block, eller är det ett `goodblocks/data-visual` med ett `type`-attribut? Densiq valde det senare; vi kan välja det förra.

## 6. Source-referens

- Render: [`densiq-asr-2025/blocks/story-module/render.php`](file:///Users/matsingerdal/Developer/GitHub/densiq/wp-content/plugins/densiq-asr-2025/blocks/story-module/render.php) (538 rader)
- Fält: [`densiq-asr-2025/fields.php`](file:///Users/matsingerdal/Developer/GitHub/densiq/wp-content/plugins/densiq-asr-2025/fields.php) (373 rader)
- Plugin-rot: [`densiq-asr-2025/densiq-asr-2025.php`](file:///Users/matsingerdal/Developer/GitHub/densiq/wp-content/plugins/densiq-asr-2025/densiq-asr-2025.php) (859 rader)
