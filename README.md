# GoodBlocks

A collection of 12 reusable Gutenberg blocks for WordPress, built and maintained by [AGoodId](https://agoodid.se).

**Requires:** WordPress 6.4+ &bull; PHP 8.0+
**License:** GPL-2.0-or-later

---

## Blocks

### Masonry Query

Dynamic masonry grid that fetches posts, pages, media, or custom post types. Supports lightbox, filtering, load-more pagination, infinite scroll, hover effects, overlay styles, deep links, and animations. Highly configurable with per-breakpoint column counts and multiple image sources (featured, first in content, ACF field).

### Post Grid

Display posts in four layout variants: **grid**, **list**, **people**, and **timeline**. Supports multi-select post types and taxonomy terms, configurable excerpt length, meta fields, aspect ratio, and a "show more" link. Works with pages, custom post types, and child posts.

### Image Compare

Before/after image comparison slider with drag, touch, and keyboard support. Features:

- **Auto-tease animation** that smoothly slides between before and after when the block scrolls into view, attracting attention without user interaction
- **User takeover** — any mouse, touch, or keyboard input immediately stops the animation and hands control to the user
- **Aspect ratio control** (16:9, 4:3, 3:2, 1:1, and more) to prevent oversized blocks
- **Focal point picker** for each image, controlling which part is visible when cropped
- **Vertical mode** for top-to-bottom comparisons
- **Accessibility** — full keyboard navigation, ARIA slider role, respects `prefers-reduced-motion`

### Search Autocomplete

Live search field with REST API-powered autocomplete. Shows results with thumbnails, excerpts, and post type labels. Supports expandable mode (icon-only until clicked), configurable post types, and minimum character threshold.

### Feature Card

Versatile card block with image, hover effects (lift, zoom), and entrance animations (fade-up). Supports vertical layout, custom aspect ratio, link target, and optional meta text.

### Media Grid

Flexible grid container with bento layout options (large-left, large-right, large-top, mosaic, custom). Each grid item supports background images/video, text overlay with configurable position and opacity, hover effects, and responsive column counts per breakpoint.

### Double Container with Text

Two side-by-side containers, each with independent title, text, background image, overlay color/opacity, and link. Useful for split-screen hero sections and comparison layouts.

### Countdown

Countdown timer to a specific date and time. Optionally shows seconds. Supports wide and full alignment.

### Quiz

Interactive quiz question block with multiple answers, correct answer highlighting, and background media support. Useful for educational content and engagement.

### Page List

Sidebar-style page menu that lists child pages of a selected parent. Optionally includes the parent page itself.

### Mailchimp Signup

Simple email signup form that posts directly to a Mailchimp list. Configurable title, description text, and list link.

---

## Features beyond blocks

### Auto-updates

GoodBlocks updates itself directly from GitHub Releases via the WordPress plugin update screen. When a new release is tagged, the CI pipeline builds a zip and publishes it. WordPress checks for updates automatically (every 12 hours) or on demand via **Dashboard > Updates > Check again**.

### AGoodMonitor

Built-in WordPress health reporter that sends hourly status reports to the AGoodMember API. Collects WordPress/PHP versions, plugin and theme status (including available updates), and Site Health issues. Configured under **Settings > AGoodMonitor** with an API key.

### Page taxonomy support

Enables categories and tags on Pages (not just Posts). Active by default. Themes or plugins can disable it:

```php
add_filter( 'goodblocks_page_taxonomies', '__return_false' );
```

### Theme template overrides

Block templates can be overridden from the active theme. The plugin looks for templates in this order:

1. `your-theme/goodblocks/templates/{block}/{template}.php`
2. Plugin `build/blocks/{block}/templates/{template}.php`

The lookup path can be filtered with `goodblocks_template_path`.

### Namespace migration

Handles automatic migration from the legacy `agoodsite-fse` and `agoodblocks` namespaces. Runs on activation and on version update via `plugins_loaded`. Rolls back on deactivation so the older plugin can take over if needed.

---

## REST API

The plugin registers three endpoints under `goodblocks/v1`:

| Endpoint | Method | Purpose |
|---|---|---|
| `/masonry-query` | POST | Load-more pagination for the Masonry Query block |
| `/search` | GET | Live search with post type filtering |
| `/search/suggestions` | GET | Popular or matching post suggestions |

---

## Development

### Prerequisites

- Node.js 20+
- npm

### Setup

```bash
git clone https://github.com/AGoodId/goodblocks.git
cd goodblocks
npm install
```

### Scripts

| Command | Description |
|---|---|
| `npm run build` | Production build to `build/` |
| `npm start` | Watch mode with hot reload |
| `npm run lint:js` | Lint JavaScript with `@wordpress/eslint-plugin` |
| `npm run lint:css` | Lint SCSS files |
| `npm run lint` | Run both JS and CSS linting |

### Project structure

```
goodblocks/
  goodblocks.php          Main plugin file
  inc/
    agoodmonitor.php      Health reporter
    github-updater.php    Auto-update from GitHub releases
    helpers.php           Template loader + thumbnail fallback
    masonry-rest-api.php  Masonry load-more endpoint
    search-rest-api.php   Search + suggestions endpoints
  src/blocks/             Block source (edit.js, view.js, style.scss, render.php, block.json)
  build/blocks/           Compiled output (committed, used by WordPress)
  .github/workflows/
    ci.yml                Lint, build, PHP syntax check, block validation
    release.yml           Build zip + create GitHub Release on tag push
```

### Branch-strategi

| Branch | Syfte |
|--------|-------|
| `main` | Produktion — CI körs vid push, release vid tag |
| `feature/[namn]` | Ny funktionalitet |
| `fix/[namn]` | Buggfix |

Merge alltid via Pull Request till `main`. Pusha aldrig direkt till `main`.

### Commit-konventioner

Vi använder [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: lägg till quiz-block med mediabakgrund
fix: korrigera masonry-paginering på mobil
chore: uppdatera @wordpress/scripts till v30
refactor: bryt ut template-loader till helpers.php
```

### Releasing a new version

1. Bump the version in `goodblocks.php` (both the header and `GOODBLOCKS_VERSION`)
2. Commit and push to `main`
3. Tag and push: `git tag v1.x.x && git push origin v1.x.x`
4. The release workflow builds the zip and publishes a GitHub Release automatically
5. WordPress sites with the plugin installed will see the update within 12 hours (or immediately via **Dashboard > Updates > Check again**)

> **Note:** Sites running versions older than 1.1.0 (before the auto-updater was added) need a one-time manual upload of the latest zip.

---

## Requirements

- **WordPress** 6.4 or later
- **PHP** 8.0 or later

---

## License

GPL-2.0-or-later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).

Built by [AGoodId](https://agoodid.se).
