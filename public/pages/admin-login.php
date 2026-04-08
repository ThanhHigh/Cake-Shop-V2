<?php
/**
 * Admin Login Page
 * Phase 2: Admin Authentication
 * 
 * This page allows admin staff to access the admin panel
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

use CakeShop\Services\AuthService;

$authService = new AuthService($config);

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required';
    } else {
        $result = $authService->login($email, $password);
        
        if ($result['success']) {
            // Check if user is admin
            if ($result['user']['role'] === 'admin') {
                header('Location: /pages/admin/orders.php');
                exit;
            } else {
                $error = 'This account does not have admin privileges';
                // Clear the session
                $authService->logout();
            }
        } else {
            $error = $result['message'];
        }
    }
}

$isLoggedIn = $authService->isAuthenticated();

if ($isLoggedIn && $authService->hasRole('admin')) {
    header('Location: /pages/admin/orders.php');
    exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo htmlspecialchars($config['app_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #333 100%);
            color: #333;
            font-family: "UTMAvo", "HelveticaNeue", "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .admin-login-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
            padding: 40px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header i {
            font-size: 48px;
            color: #d9534f;
            display: block;
            margin-bottom: 15px;
        }

        .login-header h1 {
            font-size: 28px;
            color: #2d5016;
            font-weight: 300;
            margin-bottom: 5px;
        }

        .login-header p {
            font-size: 13px;
            color: #999;
        }

        .admin-badge {
            display: inline-block;
            background-color: #d9534f;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            margin-top: 10px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 13px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
            font-family: inherit;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #d9534f;
            box-shadow: 0 0 0 3px rgba(217, 83, 79, 0.1);
        }

        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .remember-me input {
            width: auto;
            margin-right: 8px;
        }

        .remember-me label {
            margin-bottom: 0;
            font-weight: normal;
            font-size: 12px;
            color: #666;
        }

        .login-btn {
            width: 100%;
            background-color: #d9534f;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        .login-btn:hover {
            background-color: #c9302c;
        }

        .login-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .login-btn i {
            margin-right: 8px;
        }

        .login-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #eee;
        }

        .login-footer p {
            font-size: 13px;
            color: #666;
            margin-bottom: 10px;
        }

        .login-footer a {
            color: #2d5016;
            text-decoration: none;
            font-weight: 600;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        .security-notice {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 12px;
            color: #856404;
        }

        .security-notice i {
            margin-right: 6px;
        }

        @media (max-width: 480px) {
            .admin-login-container {
                padding: 25px;
            }

            .login-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="login-header">
            <i class="fas fa-lock"></i>
            <h1><?php echo htmlspecialchars($config['app_name']); ?></h1>
            <p>Administrator Login</p>
            <span class="admin-badge">ADMIN PANEL</span>
        </div>

        <div class="security-notice">
            <i class="fas fa-shield-alt"></i>
            <strong>Restricted Access:</strong> Admin credentials required to access this panel.
        </div>

        <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-times-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Admin Email Address</label>
                <input type="email" id="email" name="email" placeholder="admin@cake-shop.local" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember" value="1">
                <label for="remember">Remember me</label>
            </div>

            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt"></i> Admin Sign In
            </button>
        </form>

        <div class="login-footer">
            <p>Not an admin?</p>
            <a href="/pages/login.php">
                <i class="fas fa-arrow-left"></i> Customer Login
            </a>
            <p style="margin-top: 15px;">
                <a href="/">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </p>
        </div>

        <div style="margin-top: 25px; padding: 15px; background-color: #f9f9f9; border-radius: 4px; font-size: 12px; color: #666;">
            <p><strong>Demo Admin Credentials (if available):</strong></p>
            <p>Email: admin@cake-shop.local<br>Password: admin123456</p>
        </div>
    </div>
</body>
</html>
