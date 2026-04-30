---
type: lösningsspec
related: sprints/doing/sprint-report-foundation-blocks-260429.md
created: 2026-04-29
status: draft (låses innan FAS 1 startar)
---

# Sprint A: Lösningsspec — report-foundation-blocks

> Detta dokument låser **block.json-attribut, HTML-struktur, CSS-klasser och defaults** för Sprint A:s tre block. Det är API-kontraktet — när detta är låst kan densiq-claude börja generera Gutenberg-blockmarkup parallellt med implementationen.

**Gemensamma regler (gäller alla tre block):**

- `block.json`: `apiVersion: 3`, server-render via `render: file:./render.php`
- CSS scopas under `.wp-block-goodblocks-{slug}` (WP genererar wrapper-klassen)
- Inga hårdkodade `font-family` eller `letter-spacing` — temat äger typografin
- Mobile-first: 1-kolumn på `< 640px`, bryter upp på större skärmar
- Touch-targets ≥ 44×44px på mobil
- CSS-variabler med rimliga fallback-värden så blocket ser OK ut på TwentyTwentyFour utan tema-overrides
- Använder `clamp()` för fluid storlek där så är meningsfullt
- **Inga långa body-textfält i attribut.** Korta intro/subheading-fält (`hero.text`, `section-header.lead`) bör vara ≤ ~300 tecken som riktmärke. Body-content går alltid i InnerBlocks (mönster för Sprint B:s `story-card`)

---

## 1. `goodblocks/hero` (utökas — post-PURGE + nya animationer)

### Attribut

| Namn | Typ | Default | Anmärkning |
|---|---|---|---|
| `animation` | enum | `"none"` | `"none"` \| `"fade-up"` \| `"split-words"`. Legacy-värden mappas tyst till `none` |
| `height` | string | `"100svh"` | CSS height-värde. `svh` är medvetet val (D3 i CONTEXT.md) |
| `rubrik` | string | `""` | RichText, sanitiseras via `wp_kses_post` |
| `text` | string | `""` | RichText, sanitiseras via `wp_kses_post`. Riktmärke: ≤ 300 tecken |
| `button` | string | `""` | Knapptext (RichText). Tom = ingen knapp |
| `buttonUrl` | string | `""` | URL för knapp. Sätts → renderas som `<a>`. Tom + `button` satt → `<button type="button">` (default; sällan rätt val) |
| `buttonTarget` | string | `"_self"` | `"_self"` \| `"_blank"`. Påverkar bara när `buttonUrl` är satt |
| `backgroundMedia` | object\|null | `null` | `{ id, url, type: "image"\|"video", mime, alt }` |
| `dimRatio` | number | `0` | 0–100, overlay-opacitet |
| `overlayColor` | string | `"#000000"` | Hex-färg för overlay |
| `contentPosition` | string | `"center center"` | BlockAlignmentMatrixControl-värde |
| `reverseFlow` | boolean | `false` | Byter ordning på rubrik/text |
| `scrollArrow` | boolean | `false` | Visar klickbar scroll-indikator |

**Borttagna (PURGE):** `backgroundType`, `positionClass`

### Render-struktur (HTML)

```html
<div class="wp-block-goodblocks-hero hero-block hero-block--{animation}"
     style="height: {height};">

  {if backgroundMedia.type === "video"}
    <video class="hero-block__video" autoplay muted loop playsinline>
      <source src="{backgroundMedia.url}" type="{backgroundMedia.mime}" />
    </video>
  {/if}

  <div class="hero-block__overlay"
       style="background-color: {overlayColor}; opacity: {dimRatio/100};"></div>

  <div class="hero-block__content {position-class}">
    <div class="hero-block__container">
      <div class="hero-block__text {reverseFlow ? 'reverse-flow' : ''}">
        {if rubrik}
          <h2>{rubrik}</h2>  <!-- för split-words: JS splittrar i spans efter mount -->
        {/if}
        {if text}
          <p>{text}</p>
        {/if}
      </div>
      {if button && buttonUrl}
        <a class="btn btn-large" href="{buttonUrl}"
           {buttonTarget === "_blank" ? 'target="_blank" rel="noopener noreferrer"' : ''}>
          <span>{button}</span>
        </a>
      {else if button}
        <button type="button" class="btn btn-large">
          <span>{button}</span>
        </button>
      {/if}
    </div>
  </div>

  {if scrollArrow}
    <button type="button" class="hero-block__scroll-arrow" aria-label="Scroll down">
      <svg viewBox="0 -960 960 960" width="24" height="24" fill="currentColor">
        <path d="M440-800v487L216-537l-56 57 320 320 320-320-56-57-224 224v-487h-80Z" />
      </svg>
    </button>
  {/if}

</div>
```

**Ändringar mot dagens render.php:**
- `<button type="button">` istället för `<a>` (matchar editor och frontend)
- `<button>` istället för `<span>` för scroll-arrow (klickbar + tangentbord + smooth-scroll)
- `position-class` deriverad direkt från `contentPosition` (ej cached)
- Bakgrundsbild via inline-style sätts från `backgroundMedia.url` om `type === "image"`

### CSS-klasser

- `.hero-block--none` — ingen animation
- `.hero-block--fade-up` — text fade-up vid IntersectionObserver-trigger
- `.hero-block--split-words` — text splittras i spans, varje ord fade-up med stagger-delay
- `.is-in-view` — JS-applicerad klass när IntersectionObserver triggar (CSS animationer gated på denna)
- Position-klasser från `src/shared.js`: `.is-position-{vertical}-{horizontal}` (9 varianter)

### CSS-variabler

```css
.wp-block-goodblocks-hero {
  --hero-overlay-color: var(--wp--preset--color--background, #000);
  --hero-fade-distance: 1.5rem;
  --hero-fade-duration: 0.6s;
  --hero-split-stagger: 0.05s;  /* delay per ord */
}
```

### Editor-controls (InspectorControls)

| Panel | Control | Attribut |
|---|---|---|
| Settings | SelectControl: Animation | `animation` |
| Settings | UnitControl: Height | `height` |
| Settings | ToggleControl: Reverse text order | `reverseFlow` |
| Settings | ToggleControl: Show scroll arrow | `scrollArrow` |
| Settings | MediaUpload: Background media | `backgroundMedia` |
| Settings | URLInput: Button link | `buttonUrl` |
| Settings | SelectControl: Button target | `buttonTarget` |
| Color | RangeControl: Overlay opacity | `dimRatio` |
| Color | ColorPalette: Overlay color | `overlayColor` |
| BlockControls | BlockAlignmentMatrixControl: Content position | `contentPosition` |

---

## 2. `goodblocks/section-header` (nytt)

### Attribut

| Namn | Typ | Default | Anmärkning |
|---|---|---|---|
| `kicker` | string | `""` | Liten text/nummer ovanför titel (ex: "01" eller "Strategy") |
| `title` | string | `""` | Display-rubrik (RichText) |
| `lead` | string | `""` | Intro-paragraf under titel (RichText) |
| `alignment` | enum | `"left"` | `"left"` \| `"center"` |
| `numberPosition` | enum | `"none"` | `"before"` \| `"after"` \| `"none"`. Bestämmer om kicker visas ovanför eller efter titel |
| `theme` | enum | `"light"` | `"light"` \| `"dark"` \| `"accent"`. Mappar till CSS-variabler |

### Render-struktur (HTML)

```html
<section class="wp-block-goodblocks-section-header section-header
                section-header--{theme} is-aligned-{alignment}
                {kicker && numberPosition !== 'none' ? 'has-kicker' : ''}
                {kicker && numberPosition === 'after' ? 'has-kicker--after' : ''}">
  <div class="section-header__inner">
    {if kicker && numberPosition === "before"}
      <span class="section-header__kicker">{kicker}</span>
    {/if}
    {if title}
      <h2 class="section-header__title">{title}</h2>
    {/if}
    {if kicker && numberPosition === "after"}
      <span class="section-header__kicker section-header__kicker--after">{kicker}</span>
    {/if}
    {if lead}
      <p class="section-header__lead">{lead}</p>
    {/if}
  </div>
</section>
```

### CSS-variabler (alla med fallback-värden)

```css
.wp-block-goodblocks-section-header {
  --section-bg: transparent;
  --section-fg: currentColor;
  --section-title-size: clamp(2rem, 5vw + 1rem, 4.5rem);
  --section-title-line-height: 1.1;
  --section-kicker-size: clamp(0.875rem, 1vw + 0.5rem, 1rem);
  --section-lead-size: clamp(1rem, 0.5vw + 0.875rem, 1.25rem);
  --section-padding-block: clamp(3rem, 8vw, 6rem);
  --section-padding-inline: clamp(1rem, 4vw, 3rem);
  --section-content-max-width: 60rem;
  --section-stack-gap: clamp(1rem, 2vw, 1.5rem);
}

/* Tema-varianter — kunder överrider via dessa eller via globala WP-tokens */
.section-header--light  { --section-bg: var(--wp--preset--color--base, #f5f5f5); --section-fg: var(--wp--preset--color--contrast, #111); }
.section-header--dark   { --section-bg: var(--wp--preset--color--contrast, #111); --section-fg: var(--wp--preset--color--base, #fff); }
.section-header--accent { --section-bg: var(--wp--preset--color--accent, #e0e0e0); --section-fg: currentColor; }
```

**Inga `font-family` eller `letter-spacing` i defaults.**

### Layout-regler

- `.is-aligned-left` — text-align: left, kicker + title + lead vänsterställda
- `.is-aligned-center` — text-align: center, allt centrerat
- `.has-kicker--after` — kicker hamnar EFTER `<h2>` i visuell ordning (men semantisk markup behåller den efter title för att matcha — eller använd flexbox `order` om markup-ordning behöver vara konsistent)

### Editor-controls

| Panel | Control | Attribut |
|---|---|---|
| Inline | RichText: kicker | `kicker` |
| Inline | RichText: title | `title` |
| Inline | RichText: lead | `lead` |
| Inspector → Settings | SelectControl: Alignment | `alignment` |
| Inspector → Settings | SelectControl: Kicker position | `numberPosition` |
| Inspector → Style | SelectControl: Theme | `theme` |

---

## 3. `goodblocks/kpi-grid` (nytt)

### Attribut

| Namn | Typ | Default | Anmärkning |
|---|---|---|---|
| `items` | array | `[ { id: "tile-1", value: "", label: "" } ]` | 1–6 items. Varje item: `{ id, value, label, prefix?, suffix? }` |
| `columns` | enum | `"auto"` | `"auto"` \| `"2"` \| `"3"` \| `"4"` \| `"5"` \| `"6"`. `auto` = items.length |
| `theme` | enum | `"light"` | `"light"` \| `"dark"` \| `"accent"` (matchar section-header) |

### Items-schema (per tile)

```ts
{
  id: string,        // stabil id, genereras automatiskt vid add. Används för React-keys + reorder
  value: string,     // huvudvärde, ex: "71", "5 yrs", "€250", "↑"
  label: string,     // beskrivning, ex: "TARGET REACHED", "AHEAD OF SCHEDULE"
  prefix?: string,   // valfritt, ex: "−", "≈", "€"
  suffix?: string    // valfritt, ex: "%", "t", "yrs"
}
```

`prefix` och `suffix` är separata från `value` för att kunna stilas oberoende (t.ex. mindre storlek på %-tecken).

### Render-struktur (HTML)

```html
<section class="wp-block-goodblocks-kpi-grid kpi-grid
                kpi-grid--{theme} kpi-grid--cols-{columns_resolved}">
  <div class="kpi-grid__inner">
    {foreach items as item}
      <div class="kpi-grid__tile" data-id="{item.id}">
        <div class="kpi-grid__value">
          {if item.prefix}
            <span class="kpi-grid__prefix">{item.prefix}</span>
          {/if}
          <span class="kpi-grid__number">{item.value}</span>
          {if item.suffix}
            <span class="kpi-grid__suffix">{item.suffix}</span>
          {/if}
        </div>
        {if item.label}
          <div class="kpi-grid__label">{item.label}</div>
        {/if}
      </div>
    {/foreach}
  </div>
</section>
```

`columns_resolved` = `columns === "auto" ? items.length : columns` (capped at 6).

### CSS-variabler

```css
.wp-block-goodblocks-kpi-grid {
  --kpi-bg: transparent;
  --kpi-fg: currentColor;
  --kpi-tile-bg: transparent;
  --kpi-tile-fg: currentColor;
  --kpi-tile-border: 1px solid color-mix(in srgb, currentColor 20%, transparent);
  --kpi-tile-padding: clamp(1rem, 3vw, 2rem);
  --kpi-tile-gap: clamp(0.5rem, 2vw, 1rem);
  --kpi-grid-gap: clamp(0.5rem, 1.5vw, 1rem);
  --kpi-value-size: clamp(2rem, 5vw, 3.5rem);
  --kpi-value-line-height: 1;
  --kpi-affix-size: 0.6em;  /* prefix/suffix relativt value */
  --kpi-label-size: clamp(0.75rem, 0.5vw + 0.625rem, 0.875rem);
  --kpi-label-letter-spacing: 0;  /* explicit 0 — temat äger ev. tracking */
  --kpi-label-text-transform: none;  /* temat överrider om de vill ha uppercase */
}
```

**Inga `font-family`, ingen `letter-spacing`** i defaults.

### Responsiv layout

```css
.kpi-grid__inner {
  display: grid;
  grid-template-columns: 1fr;  /* mobile-first: 1 kolumn */
  gap: var(--kpi-grid-gap);
}

@media (min-width: 640px) {
  .kpi-grid--cols-2 .kpi-grid__inner { grid-template-columns: repeat(2, 1fr); }
  .kpi-grid--cols-3 .kpi-grid__inner { grid-template-columns: repeat(2, 1fr); }  /* 3 cols fall back to 2 on tablet */
  .kpi-grid--cols-4 .kpi-grid__inner { grid-template-columns: repeat(2, 1fr); }
  .kpi-grid--cols-5 .kpi-grid__inner { grid-template-columns: repeat(2, 1fr); }
  .kpi-grid--cols-6 .kpi-grid__inner { grid-template-columns: repeat(3, 1fr); }
}

@media (min-width: 960px) {
  .kpi-grid--cols-2 .kpi-grid__inner { grid-template-columns: repeat(2, 1fr); }
  .kpi-grid--cols-3 .kpi-grid__inner { grid-template-columns: repeat(3, 1fr); }
  .kpi-grid--cols-4 .kpi-grid__inner { grid-template-columns: repeat(4, 1fr); }
  .kpi-grid--cols-5 .kpi-grid__inner { grid-template-columns: repeat(5, 1fr); }
  .kpi-grid--cols-6 .kpi-grid__inner { grid-template-columns: repeat(6, 1fr); }
}
```

### Editor-controls (InspectorControls)

| Panel | Control | Attribut |
|---|---|---|
| Tiles | Repeater (per tile): TextControl value/label/prefix/suffix + delete-button | `items` |
| Tiles | Button: + Lägg till tile (max 6) | — |
| Tiles | Drag-handles för reorder (använder stabil `id`) | — |
| Settings | SelectControl: Columns (auto/2/3/4/5/6) | `columns` |
| Style | SelectControl: Theme | `theme` |

### Edit-preview

Editorn visar tiles i en grid som matchar slutgiltig CSS. Tom item visar placeholder ("Value", "Label").

---

## 4. Visuella exempel (target — densiq-sidan)

Från `densiqgroup.com/sustainability-2025/`:

| Sektion | Block-mappning |
|---|---|
| "ANNUAL SUSTAINABILITY REPORT 2025" + cinematic video + "THE PLANET CAN'T WAIT. NEITHER CAN WE." | `goodblocks/hero` med `animation: "split-words"`, `scrollArrow: true`, video-bakgrund |
| "A sustainable strategy — Built into how we work" | `goodblocks/section-header` med `theme: "dark"`, `alignment: "left"` |
| "Key sustainability facts and figures for DENSIQ Group 2025" på grön bakgrund | `goodblocks/section-header` med `theme: "accent"`, `alignment: "left"`, `kicker`-fältet tomt |
| "Materiality Assessment & Stakeholder Engagement" | `goodblocks/section-header` med `theme: "accent"` |
| "2 / 5 / ↑" tiles (Lost Time Incidents / Total Injuries / Near Misses) | `goodblocks/kpi-grid` med 3 items, `columns: "auto"`, `theme: "accent"` |
| "−71% / 2025 / 5 yrs" tiles | `goodblocks/kpi-grid` med 3 items, prefix `"−"` på första, `suffix: "%"` på första, `suffix: "yrs"` på sista |
| "30% / 19% / −39%" HVO-tiles | `goodblocks/kpi-grid`, items med `suffix: "%"` |
| "€250 / ~60% / Immed." tiles | `goodblocks/kpi-grid`, prefix/suffix kombinerade |
| "1 444 t / −20% / 1 115 t" Scope 3-tiles | `goodblocks/kpi-grid` |
| "Clear targets. Quarterly accountability." på mörk bg | `goodblocks/section-header` med `theme: "dark"` |

---

## 5. Migration-anteckningar

- **Hero**: graceful degradation enligt PURGE sub-spec. Inga DB-migrationer.
- **Section-header / KPI-grid**: nya block, ingen migration behövs.
- **Befintliga densiq-sektioner som idag är `core/cover` / `core/columns`**: konverteras manuellt eller via densiq-claude:s blockmarkup-generator efter v1.12.0.

---

## 6. Vad som INTE specas här

- **Implementation av animationer** — JS-detaljer för IntersectionObserver, fade-up keyframes, split-words splitter ligger i FAS 1
- **Pixel-perfekt typografi** — temat äger
- **Story-card** — Sprint B
- **Helper-funktioner i `src/shared.js`** — utökas inte i Sprint A

---

## 7. Godkännande

- [ ] Mats godkänner attributnamn + defaults
- [ ] Mats godkänner CSS-variabel-namn (`--kpi-*`, `--section-*`, `--hero-*`)
- [ ] Mats godkänner att `theme: light/dark/accent`-modellen räcker för section-header + kpi-grid
- [ ] Densiq-claude bekräftar att schemat täcker alla mönster på sustainability-2025-sidan
- [ ] 0E-pre-mortem uppdaterad om risker uppstått

När alla kryss är gröna → låst kontrakt, FAS 1 kan starta.
