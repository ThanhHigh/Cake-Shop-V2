<?php
/**
 * Customer Account Manager Page
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

$authService = new AuthService($config);

if (!$authService->isAuthenticated()) {
    header('Location: /pages/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/pages/account.php'));
    exit;
}

$user = $authService->getCurrentUser();
$profileMessage = '';
$profileError = '';
$passwordMessage = '';
$passwordError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $profileData = [
            'username' => trim($_POST['username'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'full_name' => trim($_POST['full_name'] ?? ''),
        ];

        $result = $authService->updateProfile((int)$_SESSION['user_id'], $profileData);
        if ($result['success']) {
            $profileMessage = $result['message'];
            $user = $authService->getCurrentUser();
        } else {
            $profileError = $result['message'];
        }
    }

    if ($action === 'update_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';

        $result = $authService->updatePassword((int)$_SESSION['user_id'], $currentPassword, $newPassword);
        if ($result['success']) {
            $passwordMessage = $result['message'];
        } else {
            $passwordError = $result['message'];
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - <?php echo htmlspecialchars($config['app_name']); ?></title>
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
            max-width: 980px;
            margin: 0 auto;
            padding: 22px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
        }

        h1 {
            font-size: 28px;
            color: #2d5016;
            font-weight: 300;
        }

        .back-link {
            color: #2d5016;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .card {
            background-color: #fff;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 20px;
        }

        .card h2 {
            font-size: 18px;
            color: #2d5016;
            margin-bottom: 16px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 14px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 13px;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-family: inherit;
            font-size: 13px;
        }

        .form-group input:focus {
            outline: none;
            border-color: #2d5016;
            box-shadow: 0 0 0 3px rgba(45, 80, 22, 0.12);
        }

        .btn {
            border: none;
            border-radius: 3px;
            padding: 10px 14px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
        }

        .btn-primary {
            background-color: #2d5016;
            color: #fff;
        }

        .btn-primary:hover {
            background-color: #3d6b1f;
        }

        .meta {
            margin-top: 10px;
            font-size: 12px;
            color: #666;
        }

        .alert {
            padding: 10px 12px;
            border-radius: 4px;
            margin-bottom: 12px;
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

        .actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }

        .logout-link {
            background-color: #f0f0f0;
            color: #333;
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 3px;
            font-size: 13px;
            font-weight: 600;
        }

        .logout-link:hover {
            background-color: #e0e0e0;
        }

        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-user-circle"></i> Account Manager</h1>
        <a href="/catalog" class="back-link"><i class="fas fa-arrow-left"></i> Back to Catalog</a>
    </div>

    <div class="grid">
        <div class="card">
            <h2>Profile Information</h2>

            <?php if ($profileError): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($profileError); ?></div>
            <?php endif; ?>

            <?php if ($profileMessage): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($profileMessage); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="action" value="update_profile">

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="full_name">Display Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                </div>

                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Profile</button>
            </form>

            <div class="meta">
                Role: <strong><?php echo htmlspecialchars($user['role'] ?? 'customer'); ?></strong>
            </div>
        </div>

        <div class="card">
            <h2>Change Password</h2>

            <?php if ($passwordError): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($passwordError); ?></div>
            <?php endif; ?>

            <?php if ($passwordMessage): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($passwordMessage); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="action" value="update_password">

                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>

                <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> Update Password</button>
            </form>

            <div class="actions">
                <a href="/pages/order-history.php" class="logout-link"><i class="fas fa-receipt"></i> My Orders</a>
                <a href="/pages/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
