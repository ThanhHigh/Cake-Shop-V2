# Phase 2: Basic E-Commerce Features

## Overview
Phase 2 implements the complete customer-facing e-commerce interface for the Cake Shop training lab. This phase includes:
- Product catalog browsing and filtering
- Shopping cart management
- User authentication (login/registration framework)
- Customer reviews and ratings
- Responsive web design with security training features

## Architecture

### Services Layer
Located in `src/Services/`, these services handle all business logic:

#### ProductService
Manages product and category data with SQL injection vulnerability in VULNERABLE mode.

**Key Methods:**
```php
// Fetch all categories
$categories = $productService->getAllCategories();

// Get products with optional filtering
$products = $productService->getAllProducts($limit = 50, $offset = 0);

// Search products (VULNERABLE to SQLi in vulnerable mode)
$results = $productService->searchProducts($searchTerm);

// Get single product
$product = $productService->getProductById($productId);
```

**Vulnerability Learning Point:**
In VULNERABLE mode, the `searchProducts()` method demonstrates SQL injection:
```php
// VULNERABLE (vulnerable mode)
$sql = "SELECT ... WHERE p.name LIKE '%{$searchTerm}%'";  // String concatenation

// SECURE (secure mode)
$sql = "SELECT ... WHERE p.name LIKE ?";  // Prepared statement
$results = $db->query($sql, ["%{$searchTerm}%"]);
```

#### CartService
Handles shopping cart operations per user session.

**Key Methods:**
```php
// Add item to cart
$cartService->addToCart($productId, $quantity = 1);

// Get user's cart items
$cartItems = $cartService->getCartItems();

// Update quantity
$cartService->updateCartItem($productId, $quantity);

// Remove item
$cartService->removeFromCart($productId);

// Get cart totals
$total = $cartService->getCartTotal();
```

#### AuthService
Manages user authentication with intentional security weaknesses for training.

**Key Methods:**
```php
// Register new user (weak hashing in vulnerable mode)
$authService->register($email, $password, $fullName);

// Authenticate user
$result = $authService->login($email, $password);

// Check current user
$user = $authService->getCurrentUser();

// Logout
$authService->logout();
```

**Authentication Vulnerabilities:**
- **VULNERABLE mode**: Uses MD5 hashing (easily cracked)
- **SECURE mode**: Uses bcrypt with cost factor 12, rate limiting, account lockout

#### ReviewService
Manages product reviews and ratings.

**Key Methods:**
```php
// Submit review
$reviewService->addReview($productId, $userId, $rating, $title, $comment);

// Get reviews
$reviews = $reviewService->getProductReviews($productId);

// Average rating
$avg = $reviewService->getAverageRating($productId);
```

## Pages

### Home Page (`public/pages/home.php`)
Main product catalog interface.

**Features:**
- Category sidebar filter
- Full-text search with pagination
- 12 products per page
- Product cards with:
  - Product image (placeholder if missing)
  - Category tag
  - Product name & description (truncated)
  - Price
  - Stock indicator
  - View / Add to Cart buttons
- Responsive grid layout
- Header with search bar, user menu, cart
- Footer with links and contact info

**Key Parameters:**
```php
?category=1              // Filter by category
?search=chocolate        // Search products
?page=2                  // Pagination
```

### Product Detail Page (`public/pages/product-detail.php`)
Individual product view with full information and customer reviews.

**Features:**
- Large product image
- Detailed product information
- Price and stock status
- Quantity selector
- Add to Cart & Wishlist buttons
- Customer reviews section
- Review submission form (for logged-in users)
- Rating distribution
- Breadcrumb navigation

**URL:**
```php
/pages/product-detail.php?id=5
```

### Shopping Cart (`public/pages/cart.php`)
View and manage shopping cart.

**Features:**
- List of cart items with thumbnails
- Quantity adjustments
- Remove items
- Order summary with:
  - Subtotal
  - Shipping
  - Tax calculation
  - Total
- Proceed to Checkout button
- Continue Shopping link

**Requirements:**
- User must be logged in (redirects to login if not)

### Login Page (`public/pages/login.php`)
User authentication interface.

**Features:**
- Email and password form
- Remember me checkbox
- Error/success messages
- Links to registration and forgot password
- Demo credentials hint
- Vulnerability warning banner (training mode)

**Features:**
- Form validation
- Session creation on successful login
- Account lockout after 5 failed attempts (secure mode)
- Supports redirect parameter: `/pages/login.php?redirect=/pages/cart.php`

## Styling & Design

### Color Scheme
- **Primary Green**: #2d5016 (headers, buttons)
- **Accent Gold**: #c9a961 (highlights, prices)
- **Warn Red**: #d45113 (prices, out-of-stock)
- **Background**: #f8f2e8 (cream/beige)

### Typography
- Font: "UTMAvo", "HelveticaNeue", sans-serif
- Base size: 14px
- Weights: 300 (light), 600 (bold)

### Responsive Breakpoints
- Mobile: < 768px (single column layout)
- Tablet: < 1200px
- Desktop: 1400px max-width container

## Database Schema Integration

### Tables Used
- `users` - Customer accounts
- `categories` - Product categories
- `products` - Product catalog
- `cart_items` - User shopping carts
- `reviews` - Product reviews

### Key Queries
```sql
-- Get active categories
SELECT * FROM categories WHERE is_active = TRUE

-- Get products with category
SELECT p.*, c.name as category_name 
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
WHERE p.is_active = TRUE

-- Get user's cart
SELECT c.*, p.price, p.name, p.image_url
FROM cart_items c
JOIN products p ON c.product_id = p.id
WHERE c.user_id = ?
```

## Security Considerations

### Intentional Vulnerabilities (Training)
These are enabled in VULNERABLE mode for security training:

1. **SQL Injection** (ProductService.searchProducts)
   - Learn: Parameterized vs concatenated queries
   - Exploit: Search `' OR '1'='1`
   - Fix: Use prepared statements

2. **Weak Password Hashing** (AuthService.register/login)
   - Learn: MD5 vs bcrypt
   - Attack: Rainbow tables
   - Fix: Use bcrypt with appropriate cost factor

3. **Information Leakage** (AuthService.login)
   - Learn: Error message consistency
   - Leak: Email existence determination
   - Fix: Generic error messages

### Security Best Practices (Secure Mode)
- Prepared statements for all SQL
- Password hashing with bcrypt (cost 12)
- Rate limiting & account lockout
- Session management
- Input validation
- Output encoding with htmlspecialchars()

## Testing & Exercises

### Beginner Exercises
1. **Browse Products**
   - Navigate categories
   - View product details
   - Observe product information

2. **Search Basics**
   - Search for specific cakes
   - Try basic SQL injection: `' OR 1=1`
   - Compare vulnerable vs secure results

3. **Authentication**
   - Register a new account (observe hashing difference)
   - Login with correct/incorrect credentials
   - Check account lockout behavior (secure mode)

### Intermediate Exercises
1. **SQL Injection Deep Dive**
   - Craft payloads: `'; DROP TABLE products; --`
   - Extract data: `UNION SELECT`
   - Compare fix between modes

2. **Cart & Checkout**
   - Add items to cart
   - Modify quantities
   - Observe price calculations

### Advanced Exercises
1. **Session Hijacking**
   - Inspect cookies/sessions
   - Attempt session fixation
   - Compare secure mode protection

2. **Authentication Bypass**
   - Test weak password validation
   - Attempt brute force (rate limiting in secure mode)
   - Review password storage differences

## API Endpoints (Phase 3)

These are placeholders for implementation in Phase 3:
- `POST /api/cart/add` - Add item to cart
- `POST /api/cart/update` - Update cart item
- `DELETE /api/cart/item/{id}` - Remove item
- `POST /api/reviews/create` - Submit review
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout
- `POST /api/auth/register` - User registration

## Frontend Enhancement Notes

### JavaScript Functions Implemented
- `viewProduct(productId)` - Navigate to product detail
- `addToCart(productId)` - Add item (placeholder)
- `updateCartBadge()` - Update cart count
- `updateCartItem()` - Update quantity (placeholder)
- `removeFromCart()` - Delete item (placeholder)

### TODO Placeholders
- AJAX cart operations (currently show alerts)
- Real-time cart updates
- Product wishlist functionality
- Product image gallery
- Advanced filtering & sorting
- Related products suggestions

## Vulnerability Training Guide

### For Instructors
1. Start students in SECURE mode to understand functionality
2. Switch to VULNERABLE mode and demonstrate each weakness
3. Have students identify the vulnerability
4. Show the secure implementation
5. Have students fix the vulnerable code

### For Self-Study
1. Set `APP_MODE=vulnerable` in `.env`
2. Complete beginner exercises
3. Progress to intermediate (SQL injection concepts)
4. Try advanced (session/auth testing)
5. Review source code to find all vulnerabilities
6. Switch to secure mode and compare implementations
7. Implement fixes and document learning

## Configuration

### Environment Variables (.env)
```env
APP_MODE=vulnerable              # vulnerable or secure
APP_NAME=Cake Shop Training Lab
APP_URL=http://localhost:8080
DB_HOST=localhost
DB_PORT=3306
DB_NAME=cake_shop
DB_USER=cake_user
DB_PASSWORD=secure_password
```

### Per-Feature Toggles (Future)
```php
'vulnerabilities' => [
    'sql_injection' => true,
    'weak_auth' => true,
    'xss' => false,
    'csrf' => false,
    ...
]
```

## Performance Considerations

- Uses sticky headers for navigation
- Lazy-load product images
- Pagination limits (12-50 items per page)
- Database indexing on frequently searched fields
- Session-based cart (no database hits for every load)

## Accessibility

- Semantic HTML5 structure
- Alt text for images
- ARIA labels for buttons
- Keyboard navigation support
- Color contrast compliance
- Form labels properly associated

## Next Steps (Phase 3)

1. **Checkout Flow**
   - Shipping address
   - Payment method selection
   - Order confirmation

2. **Order Management**
   - Order history for customers
   - Order tracking
   - Order cancellation

3. **Email Notifications**
   - Welcome email
   - Order confirmation
   - Shipping notifications

4. **Admin Interface** (Phase 4)
   - Product management
   - Order management
   - User management
   - Analytics dashboard

## References

- [OWASP Top 10 2021](https://owasp.org/Top10/)
- [OWASP Authentication](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html)
- [PHP Security Best Practices](https://www.php.net/manual/en/security.php)
- [SQL Injection Prevention](https://owasp.org/www-community/attacks/SQL_Injection)
