# The Request Object: In-Depth

<style>
code { background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); padding: 2px 6px; border-radius: 3px; }
</style>

The `Request` object is your primary tool for interacting with incoming data. Beyond the basic methods like `post()` and `get()`, it offers several powerful shortcuts and helpers to make your code more concise and readable.

!!! info "Universal Access"
    The Request object provides unified access to all types of input data.

## Universal Parameter Access

Instead of checking `post()`, `get()`, and `json()` separately, you can use the `Request` object like a function to get a parameter from **any** input source (`$_REQUEST`). This is the quickest way to access data.

```php
<?php
use PhpSPA\Http\Request;

function SearchHandler(Request $request) {
   // This will get the 'term' value whether it comes from
   // a GET query string (?term=...) or a POST form field.
   $searchTerm = $request('term');

   // ... perform search logic ...
   return "<p>Showing results for: <strong>{$searchTerm}</strong></p>";
}
```

!!! tip "Flexible Input"
    This will get the 'term' value whether it comes from a GET query string (?term=...) or a POST form field.

## Handling File Uploads

Accessing uploaded files is simple with the `->files()` method.

=== "All Files"

    Calling `$request->files()` with no arguments returns an array of all uploaded files.

    ```php
    $allFiles = $request->files();
    ```

=== "Specific File"

    Calling it with a name returns the data for that specific file input.

    ```php
    $avatarFile = $request->files('avatar');
    ```

!!! example "File Upload Handler"
<?php
use PhpSPA\Http\Request;

function ProfileUpload(Request $request) {
   if ($request->isMethod('POST')) {
      $avatarFile = $request->files('avatar');

      if ($avatarFile && $avatarFile['error'] === UPLOAD_ERR_OK) {
         // A file was successfully uploaded
         $tmpName = $avatarFile['tmp_name'];
         $fileName = basename($avatarFile['name']);
         move_uploaded_file($tmpName, "uploads/{$fileName}");

         return "<p>File uploaded successfully!</p>";
      }
   }

   return <<<HTML
      <form method="POST" enctype="multipart/form-data">
         <input type="file" name="avatar">
         <button type="submit">Upload</button>
      </form>
   HTML;
}
```

!!! success "Secure Upload"
    Always validate file uploads on the server-side before processing them.
