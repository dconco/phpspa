# PhpSPA + Vite Example

Modern development workflow for PhpSPA using Vite with Hot Module Replacement.

## Quick Start

```bash
# Install dependencies
composer install
cd frontend && npm install

# Development (two terminals)
npm run dev              # Terminal 1: Vite dev server
php -S localhost:8000    # Terminal 2: PHP server

# Production
npm run build            # Build assets to public/assets/
php -S localhost:8000    # Run PHP server
```

Visit `http://localhost:8000`

## How It Works

**Development**: PHP automatically detects Vite dev server and uses `http://localhost:5173` URLs with HMR.

**Production**: When Vite isn't running, PHP serves built assets from `public/assets/`.

No manual switching needed - it's automatic!

## Project Structure

```
frontend/src/main.js     # Import @dconco/phpspa here
frontend/index.html      # HTML template with Vite dev URLs
public/assets/           # Built files (npm run build)
src/*.php                # PhpSPA components
index.php                # Auto-detects dev/prod mode
```

## Key Benefits

✅ Instant HMR during development  
✅ Tree-shaking & optimization  
✅ TypeScript support (rename to .ts)  
✅ Standard frontend tooling  
✅ Auto dev/prod switching  

## Using TypeScript

```bash
cd frontend
npm install -D typescript
mv src/main.js src/main.ts
```

Update `vite.config.js` input to `src/main.ts` and you're done!

## Deployment

```bash
composer install --no-dev
cd frontend && npm ci && npm run build
```

Deploy everything except `node_modules/` and `frontend/src/`.

---

**Simple principle**: Write frontend in `frontend/src/`, build to `public/assets/`, let PhpSPA handle routing.
