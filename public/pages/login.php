<?php
/**
 * User Login Page
 * Phase 2: E-commerce Features - Authentication
 * 
 * Demonstrates both vulnerable and secure authentication modes
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

function safeRedirectPath($path, $fallback = '/catalog')
{
    if (!is_string($path) || $path === '') {
        return $fallback;
    }

    if ($path[0] !== '/') {
        return $fallback;
    }

    if (strpos($path, '//') === 0) {
        return $fallback;
    }

    return $path;
}

$requestedRedirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '/catalog';
$redirectUrl = safeRedirectPath($requestedRedirect, '/catalog');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required';
    } else {
        $result = $authService->login($email, $password);
        
        if ($result['success']) {
            header('Location: ' . $redirectUrl);
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

$isLoggedIn = $authService->isAuthenticated();

if ($isLoggedIn) {
    header('Location: ' . $redirectUrl);
    exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($config['app_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #2d5016 0%, #3d6b1f 100%);
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

        .login-container {
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
            color: #2d5016;
            display: block;
            margin-bottom: 15px;
        }

        .login-header h1 {
            font-size: 28px;
            color: #2d5016;
            font-weight: 300;
            margin-bottom: 10px;
        }

        .login-header p {
            font-size: 13px;
            color: #999;
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

        <?php if ($config['app_mode'] === 'vulnerable'): ?>
        .vulnerability-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 12px;
        }

        .vulnerability-warning i {
            margin-right: 8px;
        }
        <?php endif; ?>

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
            border-color: #2d5016;
            box-shadow: 0 0 0 3px rgba(45, 80, 22, 0.1);
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
            background-color: #2d5016;
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
            background-color: #3d6b1f;
        }

        .login-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .forgot-password {
            text-align: center;
            margin-top: 15px;
        }

        .forgot-password a {
            color: #2d5016;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
        }

        .forgot-password a:hover {
            text-decoration: underline;
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

        @media (max-width: 480px) {
            .login-container {
                padding: 25px;
            }

            .login-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-cake-candles"></i>
            <h1><?php echo htmlspecialchars($config['app_name']); ?></h1>
            <p>Customer Login</p>
        </div>

        <?php if ($config['app_mode'] === 'vulnerable'): ?>
        <div class="vulnerability-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Training Mode:</strong> This login uses intentionally vulnerable authentication.
            Compare with secure mode to learn about password security.
        </div>
        <?php endif; ?>

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
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectUrl); ?>">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="you@example.com" 
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
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>

            <div class="forgot-password">
                <a href="/pages/forgot-password.php">Forgot password?</a>
            </div>
        </form>

        <div class="login-footer">
            <p>Don't have an account?</p>
            <a href="/pages/register.php?redirect=<?php echo urlencode($redirectUrl); ?>">
                <i class="fas fa-user-plus"></i> Create Account
            </a>
        </div>

        <div style="margin-top: 25px; padding: 15px; background-color: #f9f9f9; border-radius: 4px; font-size: 12px; color: #666;">
            <p><strong>Demo Credentials (if available):</strong></p>
            <p>Email: demo@cake-shop.local<br>Password: demo123456</p>
        </div>
    </div>
</body>
</html>
