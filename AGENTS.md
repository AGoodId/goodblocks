# GoodBlocks — Agent Guide

Read this before changing the GoodBlocks plugin or using it from a WordPress site.

GoodBlocks is a WordPress plugin with reusable Gutenberg blocks for AGoodId sites. It is shipped as a zip through GitHub Releases and can update WordPress installs through `GoodBlocks_GitHub_Updater`.

This file has two audiences:

- Agents working inside this plugin repo.
- Agents working inside a site repo that consumes GoodBlocks, such as `em.cheerleading.se`.

## Current Status

- Production branch: `main`.
- Do not push directly to `main`; use a PR.
- `build/` is intentionally committed and is loaded by WordPress.
- Event/schedule work is being developed as GoodBlocks-owned functionality, with a competition schedule MVP first and a fuller calendar module later.

## Stack

- PHP 8.0+
- WordPress 6.4+
- Node.js 20+
- `@wordpress/scripts` with webpack
- SCSS
- Vanilla JS for frontend behavior
- React for block editor UIs

## Commands

```bash
npm install
npm start
npm run build
npm run lint
npm run lint:js
npm run lint:css
```

Use targeted lint commands when touching files with known legacy lint problems:

```bash
npx wp-scripts lint-js src/blocks/event-schedule src/blocks/event-now-next src/blocks/event-class-schedule
npx wp-scripts lint-style 'src/blocks/event-schedule/**/*.scss' 'src/blocks/event-now-next/**/*.scss' 'src/blocks/event-class-schedule/**/*.scss'
```

Always run PHP syntax checks on changed PHP files:

```bash
php -l goodblocks.php
php -l inc/events-cpt.php
php -l src/blocks/example/render.php
```

## Architecture

```text
goodblocks/
  goodblocks.php          Plugin header, constants, requires, block registration
  inc/
    agoodapp-*.php        AGoodApp media picker/import integration
    events-cpt.php        GoodBlocks Events CPT, meta, admin UI, CSV import helpers
    events-migrate.php    WP-CLI migration from The Events Calendar
    github-updater.php    GitHub Releases auto-updater
    helpers.php           Template loader and thumbnail fallback
    masonry-rest-api.php  Load-more endpoint for Masonry Query
    popup-cpt.php         Popup CPT
    search-rest-api.php   Search and suggestions endpoints
    showcase.php          Style guide showcase registration
  src/blocks/             Block source: block.json, index.js, edit.js, view.js, style.scss, render.php
  build/blocks/           Compiled output. Commit this.
  webpack.config.js       Manual block entrypoints and PHP template copying
  .github/workflows/
    ci.yml                Lint, build, PHP syntax, block validation
    release.yml           Builds goodblocks.zip and creates GitHub Release on v* tags
```

## Block Namespaces

- `goodblocks/*`: normal GoodBlocks blocks, registered through the slug loop in `goodblocks_register_blocks()`.
- `agoodapp/*`: AGoodApp integration blocks, registered separately.

Never put `agoodapp/*` blocks into the normal slug loop.

## Development Rules

- Dynamic blocks should use `render.php`; do not add `save.js` unless there is a clear static-block reason.
- If you change JS, CSS, SCSS, block metadata, or render templates, run `npm run build` and commit the generated `build/` changes.
- If you add a block, update both `goodblocks_register_blocks()` in `goodblocks.php` and the manual entries in `webpack.config.js`.
- REST route namespace is `goodblocks/v1`.
- Prefer existing helper functions and block patterns over new abstractions.
- Keep compatibility with theme template overrides via `goodblocks_template()`.
- Do not commit secrets, API keys, `.env` values, exports with private data, or WordPress uploads.
- Commit messages should use Conventional Commits: `feat:`, `fix:`, `chore:`, `refactor:`, `docs:`, `test:`.

## Build Output

`build/` is intentionally committed. WordPress reads block registration, render callbacks, styles, and scripts from `build/blocks/*`.

When changing a block:

1. Edit files in `src/blocks/<block>/`.
2. Run `npm run build`.
3. Confirm the matching files changed under `build/blocks/<block>/`.
4. Commit both source and build output.

Do not delete or ignore `build/`.

## Releases

Push to `main` runs CI. Tags matching `v*` run the release workflow, build `goodblocks.zip`, and create a GitHub Release.

Deployment flow:

```bash
git checkout -b codex/your-change
npm run build
git status
git add ...
git commit -m "feat: ..."
git push -u origin codex/your-change
```

Open a PR to `main`. After merge, bump/tag a release only when the plugin should be distributed:

```bash
git tag vX.Y.Z
git push --tags
```

Do not push directly to `main`.

## Known Gotchas

- `build/` is unusual but intentional.
- Some older blocks still have known lint issues; do not broaden a small change into a large lint cleanup unless asked.
- CI has historically tolerated some legacy lint failures while they are being cleaned up.
- Namespace migration from `agoodsite-fse` and `agoodblocks` to `goodblocks` runs on activation/version update.
- AGoodMonitor API key is stored in `wp_options`, not `.env`.
- The plugin version is defined in both the plugin header and `GOODBLOCKS_VERSION`; keep them in sync when bumping releases.

## GoodBlocks Events

GoodBlocks Events is the shared event and schedule model. The first sharp use case is competition schedules, but the data model should stay general enough to evolve into a complete calendar module.

### Post Type

```text
goodblocks_event
```

Registered in `inc/events-cpt.php`.

### Taxonomies

```text
event_category
event_tag
```

Use `event_category` for editorial grouping such as competition, training, meeting, camp, seminar, or site-specific event sections.

### Meta Fields

```text
_event_start     Required for scheduling. Date or datetime.
_event_end       Optional. Date or datetime.
_event_all_day   Boolean-like string.
_event_class     Competition class/division, for example "Junior All Girl Elite".
_event_type      qualification | semifinal | final | award | training | other | empty.
_event_venue     Arena, mat, room, stage, field, or venue label.
_event_stream    Livestream URL.
_event_results   Results URL.
_event_status    scheduled | changed | cancelled | live | done.
```

### Helper Functions

Use these instead of duplicating date/meta logic:

```php
goodblocks_get_events( array $args = [] ): array
goodblocks_get_event_data( int $post_id ): array
goodblocks_format_event_date( string $start, string $end = '', bool $all_day = false ): string
goodblocks_format_event_time( string $start, string $end = '', bool $all_day = false ): string
goodblocks_event_type_label( string $event_type ): string
goodblocks_event_status_label( string $status ): string
```

### Event Blocks

Use these blocks for event and schedule pages:

```text
goodblocks/event-schedule
goodblocks/event-class-schedule
goodblocks/event-now-next
goodblocks/event-list
```

Recommended usage:

- `goodblocks/event-schedule`: full competition or event schedule with day filters, search, and type filtering.
- `goodblocks/event-class-schedule`: "find my class" flow for visitors who only care about their class/division.
- `goodblocks/event-now-next`: homepage, event-day page, big screen, or live information area.
- `goodblocks/event-list`: simple upcoming event listings.
- `goodblocks/post-grid`: editorial event teasers and flexible grid/list layouts. It supports `goodblocks_event` sorting by `_event_start`.

Do not use `post-grid` as the primary competition schedule UI. Use `event-schedule` or `event-class-schedule` for that.

### CSV Import

Admin path:

```text
Events -> Import Schedule
```

Supported CSV headers:

```csv
title,start,end,class,type,venue,stream,results,status,all_day,excerpt
```

Example:

```csv
title,start,end,class,type,venue,stream,results,status,all_day,excerpt
Junior All Girl Elite Qualification,2026-06-27 10:20,2026-06-27 10:28,Junior All Girl Elite,qualification,Arena A,https://example.com/live,https://example.com/results,scheduled,0,
Junior All Girl Elite Final,2026-06-28 15:10,2026-06-28 15:18,Junior All Girl Elite,final,Arena A,https://example.com/live,https://example.com/results,scheduled,0,
Awards Junior Divisions,2026-06-28 17:00,2026-06-28 17:30,Junior Divisions,award,Arena A,,,scheduled,0,
```

CSV requirements:

- `title` and `start` are required.
- Use ISO-like dates when possible: `YYYY-MM-DD HH:MM`.
- Use consistent class names. Visitor search depends on these being clean.
- Use the allowed `type` and `status` values listed above.
- `all_day` accepts `1`, `true`, `yes`, or `ja`.

### The Events Calendar Migration

There is a WP-CLI migration command in `inc/events-migrate.php`:

```bash
wp goodblocks migrate-events --dry-run
wp goodblocks migrate-events
```

It migrates `tribe_events` to `goodblocks_event` and maps The Events Calendar date meta to GoodBlocks date meta.

## Guidance For Site Agents

If you are working in a site repo that uses GoodBlocks, do not edit this plugin inside the site unless the plugin is vendored there intentionally. Prefer updating the plugin version through WordPress/GitHub Releases.

For a competition site such as `em.cheerleading.se`:

1. Confirm the installed GoodBlocks version includes the event schedule module.
2. Prepare schedule data as CSV with the headers documented above.
3. Import schedule rows through WordPress admin.
4. Create or update pages using:
   - Full schedule page: `goodblocks/event-schedule`
   - Visitor search page or section: `goodblocks/event-class-schedule`
   - Homepage/live page: `goodblocks/event-now-next`
5. Use `goodblocks/post-grid` only for teasers, news-like event cards, or landing-page sections.
6. Keep class names, event types, dates, and venue labels consistent.
7. If a time changes, update the `goodblocks_event` row, not hand-written page content.

When creating schedule pages, prioritize mobile use. Visitors are likely standing in the venue, looking for one class, one arena, and one next time.

## Future Calendar Direction

The schedule MVP should evolve into a fuller calendar module without breaking current event data.

Likely future additions:

- Month/week/list calendar views.
- Recurring events.
- ICS export and "Add to calendar" links.
- REST endpoints for events.
- Better admin table editing.
- Conflict/overlap warnings.
- Stronger relation to result pages.
- Schedule change audit trail.

Keep new event features compatible with the existing `goodblocks_event` CPT and meta fields unless there is a strong reason to migrate.

## Riskytor och verifieringspolicy (dev-orchestrator)

Denna sektion läses av den användarglobala dev-orchestrator-skillen för projektspecifika parametrar. Den har företräde framför skillens generiska defaults.

### Riskytor

GoodBlocks distribueras som en delad plugin till flera WordPress-sajter (via `GoodBlocks_GitHub_Updater`, se `inc/github-updater.php`) och auto-uppdateras på alla sajter som kör den när en `v*`-tag pushas. En trasig release har bred blast radius — den träffar alla konsument-sajter samtidigt, inte bara ett repo. Ändringar i följande kräver oberoende motbevisare (en separat subagent/modellkörning som aktivt försöker hitta fel, inte bara bekräftar) och den starkaste tillgängliga checker-modellen (Fable → Opus vid konflikt om budget):

- `inc/github-updater.php` — auto-update-mekanismen. Fel här kan bryta uppdateringar eller leverera trasig kod till alla sajter.
- `.github/workflows/ci.yml` och `.github/workflows/release.yml` — release-kedjan som paketerar och distribuerar `goodblocks.zip`.
- REST-endpoints i `inc/search-rest-api.php`, `inc/masonry-rest-api.php` (permission_callback `__return_true`, dvs publikt nåbara utan auth) och `inc/agoodapp-sideload.php`, `inc/agoodapp-proxy.php` (kräver `edit_posts`/`upload_files` — verifiera att caps-checken faktiskt körs och inte kan kringgås).
- Datamuterande vägar: CSV-import (`goodblocks_import_events_csv()` i `inc/events-cpt.php`) och WP-CLI-migreringen `wp goodblocks migrate-events` (`inc/events-migrate.php`), som skriver om `post_type` och meta i bulk. Kräv `--dry-run`-verifiering och validera att `goodblocks_get_events()`/virtuell recurrence-expansion (`_event_recurrence_*`-meta) inte kan producera oändliga eller felaktiga occurrence-listor.
- `goodblocks.php` — huvudregistrering, versionskonstant (`GOODBLOCKS_VERSION`) och namespace-migreringslogik (`agoodsite-fse` → `agoodblocks` → `goodblocks`) som körs automatiskt vid aktivering/version-update.

### Mänskliga gater

- Ingen push till `main` utan PR-granskning (gäller även agenter — se "Branch-strategi"/"Releases" ovan).
- Tagging (`git tag vX.Y.Z && git push --tags`) som triggar en publik release kräver explicit mänskligt godkännande — det är en distributionshändelse till produktionssajter, inte en vanlig commit.
- Ändringar i `.github/workflows/` kräver mänsklig granskning innan merge (redan noterat under "Vad du inte ska göra").
- Bulk-datamigrering (`wp goodblocks migrate-events` utan `--dry-run`) mot en produktionsdatabas kräver explicit mänskligt godkännande.

### Modellpolicy

- Implementation: standardmodell (Sonnet) för de flesta ändringar i block, PHP-helpers och templates.
- Oberoende verifiering/motbevisare av riskytorna ovan: starkaste tillgängliga checker-modell (Fable, eller Opus om Fable inte är tillgänglig). Motbevisaren ska vara en separat körning/subagent från den som skrev ändringen, inte samma kontext.
- Rutinmässig lint/formattering och dokumentationsuppdateringar: valfri modell, ingen oberoende granskning krävs.

### Verifieringskommando

```bash
# PHP-syntax på ändrade filer
php -l goodblocks.php
php -l inc/events-cpt.php
php -l inc/events-migrate.php
php -l inc/github-updater.php

# Lint (hela repot)
npm run lint

# Riktad lint för kända legacy-problemområden
npx wp-scripts lint-js src/blocks/event-schedule src/blocks/event-now-next src/blocks/event-class-schedule
npx wp-scripts lint-style 'src/blocks/event-schedule/**/*.scss' 'src/blocks/event-now-next/**/*.scss' 'src/blocks/event-class-schedule/**/*.scss'

# Events-specifik smoke-test
npm run test:events

# Build och verifiera att build/ faktiskt ändrades för berörda block
npm run build
git status build/blocks/

# Block-validering (samma check som CI kör)
python3 -m json.tool src/blocks/<block>/block.json

# Migrering: alltid dry-run först
wp goodblocks migrate-events --dry-run
```

## Pre-PR Checklist

- Read `git status` before editing and before finishing.
- Do not revert unrelated user or agent changes.
- Run PHP syntax checks for changed PHP files.
- Run targeted lint for changed JS/SCSS where practical.
- Run `npm run build` after block or asset changes.
- Confirm `build/` output is present for changed blocks.
- Review `git diff` for accidental generated or unrelated changes.
- Mention any known lint/test gaps in the handoff.
