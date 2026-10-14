<?php
/**
 * SSK Store - Automated Smoke Test Suite
 * Shree Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 *
 * Verifies core functionality, database queries, security gates, and functions.
 * Usage: php tools/smoke_test.php
 */

declare(strict_types=1);

// Load bootstrap BEFORE any output to avoid headers-already-sent warnings in CLI
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/mail.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "====================================================\n";
echo " SSK Store — Automated Pre-Flight Smoke Test Suite \n";
echo "====================================================\n\n";

$passed = 0;
$failed = 0;

function assert_test(string $name, bool $condition, string $details = ''): void {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] {$name}\n";
        $passed++;
    } else {
        echo " [FAIL] {$name} " . ($details ? "({$details})" : "") . "\n";
        $failed++;
    }
}

// 1. Config & Environment
assert_test("Config loaded & SITE_NAME is defined", defined('SITE_NAME') && SITE_NAME === 'SSK.STORE');
assert_test("Timezone is Asia/Kolkata", date_default_timezone_get() === 'Asia/Kolkata');

// 2. Database Connection
$pdo = DB::connect();
$driver = DB::getDriver();
assert_test("Database connected successfully", $pdo instanceof PDO, "Driver: {$driver}");

// 3. Security Helpers & CSRF
$token = get_csrf_token();
assert_test("CSRF token generates non-empty string", !empty($token) && strlen($token) >= 32);
assert_test("CSRF token verification succeeds", verify_csrf_token($token));
assert_test("CSRF token verification fails on forged token", !verify_csrf_token('invalid-token-123456'));

// 4. Functions & Helpers

// Slug Transliteration & Uniqueness
$slug1 = generate_unique_slug('શ્રી વચનામૃત રહસ્યાર્થ', 'products');
assert_test("Gujarati slug transliterates non-empty safe string", !empty($slug1) && !preg_match('/[^\w-]/', $slug1), "Slug: {$slug1}");

// SKU Generation
$sku1 = generate_unique_sku('Vachanamrut Hardcover');
assert_test("Unique SKU generates expected format", str_starts_with($sku1, 'VAC-'));

// Dynamic Shipping Calculation
$shipGujBelow = calculate_shipping(400.0, 'Gujarat');
$shipGujAbove = calculate_shipping(600.0, 'Gujarat');
$shipIndiaBelow = calculate_shipping(700.0, 'Maharashtra');
$shipIndiaAbove = calculate_shipping(850.0, 'Rajasthan');
assert_test("Gujarat shipping below ₹500 is ₹50", $shipGujBelow === 50.0, "Got: {$shipGujBelow}");
assert_test("Gujarat shipping above ₹500 is FREE", $shipGujAbove === 0.0, "Got: {$shipGujAbove}");
assert_test("Interstate shipping below ₹750 is ₹80", $shipIndiaBelow === 80.0, "Got: {$shipIndiaBelow}");
assert_test("Interstate shipping above ₹750 is FREE", $shipIndiaAbove === 0.0, "Got: {$shipIndiaAbove}");

// Indian States List
$states = get_indian_states();
assert_test("Indian states list contains 28 states + 8 UTs", count($states) >= 36);
assert_test("Gujarat is present in states list", isset($states['Gujarat']));

// Wishlist Cache & Map
$wishMap = get_customer_wishlist_map();
assert_test("Wishlist map returns array without fatal error", is_array($wishMap));

// Transactional Mail
$testMailResult = send_app_mail('dev-test@sskstore.in', 'Test Customer', 'Smoke Test Notification', '<p>Test Body</p>', 'Test Body Text');
assert_test("Transactional mail helper executes successfully", $testMailResult === true);

// 5. Schema & Tables Check
$tables = ['departments', 'categories', 'subcategories', 'products', 'customers', 'orders', 'order_items', 'cart', 'wishlist', 'login_attempts', 'password_resets'];
foreach ($tables as $t) {
    $exists = false;
    try {
        $count = db_fetch_one("SELECT COUNT(*) as c FROM {$t}");
        $exists = ($count !== null);
    } catch (\Throwable $e) {
        $exists = false;
    }
    assert_test("Table '{$t}' exists and is queryable", $exists);
}

// 6. Admin Authentication Gate Check
$adminGateBlocked = false;
try {
    $adminGateBlocked = !is_admin_logged_in();
} catch (\Throwable $e) {
    $adminGateBlocked = false;
}
assert_test("Unauthenticated admin access is recognized as unlogged", $adminGateBlocked);

echo "\n----------------------------------------------------\n";
echo " Test Results: Passed: {$passed} | Failed: {$failed}\n";
echo "====================================================\n";

exit($failed > 0 ? 1 : 0);
