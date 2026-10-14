<?php
/**
 * SSK Store - Local Development Super Admin Provisioning Script
 * Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 *
 * This script is strictly for local development and testing environments.
 * It is HARD-LOCKED against execution in production.
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Forbidden: CLI execution only.\n";
    exit(1);
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Strict Production Guard: NEVER allow weak local credentials in production
$appEnv = trim((string)(getenv('APP_ENV') ?: ($_SERVER['APP_ENV'] ?? ($_ENV['APP_ENV'] ?? ''))));
if ((defined('IS_PRODUCTION') && IS_PRODUCTION) || $appEnv === 'production') {
    echo "========================================================\n";
    echo "SECURITY ABORT: Local development provisioning script is\n";
    echo "STRICTLY PROHIBITED in production environments.\n";
    echo "Use tools/create-admin.php with strong passwords instead.\n";
    echo "========================================================\n";
    exit(1);
}

// Parse optional CLI options
$options = getopt('', ['username::', 'password::', 'email::', 'name::', 'role::']);

$username = $options['username'] ?? 'om';
$rawPassword = $options['password'] ?? '123';
$email = $options['email'] ?? 'om@sskstore.in';
$name = $options['name'] ?? 'SSK Super Admin';
$role = $options['role'] ?? 'super_admin';

if ($role !== 'super_admin') {
    $role = 'super_admin';
}

$permissions = json_encode(['*']);
$passwordHash = password_hash($rawPassword, PASSWORD_DEFAULT);

echo "========================================================\n";
echo "  SSK STORE — Local Dev Super Admin Setup\n";
echo "  Target User: $username ($role)\n";
echo "========================================================\n\n";

// Check if user already exists
$existing = db_fetch_one("SELECT id, username, email, role, permissions, is_active FROM admin_users WHERE username = ? OR email = ?", [$username, $email]);

if ($existing) {
    echo "Existing account found for username '{$existing['username']}' (ID: {$existing['id']}).\n";
    echo "  Current Role:        {$existing['role']}\n";
    echo "  Current Active:      {$existing['is_active']}\n";
    echo "  Updating credentials to Super Admin with hashed password...\n";

    db_query(
        "UPDATE admin_users SET username = ?, email = ?, password = ?, full_name = ?, role = ?, permissions = ?, is_active = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
        [$username, $email, $passwordHash, $name, $role, $permissions, $existing['id']]
    );

    echo "SUCCESS: Account '{$username}' updated to active super_admin.\n";
} else {
    echo "No existing account found. Creating new Super Admin account...\n";

    $newId = db_insert(
        "INSERT INTO admin_users (username, email, password, full_name, role, permissions, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
        [$username, $email, $passwordHash, $name, $role, $permissions]
    );

    echo "SUCCESS: Created new Super Admin user '{$username}' (ID: $newId).\n";
}

// Self-verification check
$check = db_fetch_one("SELECT id, username, email, role, permissions, is_active, password FROM admin_users WHERE username = ?", [$username]);
if (!$check || !password_verify($rawPassword, $check['password'])) {
    echo "ERROR: Password verification check failed after provisioning.\n";
    exit(1);
}

echo "\nVerification passed:";
echo "\n  Username:    {$check['username']}";
echo "\n  Email:       {$check['email']}";
echo "\n  Role:        {$check['role']}";
echo "\n  Permissions: {$check['permissions']}";
echo "\n  Is Active:   {$check['is_active']}";
echo "\n  Password:    [HASH VERIFIED VIA password_verify]\n";
echo "\nLocal setup complete. Ready to log in at http://127.0.0.1:8000/admin/login.php\n";
exit(0);
