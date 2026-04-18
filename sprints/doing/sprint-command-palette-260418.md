---
priority: 2
status: doing
start: 2026-04-18
tags: [sprint, feature, search, ux, keyboard]
---

# Sprint: search-autocomplete — command-palette + highlight + gruppering

## Bakgrund

Analys av DENSIQ-sajten (april 2026) visade tre förbättringar för `search-autocomplete` som hör hemma i GoodBlocks:

1. **`/`-tangent** öppnar blocket (command-palette-mönster) — standard-UX på GitHub, Figma, Linear
2. **Highlight** av matchad sökterm i resultatlistan — visuell feedback på varför ett resultat matchar
3. **Grupperade resultat** per post type — tydlighet när sökresultaten span:ar CPTs

Sprinten lyftes ur `sprint-densiq-interactions-260417` i agoodsite-fse-repot (curtain + tag-chips stannar där — de är theme-specifika).

---

## Mål (Outcome)

- `/`-tangent (utan modifierare) öppnar `search-autocomplete` om inget input-element är fokuserat
- `⌘K`-tangent fungerar oförändrat
- Matchad sökterm är inlindad i `<mark class="search-autocomplete__highlight">` i resultatlistan
- Resultat grupperas per post type med grupprubrik
- Tangentbordsnavigation: ArrowUp/Down navigerar, Enter öppnar, Escape stänger

---

## Scope

### Inkluderat

- `/`-tangent i `bindKeyboardShortcuts()` — kontrollerar `document.activeElement` så att `/` i textfält inte triggar
- Highlight-funktion `highlightMatch(text, query)` — wrapplar matchade tecken i `<mark>`
- Modifierad `renderResults()` att använda `highlightMatch()` på titel och excerpt
- Gruppering: `groupResultsByType(results)` → renderar `<div class="search-autocomplete__group-label">` per typ
- ArrowUp/Down keyboard-navigation med `aria-activedescendant` update
- CSS för `.search-autocomplete__highlight` och `.search-autocomplete__group-label`

### Exkluderat

- Kategorisökning / fil-sökning (bara post types i MVP)
- Fuzzy matching (befintlig REST API-sökning används as-is)
- Animerad command-palette-ram (plain expand räcker för MVP)
- `⌘K` ändras inte

---

## Kontext

`search-autocomplete` är ett GoodBlocks-block (`goodblocks/search-autocomplete`).

Relevanta filer:
- `src/blocks/search-autocomplete/view.js` — all frontend-logik, `bindKeyboardShortcuts()`, `renderResults()`
- `src/blocks/search-autocomplete/style.scss` — befintliga `.search-autocomplete__*`-klasser
- REST API `agoodsite-fse/v1/search` returnerar `{ id, title, excerpt, url, type }` per resultat — `type`-fältet används för gruppering (finns redan)

### `/`-tangent — säker implementation

```js
document.addEventListener( 'keydown', ( e ) => {
    if ( e.key !== '/' ) return;
    const tag = document.activeElement?.tagName?.toLowerCase();
    const editable = document.activeElement?.isContentEditable;
    if ( [ 'input', 'textarea', 'select' ].includes( tag ) || editable ) return;
    e.preventDefault();
    this.open(); // befintlig metod
} );
```

### Highlight

```js
highlightMatch( text, query ) {
    if ( ! query ) return this.esc( text );
    const regex = new RegExp( `(${query.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' )})`, 'gi' );
    return this.esc( text ).replace( regex, '<mark class="search-autocomplete__highlight">$1</mark>' );
}
```

Notera: `esc()` körs FÖRE regex-replace för att undvika XSS via query-strängen.

### Gruppering

```js
groupResultsByType( results ) {
    return results.reduce( ( acc, r ) => {
        const type = r.type || 'post';
        if ( ! acc[ type ] ) acc[ type ] = [];
        acc[ type ].push( r );
        return acc;
    }, {} );
}
```

---

## Acceptanskriterier

1. `/`-tangent öppnar `search-autocomplete` om inget input är fokuserat
2. `/` i sökfält, kommentarsfält, `contenteditable` triggar INTE shortcut
3. `⌘K` fungerar oförändrat
4. Matchad text i resultat visas med `<mark class="search-autocomplete__highlight">`
5. Resultat grupperas per post type med grupprubrik
6. ArrowUp/Down navigerar bland resultaten utan att tappa fokus
7. Enter öppnar det markerade resultatet
8. Escape stänger overlay och returnerar focus till trigger-elementet
9. Befintliga E2E-tester (`search.spec.js`) passerar utan ändringar

---

## Fas 1 — Implementation

### 1A — `view.js`

- [ ] `/`-tangent i `bindKeyboardShortcuts()` — `activeElement`-guard
- [ ] `highlightMatch(text, query)` — XSS-safe (esc före regex)
- [ ] Modifiera `renderResults()` att använda `highlightMatch()` på titel
- [ ] `groupResultsByType(results)` — gruppera per `type`-fält
- [ ] Rendera grupper med `<div class="search-autocomplete__group-label">`
- [ ] ArrowUp/Down keyboard-navigation + `aria-activedescendant`

### 1B — `style.scss`

- [ ] `.search-autocomplete__highlight` — markstil (bakgrundsfärg, ej understrykning)
- [ ] `.search-autocomplete__group-label` — grupprubrik (liten, dämpad, uppercase)

---

## Fas 2 — Verifiering

- [ ] `/`-tangent testad på startsida och arkivsida
- [ ] `/` i sökfält triggar INTE shortcut
- [ ] ArrowUp/Down/Enter/Escape fullständigt testat
- [ ] Highlight synlig i resultatlistan
- [ ] Gruppering renderas korrekt med minst 2 CPTs i resultaten
- [ ] `search.spec.js` passerar

---

## Definition of Done

- `/`-tangent + `⌘K` öppnar search-autocomplete
- Highlight och gruppering synliga i resultatlistan
- Tangentbordsnavigation fullständig
- Inga JS-konsolfel
- Befintliga E2E-tester gröna
