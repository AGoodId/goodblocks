---
priority: 2
status: doing
start: 2026-04-18
tags: [sprint, feature]
---

# Sprint: popup-260418

## Bakgrund

Kunder behöver ett sätt att visa modala popups (newsletter-signup, erbjudanden, notiser) utan tunga tredjepartsverktyg som Convert Pro. GoodBlocks saknar idag en lösning för detta — vi bygger den in som ett eget block med valfritt innehåll via InnerBlocks.

---

## Mål (Outcome)

En redaktör kan infoga ett popup-block på valfri sida, lägga in valfritt innehåll (t.ex. mailchimp-blocket), konfigurera trigger och cookie-varaktighet — modalen visas automatiskt för besökare som inte sett den, baserat på vald trigger.

---

## Scope

### Inkluderat — v1 (tid-trigger)
- Block `goodblocks/popup` med InnerBlocks (valfritt innehåll)
- Trigger: fördröjning i sekunder (default 3s)
- Cookie-baserat "redan sett": besökaren ser inte popupen igen förrän cookien löper ut
- Konfigurerbar cookie-varaktighet i dagar (default 7)
- Stäng-knapp (X) och stäng vid klick på overlay/backdrop
- Blockattribut: `delay` (number), `cookieDays` (number), `cookieName` (string, auto-genereras från block-ID)

### Inkluderat — v2 (fler triggers)
- Scroll-trigger: popup visas när besökaren scrollat X % av sidan
- Exit-intent: popup visas när muspekaren rör sig mot webbläsarens övre kant (desktop only)
- Triggerval i inspector: `time | scroll | exit`
- `scrollPercent` (number, default 50) visas villkorligt för scroll-trigger

### Exkluderat
- A/B-testning
- Statistik / konverteringsmätning
- Geotargeting
- Fördröjning baserat på inaktivitet
- Animationsval (v3)
- GDPR-consent-koppling (v3)

---

## Beslut

[Inga beslut ännu]

---

## Handoff

> Kopiera och klistra in som första meddelande när du byter Claude-instans.

```
Vi har en aktiv sprint i detta repo.

Läs först:
1. `sprints/doing/sprint-popup-260418.md` — sprintplanen
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

### 0A. Förståelseinfrastruktur

- [ ] `/context-layer` på `src/blocks/slider/edit.js` (InnerBlocks-mönster) och `src/blocks/countdown/view.js` (data-attribut + DOMContentLoaded) <!-- brian:id=tsk_e3c37cb1 -->

### 0B. Återanvändningskoll

**Hittades vid sprint-skapande:**
- `slider/edit.js`: `useInnerBlocksProps` med `allowedBlocks` — återanvänds för InnerBlocks-containern
- `countdown/view.js` + `slider/view.js`: data-attribut-mönster (config i `data-*`, JS läser via `dataset`) — återanvänds för delay/scrollPercent/trigger
- `hero/edit.js`: overlay-div med `dimRatio` och `overlayColor` — återanvänds för backdrop-styling
- `goodblocks/mailchimp-signup`: naturlig default-inner-block

- [ ] Bekräfta att data-attribut-mönstret från countdown/view.js används för popup-konfiguration <!-- brian:id=tsk_6179c401 -->
- [x] **Beslut: `cookieName` är ett manuellt redigerbart fält** med default `gb_popup_1`. Varje popup på en sajt måste ha ett unikt namn — dokumenteras i inspector-hjälptexten. Auto-generering från `clientId` undviks eftersom clientId ändras vid kopiering. <!-- brian:id=tsk_3d16dd2a -->

### 0C. Spec

- [ ] **Problemspec:** Redaktören vill visa en modal med valfritt innehåll för nya besökare utan att installera extra plugin <!-- brian:id=tsk_2abb33e2 -->
- [ ] **Lösningsspec:** Datamodell (attribut), view.js-flöde (check cookie → bind trigger → visa/dölj), render.php-struktur <!-- brian:id=tsk_9d027206 -->
- [ ] **Antaganden:** `localStorage` eller `document.cookie` för state? Besluta i spec. <!-- brian:id=tsk_5a1bdf67 -->
- [ ] **Acceptanskriterier:** Se nedan <!-- brian:id=tsk_d52c8d93 -->

**Utkast på acceptanskriterier:**

**v1:**
1. Popup visas efter angiven fördröjning (default 3s) om besökaren inte sett den
2. Popup visas inte på nytt inom cookie-perioden (default 7 dagar)
3. Popup stängs med X-knapp
4. Popup stängs vid klick på backdrop
5. InnerBlocks: valfritt innehåll kan läggas in (mailchimp, text, bild etc.)
6. Fungerar på mobil
7. **Popup är alltid dold i HTML (`display:none`) — JS visar den.** Fungerar korrekt med full-page cache (LiteSpeed m.fl.)
8. **`cookieName` är ett manuellt fält** med default `gb_popup_1` och hjälptext om att det måste vara unikt per sajt

**v2:**
9. Scroll-trigger: popup visas när besökaren scrollat angiven % av sidan
10. Exit-intent: popup visas när musen når övre 5% av viewporten — **endast på enheter med hover+pointer** (`matchMedia('(hover: hover) and (pointer: fine)')`)
11. Triggerval (time/scroll/exit) väljs i inspector

### 0D. Tester definieras från spec

- [ ] **Enhet (JS):** Cookie sätts vid stängning. Cookie-check förhindrar visning. Timer triggas efter korrekt delay. Scroll-procent beräknas korrekt. <!-- brian:id=tsk_21b31e91 -->
- [ ] **Integration:** Infoga block → lägg in mailchimp-block inuti → spara → verifiera att popup renderas korrekt på frontend <!-- brian:id=tsk_88d8a255 -->
- [ ] **E2E / manuellt:** Öppna sida → popup visas efter X sek → stäng → ladda om → popup syns inte → vänta cookie-period → popup visas igen <!-- brian:id=tsk_2d550651 -->
- [ ] **Regression:** Befintliga block (mailchimp, slider, countdown) opåverkade av ny view.js <!-- brian:id=tsk_1eb359e6 -->

### 0E. Pre-mortem

- [x] Kör pre-mortem — lista riskerna: <!-- brian:id=tsk_2d8118e3 -->
  1. Cache-plugins (LiteSpeed m.fl.) cachelagrar HTML → popup kan visas trots stängd cookie. **Fix: popup alltid dold i HTML, JS visar.**
  2. Exit-intent triggas på touch/mobil via `mousemove`. **Fix: guard med `matchMedia('(hover: hover) and (pointer: fine)')`.**
  3. Flera popups delar cookie-namn vid kopiering av block. **Fix: manuellt `cookieName`-fält med unikt default.**
- [x] Besluta: spec justerad med tre motåtgärder ovan. <!-- brian:id=tsk_8aa708a8 -->

---

## FAS 1 — IMPLEMENTATION

> Ge Claude spec + tester, inte problemet. Markera AI-genererade tasks med 🤖.

### v1 — Tid-trigger

- [ ] 🤖 `src/blocks/popup/block.json` + `index.js` — Block `goodblocks/popup`, InnerBlocks, attribut: `trigger`, `delay`, `cookieDays`, `cookieName` <!-- brian:id=tsk_35380538 -->
- [ ] 🤖 `src/blocks/popup/edit.js` — InspectorControls (trigger-select, delay/scrollPercent/cookieDays), InnerBlocks-preview i editorn <!-- brian:id=tsk_d0b272d7 -->
- [ ] 🤖 `src/blocks/popup/render.php` — Renderar backdrop + modal-container med InnerBlocks-innehåll, data-attribut för JS-config <!-- brian:id=tsk_31c33acd -->
- [ ] 🤖 `src/blocks/popup/view.js` — Cookie-check, tid-trigger (setTimeout), stäng via X och backdrop-klick <!-- brian:id=tsk_eeef51eb -->

### v2 — Fler triggers (byggs på v1)

- [ ] 🤖 `view.js`: scroll-trigger via `scroll`-event + `scrollPercent`-attribut <!-- brian:id=tsk_dd46d37b -->
- [ ] 🤖 `view.js`: exit-intent via `mousemove`-event, desktop only (`matchMedia`) <!-- brian:id=tsk_1cd31bea -->
- [ ] 🤖 `edit.js`: visa `scrollPercent`-kontroll villkorligt när `trigger === 'scroll'` <!-- brian:id=tsk_e75e3143 -->

### Gemensamt

- [ ] Registrera i `goodblocks.php` + entry-point i `webpack.config.js` <!-- brian:id=tsk_96f17492 -->

---

## FAS 2 — KÖRNING, VERIFIERING & EVAL

### 2A. Kör testerna

- [ ] Manuellt: tid-trigger fungerar efter angiven fördröjning <!-- brian:id=tsk_1b30b1e4 -->
- [ ] Manuellt: cookie förhindrar visning vid omladdning <!-- brian:id=tsk_98c07657 -->
- [ ] Manuellt: scroll-trigger visas vid rätt scroll-% <!-- brian:id=tsk_2ebab7ff -->
- [ ] Manuellt: exit-intent triggas på desktop, ignoreras på mobil <!-- brian:id=tsk_6e748888 -->
- [ ] Regressionstester gröna — befintliga block opåverkade <!-- brian:id=tsk_ca61269d -->

### 2B. Comprehension Gate

- [ ] `/comprehension-gate` på `src/blocks/popup/` — verdict: CLEAR / REVIEW / HOLD <!-- brian:id=tsk_9e6738d5 -->

### 2C. Eval — Uppfylldes specen?

- [ ] Alla v1-acceptanskriterier uppfyllda
- [ ] Alla v2-acceptanskriterier uppfyllda
- [ ] Inga ospecificerade AI-tillägg utanför scope

---

## Definition of Done

- [ ] Fas 0 komplett
- [ ] Alla Fas 1-tasks klara (v1 + v2)
- [ ] Tester gröna (2A)
- [ ] Comprehension gate: CLEAR (2B)
- [ ] Alla acceptanskriterier uppfyllda (2C)
- [ ] `goodblocks.php` plugin-beskrivning uppdaterad

---

## Backlog / v3

- Animationsval (fade, slide in från kant)
- GDPR-consent-koppling (visa inte förrän consent givits)
- Fördröjning baserat på inaktivitet
- Stöd för flera popups per sida med individuella cookies

---

## Lärdomar

[Fylls i när sprints stängs]
