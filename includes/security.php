<?php
/**
 * SSK Store - Unified Security & Defensive Engineering Layer
 * Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

/**
 * Escape HTML output safely for text and attributes with UTF-8 support.
 * Handles null gracefully without PHP 8.1+ deprecation warnings.
 */
function e(?string $string): string {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Enforces valid CSRF token on POST requests.
 * Accepts token from $_POST['csrf_token'] or HTTP Header 'X-CSRF-Token'.
 * Terminates execution with HTTP 403 if token validation fails.
 */
function require_csrf(?string $token = null): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $providedToken = $token 
        ?? $_POST['csrf_token'] 
        ?? $_SERVER['HTTP_X_CSRF_TOKEN'] 
        ?? '';

    if (!verify_csrf_token($providedToken)) {
        if (
            (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
            (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
            (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
        ) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Security token invalid or expired. Please refresh the page and try again.'
            ]);
            exit;
        }

        http_response_code(403);
        set_flash('error', 'Security token expired or invalid. Please refresh and try again.');
        $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        header('Location: ' . filter_var($referer, FILTER_SANITIZE_URL));
        exit;
    }
}

/**
 * Validates request Origin and Sec-Fetch-Site on API endpoints to prevent cross-origin abuse.
 */
function verify_request_origin(): bool {
    // If running in CLI / testing environment, pass
    if (php_sapi_name() === 'cli') {
        return true;
    }

    $siteUrlParts = parse_url(SITE_URL);
    $expectedHost = strtolower($siteUrlParts['host'] ?? 'localhost');
    $expectedPort = isset($siteUrlParts['port']) ? (int)$siteUrlParts['port'] : null;

    if (!empty($_SERVER['HTTP_ORIGIN'])) {
        $originParts = parse_url($_SERVER['HTTP_ORIGIN']);
        $originHost = strtolower($originParts['host'] ?? '');
        $originPort = isset($originParts['port']) ? (int)$originParts['port'] : null;

        if ($originHost !== $expectedHost || ($expectedPort && $originPort !== $expectedPort)) {
            // Also allow localhost for development
            if (!in_array($originHost, ['localhost', '127.0.0.1'], true)) {
                return false;
            }
        }
    }

    // Check Sec-Fetch-Site (modern browsers)
    if (!empty($_SERVER['HTTP_SEC_FETCH_SITE'])) {
        $fetchSite = strtolower($_SERVER['HTTP_SEC_FETCH_SITE']);
        if (!in_array($fetchSite, ['same-origin', 'same-site', 'none'], true)) {
            return false;
        }
    }

    return true;
}

/**
 * Injects mandatory HTTP defensive security headers.
 */
function send_security_headers(): void {
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), camera=(), microphone=(), payment=(self)');

    // Only send HSTS if connection is HTTPS
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) ||
               (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    // Content Security Policy (Report-Only initially to prevent breakage of inline scripts)
    $csp = "default-src 'self'; " .
           "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://fonts.googleapis.com; " .
           "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
           "font-src 'self' https://fonts.gstatic.com data:; " .
           "img-src 'self' data: https: blob:; " .
           "connect-src 'self'; " .
           "frame-ancestors 'self'; " .
           "base-uri 'self'; " .
           "form-action 'self';";

    header('Content-Security-Policy-Report-Only: ' . $csp);
}

/**
 * IP and session-based request rate limiting for brute-force sensitive endpoints.
 *
 * @param string $action Action key (e.g. 'login', 'contact', 'coupon')
 * @param int $maxAttempts Maximum allowed attempts within time window
 * @param int $decaySeconds Time window in seconds
 * @return bool True if permitted, False if rate-limited
 */
function check_rate_limit(string $action, int $maxAttempts = 5, int $decaySeconds = 300): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $key = 'rate_' . md5($action . '_' . $ip);

    $now = time();
    $attempts = $_SESSION[$key] ?? [];

    // Filter attempts within the decay window
    $attempts = array_filter($attempts, fn($timestamp) => ($now - $timestamp) < $decaySeconds);

    if (count($attempts) >= $maxAttempts) {
        return false;
    }

    $attempts[] = $now;
    $_SESSION[$key] = $attempts;
    return true;
}

/**
 * Records a failed authentication attempt in the login_attempts table.
 */
function record_login_attempt(string $accountKey): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    db_query("INSERT INTO login_attempts (ip_address, account_key, attempt_time) VALUES (?, ?, CURRENT_TIMESTAMP)", [
        $ip, substr($accountKey, 0, 190)
    ]);
}

/**
 * Checks if an IP or account identifier is currently locked out from login.
 * 5 failures within 15 minutes = locked out.
 */
function is_login_locked_out(string $accountKey, int $maxAttempts = 5, int $lockoutMinutes = 15): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $driver = DB::getDriver();
    $cutoff = ($driver === 'sqlite') 
        ? "datetime('now', '-{$lockoutMinutes} minutes')" 
        : "DATE_SUB(NOW(), INTERVAL {$lockoutMinutes} MINUTE)";

    $row = db_fetch_one("SELECT COUNT(*) as cnt FROM login_attempts WHERE (ip_address = ? OR account_key = ?) AND attempt_time >= $cutoff", [
        $ip, substr($accountKey, 0, 190)
    ]);
    return ((int)($row['cnt'] ?? 0)) >= $maxAttempts;
}

/**
 * Clears failed login attempts after successful authentication.
 */
function clear_login_attempts(string $accountKey): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    db_query("DELETE FROM login_attempts WHERE ip_address = ? OR account_key = ?", [
        $ip, substr($accountKey, 0, 190)
    ]);
}

// Automatically send security headers on inclusion
send_security_headers();
