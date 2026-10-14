<?php
/**
 * SSK Store - Secure Coupon Validation API
 * Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 */

declare(strict_types=1);

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

if (!verify_request_origin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Cross-origin request rejected.']);
    exit;
}

$raw = file_get_contents('php://input');
$json = json_decode($raw, true);
$data = is_array($json) ? array_merge($_POST, $json) : $_POST;

$code = trim(strtoupper($data['code'] ?? $_GET['code'] ?? ''));

// Subtotal MUST ALWAYS be calculated securely from the server-side cart
$subtotal = get_cart_subtotal();

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a coupon code.']);
    exit;
}

if ($subtotal <= 0) {
    echo json_encode(['success' => false, 'message' => 'Your cart is empty.']);
    exit;
}

try {
    $coupon = db_fetch_one("SELECT * FROM coupons WHERE code = ? AND is_active = 1", [$code]);

    if (!$coupon) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired coupon code.']);
        exit;
    }

    $today = date('Y-m-d');
    if (!empty($coupon['start_date']) && $coupon['start_date'] > $today) {
        echo json_encode(['success' => false, 'message' => 'This promotional coupon is not yet active.']);
        exit;
    }

    if (!empty($coupon['end_date']) && $coupon['end_date'] < $today) {
        echo json_encode(['success' => false, 'message' => 'This coupon code has expired.']);
        exit;
    }

    if (!empty($coupon['max_uses']) && (int)$coupon['used_count'] >= (int)$coupon['max_uses']) {
        echo json_encode(['success' => false, 'message' => 'This coupon has reached its maximum usage limit.']);
        exit;
    }

    $minOrder = (float)$coupon['min_order_amount'];
    if ($subtotal < $minOrder) {
        echo json_encode([
            'success' => false, 
            'message' => 'Coupon requires a minimum order of ' . format_price($minOrder) . '.'
        ]);
        exit;
    }

    $discount = 0.0;
    if ($coupon['type'] === 'percentage') {
        $pct = max(0.0, min(100.0, (float)$coupon['value']));
        $discount = round(($subtotal * $pct) / 100, 2);
    } else {
        $discount = min($subtotal, (float)$coupon['value']);
    }
    $discount = min($discount, $subtotal);

    $_SESSION['applied_coupon'] = [
        'code' => $coupon['code'],
        'discount' => $discount,
        'type' => $coupon['type'],
        'value' => (float)$coupon['value'],
        'min_order_amount' => $minOrder
    ];

    echo json_encode([
        'success' => true,
        'message' => 'Coupon ' . htmlspecialchars($coupon['code']) . ' applied successfully!',
        'discount' => $discount,
        'formatted_discount' => format_price($discount),
        'new_subtotal' => format_price(max(0, $subtotal - $discount))
    ]);
} catch (\Throwable $e) {
    error_log("Coupon Validation Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Unable to validate coupon at this time. Please try again.']);
}
