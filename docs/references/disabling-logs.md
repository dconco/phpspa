# 🔇 Disabling Logs

!!! info "New in v2.0.13"
    Silence the native compressor's informational startup and compression logs with `disableLogs()`.

The native compressor writes several informational messages to the error log (`error_log`) while it starts up and compresses output. In development these are useful; in production they add noise. `disableLogs()` lets you silence any combination of them.

---

## Usage

Call `disableLogs()` on your `App` instance, passing a bitmask of flags. It is chainable, so it fits naturally alongside `compression()` and the other configuration methods:

```php
<?php

use phpSPA\App;

$app = (new App($layout))
    ->disableLogs(DISABLE_PRELOAD_LOG | DISABLE_LIBRARY_LOG)
    ->compression(/* ... */);
```

Combine flags with the bitwise OR operator (`|`). Called with no argument, `disableLogs()` disables **all** log types:

```php
// Silence every compressor log
$app->disableLogs();
```

---

## Log Flags

Each flag silences one category of message. The flags are plain integer constants (a bitmask), so they can be OR-combined.

### `DISABLE_PRELOAD_LOG`

Silences the preload fallback notice:

```
phpspa_compressor isn't preloaded in PHP INI, falling back to loading directly from the shared library.
```

This is logged when the native compressor was **not** preloaded via FFI in your `php.ini` and the application falls back to loading it directly from the shared library. See [Native Compression](../performance/html-compression.md) for how to set up FFI preloading and remove the fallback.

### `DISABLE_LIBRARY_LOG`

Silences the library-location notices logged while the application finds and loads the native compressor library, e.g.:

```
Found native compressor library: C:\path\to\src\bin\compressor.dll
Using native compressor library from environment variable: /path/to/libcompressor.so
```

### `DISABLE_COMPRESSION_LOG`

Silences the per-compression summary logged after each successful HTML/asset compression:

```
Compressed HTML output within 0.79ms | 0s | From /
```

---

## API Reference

| Method | Parameters | Description |
|--------|-----------|-------------|
| `disableLogs(int $flags = DISABLE_COMPRESSION_LOG \| DISABLE_LIBRARY_LOG \| DISABLE_PRELOAD_LOG)` | `$flags` — bitmask of log types to disable | Disables the specified compressor logs. Chainable. Defaults to all three. |

### Flag Constants

| Constant | Value | Silences |
|----------|-------|----------|
| `DISABLE_COMPRESSION_LOG` | `1 << 0` (1) | Per-compression summary ("Compressed … output within …") |
| `DISABLE_PRELOAD_LOG` | `1 << 1` (2) | Preload fallback notice ("… isn't preloaded in PHP INI …") |
| `DISABLE_LIBRARY_LOG` | `1 << 2` (4) | Library-location notices ("Found native compressor library …") |

---

!!! tip "Related"
    See [Native Compression](../performance/html-compression.md) for configuring the compressor itself, including FFI preloading that removes the `DISABLE_PRELOAD_LOG` message at its source.
