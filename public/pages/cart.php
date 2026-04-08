<?php
/**
 * Shopping Cart Page
 * Phase 2: E-commerce Features - Cart Management
 */

// Ensure autoloader is loaded (in case page is accessed directly)
if (!function_exists('__autoload')) {
    require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';
}

// Load config if not already loaded
if (!isset($config)) {
    $config = require_once dirname(dirname(__DIR__)) . '/config/config.php';
}

// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

use CakeShop\Services\CartService;

$isLoggedIn = isset($_SESSION['user_id']);

if (!$isLoggedIn) {
    header('Location: /pages/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/pages/cart.php'));
    exit;
}

$cartService = new CartService($config, $_SESSION['user_id']);

$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';

if (($_GET['action'] ?? '') === 'add') {
    $productId = (int)($_GET['product_id'] ?? 0);
    $quantity = max(1, (int)($_GET['quantity'] ?? 1));

    $result = $cartService->addToCart($productId, $quantity);

    if ($result['success']) {
        header('Location: /pages/cart.php?message=' . urlencode($result['message']));
        exit;
    }

    header('Location: /pages/cart.php?error=' . urlencode($result['message'] ?? 'Unable to add product'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);

    if ($action === 'update') {
        $quantity = max(0, (int)($_POST['quantity'] ?? 1));
        $updated = $cartService->updateCartItem($productId, $quantity);

        if ($updated) {
            header('Location: /pages/cart.php?message=' . urlencode('Cart updated successfully'));
            exit;
        }

        header('Location: /pages/cart.php?error=' . urlencode('Failed to update cart item'));
        exit;
    }

    if ($action === 'remove') {
        $removed = $cartService->removeFromCart($productId);

        if ($removed) {
            header('Location: /pages/cart.php?message=' . urlencode('Item removed from cart'));
            exit;
        }

        header('Location: /pages/cart.php?error=' . urlencode('Failed to remove item'));
        exit;
    }
}

$cartItems = $cartService->getCartItems();
$cartTotal = $cartService->getCartTotal();
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - <?php echo htmlspecialchars($config['app_name']); ?></title>
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
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        h1 {
            font-size: 28px;
            color: #2d5016;
            margin-bottom: 25px;
            font-weight: 300;
        }

        .cart-wrapper {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
            margin-bottom: 40px;
        }

        .cart-items {
            background-color: white;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .cart-item {
            display: grid;
            grid-template-columns: 80px 1fr 100px 80px 50px;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #eee;
            align-items: center;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #f5e6d3 0%, #e8d4ba 100%);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-info h3 {
            font-size: 14px;
            font-weight: 600;
            color: #2d5016;
            margin-bottom: 5px;
        }

        .item-info p {
            font-size: 12px;
            color: #999;
        }

        .item-price {
            font-size: 16px;
            font-weight: 600;
            color: #d45113;
            text-align: center;
        }

        .item-quantity {
            text-align: center;
        }

        .item-quantity input {
            width: 50px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 3px;
            text-align: center;
            font-size: 12px;
        }

        .item-remove {
            text-align: center;
        }

        .item-remove button {
            background: none;
            border: none;
            color: #d45113;
            cursor: pointer;
            font-size: 16px;
            transition: color 0.3s;
        }

        .item-remove button:hover {
            color: #c0350f;
        }

        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-cart i {
            font-size: 48px;
            color: #ddd;
            display: block;
            margin-bottom: 20px;
        }

        .empty-cart a {
            color: #2d5016;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            margin-top: 15px;
        }

        .cart-summary {
            background-color: white;
            border-radius: 4px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .cart-summary h3 {
            font-size: 16px;
            color: #2d5016;
            margin-bottom: 15px;
            font-weight: 600;
            padding-bottom: 10px;
            border-bottom: 2px solid #ddd;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 13px;
        }

        .summary-row.total {
            font-size: 18px;
            font-weight: bold;
            color: #d45113;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            margin-top: 15px;
        }

        .checkout-btn {
            width: 100%;
            background-color: #2d5016;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            margin-top: 20px;
            transition: background-color 0.3s;
        }

        .checkout-btn:hover {
            background-color: #3d6b1f;
        }

        .continue-shopping {
            width: 100%;
            background-color: #f0f0f0;
            color: #333;
            padding: 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            margin-top: 10px;
            transition: background-color 0.3s;
        }

        .continue-shopping:hover {
            background-color: #e0e0e0;
        }

        @media (max-width: 768px) {
            .cart-wrapper {
                grid-template-columns: 1fr;
            }

            .cart-summary {
                position: static;
            }

            .cart-item {
                grid-template-columns: 60px 1fr;
            }

            .item-price, .item-quantity, .item-remove {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-shopping-cart"></i> Shopping Cart</h1>

        <?php if ($message): ?>
        <div style="padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div style="padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <div class="cart-wrapper">
            <div class="cart-items">
                <?php if (empty($cartItems)): ?>
                <div class="empty-cart">
                    <i class="fas fa-inbox"></i>
                    <h3>Your cart is empty</h3>
                    <p>Add some delicious treats to get started!</p>
                    <a href="/catalog"><i class="fas fa-arrow-left"></i> Continue Shopping</a>
                </div>
                <?php else: ?>
                <div style="padding: 15px; background-color: #f9f9f9; font-size: 12px; color: #666;">
                    <strong><?php echo count($cartItems); ?></strong> item<?php echo count($cartItems) !== 1 ? 's' : ''; ?> in your cart
                </div>
                <?php foreach ($cartItems as $item): ?>
                <div class="cart-item">
                    <div class="item-image">
                        <?php if ($item['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <?php else: ?>
                            <i class="fas fa-cake-candles" style="font-size: 40px; color: #c9a961;"></i>
                        <?php endif; ?>
                    </div>

                    <div class="item-info">
                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                        <p>SKU: #<?php echo str_pad($item['product_id'], 5, '0', STR_PAD_LEFT); ?></p>
                    </div>

                    <div class="item-price">
                        $<?php echo number_format($item['price'], 2); ?>
                    </div>

                    <div class="item-quantity">
                        <form method="POST" action="" style="display: flex; align-items: center; gap: 8px; justify-content: center;">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="product_id" value="<?php echo (int)$item['product_id']; ?>">
                            <input type="number" name="quantity" value="<?php echo (int)$item['quantity']; ?>" min="1" max="<?php echo (int)$item['stock_quantity']; ?>">
                            <button type="submit" style="background: #2d5016; color: #fff; border: none; border-radius: 3px; padding: 5px 8px; font-size: 11px; cursor: pointer;">
                                Update
                            </button>
                        </form>
                    </div>

                    <div class="item-remove">
                        <form method="POST" action="" onsubmit="return confirm('Remove this item from cart?');">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="product_id" value="<?php echo (int)$item['product_id']; ?>">
                            <button type="submit" title="Remove item">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="cart-summary">
                <h3>Order Summary</h3>
                
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>$<?php echo number_format($cartTotal, 2); ?></span>
                </div>

                <div class="summary-row">
                    <span>Shipping:</span>
                    <span>Free</span>
                </div>

                <div class="summary-row">
                    <span>Tax:</span>
                    <span>$<?php echo number_format($cartTotal * 0.1, 2); ?></span>
                </div>

                <div class="summary-row total">
                    <span>Total:</span>
                    <span>$<?php echo number_format($cartTotal * 1.1, 2); ?></span>
                </div>

                <?php if (!empty($cartItems)): ?>
                <button class="checkout-btn" onclick="proceedToCheckout()">
                    <i class="fas fa-credit-card"></i> Proceed to Checkout
                </button>
                <?php endif; ?>

                <button class="continue-shopping" onclick="window.location.href='/catalog'">
                    <i class="fas fa-arrow-left"></i> Continue Shopping
                </button>
            </div>
        </div>
    </div>

    <script>
        function proceedToCheckout() {
            window.location.href = '/pages/checkout.php';
        }
    </script>
</body>
</html>
