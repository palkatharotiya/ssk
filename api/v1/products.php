<?php
/**
 * SSK Store - REST API v1: Products Catalog
 * GET /api/v1/products.php
 * Params: dept, category, search, on_sale, sort, page, limit
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
    $dept = isset($_GET['dept']) ? (int)$_GET['dept'] : null;
    $category = isset($_GET['category']) ? (int)$_GET['category'] : null;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $onSale = isset($_GET['on_sale']) && $_GET['on_sale'] === '1';
    $sort = $_GET['sort'] ?? 'popular';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    $where = ['p.is_active = 1'];
    $params = [];

    if ($dept !== null && $dept > 0) {
        $where[] = 'p.department_id = ?';
        $params[] = $dept;
    }

    if ($category !== null && $category > 0) {
        $where[] = 'p.category_id = ?';
        $params[] = $category;
    }

    if (!empty($search)) {
        $where[] = '(p.name LIKE ? OR p.short_description LIKE ? OR d.name LIKE ?)';
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    if ($onSale) {
        $where[] = 'p.on_sale = 1';
    }

    $whereClause = implode(' AND ', $where);

    // Sort
    $orderBy = match ($sort) {
        'price_low' => 'CASE WHEN p.sale_price IS NOT NULL AND p.sale_price > 0 THEN p.sale_price ELSE p.price END ASC',
        'price_high' => 'CASE WHEN p.sale_price IS NOT NULL AND p.sale_price > 0 THEN p.sale_price ELSE p.price END DESC',
        'newest' => 'p.id DESC',
        'name_asc' => 'p.name ASC',
        'name_desc' => 'p.name DESC',
        default => 'p.is_featured DESC, p.id DESC', // popular
    };

    // Count
    $countRow = db_fetch_one(
        "SELECT COUNT(*) as total
         FROM products p
         JOIN departments d ON p.department_id = d.id
         WHERE $whereClause",
        $params
    );
    $total = (int)($countRow['total'] ?? 0);

    // Fetch products
    $products = db_fetch_all(
        "SELECT p.id, p.name, p.slug, p.short_description, p.price, p.sale_price,
                p.stock_qty, p.image, p.is_featured, p.on_sale,
                p.department_id, p.category_id,
                d.name as department_name, d.slug as department_slug,
                c.name as category_name
         FROM products p
         JOIN departments d ON p.department_id = d.id
         LEFT JOIN categories c ON p.category_id = c.id
         WHERE $whereClause
         ORDER BY $orderBy
         LIMIT $limit OFFSET $offset",
        $params
    );

    foreach ($products as &$p) {
        $p['image_url'] = get_product_image_url($p['image'], $p['name']);
        $p['price'] = (float)$p['price'];
        $p['sale_price'] = $p['sale_price'] !== null ? (float)$p['sale_price'] : null;
        $p['stock_qty'] = (int)$p['stock_qty'];
        $p['has_discount'] = $p['sale_price'] !== null && $p['sale_price'] > 0 && $p['sale_price'] < $p['price'];
        $p['discount_percent'] = $p['has_discount']
            ? round((($p['price'] - $p['sale_price']) / $p['price']) * 100)
            : 0;
    }
    unset($p);

    echo json_encode([
        'success' => true,
        'data' => [
            'products' => $products,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => (int)ceil($total / $limit),
            ],
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (\Throwable $e) {
    error_log('[SSK API v1/products] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to load products. Please try again later.'
    ]);
}
