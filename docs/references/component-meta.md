# Component Meta Tags

!!! success "New in v2.0.5"
    Attach SEO metadata to any route directly from the component definition.

PhpSPA components can now describe their own `<meta>` tags through the fluent `->meta()` method. Each call accepts named attributes (e.g., `name`, `property`, `httpEquiv`) plus the `content` value. Tags are rendered only on the initial HTML response, so SPA navigations stay lightweight.

---

## Why use `->meta()`?

- Keep titles and metadata close to the component they describe
- Emit Open Graph, Twitter, or HTTP-EQUIV tags without editing the global layout
- Prevent duplication — PhpSPA deduplicates identical meta signatures before injecting them

---

## Basic Usage

```php
<?php

new Component(...)
   ->route('/')
   ->title('PhpSPA Design System')
   ->meta(name: 'description', content: 'Design-forward PhpSPA starter')
   ->meta(name: 'keywords', content: 'PhpSPA, PHP SPA, Tailwind, Vite');
```

Each `->meta()` call corresponds to one `<meta>` element. Only `content` (or `charset`) is required, but you can mix and match other attributes freely.

---

## Open Graph & HTTP-EQUIV Example

```php
<?php

new Component(...)
   ->route('/docs')
   ->meta(property: 'og:title', content: 'PhpSPA Docs')
   ->meta(property: 'og:description', content: 'Server-driven SPA workflow')
   ->meta(httpEquiv: 'refresh', content: '120')
   ->meta(charset: 'UTF-8');
```

!!! info "Initial render only"
    Meta tags are rendered server-side when the page first loads. Client-side navigations keep the existing head content unchanged unless another component defines new metadata.

---

## Custom Attributes

Need to attach bespoke attributes (e.g., `data-*` or `media`)? Pass them through the final `$attributes` argument.

```php
<?php

new Component(...)
   ->meta(name: 'twitter:image', content: 'https://cdn.example.com/cover.png', attributes: [
      'data-theme' => 'dark',
      'media' => '(prefers-color-scheme: dark)'
   ]);
```

Resulting markup:

```html
<meta name="twitter:image" content="https://cdn.example.com/cover.png" data-theme="dark" media="(prefers-color-scheme: dark)">
```
