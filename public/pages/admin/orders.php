<?php
/**
 * Admin Orders Dashboard
 * Phase 3: Order System - Admin Order Management
 * VULNERABILITY A01: Broken Access Control
 */

// Ensure autoloader is loaded
if (!function_exists('__autoload')) {
    require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
}

// Load config if not already loaded
if (!isset($config)) {
    $config = require_once dirname(__DIR__, 3) . '/config/config.php';
}

// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

use CakeShop\Services\OrderManagementService;

$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['user_role'] ?? 'customer';

// VULNERABILITY A01: Admin access control check
// In vulnerable mode: Anyone can access this page
// In secure mode: Only admin role can access
if (function_exists('isVulnerable') && isVulnerable('access_control')) {
    // VULNERABLE: No auth check - anyone can access admin features
    // This is the critical security issue: admin endpoints are accessible to any logged-in user
} else {
    // SECURE: Require admin role
    if (!$isLoggedIn || $userRole !== 'admin') {
        header('Location: /pages/login.php?redirect=/pages/admin/orders.php&error=unauthorized');
        exit;
    }
}

$orderManagement = new OrderManagementService($config, $_SESSION['user_id'] ?? null, $userRole);

// Get filter parameters
$filterStatus = $_GET['status'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo = $_GET['date_to'] ?? '';

// Build filter array
$filters = [];
if ($filterStatus) {
    $filters['status'] = $filterStatus;
}
if ($filterDateFrom) {
    $filters['date_from'] = $filterDateFrom;
}
if ($filterDateTo) {
    $filters['date_to'] = $filterDateTo;
}

// Get orders
$orders = $orderManagement->searchOrders($filters) ?: [];

// Get statistics
$stats = $orderManagement->getOrderStatistics();

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
    <title>Admin: Orders - <?php echo htmlspecialchars($config['app_name']); ?></title>
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
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .admin-header {
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

        .admin-badge {
            background-color: #d45113;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .back-link {
            color: #2d5016;
            text-decoration: none;
            font-size: 13px;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: white;
            border-radius: 4px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-icon {
            font-size: 28px;
            margin-bottom: 10px;
            color: #2d5016;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
        }

        .filters-section {
            background-color: white;
            border-radius: 4px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .filters-title {
            font-size: 14px;
            font-weight: 600;
            color: #2d5016;
            margin-bottom: 15px;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 15px;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
            font-weight: 600;
        }

        select, input[type="date"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-family: inherit;
            font-size: 13px;
        }

        select:focus, input[type="date"]:focus {
            outline: none;
            border-color: #2d5016;
            box-shadow: 0 0 4px rgba(45, 80, 22, 0.2);
        }

        .filter-btn {
            background-color: #2d5016;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        .filter-btn:hover {
            background-color: #3d6b1f;
        }

        .reset-btn {
            background-color: #f0f0f0;
            color: #333;
            padding: 8px 16px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            margin-left: 10px;
            transition: background-color 0.3s;
        }

        .reset-btn:hover {
            background-color: #e0e0e0;
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

        .checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .order-number {
            font-weight: 600;
            color: #2d5016;
            font-family: monospace;
            font-size: 12px;
        }

        .customer-id {
            color: #999;
            font-size: 12px;
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

        .action-btns {
            display: flex;
            gap: 8px;
        }

        .view-btn, .edit-btn {
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

        .view-btn:hover, .edit-btn:hover {
            background-color: #3d6b1f;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-icon {
            font-size: 48px;
            margin-bottom: 20px;
            color: #ccc;
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

        @media (max-width: 1024px) {
            .filters-grid {
                grid-template-columns: 1fr 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 768px) {
            .filters-grid {
                grid-template-columns: 1fr;
            }

            .action-btns {
                flex-direction: column;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-header">
            <div>
                <h1><i class="fas fa-box"></i> Order Management</h1>
            </div>
            <div style="display: flex; align-items: center; gap: 20px;">
                <a class="back-link" href="/pages/admin/reviews.php"><i class="fas fa-comments"></i> Reviews</a>
                <a class="back-link" href="/pages/admin/products.php"><i class="fas fa-boxes"></i> Products</a>
                <span class="admin-badge"><i class="fas fa-shield-alt"></i> Admin</span>
                <a href="/pages/admin/users.php" class="back-link">
                    <i class="fas fa-users"></i> Manage Users
                </a>
                <a href="/pages/home.php" class="back-link">
                    <i class="fas fa-home"></i> Back to Shop
                </a>
            </div>
        </div>

        <?php if (function_exists('isVulnerable') && isVulnerable('access_control')): ?>
        <div class="vulnerability-notice">
            <strong>⚠️ VULNERABILITY A01 ACTIVE:</strong> Admin page is <strong>accessible to any logged-in user</strong>. In secure mode, only users with admin role can access this page.
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <?php if (!empty($stats)): ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_orders'] ?? 0; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-value">$<?php echo number_format($stats['revenue'] ?? 0, 0); ?></div>
                <div class="stat-label">Revenue</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo $stats['completed'] ?? 0; ?></div>
                <div class="stat-label">Completed</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?php echo $stats['pending'] ?? 0; ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filters Section -->
        <div class="filters-section">
            <form method="GET" class="filters-grid">
                <div class="filter-group">
                    <label for="status">Status</label>
                    <select name="status" id="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="paid" <?php echo $filterStatus === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="shipped" <?php echo $filterStatus === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="delivered" <?php echo $filterStatus === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $filterStatus === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="date_from">From</label>
                    <input type="date" name="date_from" id="date_from" value="<?php echo htmlspecialchars($filterDateFrom); ?>">
                </div>

                <div class="filter-group">
                    <label for="date_to">To</label>
                    <input type="date" name="date_to" id="date_to" value="<?php echo htmlspecialchars($filterDateTo); ?>">
                </div>

                <div>
                    <button type="submit" class="filter-btn">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="?status=&date_from=&date_to=" class="reset-btn">Reset</a>
                </div>
            </form>
        </div>

        <!-- Orders Table -->
        <?php if (empty($orders)): ?>
        <div class="orders-table">
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-inbox"></i>
                </div>
                <p>No orders found matching your filters.</p>
            </div>
        </div>
        <?php else: ?>
        <div class="orders-table">
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer ID</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Actions</th>
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
                            <span class="customer-id">
                                ID: <?php echo $order['user_id']; ?>
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
                            <div class="action-btns">
                                <a href="/pages/admin/order-detail.php?order_id=<?php echo urlencode($order['id']); ?>" class="view-btn">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </div>
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
