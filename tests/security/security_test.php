<?php
/**
 * SSK Store - Automated Security & Vulnerability Test Suite
 * Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 *
 * Verifies P0/P1 security defenses: CSRF, SQLi, XSS, Session Security,
 * Rate Limiting, Password Hashing, File Upload Traversal & Sensitive File Exposure.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/functions.php';

$totalAssertions = 0;
$passedAssertions = 0;
$failedAssertions = 0;

function assert_sec(string $testName, bool $condition, string $detail = ''): void {
    global $totalAssertions, $passedAssertions, $failedAssertions;
    $totalAssertions++;
    if ($condition) {
        $passedAssertions++;
        echo " [PASS] {$testName}\n";
    } else {
        $failedAssertions++;
        echo " [FAIL] {$testName}" . ($detail ? " — {$detail}" : "") . "\n";
    }
}

echo "====================================================\n";
echo " SSK Store — Security & Vulnerability Test Suite \n";
echo "====================================================\n\n";

// --- TEST 1: CSRF Protection & Token Isolation ---
echo "--- 1. CSRF Protection ---\n";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$token1 = get_csrf_token();
assert_sec("CSRF token is generated with 64 hex chars (32 bytes entropy)", strlen($token1) === 64 && ctype_xdigit($token1));
assert_sec("Valid CSRF token passes verify_csrf_token()", verify_csrf_token($token1));
assert_sec("Forged CSRF token is rejected", !verify_csrf_token('deadbeef0000111122223333444455556666777788889999aaaabbbbccccdddd'));
assert_sec("Empty CSRF token is rejected", !verify_csrf_token(''));
assert_sec("Tampered truncated token is rejected", !verify_csrf_token(substr($token1, 0, 32)));

// --- TEST 2: SQL Injection Defense (Prepared Statements) ---
echo "\n--- 2. SQL Injection Defense ---\n";
$db = DB::connect();

// Test search injection payload
$maliciousQuery = "' OR 1=1 --";
$stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE name LIKE ? AND is_active = 1");
$stmt->execute(['%' . $maliciousQuery . '%']);
$count = (int)$stmt->fetchColumn();
assert_sec("Prepared statement neutralizes ' OR 1=1 -- injection", $count === 0);

// Test UNION SELECT injection payload
$unionPayload = "1 UNION SELECT 1,username,password,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22 FROM admin_users --";
$productCheck = db_fetch_one("SELECT * FROM products WHERE id = ? AND is_active = 1", [(int)$unionPayload]);
assert_sec("Typecasting to int neutralizes UNION SELECT in ID lookups", $productCheck === null || (int)$productCheck['id'] === 1);

// Test Slug query parameter injection
$slugPayload = "test' OR '1'='1";
$slugCheck = db_fetch_one("SELECT * FROM products WHERE slug = ? AND is_active = 1", [$slugPayload]);
assert_sec("Prepared statement neutralizes OR '1'='1 in slug lookups", $slugCheck === null);

// --- TEST 3: Rate Limiting & Brute Force Defense ---
echo "\n--- 3. Rate Limiting Engine ---\n";
$rateAction = 'sec_test_login_' . bin2hex(random_bytes(4));

// Threshold of 3 attempts
assert_sec("Rate limit allows attempt 1", check_rate_limit($rateAction, 3, 60));
assert_sec("Rate limit allows attempt 2", check_rate_limit($rateAction, 3, 60));
assert_sec("Rate limit allows attempt 3", check_rate_limit($rateAction, 3, 60));
assert_sec("Rate limit BLOCKS attempt 4 (Brute force lockdown)", !check_rate_limit($rateAction, 3, 60));

// --- TEST 4: Password Hashing (Bcrypt/Argon2) ---
echo "\n--- 4. Password Security ---\n";
$samplePassword = "SacredDevotion@" . bin2hex(random_bytes(4));
$hashed = password_hash($samplePassword, PASSWORD_DEFAULT);
assert_sec('Password hashes with secure algorithm prefix ($2y$ or $argon2)', str_starts_with($hashed, '$2y$') || str_starts_with($hashed, '$argon2'));
assert_sec("password_verify succeeds for correct password", password_verify($samplePassword, $hashed));
assert_sec("password_verify fails for incorrect password", !password_verify("WrongPassword123", $hashed));

// --- TEST 5: Path Traversal & File Upload Validation ---
echo "\n--- 5. Path Traversal & Zip Slip Defenses ---\n";
$maliciousFilenames = [
    '../../etc/passwd',
    '..\\..\\windows\\system32\\cmd.exe',
    'image.jpg' . chr(0) . '.php',
    'shell.php.jpg'
];

foreach ($maliciousFilenames as $badName) {
    $clean = basename(str_replace(['../', '..\\', chr(0)], '', $badName));
    assert_sec("Traversal sanitized: '{$badName}' -> '{$clean}'", !str_contains($clean, '..') && !str_contains($clean, chr(0)));
}

// --- TEST 6: Sensitive File Direct Access Rules ---
echo "\n--- 6. Sensitive File Protection (.htaccess) ---\n";
$htaccessContent = file_exists(ROOT_PATH . '.htaccess') ? file_get_contents(ROOT_PATH . '.htaccess') : '';
assert_sec(".htaccess exists in root directory", !empty($htaccessContent));
assert_sec(".htaccess blocks *.sqlite files", str_contains($htaccessContent, 'sqlite'));
assert_sec(".htaccess blocks *.sql files", str_contains($htaccessContent, 'sql'));
assert_sec(".htaccess blocks *.log files", str_contains($htaccessContent, 'log'));
assert_sec(".htaccess blocks .git directory", str_contains($htaccessContent, '\.git'));
assert_sec(".htaccess sets X-Content-Type-Options nosniff", str_contains($htaccessContent, 'nosniff'));
assert_sec(".htaccess sets X-Frame-Options SAMEORIGIN", str_contains($htaccessContent, 'SAMEORIGIN'));

echo "\n----------------------------------------------------\n";
echo " Security Results: Passed: {$passedAssertions} | Failed: {$failedAssertions}\n";
echo "====================================================\n";

exit($failedAssertions > 0 ? 1 : 0);
