---
type: context-layer / patterns-reference
related: sprints/doing/sprint-report-story-card-260429.md
sources:
  - src/blocks/hero/
  - src/blocks/section-header/
  - src/blocks/kpi-grid/
created: 2026-04-30
---

# Sprint A patterns för Sprint B story-card-harmonisering

> Detta dokument extraherar konkreta arkitekturmönster från Sprint A:s tre block (`hero`, `section-header`, `kpi-grid`) som story-card SKA följa för att harmonisera med befintliga Sprint A-leveranser. Inte ett fullt 3-lagerskontext per block — det är ett **patterns-cheatsheet** för Sprint B FAS 1.
>
> För djup-kontext på `hero` specifikt, se [`src/blocks/hero/CONTEXT.md`](../../src/blocks/hero/CONTEXT.md).

---

## 1. `block.json`-struktur

### Standardiserad header

Alla Sprint A-block har:

```json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "goodblocks/{slug}",
  "title": "{Human Title}",
  "category": "goodblocks",
  "icon": "{dashicon-name}",
  "description": "{1-mening, plain svenska eller engelska}",
  "keywords": ["..."],
  "supports": { ... },
  "attributes": { ... },
  "example": { "attributes": { ... } },
  "textdomain": "goodblocks",
  "editorScript": "file:./index.js",
  "editorStyle": "file:./index.css",
  "style": "file:./style-index.css",
  "viewScript": "file:./view.js",  // valfritt — bara om frontend-JS behövs
  "render": "file:./render.php"
}
```

**Viktigt för story-card:**
- `apiVersion: 3` — alltid
- `render: file:./render.php` — server-side render, inte client-side `Save()`
- `example.attributes` — måste fyllas i för att blocket ska få en preview i block-väljaren
- `textdomain: "goodblocks"` — alltid (för i18n)

### Supports-konvention

| Block | `align` | `html` | `spacing` | `color` | `anchor` |
|---|---|---|---|---|---|
| hero | `["wide","full"]` | false | padding+margin | `gradient: true` | (saknas idag — overkill för hero) |
| section-header | `["wide","full"]` | false | padding+margin | — | — |
| kpi-grid | `["wide","full"]` | false | padding+margin | — | — |

**Story-card följer:** `align: ["wide","full"]`, `html: false`, `spacing: padding+margin`. Lägg till `anchor: true` (Locked decision från audit).

### Enum-validering

Sprint A använder `"enum"` i block.json för att begränsa string-attribut:

```json
"alignment": { "type": "string", "default": "left", "enum": ["left", "center"] },
"theme": { "type": "string", "default": "light", "enum": ["light", "dark", "accent"] }
```

**Story-card använder:** `layout` (5 värden), `theme` (3 värden), `actionTarget` (2 värden). Alla med enum-validering.

---

## 2. `render.php`-struktur

### Defensive validation pattern

Alla Sprint A-render.php börjar med samma försvarsmönster:

```php
$attribute_value = isset( $attributes['name'] ) ? (string) $attributes['name'] : 'default';
if ( ! in_array( $attribute_value, [ 'a', 'b', 'c' ], true ) ) {
    $attribute_value = 'default';  // fallback för okänt värde
}
```

Detta hanterar:
- Saknade attribut → default
- Okända/manipulerade värden → tyst fallback
- Legacy-värden (i hero: gamla animationer mappas till `none`)

**Story-card följer:** validera `layout`, `theme`, `actionTarget`. Värd:e som inte är i enum mappas till default.

### Empty-state guard

Alla Sprint A-block returnerar tomt om grundinnehåll saknas:

```php
// hero
if ( $title === '' && $kicker === '' && $lead === '' ) { return; }

// kpi-grid
if ( empty( $items ) ) { return; }
```

**Story-card följer:** returnera tomt om `title === ''` (rubrik är minimum-krav).

### Wrapper-attributes med klass-array

```php
$classes = [
    'block-base-class',
    'block-base-class--' . $theme,
    'is-aligned-' . $alignment,
];
if ( $has_kicker ) { $classes[] = 'has-kicker'; }

$wrapper_attrs = get_block_wrapper_attributes( [
    'class' => implode( ' ', $classes ),
] );
?>
<section <?php echo $wrapper_attrs; ?>>
    ...
</section>
```

**Story-card följer:** Bygg klass-array per layout. T.ex. `['story-card', 'story-card--' . $layout, 'story-card--' . $theme, 'is-aligned-...']`.

### Escaping-konvention

| Värde | Escape-funktion | Användning |
|---|---|---|
| RichText-attribut (kicker, title, lead) | `wp_kses_post()` | Tillåter länkar, formatering — bredare än nödvändigt men pre-existing pattern |
| Plain-text-attribut (KPI value, label) | `esc_html()` | Inga HTML-taggar — value/label är typografiska tecken |
| URL-attribut | `esc_url()` | (inte använd i Sprint A men förväntat i story-card för actionUrl, mediaUrl) |
| Klass-attribut | `esc_attr()` | Standard |
| Inline-style-attribut | `esc_attr()` på hela strängen | hero gör detta |

**Story-card följer:** Samma escaping. För `<details>`-content från InnerBlocks: `echo $content` med `phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped`-kommentar (Gutenberg har redan eskapat innehållet).

### `phpcs:ignore`-mönster

```php
echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
```

Inline kommentar — inte ovanför, för att fungera med PHPCS-rader.

---

## 3. CSS-variabel-konvention

### Naming pattern

`--{block-slug-utan-hero/section/kpi-prefix}-{property}`

Exempel:
- hero: `--hero-fade-distance`, `--hero-fade-duration`, `--hero-split-stagger`
- section-header: `--section-bg`, `--section-fg`, `--section-title-size`, `--section-padding-block`
- kpi-grid: `--kpi-bg`, `--kpi-fg`, `--kpi-tile-padding`, `--kpi-value-size`

**Story-card följer:** `--story-card-bg`, `--story-card-fg`, `--story-card-padding-block`, `--story-card-summary-size` etc.

### Tema-token-fallback-pattern

```scss
&.section-header--light {
    --section-bg: var(--wp--preset--color--base, #f5f5f5);
    --section-fg: var(--wp--preset--color--contrast, #111);
}

&.section-header--dark {
    --section-bg: var(--wp--preset--color--contrast, #111);
    --section-fg: var(--wp--preset--color--base, #fff);
}

&.section-header--accent {
    --section-bg: var(--wp--preset--color--accent, #e0e0e0);
    --section-fg: currentcolor;
}
```

Två lager: WP-globala tema-tokens (`--wp--preset--*`) som primär, hex som fallback. Säkerställer att blocket ser OK ut även på TwentyTwentyFour utan accent-token.

**Story-card följer:** Samma pattern för light/dark/accent.

### Mobile-first responsive

Sprint A använder två breakpoints:
- `640px` — tablet (mobile-first → 2 kolumner)
- `960px` — desktop (full layout)

```scss
@media (min-width: 640px) {
    /* tablet styles */
}

@media (min-width: 960px) {
    /* desktop styles */
}
```

**Story-card följer:** Samma breakpoints. För `split-left`/`split-right`-layouts (50/50 image+text), kolumn-grid kickar in vid 640px eller 960px (story-card 0C ska låsa).

### Inga brand-opinions i defaults

**Förbjudet i Sprint A-block:**
- `font-family` (utöver `inherit`)
- `letter-spacing` (utöver explicit `0`)
- `text-transform` (utöver explicit `none`)
- `font-weight` (utöver `inherit`)

**Tillåtet:**
- `font-size: clamp(...)` — fluid skalning är layout, inte brand
- `line-height` — typografisk rytm, inte brand
- `font-style: normal` — bara för att override:a kursiv `<cite>` etc.

**Story-card följer:** Samma regler. Inga DENSIQ-Roboto eller letter-spacing -0.02em i defaults.

### `currentcolor` (lowercase!)

Stylelint föredrar lowercase: `currentcolor`. Hela Sprint A använder lowercase-form (auto-fix gjorde detta).

---

## 4. Editor-controls organisation

### `<InspectorControls>` panel-struktur

Sprint A använder konsekvent panel-namngivning:

| Panel-titel | Innehåll |
|---|---|
| `Settings` / `Inställningar` | Tekniska defaults (animation, height, toggles) |
| `Layout` | Alignment, columns, kicker-position |
| `Style` | Theme-select, färger |
| `Tiles` (kpi-grid) / `Citat` (testimonials) | Blockspecifik repeater |

**Story-card följer:** `Layout` (layout-select) + `Style` (theme) + `Action` (URL-input + label + target) + `Anchor`-panel (om vi inte använder `supports.anchor`-built-in).

### RichText-attribut för text-fält

```js
<RichText
    tagName="h2"
    className="block-name__title"
    value={ title }
    onChange={ ( v ) => setAttributes( { title: v } ) }
    placeholder={ __( 'Title…', 'goodblocks' ) }
/>
```

För kicker/label-fält där HTML inte ska tillåtas:

```js
<RichText
    ...
    allowedFormats={ [] }
/>
```

**Story-card följer:** RichText för `title`, `excerpt`, `kicker`. `allowedFormats={ [] }` på kicker (plain-text only).

### Edit-preview matchar render.php-struktur

Sprint A:s edit.js har tre nivåer:
1. **Tom state:** "Add your first tile"-CTA (kpi-grid), placeholder-text (section-header)
2. **Mid-state:** Live-preview med riktiga klassnamn
3. **Full state:** Allt syns visuellt som på frontend

Edit.js:s rendering ska matcha render.php:s DOM-struktur så preview ≈ frontend.

**Story-card följer:** Edit-preview ska visa `<details>`-strukturen (med summary-text + open/closed-toggle), action-button, alla 5 layouts.

---

## 5. Frontend scope (view.js)

### När view.js behövs

| Block | view.js? | Anledning |
|---|---|---|
| hero | Ja | IntersectionObserver för animation, scroll-arrow click |
| section-header | **Nej** | Statiskt, ingen interaktion |
| kpi-grid | **Nej** | Statiskt, ingen interaktion |
| testimonials | Ja | Swiper-karusell, autoplay-toggle |

**Story-card behöver view.js?** Ja, men minimalt:
- Förbättra `<details>` smooth-expand med `interpolate-size` på browsers som stöder det
- Eventuellt focus-management vid open/close (a11y-pass)
- Inget annat (ingen IntersectionObserver, ingen animation utöver disclosure)

### `goodblocks-js`-class-pattern (no-JS fallback)

Hero introducerade:
```js
document.documentElement.classList.add( 'goodblocks-js' );
```

CSS gate:
```scss
:where(.goodblocks-js) &.hero-block--fade-up .hero-block__text {
    opacity: 0;  /* JS-gated initial-hidden state */
}
```

**Story-card behöver detta?** Bara om vi har CSS-rules som DÖLJER innehåll och förutsätter JS för att visa. Med native `<details>` är open/closed inbyggt — inga JS-gates behövs. **Story-card använder INTE `goodblocks-js`-pattern** (en av de saker vi besparar oss genom att välja `<details>`).

### `prefers-reduced-motion`

Hero respekterar:
```scss
@media (prefers-reduced-motion: reduce) {
    &.hero-block--fade-up .hero-block__text {
        opacity: 1;
        transform: none;
        transition: none;
    }
}
```

**Story-card följer:** smooth-expand stängs av för users med reduced-motion (snap open/close istället).

---

## 6. Build / registrering

### `webpack.config.js`-entry

```js
'blocks/{slug}/index': path.resolve(
    __dirname,
    'src/blocks/{slug}/index.js'
),
'blocks/{slug}/view': path.resolve(  // valfritt, bara om view.js finns
    __dirname,
    'src/blocks/{slug}/view.js'
),
```

**Story-card kräver båda entries** (har view.js för smooth-expand).

### `goodblocks.php` slug-array

```php
$blocks = [
    // ... existing ...
    'kpi-grid',
    'hero',
    'story-card',  // ← lägg till sist
    // 'agoodapp/*' INTE här — separat
];
```

**Story-card följer:** Lägg till i slug-loopen, INTE i agoodapp-namespacen.

### `inc/showcase.php`

Live-config-pattern:

```php
$blocks[] = [
    'slug'     => 'goodblocks/story-card',
    'help_key' => 'story-card',
    'live'     => true,
    'configs'  => [
        [
            'label' => 'Default layout',
            'attrs' => [ 'layout' => 'default', 'theme' => 'light', 'title' => 'Example', ... ],
        ],
        // 5 layout-varianter rekommenderas
    ],
];
```

**Story-card lägger till:** `5 layout-configs` i showcase. (Showcase-rendering med InnerBlocks är komplex — kan behöva `live: false` om InnerBlocks-rendering bryter style guide. Test i 0D.)

---

## 7. Sammanfattning: vad story-card SKA matcha vs. vad som är unikt

### Story-card MATCHAR Sprint A i:

- block.json-header (apiVersion 3, render:file, viewScript:file)
- supports `["wide","full"]` align, `html: false`, spacing
- Enum-validering på alla string-attribut
- Defensive empty-state och enum-fallback i render.php
- Klass-array byggd via implode för wrapper
- CSS-variabel-naming (`--story-card-*`)
- Tema-token-fallback för light/dark/accent
- 640/960px responsive breakpoints
- Inga brand-opinions i defaults (font-family, letter-spacing förbjudna)
- Lowercase `currentcolor`
- `prefers-reduced-motion`-respekt
- Webpack entry + slug-array + showcase-registrering
- Edit-preview matchar render.php DOM

### Story-card är UNIKT i:

- **InnerBlocks med template + allowedBlocks** — bara card-feature har detta i kodbasen idag (Sprint A-blocken har INGA InnerBlocks)
- **`<details>`/`<summary>`-disclosure** — inget annat block använder det
- **5 layouts via `layout`-attribut** — Sprint A har inga layout-varianter på det här sättet
- **`supports.anchor: true`** — bara masonry-query har det idag (locked decision)
- **Action-button med URL** — bara hero har knapp idag (utan URL ännu)
- **`interpolate-size` progressive enhancement** — ny CSS-feature, första användningen i kodbasen

---

## 8. Vad story-card ska INTE göra

Lessons från card-feature dark-code-audit (2026-04-30) som inte ska repeteras:

- **Ingen `data-animate`-attribut utan handler** — locked decision
- **Inga tema-presets i InnerBlocks-template** (`fontSize: 'lg'`, `textColor: 'text-muted'`) — locked decision
- **Ingen `<a>`-wrapper runt hela blocket** — locked decision
- **Inga `_shared/`-imports** — locked decision

---

## Källfiler

- [`src/blocks/hero/block.json`](../../src/blocks/hero/block.json), [`render.php`](../../src/blocks/hero/render.php), [`view.js`](../../src/blocks/hero/view.js), [`style.scss`](../../src/blocks/hero/style.scss), [`CONTEXT.md`](../../src/blocks/hero/CONTEXT.md)
- [`src/blocks/section-header/block.json`](../../src/blocks/section-header/block.json), [`render.php`](../../src/blocks/section-header/render.php), [`edit.js`](../../src/blocks/section-header/edit.js), [`style.scss`](../../src/blocks/section-header/style.scss)
- [`src/blocks/kpi-grid/block.json`](../../src/blocks/kpi-grid/block.json), [`render.php`](../../src/blocks/kpi-grid/render.php), [`edit.js`](../../src/blocks/kpi-grid/edit.js), [`style.scss`](../../src/blocks/kpi-grid/style.scss)
- [`inc/showcase.php`](../../inc/showcase.php) — för live-config-pattern
- [`webpack.config.js`](../../webpack.config.js) — för entry-pattern
- [`goodblocks.php`](../../goodblocks.php) — för slug-array-pattern
