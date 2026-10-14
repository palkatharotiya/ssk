<?php
/**
 * SSK Store - REST API v1: Home Screen Data
 * GET /api/v1/home.php
 * Returns: banners, departments/categories, featured products, on-sale items
 */

declare(strict_types=1);

// Prevent any HTML output — pure JSON only
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
    // 1. Active hero banners ordered by display_order
    $banners = db_fetch_all(
        "SELECT id, title, subtitle, image, link, sort_order, position
         FROM banners
         WHERE is_active = 1
         ORDER BY sort_order ASC, id ASC"
    );
    // Resolve image URLs for banners
    foreach ($banners as &$b) {
        $b['image_url'] = get_product_image_url($b['image'], $b['title'] ?? 'SSK Banner');
    }
    unset($b);

    // 2. All active departments with categories
    $departments = db_fetch_all(
        "SELECT id, name, slug, description, image, sort_order
         FROM departments
         WHERE is_active = 1
         ORDER BY sort_order ASC, name ASC"
    );
    foreach ($departments as &$dept) {
        $cats = db_fetch_all(
            "SELECT id, name, slug, department_id
             FROM categories
             WHERE department_id = ? AND is_active = 1
             ORDER BY sort_order ASC, name ASC",
            [$dept['id']]
        );
        $dept['categories'] = $cats;
    }
    unset($dept);

    // 3. Featured products (limit 8)
    $featured = db_fetch_all(
        "SELECT p.id, p.name, p.slug, p.short_description, p.price, p.sale_price,
                p.stock_qty, p.image, p.is_featured, p.on_sale,
                d.name as department_name, d.slug as department_slug
         FROM products p
         JOIN departments d ON p.department_id = d.id
         WHERE p.is_active = 1 AND p.is_featured = 1
         ORDER BY p.id DESC
         LIMIT 8"
    );
    foreach ($featured as &$fp) {
        $fp['image_url'] = get_product_image_url($fp['image'], $fp['name']);
        $fp['price'] = (float)$fp['price'];
        $fp['sale_price'] = $fp['sale_price'] !== null ? (float)$fp['sale_price'] : null;
        $fp['stock_qty'] = (int)$fp['stock_qty'];
        $fp['has_discount'] = $fp['sale_price'] !== null && $fp['sale_price'] > 0 && $fp['sale_price'] < $fp['price'];
        if ($fp['has_discount']) {
            $fp['discount_percent'] = round((($fp['price'] - $fp['sale_price']) / $fp['price']) * 100);
        } else {
            $fp['discount_percent'] = 0;
        }
    }
    unset($fp);

    // 4. On-sale products (limit 8)
    $onSale = db_fetch_all(
        "SELECT p.id, p.name, p.slug, p.short_description, p.price, p.sale_price,
                p.stock_qty, p.image, p.is_featured, p.on_sale,
                d.name as department_name, d.slug as department_slug
         FROM products p
         JOIN departments d ON p.department_id = d.id
         WHERE p.is_active = 1 AND p.on_sale = 1
         ORDER BY (p.price - COALESCE(p.sale_price, p.price)) DESC, p.id DESC
         LIMIT 8"
    );
    foreach ($onSale as &$sp) {
        $sp['image_url'] = get_product_image_url($sp['image'], $sp['name']);
        $sp['price'] = (float)$sp['price'];
        $sp['sale_price'] = $sp['sale_price'] !== null ? (float)$sp['sale_price'] : null;
        $sp['stock_qty'] = (int)$sp['stock_qty'];
        $sp['has_discount'] = $sp['sale_price'] !== null && $sp['sale_price'] > 0 && $sp['sale_price'] < $sp['price'];
        $sp['discount_percent'] = $sp['has_discount']
            ? round((($sp['price'] - $sp['sale_price']) / $sp['price']) * 100)
            : 0;
    }
    unset($sp);

    // 5. Homepage sections (admin-managed display order)
    $sections = [];
    try {
        $sectionRows = db_fetch_all(
            "SELECT section_key, section_title, items_limit, sort_order
             FROM homepage_sections
             WHERE is_active = 1
             ORDER BY sort_order ASC, id ASC"
        );
        $sections = $sectionRows;
    } catch (\Throwable $e) {
        // homepage_sections table may not exist yet
        $sections = [];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'banners' => $banners,
            'departments' => $departments,
            'featured_products' => $featured,
            'on_sale_products' => $onSale,
            'homepage_sections' => $sections,
            'store' => [
                'name' => 'Shri Sahajanand Sahitya Kendra',
                'tagline' => 'Kalakunj Mandir, Surat',
                'currency_symbol' => '₹',
                'currency_code' => 'INR',
                'whatsapp_url' => 'https://wa.me/919426551098',
            ]
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (\Throwable $e) {
    error_log('[SSK API v1/home] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to load home data. Please try again later.'
    ]);
}
