<?php
/**
 * SSK Store - CLI Admin Provisioning Tool
 * Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 *
 * Usage:
 *   Interactive:
 *     php tools/create-admin.php
 *   Non-interactive (flags):
 *     php tools/create-admin.php --username=admin --email=temple@sskstore.in --password="StrongPassword123!" --name="SSK Super Admin" --role=super_admin
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Access Denied: This tool may only be executed via the Command Line Interface (CLI).\n";
    exit(1);
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

echo "========================================================\n";
echo "  SSK STORE — Secure Admin Provisioning Tool (P0-4)\n";
echo "  Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)\n";
echo "========================================================\n\n";

// Parse CLI Options
$options = getopt('', ['username:', 'email:', 'password:', 'name:', 'role:']);

function promptInput(string $prompt, bool $required = true, string $default = ''): string {
    echo $prompt . ($default !== '' ? " [default: $default]" : '') . ": ";
    $line = trim((string)fgets(STDIN));
    if ($line === '' && $default !== '') {
        return $default;
    }
    while ($required && $line === '') {
        echo "  Field cannot be empty. $prompt: ";
        $line = trim((string)fgets(STDIN));
    }
    return $line;
}

$username = $options['username'] ?? '';
if (empty($username)) {
    $username = promptInput('Admin Username (min 3 chars)', true, 'superadmin');
}
if (strlen($username) < 3) {
    echo "Error: Username must be at least 3 characters.\n";
    exit(1);
}

$name = $options['name'] ?? '';
if (empty($name)) {
    $name = promptInput('Full Name', true, 'SSK Administrator');
}

$email = $options['email'] ?? '';
if (empty($email)) {
    $email = promptInput('Email Address', true, 'admin@sskstore.in');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Error: Invalid email address format: '$email'\n";
    exit(1);
}

$role = $options['role'] ?? '';
if (empty($role)) {
    $role = promptInput('Role (super_admin / staff)', true, 'super_admin');
}
if (!in_array($role, ['super_admin', 'staff'], true)) {
    echo "Error: Invalid role. Must be 'super_admin' or 'staff'.\n";
    exit(1);
}

$password = $options['password'] ?? '';
if (empty($password)) {
    echo "\nEnter password for '$username' (minimum 12 characters):\n";
    $password = promptInput('Password');
}

if (strlen($password) < 12) {
    echo "\nSecurity Error (Hard Rule P0-4): Admin passwords MUST be at least 12 characters long.\n";
    echo "Password entered was only " . strlen($password) . " characters. Operation aborted.\n";
    exit(1);
}

// Check for known trivial passwords
$forbidden = ['password', 'admin123', 'admin@123', '123456789012', 'password1234'];
if (in_array(strtolower($password), $forbidden, true)) {
    echo "\nSecurity Error: Trivial or default passwords are strictly prohibited.\n";
    exit(1);
}

$permissions = ($role === 'super_admin') 
    ? json_encode(['*']) 
    : json_encode(['orders', 'products', 'categories', 'banners', 'homepage_sections', 'messages']);

$hash = password_hash($password, PASSWORD_DEFAULT);

// Check if user already exists
$existing = db_fetch_one("SELECT id, username, email FROM admin_users WHERE username = ? OR email = ?", [$username, $email]);

if ($existing) {
    echo "\nFound existing admin account with matching username or email (ID: {$existing['id']}).\n";
    echo "Updating credentials and enforcing active status...\n";
    
    db_query(
        "UPDATE admin_users SET username = ?, email = ?, password = ?, full_name = ?, role = ?, permissions = ?, is_active = 1 WHERE id = ?",
        [$username, $email, $hash, $name, $role, $permissions, $existing['id']]
    );
    
    echo "SUCCESS: Admin user '{$username}' (ID: {$existing['id']}) updated with new secure password.\n";
} else {
    $newId = db_insert(
        "INSERT INTO admin_users (username, email, password, full_name, role, permissions, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)",
        [$username, $email, $hash, $name, $role, $permissions]
    );
    
    echo "SUCCESS: Created new admin user '{$username}' (ID: $newId, Role: $role).\n";
}

echo "\nYou can now sign in at http://localhost:8000/admin/login.php\n";
exit(0);
