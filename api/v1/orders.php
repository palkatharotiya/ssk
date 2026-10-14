<?php
/**
 * SSK Store - REST API v1: Orders
 * POST /api/v1/orders.php — Create new order
 * GET  /api/v1/orders.php?track={order_number} — Track order
 * GET  /api/v1/orders.php?customer_id={id} — Customer's orders
 */

declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

/**
 * Helper to validate token from Authorization header
 */
function get_authenticated_customer(): ?array {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
        $token = $matches[1];
        $decoded = json_decode(base64_decode($token), true);
        if ($decoded && isset($decoded['customer_id']) && isset($decoded['expires_at'])) {
            if ($decoded['expires_at'] >= time()) {
                return db_fetch_one(
                    "SELECT id, first_name, last_name, email, phone FROM customers WHERE id = ? AND is_active = 1",
                    [$decoded['customer_id']]
                );
            }
        }
    }
    return null;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // TRACK ORDER
        $track = trim($_GET['track'] ?? '');
        $phone = trim($_GET['phone'] ?? '');

        if (!empty($track)) {
            // Track by order number
            $order = db_fetch_one(
                "SELECT o.id, o.order_number, o.status, o.payment_status, o.payment_method,
                        o.subtotal, o.shipping_charge, o.discount, o.total,
                        o.shipping_name, o.shipping_phone, o.shipping_city, o.shipping_state,
                        o.shipping_pincode, o.created_at, o.updated_at
                 FROM orders o
                 WHERE o.order_number = ?",
                [$track]
            );

            if (!$order) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Order not found. Please check the order number.']);
                exit;
            }

            // Fetch order items
            $items = db_fetch_all(
                "SELECT oi.product_name, oi.product_sku, oi.quantity, oi.price, oi.total,
                        p.image, p.slug
                 FROM order_items oi
                 LEFT JOIN products p ON oi.product_id = p.id
                 WHERE oi.order_id = ?",
                [$order['id']]
            );
            foreach ($items as &$item) {
                $item['image_url'] = get_product_image_url($item['image'] ?? '', $item['product_name']);
                $item['price'] = (float)$item['price'];
                $item['total'] = (float)$item['total'];
                $item['quantity'] = (int)$item['quantity'];
                unset($item['image']);
            }
            unset($item);

            // Build tracking timeline
            $statusSteps = [
                'pending' => ['label' => 'Order Placed', 'icon' => '📋'],
                'confirmed' => ['label' => 'Order Confirmed', 'icon' => '✅'],
                'processing' => ['label' => 'Being Prepared', 'icon' => '📦'],
                'shipped' => ['label' => 'Shipped', 'icon' => '🚚'],
                'delivered' => ['label' => 'Delivered', 'icon' => '🏠'],
            ];
            $currentStatus = strtolower($order['status']);
            $timeline = [];
            $reached = false;
            foreach ($statusSteps as $key => $step) {
                if ($key === $currentStatus) $reached = true;
                $timeline[] = [
                    'status' => $key,
                    'label' => $step['label'],
                    'icon' => $step['icon'],
                    'completed' => $reached ? false : true,
                    'current' => $key === $currentStatus,
                ];
            }
            // Fix: mark all steps up to (and including) current as completed
            $hitCurrent = false;
            foreach ($timeline as &$t) {
                if ($t['current']) {
                    $t['completed'] = true;
                    $hitCurrent = true;
                } elseif (!$hitCurrent) {
                    $t['completed'] = true;
                } else {
                    $t['completed'] = false;
                }
            }
            unset($t);

            $order['subtotal'] = (float)$order['subtotal'];
            $order['shipping_charge'] = (float)$order['shipping_charge'];
            $order['discount'] = (float)$order['discount'];
            $order['total'] = (float)$order['total'];

            echo json_encode([
                'success' => true,
                'data' => [
                    'order' => $order,
                    'items' => $items,
                    'timeline' => $timeline,
                ]
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        // LIST CUSTOMER ORDERS (requires auth)
        $customer = get_authenticated_customer();
        if (!$customer) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Please sign in to view your orders.']);
            exit;
        }

        $orders = db_fetch_all(
            "SELECT id, order_number, status, payment_status, payment_method, total, created_at
             FROM orders
             WHERE customer_id = ?
             ORDER BY id DESC
             LIMIT 50",
            [$customer['id']]
        );
        foreach ($orders as &$o) {
            $o['total'] = (float)$o['total'];
        }
        unset($o);

        echo json_encode([
            'success' => true,
            'data' => ['orders' => $orders]
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // POST — CREATE ORDER
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        exit;
    }

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    // Validate required fields
    $firstName = trim($data['first_name'] ?? '');
    $lastName = trim($data['last_name'] ?? '');
    $email = trim(strtolower($data['email'] ?? ''));
    $phone = trim($data['phone'] ?? '');
    $address = trim($data['address'] ?? '');
    $city = trim($data['city'] ?? '');
    $state = trim($data['state'] ?? 'Gujarat');
    $pincode = trim($data['pincode'] ?? '');
    $paymentMethod = strtoupper(trim($data['payment_method'] ?? 'UPI'));
    $notes = trim($data['notes'] ?? '');
    $items = $data['items'] ?? [];

    if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || empty($address) || empty($city) || empty($pincode)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required shipping details.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please provide a valid email address.']);
        exit;
    }

    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($cleanPhone) < 10) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid 10-digit mobile number.']);
        exit;
    }

    if (strlen(preg_replace('/[^0-9]/', '', $pincode)) !== 6) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid 6-digit PIN code.']);
        exit;
    }

    if (!in_array($paymentMethod, ['UPI', 'COD', 'ONLINE'], true)) {
        $paymentMethod = 'UPI';
    }

    if (empty($items) || !is_array($items)) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty. Please add items before ordering.']);
        exit;
    }

    // Process order in transaction
    $orderId = db_transaction(function (PDO $pdo) use ($firstName, $lastName, $email, $cleanPhone, $address, $city, $state, $pincode, $paymentMethod, $notes, $items) {
        // Ensure customer exists (create guest if needed)
        $customer = db_fetch_one("SELECT id FROM customers WHERE email = ?", [$email]);
        if (!$customer) {
            $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            db_query(
                "INSERT INTO customers (first_name, last_name, email, password, phone, is_active, email_verified, is_guest) VALUES (?, ?, ?, ?, ?, 1, 0, 1)",
                [$firstName, $lastName, $email, $hash, $cleanPhone]
            );
            $customerId = (int)db_last_insert_id();
        } else {
            $customerId = (int)$customer['id'];
        }

        // Validate and compute totals
        $subtotal = 0.0;
        $orderItems = [];

        foreach ($items as $cartItem) {
            $productId = (int)($cartItem['product_id'] ?? 0);
            $qty = max(1, (int)($cartItem['quantity'] ?? 1));

            $prod = db_fetch_one("SELECT id, name, sku, price, sale_price, stock_qty, is_active FROM products WHERE id = ?", [$productId]);
            if (!$prod || !$prod['is_active']) {
                throw new RuntimeException("Product not available.");
            }
            if ($prod['stock_qty'] < $qty) {
                throw new RuntimeException("Only {$prod['stock_qty']} units of '{$prod['name']}' available.");
            }

            $unitPrice = ($prod['sale_price'] !== null && (float)$prod['sale_price'] > 0 && (float)$prod['sale_price'] < (float)$prod['price'])
                ? (float)$prod['sale_price']
                : (float)$prod['price'];
            $lineTotal = $unitPrice * $qty;
            $subtotal += $lineTotal;

            $orderItems[] = [
                'product_id' => $productId,
                'product_name' => $prod['name'],
                'product_sku' => $prod['sku'] ?? '',
                'quantity' => $qty,
                'price' => $unitPrice,
                'total' => $lineTotal,
            ];

            // Deduct stock
            db_query("UPDATE products SET stock_qty = stock_qty - ? WHERE id = ?", [$qty, $productId]);
        }

        $shipping = calculate_shipping($subtotal, $state);
        $total = $subtotal + $shipping;

        // Generate unique order number
        $orderNumber = 'SSK-' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 8));

        // Insert order
        db_query(
            "INSERT INTO orders (customer_id, order_number, status, payment_status, payment_method,
                                 subtotal, shipping_charge, discount, total,
                                 shipping_name, shipping_phone, shipping_address, shipping_city,
                                 shipping_state, shipping_pincode, shipping_country, notes)
             VALUES (?, ?, 'pending', 'pending', ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, 'India', ?)",
            [
                $customerId, $orderNumber, $paymentMethod,
                $subtotal, $shipping, $total,
                $firstName . ' ' . $lastName, $cleanPhone, $address, $city, $state, $pincode, $notes
            ]
        );
        $newOrderId = (int)db_last_insert_id();

        // Insert order items
        foreach ($orderItems as $oi) {
            db_query(
                "INSERT INTO order_items (order_id, product_id, product_name, product_sku, quantity, price, total)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$newOrderId, $oi['product_id'], $oi['product_name'], $oi['product_sku'], $oi['quantity'], $oi['price'], $oi['total']]
            );
        }

        return [
            'order_id' => $newOrderId,
            'order_number' => $orderNumber,
            'total' => $total,
            'subtotal' => $subtotal,
            'shipping' => $shipping,
        ];
    });

    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully! Jai Swaminarayan 🙏',
        'data' => [
            'order_number' => $orderId['order_number'],
            'total' => $orderId['total'],
            'subtotal' => $orderId['subtotal'],
            'shipping_charge' => $orderId['shipping'],
            'payment_method' => $paymentMethod,
            'upi_id' => STORE_UPI_ID,
            'upi_name' => STORE_UPI_NAME,
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (RuntimeException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (\Throwable $e) {
    error_log('[SSK API v1/orders] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to process your order. Please try again or contact us on WhatsApp.'
    ]);
}
