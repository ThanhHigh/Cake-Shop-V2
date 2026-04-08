<?php
/**
 * Introduction / Landing Page
 * Phase 1: Core Setup - Links to Phase 2 E-Commerce Features
 * 
 * This page serves as the welcome/info page and links to the main application
 */

if (!isset($config)) {
    $config = require_once dirname(dirname(__DIR__)) . '/config/config.php';
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($config['app_name']); ?> - Training Lab</title>
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

        .intro-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: linear-gradient(135deg, #2d5016 0%, #3d6b1f 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
            margin-bottom: 40px;
            border-radius: 4px;
        }

        header h1 {
            font-size: 36px;
            margin-bottom: 10px;
            font-weight: 300;
        }

        header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .training-banner {
            background-color: #d9534f;
            color: white;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 30px;
            text-align: center;
            font-weight: bold;
        }

        .training-banner i {
            margin-right: 8px;
        }

        .mode-badge {
            display: inline-block;
            background-color: rgba(0,0,0,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            margin-left: 10px;
            margin-top: 10px;
        }

        .intro-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .info-section {
            background-color: white;
            padding: 25px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .info-section h2 {
            color: #2d5016;
            margin-bottom: 15px;
            font-size: 22px;
            font-weight: 600;
            border-bottom: 2px solid #c9a961;
            padding-bottom: 10px;
        }

        .info-section p {
            color: #666;
            line-height: 1.8;
            margin-bottom: 15px;
        }

        .info-section ul {
            list-style: none;
            margin-bottom: 15px;
        }

        .info-section ul li {
            padding: 8px 0;
            padding-left: 25px;
            position: relative;
            color: #666;
        }

        .info-section ul li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #2d5016;
            font-weight: bold;
        }

        .cta-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
        }

        .btn-primary {
            background-color: #2d5016;
            color: white;
            flex: 1;
            min-width: 200px;
        }

        .btn-primary:hover {
            background-color: #3d6b1f;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(45, 80, 22, 0.3);
        }

        .btn-secondary {
            background-color: #c9a961;
            color: white;
            flex: 1;
            min-width: 200px;
        }

        .btn-secondary:hover {
            background-color: #b8954f;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(201, 169, 97, 0.3);
        }

        .btn-admin {
            background-color: #666;
            color: white;
            flex: 1;
            min-width: 200px;
        }

        .btn-admin:hover {
            background-color: #555;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .btn i {
            margin-right: 8px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .feature-card {
            background-color: white;
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }

        .feature-card i {
            font-size: 40px;
            color: #c9a961;
            margin-bottom: 12px;
        }

        .feature-card h3 {
            color: #2d5016;
            margin-bottom: 10px;
            font-size: 16px;
            font-weight: 600;
        }

        .feature-card p {
            color: #666;
            font-size: 13px;
            line-height: 1.6;
        }

        .app-status {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 40px;
        }

        .app-status h3 {
            color: #856404;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .app-status p {
            color: #856404;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .status-badge {
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-right: 8px;
        }

        footer {
            background-color: #2d5016;
            color: white;
            text-align: center;
            padding: 20px;
            border-radius: 4px;
            font-size: 12px;
        }

        @media (max-width: 768px) {
            .intro-grid {
                grid-template-columns: 1fr;
            }

            header h1 {
                font-size: 28px;
            }

            .cta-buttons {
                flex-direction: column;
            }

            .btn {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="intro-container">
        <!-- HEADER -->
        <header>
            <h1><i class="fas fa-cake-candles"></i> <?php echo htmlspecialchars($config['app_name']); ?></h1>
            <p>OWASP Training Lab - Secure Learning Environment</p>
            <div class="mode-badge">
                Mode: <strong><?php echo strtoupper($config['app_mode']); ?></strong>
            </div>
        </header>

        <!-- TRAINING WARNING -->
        <div class="training-banner">
            <i class="fas fa-exclamation-triangle"></i>
            ⚠️ TRAINING ENVIRONMENT ONLY - LOCAL USE ONLY ⚠️
        </div>

        <!-- MAIN CONTENT -->
        <div class="intro-grid">
            <!-- LEFT: PROJECT INFO -->
            <div class="info-section">
                <h2><i class="fas fa-info-circle"></i> About This Lab</h2>
                <p>
                    This is a deliberately built e-commerce system designed to teach web security vulnerabilities.
                    Each feature has both <strong>vulnerable</strong> and <strong>secure</strong> implementations.
                </p>
                <p>
                    Learn about OWASP Top 10 vulnerabilities by:
                </p>
                <ul>
                    <li>Browsing the vulnerable implementation
                    <li>Identifying the security flaw
                    <li>Comparing with the secure version
                    <li>Understanding the fix
                </ul>
                <p style="margin-top: 20px; color: #2d5016; font-weight: 600;">
                    Current Mode: <strong><?php echo ucfirst($config['app_mode']); ?></strong>
                    <?php if ($config['app_mode'] === 'vulnerable'): ?>
                    <br><small>Intentional vulnerabilities are active for training</small>
                    <?php else: ?>
                    <br><small>Best practices and secure implementations are active</small>
                    <?php endif; ?>
                </p>
            </div>

            <!-- RIGHT: QUICK START -->
            <div class="info-section">
                <h2><i class="fas fa-rocket"></i> Quick Start</h2>
                <p>Get started with the Cake Shop training system:</p>
                <ul>
                    <li>Browse product catalog
                    <li>Search by keywords
                    <li>View product details
                    <li>Manage shopping cart
                    <li>Create an account
                    <li>Learn security concepts
                </ul>
                <p style="margin-top: 20px; font-size: 13px; color: #999;">
                    ℹ️ Admin panel coming in Phase 4
                </p>
            </div>
        </div>

        <!-- CTA BUTTONS -->
        <div style="background-color: white; padding: 30px; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 40px;">
            <h2 style="color: #2d5016; margin-bottom: 20px; font-size: 22px; text-align: center; font-weight: 600;">
                Enter the Training Lab
            </h2>
            <div class="cta-buttons">
                <a href="/catalog" class="btn btn-primary">
                    <i class="fas fa-shopping-cart"></i> Browse Cakes
                </a>
                <?php if ($isLoggedIn): ?>
                <a href="/pages/account.php" class="btn btn-secondary">
                    <i class="fas fa-user"></i> My Account
                </a>
                <?php else: ?>
                <a href="/pages/login.php" class="btn btn-secondary">
                    <i class="fas fa-sign-in-alt"></i> Customer Login
                </a>
                <?php endif; ?>
                <a href="/pages/admin-login.php" class="btn btn-admin">
                    <i class="fas fa-lock"></i> Admin Login
                </a>
            </div>
            <?php if (!$isLoggedIn): ?>
            <div style="text-align: center; margin-top: 16px;">
                <a href="/pages/register.php" style="color: #2d5016; font-weight: 600; text-decoration: none;">
                    <i class="fas fa-user-plus"></i> New customer? Create an account
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- FEATURES -->
        <div style="margin-bottom: 40px;">
            <h2 style="color: #2d5016; text-align: center; margin-bottom: 25px; font-size: 24px; font-weight: 300;">
                <i class="fas fa-star"></i> Training Features
            </h2>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-cube"></i>
                    <h3>E-Commerce System</h3>
                    <p>Full product catalog, shopping cart, and checkout simulation with realistic workflows</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Security Training</h3>
                    <p>Learn OWASP Top 10 vulnerabilities through controlled, isolated examples</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-code-branch"></i>
                    <h3>Dual Mode System</h3>
                    <p>Switch between Vulnerable and Secure modes to compare implementations</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-book"></i>
                    <h3>Documentation</h3>
                    <p>Comprehensive guides, exercises, and vulnerability explanations</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-laptop-code"></i>
                    <h3>Hands-On Labs</h3>
                    <p>Practical exercises to exploit vulnerabilities and implement fixes</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-database"></i>
                    <h3>Realistic Data</h3>
                    <p>Mock data and workflows that simulate real e-commerce operations</p>
                </div>
            </div>
        </div>

        <!-- PROJECT STATUS -->
        <div class="app-status">
            <h3>📊 Project Status: Phase 2 - E-Commerce Features</h3>
            <p>
                <span class="status-badge">✓ COMPLETE</span>
                <strong>Phase 1:</strong> Core Setup & Configuration
            </p>
            <p>
                <span class="status-badge">✓ COMPLETE</span>
                <strong>Phase 2:</strong> E-Commerce Features (Customer Catalog, Cart, Auth)
            </p>
            <p>
                <span class="status-badge">⏳ NEXT</span>
                <strong>Phase 3:</strong> Checkout System & Orders
            </p>
            <p>
                <span class="status-badge">📋 TODO</span>
                <strong>Phase 4:</strong> Admin Panel & Management
            </p>
            <p>
                <span class="status-badge">📋 TODO</span>
                <strong>Phase 5:</strong> Full Testing & Documentation
            </p>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        <p>&copy; 2024 Cake Shop OWASP Training Lab | Educational Use Only | Local Training Environment</p>
        <p style="margin-top: 10px; opacity: 0.8;">
            Current URL: <code><?php echo $config['app_url']; ?></code> | Mode: <code><?php echo $config['app_mode']; ?></code>
        </p>
    </footer>
</body>
</html>
