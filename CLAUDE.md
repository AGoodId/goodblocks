# GoodBlocks — Claude-kontext

## Deployment

Push till `main` → CI kör (build + lint + block-validering).
Tagga `v*` → Release-workflow bygger `goodblocks.zip` och skapar GitHub Release.
WordPress-sajten (`agoodid2026.agoodsite.se`) hämtar uppdateringen automatiskt via `GoodBlocks_GitHub_Updater` i `goodblocks.php`.

**För att deploya:** commit + push till `main`, tagga sedan med `git tag vX.Y.Z && git push --tags`.

## Block-namespace

Befintliga block: `goodblocks/*`
AGoodApp-integration: `agoodapp/*` (medvetet separat — kan brytas ut till eget plugin)
CI-validatorn tillåter båda.

## Kodkonventioner

- Blocks registreras via `goodblocks_register_blocks()` i `goodblocks.php`
- `agoodapp/*`-block registreras separat (inte via slug-loopen)
- REST-routes: namespace `goodblocks/v1`
- PHP 8.0+, WP 6.4+
- Build: `npm run build` via `@wordpress/scripts`, entry-points i `webpack.config.js`
