<?php
/**
 * SSK Store - REST API v1: Store Info & Policy Pages
 * GET /api/v1/store_info.php
 * Returns: official store details + 5 policy/content pages
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
    // Fetch CMS pages
    $pages = db_fetch_all("SELECT id, title, slug, content FROM pages WHERE is_active = 1 ORDER BY id ASC");

    echo json_encode([
        'success' => true,
        'data' => [
            'store' => [
                'name' => 'Shri Sahajanand Sahitya Kendra',
                'full_name' => 'Shri Sahajanand Sahitya Kendra (Kalakunj Mandir)',
                'tagline' => 'Kalakunj Mandir, Surat',
                'address' => 'Shree Swaminarayan Mandir, Kalakunj, Varachha Main Rd, opp. Water Tank, Kalakunj Society, Swaminarayan Nagar-2, Kapodra Patiya, Surat, Gujarat 395006, India',
                'phone' => '+91 9426-55-1098',
                'whatsapp' => '+91 9426-55-1098',
                'whatsapp_url' => 'https://wa.me/919426551098',
                'email' => 'contact@sskstore.in',
                'instagram' => '@s_s_sahitya_kendra',
                'instagram_url' => 'https://www.instagram.com/s_s_sahitya_kendra/',
                'hours_morning' => '8:00 AM – 12:00 PM IST',
                'hours_evening' => '4:00 PM – 8:00 PM IST',
                'hours_days' => 'Daily, Mon – Sun',
                'upi_id' => STORE_UPI_ID,
                'upi_name' => STORE_UPI_NAME,
                'currency_symbol' => '₹',
                'currency_code' => 'INR',
                'shipping_info' => [
                    'gujarat_free_above' => 500,
                    'metro_free_above' => 750,
                    'india_free_above' => 750,
                    'gujarat_flat_rate' => 50,
                    'india_flat_rate' => 80,
                ],
                'google_maps_url' => 'https://maps.google.com/?q=Shree+Swaminarayan+Mandir+Kalakunj+Surat',
            ],
            'pages' => $pages,
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (\Throwable $e) {
    error_log('[SSK API v1/store_info] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to load store information. Please try again later.'
    ]);
}
