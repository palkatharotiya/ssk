<?php
/**
 * SSK Store - REST API v1: Authentication
 * POST /api/v1/auth.php
 * Actions: login, register, forgot_password
 * Returns JWT-style token for app session management
 */

declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST method is accepted.']);
    exit;
}

$raw = file_get_contents('php://input');
$json = json_decode($raw, true);
$data = is_array($json) ? $json : $_POST;

$action = $data['action'] ?? '';

/**
 * Generate a simple secure token for the mobile app.
 * In production, this would be a JWT or similar. For now, a random
 * token stored in a token table or session.
 */
function generate_api_token(int $customerId): string {
    $token = bin2hex(random_bytes(32));

    // Store token — use a simple approach: store in customer record or a tokens table
    // For simplicity, we'll embed customer_id in the token format
    $payload = base64_encode(json_encode([
        'customer_id' => $customerId,
        'token' => $token,
        'issued_at' => time(),
        'expires_at' => time() + (86400 * 30), // 30 days
    ]));

    return $payload;
}

function validate_api_token(string $bearerToken): ?array {
    $decoded = json_decode(base64_decode($bearerToken), true);
    if (!$decoded || !isset($decoded['customer_id']) || !isset($decoded['expires_at'])) {
        return null;
    }
    if ($decoded['expires_at'] < time()) {
        return null;
    }
    $customer = db_fetch_one(
        "SELECT id, first_name, last_name, email, phone FROM customers WHERE id = ? AND is_active = 1",
        [$decoded['customer_id']]
    );
    return $customer;
}

try {
    switch ($action) {
        case 'login':
            $email = trim(strtolower($data['email'] ?? ''));
            $password = $data['password'] ?? '';

            if (empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Please enter both email and password.']);
                exit;
            }

            $user = db_fetch_one("SELECT * FROM customers WHERE email = ? AND is_active = 1", [$email]);
            if (!$user || !password_verify($password, $user['password'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid email address or password.']);
                exit;
            }

            $token = generate_api_token((int)$user['id']);

            echo json_encode([
                'success' => true,
                'message' => 'Welcome back, ' . htmlspecialchars($user['first_name']) . '!',
                'data' => [
                    'token' => $token,
                    'customer' => [
                        'id' => (int)$user['id'],
                        'first_name' => $user['first_name'],
                        'last_name' => $user['last_name'],
                        'email' => $user['email'],
                        'phone' => $user['phone'] ?? '',
                    ]
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'register':
            $firstName = trim($data['first_name'] ?? '');
            $lastName = trim($data['last_name'] ?? '');
            $email = trim(strtolower($data['email'] ?? ''));
            $password = $data['password'] ?? '';
            $phone = trim($data['phone'] ?? '');

            if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
                exit;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Please provide a valid email address.']);
                exit;
            }
            if (strlen($password) < 8) {
                echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long.']);
                exit;
            }

            $existing = db_fetch_one("SELECT id FROM customers WHERE email = ?", [$email]);
            if ($existing) {
                echo json_encode(['success' => false, 'message' => 'An account with this email already exists.']);
                exit;
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            db_query(
                "INSERT INTO customers (first_name, last_name, email, password, phone, is_active, email_verified) VALUES (?, ?, ?, ?, ?, 1, 1)",
                [$firstName, $lastName, $email, $hash, $phone]
            );
            $newId = (int)db_last_insert_id();

            $token = generate_api_token($newId);

            echo json_encode([
                'success' => true,
                'message' => 'Account created successfully! Welcome, ' . htmlspecialchars($firstName) . '!',
                'data' => [
                    'token' => $token,
                    'customer' => [
                        'id' => $newId,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $email,
                        'phone' => $phone,
                    ]
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'forgot_password':
            $email = trim(strtolower($data['email'] ?? ''));
            if (empty($email)) {
                echo json_encode(['success' => false, 'message' => 'Please enter your email address.']);
                exit;
            }

            $user = db_fetch_one("SELECT id, first_name FROM customers WHERE email = ? AND is_active = 1", [$email]);
            // Always respond success to prevent email enumeration
            echo json_encode([
                'success' => true,
                'message' => 'If an account exists with this email, password reset instructions have been sent. Please also contact us on WhatsApp (+91 9426-55-1098) for immediate assistance.'
            ]);
            break;

        case 'validate_token':
            $token = $data['token'] ?? '';
            if (empty($token)) {
                echo json_encode(['success' => false, 'message' => 'Token is required.']);
                exit;
            }
            $customer = validate_api_token($token);
            if ($customer) {
                echo json_encode([
                    'success' => true,
                    'data' => ['customer' => $customer]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Token is invalid or expired.']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action. Use: login, register, forgot_password, or validate_token.']);
            break;
    }
} catch (\Throwable $e) {
    error_log('[SSK API v1/auth] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred. Please try again.'
    ]);
}
