<?php
/**
 * BAPS Store - Wishlist API
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$raw = file_get_contents('php://input');
$json = json_decode($raw, true);
$data = is_array($json) ? array_merge($_POST, $json) : $_POST;

$productId = (int)($data['product_id'] ?? $_GET['product_id'] ?? 0);

if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product.']);
    exit;
}

try {
    $res = toggle_wishlist($productId);
    echo json_encode($res);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
