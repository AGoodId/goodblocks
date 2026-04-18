---
priority: 2
status: doing
start: 2026-04-17
tags: [sprint, feature]
---

# Sprint: agoodapp-media-picker-260417

## Bakgrund

Många kunder på AGoodMember (agoodsport.se) har egna bilder och videor lagrade i systemets mediabank. WordPress-redaktörer behöver idag lämna WP-editorn för att hitta rätt mediafil. Vi löser det med ett Gutenberg-block som låter redaktören bläddra, välja och ladda upp media direkt inifrån editorn.

---

## Mål (Outcome)

En WordPress-redaktör kan välja bilder och videor från AGoodMember-mediabanken direkt i Gutenberg-editorn — utan att lämna WP. Vald mediafil hamnar i WP-mediabiblioteket och renderas i blocket.

---

## Scope

### Inkluderat
- Settings-sida (Settings → AGoodMember): API-nyckel + orgId per WP-installation
- PHP-proxy-endpoint: `GET /wp-json/goodblocks/v1/agoodmember/media` (vidarebefordrar till AGoodMember, håller API-nyckeln server-side)
- PHP-sideload-endpoint: `POST /wp-json/goodblocks/v1/agoodmember/sideload` (laddar upp till WP-mediabiblioteket, duplikatkontroll via `_agoodmember_source_id`)
- Block `goodblocks/agoodmember-media-picker` med modal för att bläddra och välja media
- Stöd för **bilder och videor**
- Registrering av blocket i `goodblocks.php`

### Exkluderat
- Scope OUT beslutas i Fas 0 (kandidater: bildbearbetning, multi-select, sync/uppdatering av redan uppladdade filer)
- Stöd för andra AGoodMember-resurser än media

---

## Öppna beslut (löses i Fas 0C)

- Block-namespace: `goodblocks/agoodmember-media-picker` eller `agoodmember/media-picker`?
- Vad sparar blocket? `attachmentId` + `render.php`, eller statisk saved HTML?
- API-bas-URL: hårdkodad `https://agoodsport.se` eller konfigurerbar i settings?
- Paginering i modalen: prev/next-knappar eller infinite scroll?
- Exakt scope OUT

---

## Beslut

- **Block-namespace:** `agoodapp/media-picker` (inte `goodblocks/`). Motivering: AGoodMember-integrationen är ett eget domänområde och kan brytas ut till ett eget plugin utan DB-migration. Konsekvens: registreras med en separat `register_block_type()`-rad i `goodblocks.php`, inte via den generiska slugg-loopen.
- **Vad blocket sparar:** `attachmentId` + `render.php`. Dynamisk rendering via `wp_get_attachment_image()` för bilder och `<video>`-tagg för video. Fallback-meddelande om attachment saknas.
- **API-bas-URL:** Konfigurerbar i settings-sidan, default `https://agoodsport.se`.
- **Paginering i modal:** Infinite scroll via `IntersectionObserver`. Loading-flagga för att blockera dubbla requests.

---

## Handoff

> Kopiera och klistra in som första meddelande när du byter Claude-instans.

```
Vi har en aktiv sprint i detta repo.

Läs först:
1. `sprints/doing/sprint-agoodapp-media-picker-260417.md` — sprintplanen
2. `CLAUDE.md` (om den finns) — konventioner för repot

Kontexten i korthet: vi bygger ett Gutenberg-block som låter WP-redaktörer
välja media från AGoodMember-API:et direkt i editorn. PHP-proxy håller
API-nyckeln server-side. Media sideloadas till WP-mediabiblioteket.

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

- [ ] `/dark-code-audit` på `inc/` och `src/blocks/` för att kartlägga mönster som bör följas <!-- brian:id=tsk_583e019a -->
- [ ] `/context-layer` på `inc/masonry-rest-api.php` och `src/blocks/media-grid-item/edit.js` — de närmaste analogerna <!-- brian:id=tsk_406c0291 -->

### 0B. Återanvändningskoll

**Hittades vid sprint-skapande:**
- REST-route-mönster: `inc/masonry-rest-api.php` — `register_rest_route('goodblocks/v1', ...)` med `rest_api_init`-hook. Kan återanvändas rakt av.
- Media-hantering i editor: `src/blocks/media-grid-item/edit.js` använder `MediaUpload` + `MediaUploadCheck` med stöd för både bild och video. Relevant för hur vi hanterar det valda mediet efter sideload.
- Block-registreringsmönster: `goodblocks.php` registrerar via loop över `$blocks`-array — lägg bara till slug.

- [ ] Bekräfta att `masonry-rest-api.php`-mönstret används för proxy och sideload <!-- brian:id=tsk_399ef1bc -->
- [ ] Besluta om modal byggs med `@wordpress/components` (`Modal`) eller custom — kolla om något block redan använder `Modal` <!-- brian:id=tsk_d953a78a -->

### 0C. Spec

- [ ] **Problemspec:** Definiera exakt flöde — redaktören öppnar block → klickar "Välj från AGoodMember" → modal → väljer → sideload → blocket visar media <!-- brian:id=tsk_c5e09c3b -->
- [ ] **Lösningsspec:** Besluta öppna frågor (namespace, vad sparas, API-URL, paginering, scope OUT). Dokumentera datamodell: vad lagras i block-attributen? <!-- brian:id=tsk_78f9f680 -->
- [x] **Antaganden / bekräftat API-kontrakt (260417):** <!-- brian:id=tsk_2d9b23cc -->

  ```
  GET /api/public/organizations/[orgId]/media?page=1&limit=24&search=
  Authorization: Bearer <api-key>

  Response:
  {
    "items": [
      {
        "id": "uuid",
        "title": "Logotyp vit",
        "filename": "logo-white.png",
        "format": "png",            ← filextension, INTE mediatyp
        "file_type": "image",       ← "image" | "video", NOT NULL (bekräftat 260418)
        "web_path": "https://…/logo-white.png",
        "thumbnail_path": "https://…/logo-white.png",
        "size_bytes": 14200
      }
    ],
    "total": 87,
    "hasMore": true
  }
  ```

  **Viktigt:** `format` är filextension ("jpg", "png", "mp4" etc.) — inte "image"/"video".
  `file_type` ("image"/"video") läggs till av AGoodApp-teamet. Tills dess: se BUG nedan.

  **BUG löst (260418):** `edit.js` använder nu `item.file_type === 'video'` med `?? 'image'` som försiktighetsåtgärd. `file_type` är NOT NULL i schemat — fallbacken behövs inte längre men är ofarlig.
- [ ] **Acceptanskriterier:** Se nedan <!-- brian:id=tsk_a908ddc0 -->

**Utkast på acceptanskriterier (preciseras i 0C):**
1. Redaktören kan öppna modal och se miniatyrbilder från AGoodMember-API:et
2. Paginering fungerar (next/prev eller scroll)
3. Klicka på en bild → bilden laddas upp till WP-mediabiblioteket om den inte redan finns
4. Samma bild klickas igen → duplicat skapas inte (kontroll via `_agoodmember_source_id`)
5. Vald video hanteras korrekt (inte bara bilder)
6. API-nyckel och orgId konfigureras på settings-sidan och används av proxy
7. Om API-nyckel saknas → tydligt felmeddelande i editorn
8. Blocket renderas korrekt på fronten

### 0D. Tester definieras från spec

- [ ] **Enhet (PHP):** Proxy-endpoint med giltig/ogiltig token. Sideload-endpoint med duplikat-scenario. Settings-validering. <!-- brian:id=tsk_d638caae -->
- [ ] **Integration:** Editor → klicka välj → modal öppnas → API-svar visas → välj media → sideload sker → block uppdateras med attachmentId <!-- brian:id=tsk_a5fcfe2d -->
- [ ] **E2E / manuellt:** Logga in som redaktör, infoga block, öppna modal, välj bild, spara inlägg, verifiera att bilden syns på fronten och finns i mediabiblioteket <!-- brian:id=tsk_1db80e38 -->
- [ ] **Regression:** Befintliga block (media-grid, media-grid-item, hero) påverkas inte av nya REST-routes eller `goodblocks.php`-ändringar <!-- brian:id=tsk_1d2f7ba1 -->

### 0E. Pre-mortem

> Fråga mig: "Vad kan gå fel med denna sprint?" innan Fas 1 startar.

- [ ] Kör pre-mortem — lista riskerna: <!-- brian:id=tsk_d92c24a2 -->
  1. [Risk 1]
  2. [Risk 2]
  3. [Risk 3]
- [ ] Besluta: förändras scope eller spec? <!-- brian:id=tsk_be28a610 -->

---

## FAS 1 — IMPLEMENTATION

> Ge Claude spec + tester, inte problemet. Markera AI-genererade tasks med 🤖.

- [x] 🤖 `inc/agoodapp-settings.php` — Settings-sida med API-nyckel + orgId + API-bas-URL <!-- brian:id=tsk_70964646 -->
- [x] 🤖 `inc/agoodapp-proxy.php` — REST-proxy `GET /wp-json/goodblocks/v1/agoodapp/media`, kräver `edit_posts` <!-- brian:id=tsk_903e691d -->
- [x] 🤖 `inc/agoodapp-sideload.php` — REST-endpoint `POST /wp-json/goodblocks/v1/agoodapp/sideload`, duplikatkontroll via `_agoodapp_source_id` meta <!-- brian:id=tsk_680f7d5d -->
- [x] 🤖 `src/blocks/agoodapp-media-picker/block.json` + `index.js` — namespace `agoodapp/media-picker` <!-- brian:id=tsk_1d1f5298 -->
- [x] 🤖 `src/blocks/agoodapp-media-picker/edit.js` — Modal med media-grid, infinite scroll, välj-knapp, loading/error-states <!-- brian:id=tsk_a92f7be4 -->
- [x] 🤖 `src/blocks/agoodapp-media-picker/render.php` — `wp_get_attachment_image()` för bilder, `<video>`-tagg för video, fallback om attachment saknas <!-- brian:id=tsk_ba2ff518 -->
- [x] Registrerat i `goodblocks.php` + entry-point i `webpack.config.js` <!-- brian:id=tsk_da3b7afe -->

---

## FAS 2 — KÖRNING, VERIFIERING & EVAL

### 2A. Kör testerna

- [ ] PHP-enhetstester gröna <!-- brian:id=tsk_f3e60079 -->
- [ ] Integrationstester gröna <!-- brian:id=tsk_0f3a0f36 -->
- [ ] E2E-scenario verifierat: redaktör väljer media → hamnar i WP-mediabiblioteket → syns på fronten <!-- brian:id=tsk_4e525523 -->
- [ ] Regressionstester gröna — befintliga block opåverkade <!-- brian:id=tsk_1e00c108 -->

### 2B. Comprehension Gate

- [ ] `/comprehension-gate` på `inc/agoodmember-*.php` och `src/blocks/agoodmember-media-picker/` — verdict: CLEAR / REVIEW / HOLD <!-- brian:id=tsk_d203a1f5 -->
- [ ] Kan du förklara vad koden gör utan att titta på den? <!-- brian:id=tsk_59897412 -->

### 2C. Eval — Uppfylldes specen?

- [ ] Alla acceptanskriterier uppfyllda (gå igenom 0C punkt för punkt) <!-- brian:id=tsk_f96f27a5 -->
- [ ] Inga ospecificerade AI-tillägg utanför scope
- [ ] Antaganden om AGoodMember-API:et fortfarande giltiga?

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

- **AGoodApp som `editor.MediaUpload`-option** — Lägg till AGoodApp-knapp direkt i `core/image`-blockets placeholder (bredvid Upload, Media Library, Insert from URL) via `editor.MediaUpload`-filtret. Fungerar då i alla WP-block, inte bara eget. Ca 2 dagars arbete. Kräver noggrann testning mot WP-versioner — filtret är internt och kan förändras. Prioritera efter att standalone-blocket är stabilt i produktion.

---

## Lärdomar

[Fylls i när sprints stängs]
