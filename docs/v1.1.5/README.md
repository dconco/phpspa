# phpSPA v1.1.5 Documentation

This folder contains the complete documentation for phpSPA v1.1.5, featuring major performance improvements, enhanced security, and new component capabilities.

## 📁 Documentation Structure

- **[index.md](./index.md)** - Main overview and getting started guide
- **[1-compression-system.md](./1-compression-system.md)** - HTML/CSS/JS compression and minification
- **[2-php-js-integration.md](./2-php-js-integration.md)** - Enhanced PHP-JavaScript communication
- **[3-class-components.md](./3-class-components.md)** - Object-oriented component development
- **[4-method-chaining.md](./4-method-chaining.md)** - Fluent API configuration
- **[5-csrf-protection.md](./5-csrf-protection.md)** - Built-in security features
- **[6-migration-guide.md](./6-migration-guide.md)** - Step-by-step upgrade instructions

## 🚀 Key Features

### Performance
- **15-84% size reduction** with intelligent compression
- **Automatic Gzip compression** when supported
- **Smart JavaScript minification** with ASI protection

### Security
- **CSRF protection** with automatic token management
- **Enhanced function call security** (10x improvement)
- **Timing-safe validation** to prevent attacks

### Developer Experience
- **Method chaining** for cleaner configuration
- **Class components** for better organization
- **Direct PHP-JS integration** with `useFunction()`
- **Namespace support** for components

### Compatibility
- **Backward compatible** with v1.1.4
- **Breaking changes** clearly documented
- **Migration tools** and guides provided

## 🔗 Quick Links

- [🏠 Main Documentation](https://phpspa.readthedocs.io)
- [📝 GitHub Repository](https://github.com/dconco/phpspa)
- [💬 Discord Community](https://discord.gg/FeVQs73C)
- [🎬 YouTube Channel](https://youtube.com/@daveconco)

## 📋 Migration Checklist

- [ ] Update composer dependencies to v1.1.5
- [ ] Fix namespace imports (`phpSPA\Component` → `Component`)
- [ ] Remove `data-type` attributes from scripts/styles
- [ ] Update layout files (remove `__CONTENT__` placeholders)
- [ ] Enable compression for better performance
- [ ] Add CSRF protection to forms
- [ ] Test application functionality
- [ ] Consider converting to class components
- [ ] Implement method chaining for cleaner code

---

*For detailed migration steps, see the [Migration Guide](./6-migration-guide.md)*
