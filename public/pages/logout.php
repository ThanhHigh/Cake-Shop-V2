<?php
/**
 * Logout handler
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
$authService->logout();

header('Location: /pages/login.php');
exit;
