<?php
/**
 * Checkout Page
 * Phase 3: Order System - Customer Checkout
 * Handles order creation from cart with mock payment and invoice upload
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

use CakeShop\Services\CartService;
use CakeShop\Services\OrderService;

$isLoggedIn = isset($_SESSION['user_id']);

// Redirect if not logged in
if (!$isLoggedIn) {
    header('Location: /pages/login.php?redirect=/pages/checkout.php');
    exit;
}

$cartService = new CartService($config, $_SESSION['user_id']);
$orderService = new OrderService($config, $_SESSION['user_id']);

$cartItems = $cartService->getCartItems();
$cartTotal = $cartService->getCartTotal();

// If cart is empty, redirect back
if (empty($cartItems)) {
    header('Location: /pages/cart.php?error=empty');
    exit;
}

$paymentSuccess = false;
$orderCreated = false;
$order = null;
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shippingAddress = trim($_POST['shipping_address'] ?? '');
    $customerNotes = trim($_POST['customer_notes'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'credit_card';

    // Validate inputs
    if (empty($shippingAddress)) {
        $error = 'Shipping address is required';
    } else {
        // Create order
        $result = $orderService->createOrderFromCart($shippingAddress, $customerNotes);
        
        if ($result['success']) {
            // Mock payment processing
            // In real scenario, would integrate Stripe/PayPal here
            $paymentSuccess = true;
            $orderCreated = true;
            $order = $result;

            // Update order status to "paid"
            $orderService->updateOrderStatus($result['order_id'], 'paid');

            // Redirect to confirmation page
            header('Location: /pages/order-confirmation.php?order_id=' . $result['order_id']);
            exit;
        } else {
            $error = $result['message'] ?? 'Failed to create order';
        }
    }
}

// Handle invoice upload (if present)
$invoiceFile = null;
$invoiceError = null;

if (!empty($_FILES['invoice'])) {
    // VULNERABILITY A04: File upload handling (vulnerable vs secure)
    $file = $_FILES['invoice'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        // VULNERABLE: No file type validation
        if (isVulnerable('insecure_upload')) {
            // Vulnerable path: Accept any file type, save with original name
            $filename = $file['name'];
            $uploadDir = dirname(__DIR__) . '/uploads/';
            
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
                // Rename file with hash to avoid overwrites and path traversal
                $hash = hash('sha256', uniqid() . time());
                $newFilename = $hash . '.' . $fileExt;
                $uploadDir = dirname(__DIR__) . '/uploads/';
                
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
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?php echo htmlspecialchars($config['app_name']); ?></title>
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

        .checkout-wrapper {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
            margin-bottom: 40px;
        }

        .checkout-form {
            background-color: white;
            border-radius: 4px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section h2 {
            font-size: 18px;
            color: #2d5016;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2d5016;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        input[type="text"],
        input[type="email"],
        textarea,
        select,
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-family: inherit;
            font-size: 14px;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        textarea:focus,
        select:focus,
        input[type="file"]:focus {
            outline: none;
            border-color: #2d5016;
            box-shadow: 0 0 4px rgba(45, 80, 22, 0.2);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .radio-group {
            display: flex;
            gap: 30px;
            margin-top: 10px;
        }

        .radio-group label {
            display: flex;
            align-items: center;
            margin-bottom: 0;
            font-weight: 400;
        }

        input[type="radio"] {
            margin-right: 8px;
            width: auto;
        }

        .error-message {
            background-color: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 3px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
        }

        .success-message {
            background-color: #efe;
            color: #3c3;
            padding: 12px;
            border-radius: 3px;
            margin-bottom: 20px;
            border-left: 4px solid #3c3;
        }

        .order-summary {
            background-color: white;
            border-radius: 4px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .order-summary h3 {
            font-size: 16px;
            color: #2d5016;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2d5016;
        }

        .order-item {
            display: grid;
            grid-template-columns: 1fr 50px;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 13px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-name {
            font-weight: 600;
        }

        .summary-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 10px;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .summary-row.total {
            font-size: 16px;
            font-weight: bold;
            color: #d45113;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            margin-top: 10px;
        }

        .submit-btn {
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

        .submit-btn:hover {
            background-color: #3d6b1f;
        }

        .back-link {
            color: #2d5016;
            text-decoration: none;
            font-size: 13px;
            margin-top: 15px;
            display: inline-block;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .file-input-label {
            display: inline-block;
            padding: 10px 15px;
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 3px;
            cursor: pointer;
            font-size: 13px;
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
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-shopping-bag"></i> Checkout</h1>

        <?php if ($error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="checkout-wrapper">
            <div class="checkout-form">
                <!-- Billing Address Section -->
                <div class="form-section">
                    <h2>Shipping Address</h2>
                    <div class="form-group">
                        <label for="shipping_address">Full Address *</label>
                        <textarea id="shipping_address" name="shipping_address" required placeholder="Street address, apartment/suite, city, state, ZIP code, country"></textarea>
                    </div>
                </div>

                <!-- Payment Method Section -->
                <div class="form-section">
                    <h2>Payment Method</h2>
                    <div class="form-group">
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="payment_method" value="credit_card" checked>
                                Credit Card
                            </label>
                            <label>
                                <input type="radio" name="payment_method" value="debit_card">
                                Debit Card
                            </label>
                            <label>
                                <input type="radio" name="payment_method" value="bank_transfer">
                                Bank Transfer
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="card_number">Card Number (Mock)</label>
                        <input type="text" id="card_number" placeholder="1234 5678 9012 3456" maxlength="20">
                    </div>

                    <div class="form-group">
                        <label for="card_name">Cardholder Name (Mock)</label>
                        <input type="text" id="card_name" placeholder="John Doe">
                    </div>

                    <div class="form-group">
                        <label for="card_cvv">CVV (Mock)</label>
                        <input type="text" id="card_cvv" placeholder="123" maxlength="4">
                    </div>
                </div>

                <!-- Invoice Upload Section (A04: Insecure Design) -->
                <div class="form-section">
                    <h2>Invoice Upload (Optional)</h2>
                    <div class="form-group">
                        <label for="invoice">
                            Upload Invoice Receipt
                            <span style="color: #666; font-weight: 400;">(<?php echo isVulnerable('insecure_upload') ? 'Any file (Vulnerable)' : 'PDF, JPG, PNG only (Secure)'; ?>)</span>
                        </label>
                        <input type="file" id="invoice" name="invoice">
                        <label for="invoice" class="file-input-label">
                            <i class="fas fa-upload"></i> Choose File
                        </label>
                        <div class="file-name" id="file-name"></div>
                        <?php if ($invoiceError): ?>
                        <div class="error-message" style="margin-top: 10px;">
                            <?php echo htmlspecialchars($invoiceError); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Order Notes -->
                <div class="form-section">
                    <h2>Order Notes (Optional)</h2>
                    <div class="form-group">
                        <label for="customer_notes">Special Requests or Notes</label>
                        <textarea id="customer_notes" name="customer_notes" placeholder="Any special instructions for this order..."></textarea>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-check-circle"></i> Complete Purchase
                </button>

                <a href="/pages/cart.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Return to Cart
                </a>
            </div>

            <!-- Order Summary Sidebar -->
            <div class="order-summary">
                <h3>Order Summary</h3>

                <?php foreach ($cartItems as $item): ?>
                <div class="order-item">
                    <span class="item-name"><?php echo htmlspecialchars($item['name']); ?> <span style="color: #999;">×<?php echo $item['quantity']; ?></span></span>
                    <span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                </div>
                <?php endforeach; ?>

                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>$<?php echo number_format($cartTotal, 2); ?></span>
                </div>

                <div class="summary-row">
                    <span>Tax (10%):</span>
                    <span>$<?php echo number_format($cartTotal * 0.1, 2); ?></span>
                </div>

                <div class="summary-row">
                    <span>Shipping:</span>
                    <span>FREE</span>
                </div>

                <div class="summary-row total">
                    <span>Total:</span>
                    <span>$<?php echo number_format($cartTotal * 1.1, 2); ?></span>
                </div>
            </div>
        </form>
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
