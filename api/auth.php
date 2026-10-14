<?php
/**
 * SSK Store - Authentication API
 * Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 */

declare(strict_types=1);

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

$raw = file_get_contents('php://input');
$json = json_decode($raw, true);
$data = is_array($json) ? array_merge($_POST, $json) : $_POST;

$action = $data['action'] ?? $_GET['action'] ?? '';

// All authentication state transitions require POST and verified origin
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Authentication actions require POST.']);
    exit;
}

if (!verify_request_origin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Request origin validation failed.']);
    exit;
}

try {
    switch ($action) {
        case 'login':
            $email = trim((string)($data['email'] ?? ''));
            $password = (string)($data['password'] ?? '');
            if (empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Please enter both email and password.']);
                exit;
            }
            $res = login_user($email, $password);
            echo json_encode($res);
            break;

        case 'register':
            $firstName = trim((string)($data['first_name'] ?? ''));
            $lastName = trim((string)($data['last_name'] ?? ''));
            $email = trim((string)($data['email'] ?? ''));
            $password = (string)($data['password'] ?? '');
            $phone = trim((string)($data['phone'] ?? ''));

            if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
                exit;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Please provide a valid email address.']);
                exit;
            }
            $res = register_user($firstName, $lastName, $email, $password, $phone);
            echo json_encode($res);
            break;

        case 'logout':
            logout_user();
            echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
            break;
    }
} catch (Throwable $e) {
    error_log('[SSK Auth API Error] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred. Please try again.']);
}
