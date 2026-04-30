---
type: lösningsspec
related: sprints/doing/sprint-report-story-card-260429.md
created: 2026-04-30
status: draft (låses innan FAS 1 startar)
---

# Sprint B: Lösningsspec — story-card

> API-kontraktet för `goodblocks/story-card`. När detta är låst kan FAS 1 starta. Alla 5 layouts byggs i Sprint B (Codex-locked decision 2026-04-30).

---

## 1. Block Attributes

| Namn | Typ | Default | Enum / Anmärkning |
|---|---|---|---|
| `layout` | string | `"default"` | `"default"` \| `"reverse"` \| `"split-left"` \| `"split-right"` \| `"bg-full"` |
| `theme` | string | `"light"` | `"light"` \| `"dark"` \| `"accent"` (matchar Sprint A-konvention) |
| `kicker` | string | `""` | RichText, plain text only (`allowedFormats={ [] }`) |
| `title` | string | `""` | RichText, sanitiseras via `wp_kses_post`. Riktmärke ≤ 200 tecken |
| `excerpt` | string | `""` | RichText. Riktmärke ≤ 300 tecken |
| `mediaId` | number | `0` | Attachment ID (för WP att hitta object), 0 = ingen media |
| `mediaUrl` | string | `""` | Attachment URL — backup om mediaId inte resolveras |
| `mediaAlt` | string | `""` | Alt-text för bild. För video används som aria-label |
| `mediaType` | string | `"image"` | `"image"` \| `"video"`. Bestämmer `<img>` vs `<video>` |
| `actionUrl` | string | `""` | URL för action-knapp. Tom → ingen knapp renderas |
| `actionLabel` | string | `""` | Knapptext. Tom + actionUrl satt → ingen knapp (båda krävs) |
| `actionTarget` | string | `"_self"` | `"_self"` \| `"_blank"` (lägger till `rel="noopener noreferrer"` om `_blank`) |
| `labels` | array | `[]` | string-array av generiska facets, ex. `["climate", "operations"]`. Renderas som chips, **ingen filterlogik** |
| `summaryLabel` | string | `""` | Disclosure-knappens text. Default vid render: `__( 'Read more', 'goodblocks' )` |
| `openByDefault` | boolean | `false` | `<details open>` vid render — för stories som ska visa hela innehållet direkt |

**Borttaget från tidig spec (Codex-locked decision):** `anchorId` ersätts av `supports.anchor: true` (Gutenberg-konvention).

---

## 2. InnerBlocks Contract

### `allowedBlocks` (låst lista)

```js
const ALLOWED_BLOCKS = [
    'core/heading',
    'core/paragraph',
    'core/list',
    'core/image',
    'core/quote',
    'goodblocks/kpi-grid',  // Sprint A-block kan nestas in
];
```

### `template` (utan tema-presets)

```js
const TEMPLATE = [
    [ 'core/heading', { level: 3, placeholder: __( 'Body heading…', 'goodblocks' ) } ],
    [ 'core/paragraph', { placeholder: __( 'Body content goes here…', 'goodblocks' ) } ],
];
```

**Locked decision:** Inga `fontSize`, `textColor`, `customClassName` eller andra tema-presets i template.

### `templateLock`

```js
templateLock={ false }  // redaktör kan ta bort, lägga till och omordna inner blocks
```

### Save vs render

**`save.js` (NY för kodbasen — story-card pionjärar mönstret):**

```js
import { InnerBlocks } from '@wordpress/block-editor';

const Save = () => <InnerBlocks.Content />;

export default Save;
```

`<InnerBlocks.Content />` säkerställer att inner blocks **persistas till `post_content`**. Utan detta sparas ingenting.

**`render.php`:**

`$content` (3:e parametern WordPress skickar till render-callback) innehåller **redan parsade inner blocks som HTML-sträng**. Echo:as inom `<details>`-body:

```php
<?php if ( $content ) : ?>
    <details class="story-card__disclosure" <?php echo $open_by_default ? 'open' : ''; ?>>
        <summary class="story-card__summary">
            <span class="story-card__summary-label"><?php echo esc_html( $summary_label ); ?></span>
        </summary>
        <div class="story-card__body">
            <?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
    </details>
<?php endif; ?>
```

`phpcs:ignore` motiveras av att Gutenberg redan eskapat innehåll i `$content` — ingen dubbel-escape behövs.

---

## 3. DOM Contract

### Single canonical structure (alla 5 layouts använder denna)

```html
<article class="wp-block-goodblocks-story-card story-card story-card--{layout} story-card--{theme} {extra-classes}" id="{anchor}">

    {if mediaUrl AND layout === 'bg-full'}
    <div class="story-card__bg" aria-hidden="true">
        <img class="story-card__bg-media" src="..." alt="" loading="lazy"/>
        <!-- eller <video> beroende på mediaType -->
    </div>
    {/if}

    <div class="story-card__inner">

        <div class="story-card__text">

            <header class="story-card__header">
                {if kicker}
                <span class="story-card__kicker">{kicker}</span>
                {/if}

                {if title}
                <h3 class="story-card__title">{title}</h3>
                {/if}

                {if excerpt}
                <p class="story-card__excerpt">{excerpt}</p>
                {/if}
            </header>

            {if labels.length > 0}
            <ul class="story-card__labels">
                {foreach labels as label}
                <li class="story-card__label">{label}</li>
                {/foreach}
            </ul>
            {/if}

            {if actionUrl AND actionLabel}
            <div class="story-card__actions">
                <a class="story-card__action"
                   href="{actionUrl}"
                   target="{actionTarget}"
                   {if actionTarget === '_blank'}rel="noopener noreferrer"{/if}>
                    {actionLabel}
                </a>
            </div>
            {/if}

            {if $content}
            <details class="story-card__disclosure" {if openByDefault}open{/if}>
                <summary class="story-card__summary">
                    <span class="story-card__summary-label">{summaryLabel || 'Read more'}</span>
                </summary>
                <div class="story-card__body">
                    <?php echo $content; ?>
                </div>
            </details>
            {/if}

        </div>

        {if mediaUrl AND layout !== 'bg-full'}
        <figure class="story-card__media">
            <img class="story-card__media-element" src="..." alt="..." loading="lazy"/>
            <!-- eller <video autoplay muted loop playsinline> beroende på mediaType -->
        </figure>
        {/if}

    </div>
</article>
```

### Element-konvention

| Element | Klass | Anmärkning |
|---|---|---|
| `<article>` | `.story-card` (+ modifiers) | Wrapper. Inte `<a>`-wrapper (locked decision). |
| `.story-card__bg` | (bara bg-full) | Absolut-positionerad bakgrundsmedia |
| `.story-card__inner` | (alltid) | Layout-container — grid/flex per layout |
| `.story-card__text` | (alltid) | Textinnehåll — kicker, title, excerpt, labels, actions, disclosure |
| `.story-card__media` | (default/reverse/split-*) | `<figure>` med media. Inte i bg-full |
| `.story-card__header` | (alltid om text) | Semantisk gruppering av kicker+title+excerpt |
| `.story-card__kicker` | (om kicker) | Liten text/nummer ovanför title |
| `.story-card__title` | (om title) | `<h3>` (level 3 — kan justeras via tema om så önskas) |
| `.story-card__excerpt` | (om excerpt) | `<p>` med ingress |
| `.story-card__labels` | (om labels) | `<ul>` med label-chips |
| `.story-card__label` | per label | `<li>` med text. Inga klick-handlers (locked: ingen filter-state) |
| `.story-card__actions` | (om action) | Container för action-knapp |
| `.story-card__action` | (om action) | `<a>` med href + target. Aldrig wrappar hela kortet |
| `.story-card__disclosure` | (om $content) | `<details>` |
| `.story-card__summary` | (om disclosure) | `<summary>`-knapp som triggar open/close |
| `.story-card__summary-label` | i summary | `<span>` med "Read more"-text — låter CSS styla pseudo-elements separat |
| `.story-card__body` | i details | `<div>` runt `$content` — ger CSS handle för animationer |

---

## 4. Anchor Contract

### `supports.anchor: true`

Lägg till i block.json:
```json
"supports": {
    "anchor": true,
    ...
}
```

### Vad detta ger gratis

- Inspector-UI under "Avancerat"-panel: "HTML-ankare"-fält
- Gutenberg sätter `id`-attribut på blocket vid spara
- Editor-experience matchar resten av WP

### `render.php`-konsekvens

`get_block_wrapper_attributes()` lägger automatiskt till `id="{anchor-value}"` om redaktör satt ankare. **Inget extra arbete krävs.**

```php
$wrapper_attrs = get_block_wrapper_attributes( [
    'class' => implode( ' ', $classes ),
] );
// $wrapper_attrs innehåller redan id="..." om anchor är satt
?>
<article <?php echo $wrapper_attrs; ?>>
```

### Locked decisions

- ✅ `supports.anchor: true`
- ❌ Ingen custom `anchorId`-attribut i MVP
- ❌ Ingen auto-generation från headline (densiq:s headline-map-pattern)
- ✅ Deep-links fungerar via standard WP-mechanism: `https://site.com/page/#story-anchor`

---

## 5. Layout Contract

### 5 layouts via CSS, samma DOM

Alla 5 layouts använder samma DOM-struktur (sektion 3). CSS-skillnader:

#### `default` — text vänster, media höger (~50/50 desktop)

```scss
.story-card--default {
    .story-card__inner {
        display: grid;
        grid-template-columns: 1fr;  // mobile-first
        gap: var(--story-card-gap);

        @media (min-width: 960px) {
            grid-template-columns: 1fr 1fr;
        }
    }
    // text first, media second i DOM-ordning
}
```

#### `reverse` — media vänster, text höger (CSS-flip)

```scss
.story-card--reverse {
    .story-card__inner {
        display: grid;
        grid-template-columns: 1fr;

        @media (min-width: 960px) {
            grid-template-columns: 1fr 1fr;

            .story-card__text { order: 2; }
            .story-card__media { order: 1; }
        }
    }
}
```

På mobil: text först alltid (läsbarhetsval). Endast desktop flippar.

#### `split-left` — fullbleed media vänster

```scss
.story-card--split-left {
    .story-card__inner {
        display: grid;
        grid-template-columns: 1fr;

        @media (min-width: 768px) {
            grid-template-columns: 1fr 1fr;

            .story-card__media {
                order: 1;
                margin: 0;
                aspect-ratio: 1 / 1;  // eller behållen ratio
            }

            .story-card__text {
                order: 2;
                padding: var(--story-card-text-padding);
            }
        }
    }

    .story-card__media-element {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
}
```

#### `split-right` — fullbleed media höger

Identisk med `split-left` förutom `order`-värdena (text:1, media:2).

```scss
.story-card--split-right {
    .story-card__inner {
        @media (min-width: 768px) {
            grid-template-columns: 1fr 1fr;
            // default DOM order — inga overrides
        }
    }
    // resten samma som split-left
}
```

**DRY-mönster:** split-left och split-right delar bas-styles via `.story-card[class*="split-"]` eller mixin/`@extend`.

#### `bg-full` — text på fullbleed bakgrundsmedia

```scss
.story-card--bg-full {
    position: relative;
    overflow: hidden;
    min-height: clamp(400px, 60vh, 720px);

    .story-card__bg {
        position: absolute;
        inset: 0;
        z-index: 0;
    }

    .story-card__bg-media {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .story-card__inner {
        position: relative;
        z-index: 1;
        // ev. backdrop för läsbarhet
        background: linear-gradient(to top, rgba(0,0,0,0.5), rgba(0,0,0,0));
        padding: var(--story-card-bg-padding);
    }

    // För bg-full kan story-card__media saknas (det är __bg som visar mediat)
}
```

### Mobile-first responsive breakpoints (Sprint A-konvention)

| Breakpoint | Beteende |
|---|---|
| `< 640px` | Alla layouts: 1 kolumn, text först, media efteråt (inga split-fullbleed) |
| `≥ 640px` | split-left/right kan brytas upp till 2 kolumner |
| `≥ 960px` | default/reverse bryts till 2 kolumner |

Specifika brytpunkter kan justeras under FAS 1 om visuell test visar problem.

### Action-button placering per layout

Action-button + labels ligger ALLTID i `.story-card__text` (inom `.story-card__inner`). **Layout påverkar inte action-position.** Det betyder att split-* har action-knapp på text-sidan (inte fullbleed). Detta är medvetet — knappen ska vara läsbar, inte gömd bakom fullbleed-media.

---

## 6. View.js Contract — INGEN i MVP

### Beslut

Story-card MVP har **inget `viewScript`** i block.json. Frontend-interaktivitet hanteras helt av native HTML + CSS:

| Behov | Lösning utan view.js |
|---|---|
| Toggle open/closed | Native `<details>` |
| Keyboard navigation | Native `<summary>` |
| Screen reader state | Native `<details>` semantics |
| Smooth-expand-animation | `interpolate-size: allow-keywords` (CSS) |
| Anchor scrollIntoView | WP-core hanterar fragment-link |
| Focus management | Native `<summary>` är fokuserbart |

### CSS-progressive-enhancement för smooth-expand

```scss
/* Stöd-detection via @supports */
@supports (interpolate-size: allow-keywords) {
    :root {
        interpolate-size: allow-keywords;
    }

    .story-card__disclosure {
        .story-card__body {
            block-size: 0;
            overflow: hidden;
            transition: block-size 0.4s ease-out;
        }

        &[open] .story-card__body {
            block-size: auto;
        }
    }
}

/* prefers-reduced-motion override */
@media (prefers-reduced-motion: reduce) {
    .story-card__disclosure .story-card__body {
        transition: none;
    }
}
```

### Fallback-beteende (browsers utan `interpolate-size`)

- Disclosure öppnas/stängs **instant** (native `<details>` default-beteende)
- Inget `block-size: 0`-state — innehåll bara visible/hidden via native
- Acceptabelt enligt Sprint B locked decision (a11y > animation)

### Notering om eventuell framtida view.js

Om FAS 1 eller FAS 2 visar att vi behöver view.js (t.ex. för analytics-event på open, eller bug-workaround), kan vi lägga till den utan att bryta MVP-arkitekturen. Bara webpack-entry + viewScript i block.json behöver tillkomma.

---

## 7. Acceptance Criteria

För FAS 2C — alla måste vara verifierade innan sprint stängs:

1. **Block syns i editor** under "GoodBlocks"-kategori
2. **Editor sparar och laddar InnerBlocks korrekt** — efter spara + reload har inner blocks bevarats
3. **Frontend renderar InnerBlocks via `$content`** — inner blocks-HTML hamnar inom `<details>`-body
4. **No-JS disclosure fungerar** — med JS avstängt kan användare öppna/stänga via `<details>`/`<summary>`
5. **Keyboard navigation fungerar** — Tab till summary, Space/Enter togglar, Tab in i innehållet
6. **Screen reader läser korrekt state** — VoiceOver/NVDA hör "expanded"/"collapsed" via native semantik
7. **Anchor deep-link fungerar** — URL `#anchor-value` scrollar till och ev. öppnar story-card
8. **`goodblocks/kpi-grid` kan nestas** som inner block och renderas korrekt
9. **Smooth-expand i Chrome/Edge 129+** via `interpolate-size`; instant fallback i andra browsers
10. **Alla 5 layouts renderas korrekt** i 320 / 768 / 1024 / 1440px viewports
11. **Action-button (URL + label) renderas bara när båda är satta** — annars ingen knapp
12. **Inga `data-animate`-attribut** i frontend-HTML
13. **Inga tema-presets i block.json eller InnerBlocks-template**
14. **Ingen `_shared/`-import** i story-card-filerna
15. **Tema-oberoende verifierat på TwentyTwentyFour** — block ser rimligt ut utan agoodsite-fse-tema
16. **Touch-targets ≥ 44×44px på mobil** för summary, action-button, externa länkar
17. **Inga hårdkodade `font-family`, `letter-spacing`, `text-transform: uppercase`** i defaults
18. **`npm run lint:js` + `npm run build` passerar utan nya errors**

---

## 8. Filstruktur

```
src/blocks/story-card/
├── block.json       (~60 rader)
├── index.js         (~12 rader, registerBlockType med edit + save)
├── save.js          (~5 rader, NY pattern: <InnerBlocks.Content />)
├── edit.js          (~250 rader, full editor-UI med 5 layouts + InspectorControls + InnerBlocks)
├── render.php       (~120 rader, escape + classes + DOM + <details> + $content echo)
└── style.scss       (~250 rader, 5 layouts × responsive + 3 teman + interpolate-size + a11y)
```

**Inga filer:**
- `view.js` (MVP-beslut)
- `editor.scss` (editor-overrides läggs i style.scss om nödvändigt)

---

## 9. CSS-variabler (slutgiltig lista)

```scss
.wp-block-goodblocks-story-card {
    // Container
    --story-card-bg: transparent;
    --story-card-fg: currentcolor;
    --story-card-padding-block: clamp(2rem, 5vw, 4rem);
    --story-card-padding-inline: clamp(1rem, 4vw, 3rem);
    --story-card-gap: clamp(1.5rem, 4vw, 3rem);

    // Text
    --story-card-text-padding: clamp(1rem, 4vw, 3rem);  // för split-* layouts
    --story-card-stack-gap: clamp(1rem, 2vw, 1.5rem);

    // Typography sizes (no font-family, no letter-spacing)
    --story-card-kicker-size: clamp(0.75rem, 0.5vw + 0.625rem, 0.875rem);
    --story-card-title-size: clamp(1.5rem, 3vw + 1rem, 2.5rem);
    --story-card-title-line-height: 1.2;
    --story-card-excerpt-size: clamp(1rem, 0.5vw + 0.875rem, 1.125rem);
    --story-card-summary-size: 0.95rem;
    --story-card-label-size: 0.75rem;

    // Disclosure
    --story-card-disclosure-padding: 1rem;
    --story-card-summary-bg: transparent;
    --story-card-summary-border: 1px solid color-mix(in srgb, currentcolor 25%, transparent);

    // Action button
    --story-card-action-padding: 0.75rem 1.5rem;
    --story-card-action-bg: var(--story-card-fg);
    --story-card-action-fg: var(--story-card-bg);
    --story-card-action-border-radius: 2px;

    // bg-full
    --story-card-bg-overlay: linear-gradient(to top, rgba(0,0,0,0.55), rgba(0,0,0,0.1));
    --story-card-bg-padding: clamp(2rem, 6vw, 5rem);

    // Animation
    --story-card-disclosure-duration: 0.4s;
    --story-card-disclosure-easing: ease-out;
}

// Tema-varianter (samma pattern som section-header / kpi-grid)
.story-card--light  { --story-card-bg: var(--wp--preset--color--base, #f5f5f5); --story-card-fg: var(--wp--preset--color--contrast, #111); }
.story-card--dark   { --story-card-bg: var(--wp--preset--color--contrast, #111); --story-card-fg: var(--wp--preset--color--base, #fff); }
.story-card--accent { --story-card-bg: var(--wp--preset--color--accent, #e0e0e0); --story-card-fg: currentcolor; }
```

---

## 10. Icke-mål (out of scope)

- ❌ Web Share API / share-knapp utöver generisk action-button
- ❌ `<details>` med animation som inte använder `interpolate-size` (ingen polyfill)
- ❌ Anchor auto-generation från title (densiq:s headline-map)
- ❌ Visuella kompositioner som kräver `goodblocks/stat-counter`, `goodblocks/bar-chart`, `goodblocks/before-after`, `goodblocks/countdown-extended` (Sprint C/D)
- ❌ Filter-state för labels (kunder bygger eget JS om de behöver)
- ❌ Multi-media-gallerier (en media per story-card)
- ❌ Stacked stories i grid-layout (det är temat eller core/group som äger)

---

## 11. Godkännande

- [ ] Mats godkänner alla 15 attribut + defaults
- [ ] Mats godkänner DOM-struktur + klassnamn-konvention
- [ ] Mats godkänner 5 layout-CSS-strategi (single DOM, CSS-grid + order)
- [ ] Mats godkänner ingen view.js i MVP
- [ ] Codex/densiq-claude bekräftar att schemat täcker alla story-mönster på sustainability-2025
- [ ] Pre-mortem (0E) körd och inga blockerande risker

När alla kryss är gröna → låst kontrakt, FAS 1 kan starta.
