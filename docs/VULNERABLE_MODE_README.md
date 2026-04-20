# Vulnerable Mode Lab Guide

This guide helps learners test intentionally vulnerable behavior in a safe local lab.

## Scope and Safety

- Run locally only.
- Use mock data only.
- Keep APP_URL and DB host on localhost.
- Do not expose this environment to the internet.

## Mode Setup

1. Open .env.
2. Set:

```env
APP_MODE=vulnerable
VULN_SQL_INJECTION=true
VULN_ACCESS_CONTROL=true
VULN_WEAK_AUTH=true
VULN_INSECURE_UPLOAD=true
VULN_MISCONFIGURATION=true
VULN_BROKEN_AUTH=true
VULN_SESSION_ISSUES=true
VULN_INTEGRITY_FAILURES=true
VULN_LOGGING_FAILURES=true
VULN_SSRF=true
VULN_REVIEW_INJECTION=true
VULN_OS_COMMAND_INJECTION=true
```

3. Restart the app.

Secure comparison:
- Set APP_MODE=secure and repeat the same tests.
- Expected: exploit attempts fail or are neutralized.

## Where to Validate OS Command Injection Proof

Use /tmp only.

- Docker host path: tmp/osci-proof.csv
- Container path: /var/www/html/tmp/osci-proof.csv
- Manual VM path: /var/www/cake_shop_v2/tmp/osci-proof.csv

## Vulnerability Test Hints

Use these as starter checks. Keep tests authorized and local.

1. A01 Broken Access Control
- Try opening admin pages as a customer account.
- Try changing object id values in order detail URLs.

2. A02 Cryptographic Failures
- Review how passwords are handled in vulnerable mode.
- Compare hash behavior between vulnerable and secure modes.

3. A03 SQL Injection
- In search fields, try boolean payload styles such as quote-based true conditions.
- Compare vulnerable mode behavior versus secure mode prepared-statement handling.

4. A04 Insecure Design (Uploads)
- Test upload validation boundaries with file name and content mismatch cases.

5. A05 Security Misconfiguration
- Compare verbose errors in vulnerable mode against reduced disclosure in secure mode.

6. A07 Authentication Failures
- Attempt repeated failed logins and observe lockout differences.

7. A08 Software and Data Integrity Failures
- Test whether sensitive update actions enforce integrity checks.

8. A09 Logging and Monitoring Failures
- Trigger important security events and verify audit log coverage.

9. A10 SSRF
- Test URL input restrictions and internal target protections in secure mode.

10. Review Injection / Stored Injection
- Submit review content containing script-like input and compare output handling.

11. OS Command Injection (CWE-78 training extension)
- Use the cart export filename field to test shell metacharacter handling.
- Vulnerable mode exports to server tmp path and should demonstrate command execution risk.
- Secure mode now returns a direct browser CSV download and neutralizes filename abuse.

## Detailed OSCI Walkthrough

Preconditions:
- Logged in as customer.
- At least one cart item exists.
- APP_MODE=vulnerable and VULN_OS_COMMAND_INJECTION=true.

Steps:
1. Open the cart page and find Export Cart CSV.
2. In filename input, submit:

```text
cart.csv; echo "OSCI-OK $(whoami) $(date)" > /var/www/html/tmp/osci-proof.csv #
```

For manual VM path, use:

```text
cart.csv; echo "OSCI-OK $(whoami) $(date)" > /var/www/cake_shop_v2/tmp/osci-proof.csv #
```

3. Submit export.
4. Verify proof file:

```bash
cat tmp/osci-proof.csv
```

Expected in vulnerable mode:
- Proof file content changes with OSCI marker.
- Export result is handled on server filesystem (tmp path), not direct download.

Expected in secure mode:
- The same payload no longer creates shell side effects.
- Browser receives CSV download directly with sanitized filename.

## Quick Reset Between Attempts

Docker/local repo root:

```bash
printf 'SECURE_RESET\n' > tmp/osci-proof.csv
```

Manual VM:

```bash
printf 'SECURE_RESET\n' | sudo tee /var/www/cake_shop_v2/tmp/osci-proof.csv > /dev/null
```

## References

- docs/PHASE2_QUICKSTART.md
- docs/PHASE2.md
- tmp/ubuntu18-manual-deploy-guide.md
