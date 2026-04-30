---
type: sub-spec
related: sprints/doing/sprint-report-foundation-blocks-260429.md
created: 2026-04-29
---

# Hero PURGE — Sub-spec för Sprint A:s Fas 0

> Stöddokument till Sprint A. Beskriver konkret vad PURGE-vägen från `dark-code-audit` innebär. Måste godkännas innan 0C. Lösningsspec kan låsas.

## Bakgrund

`dark-code-audit` på `src/blocks/hero/` identifierade ett delvis trasigt animationssystem: 5 deklarerade animationer (`ingen`, `standard`, `wild`, `from-right`, `from-left`) varav minst 4 saknar matchande `@keyframes`. Tre attribut är döda (`backgroundType`, `positionClass`, `backgroundColor`-destrukturering utan källa). Redaktörs-konventioner som `||br||` och `*`-pulse är odokumenterade.

Beslut: **PURGE** — radera de trasiga animationerna och oanvända attributen, lägg sedan in Sprint A:s nya animationer (`none`, `fade-up`, `split-words`) på en ren grund.

---

## Förändringar i `block.json`

### Ändras
- `animation` enum: `["ingen", "standard", "wild", "from-right", "from-left"]` → `["none", "fade-up", "split-words"]`
- `animation` default: `"ingen"` → `"none"`

### Tas bort
- `backgroundType` (default `"image"`) — läses aldrig
- `positionClass` (default `""`) — cachad derivata av `contentPosition`

### Behålls
- `contentPosition`, `height`, `rubrik`, `text`, `button`, `reverseFlow`, `backgroundMedia`, `dimRatio`, `overlayColor`, `scrollArrow`

### Återanvänds (inte nytt attribut)
- `scrollArrow` — Sprint A:s "scroll-indikator" mappar till befintligt attribut, inte nytt `showScrollIndicator`

---

## Förändringar i `edit.js`

- Ta bort `backgroundColor`-destrukturering (finns inte i attributes)
- Ta bort `imageType === 'color'`-grenen (utan `backgroundColor` är den död)
- Ta bort de fyra gamla `SelectControl`-värdena, lägg in tre nya: `none` / `fade-up` / `split-words`
- Ta bort `getPositionClassName(v)`-anropet i `onChange` (positionClass-cache försvinner)
- Byt `<a className="btn">` (med eslint-disable) → `<button className="btn btn-large" type="button">` så editor och frontend matchar

---

## Förändringar i `render.php`

### Tas bort (rader 38–60)
- Hela title-loopen (bokstavssplittning, magic numbers `% 8`, `% 16`, position 3)
- `||br||`-token replacement
- `*` → `pulse`-mappning
- `from-right`/`from-left`-grenarna i if-elseif

### Ersätts med
```php
$title = '<h2>' . wp_kses_post( $rubrik ) . '</h2>';
```

Animation hanteras därefter via klass på wrappern: `hero-block--none`, `hero-block--fade-up`, `hero-block--split-words` (`split-words` får text-splittning via JS i view.js, Sprint A FAS 1).

- Ta bort `positionClass`-läsning, derivera från `contentPosition` direkt
- `<button class="btn btn-large">` får `type="button"` för accessibility

---

## Förändringar i `style.scss` + `editor.scss`

- Ta bort `&.from-right, &.from-left { white-space: nowrap; }` (klasserna försvinner)
- Ta bort `span.inline-block`, `.inline-block { display: inline-block; }` (legacy från title-loop)
- Editor-preview synkas: ta bort `editor.scss`-overrides för `height` och `font-size` så preview matchar frontend
- Sprint A FAS 1 lägger till `@keyframes hero-fade-up` + JS för `split-words`

---

## Datamigrering: graceful degradation, ingen DB-migration

**Vi gör INGEN destruktiv DB-migration.** Istället:

- `render.php` accepterar gamla värden tyst:
  ```php
  $animation = $attributes['animation'] ?? 'none';
  if ( ! in_array( $animation, [ 'none', 'fade-up', 'split-words' ], true ) ) {
      $animation = 'none'; // Legacy: ingen, standard, wild, from-right, from-left → none
  }
  ```
- Befintliga block med `animation: ingen` / `standard` / `wild` etc. renderas som `none`
- Editor visar `none` i select när blocket öppnas (ingenting krockar)
- Vid omsparning skrivs det till `"animation":"none"` — datat helar sig själv över tid
- Borttagna attribut (`backgroundType`, `positionClass`) ignoreras — WordPress kasserar okända attribut tyst
- `||br||` i `rubrik` renderas som literal text om någon haft det (osannolikt utanför animation-cases där det aldrig syntes ändå)
- `*` i `rubrik` renderas som vanlig asterisk — pulse-beteendet är borta

---

## Vad redaktörer ser efter uppdatering

- **Befintliga hero-block:** Ser identiska ut som idag (animationerna fungerade ändå inte). Inga "block invalid"-varningar.
- **När de öppnar Inspector-panelen:** Animation-select visar `None` / `Fade up` / `Split words`. Gammalt värde mappas till `None` visuellt — vid spara skrivs `none`.
- **Knapp i hero:** Renderas nu som `<button>` istället för `<a>` (osynlig skillnad).
- **`||br||` eller `*` i rubrik:** Visas som vanlig text. Tidigare användare av dessa konventioner får sina rubriker felformaterade — bör flaggas till Mats om någon kund använt dem.

---

## Risker med PURGE-vägen

| Risk | Sannolikhet | Mitigering |
|---|---|---|
| Kund har använt `||br||` i rubrik och får trasig text | Låg | Audit: grep i AGoodId-kunders post_content efter `||br||` innan release. Om hits → manuell migration. |
| Kund har använt `*` för pulse och förlorar effekten | Mycket låg | Effekten var odokumenterad. Acceptera regression. |
| Befintliga `from-right`/`from-left` block används aktivt | Låg | CSS-keyframes saknas → animationen är osynlig redan. Ingen synlig regression. |
| Sprint A:s AI tar bort fel sak | Medium | Comprehension-kommentarer i render.php innan Fas 1, plus comprehension-gate på koden efter Fas 1. |

---

## Audit-kommando innan release

Innan v1.12.0 taggas:

```bash
# Kör mot varje AGoodId-WP-instans
wp db query "SELECT ID, post_title FROM wp_posts WHERE post_content LIKE '%goodblocks/hero%' AND (post_content LIKE '%||br||%' OR post_content LIKE '%\"animation\":\"standard\"%' OR post_content LIKE '%\"animation\":\"wild\"%')"
```

Hits på den första `LIKE` (`||br||`) = manuell granskning krävs. Hits på `standard`/`wild` = informativt, inte blockerande.

---

## Sprint A-integration

Detta sub-spec ersätter delar av FAS 1-task `tsk_5ba1e921` (utöka hero):

**Före Sprint A FAS 1 startar:**
1. Detta sub-spec godkänns
2. Sub-specens "Tas bort"-listor läggs in i 0C. Lösningsspec
3. `goodblocks_migrate_hero_v2()` behöver INTE skrivas (graceful degradation räcker)

**I Sprint A FAS 1:**
- `tsk_5ba1e921` blir två-stegs: (a) PURGE enligt detta dokument, (b) Lägg till `fade-up`/`split-words` på ren grund
- Comprehension-gate efter steg (a) innan steg (b) startar — säkerställ att purgen inte tappade något oavsiktligt

---

## Godkännande

- [x] Mats godkänner PURGE-vägen (vs. KEEP eller FIX) — godkänt 2026-04-29
- [x] HTML-audit körd mot golfhallen.agoodsite.se + densiqgroup.com/sustainability-2025/ — **0 träffar** på `||br||` och 0 träffar på legacy animation-värden i renderad HTML
- [x] ~~DB-audit (`wp db query`) mot AGoodId-WP-instanser~~ — **skippad 2026-04-29**: HTML-auditen gav 0 träffar på de två huvudsajterna (golfhallen, densiq); riskerna är bounded (legacy-värden mappas tyst till `none`, worst case är `||br||` som literal text i en rubrik)
- [ ] 0C. Lösningsspec uppdateras med "Tas bort"-listor (under Fas 0)
- [x] FAS 1-task `tsk_5ba1e921` uppdelas i (a) purge + (b) lägg-till — gjort 2026-04-29
