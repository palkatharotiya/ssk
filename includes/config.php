<?php
/**
 * SSK Store - Core Configuration & Environment Settings
 * Shree Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 */

// Start output buffering to prevent headers already sent errors
if (!ob_get_level()) {
    ob_start();
}

// Environment: set to true in live production hosting
define('IS_PRODUCTION', getenv('APP_ENV') === 'production');

if (IS_PRODUCTION) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'ssk_store');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Dynamic Site URL detection
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
$proto = $isHttps ? 'https://' : 'http://';
$httpHost = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$subDir = preg_replace('#/(admin|api|pages).*$#', '', $scriptPath);
$subDir = rtrim($subDir, '/');

define('SITE_URL', $proto . $httpHost . $subDir);
define('SITE_NAME', 'SSK.STORE');
define('SITE_TAGLINE', 'Shri Sahajanand Sahitya Kendra — Kalakunj Mandir, Surat');
define('CURRENCY_SYMBOL', '₹');
define('CURRENCY_CODE', 'INR');

// Centralized Store Contact & Payment Constants (Single Source of Truth)
define('STORE_UPI_ID', '9426551098@upi');
define('STORE_UPI_NAME', 'SSK Kalakunj Mandir');
define('STORE_WHATSAPP_NUMBER', '+91 9426-55-1098');
define('STORE_WHATSAPP_CLEAN', '919426551098');
define('STORE_WHATSAPP_URL', 'https://wa.me/919426551098?text=Jai%20Swaminarayan');
define('STORE_EMAIL', 'contact@sskstore.in');
define('STORE_PHONE', '+91 9426-55-1098');
define('STORE_HOURS', 'Daily (Mon – Sun): 8:00 AM – 12:00 PM & 4:00 PM – 8:00 PM IST');
define('STORE_ADDRESS', 'Shree Swaminarayan Mandir, Kalakunj, Varachha Main Rd, opp. Water Tank, Surat, Gujarat 395006, India');

// File paths
define('ROOT_PATH', dirname(__DIR__) . '/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('UPLOADS_PATH', ROOT_PATH . 'uploads/');
define('ASSETS_URL', SITE_URL . '/assets');
define('UPLOADS_URL', SITE_URL . '/uploads');

// Secure Session Cookie Configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    session_name('SSK_SESSID');

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } else {
        session_set_cookie_params(0, '/; samesite=Lax', '', $isHttps, true);
    }

    // Skip session startup on pure static media/asset generators unless a session cookie already exists
    $scriptName = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $isStaticEndpoint = in_array($scriptName, ['placeholder.php', 'sitemap.xml.php', 'robots.txt.php'], true);

    if (!$isStaticEndpoint || isset($_COOKIE['SSK_SESSID']) || isset($_COOKIE['PHPSESSID'])) {
        session_start();
    }
}

// Error reporting: Log errors to file and hide from public view in production
if (IS_PRODUCTION) {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', ROOT_PATH . 'logs/php-errors.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Timezone
date_default_timezone_set('Asia/Kolkata');
