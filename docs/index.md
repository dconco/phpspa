---
hide:
  - navigation
  - toc
---

<div style="text-align: center; padding: 4rem 2rem 3rem;">
  <div style="display: inline-block; position: relative;">
    <h1 style="font-size: 4rem; font-weight: 900; margin: 0; line-height: 1.2;">
      <span style="background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; display: inline-block;">
        PhpSPA
      </span>
      <span style="font-size: 3rem; display: inline-block; animation: wave 2s ease-in-out infinite;">👋</span>
    </h1>
  </div>
  <p style="font-size: 1.75rem; color: var(--md-default-fg-color--light); max-width: 700px; margin: 1.5rem auto 0; font-weight: 500; line-height: 1.6;">
    Ready to build modern, reactive web UIs without leaving the comfort of PHP? <span style="color: var(--md-primary-fg-color); font-weight: 600;">You're in the right place.</span>
  </p>
</div>

<style>
@keyframes wave {
  0%, 100% { transform: rotate(0deg); }
  25% { transform: rotate(20deg); }
  75% { transform: rotate(-20deg); }
}
</style>

!!! tip "What is PhpSPA?"
    PhpSPA is a **component-based library** that brings the power and syntax of React to your PHP workflow. You build small, reusable components, and the library handles the magic of rendering them and updating the UI when their state changes.


[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=for-the-badge)](LICENSE)
[![Documentation](https://img.shields.io/badge/docs-read%20the%20docs-blue.svg?style=for-the-badge)](https://phpspa.tech)
[![GitHub stars](https://img.shields.io/github/stars/dconco/phpspa?style=for-the-badge&color=yellow)](https://github.com/dconco/phpspa)
[![PHP Version](https://img.shields.io/packagist/v/dconco/phpspa?style=for-the-badge&color=purple)](https://packagist.org/packages/dconco/phpspa)
[![Downloads](https://img.shields.io/packagist/dt/dconco/phpspa?style=for-the-badge&color=orange)](https://packagist.org/packages/dconco/phpspa)
[![PHP Tests](https://github.com/dconco/phpspa/actions/workflows/php-tests.yml/badge.svg)](https://github.com/dconco/phpspa/actions/workflows/php-tests.yml)

---

## :rocket: Quick Start

<div class="grid cards" markdown>

-   :material-download: **Installation**

    ---

    Get started with PhpSPA in seconds using Composer

    [:octicons-arrow-right-24: Install Now](installation.md)

-   :material-code-braces: **Core Concepts**

    ---

    Learn the fundamentals of App and Component

    [:octicons-arrow-right-24: Learn Basics](core-concepts.md)

-   :material-routes: **Routing**

    ---

    Master dynamic routing and navigation

    [:octicons-arrow-right-24: Explore Routing](routing/index.md)

-   :material-state-machine: **State Management**

    ---

    Build reactive UIs with useState and useEffect

    [:octicons-arrow-right-24: Manage State](hooks/use-state.md)

</div>

---

## :sparkles: Key Features

=== "Component-Based"

    Build your UI with small, reusable components just like React
    
    ```php
    <?php

    function Button() {
       return <<<HTML
         <button>Click Me</button>
       HTML;
    }
    ```

=== "Reactive State"

    Components automatically re-render when state changes
    
    ```php
    <?php

    $count = useState('count', 0);

    return "<button onclick='setState(\"count\", {$count} + 1)'>
       Count: {$count}
    </button>";
    ```

=== "Client-Side Routing"

    Navigate between pages without full page reloads
    
    ```php
    <Component.Link to="/about">About</Component.Link>
    ```

=== "PHP Functions from JS"

    Call PHP functions directly from JavaScript without APIs
    
    ```php
    <?php

    $greeter = useFunction(fn($name) => "Hello, $name!");

    const greeting = await {$greeter('name')};
    ```

---

!!! info "Documentation Overview"
    This documentation is designed to be **fast and straight to the point**. No long stories, just code. Let's dive in and start building something awesome. 🚀