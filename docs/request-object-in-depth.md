## The Request Object: In-Depth

The `Request` object is your primary tool for interacting with incoming data. Beyond the basic methods like `post()` and `get()`, it offers several powerful shortcuts and helpers to make your code more concise and readable.

-----

### Universal Parameter Access

Instead of checking `post()`, `get()`, and `json()` separately, you can use the `Request` object like a function to get a parameter from **any** input source (`$_REQUEST`). This is the quickest way to access data.

```php
<?php
use phpSPA\Http\Request;

function SearchHandler(Request $request) {
   // This will get the 'term' value whether it comes from
   // a GET query string (?term=...) or a POST form field.
   $searchTerm = $request('term');

   // ... perform search logic ...
   return "<p>Showing results for: <strong>{$searchTerm}</strong></p>";
}
```

-----

### Handling File Uploads

Accessing uploaded files is simple with the `->files()` method.

  * Calling `$request->files()` with no arguments returns an array of all uploaded files.
  * Calling it with a name, like `$request->files('avatar')`, returns the data for that specific file input.

<!-- end list -->

```php
<?php
use phpSPA\Http\Request;

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
