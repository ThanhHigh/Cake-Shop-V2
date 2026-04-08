<?php
/**
 * Product Detail Page
 * Phase 2: E-commerce Features - Individual Product View
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
use CakeShop\Services\ReviewService;
use CakeShop\Services\CartService;

// Initialize services
$productService = new ProductService($config);
$reviewService = new ReviewService($config);
$cartService = new CartService($config, $_SESSION['user_id'] ?? null);

$cartMessage = '';
$cartError = '';

// Get product ID from URL
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$productId) {
    header('Location: /');
    exit;
}

// Fetch product details
$product = $productService->getProductById($productId);

if (!$product) {
    header('HTTP/1.0 404 Not Found');
    echo "Product not found";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_to_cart') {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /pages/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/pages/product-detail.php?id=' . $productId));
        exit;
    }

    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    $result = $cartService->addToCart($productId, $quantity);

    if ($result['success']) {
        $cartMessage = $result['message'];
    } else {
        $cartError = $result['message'] ?? 'Unable to add item to cart';
    }
}

// Get product reviews
$reviews = $reviewService->getProductReviews($productId);
$avgRating = $reviewService->getAverageRating($productId);
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - <?php echo htmlspecialchars($config['app_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #f8f2e8;
            color: #333;
            font-family: "UTMAvo", "HelveticaNeue", "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 14px;
            font-weight: 300;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .breadcrumb {
            margin-bottom: 20px;
            font-size: 13px;
        }

        .breadcrumb a {
            color: #2d5016;
            text-decoration: none;
            margin: 0 5px;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .product-detail {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            background-color: white;
            padding: 30px;
            border-radius: 4px;
            margin-bottom: 40px;
        }

        .product-image-main {
            width: 100%;
            height: 400px;
            background: linear-gradient(135deg, #f5e6d3 0%, #e8d4ba 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            overflow: hidden;
        }

        .product-image-main img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info h1 {
            font-size: 32px;
            color: #2d5016;
            margin-bottom: 10px;
            font-weight: 300;
        }

        .product-meta {
            font-size: 13px;
            color: #999;
            margin-bottom: 20px;
        }

        .product-rating {
            margin-bottom: 20px;
            font-size: 14px;
        }

        .rating-stars {
            color: #f0ad4e;
            margin-right: 10px;
        }

        .product-price {
            font-size: 36px;
            color: #d45113;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .product-description {
            color: #666;
            line-height: 1.8;
            margin-bottom: 25px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 4px;
        }

        .product-stock {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 4px;
            font-weight: 600;
        }

        .stock-available {
            background-color: #d4edda;
            color: #155724;
        }

        .stock-limited {
            background-color: #fff3cd;
            color: #856404;
        }

        .stock-unavailable {
            background-color: #f8d7da;
            color: #721c24;
        }

        .product-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quantity-selector input {
            width: 50px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
            text-align: center;
            font-size: 14px;
        }

        .btn-add-cart {
            flex: 1;
            background-color: #2d5016;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
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

        .btn-wishlist {
            background-color: #f0f0f0;
            color: #d45113;
            padding: 12px 20px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        .btn-wishlist:hover {
            background-color: #e0e0e0;
        }

        .reviews-section {
            background-color: white;
            padding: 30px;
            border-radius: 4px;
            margin-bottom: 40px;
        }

        .reviews-section h2 {
            font-size: 24px;
            color: #2d5016;
            margin-bottom: 20px;
            font-weight: 300;
        }

        .review-form {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 13px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-family: inherit;
            font-size: 13px;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn-submit {
            background-color: #c9a961;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        .btn-submit:hover {
            background-color: #b8954f;
        }

        .review-item {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .review-item:last-child {
            border-bottom: none;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .review-author {
            font-weight: 600;
            color: #333;
        }

        .review-date {
            font-size: 12px;
            color: #999;
        }

        .review-rating {
            color: #f0ad4e;
            margin-bottom: 8px;
        }

        .review-text {
            color: #666;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .product-detail {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .product-image-main {
                height: 300px;
            }

            .product-info h1 {
                font-size: 24px;
            }

            .product-actions {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($cartMessage): ?>
        <div style="padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($cartMessage); ?>
        </div>
        <?php endif; ?>

        <?php if ($cartError): ?>
        <div style="padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($cartError); ?>
        </div>
        <?php endif; ?>

        <div class="breadcrumb">
            <a href="/"><i class="fas fa-home"></i> Home</a>
            <span>/</span>
            <a href="/catalog?category=<?php echo $product['category_id']; ?>">
                <?php echo htmlspecialchars($product['category_name']); ?>
            </a>
            <span>/</span>
            <span><?php echo htmlspecialchars($product['name']); ?></span>
        </div>

        <div class="product-detail">
            <div class="product-image-main">
                <?php if ($product['image_url']): ?>
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                <?php else: ?>
                    <i class="fas fa-cake-candles" style="font-size: 120px; color: #c9a961;"></i>
                <?php endif; ?>
            </div>

            <div class="product-info">
                <div class="product-meta">
                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($product['category_name']); ?>
                </div>

                <h1><?php echo htmlspecialchars($product['name']); ?></h1>

                <div class="product-rating">
                    <span class="rating-stars">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <?php if ($i < floor($avgRating)): ?>
                                <i class="fas fa-star"></i>
                            <?php else: ?>
                                <i class="far fa-star"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </span>
                    <span>(<?php echo count($reviews); ?> reviews)</span>
                </div>

                <div class="product-price">
                    <span style="font-size: 24px;">$</span><?php echo number_format($product['price'], 2); ?>
                </div>

                <div class="product-description">
                    <?php echo htmlspecialchars($product['description']); ?>
                </div>

                <div class="product-stock <?php 
                    echo $product['stock_quantity'] > 10 ? 'stock-available' :
                         ($product['stock_quantity'] > 0 ? 'stock-limited' : 'stock-unavailable');
                ?>">
                    <?php if ($product['stock_quantity'] > 0): ?>
                        <i class="fas fa-check-circle"></i> In Stock (<?php echo $product['stock_quantity']; ?> available)
                    <?php else: ?>
                        <i class="fas fa-times-circle"></i> Out of Stock
                    <?php endif; ?>
                </div>

                <div class="product-actions">
                    <div class="quantity-selector">
                        <label for="quantity" style="margin: 0; font-size: 13px;">Qty:</label>
                        <input type="number" id="quantity" min="1" max="<?php echo $product['stock_quantity']; ?>" value="1">
                    </div>
                    <button class="btn-add-cart" type="button" onclick="addToCart()"
                            <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                        <i class="fas fa-cart-plus"></i> Add to Cart
                    </button>
                </div>

                <button class="btn-wishlist">
                    <i class="fas fa-heart"></i> Add to Wishlist
                </button>

                <div style="margin-top: 30px; padding: 15px; background-color: #f9f9f9; border-radius: 4px; font-size: 12px; color: #666;">
                    <p><strong>Shipping:</strong> Free shipping on orders over $50</p>
                    <p style="margin-top: 8px;"><strong>Returns:</strong> 30-day money-back guarantee</strong></p>
                </div>
            </div>
        </div>

        <div class="reviews-section">
            <h2><i class="fas fa-comments"></i> Customer Reviews</h2>

            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="review-form">
                <h3 style="margin-bottom: 15px; font-size: 16px;">Leave a Review</h3>
                <div id="reviewMessage" style="display: none; padding: 12px; margin-bottom: 15px; border-radius: 4px; font-size: 13px;"></div>
                <form id="reviewForm" method="POST" action="/api/reviews/create">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">

                    <div class="form-group">
                        <label for="rating">Rating:</label>
                        <select id="rating" name="rating" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 3px; font-size: 13px;">
                            <option value="">-- Select Rating --</option>
                            <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                            <option value="4">⭐⭐⭐⭐ Very Good</option>
                            <option value="3">⭐⭐⭐ Good</option>
                            <option value="2">⭐⭐ Fair</option>
                            <option value="1">⭐ Poor</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="title">Title:</label>
                        <input type="text" id="title" name="title" placeholder="Summary of your review" required>
                    </div>

                    <div class="form-group">
                        <label for="comment">Your Review:</label>
                        <textarea id="comment" name="comment" placeholder="Share your experience..." required></textarea>
                    </div>

                    <button type="submit" id="submitBtn" class="btn-submit">Submit Review</button>
                </form>
            </div>
            
            <script>
                document.getElementById('reviewForm').addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const messageDiv = document.getElementById('reviewMessage');
                    const submitBtn = document.getElementById('submitBtn');
                    
                    // Clear previous messages
                    messageDiv.style.display = 'none';
                    messageDiv.innerHTML = '';
                    messageDiv.className = '';
                    
                    // Disable submit button during request
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Submitting...';
                    
                    try {
                        const formData = new FormData(this);
                        const response = await fetch('/api/reviews/create', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const data = await response.json();
                        
                        if (response.ok) {
                            // Success
                            messageDiv.style.backgroundColor = '#d4edda';
                            messageDiv.style.borderColor = '#c3e6cb';
                            messageDiv.style.color = '#155724';
                            messageDiv.style.border = '1px solid';
                            messageDiv.innerHTML = '✓ ' + data.message;
                            messageDiv.style.display = 'block';
                            
                            // Clear form
                            document.getElementById('reviewForm').reset();
                            
                            // Re-enable button
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Submit Review';
                        } else {
                            // Error
                            messageDiv.style.backgroundColor = '#f8d7da';
                            messageDiv.style.borderColor = '#f5c6cb';
                            messageDiv.style.color = '#721c24';
                            messageDiv.style.border = '1px solid';
                            messageDiv.innerHTML = '✗ ' + (data.error || 'An error occurred.');
                            messageDiv.style.display = 'block';
                            
                            // Check for 401 - redirect to login
                            if (response.status === 401) {
                                setTimeout(() => {
                                    window.location.href = '/pages/login.php?redirect=' + encodeURIComponent(window.location.href);
                                }, 2000);
                            }
                            
                            // Re-enable button
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Submit Review';
                        }
                    } catch (err) {
                        messageDiv.style.backgroundColor = '#f8d7da';
                        messageDiv.style.borderColor = '#f5c6cb';
                        messageDiv.style.color = '#721c24';
                        messageDiv.style.border = '1px solid';
                        messageDiv.innerHTML = '✗ Network error: ' + err.message;
                        messageDiv.style.display = 'block';
                        
                        // Re-enable button
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Submit Review';
                    }
                });
            </script>
            <?php else: ?>
            <div style="padding: 15px; background-color: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; margin-bottom: 25px; color: #0c5460;">
                <a href="/pages/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? '/pages/product-detail.php?id=' . $productId); ?>" style="color: #0c5460; font-weight: 600;">Log in</a> to leave a review
            </div>
            <?php endif; ?>

            <?php if (empty($reviews)): ?>
            <p style="text-align: center; color: #999; padding: 30px 0;">No reviews yet. Be the first to review!</p>
            <?php else: ?>
            <div style="margin-top: 25px;">
                <?php foreach ($reviews as $review): ?>
                <div class="review-item">
                    <div class="review-header">
                        <span class="review-author"><?php echo htmlspecialchars($review['author_name']); ?></span>
                        <span class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                    </div>
                    <div class="review-rating">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <?php if ($i < $review['rating']): ?>
                                <i class="fas fa-star"></i>
                            <?php else: ?>
                                <i class="far fa-star"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                    <h4 style="margin-bottom: 8px; font-size: 14px; color: #333;">
                        <?php echo htmlspecialchars($review['title']); ?>
                    </h4>
                    <div class="review-text">
                        <?php echo htmlspecialchars($review['comment']); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function addToCart() {
            const quantityInput = document.getElementById('quantity');
            const form = document.getElementById('addToCartForm');
            if (!quantityInput || !form) {
                return;
            }

            form.querySelector('input[name="quantity"]').value = quantityInput.value;
            form.submit();
        }
    </script>

    <form id="addToCartForm" method="POST" action="" style="display: none;">
        <input type="hidden" name="action" value="add_to_cart">
        <input type="hidden" name="quantity" value="1">
    </form>
</body>
</html>
