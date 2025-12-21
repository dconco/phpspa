# Client-Side `useCallback`

!!! success "New in v2.0.5"
   :material-new-box: **Memoized callbacks** keep listener references stable even when PhpSPA re-renders a target.

---

Keep event handlers and other functions stable between renders so you do not attach duplicate listeners every time PhpSPA updates a target. `useCallback()` memoizes a function until one of its dependencies changes.

```javascript
const memoizedFn = useCallback(fn, dependencies?)
```

- **fn**: The callback you plan to pass to an event listener, child script, or another hook.
- **dependencies**: Optional array containing **state keys (strings)** and/or **direct values/objects**. PhpSPA resolves each string against its state store and keeps the DOM node reference intact, so the memoized function refreshes whenever the underlying state value changes or one of the direct dependencies gains a new reference.

## When to Use It

- Attaching DOM listeners once (e.g., `addEventListener` / `removeEventListener`).
- Passing callbacks to custom elements or scripts that expect a stable reference.
- Pairing with `useEffect()` cleanups that depend on referential equality.

## Example: Toggling the Docs Navigation

```typescript
const toggleNavLinks = () => {
   const navToggle = document.querySelector('[data-nav-toggle]')
   const navLinks = document.querySelector('[data-nav-links]')

   if (!navToggle || !navLinks) return

   const toggle = useCallback(() => {
      navLinks.classList.toggle('hidden')
   }, ['theme', navLinks])

   navToggle.addEventListener('click', toggle)
}
```

- `'theme'` refers to the `theme` key inside PhpSPA state. When `setState('theme', value)` runs, the memoized function is recreated.
- `navLinks` keeps the memoized function tied to the actual DOM node. If the layout swaps out the element, the dependency array detects the new reference and rebinds the handler automatically.

## Cleanup Tip

Pair `useCallback()` with `useEffect()` when you need to tear down listeners:

```javascript
useEffect(() => {
   navToggle.addEventListener('click', toggle)
   return () => navToggle.removeEventListener('click', toggle)
}, [toggle])
```

Because `toggle` stays stable until its dependencies change, the effect can safely remove and reattach the listener without leaking handlers.

## Full Example with `useCallback()` and `useEffect()` cleanup

```typescript
const toggleNavLinks = () => {
   const navToggle = document.querySelector('[data-nav-toggle]')
   const navLinks = document.querySelector('[data-nav-links]')

   if (!navToggle || !navLinks) return

   const toggle = useCallback(() => {
      navLinks.classList.toggle('hidden')
   }, ['theme', navLinks])

   useEffect(() => {
      navToggle.addEventListener('click', toggle)
      return () => navToggle.removeEventListener('click', toggle)
   }, [toggle])
}
```
