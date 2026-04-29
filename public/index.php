<?php
/**
 * Cake Shop Training Lab - Main Entry Point
 * 
 * This is the primary entry point for all web requests.
 * Routes are dispatched here based on URI and HTTP method.
 */

// Security: Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load Composer autoloader for PSR-4 namespace support
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Load configuration
$config = require_once dirname(__DIR__) . '/config/config.php';

// Display startup banner in CLI-only mode (not in web/HTTP mode)
if (php_sapi_name() === 'cli') {
    echo "\n";
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║  CAKE SHOP OWASP TRAINING LAB                                ║\n";
    echo "║  Mode: " . strtoupper($config['app_mode']) . " | Debug: " . ($config['app_debug'] ? 'ON' : 'OFF') . "                            ║\n";
    echo "║  ⚠️  TRAINING ENVIRONMENT ONLY - LOCAL USE ONLY               ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
}

// Simple routing dispatcher (will be expanded in Phase 2)
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove public prefix if present
$requestUri = str_replace('/cake_shop_v2/public', '', $requestUri);
if ($requestUri === '') {
    $requestUri = '/';
}

// Start session for cart and authentication
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Route handling
if ($requestUri === '/' || $requestUri === '/intro') {
    // Landing page - intro/welcome
    require_once __DIR__ . '/pages/intro.php';
} elseif ($requestUri === '/catalog' || $requestUri === '/shop') {
    // Product catalog - e-commerce home
    require_once __DIR__ . '/pages/home.php';
} elseif ($requestUri === '/api/health') {
    // Health check endpoint
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'ok',
        'timestamp' => date('c'),
        'mode' => $config['app_mode'],
        'app_name' => $config['app_name'],
    ]);
} elseif ($requestUri === '/api/reviews/create' && $requestMethod === 'POST') {
    // Review submission endpoint
    header('Content-Type: application/json');
    
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized: Please log in to submit a review'
        ]);
        exit;
    }
    
    try {
        // Extract and validate form data
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
        $proofUrl = isset($_POST['proof_url']) ? trim($_POST['proof_url']) : '';
        $userId = $_SESSION['user_id'];
        
        // Validation
        $errors = [];
        if ($productId <= 0) $errors[] = 'Invalid product.';
        if ($rating < 1 || $rating > 5) $errors[] = 'Rating must be between 1 and 5.';
        if (empty($title)) $errors[] = 'Title is required.';
        if (empty($comment)) $errors[] = 'Review text is required.';
        
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => implode(' ', $errors)
            ]);
            exit;
        }

        require_once dirname(__DIR__) . '/src/Services/ReviewService.php';
        $reviewService = new \CakeShop\Services\ReviewService($config);

        // Review anti-spam: max 3 reviews / 5 minutes per user.
        // In vulnerable mode with weak_auth enabled, this control is intentionally disabled.
        if (!(isVulnerable('weak_auth'))) {
            $rateLimit = $reviewService->isReviewRateLimited($userId, 3, 300);
            if ($rateLimit['limited']) {
                http_response_code(429);
                echo json_encode([
                    'success' => false,
                    'error' => 'Too many reviews submitted. Please wait a few minutes and try again.'
                ]);
                exit;
            }
        }
        
        // Vulnerable mode: Store comment without sanitization (XSS teaching point)
        if (isVulnerable('review_injection')) {
            // VULNERABLE: No sanitization - allows HTML/JavaScript injection
            // This demonstrates stored XSS vulnerability
            $reviewComment = $comment;
        } else {
            // SECURE: Sanitize output to prevent XSS
            // Using htmlspecialchars to escape HTML entities
            $reviewComment = htmlspecialchars($comment, ENT_QUOTES, 'UTF-8');
        }
        
        // Note: We pass the processed comment to the service
        // In vulnerable mode: unescaped HTML
        // In secure mode: escaped to prevent injection
        $result = $reviewService->addReview($productId, $userId, $rating, $title, $reviewComment, $proofUrl);
        
        if ($result['success']) {
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Thank you! Your review has been submitted and is pending approval.',
                'proof' => [
                    'url' => $result['proof_url'] ?? '',
                    'status' => $result['proof_fetch_status'] ?? 'not_provided',
                    'message' => $result['proof_fetch_message'] ?? null,
                    'preview' => $result['proof_preview'] ?? null,
                ],
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $result['message'] ?? 'Failed to submit review.'
            ]);
        }
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Server error: ' . ($config['app_debug'] ? $e->getMessage() : 'Unable to process request')
        ]);
    }
    exit;
} elseif (($requestUri === '/api/admin/reviews/approve' || $requestUri === '/api/admin/reviews/reject') && $requestMethod === 'POST') {
    // Admin review moderation endpoints
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $userRole = $_SESSION['user_role'] ?? 'customer';
    if (!isVulnerable('access_control') && $userRole !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Forbidden']);
        exit;
    }

    $reviewId = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;
    if ($reviewId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid review id']);
        exit;
    }

    try {
        require_once dirname(__DIR__) . '/src/Services/ReviewService.php';
        $reviewService = new \CakeShop\Services\ReviewService($config);

        if ($requestUri === '/api/admin/reviews/approve') {
            $result = $reviewService->approveReview($reviewId);
        } else {
            $result = $reviewService->rejectReview($reviewId);
        }

        if ($result['success']) {
            echo json_encode(['success' => true, 'message' => $result['message']]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $result['message']]);
        }
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Server error: ' . ($config['app_debug'] ? $e->getMessage() : 'Unable to process request')
        ]);
    }
    exit;
} elseif (($requestUri === '/api/admin/products/create' || $requestUri === '/api/admin/products/update' || $requestUri === '/api/admin/products/delete') && $requestMethod === 'POST') {
    // Admin product management endpoints
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $userRole = $_SESSION['user_role'] ?? 'customer';
    if (!isVulnerable('access_control') && $userRole !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Forbidden']);
        exit;
    }

    try {
        require_once dirname(__DIR__) . '/src/Services/ProductService.php';
        $productService = new \CakeShop\Services\ProductService($config);

        if ($requestUri === '/api/admin/products/create') {
            $result = $productService->adminCreateProduct($_POST);
        } elseif ($requestUri === '/api/admin/products/update') {
            $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
            $result = $productService->adminUpdateProduct($productId, $_POST);
        } else {
            $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
            $result = $productService->adminSoftDeleteProduct($productId);
        }

        if (!empty($result['success'])) {
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $result['message'] ?? 'Product operation failed.'
            ]);
        }
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Server error: ' . ($config['app_debug'] ? $e->getMessage() : 'Unable to process request')
        ]);
    }
    exit;
} elseif (strpos($requestUri, '/pages/') === 0) {
    // Page routes - map to actual pages
    $pageName = str_replace('/pages/', '', $requestUri);
    $pageName = str_replace('.php', '', $pageName);
    $pagePath = __DIR__ . '/pages/' . $pageName . '.php';
    
    if (file_exists($pagePath)) {
        require_once $pagePath;
    } else {
        http_response_code(404);
        echo "404 - Page not found: " . htmlspecialchars($pageName);
    }
} elseif (strpos($requestUri, '/admin/') === 0) {
    // Admin routes
    $adminPage = str_replace('/admin/', '', $requestUri);
    $adminPage = str_replace('.php', '', $adminPage);
    $adminPath = __DIR__ . '/admin/' . $adminPage . '.php';
    
    if (file_exists($adminPath)) {
        require_once $adminPath;
    } else {
        // Try pages directory for admin login
        $pagePath = __DIR__ . '/pages/admin-' . $adminPage . '.php';
        if (file_exists($pagePath)) {
            require_once $pagePath;
        } else {
            http_response_code(404);
            echo "404 - Admin page not found: " . htmlspecialchars($adminPage);
        }
    }
} else {
    // Default 404
    http_response_code(404);
    echo "404 - Route not found: " . htmlspecialchars($requestUri);
}
