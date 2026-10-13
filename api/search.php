<?php
/**
 * BAPS Store - Live Search Autocomplete API
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$query = trim($_GET['q'] ?? '');

if (mb_strlen($query) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

try {
    $searchTerm = '%' . $query . '%';
    $sql = "SELECT id, name, slug, price, sale_price, image 
            FROM products 
            WHERE is_active = 1 AND (name LIKE ? OR sku LIKE ? OR short_description LIKE ?)
            ORDER BY is_featured DESC, name ASC 
            LIMIT 6";

    $products = db_fetch_all($sql, [$searchTerm, $searchTerm, $searchTerm]);

    $results = array_map(function($p) {
        $price = ($p['sale_price'] !== null && $p['sale_price'] > 0) ? $p['sale_price'] : $p['price'];
        return [
            'id' => (int)$p['id'],
            'name' => $p['name'],
            'slug' => $p['slug'],
            'formatted_price' => format_price($price),
            'image_url' => get_product_image_url($p['image'], $p['name'])
        ];
    }, $products);

    echo json_encode(['results' => $results]);
} catch (Exception $e) {
    echo json_encode(['results' => [], 'error' => $e->getMessage()]);
}
