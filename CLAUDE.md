# GoodBlocks — Claude-kontext

> Läs detta innan du gör något i repot.

## Vad är detta?

WordPress-plugin med återanvändbara Gutenberg-block för AGoodId-sajter. Distribueras som zip via GitHub Releases och uppdateras automatiskt i WordPress via den inbyggda `GoodBlocks_GitHub_Updater`.

## Stack

- PHP 8.0+, WordPress 6.4+
- Node.js 20+, `@wordpress/scripts` (webpack under huven)
- SCSS för stilar, vanilla JS + React för block-edit

## Kommandon

```bash
# Installera
npm install

# Starta dev-server (watch-läge)
npm start

# Bygg för produktion
npm run build

# Lint
npm run lint
```

## Arkitektur

```
goodblocks/
  goodblocks.php          Huvudfil — registrering, hooks, version
  inc/
    agoodmonitor.php      Hourly health reporter till AGoodMember API
    github-updater.php    Auto-update från GitHub Releases
    helpers.php           Template loader + thumbnail fallback
    masonry-rest-api.php  Load-more endpoint för Masonry Query
    search-rest-api.php   Sök + suggestions endpoints
  src/blocks/             Block-källkod (edit.js, view.js, style.scss, render.php, block.json)
  build/blocks/           Kompilerad output — COMMITTAS, används direkt av WordPress
  .github/workflows/
    ci.yml                Lint + build + PHP syntax + block-validering
    release.yml           Bygger goodblocks.zip och skapar GitHub Release vid tag
```

## Block-namespace

- `goodblocks/*` — vanliga block, registreras via slug-loopen i `goodblocks_register_blocks()`
- `agoodapp/*` — AGoodApp-integration, registreras **separat** (inte via loopen)
- CI-validatorn tillåter båda namespacen

## Kodkonventioner

- Dynamiska block använder alltid `render.php` — inte `save.js`
- `build/`-mappen committas — kör `npm run build` innan commit om du ändrat JS/CSS/SCSS
- REST-routes: namespace `goodblocks/v1`
- Lägg inte `agoodapp/*`-block i slug-loopen i `goodblocks_register_blocks()`
- Commit-format: Conventional Commits (`feat:`, `fix:`, `chore:`, `refactor:`, `docs:`, `test:`)

## Deployment

Push till `main` → CI kör (build + lint + block-validering).
Tagga `v*` → Release-workflow bygger `goodblocks.zip` och skapar GitHub Release.
WordPress-sajten hämtar uppdateringen automatiskt via `GoodBlocks_GitHub_Updater`.

**För att deploya:** commit + push till `main`, tagga sedan med `git tag vX.Y.Z && git push --tags`.

## Branch-strategi

| Branch | Syfte |
|--------|-------|
| `main` | Produktion — CI körs vid push, release vid tag |
| `feature/[namn]` | Ny funktionalitet |
| `fix/[namn]` | Buggfix |

Merge alltid via Pull Request till `main`. Pusha aldrig direkt till `main`.

## Vad du inte ska göra

- Pusha direkt till `main`
- Ta bort eller ignorera `build/`-mappen — den är intentionellt committad
- Lägga `agoodapp/*`-block i slug-loopen
- Lägga hemligheter i kod eller commits
- Ändra `.github/workflows/` utan att förstå konsekvenserna

## Gotchas

- `build/`-mappen committas (ovanligt men avsiktligt) — WordPress läser block direkt därifrån
- 16 kvarvarande lint-fel i `card-feature`, `media-grid`, `masonry-query` (view.js) — CI kör med `continue-on-error` tills de städats upp
- Namespace-migrering (`agoodsite-fse` → `agoodblocks` → `goodblocks`) körs automatiskt vid aktivering och version-update
- AGoodMonitor API-nyckel lagras i `wp_options`, inte i `.env`
