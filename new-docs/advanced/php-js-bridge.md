# :bridge_at_night: PHP-JavaScript Bridge

Call **PHP functions directly from JavaScript** with phpSPA's revolutionary bridge system. Build interactive applications that seamlessly blend server and client-side logic.

---

## :magic_wand: **How It Works**

phpSPA creates secure, token-based bridges between PHP and JavaScript, allowing you to call server-side functions from client code with CSRF protection built-in.

### The Magic
```javascript
// JavaScript calls PHP function directly!
phpspa.__call('getUserData', { userId: 123 })
    .then(data => {
        console.log('User data from PHP:', data);
    });
```

---

## :gear: **Basic Usage**

### Create a Callable Function

```php title="PHP Function Setup"
<?php
use function Component\useFunction;

function UserDashboard() {
    // Create a callable PHP function
    $getUserData = useFunction(function($userId) {
        // This runs on the server
        $user = Database::findUser($userId);
        return [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar_url
        ];
    });
    
    return <<<HTML
        <div class="dashboard">
            <h2>User Dashboard</h2>
            <div id="userInfo">Loading...</div>
            
            <button onclick="loadUserData(123)">
                Load User Data
            </button>
            
            <script>
                async function loadUserData(userId) {
                    try {
                        const userData = await {$getUserData}(userId);
                        
                        document.getElementById('userInfo').innerHTML = \`
                            <h3>\${userData.name}</h3>
                            <p>\${userData.email}</p>
                            <img src="\${userData.avatar}" alt="Avatar">
                        \`;
                    } catch (error) {
                        console.error('Error loading user data:', error);
                    }
                }
            </script>
        </div>
    HTML;
}
```

### Multiple Function Calls

```php title="Multiple PHP Functions"
<?php
function ProductManager() {
    $getProducts = useFunction(function() {
        return Database::getAllProducts();
    });
    
    $addProduct = useFunction(function($name, $price, $category) {
        $product = new Product();
        $product->name = $name;
        $product->price = $price;
        $product->category = $category;
        return $product->save();
    });
    
    $deleteProduct = useFunction(function($productId) {
        return Product::find($productId)->delete();
    });
    
    return <<<HTML
        <div class="product-manager">
            <h2>Product Manager</h2>
            
            <button onclick="loadProducts()">Load Products</button>
            <button onclick="addNewProduct()">Add Product</button>
            
            <div id="productList"></div>
            
            <script>
                async function loadProducts() {
                    const products = await {$getProducts}();
                    displayProducts(products);
                }
                
                async function addNewProduct() {
                    const name = prompt('Product name:');
                    const price = prompt('Price:');
                    const category = prompt('Category:');
                    
                    if (name && price && category) {
                        const result = await {$addProduct}(name, parseFloat(price), category);
                        if (result) {
                            loadProducts(); // Refresh list
                        }
                    }
                }
                
                async function deleteProduct(id) {
                    if (confirm('Delete this product?')) {
                        await {$deleteProduct}(id);
                        loadProducts(); // Refresh list
                    }
                }
                
                function displayProducts(products) {
                    const html = products.map(product => \`
                        <div class="product">
                            <h3>\${product.name}</h3>
                            <p>Price: $\${product.price}</p>
                            <p>Category: \${product.category}</p>
                            <button onclick="deleteProduct(\${product.id})">Delete</button>
                        </div>
                    \`).join('');
                    
                    document.getElementById('productList').innerHTML = html;
                }
            </script>
        </div>
    HTML;
}
```

---

## :shield: **Security Features**

### Automatic CSRF Protection
Every function call includes CSRF protection:

```php title="Secure Function Calls"
<?php
function SecureTransfer() {
    $transferMoney = useFunction(function($fromAccount, $toAccount, $amount) {
        // Automatically protected by CSRF tokens
        
        // Validate user permissions
        if (!canUserAccessAccount($fromAccount)) {
            throw new Exception('Access denied');
        }
        
        // Perform secure transfer
        return BankService::transfer($fromAccount, $toAccount, $amount);
    });
    
    return <<<HTML
        <div class="money-transfer">
            <h2>Secure Money Transfer</h2>
            
            <form onsubmit="performTransfer(event)">
                <input type="text" id="fromAccount" placeholder="From Account">
                <input type="text" id="toAccount" placeholder="To Account">
                <input type="number" id="amount" placeholder="Amount">
                <button type="submit">Transfer</button>
            </form>
            
            <script>
                async function performTransfer(event) {
                    event.preventDefault();
                    
                    const fromAccount = document.getElementById('fromAccount').value;
                    const toAccount = document.getElementById('toAccount').value;
                    const amount = parseFloat(document.getElementById('amount').value);
                    
                    try {
                        // CSRF protection is automatic
                        const result = await {$transferMoney}(fromAccount, toAccount, amount);
                        alert('Transfer successful!');
                    } catch (error) {
                        alert('Transfer failed: ' + error.message);
                    }
                }
            </script>
        </div>
    HTML;
}
```

### Input Validation

```php title="Server-Side Validation"
<?php
function ValidatedForm() {
    $saveUser = useFunction(function($userData) {
        // Validate input on server
        $validator = new UserValidator();
        
        if (!$validator->validate($userData)) {
            throw new Exception('Invalid user data: ' . implode(', ', $validator->getErrors()));
        }
        
        // Sanitize data
        $userData['email'] = filter_var($userData['email'], FILTER_SANITIZE_EMAIL);
        $userData['name'] = htmlspecialchars($userData['name']);
        
        return User::create($userData);
    });
    
    return <<<HTML
        <div class="user-form">
            <form onsubmit="saveUserData(event)">
                <input type="text" id="name" placeholder="Full Name" required>
                <input type="email" id="email" placeholder="Email" required>
                <input type="tel" id="phone" placeholder="Phone">
                <button type="submit">Save User</button>
            </form>
            
            <script>
                async function saveUserData(event) {
                    event.preventDefault();
                    
                    const userData = {
                        name: document.getElementById('name').value,
                        email: document.getElementById('email').value,
                        phone: document.getElementById('phone').value
                    };
                    
                    try {
                        const user = await {$saveUser}(userData);
                        alert('User saved successfully!');
                    } catch (error) {
                        alert('Validation error: ' + error.message);
                    }
                }
            </script>
        </div>
    HTML;
}
```

---

## :rocket: **Advanced Patterns**

### Real-time Data Updates

```php title="Live Data Updates"
<?php
function LiveChat() {
    $getMessages = useFunction(function($chatId, $lastMessageId = 0) {
        return ChatService::getNewMessages($chatId, $lastMessageId);
    });
    
    $sendMessage = useFunction(function($chatId, $message, $userId) {
        return ChatService::sendMessage($chatId, $message, $userId);
    });
    
    return <<<HTML
        <div class="live-chat">
            <div id="messages"></div>
            
            <form onsubmit="sendMessage(event)">
                <input type="text" id="messageInput" placeholder="Type a message...">
                <button type="submit">Send</button>
            </form>
            
            <script>
                let chatId = 1;
                let userId = getCurrentUserId();
                let lastMessageId = 0;
                
                // Poll for new messages every 2 seconds
                setInterval(async function() {
                    try {
                        const newMessages = await {$getMessages}(chatId, lastMessageId);
                        
                        if (newMessages.length > 0) {
                            appendMessages(newMessages);
                            lastMessageId = Math.max(...newMessages.map(m => m.id));
                        }
                    } catch (error) {
                        console.error('Error fetching messages:', error);
                    }
                }, 2000);
                
                async function sendMessage(event) {
                    event.preventDefault();
                    
                    const messageInput = document.getElementById('messageInput');
                    const message = messageInput.value.trim();
                    
                    if (message) {
                        try {
                            await {$sendMessage}(chatId, message, userId);
                            messageInput.value = '';
                        } catch (error) {
                            alert('Failed to send message: ' + error.message);
                        }
                    }
                }
                
                function appendMessages(messages) {
                    const messagesDiv = document.getElementById('messages');
                    
                    messages.forEach(message => {
                        const messageDiv = document.createElement('div');
                        messageDiv.innerHTML = \`
                            <strong>\${message.username}:</strong> \${message.text}
                            <small>\${message.timestamp}</small>
                        \`;
                        messagesDiv.appendChild(messageDiv);
                    });
                    
                    messagesDiv.scrollTop = messagesDiv.scrollHeight;
                }
            </script>
        </div>
    HTML;
}
```

### File Upload with Progress

```php title="File Upload with PHP Backend"
<?php
function FileUploader() {
    $uploadFile = useFunction(function($fileData, $fileName) {
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($fileData['type'], $allowedTypes)) {
            throw new Exception('Invalid file type');
        }
        
        // Validate file size (max 5MB)
        if ($fileData['size'] > 5 * 1024 * 1024) {
            throw new Exception('File too large');
        }
        
        // Save file
        $uploadDir = '/uploads/';
        $filePath = $uploadDir . uniqid() . '_' . $fileName;
        
        if (move_uploaded_file($fileData['tmp_name'], $filePath)) {
            return ['success' => true, 'path' => $filePath];
        } else {
            throw new Exception('Upload failed');
        }
    });
    
    return <<<HTML
        <div class="file-uploader">
            <input type="file" id="fileInput" accept="image/*" onchange="uploadFile()">
            <div id="uploadProgress" style="display: none;">
                <div class="progress-bar">
                    <div id="progressFill" style="width: 0%; background: #4CAF50; height: 20px;"></div>
                </div>
                <span id="progressText">0%</span>
            </div>
            <div id="uploadResult"></div>
            
            <script>
                async function uploadFile() {
                    const fileInput = document.getElementById('fileInput');
                    const file = fileInput.files[0];
                    
                    if (!file) return;
                    
                    // Show progress
                    document.getElementById('uploadProgress').style.display = 'block';
                    
                    try {
                        // Simulate progress (in real app, you'd track actual upload progress)
                        for (let i = 0; i <= 100; i += 10) {
                            updateProgress(i);
                            await new Promise(resolve => setTimeout(resolve, 100));
                        }
                        
                        // Convert file to format PHP can handle
                        const fileData = {
                            name: file.name,
                            type: file.type,
                            size: file.size,
                            data: await fileToBase64(file)
                        };
                        
                        const result = await {$uploadFile}(fileData, file.name);
                        
                        document.getElementById('uploadResult').innerHTML = \`
                            <p>Upload successful!</p>
                            <img src="\${result.path}" style="max-width: 200px;">
                        \`;
                    } catch (error) {
                        document.getElementById('uploadResult').innerHTML = \`
                            <p style="color: red;">Upload failed: \${error.message}</p>
                        \`;
                    } finally {
                        document.getElementById('uploadProgress').style.display = 'none';
                    }
                }
                
                function updateProgress(percent) {
                    document.getElementById('progressFill').style.width = percent + '%';
                    document.getElementById('progressText').textContent = percent + '%';
                }
                
                function fileToBase64(file) {
                    return new Promise((resolve, reject) => {
                        const reader = new FileReader();
                        reader.onload = () => resolve(reader.result);
                        reader.onerror = reject;
                        reader.readAsDataURL(file);
                    });
                }
            </script>
        </div>
    HTML;
}
```

---

## :test_tube: **Error Handling**

### Graceful Error Management

```php title="Robust Error Handling"
<?php
function ErrorHandlingExample() {
    $riskyOperation = useFunction(function($data) {
        try {
            // Simulate operations that might fail
            if (empty($data['email'])) {
                throw new InvalidArgumentException('Email is required');
            }
            
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid email format');
            }
            
            // Simulate database operation
            if (rand(1, 3) === 1) {
                throw new RuntimeException('Database connection failed');
            }
            
            return ['success' => true, 'message' => 'Operation completed'];
            
        } catch (InvalidArgumentException $e) {
            throw $e; // Re-throw validation errors
        } catch (Exception $e) {
            error_log('System error: ' . $e->getMessage());
            throw new Exception('System temporarily unavailable');
        }
    });
    
    return <<<HTML
        <div class="error-handling-demo">
            <h2>Error Handling Demo</h2>
            
            <form onsubmit="testErrorHandling(event)">
                <input type="email" id="email" placeholder="Enter email">
                <button type="submit">Test Operation</button>
            </form>
            
            <div id="result"></div>
            
            <script>
                async function testErrorHandling(event) {
                    event.preventDefault();
                    
                    const email = document.getElementById('email').value;
                    const resultDiv = document.getElementById('result');
                    
                    try {
                        resultDiv.innerHTML = '<p>Processing...</p>';
                        
                        const result = await {$riskyOperation}({ email: email });
                        
                        resultDiv.innerHTML = \`
                            <div style="color: green;">
                                <h3>Success!</h3>
                                <p>\${result.message}</p>
                            </div>
                        \`;
                        
                    } catch (error) {
                        resultDiv.innerHTML = \`
                            <div style="color: red;">
                                <h3>Error</h3>
                                <p>\${error.message}</p>
                                <button onclick="retryOperation()">Retry</button>
                            </div>
                        \`;
                    }
                }
                
                function retryOperation() {
                    testErrorHandling({ preventDefault: () => {} });
                }
            </script>
        </div>
    HTML;
}
```

---

## :bulb: **Best Practices**

### Performance Optimization

```php title="Optimized Function Calls"
<?php
function OptimizedBridge() {
    // Cache expensive operations
    static $cache = [];
    
    $getCachedData = useFunction(function($key) use ($cache) {
        if (!isset($cache[$key])) {
            $cache[$key] = performExpensiveOperation($key);
        }
        return $cache[$key];
    });
    
    // Batch operations
    $batchProcess = useFunction(function($items) {
        // Process multiple items in one call instead of multiple calls
        $results = [];
        foreach ($items as $item) {
            $results[] = processItem($item);
        }
        return $results;
    });
    
    return <<<HTML
        <div class="optimized-bridge">
            <button onclick="loadCachedData()">Load Cached Data</button>
            <button onclick="batchProcess()">Batch Process</button>
            
            <script>
                // Good: Batch multiple operations
                async function batchProcess() {
                    const items = [1, 2, 3, 4, 5];
                    const results = await {$batchProcess}(items);
                    console.log('Batch results:', results);
                }
                
                // Good: Cache results client-side
                let cachedData = null;
                async function loadCachedData() {
                    if (!cachedData) {
                        cachedData = await {$getCachedData}('user_preferences');
                    }
                    console.log('Cached data:', cachedData);
                }
            </script>
        </div>
    HTML;
}
```

### Security Best Practices

```php title="Secure Implementation"
<?php
function SecureBridge() {
    $secureOperation = useFunction(function($data) {
        // 1. Validate user permissions
        if (!Auth::user()->hasPermission('admin')) {
            throw new Exception('Insufficient permissions');
        }
        
        // 2. Validate and sanitize input
        $validator = new InputValidator();
        $cleanData = $validator->sanitize($data);
        
        // 3. Use prepared statements for database
        $stmt = DB::prepare("INSERT INTO secure_table (data) VALUES (?)");
        $stmt->execute([$cleanData]);
        
        // 4. Don't expose sensitive information
        return ['status' => 'success', 'id' => $stmt->lastInsertId()];
    });
    
    return <<<HTML
        <div class="secure-demo">
            <script>
                async function secureOperation(data) {
                    try {
                        // Client-side validation (for UX, not security)
                        if (!data || typeof data !== 'object') {
                            throw new Error('Invalid data format');
                        }
                        
                        const result = await {$secureOperation}(data);
                        return result;
                        
                    } catch (error) {
                        // Don't expose sensitive error details to client
                        console.error('Operation failed:', error.message);
                        throw new Error('Operation failed');
                    }
                }
            </script>
        </div>
    HTML;
}
```

---

!!! success "PHP-JS Bridge Mastery!"
    You can now seamlessly bridge PHP and JavaScript! This opens up endless possibilities for building interactive, real-time applications with server-side logic accessible from the client. Explore more advanced features in the next sections.
