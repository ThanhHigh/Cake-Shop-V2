# Phase 2 Execution Plan: Vulnerable Mode E2E Verification

## Objective
Validate that the normal customer flow works end to end in vulnerable mode, while confirming that intentionally vulnerable behaviors are actually present where expected.

This plan is aligned to current implementation in:
- src/Services/ProductService.php
- src/Services/AuthService.php
- src/Services/CartService.php
- src/Services/OrderService.php
- src/Services/OrderManagementService.php
- src/Services/IntegrationService.php
- public/pages/home.php
- public/pages/product-detail.php
- public/pages/register.php
- public/pages/login.php
- public/pages/cart.php
- public/pages/checkout.php
- public/pages/order-history.php
- public/pages/order-detail.php

## Scope
In scope:
- Normal user flow in vulnerable mode: register/login -> browse/search -> product detail -> add cart -> checkout -> order confirmation -> order history -> order detail
- Vulnerability behavior checks for implemented toggles in Phase 2/3 code paths
- Failure triage and fix workflow when behavior is wrong

Out of scope:
- Public internet deployment
- Non-local database hosts
- Full Phase 3/4 completion items that are not currently wired to pages

## Preconditions
1. Environment is local only and healthy.
2. APP_MODE is vulnerable in .env.
3. Vulnerability toggles needed for this run are true in .env:
   - VULN_SQL_INJECTION
   - VULN_WEAK_AUTH
   - VULN_ACCESS_CONTROL
   - VULN_INSECURE_UPLOAD
   - VULN_OS_COMMAND_INJECTION
   - VULN_SSRF
4. Docker stack is running and database initialized.

## Test Data Setup
Use deterministic users so reruns are easy:
- customer_a: customer.a+e2e@cake-shop.local
- customer_b: customer.b+e2e@cake-shop.local
- admin (if seeded): admin@cake-shop.local

Use at least 2 products from catalog with stock > 0.

## Part A: Vulnerable Mode Normal Flow (Happy Path)

### A1. Register and auto-login
1. Open /pages/register.php
2. Register customer_a with valid password.
3. Expect redirect to /catalog (or requested redirect).
4. Verify session exists by opening /pages/cart.php without redirect to login.

Pass criteria:
- Account created.
- Session active.

### A2. Browse and search catalog
1. Open /catalog.
2. Filter by category.
3. Search for a normal term (example: chocolate).
4. Open one product detail page.

Pass criteria:
- Category filter returns products.
- Search returns expected products.
- Product detail loads with rating block and add-to-cart form.

### A3. Add to cart and update cart
1. Add item from product detail or catalog.
2. Open /pages/cart.php.
3. Update quantity.
4. Remove and re-add item.

Pass criteria:
- Cart totals update correctly.
- No unexpected errors.

### A4. Checkout and order creation
1. Open /pages/checkout.php with non-empty cart.
2. Submit shipping address and place order.
3. Verify redirect to /pages/order-confirmation.php?order_id=X.
4. Verify order appears in /pages/order-history.php.

Pass criteria:
- Order created and status updated to paid.
- Cart cleared after checkout.

### A5. Order detail
1. From order history, open order detail.
2. Confirm line items and total visible.

Pass criteria:
- Order detail page loads and matches order content.

## Part B: Vulnerability Verification Matrix (Vulnerable Mode)

### B1. SQL Injection in search
Area:
- ProductService::searchProducts vulnerable branch

Steps:
1. In /catalog search box, submit payload: ' OR '1'='1
2. Compare result count against normal unlikely term.

Expected in vulnerable mode:
- Payload changes query behavior and returns broad result set.

### B2. Weak authentication hashing
Area:
- AuthService::register vulnerable branch (MD5)

Steps:
1. Register fresh user.
2. Inspect users.password_hash in DB.

Expected in vulnerable mode:
- Hash format looks like 32-char MD5 hex, not bcrypt.

### B3. Access control weakness on order visibility
Area:
- order-history.php vulnerable branch
- OrderManagementService::getOrderWithAccessCheck vulnerable branch

Steps:
1. Create orders as customer_a and customer_b.
2. Login as customer_a and open /pages/order-history.php.
3. Attempt opening customer_b order by ID in /pages/order-detail.php?order_id=...

Expected in vulnerable mode:
- customer_a can see all orders in history and can access other user order detail.

### B4. OS command injection in cart CSV export
Area:
- CartService::exportCartToCsv vulnerable branch

Steps:
1. In /pages/cart.php export CSV with crafted filename payload.
2. Observe command side effects in tmp folder.

Expected in vulnerable mode:
- Shell command uses unsanitized output path, allowing command injection side effects.

### B5. Insecure upload behavior
Area:
- ImageUploadService vulnerable branch
- Checkout invoice upload path

Steps:
1. Upload disallowed extension file via invoice upload path.
2. Verify original filename handling and acceptance behavior.

Expected in vulnerable mode:
- Weak validation behavior present (depends on current branch logic).

### B6. SSRF behavior
Area:
- IntegrationService::fetchUrl vulnerable branch
- Customer review flow: /pages/product-detail.php -> /api/reviews/create

Steps:
1. Log in and open a product detail page.
2. Submit a review with Proof URL set to an internal target (example: http://127.0.0.1/admin).
3. Observe proof fetch status/message/preview in review submission response.
4. Compare behavior with secure mode later.

Expected in vulnerable mode:
- Internal/private target requests are not blocked by SSRF validation controls.
- Learner can observe non-blind SSRF response feedback in UI/API.

## Part C: Failure Triage and Fix Plan

### Severity tiers
Tier 1 (Blocker):
- Normal flow broken (cannot register/login/cart/checkout/order flow).

Tier 2 (Security mismatch):
- Vulnerable mode does not expose intended weakness.
- Or vulnerable mode accidentally blocks normal flow.

Tier 3 (Quality):
- Styling/UI issues, weak error messaging, minor inconsistencies.

### Triage steps when a check fails
1. Capture failing step, request payload, and exact URL.
2. Capture app logs and DB state snapshot (affected rows/tables).
3. Identify failing layer:
   - Page wiring issue
   - Service logic issue
   - Toggle/config issue
   - Data setup issue
4. Patch smallest safe change in relevant service/page.
5. Re-run only failing case, then rerun Part A happy path smoke.
6. Record fix in changelog section below.

### Fast mapping from symptom to likely fix location
- Search payload has no effect in vulnerable mode:
  - src/Services/ProductService.php and .env toggles
- Login/register issues:
  - src/Services/AuthService.php, public/pages/login.php, public/pages/register.php
- Cart/export issues:
  - src/Services/CartService.php, public/pages/cart.php
- Checkout/order issues:
  - src/Services/OrderService.php, public/pages/checkout.php
- Order access control mismatch:
  - public/pages/order-history.php, src/Services/OrderManagementService.php
- SSRF mismatch:
  - src/Services/IntegrationService.php and SSRF_ALLOWED_HOSTS env

## Part D: Automation Plan (After Manual Baseline)
1. Add PHPUnit feature-style tests for Part A happy path service calls.
2. Add vulnerability assertion tests for Part B with mode-aware expectations.
3. Add DB reset helper in tests/bootstrap.php.
4. Run test suite in vulnerable mode first, then secure mode for opposite assertions where applicable.

## Execution Checklist
- [ ] Preconditions validated
- [ ] Part A completed (all pass)
- [ ] Part B completed (all expected vulnerable behaviors observed)
- [ ] Any failures triaged with Tier classification
- [ ] Fixes applied and failing tests re-run
- [ ] Final smoke run of Part A after fixes

## Fix Log Template
Use this block for each issue found:

Issue ID:
- Failing step:
- Expected:
- Actual:
- Severity tier:
- Root cause file:
- Fix summary:
- Re-test evidence:
