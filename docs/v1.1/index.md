# Version 1.1 Highlights

## ✨ New Features

### Core Components

- **HTML Tag Components**  
  `<Component />` syntax for native HTML integration  
  → [Documentation](./3-using-component-functions-by-html-tags.md)

- **Enhanced `<Link>`**  
  Namespaced as `<PhpSPA.Component.Link>` with `to` attribute  
  → [Documentation](./5-link-component.md)

- **Navigation Component**  
  `<Navigate to="/path" state="push" />` for SPA routing  
  → [Documentation](./8-navigate-component.md)

### Utilities

- **File Import**  
  `phpSPA\Component\import()` for asset management  
  → [Documentation](./1-file-import-utility.md)

- **Attribute Converter**  
  `HTMLAttrInArrayToString()` for prop handling  
  → [Documentation](./6-html-attr-in-array-to-string-function.md)

- **State Mapping**  
  `$state->map()` for array-to-HTML transformation  
  → [Documentation](./2-mapping-in-state-management.md)

### HTTP Tools

- **Redirect Function**  
  `Redirect('/path', 301)` with status code support  
  → [Documentation](./7-redirect-function.md)

## ⚠️ Breaking Changes

- `<Link>` component now requires full namespace:  

  ```diff
  - <Link to="/old" label="Button" />
  + <PhpSPA.Component.Link to="/new" children="Button" />
  ```

  [Migration Guide](./5-link-component.md#deprecated)

## 📚 Full Documentation

| Feature             | Description              | Link                                                                                      |
| ------------------- | ------------------------ | ----------------------------------------------------------------------------------------- |
| File Import         | Image/asset handling     | [File Import Utility](./1-file-import-utility.md)                                         |
| State Mapping       | Array-to-HTML conversion | [State Management - map()](./2-mapping-in-state-management.md)                            |
| HTML Components     | Tag-based syntax         | [Using Component Functions via HTML Tags](./3-using-component-functions-by-html-tags.md)  |
| Component Basics    | Tag-based syntax example | [Component Baics](./4-component-basics.md)                                                |
| Link Component      | Enhanced SPA links       | [Link Component](./5-link-component.md)                                                   |
| Attribute Converter | Props to HTML string     | [HTML Attribute Array to String Conversion](./6-html-attr-in-array-to-string-function.md) |
| Redirects           | HTTP navigation          | [Redirect Function](7-redirect-function.md)                                               |
| SPA Navigation      | Client-side routing      | [Navigate Component](./8-navigate-component.md)                                           |

---

> **Tip**: Use namespace prefixes for all components in v1.1+  
> Example: `<PhpSPA.Component.Link>`, `<PhpSPA.Component.Navigate>`
