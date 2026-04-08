<?php
/**
 * Home Page - Cake Shop Customer Interface
 * Phase 2: E-commerce Features - Product Catalog & Shopping
 * 
 * Features:
 * - Category browsing and filtering
 * - Product catalog with search
 * - Add to cart functionality
 * - Shopping cart preview
 * 
 * VULNERABILITY DISCLAIMER:
 * This page contains intentional security weaknesses for training purposes.
 * Mode: <?php echo strtoupper(getenv('APP_MODE') ?: 'vulnerable'); ?>
 */

// Ensure autoloader is loaded (in case page is accessed directly)
if (!function_exists('__autoload')) {
    require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';
}

// Load config if not already loaded
if (!isset($config)) {
    $config = require_once dirname(dirname(__DIR__)) . '/config/config.php';
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

use CakeShop\Services\ProductService;
use CakeShop\Services\CartService;

// Initialize services
$productService = new ProductService($config);
$userId = $_SESSION['user_id'] ?? null;
$cartService = new CartService($config, $userId);

// Get user session info (simplified for now)
$isLoggedIn = isset($_SESSION['user_id']);

$cartMessage = $_GET['cart_message'] ?? '';
$cartError = $_GET['cart_error'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_to_cart') {
    if (!$isLoggedIn) {
        header('Location: /pages/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/catalog'));
        exit;
    }

    $productIdToAdd = (int)($_POST['product_id'] ?? 0);
    $quantityToAdd = max(1, (int)($_POST['quantity'] ?? 1));
    $result = $cartService->addToCart($productIdToAdd, $quantityToAdd);

    if ($result['success']) {
        header('Location: /catalog?cart_message=' . urlencode($result['message']));
        exit;
    }

    header('Location: /catalog?cart_error=' . urlencode($result['message'] ?? 'Unable to add item to cart'));
    exit;
}

$cartCount = $isLoggedIn ? $cartService->getCartItemCount() : 0;

// Get request parameters
$categoryId = isset($_GET['category']) ? intval($_GET['category']) : null;
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$itemsPerPage = 12;
$offset = ($page - 1) * $itemsPerPage;

// Fetch data
$allCategories = $productService->getAllCategories();
$products = [];
$totalProducts = 0;

if ($searchTerm) {
    // Search results
    $products = $productService->searchProducts($searchTerm, $itemsPerPage, $offset);
    $totalProducts = count($products); // Simplified count
} elseif ($categoryId) {
    // Category filtered products
    $selectedCategory = $productService->getCategoryById($categoryId);
    $products = $productService->getProductsByCategory($categoryId, $itemsPerPage, $offset);
    $totalProducts = $productService->getProductCount($categoryId);
} else {
    // All products
    $products = $productService->getAllProducts($itemsPerPage, $offset);
    $totalProducts = $productService->getProductCount();
}

$totalPages = ceil($totalProducts / $itemsPerPage);
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($config['app_name']); ?> - Premium Cakes & Pastries</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ========== RESET & BASE ========== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
        }

        body {
            background-color: #f8f2e8;
            color: #333;
            font-family: "UTMAvo", "HelveticaNeue", "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 14px;
            font-weight: 300;
            line-height: 1.6;
        }

        /* ========== HEADER & NAVIGATION ========== */
        header {
            background: linear-gradient(135deg, #2d5016 0%, #3d6b1f 100%);
            color: white;
            padding: 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-top {
            background-color: #8b9e57;
            padding: 8px 0;
            font-size: 12px;
            text-align: center;
        }

        .header-top span {
            margin: 0 15px;
        }

        .header-main {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 20px;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            font-size: 28px;
        }

        .search-bar {
            flex: 1;
            max-width: 400px;
            margin: 0 30px;
        }

        .search-bar form {
            display: flex;
        }

        .search-bar input {
            flex: 1;
            padding: 10px 15px;
            border: none;
            border-radius: 3px 0 0 3px;
            font-size: 13px;
        }

        .search-bar button {
            padding: 10px 20px;
            background-color: #c9a961;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 13px;
            border-radius: 0 3px 3px 0;
            transition: background-color 0.3s;
        }

        .search-bar button:hover {
            background-color: #b8954f;
        }

        .header-actions {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .header-actions a {
            color: white;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 12px;
            transition: color 0.3s;
        }

        .header-actions a:hover {
            color: #c9a961;
        }

        .header-actions i {
            font-size: 20px;
            margin-bottom: 3px;
        }

        .cart-badge {
            background-color: #d45113;
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            position: absolute;
            right: -8px;
            top: -8px;
        }

        .header-actions .cart-link {
            position: relative;
        }

        /* ========== VULNERABILITY WARNING BANNER ========== */
        <?php if ($config['app_mode'] === 'vulnerable') { ?>
        .vulnerability-banner {
            background-color: #d9534f;
            color: white;
            padding: 12px 20px;
            text-align: center;
            font-weight: bold;
            font-size: 13px;
        }

        .vulnerability-banner i {
            margin-right: 8px;
        }
        <?php } ?>

        /* ========== BANNER SECTION ========== */
        .banner {
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), 
                        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 400"><rect fill="%23f8d4a8" width="1200" height="400"/></svg>');
            background-size: cover;
            background-position: center;
            padding: 60px 20px;
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }

        .banner h1 {
            font-size: 48px;
            margin-bottom: 10px;
            font-weight: 300;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .banner p {
            font-size: 18px;
            margin-bottom: 20px;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
        }

        .banner .btn-primary {
            display: inline-block;
            background-color: #c9a961;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
            font-weight: bold;
        }

        .banner .btn-primary:hover {
            background-color: #b8954f;
        }

        /* ========== MAIN CONTAINER ========== */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* ========== SIDEBAR & FILTERS ========== */
        .content-wrapper {
            display: flex;
            gap: 30px;
            margin-bottom: 40px;
        }

        .sidebar {
            width: 220px;
            background-color: white;
            padding: 20px;
            border-radius: 4px;
            height: fit-content;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .sidebar h3 {
            font-size: 16px;
            margin-bottom: 15px;
            color: #2d5016;
            font-weight: 600;
            text-transform: uppercase;
            border-bottom: 2px solid #c9a961;
            padding-bottom: 10px;
        }

        .category-list {
            list-style: none;
        }

        .category-list li {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .category-list li:last-child {
            border-bottom: none;
        }

        .category-list a {
            color: #333;
            text-decoration: none;
            display: block;
            padding: 5px 0;
            transition: color 0.3s;
            font-size: 13px;
        }

        .category-list a:hover,
        .category-list a.active {
            color: #c9a961;
            font-weight: 600;
        }

        .search-filters {
            background-color: white;
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .search-filters h3 {
            font-size: 14px;
            margin-bottom: 15px;
            font-weight: 600;
            color: #2d5016;
        }

        .filter-group {
            margin-bottom: 15px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 13px;
        }

        .clear-filters {
            background-color: #f0f0f0;
            color: #333;
            padding: 8px 12px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            width: 100%;
            transition: background-color 0.3s;
        }

        .clear-filters:hover {
            background-color: #e0e0e0;
        }

        /* ========== MAIN CONTENT ========== */
        .main-content {
            flex: 1;
        }

        .section-title {
            font-size: 28px;
            color: #2d5016;
            margin-bottom: 25px;
            font-weight: 300;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .section-title i {
            font-size: 32px;
            color: #c9a961;
        }

        /* ========== PRODUCT GRID ========== */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .product-card {
            background-color: white;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }

        .product-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #f5e6d3 0%, #e8d4ba 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-image-placeholder {
            font-size: 60px;
            color: #c9a961;
        }

        .stock-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(0,0,0,0.75);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .stock-badge.in-stock {
            background-color: #5cb85c;
        }

        .stock-badge.low-stock {
            background-color: #f0ad4e;
        }

        .stock-badge.out-of-stock {
            background-color: #d9534f;
        }

        .product-info {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .product-category {
            font-size: 11px;
            color: #c9a961;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .product-name {
            font-size: 16px;
            color: #2d5016;
            font-weight: 600;
            margin-bottom: 8px;
            line-height: 1.3;
            min-height: 2.6em;
        }

        .product-description {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
            line-height: 1.4;
            flex-grow: 1;
        }

        .product-rating {
            font-size: 12px;
            color: #f0ad4e;
            margin-bottom: 10px;
        }

        .product-price {
            font-size: 24px;
            color: #d45113;
            font-weight: bold;
            margin-bottom: 12px;
        }

        .product-price .currency {
            font-size: 16px;
        }

        .product-actions {
            display: flex;
            gap: 8px;
            margin-top: auto;
        }

        .btn-view {
            flex: 1;
            background-color: #f0f0f0;
            color: #333;
            padding: 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        .btn-view:hover {
            background-color: #e0e0e0;
        }

        .btn-add-cart {
            flex: 1;
            background-color: #2d5016;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        .btn-add-cart:hover:not(:disabled) {
            background-color: #3d6b1f;
        }

        .btn-add-cart:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        /* ========== PAGINATION ========== */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin: 40px 0;
        }

        .pagination a,
        .pagination span {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 3px;
            text-decoration: none;
            color: #333;
            font-size: 13px;
            transition: background-color 0.3s, color 0.3s;
        }

        .pagination a:hover {
            background-color: #c9a961;
            color: white;
            border-color: #c9a961;
        }

        .pagination .active {
            background-color: #2d5016;
            color: white;
            border-color: #2d5016;
        }

        .pagination .disabled {
            color: #999;
            cursor: not-allowed;
        }

        /* ========== NO RESULTS ========== */
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .no-results i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 20px;
            display: block;
        }

        .no-results h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #666;
        }

        /* ========== FOOTER ========== */
        footer {
            background-color: #2d5016;
            color: white;
            padding: 40px 20px;
            margin-top: 60px;
        }

        footer .container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        footer h4 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 15px;
            border-bottom: 2px solid #c9a961;
            padding-bottom: 10px;
        }

        footer ul {
            list-style: none;
        }

        footer ul li {
            margin-bottom: 8px;
        }

        footer ul li a {
            color: #c9a961;
            text-decoration: none;
            font-size: 13px;
            transition: color 0.3s;
        }

        footer ul li a:hover {
            color: white;
        }

        .footer-bottom {
            border-top: 1px solid #4a6b2e;
            padding-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #999;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .content-wrapper {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }

            .header-main {
                flex-wrap: wrap;
            }

            .search-bar {
                max-width: 100%;
                order: 3;
                margin: 10px 0 0 0;
                width: 100%;
            }

            .search-bar form {
                width: 100%;
            }

            .banner h1 {
                font-size: 32px;
            }
        }

        /* ========== MODALS & ALERTS ========== */
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <header>
        <?php if ($config['app_mode'] === 'vulnerable') { ?>
        <div class="vulnerability-banner">
            <i class="fas fa-exclamation-triangle"></i>
            TRAINING MODE - INTENTIONAL VULNERABILITIES - LOCAL ONLY
        </div>
        <?php } ?>

        <div class="header-top">
            <span><i class="fas fa-phone"></i> +1 (555) 123-4567</span>
            <span><i class="fas fa-envelope"></i> info@cakeshop.local</span>
            <span><i class="fas fa-clock"></i> Mon-Fri: 9AM-6PM | Sat-Sun: 10AM-5PM</span>
        </div>

        <div class="header-main">
            <div class="logo">
                <a href="/catalog" style="color: white; text-decoration: none; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-cake-candles"></i>
                    <span><?php echo htmlspecialchars($config['app_name']); ?></span>
                </a>
            </div>

            <div class="search-bar">
                <form method="GET" action="/catalog">
                    <input type="text" name="search" placeholder="Search cakes, pastries..." 
                           value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                </form>
            </div>

            <div class="header-actions">
                <a href="/" title="Info">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <a href="/pages/wishlist.php" title="Wishlist">
                    <i class="fas fa-heart"></i>
                    <span>Wishlist</span>
                </a>
                <?php if ($isLoggedIn) { ?>
                <a href="/pages/account.php" title="My Account">
                    <i class="fas fa-user"></i>
                    <span>Account</span>
                </a>
                <?php } else { ?>
                <a href="/pages/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? '/catalog'); ?>" title="Login">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                </a>
                <?php } ?>
                <a href="/pages/cart.php" class="cart-link" title="Shopping Cart">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Cart</span>
                    <span class="cart-badge"><?php echo (int)$cartCount; ?></span>
                </a>
            </div>
        </div>
    </header>

    <!-- BANNER SECTION -->
    <div class="banner">
        <h1>🎂 Welcome to Our Premium Cake Shop 🎂</h1>
        <p>Handcrafted cakes and pastries made fresh to order</p>
        <a href="#products" class="btn-primary">Shop Now</a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="container">
        <?php if ($cartMessage): ?>
        <div class="message-success" style="margin-bottom: 20px;">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($cartMessage); ?>
        </div>
        <?php endif; ?>

        <?php if ($cartError): ?>
        <div class="message-error" style="margin-bottom: 20px;">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($cartError); ?>
        </div>
        <?php endif; ?>

        <div class="content-wrapper">
            <!-- SIDEBAR (FILTERS) -->
            <aside class="sidebar">
                <h3><i class="fas fa-filter"></i> Categories</h3>
                <ul class="category-list">
                    <li>
                        <a href="/catalog" <?php echo !$categoryId && !$searchTerm ? 'class="active"' : ''; ?>>
                            <i class="fas fa-th"></i> All Products
                        </a>
                    </li>
                    <?php foreach ($allCategories as $cat): ?>
                    <li>
                        <a href="/catalog?category=<?php echo $cat['id']; ?>" 
                           <?php echo $categoryId === $cat['id'] ? 'class="active"' : ''; ?>>
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($cat['name']); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($categoryId || $searchTerm): ?>
                <div style="margin-top: 20px;">
                    <a href="/catalog" class="clear-filters">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                </div>
                <?php endif; ?>
            </aside>

            <!-- MAIN PRODUCTS SECTION -->
            <main class="main-content" id="products">
                <?php if ($searchTerm): ?>
                <div class="section-title">
                    <i class="fas fa-search"></i>
                    Search Results for "<?php echo htmlspecialchars($searchTerm); ?>"
                </div>
                <?php elseif ($categoryId && isset($selectedCategory)): ?>
                <div class="section-title">
                    <i class="fas fa-tag"></i>
                    <?php echo htmlspecialchars($selectedCategory['name']); ?>
                </div>
                <?php if ($selectedCategory['description']): ?>
                <p style="margin-bottom: 20px; color: #666; font-size: 14px;">
                    <?php echo htmlspecialchars($selectedCategory['description']); ?>
                </p>
                <?php endif; ?>
                <?php else: ?>
                <div class="section-title">
                    <i class="fas fa-box"></i>
                    NEW CAKE COLLECTION
                </div>
                <?php endif; ?>

                <?php if (empty($products)): ?>
                <div class="no-results">
                    <i class="fas fa-inbox"></i>
                    <h3>No products found</h3>
                    <p>Try adjusting your filters or search terms</p>
                </div>
                <?php else: ?>
                <!-- PRODUCT GRID -->
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php if ($product['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php else: ?>
                                <div class="product-image-placeholder">
                                    <i class="fas fa-cake-candles"></i>
                                </div>
                            <?php endif; ?>
                            
                            <span class="stock-badge <?php 
                                echo $product['stock_quantity'] > 5 ? 'in-stock' : 
                                     ($product['stock_quantity'] > 0 ? 'low-stock' : 'out-of-stock');
                            ?>">
                                <?php 
                                    echo $product['stock_quantity'] > 0 ? 
                                        'Stock: ' . $product['stock_quantity'] : 'Out of Stock';
                                ?>
                            </span>
                        </div>

                        <div class="product-info">
                            <?php if ($product['category_name']): ?>
                            <div class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></div>
                            <?php endif; ?>
                            
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            
                            <?php if ($product['description']): ?>
                            <p class="product-description">
                                <?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>
                                <?php echo strlen($product['description']) > 100 ? '...' : ''; ?>
                            </p>
                            <?php endif; ?>
                            
                            <div class="product-price">
                                <span class="currency">$</span><?php echo number_format($product['price'], 2); ?>
                            </div>

                            <div class="product-actions">
                                <button class="btn-view" onclick="viewProduct(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                <button class="btn-add-cart" 
                                        onclick="addToCart(<?php echo $product['id']; ?>)"
                                        <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                                    <i class="fas fa-cart-plus"></i> Add
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- PAGINATION -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php 
                    $queryParams = [];
                    if ($categoryId) $queryParams['category'] = $categoryId;
                    if ($searchTerm) $queryParams['search'] = $searchTerm;
                    $baseUrl = '/catalog?' . http_build_query($queryParams);
                    ?>
                    
                    <?php if ($page > 1): ?>
                    <a href="<?php echo $baseUrl . '&page=' . ($page - 1); ?>">← Previous</a>
                    <?php else: ?>
                    <span class="disabled">← Previous</span>
                    <?php endif; ?>

                    <?php 
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    
                    if ($start > 1): ?>
                    <a href="<?php echo $baseUrl . '&page=1'; ?>">1</a>
                    <?php if ($start > 2): ?>
                    <span>...</span>
                    <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $start; $i <= $end; $i++): ?>
                    <?php if ($i == $page): ?>
                    <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                    <a href="<?php echo $baseUrl . '&page=' . $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($end < $totalPages): ?>
                    <?php if ($end < $totalPages - 1): ?>
                    <span>...</span>
                    <?php endif; ?>
                    <a href="<?php echo $baseUrl . '&page=' . $totalPages; ?>"><?php echo $totalPages; ?></a>
                    <?php endif; ?>

                    <?php if ($page < $totalPages): ?>
                    <a href="<?php echo $baseUrl . '&page=' . ($page + 1); ?>">Next →</a>
                    <?php else: ?>
                    <span class="disabled">Next →</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        <div class="container">
            <div>
                <h4><i class="fas fa-info-circle"></i> About Us</h4>
                <p style="font-size: 13px; line-height: 1.6; color: #c9a961;">
                    Welcome to <?php echo htmlspecialchars($config['app_name']); ?>! We've been serving 
                    delicious handcrafted cakes and pastries since 2020. Each creation is made with love 
                    and the finest ingredients.
                </p>
            </div>

            <div>
                <h4><i class="fas fa-bars"></i> Quick Links</h4>
                <ul>
                    <li><a href="/">Home</a></li>
                    <li><a href="#products">Products</a></li>
                    <li><a href="/pages/about.php">About Us</a></li>
                    <li><a href="/pages/contact.php">Contact</a></li>
                </ul>
            </div>

            <div>
                <h4><i class="fas fa-user"></i> Customer Service</h4>
                <ul>
                    <li><a href="/pages/faq.php">FAQ</a></li>
                    <li><a href="/pages/shipping.php">Shipping Info</a></li>
                    <li><a href="/pages/returns.php">Returns & Refunds</a></li>
                    <li><a href="/pages/privacy.php">Privacy Policy</a></li>
                </ul>
            </div>

            <div>
                <h4><i class="fas fa-phone"></i> Contact Info</h4>
                <p style="font-size: 13px; margin-bottom: 10px;">
                    <i class="fas fa-phone"></i> +1 (555) 123-4567
                </p>
                <p style="font-size: 13px; margin-bottom: 10px;">
                    <i class="fas fa-envelope"></i> info@cakeshop.local
                </p>
                <p style="font-size: 13px;">
                    <i class="fas fa-clock"></i> Mon-Fri: 9AM-6PM<br>
                    Sat-Sun: 10AM-5PM
                </p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2024 <?php echo htmlspecialchars($config['app_name']); ?>. All rights reserved. | 
               OWASP Training Lab - Local Use Only</p>
        </div>
    </footer>

    <script>
        // View product details
        function viewProduct(productId) {
            window.location.href = '/pages/product-detail.php?id=' + productId;
        }

        // Submit add-to-cart request to backend
        function addToCart(productId) {
            <?php if (!$isLoggedIn): ?>
            window.location.href = '/pages/login.php?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
            return;
            <?php endif; ?>

            const addToCartForm = document.getElementById('addToCartForm');
            if (!addToCartForm) {
                return;
            }

            addToCartForm.querySelector('input[name="product_id"]').value = String(productId);
            addToCartForm.querySelector('input[name="quantity"]').value = '1';
            addToCartForm.submit();
        }

        // Update cart badge count
        function updateCartBadge() {
            const badge = document.querySelector('.cart-badge');
            if (!badge) {
                return;
            }

            badge.textContent = '<?php echo (int)$cartCount; ?>';
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            updateCartBadge();
        });
    </script>

    <form id="addToCartForm" method="POST" action="/catalog" style="display: none;">
        <input type="hidden" name="action" value="add_to_cart">
        <input type="hidden" name="product_id" value="0">
        <input type="hidden" name="quantity" value="1">
    </form>
</body>
</html>
