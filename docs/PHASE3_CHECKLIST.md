# Phase 3 Implementation Checklist - Order System & Admin Management

## Overview
Phase 3 focuses on building a complete order system spanning customer checkout, order history tracking, and admin order management. This phase introduces two major OWASP vulnerabilities for training:
- **A01: Broken Access Control** — Demonstrates authorization bypasses
- **A04: Insecure Design/File Upload** — Demonstrates file upload vulnerabilities

---

## Services Layer ✅

### OrderService (src/Services/OrderService.php)
- [x] Create order from cart items
- [x] Cart-to-order conversion with transaction support
- [x] Order number generation (YYYYMMDD-USERID-RANDOM format)
- [x] Get order by ID with items
- [x] Get orders by user ID
- [x] Get current user's orders
- [x] Get order items for specific order
- [x] Update order status
- [x] Calculate order total with tax (10%)
- [x] Automatic cart clearing after order creation
- [x] Transaction rollback on failure

### OrderManagementService (src/Services/OrderManagementService.php)
- [x] Get all orders (A01: vulnerable=no filter, secure=admin only)
- [x] Get single order with access control check (A01: core vulnerability)
- [x] Get orders by user ID (secure boundary)
- [x] Update order status with role check (A01: vulnerable=anyone, secure=admin)
- [x] Get order items
- [x] Validate order management access (A01: vulnerable=anyone, secure=admin)
- [x] Get order statistics (A01: vulnerable=anyone, secure=admin)
- [x] Search orders with filters (status, date range, user ID)

### ImageUploadService (src/Services/ImageUploadService.php)
- [x] Upload file with dual-mode validation
- [x] File extension whitelist (pdf, jpg, jpeg, png)
- [x] MIME type validation using finfo
- [x] File size limit (5MB)
- [x] A04: Vulnerable mode (no validation, original filename)
- [x] A04: Secure mode (hash rename, MIME validation, path traversal prevention)
- [x] Download file with access control
- [x] A04: Vulnerable download (no type check)
- [x] A04: Secure download (MIME validation, path traversal check)
- [x] File existence check
- [x] File deletion (admin operation)
- [x] Error handling with user-friendly messages

---

## Frontend - Customer Pages ✅

### Checkout Page (public/pages/checkout.php)
- [x] Session authentication check
- [x] Cart validation (redirect if empty)
- [x] Cart summary display (read-only):
  - [x] Item list with quantities and prices
  - [x] Subtotal, tax, shipping, total calculation
- [x] Shipping address form (required):
  - [x] Text area for full address
  - [x] Form validation
- [x] Mock payment section:
  - [x] Payment method radio buttons (credit card, debit card, bank transfer)
  - [x] Card number input (mock)
  - [x] Cardholder name input (mock)
  - [x] CVV input (mock)
- [x] Invoice upload (A04 vulnerability):
  - [x] File input with custom styling
  - [x] Vulnerable mode: Accept any file, save with original name
  - [x] Secure mode: Validate file type (pdf/jpg/png), rename with hash
  - [x] Error messages
- [x] Order notes (optional textarea)
- [x] Submit button triggers OrderService.createOrderFromCart()
- [x] Redirect to order-confirmation.php on success
- [x] Error messaging
- [x] Responsive design

### Order Confirmation Page (public/pages/order-confirmation.php)
- [x] Session authentication check
- [x] Order validation (verify user owns order)
- [x] Success icon and message
- [x] Order number display (formatted, copyable)
- [x] Order details:
  - [x] Order date
  - [x] Estimated delivery (5-10 business days + weekends)
  - [x] Order status badge
  - [x] Order total
- [x] Order items list:
  - [x] Product name, quantity, line total
  - [x] Subtotal, tax, shipping, grand total
- [x] Shipping address display
- [x] Customer notes display
- [x] Info box with next steps
- [x] Action buttons:
  - [x] View Order Details link
  - [x] Continue Shopping link
- [x] Responsive design

### Order History Page (public/pages/order-history.php)
- [x] Session authentication check
- [x] A01 Vulnerability check:
  - [x] Vulnerable: Show ALL orders in database
  - [x] Secure: Show only current user's orders
  - [x] Vulnerability notice for vulnerable mode
- [x] Orders table:
  - [x] Order number (formatted)
  - [x] Order date
  - [x] Status badge (color-coded)
  - [x] Total amount
  - [x] View Details action link
- [x] Empty state messaging
- [x] Responsive design
- [x] Sort by date descending

### Order Detail Page (public/pages/order-detail.php)
- [x] Session authentication check
- [x] A01 Vulnerability check:
  - [x] Vulnerable: Access any order by URL parameter
  - [x] Secure: Verify user owns order or is admin
  - [x] Access denied (403) page for unauthorized
  - [x] Vulnerability notice for vulnerable mode
- [x] Order header:
  - [x] Order number
  - [x] Customer ID
  - [x] Order date/time
  - [x] Status badge
- [x] Order items section:
  - [x] Item list with name, quantity, price
  - [x] Totals: subtotal, tax, shipping, order total
- [x] Shipping address section
- [x] Customer notes section
- [x] Status timeline:
  - [x] Order Created
  - [x] Payment Received (if not pending)
  - [x] Shipped (if applicable)
  - [x] Delivered (if applicable)
  - [x] Cancelled (if applicable)
- [x] Responsive design

### Cart Page Update (public/pages/cart.php)
- [x] Updated proceedToCheckout() to redirect to /pages/checkout.php
- [x] Removed alert message
- [x] Checkout button enabled

---

## Frontend - Admin Pages ✅

### Admin Orders Dashboard (public/pages/admin/orders.php)
- [x] A01 Authentication check:
  - [x] Vulnerable: Accessible to any logged-in user
  - [x] Secure: Require admin role
  - [x] Vulnerability notice for vulnerable mode
- [x] Admin badge indicator
- [x] Statistics cards:
  - [x] Total orders
  - [x] Revenue total
  - [x] Completed orders
  - [x] Pending orders
- [x] Filter section:
  - [x] Status filter dropdown
  - [x] Date from/to filter
  - [x] Apply filter button
  - [x] Reset filters button
- [x] Orders table:
  - [x] Order number (formatted)
  - [x] Customer ID
  - [x] Date
  - [x] Status badge (color-coded)
  - [x] Total amount
  - [x] View action link
- [x] Empty state messaging
- [x] Responsive design

### Admin Order Detail Page (public/pages/admin/order-detail.php)
- [x] A01 Authentication check:
  - [x] Vulnerable: Accessible to any logged-in user
  - [x] Secure: Require admin role
- [x] Order header with key info
- [x] Success/error message display
- [x] Complete order details:
  - [x] Order items
  - [x] Totals breakdown
  - [x] Shipping address
  - [x] Customer notes
- [x] Order management section:
  - [x] Status dropdown for updates
  - [x] Update status button
  - [x] Current status display
- [x] Invoice management section:
  - [x] Invoice upload form (A04 vulnerability):
    - [x] Vulnerable: Accept any file, save with original name
    - [x] Secure: Validate file type, rename with hash
  - [x] File selection UI
  - [x] Upload button
  - [x] Error messages
  - [x] Uploaded file display
  - [x] Download invoice button (A04 download vulnerability):
    - [x] Vulnerable: Serve file without type check
    - [x] Secure: Validate MIME type and path traversal

---

## Vulnerability Training - A01: Broken Access Control

### Vulnerable Scenarios Created:

#### Customer can view other customers' orders
- **Component**: order-history.php (vulnerable mode)
- **Impact**: When VULN_ACCESS_CONTROL=true, shows all orders in database
- **Test**: Login as customer, navigate to order-history.php, verify all users' orders visible

#### Customer can access other customers' order details
- **Component**: order-detail.php + OrderManagementService.getOrderWithAccessCheck()
- **Impact**: Customer can change URL from ?order_id=1 to ?order_id=2 and see any order
- **Test**: Login as customer, change order_id in URL, verify access to order not owned

#### Customer can access admin dashboard
- **Component**: admin/orders.php
- **Impact**: Any logged-in user can access admin features
- **Test**: Login as customer, navigate to /pages/admin/orders.php, verify access without error

#### Customer can modify order status
- **Component**: admin/order-detail.php + OrderManagementService.updateOrderStatus()
- **Impact**: Customer can change order status (e.g., pending to delivered)
- **Test**: Login as customer, access admin order detail, change status, verify change applied

### Secure Implementation (VULN_ACCESS_CONTROL=false):
- All customer-facing order pages check user ownership
- Admin pages require role === 'admin'
- OrderManagementService enforces role checks
- Unauthorized access returns 403 error

---

## Vulnerability Training - A04: Insecure Design/File Upload

### Vulnerable Scenarios Created:

#### Upload any file type (checkout.php)
- **Component**: checkout.php invoice upload
- **Impact**: Can upload .exe, .sh, or other executable files
- **Test**: Upload .exe file in vulnerable mode, verify accepted; secure mode should reject

#### Upload any file type (admin/order-detail.php)
- **Component**: admin/order-detail.php invoice upload
- **Impact**: Admin can be tricked into uploading malicious files
- **Test**: Upload .exe file in vulnerable mode, verify accepted; secure mode should reject

#### Original filename preserved (vulnerable mode)
- **Component**: ImageUploadService.uploadFileVulnerable()
- **Impact**: Same filename uploads could overwrite existing files
- **Test**: Upload file.pdf twice, verify second overwrites first in vulnerable mode

#### File download without MIME validation
- **Component**: admin/order-detail.php download handler (vulnerable mode)
- **Impact**: Could expose non-media files if path traversal works
- **Test**: Try accessing file with ../../../ paths in vulnerable mode

### Secure Implementation (VULN_INSECURE_UPLOAD=false):
- Only .pdf, .jpg, .png extensions allowed
- MIME type validated using finfo
- Files renamed with SHA256 hash (prevents overwrites)
- Path traversal prevention using realpath() validation  
- Downloads validate MIME type and file location

---

## Database Schema ✅

### Orders Table (already defined in schema.sql)
```sql
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    status ENUM('pending', 'paid', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10, 2) NOT NULL,
    shipping_address TEXT,
    customer_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);
```

### Order Items Table (already defined in schema.sql)
```sql
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_price DECIMAL(10, 2) NOT NULL,
    quantity INT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_order (order_id)
);
```

---

## Test Matrix

### Customer Workflow Tests

| Test Case | Vulnerable Mode | Secure Mode | Status |
|-----------|---|---|---|
| **Checkout Flow** | | | |
| Add items to cart → Checkout | ✓ Creates order, clears cart | ✓ Creates order, clears cart | ✓ |
| Cart empty redirect | ✓ Redirects to cart | ✓ Redirects to cart | ✓ |
| Order confirmation display | ✓ Shows correct order | ✓ Shows correct order | ✓ |
| **Order History** | | | |
| View own order in history | ✓ Visible | ✓ Visible | ✓ |
| View other's order in history | ✓ ALL visible (A01 vuln) | ✓ Only own visible | Test |
| **Order Detail** | | | |  
| Access own order | ✓ Allowed | ✓ Allowed | ✓ |
| Access other's order by URL change | ✓ Allowed (A01 vuln) | ✗ Denied (403) | Test |
| **File Upload** | | | |
| Upload PDF invoice | ✓ Accepted, original name | ✓ Accepted, hashed name | ✓ |
| Upload .exe file | ✓ Accepted (A04 vuln) | ✗ Rejected | Test |
| Upload .jpg image | ✓ Accepted, original name | ✓ Accepted, hashed name | ✓ |

### Admin Workflow Tests

| Test Case | Vulnerable Mode | Secure Mode | Status |
|-----------|---|---|---|
| **Admin Access** | | | |
| Customer accesses admin/orders.php | ✓ Access granted (A01 vuln) | ✗ Redirected to login | Test |
| Admin accesses admin/orders.php | ✓ Access granted | ✓ Access granted | ✓ |
| **Order Management** | | | |
| View all orders | ✓ All visible | ✓ All visible (admin role) | ✓ |
| Update order status as admin | ✓ Allowed | ✓ Allowed | ✓ |
| Update order status as customer | ✓ Allowed (A01 vuln) | ✗ Denied | Test |
| **Upload Handling** | | | |
| Upload .exe file as admin | ✓ Accepted (A04 vuln) | ✗ Rejected | Test |
| Upload PDF as admin | ✓ Accepted, original name | ✓ Accepted, hashed name | ✓ |
| Download invoice | ✓ Any file (no check) | ✓ MIME validated | ✓ |

---

## Key Design Decisions

1. **Order Number Format**: `YYYYMMDD-USERID-RANDOM6DIGITS`
   - Human-friendly for customer reference
   - Avoids sequential patterns
   - Includes timestamp for chronological sorting

2. **Mock Payment**: Click "Pay" button generates transaction ID and sets status to "paid"
   - No real payment gateway integration
   - Focus remains on vulnerability training, not e-commerce

3. **Tax Calculation**: 10% of subtotal
   - Applied at checkout + stored in order.total_amount
   - Matches Phase 2 cart display

4. **File Upload Strategy**:
   - Invoices stored in `public/uploads/` directory
   - Vulnerable mode: Original filename preserved
   - Secure mode: SHA256 hash + original extension
   - Both modes handle file type validation in dual-mode code paths

5. **Access Control Pattern**:
   - `isVulnerable('access_control')` helper used throughout
   - Customer pages check `user_id` or forward to secure service
   - Admin pages use role-based checks in OrderManagementService

6. **One-Way Status Flow**:
   - Pending → Paid → Shipped → Delivered (or Cancel)
   - No reverse transitions
   - Keeps Phase 3 logic simple

---

## Manual Testing Procedures

### Test A01: Broken Access Control

#### Scenario 1: Customer views other's order
```bash
1. Start app in VULNERABLE mode (VULN_ACCESS_CONTROL=true)
2. Create 2 user accounts (customer1, customer2)
3. As customer1: Place order #1
4. As customer2: Place order #2
5. As customer2: Navigate to /pages/order-history.php
   VULNERABLE: See both order #1 and #2
   SECURE: See only order #2
6. As customer2: Access /pages/order-detail.php?order_id=1
   VULNERABLE: Can view customer1's order (A01!)
   SECURE: See 403 Forbidden
```

#### Scenario 2: Customer accesses admin
```bash
1. Start app in VULNERABLE mode
2. As customer: Login and navigate to /pages/admin/orders.php
   VULNERABLE: Full admin dashboard accessible
   SECURE: Redirected to login with error
3. As admin: True admin can access (both modes)
```

#### Scenario 3: Customer modifies order status
```bash
1. As customer: Access /pages/admin/order-detail.php?order_id=1
   VULNERABLE: Can change status from pending to delivered
   SECURE: Cannot access (403)
```

### Test A04: Insecure File Upload

#### Scenario 1: Upload executable
```bash
1. Create a simple .exe or .sh file
2. In checkout or admin, attempt to upload file
   VULNERABLE: File accepted, saved as "file.exe"
   SECURE: Rejected with "Invalid file type" error
```

#### Scenario 2: Upload valid PDF
```bash
1. Create valid PDF file
   VULNERABLE: Saved as "invoice.pdf" (original name)
   SECURE: Saved as "abc123def456...pdf" (hashed)
2. Both modes accept and allow download
```

#### Scenario 3: Path traversal attempt (download)
```bash
1. Attempt to download files with path traversal: 
   `/pages/admin/order-detail.php?order_id=1&download=../../config.php`
   VULNERABLE: Could expose system files
   SECURE: realpath() validates path is in /uploads/ only
```

---

## Environment Testing

### Vulnerable Mode Config (.env)
```
APP_MODE=vulnerable
VULN_ACCESS_CONTROL=true
VULN_INSECURE_UPLOAD=true
```

### Secure Mode Config (.env)
```
APP_MODE=secure
VULN_ACCESS_CONTROL=false
VULN_INSECURE_UPLOAD=false
```

### Docker Testing
```bash
# Launch vulnerable config
APP_MODE=vulnerable VULN_ACCESS_CONTROL=true docker-compose up

# Launch secure config  
APP_MODE=secure VULN_ACCESS_CONTROL=false docker-compose up
```

---

## Learner Exercises

### Exercise 1: Exploit A01 (Broken Access Control)
**Objective**: Demonstrate why server-side authorization is critical

1. Start app in vulnerable mode
2. Login as customer A
3. Place order #1
4. In browser console or URL bar, change order_id to another customer's order
5. **Observation**: You can see another customer's order details
6. **Why**: No server-side check of order ownership
7. **Fix**: ProductService checks `user_id` before returning order in secure mode

### Exercise 2: Exploit A04 (Insecure Upload)  
**Objective**: Demonstrate file upload risks

1. Start app in vulnerable mode
2. Create a test file: `test.exe` or `test.js`
3. During checkout, attempt to upload this file
4. **Observation**: File accepted without validation
5. **Why**: No file type checking or MIME validation
6. **Fix**: Secure mode validates extension AND MIME type

### Exercise 3: Compare Modes
**Objective**: See same code behave differently

1. Start app in vulnerable mode, complete exercises 1-2
2. Stop app, change `.env` to secure mode
3. Repeat exercises 1-2
4. **Observation**: Same UI, completely different access control and validation
5. **Learning**: This is the intent of Phase 3 - show consequences of secure vs vulnerable patterns on identical functionality

---

## Files Modified/Created

**New Services**:
- src/Services/OrderService.php
- src/Services/OrderManagementService.php
- src/Services/ImageUploadService.php

**New Customer Pages**:
- public/pages/checkout.php
- public/pages/order-confirmation.php
- public/pages/order-history.php
- public/pages/order-detail.php

**New Admin Pages**:
- public/pages/admin/orders.php
- public/pages/admin/order-detail.php

**Modified Files**:
- public/pages/cart.php (checkout button updated)
- config/config.php (verified toggles exist)
- database/schema.sql (verified orders/order_items tables)

---

## Next Steps (Phase 4 Recommendations)

1. **Email Notifications**: Send order confirmation/status emails
2. **Inventory Management**: Decrease stock on order creation
3. **Payment Gateways**: Real Stripe/PayPal integration
4. **Admin Features**: Product CRUD, user management
5. **Audit Logging**: Structured event logging to audit_logs table
6. **Image Uploads**: Product image management (separate upload handler)
7. **Order History Export**: CSV/PDF export for customers/admins
8. **Review System**: Product reviews linked to orders

---

## Success Criteria

- [x] Customers can complete checkout flow with mock payment
- [x] Order history shows orders with proper filtering
- [x] Order detail shows complete information
- [x] Admin can view and manage all orders
- [x] A01 vulnerability visibly exploitable in vulnerable mode
- [x] A01 properly protected in secure mode
- [x] A04 vulnerability visibly exploitable in vulnerable mode
- [x] A04 properly protected in secure mode
- [x] All pages have proper session/role checks
- [x] Responsive design across all new pages
- [x] Error handling and user feedback

**Status**: ✅ **Phase 3 Core Complete**
