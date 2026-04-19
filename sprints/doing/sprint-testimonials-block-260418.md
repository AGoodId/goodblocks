---
priority: 2
status: doing
start: 2026-04-18
tags: [sprint, feature]
---

# Sprint: testimonials-block

## Bakgrund

golfhallen.info använder Spectra (`wp-block-uagb-testimonial` + `uagb-slick-carousel`) för en citatkartusell på startsidan. Vi vill ersätta det med ett nativt `goodblocks/testimonials`-block så att sajten inte är beroende av ett externt plugin. Playwright-audit av golfhallen.info (2026-04-18) bekräftade att detta är det enda saknade blocket av värde.

---

## Mål (Outcome)

- Blocket `goodblocks/testimonials` finns i goodblocks
- Fungerar som karusell: ett citat i taget, pilar + dots, fade eller slide
- Kan aktiveras på golfhallen.agoodsite.se utan Spectra-plugin
- Inga externa plugin-beroenden

---

## Scope

### Inkluderat
- `block.json` + registrering i `goodblocks.php`
- `render.php` — HTML-struktur med data-attribut
- `edit.js` — repeater för items (quote, author, role) + förhandsvisning
- `view.js` — Swiper-baserad karusell (Navigation, Pagination, Autoplay, EffectFade)
- `style.scss` — minimalistisk styling, CSS-variabler för anpassning
- Attribut: `items[]` (quote, author, role), `autoplay` (bool), `autoplayDelay` (ms), `animation` (fade|slide), `showArrows` (bool), `showDots` (bool)

### Exkluderat
- Stjärnbetyg
- Profilbilder / avatarer
- Grid-vy (flera synliga samtidigt)
- RTL-stöd

---

## Återanvändning

- **Swiper** — redan en projektdependency (`swiper: ^3.12.0`). Importmönster och modulval (Navigation, Pagination, Autoplay, EffectFade) återanvänds från `src/blocks/slider/view.js`
- **dataset-mönster** — läsa inställningar från data-attribut, se `search-autocomplete/view.js` och `slider/view.js`
- **render.php-mönster** — `get_block_wrapper_attributes()` + data-attribut, se valfritt befintligt block
- **edit.js repeater** — samma mönster som `search-autocomplete` (suggestedLinks-repeater, 2026-04-18)

---

## Beslut

- Swiper används (redan dependency) i stället för egen karusell-JS — minskar underhåll
- Attribut-baserade items (inte inner blocks) — enklare editor-UX för redaktörer

---

## Handoff

> Kopiera och klistra in som första meddelande när du byter Claude-instans.

```
Vi har en aktiv sprint i detta repo.

Läs först:
1. `sprints/doing/sprint-testimonials-block-260418.md` — sprintplanen
2. `CLAUDE.md` — konventioner för repot

Starta med Fas 0. Regler:
- Ingen kod ändras utan godkänd spec
- Markera tasks klara löpande (- [ ] → - [x])
- Kör comprehension gate innan merge av AI-genererad kod

Är du redo?
```

---

## FAS 0 — FÖRSTÅ & SPECIFICERA

> Ingen implementationskod skrivs förrän Fas 0 är klar.

### 0B. Återanvändningskoll

- [x] Genomsök `slider/view.js` och `product-carousel/view.js` — lista exakt vilka Swiper-moduler och CSS-importer som behövs <!-- brian:id=tsk_086c5b9d gh:https://github.com/AGoodId/goodblocks/issues/2 -->
- [x] Beslut: Swiper med Navigation + Pagination + Autoplay + EffectFade — bekräfta att detta täcker scope <!-- brian:id=tsk_1471fdce gh:https://github.com/AGoodId/goodblocks/issues/3 -->

### 0C. Spec

- [x] **Problemspec:** Vilka exakta Spectra-features används på golfhallen.info som vi måste matcha? (kör Playwright mot golfhallen.info om nödvändigt) <!-- brian:id=tsk_e387e8a1 gh:https://github.com/AGoodId/goodblocks/issues/4 -->
- [x] **Lösningsspec:** Datamodell, HTML-struktur, Swiper-config, CSS-variabler, block-attribut med defaultvärden <!-- brian:id=tsk_05bf6eb9 gh:https://github.com/AGoodId/goodblocks/issues/5 -->
- [x] **Antaganden:** Vilka WordPress/Swiper-versioner förutsätts? Hur hanteras 0 items i editor? <!-- brian:id=tsk_44ba0fde gh:https://github.com/AGoodId/goodblocks/issues/6 -->
- [x] **Acceptanskriterier:** Konkreta, verifierbara påståenden (se förslag nedan) <!-- brian:id=tsk_035022c2 gh:https://github.com/AGoodId/goodblocks/issues/7 -->

  Förslag på acceptanskriterier:
  1. Blocket finns i block-väljaren under kategorin "goodblocks"
  2. Redaktör kan lägga till/ta bort/redigera items (quote, author, role) i InspectorControls
  3. Karusellen visar ett citat i taget med fade-animation som default
  4. Pilar navigerar till nästa/föregående citat, wrapping runt
  5. Dots visar aktivt index och är klickbara
  6. Autoplay startar om det är aktiverat, pausar vid hover
  7. Blocket fungerar utan Spectra-plugin installerat
  8. `npm run lint` passerar utan nya errors

### 0D. Tester definieras från spec

- [x] **Enhet:** Swiper initieras korrekt — navigation, pagination, autoplay konfigurerade enligt data-attribut <!-- brian:id=tsk_e82eab7a gh:https://github.com/AGoodId/goodblocks/issues/8 -->
- [x] **Integration:** Block sparas i editor med 2+ items → render.php genererar korrekt HTML med data-attribut → view.js initierar Swiper på rendered output <!-- brian:id=tsk_af9e4f48 gh:https://github.com/AGoodId/goodblocks/issues/9 -->
- [x] **E2E / manuellt:** Öppna sida med blocket → citaten synliga → klicka nästa-pil → andra citatet visas → dot uppdateras → klicka dot 1 → första citatet visas igen <!-- brian:id=tsk_811fa31e gh:https://github.com/AGoodId/goodblocks/issues/10 -->
- [x] **Regression:** `slider`-blocket fungerar fortfarande (delar Swiper-dependency), `npm run build` utan fel <!-- brian:id=tsk_5b612787 gh:https://github.com/AGoodId/goodblocks/issues/11 -->

### 0E. Pre-mortem

- [x] Kör pre-mortem — lista riskerna: <!-- brian:id=tsk_a45009a9 gh:https://github.com/AGoodId/goodblocks/issues/12 -->
  1. Swiper-version conflict — slider och testimonials importerar olika moduler; CSS kan kollidera
  2. items-repeater i editor kan bli klumpig med många citat — överväg max-gräns
  3. Swiper initieras innan DOM är redo om blocket laddas asynkront (FSE/query-block)
- [x] Beslut: förändras scope eller spec baserat på riskerna? <!-- brian:id=tsk_19c9e56e gh:https://github.com/AGoodId/goodblocks/issues/13 -->

---

## FAS 1 — IMPLEMENTATION

> Ge Claude spec + tester, inte problemet.
> Markera AI-genererade tasks med 🤖.

- [x] 🤖 `block.json` + registrering i `goodblocks.php` (namespace `goodblocks/testimonials`, kategori `goodblocks`, attribut enligt spec) <!-- brian:id=tsk_d7f17999 gh:https://github.com/AGoodId/goodblocks/issues/14 -->
- [x] 🤖 `render.php` — wrapper med data-attribut, Swiper-HTML-struktur (`.swiper`, `.swiper-wrapper`, `.swiper-slide` per item, navigation, pagination) <!-- brian:id=tsk_0c2a9a19 gh:https://github.com/AGoodId/goodblocks/issues/15 -->
- [x] 🤖 `edit.js` — InspectorControls med items-repeater (quote textarea, author, role) + statisk förhandsvisning av första citatet <!-- brian:id=tsk_663fafae gh:https://github.com/AGoodId/goodblocks/issues/16 -->
- [x] 🤖 `view.js` — Swiper-init med Navigation, Pagination, Autoplay, EffectFade; läser inställningar från data-attribut <!-- brian:id=tsk_1c95ed34 gh:https://github.com/AGoodId/goodblocks/issues/17 -->
- [x] 🤖 `style.scss` — minimalistisk layout, citat centrerat, author under, pilar utanför, dots under; CSS-variabler för färg och typografi <!-- brian:id=tsk_cb5ccb59 gh:https://github.com/AGoodId/goodblocks/issues/18 -->

---

## FAS 2 — KÖRNING, VERIFIERING & EVAL

### 2A. Kör testerna

- [ ] Enhetstester gröna — Swiper initieras med rätt config <!-- brian:id=tsk_99662f62 gh:https://github.com/AGoodId/goodblocks/issues/19 -->
- [ ] Integrationstester gröna — block editor → render.php → view.js-flöde <!-- brian:id=tsk_f5f3f53c gh:https://github.com/AGoodId/goodblocks/issues/20 -->
- [ ] E2E-scenario verifierat: navigation, dots, autoplay (från 0D) <!-- brian:id=tsk_3995793f gh:https://github.com/AGoodId/goodblocks/issues/21 -->
- [x] Regressionstester gröna — slider-blocket fungerar, build utan fel, lint utan nya errors <!-- brian:id=tsk_2b279e90 gh:https://github.com/AGoodId/goodblocks/issues/22 -->

### 2B. Comprehension Gate

- [x] `/comprehension-gate` på `src/blocks/testimonials/` — verdict: CLEAR <!-- brian:id=tsk_9cd11f16 gh:https://github.com/AGoodId/goodblocks/issues/23 -->
- [x] Kan du förklara hur Swiper-instansen initieras och vad som händer om blocket visas utanför DOMContentLoaded? <!-- brian:id=tsk_1a0a65b5 gh:https://github.com/AGoodId/goodblocks/issues/24 -->

### 2C. Eval — Uppfylldes specen?

- [ ] Alla 8 acceptanskriterier uppfyllda (gå igenom 0C punkt för punkt) <!-- brian:id=tsk_acbd3515 gh:https://github.com/AGoodId/goodblocks/issues/25 -->
- [ ] Inga ospecificerade AI-tillägg utanför scope (inga stjärnbetyg, avatarer etc) <!-- brian:id=tsk_6c30293b gh:https://github.com/AGoodId/goodblocks/issues/26 -->
- [ ] Antaganden fortfarande giltiga? <!-- brian:id=tsk_f2eeb116 gh:https://github.com/AGoodId/goodblocks/issues/27 -->

---

## Definition of Done

- [x] Fas 0 komplett — spec och tester definierade innan implementation
- [x] Alla Fas 1-tasks klara
- [ ] Tester gröna (2A)
- [ ] Comprehension gate: CLEAR (2B)
- [ ] Alla 8 acceptanskriterier uppfyllda (2C)
- [x] `npm run lint` — 0 errors
- [x] `npm run build` — successful

---

## Lärdomar

[Fylls i när sprints stängs]
