# Cake Shop OWASP Training Lab - Project Guidelines

## Code Style

- **Language**: PHP 7.4+
- **Autoloading**: PSR-4 namespace `CakeShop\` maps to `src/` directory
- **Code Standard**: Follow conventions exemplified in `src/Services/`—class methods snake_case, properties camelCase, no tabs
- **Database Queries**: Always use prepared statements with parameter binding (see `Database::queryAll()` and `Database::queryOne()`)—never string concatenation for SQL

## Architecture

This is an **OWASP training lab** with a **dual-mode security architecture**:

```
Vulnerable Mode  ←→  Secure Mode  (toggled via APP_MODE in .env)
     ↓                    ↓
Intentional           Secure
Vulnerabilities       Implementations
```

**Core Components**:
- **Service Layer** (`src/Services/`) — Business logic with dependency injection
  - `Database` (PDO singleton) — Data access layer with prepared statements
  - `ProductService` — Product/category queries (SQL injection teaching point)
  - `AuthService` — Login/registration (password hashing & rate limiting)
  - `CartService` — Shopping cart operations
  - `ReviewService` — Customer feedback
- **Frontend** (`public/pages/`) — HTML templates that instantiate services
- **Database** (`database/schema.sql`) — Tables with security indices (user rate limiting, audit logs)

**Dual-Mode Pattern** (critical design):
```php
// In config.php, call: isVulnerable('feature_name')
if (isVulnerable('sql_injection')) {
    // VULNERABLE: String concatenation
    $sql = "SELECT * WHERE name LIKE '%{$term}%'";
} else {
    // SECURE: Prepared statement
    $sql = "SELECT * WHERE name LIKE ?";
}
```

## Build and Test

### Quick Start (Docker)
```bash
docker-compose up              # Start PHP 7.4-Apache + MySQL 5.7 on ports 8080, 3306
```

### Quick Start (Manual)
```bash
cd /path/to/cake_shop_v2
cp .env.example .env
mysql -u root -p < database/schema.sql
cd public && php -S localhost:8000
```

### Composer Commands
```bash
composer run server            # Start dev server on http://localhost:8000
composer run test              # Run PHPUnit tests
composer run lint              # Check PHP CodeSniffer standards
```

### Health Check
```bash
curl http://localhost:8080/api/health  # Returns JSON health status
```

## Conventions

### Dual-Mode Toggles (in .env)
```
APP_MODE=vulnerable            # Or: secure
VULN_SQL_INJECTION=true         # Toggle each vulnerability (11 toggles total)
VULN_WEAK_AUTH=true
VULN_ACCESS_CONTROL=true
...and 8 more
```

### Critical Safety Boundaries
- **Database**: Must bind to `localhost` or `127.0.0.1` only (enforced in `config.php`)
- **APP_URL**: Must contain `localhost` or `127.0.0.1` (enforced on startup)
- **APP_MODE**: Must be exactly `'vulnerable'` or `'secure'` (validated at startup)

Violations cause a fatal error — this prevents exposing vulnerable code to the internet.

### Authentication Pattern
After login, `$_SESSION` contains: `user_id`, `user_email`, `user_role` (`'customer'` or `'admin'`)

### Rate Limiting (Secure Mode)
- Max 5 failed login attempts
- Account locked 15 minutes after 5th failure
- Reset on successful login

### Page Structure
All pages in `public/pages/` follow this pattern:
```php
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';
$config = require_once dirname(dirname(__DIR__)) . '/config/config.php';
// Services instantiated with $config passed
?><!DOCTYPE html>
<!-- HTML template -->
```

## Project Phases & Roadmap

### Current Status: **Phase 2** (Basic E-Commerce Features)
**Goal**: Build realistic customer workflows as functional base for vulnerability scenarios.

**Completed**:
- Catalog browse, category filter, search, product detail
- Cart add/update/remove operations
- Register/login/logout with session management
- Reviews on products with ratings
- Secure baseline implementations for clean comparison

**Upcoming Phases**:
- **Phase 3**: Admin system (CRUD, auth, audit logging)
- **Phase 4**: Vulnerability injection (isolated toggles per feature, UI warnings)
- **Phase 5**: Testing, documentation, learner guides, reset scripts

See [plan.md](.github/plan.md) for full roadmap and [PHASE2_QUICKSTART.md](PHASE2_QUICKSTART.md) for feature testing guide.

## Vulnerability Design Plan

The project implements 10 OWASP Top 10 vulnerabilities across phases, each with isolated toggles and secure/vulnerable comparative implementations:

| # | Vulnerability | Current Status | Key Learning |
|---|---|---|---|
| **A01** | Broken Access Control | Phase 3 (planned) | Server-side authorization on every route |
| **A02** | Cryptographic Failures | ✅ Phase 2 | MD5 (weak) vs BCrypt (strong) password hashing |
| **A03** | SQL Injection | ✅ Phase 2 | String concat vs prepared statements |
| **A04** | Insecure Design (uploads) | Phase 3 (planned) | Allowlist + content validation + safe storage |
| **A05** | Misconfiguration | ✅ Phase 2 | Verbose errors vs generic messages; secure headers |
| **A06** | Outdated Components | Phase 4 (planned) | Intentional/patched dependency profiles |
| **A07** | Auth Failures | ✅ Phase 2 | Rate limiting, session policy, lockout |
| **A08** | Integrity Failures | Phase 3 (planned) | Data verification, trusted source validation |
| **A09** | Logging Failures | ✅ Phase 2 (partial) | Structured audit logs vs missing events |
| **A10** | SSRF | Phase 4 (planned) | URL allowlists, network restrictions |

Full design details in [plan.md#vulnerability-design-plan](.github/plan.md).

## Safe Learning Environment Principles

1. **Local-only execution**: Bind services to localhost; firewall/container limits enforce isolation
2. **Environment separation**: Distinct vulnerable/secure profiles with separate databases
3. **Mock data only**: No real customer/payment/PII data
4. **Guardrails**: Startup aborts if unsafe host binding or configuration detected
5. **Deterministic reset**: Scripts allow exercises to be replayed cleanly

## Key Files

- [plan.md](.github/plan.md) — Full roadmap, vulnerability design, learning guide
- [setup.sh](setup.sh) — Automated setup scripts
- [PHASE2_QUICKSTART.md](docs/PHASE2_QUICKSTART.md) — Feature overview and testing guide
- [database/schema.sql](database/schema.sql) — Database schema with security-related patterns
- [docs/PHASE2.md](docs/PHASE2.md) — Detailed architecture documentation

## Common Tasks

**Adding a new vulnerable feature**: 
1. Add toggle to `config.php` (e.g., `VULN_NEW_FEATURE`)
2. Map to OWASP vulnerability in plan.md
3. Implement both vulnerable and secure code paths using `isVulnerable()` helper
4. Add inline comments marking the vulnerable/secure boundaries
5. Document exploit concept and secure fix in inline comments

**Running in secure mode for training**: Set `APP_MODE=secure` in `.env` and toggle only the specific vulnerabilities you want to expose.

**Testing a vulnerability**: 
- Example SQLi: Search with `' OR '1'='1` in vulnerable mode; verify prepared statements block in secure mode
- Example weak auth: Attempt 6 failed logins; verify rate limiting locks in secure mode
- Example access control: Try accessing admin URLs as customer; verify role checks enforce in secure mode

**Beginner learner exercises**:
1. Trigger broken access control (try admin URLs as customer)
2. Exploit SQL injection in search/login, compare to secure mode
3. Observe weak password hashing vs strong BCrypt
4. Verify session/auth lockout behavior in secure mode

---

**Last updated**: Phase 2 (August 2024) | **Next**: Phase 3 Admin System
