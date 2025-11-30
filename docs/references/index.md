# API References

<div class="grid cards" markdown>

-   :material-package-variant:{ .lg .middle } **fmt() Helper** :material-new-box:

    ---

    Type-safe prop passing with automatic serialization

    [:octicons-arrow-right-24: Learn more](https://phpspa.readthedocs.io/en/stable/references/helpers/fmt/)

-   :material-file-upload:{ .lg .middle } **File Import Utility**

    ---

    Secure file imports with validation and metadata

    [:octicons-arrow-right-24: Learn more](https://phpspa.readthedocs.io/en/stable/references/file-import-utility/)

-   :material-format-title:{ .lg .middle } **DOM Utilities** :material-new-box:

    ---

    Dynamic page title manipulation

    [:octicons-arrow-right-24: Learn more](https://phpspa.readthedocs.io/en/stable/references/dom-utilities/)

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
    | [`DOM::Title()`](https://phpspa.readthedocs.io/en/stable/references/dom-utilities/) | Get or set page title dynamically | v2.0.4 |

=== "Components"

    | Feature | Description | Version |
    |---------|-------------|---------|
    | [Component Preloading](https://phpspa.readthedocs.io/en/stable/references/preloading-component/) | Multi-section layouts with independent updates | v2.0.4 |
    | [`<Component.Link>`](../navigations/link-component.md) | Client-side navigation | v1.1.0 |
    | [`<Component.Csrf>`](../security/csrf-protection.md) | CSRF token management | v1.1.5 |
    | [`<Component.Navigate>`](../navigations/navigate-component.md) | Programmatic navigation | v1.1.0 |

=== "Performance"

    | Feature | Description | Version |
    |---------|-------------|---------|
    | [Native Compression (C++ FFI)](https://phpspa.readthedocs.io/en/stable/references/compression/) | Lightning-fast HTML/CSS/JS minification | v2.0.3 |
    | [HTML Compression](../performance/html-compression.md) | Enable compression for faster page loads | v1.1.5 |
    | [Assets Caching](../performance/assets-caching.md) | Configure asset caching duration | v1.1.7 |
    | [Managing Styles & Scripts](../performance/managing-styles-and-scripts.md) | Global and component-level asset management | v1.1.7 |
