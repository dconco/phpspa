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
- **dependencies**: Array of state keys (strings). The effect re-runs only when one of these keys changes.

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
    - `['stateKey']`: Effect runs on mount and whenever `stateKey` changes.
    - `[]`: Effect runs **only once** on mount (perfect for initial setup).
    - `null` (or omitted): Effect runs on every single render (usually not recommended).
