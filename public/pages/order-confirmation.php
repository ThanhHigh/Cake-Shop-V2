<?php
/**
 * Order Confirmation Page
 * Phase 3: Order System - Customer Order Confirmation
 * Shows order summary after successful checkout
 */

// Ensure autoloader is loaded
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

use CakeShop\Services\OrderService;

$isLoggedIn = isset($_SESSION['user_id']);

// Redirect if not logged in
if (!$isLoggedIn) {
    header('Location: /pages/login.php?redirect=/pages/order-confirmation.php');
    exit;
}

$orderId = $_GET['order_id'] ?? null;

if (!$orderId) {
    header('Location: /pages/home.php?error=invalid_order');
    exit;
}

$orderService = new OrderService($config, $_SESSION['user_id']);
$order = $orderService->getOrderById($orderId);

// Verify order belongs to current user
if (!$order || $order['user_id'] != $_SESSION['user_id']) {
    header('Location: /pages/home.php?error=unauthorized');
    exit;
}

// Calculate delivery estimate: current date + 5-10 business days
$estimatedDelivery = new DateTime();
$deliveryDays = rand(5, 10);
$deliveryDays += ceil($deliveryDays / 5);  // Add weekends (rough estimate)
$estimatedDelivery->modify("+$deliveryDays days");
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - <?php echo htmlspecialchars($config['app_name']); ?></title>
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
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .success-container {
            background-color: white;
            border-radius: 4px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }

        .success-icon {
            font-size: 60px;
            color: #28a745;
            margin-bottom: 20px;
        }

        h1 {
            font-size: 28px;
            color: #2d5016;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
        }

        .order-number {
            background-color: #f8f2e8;
            padding: 20px;
            border-radius: 4px;
            margin: 30px 0;
            border-left: 4px solid #2d5016;
        }

        .order-number label {
            display: block;
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .order-number span {
            font-size: 24px;
            font-weight: bold;
            color: #2d5016;
            font-family: monospace;
        }

        .order-details {
            text-align: left;
            margin: 30px 0;
        }

        .detail-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }

        .detail-item {
            background-color: #f8f2e8;
            padding: 15px;
            border-radius: 4px;
        }

        .detail-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }

        .order-items {
            background-color: #f8f2e8;
            padding: 20px;
            border-radius: 4px;
            margin: 30px 0;
            text-align: left;
        }

        .order-items h3 {
            color: #2d5016;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .order-item {
            display: grid;
            grid-template-columns: 1fr 80px 80px;
            gap: 15px;
            padding: 10px;
            border-bottom: 1px solid #ddd;
            align-items: center;
            font-size: 14px;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-qty {
            text-align: center;
            color: #666;
        }

        .item-price {
            text-align: right;
            font-weight: 600;
        }

        .order-totals {
            display: grid;
            grid-template-columns: 1fr 150px;
            gap: 20px;
            margin-top: 20px;
            border-top: 2px solid #ddd;
            padding-top: 15px;
        }

        .total-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 15px;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .total-row.grand-total {
            font-size: 18px;
            font-weight: bold;
            color: #d45113;
            margin-top: 10px;
        }

        .shipping-address {
            background-color: #f8f2e8;
            padding: 15px;
            border-radius: 4px;
            text-align: left;
            font-size: 14px;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 30px 0 0 0;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: background-color 0.3s;
        }

        .btn-primary {
            background-color: #2d5016;
            color: white;
        }

        .btn-primary:hover {
            background-color: #3d6b1f;
        }

        .btn-secondary {
            background-color: #f0f0f0;
            color: #333;
        }

        .btn-secondary:hover {
            background-color: #e0e0e0;
        }

        .info-box {
            background-color: #e8f5e9;
            border-left: 4px solid #28a745;
            padding: 15px;
            border-radius: 3px;
            margin: 20px 0;
            font-size: 14px;
        }

        .info-box strong {
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-container">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>

            <h1>Order Confirmed!</h1>
            <p class="subtitle">Thank you for your order. We'll get started on it right away.</p>

            <div class="order-number">
                <label>Order Number</label>
                <span><?php echo htmlspecialchars($order['order_number']); ?></span>
            </div>

            <!-- Order Details Grid -->
            <div class="order-details">
                <div class="detail-row">
                    <div class="detail-item">
                        <div class="detail-label">Order Date</div>
                        <div class="detail-value"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Estimated Delivery</div>
                        <div class="detail-value"><?php echo $estimatedDelivery->format('M d, Y'); ?></div>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-item">
                        <div class="detail-label">Order Status</div>
                        <div class="detail-value">
                            <span style="background-color: #007bff; color: white; padding: 4px 8px; border-radius: 3px; font-size: 12px;">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Order Total</div>
                        <div class="detail-value" style="color: #d45113;">$<?php echo number_format($order['total_amount'], 2); ?></div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="order-items">
                <h3><i class="fas fa-box"></i> Order Items</h3>
                <?php foreach ($order['items'] as $item): ?>
                <div class="order-item">
                    <span><?php echo htmlspecialchars($item['product_name']); ?></span>
                    <span class="item-qty">×<?php echo $item['quantity']; ?></span>
                    <span class="item-price">$<?php echo number_format($item['product_price'] * $item['quantity'], 2); ?></span>
                </div>
                <?php endforeach; ?>

                <div class="order-totals">
                    <div style="text-align: right;">
                        <div class="total-row">
                            <span>Subtotal:</span>
                            <span>$<?php echo number_format($order['total_amount'] / 1.1, 2); ?></span>
                        </div>
                        <div class="total-row">
                            <span>Tax (10%):</span>
                            <span>$<?php echo number_format($order['total_amount'] * 0.1 / 1.1, 2); ?></span>
                        </div>
                        <div class="total-row">
                            <span>Shipping:</span>
                            <span>FREE</span>
                        </div>
                        <div class="total-row grand-total">
                            <span>Total:</span>
                            <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shipping Address -->
            <?php if ($order['shipping_address']): ?>
            <div style="text-align: left; margin: 30px 0;">
                <h3 style="color: #2d5016; margin-bottom: 10px; font-size: 14px;">Shipping Address</h3>
                <div class="shipping-address">
                    <?php echo htmlspecialchars($order['shipping_address']); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Customer Notes -->
            <?php if ($order['customer_notes']): ?>
            <div style="text-align: left; margin: 20px 0;">
                <h3 style="color: #2d5016; margin-bottom: 10px; font-size: 14px;">Special Notes</h3>
                <div class="shipping-address">
                    <?php echo htmlspecialchars($order['customer_notes']); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Info Box -->
            <div class="info-box">
                <strong><i class="fas fa-info-circle"></i> Next Steps:</strong><br>
                We'll send you an email confirmation shortly. You can track your order status at any time from your account.
            </div>

            <!-- Action Buttons -->
            <div class="actions">
                <a href="/pages/order-detail.php?order_id=<?php echo urlencode($order['id']); ?>" class="btn btn-primary">
                    <i class="fas fa-receipt"></i> View Order Details
                </a>
                <a href="/pages/home.php" class="btn btn-secondary">
                    <i class="fas fa-shopping-bag"></i> Continue Shopping
                </a>
            </div>
        </div>
    </div>
</body>
</html>
