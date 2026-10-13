<?php
/**
 * BAPS Store - Cart API
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$raw = file_get_contents('php://input');
$json = json_decode($raw, true);
$data = is_array($json) ? array_merge($_POST, $json) : $_POST;

$action = $data['action'] ?? $_GET['action'] ?? 'get';

try {
    switch ($action) {
        case 'add':
            $productId = (int)($data['product_id'] ?? 0);
            $qty = (int)($data['quantity'] ?? 1);
            if ($productId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid product.']);
                exit;
            }
            $result = add_to_cart($productId, $qty);
            echo json_encode($result);
            break;

        case 'update':
            $productId = (int)($data['product_id'] ?? 0);
            $qty = (int)($data['quantity'] ?? 1);
            if ($productId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid product.']);
                exit;
            }
            $result = update_cart_quantity($productId, $qty);
            echo json_encode($result);
            break;

        case 'remove':
            $productId = (int)($data['product_id'] ?? 0);
            if ($productId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid product.']);
                exit;
            }
            $result = remove_from_cart($productId);
            echo json_encode($result);
            break;

        case 'clear':
            clear_cart();
            echo json_encode(['success' => true, 'cart_count' => 0, 'subtotal' => format_price(0)]);
            break;

        case 'get':
        default:
            $items = get_cart_items();
            $formatted = array_map(function($item) {
                return [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'price' => (float)$item['unit_price'],
                    'formatted_price' => format_price($item['unit_price']),
                    'quantity' => (int)$item['quantity'],
                    'image_url' => get_product_image_url($item['image'], $item['name']),
                    'line_total' => format_price($item['unit_price'] * $item['quantity'])
                ];
            }, $items);

            echo json_encode([
                'success' => true,
                'items' => $formatted,
                'cart_count' => get_cart_count(),
                'subtotal' => get_cart_subtotal(),
                'formatted_subtotal' => format_price(get_cart_subtotal())
            ]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
