# Cake Shop OWASP Training Lab

A deliberately vulnerable PHP + MySQL e-commerce system designed for cybersecurity training. Learn and practice OWASP Top 10 vulnerabilities in a controlled, local-only lab environment.

## 🚀 Quick Start

### Prerequisites
- PHP 7.4+
- MySQL 5.7+
- Composer (optional, for dependencies)

### Setup

1. **Clone/Extract the project**
   ```bash
   cd /path/to/cake_shop_v2
   ```

2. **Create `.env` file from template**
   ```bash
   cp .env.example .env
   ```

3. **Initialize the database**
   ```bash
   mysql -u root -p < database/schema.sql
   ```
   Or create the database manually and import:
   ```sql
   CREATE DATABASE cake_shop_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   USE cake_shop_v2;
   SOURCE database/schema.sql;
   ```

4. **Start PHP development server**
   ```bash
   cd public
   php -S localhost:8000
   ```

5. **Access the application**
   - Home page: http://localhost:8000
   - API health check: http://localhost:8000/api/health

## 📋 Project Structure

```
.
├── config/              # Configuration files
│   └── config.php       # Main configuration (dual-mode support)
├── public/              # Web root
│   ├── index.php        # Main entry point
│   ├── css/             # Stylesheets
│   ├── js/              # Client-side JavaScript
│   ├── images/          # Static images
│   └── pages/           # Page templates
├── src/                 # Application source code
│   ├── Services/        # Shared business logic
│   ├── Customer/        # Customer-facing features
│   ├── Admin/           # Admin panel features
│   └── Auth/            # Authentication & authorization
├── database/            # Database schema and seeds
│   └── schema.sql       # MySQL DDL
├── tests/               # Test suites
├── docs/                # Documentation & learner guides
```

## 🔐 Configuration

### Environment Variables (.env)

```env
# Database
DB_HOST=localhost
DB_USER=cake_shop
DB_PASSWORD=securepassword
DB_NAME=cake_shop_v2

# Application Mode
APP_MODE=vulnerable    # or 'secure'

# Per-Vulnerability Toggles (only active in vulnerable mode)
VULN_SQL_INJECTION=true
VULN_ACCESS_CONTROL=true
VULN_WEAK_AUTH=true
# ... (10 total)
```

### Application Modes

- **`vulnerable`**: OWASP vulnerabilities enabled for learning. Use to understand attacks.
- **`secure`**: Secure implementations enabled. Use to learn fixes and best practices.

Switch modes by changing `APP_MODE` in `.env` and restarting the server.

## 🎯 Vulnerability Coverage (OWASP Top 10)

| # | Vulnerability | Feature | Link |
|---|---|---|---|
| 1 | Broken Access Control | Admin, Orders | [Learner Guide](docs/vulnerabilities/01-broken-access-control.md) |
| 2 | Cryptographic Failures | Auth, Login | [Learner Guide](docs/vulnerabilities/02-cryptographic-failures.md) |
| 3 | Injection (SQLi) | Search, Login, Filters | [Learner Guide](docs/vulnerabilities/03-injection-sqli.md) |
| 4 | Insecure Design | File Upload | [Learner Guide](docs/vulnerabilities/04-insecure-design.md) |
| 5 | Security Misconfiguration | Config, Errors | [Learner Guide](docs/vulnerabilities/05-misconfiguration.md) |
| 6 | Vulnerable/Outdated Components | Dependencies | [Learner Guide](docs/vulnerabilities/06-vulnerable-components.md) |
| 7 | Auth Failures | Session, Login | [Learner Guide](docs/vulnerabilities/07-auth-failures.md) |
| 8 | Integrity Failures | Data Updates | [Learner Guide](docs/vulnerabilities/08-integrity-failures.md) |
| 9 | Logging & Monitoring Failures | Audit Logs | [Learner Guide](docs/vulnerabilities/09-logging-failures.md) |
| 10 | SSRF / Request Abuse | URL Fetching | [Learner Guide](docs/vulnerabilities/10-ssrf.md) |

## 📚 Learning Path

### Beginner Exercises
1. [Access Control Bypass](docs/exercises/01-broken-access-control.md)
2. [SQL Injection Basics](docs/exercises/03-sql-injection.md)
3. [Weak Authentication](docs/exercises/07-auth-failures.md)

### Intermediate Exercises
1. [Chained Vulnerabilities](docs/exercises/chained-attacks.md)
2. [File Upload Abuse](docs/exercises/04-insecure-design.md)
3. [Session Hijacking](docs/exercises/07-session-security.md)

### Fix-Focused Exercises
1. [Authorization Hardening](docs/exercises/fix-01-authz.md)
2. [Input Validation](docs/exercises/fix-03-input-validation.md)
3. [Secure Session Management](docs/exercises/fix-07-sessions.md)

## ⚙️ Default Credentials

**Admin User**
- Email: `admin@cakeshop.local`
- Password: `admin123`

**Customer User**
- Email: `customer@example.com`
- Password: `customer123`

## 🔒 Safety & Local-Only Execution

- All services bind to `localhost` only.
- No real customer data—mock data only.
- Preflight checks prevent unsafe configurations.
- Startup banner warns: "TRAINING ENVIRONMENT ONLY".

## 📖 Running Tests

```bash
composer install     # Install dev dependencies
composer test        # Run phpunit tests
composer lint        # Check code style
```

## 🛠️ Development

### Project Phases

- **Phase 1**: ✅ Core Setup (complete)
- **Phase 2**: 🔄 Basic E-commerce Features (in progress)
- **Phase 3**: 📅 Admin System
- **Phase 4**: 📅 Vulnerability Lab Integration
- **Phase 5**: 📅 Testing & Documentation

### Contributing

1. Follow the plan in `plan.md`
2. Implement features per phase
3. Maintain vulnerable and secure code paths
4. Document vulnerabilities with learner guides
5. Test in both modes

## ⚠️ Important Notes

- **LOCAL USE ONLY**: This system is intentionally vulnerable. Never deploy to a public server.
- **Educational Purpose**: Designed for authorized cybersecurity training only.
- **No Real Data**: All data is mock and non-sensitive.
- **Isolated Environment**: Runs only on localhost, 127.0.0.1.

## 📝 License

MIT License - See LICENSE file for details

## 🤝 Support & Feedback

For issues, questions, or improvements, refer to:
- Project plan: [plan.md](.github/plan.md)
- Developer docs: [docs/](docs/)
- Learner guides: [docs/vulnerabilities/](docs/vulnerabilities/)

---

**Status**: Phase 1 Complete | Ready for Phase 2 Implementation
