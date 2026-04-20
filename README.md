# Cake Shop OWASP Training Lab

A deliberately vulnerable PHP + MySQL e-commerce system designed for cybersecurity training. Learn and practice OWASP Top 10 vulnerabilities in a controlled, local-only lab environment.

## 🚀 Quick Start

Vulnerable mode learner guide: [docs/VULNERABLE_MODE_README.md](docs/VULNERABLE_MODE_README.md)

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
| 1 | Broken Access Control | Admin, Orders | [Vulnerable Mode Guide](docs/VULNERABLE_MODE_README.md) |
| 2 | Cryptographic Failures | Auth, Login | [Vulnerable Mode Guide](docs/VULNERABLE_MODE_README.md) |
| 3 | Injection (SQLi) | Search, Login, Filters | [Vulnerable Mode Guide](docs/VULNERABLE_MODE_README.md) |
| 4 | Insecure Design | File Upload | [Vulnerable Mode Guide](docs/VULNERABLE_MODE_README.md) |
| 5 | Security Misconfiguration | Config, Errors | [Vulnerable Mode Guide](docs/VULNERABLE_MODE_README.md) |
| 6 | Vulnerable/Outdated Components | Dependencies | [Vulnerable Mode Guide](docs/VULNERABLE_MODE_README.md) |
| 7 | Auth Failures | Session, Login | [Vulnerable Mode Guide](docs/VULNERABLE_MODE_README.md) |
| 8 | Integrity Failures | Data Updates | [Vulnerable Mode Guide](docs/VULNERABLE_MODE_README.md) |
| 9 | Logging & Monitoring Failures | Audit Logs | [Vulnerable Mode Guide](docs/VULNERABLE_MODE_README.md) |
| 10 | SSRF / Request Abuse | URL Fetching | [Vulnerable Mode Guide](docs/VULNERABLE_MODE_README.md) |

Training extension included:
- OS command injection (CWE-78) via cart CSV filename handling, documented in [docs/VULNERABLE_MODE_README.md](docs/VULNERABLE_MODE_README.md)

## 📚 Learning Path

### Beginner Exercises
1. [Vulnerable Mode Guide](docs/VULNERABLE_MODE_README.md)
2. [Phase 2 Quickstart](docs/PHASE2_QUICKSTART.md)
3. [Phase 2 Architecture](docs/PHASE2.md)

### Intermediate Exercises
1. [Vulnerable Mode Guide](docs/VULNERABLE_MODE_README.md)
2. [Phase 3 Checklist](docs/PHASE3_CHECKLIST.md)
3. [Manual VM Deployment Guide](tmp/ubuntu18-manual-deploy-guide.md)

### Fix-Focused Exercises
1. Set APP_MODE=secure and rerun the same tests from [docs/VULNERABLE_MODE_README.md](docs/VULNERABLE_MODE_README.md)
2. Compare implementation details in [docs/PHASE2.md](docs/PHASE2.md)
3. Track progress against [docs/PHASE2_CHECKLIST.md](docs/PHASE2_CHECKLIST.md)

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
- Learner guide: [docs/VULNERABLE_MODE_README.md](docs/VULNERABLE_MODE_README.md)

---

**Status**: Phase 1 Complete | Ready for Phase 2 Implementation
