<?php
/**
 * Order History Page
 * Phase 3: Order System - Customer Order History
 * Display all customer orders with ability to view details
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

$isLoggedIn = isset($_SESSION['user_id']);

// Redirect if not logged in
if (!$isLoggedIn) {
    header('Location: /pages/login.php?redirect=/pages/order-history.php');
    exit;
}

$orderService = new OrderService($config, $_SESSION['user_id']);

// VULNERABILITY A01: Broken Access Control
// In vulnerable mode: Query receives no user_id filter, shows ALL orders
// In secure mode: Only current user's orders shown
if (function_exists('isVulnerable') && isVulnerable('access_control')) {
    // VULNERABLE: Get ALL orders from database (not just user's)
    $sql = "SELECT id, user_id, order_number, status, total_amount, created_at, updated_at
            FROM orders
            ORDER BY created_at DESC";
    
    // Directly query database to bypass OrderService's user_id check
    $db = \CakeShop\Services\Database::getInstance($config);
    $orders = $db->queryAll($sql, []);
} else {
    // SECURE: Only get current user's orders
    $orders = $orderService->getMyOrders();
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
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - <?php echo htmlspecialchars($config['app_name']); ?></title>
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
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
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

        .orders-table {
            width: 100%;
            background-color: white;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background-color: #2d5016;
            color: white;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        tbody tr:hover {
            background-color: #f8f2e8;
        }

        .order-number {
            font-weight: 600;
            color: #2d5016;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }

        .order-total {
            font-weight: 600;
            color: #d45113;
        }

        .view-btn {
            background-color: #2d5016;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }

        .view-btn:hover {
            background-color: #3d6b1f;
        }

        .empty-state {
            background-color: white;
            border-radius: 4px;
            padding: 60px 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .empty-icon {
            font-size: 60px;
            color: #ccc;
            margin-bottom: 20px;
        }

        .empty-text {
            color: #666;
            margin-bottom: 20px;
        }

        .continue-btn {
            display: inline-block;
            background-color: #2d5016;
            color: white;
            padding: 10px 20px;
            border-radius: 3px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .continue-btn:hover {
            background-color: #3d6b1f;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-history"></i> Order History</h1>
            <a href="/pages/home.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Shop
            </a>
        </div>

        <?php if (function_exists('isVulnerable') && isVulnerable('access_control') && !empty($orders)): ?>
        <div class="vulnerability-notice">
            <strong>⚠️ VULNERABILITY A01 ACTIVE:</strong> You are viewing <strong>ALL orders in the system</strong> regardless of ownership. In secure mode, you would only see your own orders. This demonstrates Broken Access Control.
        </div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-inbox"></i>
            </div>
            <p class="empty-text">No orders yet. Start shopping to place your first order!</p>
            <a href="/pages/home.php" class="continue-btn">
                <i class="fas fa-shopping-bag"></i> Continue Shopping
            </a>
        </div>
        <?php else: ?>
        <div class="orders-table">
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>
                            <span class="order-number">
                                <?php echo htmlspecialchars($order['order_number']); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                        </td>
                        <td>
                            <span class="status-badge" style="background-color: <?php echo $statusColors[$order['status']]; ?>;">
                                <?php echo $statusTexts[$order['status']]; ?>
                            </span>
                        </td>
                        <td>
                            <span class="order-total">
                                $<?php echo number_format($order['total_amount'], 2); ?>
                            </span>
                        </td>
                        <td>
                            <a href="/pages/order-detail.php?order_id=<?php echo urlencode($order['id']); ?>" class="view-btn">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
