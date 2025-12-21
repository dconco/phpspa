# PhpSPA + Vite Starter

Opinionated PhpSPA starter that shows how to compose PHP-first layouts, hydrate them with Vite + Tailwind, and keep SEO-friendly
rendering without rebuilding your routing layer.

## Highlights

- PHP renders every first request, PhpSPA hydrates subsequent navigations with scroll/state preservation.
- Vite 5 + Tailwind v4 handle the asset pipeline with instant HMR and production manifest output.
- `Component::meta()` already wires OpenGraph + description tags for Home and About pages.
- Highlight.js and debug hooks demonstrate how to add client helpers inside `src/main.ts`.
- `app/layout/layout.php` swaps Vite dev URLs for manifest assets automatically—no manual toggles.

## Requirements

- PHP 8.4+
- Composer 2+
- Node 18+ with [pnpm](https://pnpm.io/) (or npm/yarn if you update the scripts)

## Quick Start (Development)

```bash
cd vite-template
composer install        # Installs PhpSPA runtime
pnpm install            # Installs Vite + frontend deps

# Terminal 1 – Vite + HMR
pnpm dev

# Terminal 2 – PHP server
php -S localhost:8000 index.php
```

Open http://localhost:8000. The layout helper pings http://localhost:5173 to detect HMR mode and injects the correct scripts.

## Production Build

```bash
pnpm build                          # emits public/assets/.vite/manifest.json
php -S localhost:8000 index.php     # or deploy under Apache/Nginx
```

When the Vite server is not running, `app/layout/layout.php` uses the manifest to link hashed JS/CSS files automatically.

## Available Scripts

| Command        | Description |
| -------------- | ----------- |
| `pnpm dev`     | Run Vite in dev mode with HMR on port 5173. |
| `pnpm build`   | Production build to `public/assets/`. |
| `pnpm preview` | Preview the prod build served by Vite. |
| `pnpm watch`   | Continuous build (useful for backend-only servers). |

## Project Layout

```
app/
	components/          # Shared view fragments (HomeComponents, AboutComponents)
	layout/layout.php    # Detects dev vs prod assets
	pages/               # Each Component registered with PhpSPA
index.php              # Boots PhpSPA App, attaches pages, serves static assets
index.html             # Base template consumed by layout.php
public/assets/         # Build artifacts from Vite
src/                   # Frontend runtime (main.ts, helpers, styles)
vite.config.ts         # Tailwind + PhpSPA-friendly Vite config
```

## How It Fits Together

1. `index.php` boots `PhpSPA\App`, attaches the Home/About components, and exposes `/public/assets` for built files.
2. `app/layout/layout.php` reads `index.html`, checks if the dev server is live, and swaps the correct scripts/styles.
3. `app/pages/*.php` define each route with `->route()`, titles, and meta tags. They render server-side before hydration.
4. `src/main.ts` registers Highlight.js, wires debug hooks (`registerDebugHooks`), and boots `@dconco/phpspa` on the client.

## Extending the Starter

- Add new pages by creating a component in `app/pages`, then `require_once` it in `index.php` and call `$app->attach(...)`.
- Share data or listen to navigation events via `useState`, `useEffect`, and `phpspa.on('beforeload'|'load')`.
- Drop Tailwind utilities directly into the PHP components; Vite handles tree-shaking during `pnpm build`.
- Customize SEO metadata with the fluent `->meta()` API already used on the Home and About pages.

Happy building! Let PhpSPA handle routing, hydration, and compression while Vite keeps the frontend fast.
