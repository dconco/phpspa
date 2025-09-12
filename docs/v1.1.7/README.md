# phpSPA v1.1.7 Documentation

This folder contains the complete documentation for phpSPA v1.1.7, featuring enhanced global asset management and improved script/style injection capabilities.

## 📁 Documentation Structure

- **[index.md](./index.md)** - Main overview and getting started guide
- **[asset-cache-management.md](./asset-cache-management.md)** - Asset cache configuration with `App::assetCacheHours()`
- **[global-scripts-and-styles.md](./global-scripts-and-styles.md)** - Global script and stylesheet injection

## 🚀 Key Features

### Asset Management
- **Configurable asset caching** with `App::assetCacheHours()`
- **Session-only or time-based caching** for CSS/JS assets
- **Optimized cache management** for better performance

### Global Asset Injection
- **Global JavaScript injection** with `App::script()`
- **Global stylesheet injection** with `App::styleSheet()`
- **Application-wide asset management** for consistent styling and behavior
- **Method chaining support** for fluent configuration

### Developer Experience
- **Simple API** for global asset management
- **Callable-based injection** for dynamic content generation
- **Seamless integration** with existing phpSPA applications

## 🔗 Quick Links

- [🏠 Main Documentation](https://phpspa.readthedocs.io)
- [📝 GitHub Repository](https://github.com/dconco/phpspa)
- [💬 Discord Community](https://discord.gg/FeVQs73C)
- [🎬 YouTube Channel](https://youtube.com/@daveconco)

## 📋 Upgrade Checklist

- [ ] Update composer dependencies to v1.1.7
- [ ] Review asset caching strategy with new `assetCacheHours()` method
- [ ] Consider migrating repeated scripts/styles to global injection
- [ ] Test application functionality with new features
- [ ] Implement method chaining for cleaner configuration

---

*For detailed feature documentation, see the individual guide files in this directory*