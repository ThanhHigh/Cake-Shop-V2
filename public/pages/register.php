<?php
/**
 * Customer Registration Page
 */

if (!function_exists('__autoload')) {
    require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';
}

if (!isset($config)) {
    $config = require_once dirname(dirname(__DIR__)) . '/config/config.php';
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

use CakeShop\Services\AuthService;

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

$authService = new AuthService($config);
$error = '';
$success = '';
$requestedRedirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '/catalog';
$redirectUrl = safeRedirectPath($requestedRedirect, '/catalog');

if ($authService->isAuthenticated()) {
    header('Location: ' . $redirectUrl);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = $authService->register($username, $email, $password);

    if ($result['success']) {
        $loginResult = $authService->login($email, $password);
        if ($loginResult['success']) {
            header('Location: ' . $redirectUrl);
            exit;
        }

        $success = 'Registration successful. Please log in.';
    } else {
        $error = $result['message'];
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - <?php echo htmlspecialchars($config['app_name']); ?></title>
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

        .register-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 420px;
            padding: 36px;
        }

        .register-header {
            text-align: center;
            margin-bottom: 26px;
        }

        .register-header i {
            font-size: 44px;
            color: #2d5016;
            display: block;
            margin-bottom: 12px;
        }

        .register-header h1 {
            font-size: 28px;
            color: #2d5016;
            font-weight: 300;
            margin-bottom: 8px;
        }

        .register-header p {
            color: #777;
            font-size: 13px;
        }

        .alert {
            padding: 12px 14px;
            border-radius: 4px;
            margin-bottom: 16px;
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
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
            font-size: 13px;
        }

        .form-group input {
            width: 100%;
            padding: 11px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
            font-family: inherit;
        }

        .form-group input:focus {
            outline: none;
            border-color: #2d5016;
            box-shadow: 0 0 0 3px rgba(45, 80, 22, 0.12);
        }

        .submit-btn {
            width: 100%;
            background-color: #2d5016;
            color: #fff;
            padding: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .submit-btn:hover {
            background-color: #3d6b1f;
        }

        .register-footer {
            margin-top: 22px;
            text-align: center;
            font-size: 13px;
            color: #666;
        }

        .register-footer a {
            color: #2d5016;
            text-decoration: none;
            font-weight: 600;
        }

        .register-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="register-container">
    <div class="register-header">
        <i class="fas fa-user-plus"></i>
        <h1>Create Account</h1>
        <p>Start shopping with your new account</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
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
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="password">Create Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="submit-btn">
            <i class="fas fa-check"></i> Register
        </button>
    </form>

    <div class="register-footer">
        Already have an account?
        <a href="/pages/login.php?redirect=<?php echo urlencode($redirectUrl); ?>">Log in</a>
    </div>
</div>
</body>
</html>
