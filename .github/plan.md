## Plan: Cake Shop OWASP Training Lab

Recommended approach: build one realistic PHP + MySQL e-commerce system with two runtime modes (Vulnerable and Secure). Implement business features first, then layer in controlled vulnerabilities feature-by-feature so each one is isolated, teachable, and reversible.

I also saved this plan to session memory at /memories/session/plan.md for iteration.

### Phase 1: Core Setup
Goal:
- Establish project architecture, environment isolation, and safe-local lab boundaries.

Tasks:
1. Define app structure for customer, admin, auth, and shared services.
2. Create two environment profiles: Vulnerable and Secure.
3. Add mode switch mechanism per feature (not one global unsafe switch only).
4. Set up MySQL schema + mock seed data only.
5. Add startup warnings and local-only preflight checks.

Expected outcome:
- A runnable local baseline with clear separation between vulnerable and secure behavior paths.

### Phase 2: Basic E-commerce Features
Goal:
- Build realistic customer workflows as the functional base for later vulnerability scenarios.

Tasks:
1. Catalog browse, category filter, search, product detail.
2. Cart add/update/remove and checkout simulation.
3. Register/login/logout and order history.
4. Reviews/comments on products.
5. Build secure-baseline implementations first for clean comparison later.

Expected outcome:
- End-to-end customer journey works in secure baseline mode.

### Phase 3: Admin System
Goal:
- Build complete admin operations where access control and input handling can be trained.

Tasks:
1. Admin dashboard.
2. Product CRUD (including image upload).
3. Order management (view, status transitions).
4. User management (role changes, suspension/reactivation).
5. Basic audit event recording.

Expected outcome:
- Business-like admin panel ready for controlled security faults.

### Phase 4: Vulnerability Lab Integration
Goal:
- Inject planned OWASP-style vulnerabilities in isolated toggles per feature.

Tasks:
1. Add per-vulnerability toggles (example: SQLi toggle only affects chosen endpoints).
2. Add vulnerability tags in docs and in-code markers for training navigation.
3. Implement vulnerable and secure versions side-by-side for each mapped feature.
4. Add UI warning banner in vulnerable mode.
5. Ensure no single vulnerability can crash all workflows.

Expected outcome:
- A deliberate vulnerable lab where each weakness is controllable and independently testable.

### Phase 5: Testing and Documentation
Goal:
- Make the project teachable, reproducible, and safe for local security learning.

Tasks:
1. Functional tests for customer/admin flows in both modes.
2. Security validation checks: vulnerable mode should expose expected behavior, secure mode should block it.
3. Write learner guides and instructor notes.
4. Add reset scripts for deterministic exercises.
5. Final local-only safety audit.

Expected outcome:
- A complete cybersecurity training lab with clear exercises and fix paths.

---

## Vulnerability Design Plan (OWASP-aligned, 10 items)

| # | Vulnerability | Feature | Vulnerable version (intentional) | Exploit concept (high-level) | Secure version (best practice) | Risk | Learning objective |
|---|---|---|---|---|---|---|---|
| 1 | A01 Broken Access Control | Admin pages, order/user endpoints | Trusts client role flags; missing server-side authorization checks | Normal user accesses admin URLs or other users’ resources | Enforce role checks on every sensitive route and object ownership checks | High | Understand server-side authorization as non-optional |
| 2 | A02 Cryptographic Failures | Login/register, credential storage | Weak password hashing and poor secret handling | Credential theft impact amplified by weak storage | Use strong password hashing and protected secret management | High | Learn secure credential handling fundamentals |
| 3 | A03 Injection (SQLi) | Search, login, filters | Raw SQL string concatenation from request input | Malicious input changes query logic or exposes data | Prepared statements + strict input validation | High | Learn parameterization and query safety |
| 4 | A04 Insecure Design (upload workflow) | Admin image upload | Weak upload controls and unsafe storage choices | Attacker uploads unexpected file type to abuse system behavior | Strict allowlist, content validation, safe storage and execution controls | High | Learn secure file handling design |
| 5 | A05 Security Misconfiguration | App/server config, error handling | Verbose errors, insecure headers, debug settings enabled | Error disclosures leak internals useful for chained attacks | Hardened headers, controlled errors, secure defaults | Medium | Learn configuration as a security control |
| 6 | A06 Vulnerable/Outdated Components | Dependency layer | Intentionally outdated package profile in vulnerable mode | Known component weakness increases compromise likelihood | Maintain patched versions and dependency governance | Medium | Learn supply-chain hygiene |
| 7 | A07 Identification and Authentication Failures | Session/login | Weak session policy, poor lockout, predictable behavior | Session abuse or brute-force success rises | Strong session controls, lockout/rate limits, secure cookies | High | Learn authentication lifecycle hardening |
| 8 | A08 Software and Data Integrity Failures | Import/update or admin utility endpoints | Missing integrity verification of trusted data/actions | Tampered data or untrusted updates accepted | Integrity checks, trusted source validation, signed artifacts/processes | Medium | Learn integrity validation patterns |
| 9 | A09 Logging and Monitoring Failures | Security event logging | Missing or low-quality logs for auth/admin anomalies | Suspicious activity goes undetected and uninvestigated | Structured security logging and alerting thresholds | Medium | Learn detection and incident visibility |
|10| A10 SSRF / request abuse simulation | Admin URL fetch helper/integrations | Server fetches attacker-supplied URLs without restrictions | Internal resource probing through server-side requests | URL allowlists, network egress restrictions, safe fetch rules | High | Learn outbound request trust boundaries |

---

## Safe Learning Environment Design

1. Local-only execution:
- Bind all services to localhost.
- Use firewall/container network limits to block unintended outbound/inbound exposure.

2. Environment separation:
- Distinct vulnerable and secure profiles.
- Separate databases per mode.

3. Data policy:
- Mock data only.
- No real customer data, payment data, or personal identifiers.

4. Guardrails:
- Startup script aborts if host binding or environment is unsafe.
- Visible warning banner in UI and docs: deliberately vulnerable training system.

5. Operational controls:
- Deterministic reset scripts between exercises.
- No deployment pipeline to internet-facing environment.

---

## Secure Version Comparison Mode

For each vulnerable feature, maintain a pair:
1. Vulnerable path:
- Intentionally weak control for demonstration.
- Clearly labeled with risk and learning objective.

2. Secure path:
- Production-style best practice implementation.
- Same feature behavior from user perspective where possible.

3. Difference highlight format (per vulnerability):
- What changed in validation, authorization, encoding, session, config, or logging.
- Why it prevents the exploit concept.
- Tradeoff notes (complexity/performance/usability if relevant).

---

## Testing and Learning Guide

Beginner exercises:
1. Identify broken access control in admin route exposure.
2. Observe reflected/stored XSS behavior in review fields.
3. Trigger SQL injection concept in search/login and compare secure mode response.
4. Verify CSRF-style state-change weakness (if included in auth/order flows).

Intermediate exercises:
1. Chain two weaknesses (example: low-privilege account plus access control gap).
2. Exploit weak session/auth behavior and then harden controls.
3. Abuse upload workflow design and then implement strict safe pipeline.
4. Build detection rule improvements for missing logging/monitoring scenarios.

Fix-focused exercises:
1. For each vulnerability, patch vulnerable path and prove secure behavior with tests.
2. Produce a short remediation note: root cause, fix pattern, validation evidence.

Difficulty progression:
1. Beginner: single endpoint, single vulnerability.
2. Intermediate: multi-step flow and weak-control chaining.
3. Advanced-intermediate: secure refactor plus regression verification.

---

## Scope Boundaries

Included:
- Customer + admin full flow.
- 10 OWASP-inspired intentional vulnerabilities.
- Vulnerable vs secure comparison mode.
- Learning exercises and verification plan.

Excluded:
- Real payment gateways.
- Internet/public deployment.
- Real user data ingestion.

---

