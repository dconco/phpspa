# Response API Reference

The `Response` class provides a fluent interface for HTTP response management in PhpSPA. It combines powerful helper methods with chainable modifiers to make API development robust and expressive.

!!! info "Namespace"
    `PhpSPA\Http\Response`

---

## **Basic Usage**

### **Quick JSON Response**
```php
use PhpSPA\Http\Response;

// Return a standard JSON response
return Response::sendSuccess(['id' => 1], 'User created');
```

### **Fluent Chain Construction**
```php
return (new Response())
    ->status(200)
    ->header('X-Custom-Header', 'AppValue')
    ->data(['message' => 'Hello World']);
```

---

## **Response Types**

The `Response` class includes dedicated methods for common HTTP scenarios.

### **Success Responses**

| Method | Status | Description |
| :--- | :--- | :--- |
| `success($data, $message)` | `200` | Standard success response |
| `created($data, $message)` | `201` | Resource successfully created |
| `sendFile($path)` | `200` | Streams a file with auto-compression |

**Example:**
```php
// Standard success
return $response->success($user, 'User profile retrieved');

// File download with compression support
return $response->sendFile('/storage/reports/2024.pdf');
```

---

### **Error Responses**

| Method | Status | Description |
| :--- | :--- | :--- |
| `error($msg, $details)` | `---` | Generic error wrapper |
| `notFound($msg)` | `404` | Resource not found |
| `unauthorized($msg)` | `401` | Authentication required |
| `forbidden($msg)` | `403` | Access denied |
| `validationError($errors)` | `422` | Form validation failure |

**Example:**
```php
if (!$user) {
    return $response->notFound('User does not exist');
}

if ($input->fails()) {
    return $response->validationError($input->errors());
}
```

---

### **Special Responses**

#### **Redirects**
!!! warning "Exit Behavior"
    The `redirect()` method terminates script execution immediately.

```php
// Redirect to another URL
$response->redirect('/login', 302);
```

#### **Pagination**
Standardized pagination structure.

```php
return $response->paginate(
    items: $users, 
    total: 100, 
    perPage: 15, 
    currentPage: 1, 
    lastPage: 7
);
```

---

## **Modifiers**

Customize instances using chainable methods.

### `status(int $code)`
Set the HTTP status code.
```php
$response->status(Response::StatusTeapot); // 418
```

### `header(string $name, string $value)`
Set a single header.
```php
$response->header('Cache-Control', 'no-cache');
```

### `contentType(string $type)`
Set the MIME type.
```php
$response->contentType('application/xml');
```

---

## **Static Helpers**

Helpers to send responses immediately without `return`.

!!! tip "Immediate Execution"
    These methods construct the response, send headers, output content, and **exit** the application.

```php
// Send simple JSON
Response::sendJson(['foo' => 'bar']);

// Send formatted success
Response::sendSuccess($data, 'Operation complete');

// Send formatted error
Response::sendError('Database connection failed', 500);
```

---

## **Status Constants**

Use these constants for readable status codes.

| Constant | Value |
| :--- | :--- |
| `Response::StatusOK` | 200 |
| `Response::StatusCreated` | 201 |
| `Response::StatusBadRequest` | 400 |
| `Response::StatusUnauthorized` | 401 |
| `Response::StatusForbidden` | 403 |
| `Response::StatusNotFound` | 404 |
| `Response::StatusInternalServerError` | 500 |
