# Technical Standards & Architecture

Foundational technical requirements and architectural patterns for Cake Shop development.

## Language & Environment

- **PHP Version**: 7.4 or higher (strict compatibility)
- **Autoloading**: PSR-4 namespace `CakeShop\` maps to `src/` directory
- **Framework**: No framework—pure PHP with dependency injection
- **Database**: MySQL 5.7+ or Docker MySQL container
- **Web Server**: Apache 2.4 (Docker) or PHP built-in dev server

## Code Style Requirements

### Naming Conventions

| Element | Style | Example | Rule |
|---------|-------|---------|------|
| Class | PascalCase | `AuthService`, `ProductService` | One class per file |
| Method | snake_case | `get_user()`, `validate_email()` | All lowercase, underscores between words |
| Property | camelCase | `$userId`, `$cartItems` | Lowercase first letter, no underscores |
| Constant | UPPER_SNAKE_CASE | `DB_HOST`, `MAX_RETRIES` | All uppercase, underscores |
| Variable | camelCase | `$productName`, `$emailAddress` | Temporary and local scope |
| Private Method | snake_case (leading underscore) | `_private_helper()` | Signals private intent |

### Code Formatting Standards

- **Indentation**: Spaces only (4 spaces per level); **never use tabs**
- **Line Length**: Keep under 100 characters where practical
- **Braces**: Opening brace on same line for control structures
  ```php
  if ($condition) {
      // code
  } else {
      // code
  }
  ```
- **Spacing**: Single blank line between methods, no blank lines within method bodies
- **PHP Tags**: Always use `<?php` and `?>` (never short tags `<?`)

### Documentation

**PHPDoc for Methods**:
```php
/**
 * Validates user credentials against stored hash.
 *
 * @param string $email User email address
 * @param string $password Plain text password
 * @return bool True if credentials valid, false otherwise
 */
public function validate_credentials($email, $password)
{
    // implementation
}
```

**Inline Comments**: Explain **why**, not **what** (code should be self-documenting)
```php
// VULNERABLE: String concatenation allows SQL injection
$sql = "SELECT * FROM products WHERE name LIKE '%{$term}%'";

// SECURE: Prepared statement with parameter binding
$sql = "SELECT * FROM products WHERE name LIKE ?";
```

## Database Layer

### Critical: Prepared Statements

**ALWAYS** use prepared statements with parameter binding. **NEVER** concatenate user input into SQL strings.

```php
// ✓ CORRECT: Using Database service
$sql = "SELECT * FROM users WHERE email = ? AND role = ?";
$user = $this->database->queryOne($sql, [$email, $role]);

// ✓ CORRECT: Named parameters
$sql = "SELECT * FROM products WHERE category_id = ? AND price > ?";
$products = $this->database->queryAll($sql, [$categoryId, $minPrice]);

// ❌ WRONG: Never do this
$sql = "SELECT * FROM users WHERE email = '$email'";
$user = $this->database->execute($sql);

// ❌ WRONG: String interpolation
$sql = "SELECT * WHERE id = {$id}";
```

### Database Service API

Location: `src/Services/Database.php`

```php
namespace CakeShop\Services;

class Database
{
    // Single database connection (PDO singleton)
    public static function getInstance($config)
    {
        // Returns singleton PDO instance
    }

    // Execute query, return rows
    public function queryAll(string $sql, array $params): array
    {
        // Execute prepared statement
        // Return array of associative arrays or empty array
    }

    // Execute query, return first row
    public function queryOne(string $sql, array $params): ?array
    {
        // Execute prepared statement
        // Return single row or null if not found
    }

    // Execute update/insert/delete
    public function execute(string $sql, array $params): int
    {
        // Execute prepared statement
        // Return number of affected rows
    }
}
```

## Service Layer Architecture

### Service Constructor Pattern

All services follow this dependency injection pattern:

```php
<?php

namespace CakeShop\Services;

class MyService
{
    private $database;
    private $config;

    public function __construct($config, $database)
    {
        $this->config = $config;
        $this->database = $database;
    }

    public function public_method($param)
    {
        // Business logic
    }

    private function _private_helper($data)
    {
        // Helper method (underscore signals private)
    }
}
```

### Service Responsibilities

- **Database Access**: All database interactions via `Database` service
- **Business Logic**: No business logic in pages; all logic in services
- **Error Handling**: Catch exceptions, return null/false, log errors
- **No State**: Services are stateless; create fresh instance per request

### Available Services

| Service | Location | Purpose |
|---------|----------|---------|
| `Database` | `src/Services/Database.php` | PDO wrapper with prepared statements |
| `AuthService` | `src/Services/AuthService.php` | User authentication & registration |
| `ProductService` | `src/Services/ProductService.php` | Product queries & search |
| `CartService` | `src/Services/CartService.php` | Shopping cart (session-based) |
| `ReviewService` | `src/Services/ReviewService.php` | Product reviews & ratings |

## Session Management

### Session Structure

After successful login, `$_SESSION` contains:

```php
$_SESSION = [
    'user_id' => 123,                  // Integer ID from users table
    'user_email' => 'test@example.com', // User email address
    'user_role' => 'customer',         // 'customer' or 'admin'
];
```

### Session Checking in Pages

```php
<?php
// Protect page with authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Protect admin-only pages
if ($_SESSION['user_role'] !== 'admin') {
    die('403 Access Denied');
}
?>
```

### Session Cleanup (Logout)

```php
public function logout()
{
    $_SESSION = [];              // Clear all session data
    session_destroy();           // Destroy session file
    setcookie('PHPSESSID', '', time() - 3600, '/'); // Delete cookie
}
```

## Page Structure Template

All pages in `public/pages/` follow this structure:

```php
<?php
// 1. Include autoloader and config
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';
$config = require_once dirname(dirname(__DIR__)) . '/config/config.php';

// 2. Instantiate services
$database = CakeShop\Services\Database::getInstance($config);
$authService = new CakeShop\Services\AuthService($config, $database);
$productService = new CakeShop\Services\ProductService($config, $database);

// 3. Check authentication if required
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// 4. Handle form submissions
$data = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $productService->search($_POST['query'] ?? '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cake Shop</title>
</head>
<body>
    <!-- 5. Render template with data -->
    <?php foreach ($data as $item): ?>
        <p><?php echo htmlspecialchars($item['name']); ?></p>
    <?php endforeach; ?>
</body>
</html>
```

## Error Handling

### Database Exceptions

```php
try {
    $result = $this->database->queryOne($sql, $params);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    return null; // Resume gracefully, don't expose error
}
```

### Output Escaping

Always escape output to prevent XSS:

```php
// Escape for HTML context
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// Escape for HTML attributes
echo 'value="' . htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8') . '"';

// Escape for JavaScript context
echo json_encode($userInput);
```

---

**See Also**: [workflow.md](workflow.md), [design-rules.md](design-rules.md)
