<?php
/**
 * Order Detail Page
 * Phase 3: Order System - Customer Order Detail
 * Display full order details with access control
 * VULNERABILITY A01: Broken Access Control
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
use CakeShop\Services\OrderManagementService;

$isLoggedIn = isset($_SESSION['user_id']);

// Redirect if not logged in
if (!$isLoggedIn) {
    header('Location: /pages/login.php?redirect=/pages/order-detail.php');
    exit;
}

$orderId = $_GET['order_id'] ?? null;

if (!$orderId) {
    header('Location: /pages/order-history.php?error=invalid_order');
    exit;
}

$userRole = $_SESSION['user_role'] ?? 'customer';
$orderManagementService = new OrderManagementService($config, $_SESSION['user_id'], $userRole);

// This will handle A01 vulnerability check
$order = $orderManagementService->getOrderWithAccessCheck($orderId);

// Check for access denied
if ($order === null) {
    http_response_code(403);
    ?><!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Denied</title>
        <style>
            body {
                background-color: #f8f2e8;
                color: #333;
                font-family: "UTMAvo", "HelveticaNeue", "Helvetica Neue", Helvetica, Arial, sans-serif;
                padding: 50px 20px;
                text-align: center;
            }
            .error-box {
                background-color: white;
                padding: 40px;
                border-radius: 4px;
                max-width: 500px;
                margin: 0 auto;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            .error-icon {
                font-size: 48px;
                color: #dc3545;
                margin-bottom: 20px;
            }
            h1 {
                color: #2d5016;
                margin-bottom: 10px;
            }
            p {
                color: #666;
                margin-bottom: 20px;
            }
            a {
                color: #2d5016;
                text-decoration: none;
                font-weight: 600;
            }
            a:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <div class="error-box">
            <div class="error-icon">🔒</div>
            <h1>Access Denied</h1>
            <p>You don't have permission to view this order.</p>
            <a href="/pages/order-history.php">← Back to Orders</a>
        </div>
    </body>
    </html><?php
    exit;
}

// Map status to badge colors
$statusColors = [
    'pending' => '#ffc107',
    'paid' => '#007bff',
    'shipped' => '#ff9800',
    'delivered' => '#28a745',
    'cancelled' => '#dc3545',
];

$statusTexts = [
    'pending' => 'Pending',
    'paid' => 'Paid',
    'shipped' => 'Shipped',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled',
];

$statusIcons = [
    'pending' => 'fas fa-clock',
    'paid' => 'fas fa-credit-card',
    'shipped' => 'fas fa-truck',
    'delivered' => 'fas fa-check-circle',
    'cancelled' => 'fas fa-times-circle',
];
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - <?php echo htmlspecialchars($config['app_name']); ?></title>
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
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        h1 {
            font-size: 28px;
            color: #2d5016;
            font-weight: 300;
        }

        .back-link {
            color: #2d5016;
            text-decoration: none;
            font-size: 13px;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .order-header {
            background-color: white;
            border-radius: 4px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 30px;
        }

        .header-item {
            text-align: center;
        }

        .header-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .header-value {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .header-value.order-number {
            color: #2d5016;
            font-family: monospace;
        }

        .header-value.status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            color: white;
            font-size: 14px;
        }

        .order-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .order-section {
            background-color: white;
            border-radius: 4px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 16px;
            color: #2d5016;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2d5016;
        }

        .order-item {
            display: grid;
            grid-template-columns: 1fr 80px 80px;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #eee;
            align-items: center;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-name {
            font-weight: 600;
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
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #eee;
        }

        .total-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 20px;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .total-row.grand-total {
            font-size: 18px;
            font-weight: bold;
            color: #d45113;
            margin-top: 10px;
        }

        .sidebar-section {
            background-color: white;
            border-radius: 4px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .sidebar-title {
            font-size: 14px;
            color: #2d5016;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .info-text {
            font-size: 13px;
            color: #666;
            line-height: 1.8;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .status-timeline {
            margin-top: 15px;
        }

        .timeline-item {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            position: relative;
            padding-left: 0;
        }

        .timeline-item:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 12px;
            top: 30px;
            width: 2px;
            height: 20px;
            background-color: #ddd;
        }

        .timeline-icon {
            width: 28px;
            height: 28px;
            background-color: #f0f0f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2d5016;
            font-size: 14px;
            flex-shrink: 0;
        }

        .timeline-content {
            padding-top: 4px;
        }

        .timeline-status {
            font-weight: 600;
            font-size: 13px;
        }

        .timeline-time {
            font-size: 12px;
            color: #999;
            margin-top: 3px;
        }

        .vulnerability-notice {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 3px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .vulnerability-notice strong {
            color: #856404;
        }

        @media (max-width: 768px) {
            .order-header {
                grid-template-columns: 1fr;
            }

            .order-content {
                grid-template-columns: 1fr;
            }

            .order-item {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .item-qty, .item-price {
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-receipt"></i> Order Details</h1>
            <a href="/pages/order-history.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
        </div>

        <?php if (function_exists('isVulnerable') && isVulnerable('access_control')): ?>
        <div class="vulnerability-notice">
            <strong>⚠️ VULNERABILITY A01 ACTIVE:</strong> In vulnerable mode, you can access <strong>any order</strong> by changing the order_id parameter in the URL. This should only work for your own orders or if you're an admin.
        </div>
        <?php endif; ?>

        <!-- Order Header -->
        <div class="order-header">
            <div class="header-item">
                <div class="header-label">Order Number</div>
                <div class="header-value order-number"><?php echo htmlspecialchars($order['order_number']); ?></div>
            </div>

            <div class="header-item">
                <div class="header-label">Order Date</div>
                <div class="header-value"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></div>
            </div>

            <div class="header-item">
                <div class="header-label">Status</div>
                <div class="header-value status-badge" style="background-color: <?php echo $statusColors[$order['status']]; ?>;">
                    <i class="<?php echo $statusIcons[$order['status']]; ?>"></i>
                    <?php echo $statusTexts[$order['status']]; ?>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="order-content">
            <!-- Order Items -->
            <div class="order-section">
                <div class="section-title">
                    <i class="fas fa-box"></i> Order Items
                </div>

                <?php foreach ($order['items'] as $item): ?>
                <div class="order-item">
                    <span class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></span>
                    <span class="item-qty">×<?php echo $item['quantity']; ?></span>
                    <span class="item-price">$<?php echo number_format($item['product_price'] * $item['quantity'], 2); ?></span>
                </div>
                <?php endforeach; ?>

                <!-- Order Totals -->
                <div class="order-totals">
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
                        <span>Total Amount:</span>
                        <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div>
                <!-- Shipping Address -->
                <?php if ($order['shipping_address']): ?>
                <div class="sidebar-section">
                    <div class="sidebar-title"><i class="fas fa-map-marker-alt"></i> Shipping Address</div>
                    <div class="info-text"><?php echo htmlspecialchars($order['shipping_address']); ?></div>
                </div>
                <?php endif; ?>

                <!-- Customer Notes -->
                <?php if ($order['customer_notes']): ?>
                <div class="sidebar-section">
                    <div class="sidebar-title"><i class="fas fa-sticky-note"></i> Special Notes</div>
                    <div class="info-text"><?php echo htmlspecialchars($order['customer_notes']); ?></div>
                </div>
                <?php endif; ?>

                <!-- Status Timeline -->
                <div class="sidebar-section">
                    <div class="sidebar-title"><i class="fas fa-history"></i> Status Timeline</div>
                    <div class="status-timeline">
                        <div class="timeline-item">
                            <div class="timeline-icon">
                                <i class="fas fa-plus"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-status">Order Created</div>
                                <div class="timeline-time"><?php echo date('M d, Y \a\t H:i', strtotime($order['created_at'])); ?></div>
                            </div>
                        </div>

                        <?php if ($order['status'] !== 'pending'): ?>
                        <div class="timeline-item">
                            <div class="timeline-icon">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-status">Payment Received</div>
                                <div class="timeline-time"><?php echo date('M d, Y \a\t H:i', strtotime($order['updated_at'])); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (in_array($order['status'], ['shipped', 'delivered'])): ?>
                        <div class="timeline-item">
                            <div class="timeline-icon">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-status">Shipped</div>
                                <div class="timeline-time"><?php echo date('M d, Y', strtotime($order['updated_at'])); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($order['status'] === 'delivered'): ?>
                        <div class="timeline-item">
                            <div class="timeline-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-status">Delivered</div>
                                <div class="timeline-time"><?php echo date('M d, Y \a\t H:i', strtotime($order['updated_at'])); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($order['status'] === 'cancelled'): ?>
                        <div class="timeline-item">
                            <div class="timeline-icon">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-status">Cancelled</div>
                                <div class="timeline-time"><?php echo date('M d, Y \a\t H:i', strtotime($order['updated_at'])); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
