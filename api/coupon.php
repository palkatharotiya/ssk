<?php
/**
 * BAPS Store - Coupon Validation API
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$raw = file_get_contents('php://input');
$json = json_decode($raw, true);
$data = is_array($json) ? array_merge($_POST, $json) : $_POST;

$code = trim(strtoupper($data['code'] ?? $_GET['code'] ?? ''));
$subtotal = (float)($data['subtotal'] ?? get_cart_subtotal());

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a coupon code.']);
    exit;
}

try {
    $coupon = db_fetch_one("SELECT * FROM coupons WHERE code = ? AND is_active = 1", [$code]);

    if (!$coupon) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired coupon code.']);
        exit;
    }

    if ($subtotal < (float)$coupon['min_order_amount']) {
        echo json_encode([
            'success' => false, 
            'message' => 'Coupon requires a minimum order of ' . format_price($coupon['min_order_amount']) . '.'
        ]);
        exit;
    }

    $discount = 0.0;
    if ($coupon['type'] === 'percentage') {
        $discount = round(($subtotal * (float)$coupon['value']) / 100, 2);
    } else {
        $discount = min($subtotal, (float)$coupon['value']);
    }

    $_SESSION['applied_coupon'] = [
        'code' => $coupon['code'],
        'discount' => $discount,
        'type' => $coupon['type'],
        'value' => $coupon['value']
    ];

    echo json_encode([
        'success' => true,
        'message' => 'Coupon ' . htmlspecialchars($coupon['code']) . ' applied successfully!',
        'discount' => $discount,
        'formatted_discount' => format_price($discount),
        'new_subtotal' => format_price(max(0, $subtotal - $discount))
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
