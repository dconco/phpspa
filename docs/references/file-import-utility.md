# File Import Utility

## Overview

The file import utility provides a secure way to handle file imports with validation and returns a rich `ImportedFile` object.

### Must Import Namespace

```php
<?php
use function Component\import;
use PhpSPA\Exceptions\AppException;
```

## Function Reference

### `import()`

```php
function import(string $file): ImportedFile
```

#### Description

Imports a file with validation and returns an `ImportedFile` object.

#### Example

```php
<?php

   $image = import('assets/profile.jpg');

   echo "<img src='{$image}'>";
```

##### Metadata Methods

| Method               | Returns | Description                 |
| -------------------- | ------- | --------------------------- |
| `getContentType()`   | string  | File MIME type              |
| `getContentLength()` | int     | Base64 content length       |
| `getOriginalSize()`  | int     | Original file size in bytes |
| `getLocation()`      | string  | Original file path          |
| `getFilename()`      | string  | Filename without path       |
| `getExtension()`     | string  | File extension              |
| `isImage()`          | bool    | Whether file is an image    |

##### Content Methods

| Method                 | Returns | Description                |
| ---------------------- | ------- | -------------------------- |
| `getRawContent()`      | string  | Decoded file content       |
| `getBase64Content()`   | string  | Base64 encoded content     |
| `saveAs(string $dest)` | bool    | Saves file to new location |

## Usage Examples

### Basic File Import

```php
<?php
use PhpSPA\Exceptions\AppException;
use function PhpSPA\Component\import;

try {
    $file = import('documents/report.pdf');
    echo 'File size: ' . $file->getOriginalSize() . ' bytes';
} catch (AppException $e) {
    error_log($e->getMessage());
}
```

### Image Handling

```php
<?php
$image = import('gallery/photo.jpg');

if ($image->isImage()) {
    echo '<img src="' . $image . '" 
         alt="' . htmlspecialchars($image->getFilename()) . '"
         class="img-fluid">';
}
```

### File Saving

```php
<?php
$imported = import('temp/upload.tmp');

if ($imported->saveAs('archive/' . $imported->getFilename())) {
    echo 'File archived successfully';
}
```

## Error Handling

Common error scenarios:

```php
<?php
try {
    // Attempt to import non-existent file
    $file = import('missing.png');
} catch (AppException $e) {
    echo $e->getMessage(); // "Unable to get file: missing.png"
}

try {
    // Attempt to import large file
    $file = import('large-video.mp4');
} catch (AppException $e) {
    echo $e->getMessage(); // "File too large to import: large-video.mp4"
}
```

## Best Practices

1. Always wrap imports in try-catch blocks
2. Verify file types before processing
3. Use `isImage()` for image-specific handling
4. Store large files directly rather than using data URIs
