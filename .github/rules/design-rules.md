# Design Rules: Architecture, Security & Learning

Architectural patterns, vulnerability design principles, and safe learning environment guidelines.

## Dual-Mode Architecture

Cake Shop implements a **teach-through-comparison** approach with two code paths:

```
Vulnerable Mode  ←→  Secure Mode  (toggled via APP_MODE in .env)
     ↓                    ↓
Intentional           Secure
Vulnerabilities       Implementations
```

### How It Works

All security-relevant features support dual modes via `isVulnerable()` helper:

```php
// In config.php
function isVulnerable($feature_name): bool
{
    $var_name = 'VULN_' . strtoupper($feature_name);
    return getenv($var_name) === 'true' || getenv($var_name) === '1';
}
```

### Implementation Pattern

```php
// In services: ProductService::search()
if (isVulnerable('sql_injection')) {
    // ❌ VULNERABLE: String concatenation opens SQL injection
    $sql = "SELECT * FROM products WHERE name LIKE '%{$term}%'";
} else {
    // ✓ SECURE: Prepared statement with parameter binding
    $sql = "SELECT * FROM products WHERE name LIKE ?";
    $results = $this->database->queryAll($sql, ["%{$term}%"]);
}
```

### Why Dual-Mode?

1. **Learning**: Students see vulnerable AND secure implementations side-by-side
2. **Testing**: Easily switch modes to verify fixes work
3. **Isolation**: Each vulnerability is independently toggleable
4. **Safety**: Secure mode is default; vulnerabilities are opt-in

## Core Components

### Service Layer Architecture

**Location**: `src/Services/`

| Service | Purpose | Vulnerabilities |
|---------|---------|---|
| `Database.php` | PDO wrapper with prepared statements | SQL injection defense |
| `AuthService.php` | User auth, registration, login | Weak auth, rate limiting |
| `ProductService.php` | Product queries and search | SQL injection |
| `CartService.php` | Shopping cart (session-based) | Integrity (Phase 3) |
| `ReviewService.php` | Product reviews and ratings | SSRF (Phase 4) |

### Frontend Layer

**Location**: `public/pages/`

- `home.php` — Product catalog, category filter, search
- `product-detail.php` — Individual product view with reviews
- `login.php` — User authentication
- `register.php` — User registration
- `cart.php` — Shopping cart management

### Database Layer

**Location**: `database/schema.sql`

Tables designed with security indices and audit columns:
- `users` — User accounts with pwd_hash, role, locked_until (rate limiting)
- `products` — Product catalog
- `categories` — Product categories
- `reviews` — Customer feedback with timestamps
- `audit_logs` — Security event trail (user_id, action, ip_address, timestamp)

## Critical Safety Boundaries

### Boundary 1: Database Binding (localhost only)

```php
// config/config.php — enforced at startup
$db_host = getenv('DB_HOST');
if (!in_array($db_host, ['localhost', '127.0.0.1'])) {
    die("FATAL: Database must bind to localhost or 127.0.0.1");
}
```

**Purpose**: Prevents exposing vulnerable code to remote network access.

### Boundary 2: Application URL (localhost only)

```php
// config/config.php — enforced at startup
$app_url = getenv('APP_URL');
if (strpos($app_url, 'localhost') === false && 
    strpos($app_url, '127.0.0.1') === false) {
    die("FATAL: APP_URL must contain 'localhost' or '127.0.0.1'");
}
```

**Purpose**: Prevents accidental deployment to production servers.

### Boundary 3: APP_MODE Validation

```php
// config/config.php — enforced at startup
$app_mode = getenv('APP_MODE') ?? 'secure';
if (!in_array($app_mode, ['vulnerable', 'secure'])) {
    die("FATAL: APP_MODE must be exactly 'vulnerable' or 'secure'");
}
```

**Purpose**: Prevents undefined behavior from typos or misconfiguration.

## OWASP Top 10 Implementation Roadmap

The project maps to OWASP Top 10 2021 with phased implementation:

| # | Vulnerability | Phase | Status | Teaching Point |
|---|---|---|---|---|
| A01 | Broken Access Control | 3 | 🔜 Planned | Role checks on every endpoint |
| A02 | Cryptographic Failures | 2 | ✅ Active | MD5 vs BCrypt password hashing |
| A03 | SQL Injection | 2 | ✅ Active | String concat vs prepared statements |
| A04 | Insecure Design | 3 | 🔜 Planned | File upload validation, allowlists |
| A05 | Misconfiguration | 2 | ✅ Active | Verbose errors, security headers |
| A06 | Outdated Components | 4 | 🔜 Planned | Dependency version management |
| A07 | Authentication Failures | 2 | ✅ Active | Rate limiting, session hardening |
| A08 | Integrity Failures | 4 | 🔜 Planned | CSRF, data verification |
| A09 | Logging Failures | 2 | ✅ Active | Audit log completeness |
| A10 | SSRF | 4 | 🔜 Planned | URL allowlists, outbound restrictions |

### Phase 2 (Current) Vulnerabilities

**A02: Cryptographic Failures** — Password Hashing
- Vulnerable: `md5()` (no salt, crackable)
- Secure: `password_hash()` with bcrypt
- Toggle: `VULN_WEAK_AUTH`

**A03: SQL Injection** — Search/Login Queries
- Vulnerable: `"... WHERE name LIKE '%{$term}%'"`
- Secure: Prepared statement with parameter binding
- Toggle: `VULN_SQL_INJECTION`

**A05: Misconfiguration** — Error Messages & Headers
- Verbose vs generic error messages
- Security headers presence (X-Frame-Options, CSP, etc.)
- Toggle: `VULN_VERBOSE_ERRORS`, `VULN_MISSING_HEADERS`

**A07: Authentication Failures** — Rate Limiting & Sessions
- Rate limiting: 5 failed attempts → 15 min lockout
- Session hardening: HTTP-only, secure, SameSite cookies
- Toggle: `VULN_NO_RATE_LIMIT`, `VULN_WEAK_SESSION`

**A09: Logging Failures** — Audit Trail
- Verbose (all events logged) vs minimal (errors only)
- Toggle: `VULN_MINIMAL_LOGGING`

## Session Management Security

### Session Configuration (Secure Mode)

```php
// config/config.php
ini_set('session.cookie_httponly', true);      // Block JavaScript access
ini_set('session.cookie_secure', true);        // HTTPS only
ini_set('session.cookie_samesite', 'Strict');  // CSRF protection
ini_set('session.use_strict_mode', true);      // Reject invalid session IDs
session_start();
session_regenerate_id(true);                   // New ID on login
```

### Authentication Pattern

After successful login:
```php
$_SESSION['user_id'] = 42;
$_SESSION['user_email'] = 'user@example.com';
$_SESSION['user_role'] = 'customer';  // or 'admin'
```

### Rate Limiting (Secure Mode)

- Max: 5 failed login attempts per email
- Lockout: 15 minutes after 5th failure
- Reset: On successful login
- Storage: `users.locked_until` timestamp column

## Safe Learning Environment Principles

### 1. Local-Only Execution
- Database binds to `localhost` / `127.0.0.1` only (enforced)
- Application URL must contain `localhost` / `127.0.0.1` (enforced)
- Internet access impossible by design
- Docker containers on internal network only

### 2. Environment Separation
- Vulnerable/secure code in same codebase
- Toggled via environment variables
- No separate deployments or databases
- Clean switch between learning modes

### 3. Mock Data Only
- No real customer data, payment info, or PII
- All data is synthetic and for training
- Session-based carts (not persistent)
- Demo users reset on database initialization

### 4. Enforced Guardrails
- Startup validation aborts on unsafe config
- No warnings—fatal errors on violations
- Database/URL/Mode hardcoded checks
- Prevents accidental exposure

### 5. Deterministic Reset
- Scripts reset database to known state
- Exercises replay cleanly without side effects
- No manual cleanup needed
- Session data auto-destroys on timeout

## Project Phases

### Phase 2: Basic E-Commerce (Current) ✅

**Completed Features**:
- User registration/login with session management
- Product catalog with category filter and search
- Shopping cart (session-based, in-memory)
- Product reviews with 1-5 star ratings
- All 5 Phase 2 vulnerabilities (A02, A03, A05, A07, A09)

**Milestone**: Functional e-commerce baseline for vulnerability teaching

### Phase 3: Admin System (Upcoming) 🔜

**Planned**:
- Admin login and dashboard
- Product CRUD (create/edit/delete)
- User management and role assignment
- Authorization checks on all endpoints
- CSRF token protection
- File upload handling with validation
- Audit logging of admin actions

**New Vulnerabilities**: A01 (access control), A04 (insecure design), A08 (integrity)

### Phase 4: Advanced Vulnerabilities (Future) 🔜

- Outdated component management (A06)
- Data integrity verification (A08)
- SSRF prevention (A10)

### Phase 5: Documentation & Testing (Final) 🔜

- 95%+ test coverage
- Learner guides and exercise walkthroughs
- Video tutorials
- Final security review and certification

## Learner Exercises

### Exercise 1: Crack the Password (A02)
1. Set `VULN_WEAK_AUTH=true`
2. Register account: `test@test.com` / `secret123`
3. Query database: `SELECT password_hash FROM users WHERE email = 'test@test.com'`
4. Use online MD5 lookup service to crack hash
5. Password revealed instantly
6. Set `VULN_WEAK_AUTH=false` and repeat: bcrypt cannot be reversed

### Exercise 2: Exploit SQL Injection (A03)
1. Set `VULN_SQL_INJECTION=true`
2. Visit search, enter payload: `' OR '1'='1`
3. Observe all products returned (WHERE clause bypassed)
4. Set `VULN_SQL_INJECTION=false`
5. Same payload returns 0 results (treated as literal string)

### Exercise 3: Bypass Rate Limiting (A07)
1. Set `VULN_NO_RATE_LIMIT=true`
2. Attempt login 100 times with wrong password
3. All attempts accepted (no lockout, enables brute force)
4. Set `VULN_NO_RATE_LIMIT=false`
5. 6th failed attempt triggers 15-minute lockout

## Common Development Tasks

### Adding a Vulnerable Feature

1. Add toggle to `.env.example`: `VULN_NEW_FEATURE=false`
2. Implement dual code paths using `isVulnerable()`
3. Add inline comments marking vulnerable/secure boundaries
4. Create unit tests (vulnerable mode + secure mode scenarios)
5. Document in [plan.md](.github/plan.md)
6. Update roadmap if it's a new OWASP category

### Testing a Vulnerability

```bash
# 1. Enable vulnerable mode
VULN_FEATURE=true

# 2. Create test case
public function testVulnerableScenario() {
    if (isVulnerable('feature')) {
        $result = $this->service->dangerous_operation($_GET['input']);
        // Verify vulnerability is exploitable
    }
}

# 3. Run tests
composer run test

# 4. Switch to secure mode and verify protection
VULN_FEATURE=false
composer run test
```

---

**See Also**: [technical-defaults.md](technical-defaults.md), [workflow.md](workflow.md)

This lab is run primarily with Docker setup, not a host Composer workflow.

