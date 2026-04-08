<?php
/**
 * Admin Order Detail Page
 * Phase 3: Order System - Admin Order Management Detail
 * VULNERABILITY A01: Broken Access Control
 * VULNERABILITY A04: Insecure Design - File Download
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
if (function_exists('isVulnerable') && isVulnerable('access_control')) {
    // VULNERABLE: No auth check
} else {
    // SECURE: Require admin role
    if (!$isLoggedIn || $userRole !== 'admin') {
        header('Location: /pages/login.php?redirect=/pages/admin/order-detail.php&error=unauthorized');
        exit;
    }
}

$orderId = $_GET['order_id'] ?? null;

if (!$orderId) {
    header('Location: /pages/admin/orders.php?error=invalid_order');
    exit;
}

$orderManagement = new OrderManagementService($config, $_SESSION['user_id'] ?? null, $userRole);
$order = $orderManagement->getOrderWithAccessCheck($orderId);

if (!$order) {
    http_response_code(404);
    die('Order not found');
}

// Handle status update
$statusMessage = '';
$statusError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = $_POST['status'] ?? '';
    
    if ($newStatus) {
        $result = $orderManagement->updateOrderStatus($orderId, $newStatus);
        
        if ($result['success']) {
            $statusMessage = $result['message'];
            $order['status'] = $newStatus;
        } else {
            $statusError = $result['message'];
        }
    }
}

// Handle invoice upload
$invoiceFile = null;
$invoiceError = null;

if (!empty($_FILES['invoice'])) {
    $file = $_FILES['invoice'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        // VULNERABILITY A04: File upload handling (vulnerable vs secure)
        if (isVulnerable('insecure_upload')) {
            // Vulnerable path: Accept any file type, save with original name
            $filename = $file['name'];
            $uploadDir = dirname(dirname(__DIR__)) . '/uploads/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $uploadPath = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $invoiceFile = $filename;
            } else {
                $invoiceError = 'Failed to upload file';
            }
        } else {
            // SECURE: Validate file type and rename
            $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            // Check MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
            
            if (!in_array($fileExt, $allowedExtensions) || !in_array($mimeType, $allowedMimes)) {
                $invoiceError = 'Invalid file type. Only PDF, JPG, and PNG files are allowed.';
            } else {
                // Rename file with hash
                $hash = hash('sha256', uniqid() . time());
                $newFilename = $hash . '.' . $fileExt;
                $uploadDir = dirname(dirname(__DIR__)) . '/uploads/';
                
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $uploadPath = $uploadDir . $newFilename;
                
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $invoiceFile = $newFilename;
                } else {
                    $invoiceError = 'Failed to upload file';
                }
            }
        }
    }
}

// Handle file download (A04: Insecure Design)
if (!empty($_GET['download'])) {
    $fileToDownload = $_GET['download'];
    
    if (isVulnerable('insecure_upload')) {
        // VULNERABLE: Serve file without type checking
        // Risk: Could expose system files if path traversal is used
        $uploadDir = dirname(dirname(__DIR__)) . '/uploads/';
        $filePath = $uploadDir . $fileToDownload;
        
        // Weak check - can be bypassed with path traversal
        if (file_exists($filePath)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($filePath));
            readfile($filePath);
            exit;
        }
    } else {
        // SECURE: Validate file exists and is in upload directory
        $uploadDir = dirname(dirname(__DIR__)) . '/uploads/';
        $filePath = realpath($uploadDir . $fileToDownload);
        
        // Verify the file is actually in the upload directory (prevent path traversal)
        if ($filePath && strpos($filePath, realpath($uploadDir)) === 0 && file_exists($filePath)) {
            // Validate MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            
            $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
            
            if (in_array($mimeType, $allowedMimes)) {
                header('Content-Type: ' . $mimeType);
                header('Content-Disposition: attachment; filename=' . basename($filePath));
                readfile($filePath);
                exit;
            }
        }
    }
    
    http_response_code(404);
    die('File not found');
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
    <title>Admin: Order Details - <?php echo htmlspecialchars($config['app_name']); ?></title>
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

        .content-grid {
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
            padding: 12px;
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
            font-size: 13px;
        }

        .item-price {
            text-align: right;
            font-weight: 600;
            font-size: 13px;
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
            margin-bottom: 8px;
            font-size: 13px;
        }

        .total-row.grand-total {
            font-size: 16px;
            font-weight: bold;
            color: #d45113;
            margin-top: 10px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
            font-size: 13px;
        }

        select, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-family: inherit;
            font-size: 13px;
        }

        select:focus, textarea:focus {
            outline: none;
            border-color: #2d5016;
            box-shadow: 0 0 4px rgba(45, 80, 22, 0.2);
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn {
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

        .btn:hover {
            background-color: #3d6b1f;
        }

        .info-text {
            font-size: 13px;
            color: #666;
            line-height: 1.8;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .info-box {
            background-color: #f8f2e8;
            padding: 12px;
            border-radius: 3px;
            margin-top: 10px;
        }

        .message {
            padding: 12px;
            border-radius: 3px;
            margin-bottom: 15px;
            font-size: 13px;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .file-upload-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .file-input-label {
            display: inline-block;
            padding: 8px 12px;
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            transition: background-color 0.3s;
        }

        .file-input-label:hover {
            background-color: #e0e0e0;
        }

        input[type="file"] {
            display: none;
        }

        .file-name {
            color: #666;
            font-size: 12px;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .order-header {
                grid-template-columns: 1fr;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-receipt"></i> Order Details (Admin)</h1>
            <a href="/pages/admin/orders.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
        </div>

        <!-- Order Header -->
        <div class="order-header">
            <div class="header-item">
                <div class="header-label">Order Number</div>
                <div class="header-value order-number"><?php echo htmlspecialchars($order['order_number']); ?></div>
            </div>

            <div class="header-item">
                <div class="header-label">Customer ID</div>
                <div class="header-value"><?php echo $order['user_id']; ?></div>
            </div>

            <div class="header-item">
                <div class="header-label">Order Date</div>
                <div class="header-value"><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></div>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($statusMessage): ?>
        <div class="message success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($statusMessage); ?>
        </div>
        <?php endif; ?>

        <?php if ($statusError): ?>
        <div class="message error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($statusError); ?>
        </div>
        <?php endif; ?>

        <!-- Main Content -->
        <div class="content-grid">
            <!-- Left Column: Order Details -->
            <div>
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
                            <span>Total:</span>
                            <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Shipping Address -->
                <?php if ($order['shipping_address']): ?>
                <div class="order-section" style="margin-top: 20px;">
                    <div class="section-title">
                        <i class="fas fa-map-marker-alt"></i> Shipping Address
                    </div>
                    <div class="info-text"><?php echo htmlspecialchars($order['shipping_address']); ?></div>
                </div>
                <?php endif; ?>

                <!-- Customer Notes -->
                <?php if ($order['customer_notes']): ?>
                <div class="order-section" style="margin-top: 20px;">
                    <div class="section-title">
                        <i class="fas fa-sticky-note"></i> Customer Notes
                    </div>
                    <div class="info-text"><?php echo htmlspecialchars($order['customer_notes']); ?></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Column: Actions -->
            <div>
                <!-- Status Update Form -->
                <div class="order-section">
                    <div class="section-title">
                        <i class="fas fa-cogs"></i> Order Management
                    </div>

                    <form method="POST">
                        <div class="form-group">
                            <label for="status">Update Status</label>
                            <select name="status" id="status">
                                <option value="">-- Select Status --</option>
                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="paid" <?php echo $order['status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>

                        <button type="submit" class="btn">
                            <i class="fas fa-save"></i> Update Status
                        </button>
                    </form>

                    <div class="info-box" style="margin-top: 15px;">
                        <strong>Current Status:</strong><br>
                        <span style="background-color: <?php echo $statusColors[$order['status']]; ?>; color: white; padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: 600;">
                            <?php echo $statusTexts[$order['status']]; ?>
                        </span>
                    </div>
                </div>

                <!-- Invoice Upload Section (A04: Insecure Design) -->
                <div class="order-section" style="margin-top: 20px;">
                    <div class="section-title">
                        <i class="fas fa-file-upload"></i> Invoice Management
                    </div>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group file-upload-section">
                            <label>Upload Invoice
                                <span style="color: #666; font-weight: 400; font-size: 12px;">(<?php echo isVulnerable('insecure_upload') ? 'Any file (Vulnerable)' : 'PDF, JPG, PNG only (Secure)'; ?>)</span>
                            </label>
                            <input type="file" id="invoice" name="invoice">
                            <label for="invoice" class="file-input-label">
                                <i class="fas fa-upload"></i> Choose File
                            </label>
                            <div class="file-name" id="file-name"></div>

                            <?php if ($invoiceError): ?>
                            <div class="message error" style="margin-top: 10px;">
                                <?php echo htmlspecialchars($invoiceError); ?>
                            </div>
                            <?php endif; ?>

                            <button type="submit" class="btn" style="margin-top: 10px;">
                                <i class="fas fa-upload"></i> Upload Invoice
                            </button>
                        </div>
                    </form>

                    <?php if ($invoiceFile): ?>
                    <div style="margin-top: 15px; padding: 12px; background-color: #e8f5e9; border-radius: 3px; border-left: 3px solid #28a745;">
                        <strong>Invoice File:</strong><br>
                        <small style="color: #666;"><?php echo htmlspecialchars($invoiceFile); ?></small><br>
                        <a href="?order_id=<?php echo urlencode($orderId); ?>&download=<?php echo urlencode($invoiceFile); ?>" style="color: #2d5016; text-decoration: none; font-weight: 600; font-size: 12px;">
                            <i class="fas fa-download"></i> Download Invoice
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // File input label interaction
        document.getElementById('invoice').addEventListener('change', function() {
            const fileName = this.files[0]?.name || '';
            document.getElementById('file-name').textContent = fileName ? 'Selected: ' + fileName : '';
        });
    </script>
</body>
</html>
