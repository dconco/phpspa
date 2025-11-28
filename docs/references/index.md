# API References

<div class="grid cards" markdown>

-   :material-book-open-variant:{ .lg .middle } __Complete Documentation__

    ---

    Comprehensive guides for all PhpSPA APIs and utilities

-   :material-code-tags:{ .lg .middle } __Code Examples__

    ---

    Practical examples for every feature

-   :material-lightbulb-on:{ .lg .middle } __Best Practices__

    ---

    Learn the recommended patterns and techniques

-   :material-update:{ .lg .middle } __Always Current__

    ---

    Updated documentation for the latest version

</div>

---

## :material-webhook: HTTP & Networking

<div class="grid cards" markdown>

-   :material-api:{ .lg .middle } **Response API**

    ---

    Build and customize HTTP responses with fluent API

    [:octicons-arrow-right-24: Learn more](https://phpspa.readthedocs.io/en/stable/references/response/)

-   :material-cloud-download:{ .lg .middle } **useFetch Hook** :material-new-box:

    ---

    Async HTTP client with parallel execution support

    [:octicons-arrow-right-24: Learn more](https://phpspa.readthedocs.io/en/stable/references/hooks/use-fetch/)

</div>

---

## :material-tools: Helpers & Utilities

<div class="grid cards" markdown>

-   :material-package-variant:{ .lg .middle } **fmt() Helper** :material-new-box:

    ---

    Type-safe prop passing with automatic serialization

    [:octicons-arrow-right-24: Learn more](https://phpspa.readthedocs.io/en/stable/references/helpers/fmt/)

-   :material-file-upload:{ .lg .middle } **File Import Utility**

    ---

    Secure file imports with validation and metadata

    [:octicons-arrow-right-24: Learn more](https://phpspa.readthedocs.io/en/stable/references/file-import-utility/)

</div>

---

## :material-speedometer: Performance

<div class="grid cards" markdown>

-   :material-speedometer:{ .lg .middle } **Native Compression (C++ FFI)**

    ---

    Enable lightning-fast HTML/CSS/JS minification with the C++ compressor

    [:octicons-arrow-right-24: Learn more](https://phpspa.readthedocs.io/en/stable/references/compression/)

</div>

---

## :material-view-grid: Browse by Category

=== "Hooks"

    | Hook | Description | Version |
    |------|-------------|---------|
    | [`useFetch()`](https://phpspa.readthedocs.io/en/stable/references/hooks/use-fetch/) | Async HTTP client with parallel execution | v2.0.1 |
    | [`useState()`](../hooks/use-state.md) | Reactive state management | v2.0.0 |
    | [`useEffect()`](../hooks/use-effect.md) | Side effects and lifecycle hooks | v2.0.0 |
    | [`useFunction()`](../hooks/use-function.md) | Call PHP functions from JavaScript | v1.1.5 |

=== "Helpers"

    | Helper | Description | Version |
    |--------|-------------|---------|
    | [`fmt()`](https://phpspa.readthedocs.io/en/stable/references/helpers/fmt/) | Type preservation for component props | v2.0.1 |
    | [`import()`](https://phpspa.readthedocs.io/en/stable/references/file-import-utility/) | Secure file imports | v1.1.0 |
    | [`response()`](https://phpspa.readthedocs.io/en/stable/references/response/) | HTTP response builder | v1.1.8 |
    | [`router()`](https://phpspa.readthedocs.io/en/stable/references/response/#response-api-examples) | Router instance access | v1.1.8 |

=== "Components"

    | Component | Description | Version |
    |-----------|-------------|---------|
    | [`<Component.Link>`](../navigations/link-component.md) | Client-side navigation | v1.1.0 |
    | [`<Component.Csrf>`](../security/csrf-protection.md) | CSRF token management | v1.1.5 |
    | [`<Component.Navigate>`](../navigations/navigate-component.md) | Programmatic navigation | v1.1.0 |

=== "Performance"

    | Feature | Description | Version |
    |---------|-------------|---------|
    | [Native Compression (C++ FFI)](https://phpspa.readthedocs.io/en/stable/references/compression/) | Lightning-fast HTML/CSS/JS minification | v2.0.3 |
