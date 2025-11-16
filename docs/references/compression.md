# ⚡ Native Compression (C++ FFI)

!!! success "New in v2.0.3"
    :material-new-box: Native C++ compressor for lightning-fast HTML/CSS/JS minification

> **Supercharge your app:**
> Use the C++ compressor for the fastest HTML/CSS/JS minification in PhpSPA. No extra PHP dependencies—just native speed!

---

## Quick Setup

### 1. Enable FFI in `php.ini`
```ini
ffi.enable=true
extension=ffi
```

### 2. (Optional) Custom DLL/SO Location
By default, PhpSPA auto-detects your compressor library. Only set this ENV if you use a custom path:

```bash
export PHPSPA_COMPRESSOR_LIB="/absolute/path/to/compressor.dll"
```

### 3. Force Native Compression (Optional)
To always use the native compressor:
```bash
export PHPSPA_COMPRESSION_STRATEGY=native
```

---

## How It Works
- **Auto-detect:** PhpSPA finds the PhpSPA Compression DLL/SO automatically.
- **Manual override:** Set `PHPSPA_COMPRESSOR_LIB` to use a specific library file.
- **Strategy:** Set `PHPSPA_COMPRESSION_STRATEGY` to `native` to require native, or leave unset for auto-fallback.
- **Verification:** Check for `X-PhpSPA-Compression-Engine: native` in your HTTP response headers.

---

## Troubleshooting
- **FFI errors?** Make sure `ffi.enable=true` in the correct `php.ini` (see `phpinfo()` for the loaded config file).
- **DLL not found?** Set `PHPSPA_COMPRESSOR_LIB` to the absolute path of your DLL/SO.
- **Fallback used?** If native fails, PhpSPA will use the PHP fallback unless you force native mode.

---

## Example Response Header
```http
X-PhpSPA-Compression-Engine: native
```

---

## Learn More & Enable Compression

- [Full Compression Guide](https://phpspa.readthedocs.io/en/stable/performance/html-compression/) — How PhpSPA compression works, best practices, and advanced options.

### Enable Compression in Your App

```php
use PhpSPA\Compression\Compressor;

$app->compression(Compressor::LEVEL_AGGRESSIVE, true);
```

### Programmatic API

Two handy static helpers are available when you need programmatic control or diagnostics:

- `Compressor::compressWithLevel(string $html, int $level): string` — Minify an HTML string using the given compression level (returns the minified string; does not set response headers).
- `Compressor::getCompressionEngine(): string` — Returns which engine handled the last compression call: `native`, `php`, or `disabled`.

Example:

```php
use PhpSPA\Compression\Compressor;

$html = '<div>\n  <span> Hello </span>\n</div>';

// Programmatically compress at AGGRESSIVE level
$compressed = Compressor::compressWithLevel($html, Compressor::LEVEL_AGGRESSIVE);

// Check which engine handled the previous compression
$engine = Compressor::getCompressionEngine(); // 'native' or 'php' or 'disabled'

echo "Engine used: $engine\n";
echo $compressed;
```

Note: `compressWithLevel()` uses the same strategy (native vs fallback) as the normal runtime compressor; if `PHPSPA_COMPRESSION_STRATEGY` is set to `native` and the native library fails to load, an exception will be thrown.

---

<div align="center">
  <img src="https://raw.githubusercontent.com/dconco/dconco/refs/heads/main/phpspa-icon.jpg" width="120" style="border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); margin: 1rem 0;" />
  <br>
  <strong>PhpSPA Native Compression</strong>
  <br>
  <em>Lightning-fast, zero-overhead minification for modern PHP apps</em>
</div>
