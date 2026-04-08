# Phase 2: E-Commerce Features - Quick Start Guide

## What's Been Built

### ✅ Complete E-Commerce Home Page
- Modern, responsive product catalog interface
- Category filtering sidebar
- Full-text search functionality with pagination
- Professional cake shop design theme
- Mobile-friendly layout

### ✅ Product Management Services
- **ProductService** - Query products, categories, search
- **CartService** - Manage shopping carts per user
- **AuthService** - User login/registration with security training
- **ReviewService** - Product reviews and ratings

### ✅ Customer-Facing Pages
1. **Home** (`/`) - Product catalog with filters & search
2. **Product Detail** (`/pages/product-detail.php?id=X`) - Detailed product view with reviews
3. **Shopping Cart** (`/pages/cart.php`) - View and manage cart items
4. **Login** (`/pages/login.php`) - User authentication
5. **Support Pages** (placeholders) - FAQ, shipping, returns, privacy

### ✅ Security Features
- **Intentional Vulnerabilities** (VULNERABLE mode)
  - SQL injection in product search
  - Weak password hashing (MD5)
  - Information leakage in errors
  
- **Secure Implementations** (SECURE mode)
  - Prepared SQL statements
  - BCrypt password hashing
  - Account lockout & rate limiting
  - Generic error messages

---

## How to Use

### Basic Navigation
```
Home (/) 
  ↓ Search or browse categories
  ↓ Click product to view details
  ↓ Add to cart
  ↓ Checkout (coming Phase 3)
```

### Testing the Features

#### 1. Browse Products
```
Visit: http://localhost:8080/
- Click categories in sidebar to filter
- Use search bar to find products
- View pagination for large results
```

#### 2. View Product Details
```
Click any product card to see:
- Full description
- Customer reviews
- Stock availability
- Rating distribution
- Add to cart option
```

#### 3. Login
```
Visit: http://localhost:8080/pages/login.php
- Demo account: demo@cake-shop.local (if set up)
- Will be persistent across pages
- Required for cart & checkout
```

#### 4. Shopping Cart
```
After login, click cart to:
- View items
- Adjust quantities
- Remove items
- See order summary
- Proceed to checkout (Phase 3)
```

---

## File Structure Created

```
src/Services/
  ├── ProductService.php      ← Product & category queries
  ├── CartService.php         ← Shopping cart operations
  ├── AuthService.php         ← User authentication
  └── ReviewService.php       ← Product reviews

public/pages/
  ├── home.php                ← Main catalog page (REBUILT)
  ├── product-detail.php      ← Single product view (NEW)
  ├── cart.php                ← Shopping cart (NEW)
  ├── login.php               ← User login (NEW)
  ├── wishlist.php            ← Placeholder
  ├── account.php             ← Placeholder
  ├── about.php               ← Placeholder
  ├── faq.php                 ← Placeholder
  ├── shipping.php            ← Placeholder
  ├── returns.php             ← Placeholder
  ├── privacy.php             ← Placeholder
  └── contact.php             ← Placeholder

docs/
  └── PHASE2.md               ← Detailed documentation
```

---

## Visual Design

### Color Palette
```
Primary:     #2d5016 (Sage Green)
Accent:      #c9a961 (Gold)
Alert/Price: #d45113 (Burnt Orange)
Background:  #f8f2e8 (Cream)
```

### Layout Features
- **Header**: Logo, search, user menu, cart
- **Sidebar**: Category filters
- **Main Content**: Product grid (3-4 columns responsive)
- **Footer**: Links, company info, contact
- **Cards**: Hover effects, stock indicators, quick actions

---

## Security Training Features

### Learning the Vulnerabilities

#### SQL Injection
```php
// VULNERABLE (searchable in VULNERABLE mode)
Search: ' OR '1'='1
  → Returns all products regardless of search term

// SECURE (uses prepared statements)
Search: ' OR '1'='1
  → Treats as literal search term, no results
```

#### Weak Authentication
```php
// VULNERABLE mode:
md5("password123") = 482c811da5d5b4bc6d497ffa98491e38
// Easily cracked with rainbow tables

// SECURE mode:
password_hash("password123", PASSWORD_BCRYPT)
// $2y$12$... (bcrypt, much stronger)
```

#### Account Lockout
```
VULNERABLE mode:
- No lockout, unlimited retry
- Easy brute force attacks

SECURE mode:
- 5 failed attempts = 1-hour lockout
- Rate limiting per IP (implement in Phase 3)
```

### Exercises for Learners

**Beginner:**
1. Browse products and manage cart
2. Search for specific cakes
3. Review product details
4. Log in and create account

**Intermediate:**
1. Try SQL injection in search (VULNERABLE mode)
2. Compare search results between modes
3. Inspect password hashing differences
4. Review code implementations

**Advanced:**
1. Craft advanced SQL injection payloads
2. Analyze authentication flow
3. Test session security
4. Document vulnerability patterns

---

## Configuration

### Environment (.env file)
```env
APP_MODE=vulnerable              # or 'secure'
APP_NAME=Cake Shop Training Lab
APP_URL=http://localhost:8080
APP_DEBUG=true

DB_HOST=localhost
DB_PORT=3306
DB_NAME=cake_shop
DB_USER=cake_shop_user
DB_PASSWORD=your_password
```

### Switching Modes
```bash
# VULNERABLE mode (intentional weaknesses)
APP_MODE=vulnerable

# SECURE mode (best practices)
APP_MODE=secure
```

---

## Known Limitations & TODOs

### Not Yet Implemented
- ❌ AJAX add-to-cart (shows placeholder)
- ❌ Wishlist
- ❌ Advanced product filtering
- ❌ Product recommendations
- ❌ Guest checkout
- ❌ Multiple payment methods
- ❌ Email notifications
- ❌ Admin panel

### Placeholder Pages
These have links but no functionality yet:
- `/pages/wishlist.php`
- `/pages/account.php`
- `/pages/about.php`
- `/pages/contact.php`
- `/pages/register.php`
- `/pages/forgot-password.php`

---

## Database Setup

### Required Tables
Ensure schema has these tables (created in Phase 1):
```sql
- users (email, password_hash, role, etc.)
- categories (name, description, image_url, etc.)
- products (name, price, category_id, stock_quantity, etc.)
- cart_items (user_id, product_id, quantity)
- orders (user_id, total_amount, status, etc.)
- reviews (product_id, user_id, rating, comment, etc.)
```

### Seed Data
Add sample products and categories:
```sql
INSERT INTO categories (name, description) VALUES
  ('Chocolate Cakes', '...'),
  ('Fruit Cakes', '...'),
  ('Cheesecakes', '...'),
  ...

INSERT INTO products (category_id, name, price, stock_quantity) VALUES
  (1, 'Chocolate Fudge Cake', 25.99, 5),
  (2, 'Strawberry Shortcake', 22.50, 8),
  ...
```

---

## API Endpoints (Phase 3)

These are implemented in pages but not as separate API endpoints yet:

```
GET  /                           → Home (all products)
GET  /pages/product-detail.php?id=X   → Product details
GET  /pages/cart.php             → Shopping cart
POST /pages/login.php            → User login

Coming in Phase 3:
POST /api/cart/add               → Add to cart
POST /api/cart/update            → Update cart item
POST /api/cart/remove            → Remove from cart
POST /api/auth/register          → User registration
POST /api/reviews/create         → Submit review
```

---

## Testing Checklist

### Functionality
- [ ] Browse all products on home page
- [ ] Filter by category
- [ ] Search for products
- [ ] View product details
- [ ] See available stock
- [ ] Read customer reviews
- [ ] Login page displays
- [ ] Cart page displays
- [ ] Responsive on mobile

### Security (VULNERABLE Mode)
- [ ] Search accepts SQL injection
- [ ] Password not hashed with bcrypt
- [ ] No account lockout
- [ ] Error messages may leak info

### Security (SECURE Mode)
- [ ] Search uses prepared statements
- [ ] Passwords use bcrypt
- [ ] Account locks after 5 failures
- [ ] Generic error messages

### Design
- [ ] Colors match theme
- [ ] Layout responsive
- [ ] Icons display properly
- [ ] Hover effects work
- [ ] Stock badges show correctly

---

## Next Steps (Phase 3)

### Checkout System
```
Cart → Shipping Address → Payment Method → Confirmation
```

### Order Management
- Create orders from cart
- Track order status
- Generate receipts
- Send order emails

### Customer Enhancements
- Order history
- Address book
- Saved preferences
- Loyalty rewards

---

## Common Issues & Fixes

### Products Not Showing
```
1. Check database connection in config.php
2. Verify categories table has data
3. Verify products table has data
4. Check browser console for errors
```

### Search Not Working
```
1. Ensure ProductService is loaded
2. Check search term in URL (?search=term)
3. Verify products exist with matching names
4. Check database charset (utf8mb4)
```

### Login Not Working
```
1. Start session in index.php: session_start();
2. Check users table has test account
3. Verify password hashing matches mode
4. Check cookie settings in php.ini
```

### Cart Empty
```
1. Must be logged in to use cart
2. Check session is persisting
3. Verify cart_items table exists
4. Check user_id in session
```

---

## Support & Questions

### Documentation
- See [PHASE2.md](docs/PHASE2.md) for detailed technical docs
- See [plan.md](plan.md) for overall project roadmap
- Check inline code comments for implementation details

### Training Resources
- OWASP Top 10: https://owasp.org/Top10/
- SQL Injection Guide: https://owasp.org/www-community/attacks/SQL_Injection
- Password Security: https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html

---

## Project Status

**Phase 1:** ✅ COMPLETE (Core Setup)
**Phase 2:** ✅ COMPLETE (E-Commerce Features)
**Phase 3:** 📋 NEXT (Checkout & Orders)
**Phase 4:** ⏳ TODO (Admin Panel)
**Phase 5:** ⏳ TODO (Testing & Documentation)

---

*This is a training project designed to teach web security vulnerabilities in a safe, controlled environment. All content is intentionally simplified for educational purposes.*
