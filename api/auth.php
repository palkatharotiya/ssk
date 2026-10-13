<?php
/**
 * BAPS Store - Authentication API
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$raw = file_get_contents('php://input');
$json = json_decode($raw, true);
$data = is_array($json) ? array_merge($_POST, $json) : $_POST;

$action = $data['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'login':
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';
            if (empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Please enter both email and password.']);
                exit;
            }
            $res = login_user($email, $password);
            echo json_encode($res);
            break;

        case 'register':
            $firstName = $data['first_name'] ?? '';
            $lastName = $data['last_name'] ?? '';
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';
            $phone = $data['phone'] ?? '';

            if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
                exit;
            }
            $res = register_user($firstName, $lastName, $email, $password, $phone);
            echo json_encode($res);
            break;

        case 'logout':
            logout_user();
            if (isset($_GET['redirect'])) {
                header('Location: ../' . $_GET['redirect']);
                exit;
            }
            echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
