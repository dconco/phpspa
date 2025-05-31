# 🔡 Route Case Sensitivity

phpSPA routes are **case-insensitive by default** — that means `/Login` and `/login` are treated the same.

But you can change that behavior globally or per component.

---

## 🌍 Global Setting

```php
$app->defaultToCaseSensitive();
```

This will make all routes case-sensitive unless overridden.

---

## 🔧 Per Component

You can override the case-sensitivity for individual components.

```php
$component->caseSensitive();     // Force sensitivity for this one
$component->caseInSensitive();   // Explicitly make this one case-insensitive
```

This is useful if:

* You want case-insensitivity generally, but a few routes must be strict.
* You want to allow `/Dashboard` and `/dashboard` to act differently.

---

## 🔎 Real-World Use Case

```php
$one = new Component('Admin');
$one->route("/Admin");
$one->caseSensitive();

$two = new Component('Admin');
$two->route("/admin");
$two->caseInSensitive(); // Optional here — it's default anyway
```

Without case sensitivity:

* `/Admin` and `/admin` would both go to the same component.

With case sensitivity enabled:

* Only exact-case matches will work.

---

➡️ Up next: [Setting Page Titles](./13-setting-page-titles.md)
