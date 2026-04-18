---
priority: 2
status: doing
start: 2026-04-18
tags: [sprint, feature]
---

# Sprint: events-calendar-260418

## Bakgrund

Kunder använder The Events Calendar och Modern Events Calendar för att hantera händelser. Dessa plugins är tunga, dyra och introducerar externa beroenden. Vi vill ersätta dem med en enkel, stabil och lättanvänd lösning inbyggd i GoodBlocks — utan onödig komplexitet.

---

## Mål (Outcome)

En väl fungerande och lättanvänd kalender inbyggd i GoodBlocks. Redaktörer kan skapa och hantera händelser direkt i WP-admin, och befintliga event från The Events Calendar kan migreras utan dataförlust.

---

## Scope

### Inkluderat
- Custom post type `goodblocks_event` med stöd för kategorier och taggar
- Meta-fält: `_event_start` och `_event_end` (datetime, ISO 8601)
- Standardfält: titel, brödtext (Gutenberg-editorn), utdrag
- Block `goodblocks/event-list` med `viewMode: list|grid`
- List-vy: datum + titel + utdrag, sorterat på startdatum
- Grid-vy: cards med thumbnail, datum, titel, utdrag
- Migreringsscript från The Events Calendar (`_EventStartDate` → `_event_start`, `_EventEndDate` → `_event_end`)
- Meta box i admin för start/slut-datum och tid

### Exkluderat
- Kalendervy (månadsvy) — v2
- Återkommande event
- Biljetter, venues, organisatörer
- Modern Events Calendar-migration (scope ut tills vidare)
- iCal/export
- Filtrering på frontend (v2)

---

## Beslut

[Inga beslut ännu]

---

## Handoff

> Kopiera och klistra in som första meddelande när du byter Claude-instans.

```
Vi har en aktiv sprint i detta repo.

Läs först:
1. `sprints/doing/sprint-events-calendar-260418.md` — sprintplanen
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

- [ ] `/dark-code-audit` på `src/blocks/post-grid/` och `inc/` — kartlägg mönster att följa <!-- brian:id=tsk_2732f78d -->
- [ ] `/context-layer` på `src/blocks/post-grid/render.php` och `goodblocks.php` — närmaste analoger <!-- brian:id=tsk_6d7c841f -->

### 0B. Återanvändningskoll

**Hittades vid sprint-skapande:**
- `post-grid`: multi-vy-arkitektur med `gridType`-attribut (grid|list|people|timeline) + template-system via `goodblocks_template()`. Återanvänds direkt.
- `post-grid/render.php`: WP_Query-byggare med `meta_query` datetime-jämförelser och `meta_value` sortering. Återanvänds rakt av.
- `inc/helpers.php`: `goodblocks_template()` för template override-system.
- `inc/masonry-rest-api.php`: REST-route-mönster med sanitering och validering.
- `goodblocks.php`: CPT-registrering sker i `goodblocks_register_blocks()` — events CPT registreras analogt med `add_action('init', ...)`.

- [ ] Bekräfta att `post-grid`-mönstret återanvänds för block + render <!-- brian:id=tsk_7a44bd9b -->
- [ ] Besluta meta box-approach: native WP meta box eller `@wordpress/data` + block sidebar panel <!-- brian:id=tsk_c9aa1040 -->

### 0C. Spec

- [ ] **Problemspec:** Definiera exakt vad en redaktör behöver göra — skapa event, redigera datum/tid, se event i list/grid på fronten <!-- brian:id=tsk_db72c63d -->
- [ ] **Lösningsspec:** Datamodell (`_event_start`/`_event_end` som `YYYY-MM-DD HH:MM:SS`), block-attribut, WP_Query-parametrar, template-struktur <!-- brian:id=tsk_7550161f -->
- [ ] **Antaganden:** The Events Calendar lagrar datum i `_EventStartDate`/`_EventEndDate` som `YYYY-MM-DD HH:MM:SS` i UTC <!-- brian:id=tsk_286c89fe -->
- [ ] **Acceptanskriterier:** Se nedan <!-- brian:id=tsk_d73c3904 -->

**Utkast på acceptanskriterier (preciseras i 0C):**
1. Redaktör kan skapa ett event med titel, brödtext, startdatum+tid, slutdatum+tid, kategori och tagg
2. Block `goodblocks/event-list` med `viewMode: list` visar kommande event sorterade på startdatum
3. Block `goodblocks/event-list` med `viewMode: grid` visar event som cards med thumbnail, datum, titel, utdrag
4. Passerande event visas inte som standard (filter för att inkludera)
5. Migreringsscript konverterar `_EventStartDate`→`_event_start` och `_EventEndDate`→`_event_end` utan dataförlust
6. Befintliga block (post-grid, masonry-query m.fl.) påverkas inte
7. Fungerar i WP 6.4+ med PHP 8.0+

### 0D. Tester definieras från spec

- [ ] **Enhet (PHP):** `goodblocks_event_query()` med framtida/passerade/alla event. Migreringsscript med mock-meta. <!-- brian:id=tsk_213658fc -->
- [ ] **Integration:** Skapa event → lägg in block → verifiera att WP_Query returnerar rätt event <!-- brian:id=tsk_e0f58396 -->
- [ ] **E2E / manuellt:** Logga in som redaktör → skapa event med datum → infoga block på sida → verifiera list- och grid-vy på fronten → verifiera att passerat event försvinner <!-- brian:id=tsk_92ab7757 -->
- [ ] **Regression:** Befintliga block (post-grid, masonry-query, agoodapp-media-picker) opåverkade <!-- brian:id=tsk_e2ac21c7 -->

### 0E. Pre-mortem

- [ ] Kör pre-mortem — lista riskerna: <!-- brian:id=tsk_9d277dc7 -->
  1. [Risk 1]
  2. [Risk 2]
  3. [Risk 3]
- [ ] Besluta: förändras scope eller spec? <!-- brian:id=tsk_8164d518 -->

---

## FAS 1 — IMPLEMENTATION

> Ge Claude spec + tester, inte problemet. Markera AI-genererade tasks med 🤖.

- [ ] 🤖 `inc/events-cpt.php` — Registrera CPT `goodblocks_event`, taxonomier (kategori + tagg), meta-fält + meta box för start/slut-datum <!-- brian:id=tsk_ecd78d9d -->
- [ ] 🤖 `src/blocks/event-list/block.json` + `index.js` — Block `goodblocks/event-list` med attribut `viewMode`, `maxEvents`, `category`, `showPast` <!-- brian:id=tsk_15e9c79a -->
- [ ] 🤖 `src/blocks/event-list/edit.js` — Inspector Controls (viewMode-toggle, maxEvents, kategorifilter), preview i editorn <!-- brian:id=tsk_051effc8 -->
- [ ] 🤖 `src/blocks/event-list/render.php` — WP_Query på `goodblocks_event`, list- och grid-templates, fallback om inga event <!-- brian:id=tsk_90436271 -->
- [ ] 🤖 `inc/events-migrate.php` — WP-CLI-kommando `wp goodblocks migrate-events` som kopierar TEC-meta till GoodBlocks-schema <!-- brian:id=tsk_475c8baa -->
- [ ] Registrera CPT och block i `goodblocks.php` + entry-point i `webpack.config.js` <!-- brian:id=tsk_230f4a51 -->

---

## FAS 2 — KÖRNING, VERIFIERING & EVAL

### 2A. Kör testerna

- [ ] PHP-enhetstester gröna <!-- brian:id=tsk_841e8399 -->
- [ ] Integrationstester gröna <!-- brian:id=tsk_4d963b38 -->
- [ ] E2E-scenario verifierat: redaktör skapar event → syns i list/grid-vy på fronten <!-- brian:id=tsk_2752087b -->
- [ ] Regressionstester gröna — befintliga block opåverkade <!-- brian:id=tsk_53048328 -->

### 2B. Comprehension Gate

- [ ] `/comprehension-gate` på `inc/events-cpt.php`, `inc/events-migrate.php` och `src/blocks/event-list/` — verdict: CLEAR / REVIEW / HOLD <!-- brian:id=tsk_5babf34c -->
- [ ] Kan du förklara vad koden gör utan att titta på den? <!-- brian:id=tsk_4e757cf2 -->

### 2C. Eval — Uppfylldes specen?

- [ ] Alla acceptanskriterier uppfyllda (gå igenom 0C punkt för punkt) <!-- brian:id=tsk_a44c5fa4 -->
- [ ] Inga ospecificerade AI-tillägg utanför scope
- [ ] Antaganden om TEC-dataformat fortfarande giltiga?

---

## Definition of Done

- [ ] Fas 0 komplett — spec och tester definierade innan implementation
- [ ] Alla Fas 1-tasks klara
- [ ] Tester gröna (2A)
- [ ] Comprehension gate: CLEAR (2B)
- [ ] Alla acceptanskriterier uppfyllda (2C)
- [ ] Ingen ny dark code utan förståelsekontroll
- [ ] `goodblocks.php` plugin-beskrivning uppdaterad med nya blocket

---

## Backlog / v2

- Kalendervy (månadsvy)
- Filtrering på frontend (kategori, datum)
- Modern Events Calendar-migration
- iCal-export
- Återkommande event

---

## Lärdomar

[Fylls i när sprints stängs]
