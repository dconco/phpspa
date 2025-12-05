# Contributing to PhpSPA

Thank you for your interest in contributing to PhpSPA! We welcome contributions from the community.

## Getting Started

1. **Fork the Repository**
   - Go to [https://github.com/dconco/phpspa](https://github.com/dconco/phpspa)
   - Click the "Fork" button to create your own copy of the repository

---

## How to Contribute

### Adding Components

PhpSPA components are located in `app/client/Component`. You can contribute by adding new hooks or components.

#### 1. Creating PhpSPA Hooks

Hooks are functions that start with `use` and provide reusable functionality.

- Navigate to `app/client/Component` in your forked repository
- Create a new hook file following the naming convention: `use_custom_hook.php`
- Implement your hook function, for example:
  ```php
  <?php
  namespace Component;

  function useCustom() {
      // Your hook implementation
      return "custom value";
  }
  ```

#### 2. Creating PhpSPA Components

Components are reusable UI elements that can be used with the `<Component.Custom />` syntax.

- Navigate to `app/client/Component` in your forked repository
- Create a new component file following the naming convention: `custom_component.php`
- Implement your component function, for example:
  ```php
  <?php
  namespace Component;

  function Custom($children) {
      // Your component implementation
      return <<<HTML
         <div class='custom'>{$children}</div>
      HTML;
  }
  ```

### Improving Documentation

Documentation is located in the `docs` folder and uses MkDocs format.

- Navigate to the `docs` folder in your forked repository
- Edit existing markdown files or create new ones
- Follow MkDocs markdown format and conventions
- Ensure proper formatting and structure
- Example documentation file structure:

```markdown
  # Page Title

  Brief introduction to the topic.

  ## Section Heading

  Content with code examples:

  ````php
  // Code example
  ````

  ## Another Section

  More content...
```

---

## Testing Your Changes

- Ensure your code works as expected
- Follow the existing code style and conventions
- Add appropriate documentation/comments
- Test thoroughly before submitting
- Run locally:

```bash
composer dumpautoload
composer test
```

## Submitting a Pull Request

1. Commit your changes to your forked repository
2. Create a pull request to the main repository
3. Provide a clear description of your changes and why they're useful

---

## Code Guidelines

- Follow the existing code style and naming conventions
- Use descriptive function and variable names
- Add comments to explain complex logic
- Test your code thoroughly before submitting

## Questions?

If you have any questions about contributing, feel free to open an issue on GitHub.

Thank you for contributing to PhpSPA!
