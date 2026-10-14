<?php
/**
 * SSK Store - REST API v1: Product Detail
 * GET /api/v1/product.php?id={id}
 * Returns: full product details, images, pricing, stock, related items
 */

declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Product ID is required.']);
        exit;
    }

    $product = db_fetch_one(
        "SELECT p.*, d.name as department_name, d.slug as department_slug,
                c.name as category_name, c.slug as category_slug
         FROM products p
         JOIN departments d ON p.department_id = d.id
         LEFT JOIN categories c ON p.category_id = c.id
         WHERE p.id = ? AND p.is_active = 1",
        [$id]
    );

    if (!$product) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found.']);
        exit;
    }

    // Process product data
    $product['image_url'] = get_product_image_url($product['image'], $product['name']);
    $product['price'] = (float)$product['price'];
    $product['sale_price'] = $product['sale_price'] !== null ? (float)$product['sale_price'] : null;
    $product['cost_price'] = null; // Never expose cost to client
    $product['stock_qty'] = (int)$product['stock_qty'];
    $product['min_order_qty'] = (int)($product['min_order_qty'] ?? 1);
    $product['views'] = (int)($product['views'] ?? 0);
    $product['has_discount'] = $product['sale_price'] !== null && $product['sale_price'] > 0 && $product['sale_price'] < $product['price'];
    $product['discount_percent'] = $product['has_discount']
        ? round((($product['price'] - $product['sale_price']) / $product['price']) * 100)
        : 0;

    // Gallery images
    $galleryImages = [];
    $galleryImages[] = $product['image_url']; // Primary image
    if (!empty($product['gallery'])) {
        $galleryEntries = json_decode($product['gallery'], true);
        if (is_array($galleryEntries)) {
            foreach ($galleryEntries as $g) {
                $galleryImages[] = get_product_image_url($g, $product['name']);
            }
        }
    }
    $product['gallery_images'] = $galleryImages;

    // Remove sensitive/internal fields
    unset($product['cost_price'], $product['gallery']);

    // Increment view count
    try {
        db_query("UPDATE products SET views = views + 1 WHERE id = ?", [$id]);
    } catch (\Throwable $e) {
        // non-critical
    }

    // Related products (same department, excluding current)
    $related = db_fetch_all(
        "SELECT p.id, p.name, p.slug, p.short_description, p.price, p.sale_price,
                p.stock_qty, p.image, p.is_featured, p.on_sale,
                d.name as department_name
         FROM products p
         JOIN departments d ON p.department_id = d.id
         WHERE p.is_active = 1 AND p.department_id = ? AND p.id != ?
         ORDER BY p.is_featured DESC, RANDOM()
         LIMIT 6",
        [$product['department_id'], $id]
    );

    foreach ($related as &$rp) {
        $rp['image_url'] = get_product_image_url($rp['image'], $rp['name']);
        $rp['price'] = (float)$rp['price'];
        $rp['sale_price'] = $rp['sale_price'] !== null ? (float)$rp['sale_price'] : null;
        $rp['stock_qty'] = (int)$rp['stock_qty'];
        $rp['has_discount'] = $rp['sale_price'] !== null && $rp['sale_price'] > 0 && $rp['sale_price'] < $rp['price'];
        $rp['discount_percent'] = $rp['has_discount']
            ? round((($rp['price'] - $rp['sale_price']) / $rp['price']) * 100)
            : 0;
    }
    unset($rp);

    echo json_encode([
        'success' => true,
        'data' => [
            'product' => $product,
            'related_products' => $related,
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (\Throwable $e) {
    error_log('[SSK API v1/product] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to load product details. Please try again later.'
    ]);
}
