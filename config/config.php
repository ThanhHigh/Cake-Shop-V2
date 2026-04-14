<?php
/**
 * Cake Shop OWASP Training Lab - Configuration
 * 
 * This configuration file handles both vulnerable and secure runtime modes.
 * Environment variables control the active mode and per-feature toggles.
 */

// Load environment variables from .env file
function loadEnv($filePath)
{
    if (!file_exists($filePath)) {
        throw new RuntimeException(".env file not found at: $filePath");
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') === false || $line[0] === '#') {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        putenv("$key=$value");
    }
}

// Load environment
$envPath = dirname(__DIR__) . '/.env';
if (file_exists($envPath)) {
    loadEnv($envPath);
}

// Preflight checks: ensure safe local-only execution
function performSafetyChecks()
{
    $appMode = getenv('APP_MODE') ?: 'vulnerable';
    $appUrl = getenv('APP_URL') ?: '';
    $dbHost = getenv('DB_HOST') ?: 'localhost';

    $errors = [];

    // Check: only localhost binding allowed
    if ($dbHost !== 'localhost' && $dbHost !== '127.0.0.1') {
        $errors[] = "⚠️  SECURITY: Database must bind to localhost only. Found: $dbHost";
    }

    // Check: app URL must be local
    if ($appUrl && (strpos($appUrl, 'localhost') === false && strpos($appUrl, '127.0.0.1') === false)) {
        $errors[] = "⚠️  SECURITY: APP_URL must be localhost only. Found: $appUrl";
    }

    // Check: mode must be valid
    if ($appMode !== 'vulnerable' && $appMode !== 'secure') {
        $errors[] = "ERROR: APP_MODE must be 'vulnerable' or 'secure'. Found: $appMode";
    }

    // Display warnings/errors (CLI mode only - avoid headers conflict in web mode)
    if (!empty($errors) && php_sapi_name() === 'cli') {
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "CAKE SHOP TRAINING LAB - PREFLIGHT CHECK\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        foreach ($errors as $error) {
            echo $error . "\n";
        }
        if (preg_grep('/^ERROR/', $errors)) {
            echo "\n❌ Critical safety requirement failed. Aborting startup.\n";
            exit(1);
        }
    } elseif (!empty($errors)) {
        // In web mode, fail silently to critical errors only
        if (preg_grep('/^ERROR/', $errors)) {
            http_response_code(500);
            die('Critical configuration error. Check logs.');
        }
    }

    return $appMode;
}

// Global configuration array
$config = [
    // App Identity
    'app_name' => getenv('APP_NAME') ?: 'Cake Shop Training Lab',
    'app_mode' => performSafetyChecks(),
    'app_debug' => (getenv('APP_DEBUG') === 'true'),
    'app_url' => getenv('APP_URL') ?: 'http://localhost:8000',

    // Database
    'db' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => (int)(getenv('DB_PORT') ?: 3306),
        'user' => getenv('DB_USER') ?: 'cake_shop',
        'password' => getenv('DB_PASSWORD') ?: '',
        'name' => getenv('DB_NAME') ?: 'cake_shop_v2',
    ],

    // Security
    'security' => [
        'session_lifetime' => (int)(getenv('SESSION_LIFETIME') ?: 3600),
        'csrf_token_length' => (int)(getenv('CSRF_TOKEN_LENGTH') ?: 32),
        'max_login_attempts' => (int)(getenv('MAX_LOGIN_ATTEMPTS') ?: 5),
        'lockout_duration' => (int)(getenv('LOCKOUT_DURATION') ?: 900),
    ],

    // Per-feature vulnerability toggles
    'vulnerabilities' => [
        'sql_injection' => (getenv('VULN_SQL_INJECTION') === 'true'),
        'access_control' => (getenv('VULN_ACCESS_CONTROL') === 'true'),
        'weak_auth' => (getenv('VULN_WEAK_AUTH') === 'true'),
        'insecure_upload' => (getenv('VULN_INSECURE_UPLOAD') === 'true'),
        'os_command_injection' => (getenv('VULN_OS_COMMAND_INJECTION') === 'true'),
        'misconfiguration' => (getenv('VULN_MISCONFIGURATION') === 'true'),
        'broken_auth' => (getenv('VULN_BROKEN_AUTH') === 'true'),
        'session_issues' => (getenv('VULN_SESSION_ISSUES') === 'true'),
        'integrity_failures' => (getenv('VULN_INTEGRITY_FAILURES') === 'true'),
        'logging_failures' => (getenv('VULN_LOGGING_FAILURES') === 'true'),
        'ssrf' => (getenv('VULN_SSRF') === 'true'),
        'review_injection' => (getenv('VULN_REVIEW_INJECTION') === 'true'),
    ],
];

/**
 * Helper function: check if a vulnerability is active
 */
function isVulnerable($vulnerabilityName)
{
    global $config;
    return $config['app_mode'] === 'vulnerable' &&
           $config['vulnerabilities'][$vulnerabilityName] ?? false;
}

/**
 * Helper function: check if we're in secure mode
 */
function isSecure()
{
    global $config;
    return $config['app_mode'] === 'secure';
}

return $config;
