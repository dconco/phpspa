<div align="center">

# 🚀 PhpSPA + Vite Starter

**A PhpSPA boilerplate that serves PhpSPA pages first, then hydrates navigation with Vite + TypeScript + Tailwind.**

[![PHP Version](https://img.shields.io/badge/PHP-8.4%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Node Version](https://img.shields.io/badge/Node-18%2B-339933?logo=node.js&logoColor=white)](https://nodejs.org/)
[![Vite](https://img.shields.io/badge/Vite-5.0-646CFF?logo=vite&logoColor=white)](https://vitejs.dev/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-v4-38B2AC?logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)

[**🌐 Live Preview**](https://phpspa-client.up.railway.app) • [Documentation](https://phpspa.tech)

</div>

---

## ✨ What You Get

<table>
<tr>
<td width="50%">

**🎯 Zero Config**
- PHP renders first request
- PhpSPA handles client nav
- Auto asset switching

</td>
<td width="50%">

**⚡ Lightning Fast**
- Vite HMR < 50ms
- Instant page transitions
- Production optimized

</td>
</tr>
<tr>
<td width="50%">

**🎨 Modern Stack**
- Tailwind v4 utilities
- TypeScript ready
- SEO-friendly meta tags

</td>
<td width="50%">

**📦 Everything Included**
- Component system
- State management
- Syntax highlighting

</td>
</tr>
</table>

---

## 📦 Installation

```bash
composer create-project phpspa/client my-project
cd my-project
```

---

## 🎯 Quick Start

### Step 1: Start Development Servers

```bash
# Terminal 1 – Vite dev server with HMR
pnpm dev

# Terminal 2 – PHP built-in server
php -S localhost:8000 index.php
```

Open **http://localhost:8000** in your browser.

---

### ⚠️ IMPORTANT: Development Script Tags

<table>
<tr>
<td>

> **🔴 CRITICAL STEP** – This starter supports a Vite-dev workflow by loading Vite’s HTML when the dev server is running.

**To enable Vite HMR during development, add these two lines to `index.html` before `</body>`:**

```html
<script type="module" src="http://localhost:5173/@vite/client"></script>
<script type="module" src="http://localhost:5173/src/main.ts"></script>
```

</td>
</tr>
</table>

#### What each script does:

| Script | Purpose |
|--------|---------|
| `@vite/client` | 🔥 Connects browser to Vite HMR server (port 5173) for instant hot module updates |
| `src/main.ts` | 🎬 Your app entry point: imports styles, registers hooks, boots `@dconco/phpspa` runtime |

> 💡 **Pro tip:** Leave these in during dev, remove them before deploying to production.

#### How the layout decides what to serve

- In [app/layout/layout.php](app/layout/layout.php) we first try to fetch the Vite dev server (default `http://localhost:5173`).
- If the dev server is **reachable**, we use its HTML (HMR works).
- If it’s **not reachable**, we fall back to `index.html` and load the production assets from `public/assets/.vite/manifest.json`.

#### Changing the Vite dev server URL

If your Vite server is not `http://localhost:5173` (different host/port), you must update **both**:

- `index.html` (the two `<script type="module">` URLs)
- [app/layout/layout.php](app/layout/layout.php) (`$viteDevOrigin`)

---

## 🏗️ Production Build

### Step 1: Build Assets

```bash
pnpm build
```

This generates optimized, hashed assets in `public/assets/` and creates the Vite manifest.

### Step 2: Deploy

```bash
php -S localhost:8000 index.php
# Or deploy to Apache/Nginx
```

When dev scripts are missing, `app/layout/layout.php` automatically loads manifest assets.

### Production recommendation

Set `APP_ENV=production` in your environment.

- This disables the dev-server probe entirely (no extra network call in production).
- The layout always loads the manifest assets.

---

## 📜 Available Scripts

| Command | Description |
|---------|-------------|
| `pnpm dev` | 🔧 Start Vite dev server with HMR on port 5173 |
| `pnpm build` | 📦 Production build to `public/assets/` |
| `pnpm preview` | 👀 Preview production build served by Vite |
| `pnpm watch` | 👁️ Continuous build mode for backend-only servers |

---

## 🎨 How to Extend

<table>
<tr>
<td>

**Add New Pages**
```php
// app/pages/contact.php
return new Component(fn () => '<h1>Contact</h1>')
    ->route('/contact')
    ->title('Contact Us')
```

Then attach in `index.php`:
```php
$app->attach(require 'app/pages/contact.php');
```

</td>
<td>

**Add Client Hooks**
```typescript
// src/main.ts
import { useEffect, setState } from '@dconco/phpspa';

useEffect(() => {
    console.log('Page loaded!');
}, []);
```

</td>
</tr>
</table>

---

## 🤝 Contributing

Issues and PRs welcome! Visit [phpspa.tech](https://phpspa.tech) for full documentation.

---

<div align="center">

**Built with ❤️ using PhpSPA + Vite**

[Documentation](https://phpspa.tech) • [GitHub](https://github.com/dconco/phpspa) • [NPM Package](https://www.npmjs.com/package/@dconco/phpspa)

</div>
