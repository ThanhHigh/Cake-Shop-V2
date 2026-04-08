# Workflow: Build, Test & Deployment

Development and deployment procedures for Cake Shop.

## Quick Start: Docker (Recommended)

```bash
# 1. Clone repository and navigate to project
cd /path/to/cake_shop_v2

# 2. Start containers (PHP 7.4 + MySQL 5.7)
docker-compose up

# 3. Seed database (in another terminal)
docker exec cake_shop_db mysql -u root -proot cake_shop < database/schema.sql

# 4. Visit application
# PHP runs on http://localhost:8080
# MySQL on localhost:3306
```

### Docker Services

| Service | Port | Purpose |
|---------|------|---------|
| `cake_shop_web` | 8080 | PHP 7.4-Apache web server |
| `cake_shop_db` | 3306 | MySQL 5.7 database |

**Stop Services**:
```bash
docker-compose down
```

## Quick Start: Manual (Local PHP)

```bash
# 1. Install PHP 7.4+ and MySQL locally
# On macOS: brew install php@7.4 mysql
# On Ubuntu: sudo apt install php7.4 mysql-server

# 2. Install Composer dependencies
composer install

# 3. Create environment file
cp .env.example .env

# 4. Initialize database
mysql -u root -p < database/schema.sql

# 5. Start PHP development server
cd public
php -S localhost:8000

# 6. Visit http://localhost:8000
```

## Composer Commands

Run from project root directory:

```bash
# Start development server with auto-reload
composer run server

# Run all PHPUnit tests
composer run test

# Run tests with verbose output
composer run test -- --verbose

# Run specific test file
composer run test -- tests/Services/ProductServiceTest.php

# Run specific test method
composer run test -- --filter=testSQLInjectionVulnerableMode

# Check PHP code standards (PSR-2/PSR-12)
composer run lint

# Automatically fix code style issues
composer run fix-styles

# Stop on first failure
composer run test -- --stop-on-failure

# Generate code coverage report
composer run test -- --coverage-html coverage/
```

## Testing

### Test Structure

```
tests/
├── Services/
│   ├── ProductServiceTest.php    # ProductService tests
│   ├── AuthServiceTest.php       # AuthService tests
│   ├── CartServiceTest.php       # CartService tests
│   └── DatabaseTest.php          # Database tests
└── Pages/
    ├── HomePageTest.php          # Integration tests
    └── LoginPageTest.php         # Authentication flow
```

### Run Tests

```bash
# All tests
vendor/bin/phpunit

# With verbose output (shows each test)
vendor/bin/phpunit --verbose

# Only Services directory
vendor/bin/phpunit tests/Services/

# Single file
vendor/bin/phpunit tests/Services/ProductServiceTest.php

# Single test method (uses regex filter)
vendor/bin/phpunit --filter=testSearchReturnsProducts

# Stop at first failure
vendor/bin/phpunit --stop-on-failure
```

### Test Pattern Example

```php
<?php

namespace CakeShop\Tests\Services;

use PHPUnit\Framework\TestCase;
use CakeShop\Services\ProductService;

class ProductServiceTest extends TestCase
{
    private $productService;

    protected function setUp(): void
    {
        // Initialize before each test
        $config = require __DIR__ . '/../../config/config.php';
        $this->productService = new ProductService($config, $database);
    }

    public function testSearchReturnsArray()
    {
        $results = $this->productService->search('cake');
        $this->assertIsArray($results);
    }

    public function testSearchWithSQLInjection()
    {
        if (isVulnerable('sql_injection')) {
            $dangerous = "' OR '1'='1";
            $results = $this->productService->search($dangerous);
            // Verify vulnerability is triggerable
            $this->assertGreater(count($results), 1);
        }
    }
}
```

## Code Linting

### PHP CodeSniffer

```bash
# Check all code for PSR-2/PSR-12 standards
composer run lint

# Check specific file
vendor/bin/phpcs src/Services/ProductService.php

# Try automatic fixes
composer run fix-styles

# Show specific standard
vendor/bin/phpcs --standard=PSR2 src/

# Show which files have issues (without details)
vendor/bin/phpcs --report=summary src/
```

### Common Issues & Fixes

| Issue | Command |
|-------|---------|
| Tabs vs spaces | `composer run fix-styles` |
| Line too long | Manual reformat to <100 chars |
| Missing docblock | Add PHPDoc comment |
| Incorrect naming | Rename per conventions |

## Health Check

Verify application is running correctly:

```bash
# HTTP endpoint (returns JSON)
curl http://localhost:8000/api/health

# Expected response (200 OK):
{
    "status": "ok",
    "version": "phase-2",
    "app_mode": "secure",
    "database": "connected",
    "php_version": "7.4.0"
}
```

## Database Management

### Reset Database

```bash
# Reset to fresh state (all data lost)
mysql -u root -p cake_shop < database/schema.sql

# With Docker
docker exec cake_shop_db mysql -u root -proot cake_shop < database/schema.sql
```

### Backup Database

```bash
# Dump to SQL file
mysqldump -u root -p cake_shop > backup_$(date +%Y%m%d).sql

# With Docker
docker exec cake_shop_db mysqldump -u root -proot cake_shop > backup.sql
```

### Access MySQL CLI

```bash
# Local
mysql -u root -p
use cake_shop;
SHOW TABLES;

# Docker
docker exec -it cake_shop_db mysql -u root -proot
use cake_shop;
SELECT * FROM users;
```

### Key Tables

| Table | Purpose | Columns |
|-------|---------|---------|
| `users` | User accounts | id, email, password_hash, role, created_at |
| `products` | Product catalog | id, name, price, category_id, created_at |
| `categories` | Categories | id, name, description |
| `reviews` | Customer feedback | id, product_id, user_id, rating, content |
| `audit_logs` | Security trail | id, user_id, action, details, ip_address |
| `cart_items` | Session carts | (Stored in $_SESSION, not DB) |

## Deployment Checklist

### Pre-Deployment Verification

- [ ] All tests passing: `composer run test`
- [ ] Code standards clean: `composer run lint`
- [ ] `APP_MODE` = `'secure'` in `.env`
- [ ] `DB_HOST` = `localhost` or `127.0.0.1` (enforced)
- [ ] `APP_URL` contains `localhost` or `127.0.0.1` (enforced)
- [ ] `.env` file NOT committed to repository
- [ ] Database backups in place
- [ ] Logs directory writable: `logs/`
- [ ] Session directory writable: `/var/lib/php/sessions`
- [ ] Vulnerability toggles set appropriately

### Production Safety (Enforced at Startup)

These checks run automatically on application start:

```php
// Check 1: Database must be localhost
if (DB_HOST not in ['localhost', '127.0.0.1']) {
    FATAL: "Database must bind to localhost"
}

// Check 2: APP_URL must be localhost
if (APP_URL doesn't contain 'localhost' or '127.0.0.1') {
    FATAL: "APP_URL must contain localhost"
}

// Check 3: APP_MODE must be valid
if (APP_MODE not in ['vulnerable', 'secure']) {
    FATAL: "APP_MODE must be exactly 'vulnerable' or 'secure'"
}
```

**Violations**: Application exits with fatal error (prevents accidental deployment).

## Environment Configuration

### .env File Structure

```ini
# Application
APP_MODE=secure                    # 'vulnerable' or 'secure'
APP_URL=http://localhost:8000

# Database
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=cake_shop
DB_USER=root
DB_PASSWORD=root

# Logging
LOG_LEVEL=info
LOG_PATH=logs/app.log
```

### Vulnerability Toggles

```ini
VULN_WEAK_AUTH=false               # MD5 vs BCrypt
VULN_SQL_INJECTION=false           # String concat vs prepared statements
VULN_VERBOSE_ERRORS=false          # Generic vs detailed errors
VULN_MISSING_HEADERS=false         # Security headers
VULN_WEAK_SESSION=false            # Session hardening
VULN_NO_RATE_LIMIT=false           # Rate limiting
VULN_MINIMAL_LOGGING=false         # Comprehensive logging
VULN_ACCESS_CONTROL=false          # Authorization checks
VULN_FILE_UPLOAD=false             # File upload validation
VULN_SSRF=false                    # URL validation
VULN_INTEGRITY=false               # Data verification
```

## Troubleshooting

| Problem | Solution |
|---------|----------|
| "Port 8080 in use" | Change port in docker-compose.yml or stop conflicting service |
| "Connection refused" | Verify MySQL running: `docker-compose ps` or `mysql -u root -p` |
| "Class not found" | Run `composer install` to regenerate autoloader |
| "Permission denied logs/" | `chmod 755 logs/` then `sudo chown www-data logs/` |
| "Session not persisting" | Check `/var/lib/php/sessions` writable by web server |
| "Tests fail" | Clear cache, check database: `mysql cake_shop < database/schema.sql` |

---

**See Also**: [technical-defaults.md](technical-defaults.md), [design-rules.md](design-rules.md)
