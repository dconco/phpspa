# Client-Side useEffect ⚛️

!!! success "New in v2.0.4"
    :material-new-box: **Client-side useEffect hook** for side effects in JavaScript component scripts.

---

Manage side effects (like event listeners, timers, or DOM manipulation) and their cleanups in your component scripts.

## Usage

The hook accepts a **callback function** and an optional **dependency array**.

```javascript
useEffect(callback, dependencies?)
```

- **callback**: Function containing your side effect logic. It can optionally return a cleanup function.
- **dependencies**: Array of **state keys (strings)** or **arbitrary values/objects**. Strings map to `phpspa` state keys, while any other value is compared by reference. The effect re-runs when any resolved dependency changes.

## Real-World Example

```html
<script>
   // Initial state from PHP
   let currentCount = {$count};

   useEffect(() => {
      const btn = document.getElementById('counter-btn');

      const handleClick = async () => {
         currentCount++;
         // Update application state
         await phpspa.setState('counter', currentCount);
      };

      btn.addEventListener('click', handleClick);

      // Cleanup: remove listener when component unmounts or effect re-runs
      return () => btn.removeEventListener('click', handleClick);
   }, ['counter']); 
</script>
```

!!! tip "Dependency Control"
    - `['stateKey']`: Effect runs on mount and whenever the `stateKey` value in PhpSPA state changes.
    - `['stateKey', navToggle]`: Mix state keys with concrete values (DOM nodes, numbers, etc.). PhpSPA compares the **resolved values**, so the effect re-runs when the state changes or when the direct value reference changes.
    - `[]`: Effect runs **only once** on mount (perfect for initial setup).
    - `null` (or omitted): Effect runs on every render (usually not recommended).

## Mixing State and Direct Dependencies

```javascript
useEffect(() => {
   const nav = document.querySelector('[data-nav-links]')
   if (!nav) return

   const handler = event => {
      console.log('theme state is now', phpspaState.theme)
      nav.classList.toggle('hidden')
   }

   document.addEventListener('phpspa:theme-toggle', handler)
   return () => document.removeEventListener('phpspa:theme-toggle', handler)
}, ['theme', document.querySelector('[data-nav-links]')])
```

The hook tracks the `theme` state key under the hood and keeps the DOM node reference intact. The effect runs again only when the theme state value changes or when the DOM node reference changes (e.g., the layout is replaced).
