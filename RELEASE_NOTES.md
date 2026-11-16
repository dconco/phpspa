# v2.1.8 — Strict Component Tag Isolation, UIComponent Interface & Cryptographic Key Derivation

> [!IMPORTANT]
> **SECURITY & ARCHITECTURE RELEASE**: This release introduces strict component isolation for PhpSPA's template engine, requires all class-based components to implement `PhpSPA\Interfaces\UIComponent`, enforces PascalCase on all component tag segments, and switches HMAC signing in `ComponentValueCodec` and `AssetLinkManager` to stateless keys derived from `APP_KEY`. All applications are encouraged to upgrade.

```bash
composer require dconco/phpspa:^2.1.8
```

---

## 🛡️ Security Hardening & Tag Isolation

- **`UIComponent` Marker Interface for Class Components**:
  Every class invoked as an HTML component tag (`<MyClass />` or `<MyClass::Method />`) must now implement `PhpSPA\Interfaces\UIComponent`. Arbitrary framework, third-party, or internal PHP classes (such as `HttpRequest`, `Session`, `PHPMailer`, etc.) can no longer be instantiated or executed via component tag formatting, permanently eliminating arbitrary class and method execution vectors.

- **Strict PascalCase Tag & Method Matching**:
  The component tag regex pattern now strictly requires PascalCase for every segment: `<Namespace.Class::Method />` and `<Class::Method />`. Tags containing lowercase namespaces or method names (e.g. `<Class::method />` or `<Namespace.class::Method />`) are excluded from component execution and remain untouched as regular markup.

- **Exact-Case Verification via Reflection**:
  Function components and class methods are now strictly validated against their exact declaration in PHP (`$reflection->getName() === $className`), completely blocking case-folding bypasses on lowercase built-in PHP functions (`<File_get_contents />`, `<Exec />`, etc.).

- **Dynamic HMAC Key Derivation in `ComponentValueCodec`**:
  HMAC keys used to sign and authenticate serialized component values are now derived dynamically using `hash_hmac('sha256', session_id(), APP_KEY, true)`. Secret signing keys are no longer stored in `$_SESSION`, eliminating session-storage leakage risks and ensuring stateless cryptographic verification.

- **Centralized `APP_KEY` Integration in `AssetLinkManager`**:
  Asset URL HMAC signatures now automatically source the encryption key from `APP_KEY` in the environment (`getenv('APP_KEY')` / `$_ENV['APP_KEY']`), with programmatic override via `AssetLinkManager::setSecretKey()`.

---

## 📋 Security & Architecture Breakdown

| Component / Area | Prior Behavior | Status in v2.1.8 |
|---|---|---|
| Class Component Tags | Any class with `__render` or static method | Required to implement `UIComponent` |
| Component Tag Methods | Permitted lowercase method names | Strict PascalCase (`<Class::Method />`) |
| Function Component Verification | Relied on case-insensitive `function_exists` | Exact-casing match (`$reflection->getName() === $name`) |
| Component Value HMAC Key | Stored in `$_SESSION['__phpspa_component_value_key']` | Statelessly derived via `hash_hmac(session_id(), APP_KEY)` |
| Asset Link Secret Key | Hardcoded default fallback key | Automatically sources `APP_KEY` environment variable |

---

## ⚠️ Breaking Changes

- **Class Components Must Implement `UIComponent`**:
  Any class used as an HTML component must declare `implements PhpSPA\Interfaces\UIComponent`.
- **Class Methods Must Use PascalCase**:
  Class methods invoked via `<Class::Method />` must be declared and called in PascalCase (e.g., `<Card::Header />` rather than `<Card::header />`).
