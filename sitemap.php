<?php
/**
 * SSK Store - Dynamic XML Sitemap Generator
 * Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 *
 * Dynamically queries active products, departments, categories, and pages.
 * Supports web delivery (Content-Type: application/xml) and CLI dump.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

function build_sitemap_xml(): string {
    $baseUrl = rtrim(SITE_URL, '/');
    $today = date('Y-m-d');

    $urls = [];

    // 1. Core Storefront Pages
    $urls[] = [
        'loc' => $baseUrl . '/',
        'lastmod' => $today,
        'changefreq' => 'daily',
        'priority' => '1.0'
    ];
    $urls[] = [
        'loc' => $baseUrl . '/catalog.php',
        'lastmod' => $today,
        'changefreq' => 'daily',
        'priority' => '0.9'
    ];

    try {
        // 2. Departments
        $departments = db_fetch_all("SELECT id, created_at FROM departments WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
        foreach ($departments as $dept) {
            $lastmod = !empty($dept['created_at']) ? date('Y-m-d', strtotime($dept['created_at'])) : $today;
            $urls[] = [
                'loc' => "{$baseUrl}/catalog.php?dept={$dept['id']}",
                'lastmod' => $lastmod,
                'changefreq' => 'weekly',
                'priority' => '0.8'
            ];
        }

        // 3. Categories
        $categories = db_fetch_all("SELECT id, department_id, created_at FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
        foreach ($categories as $cat) {
            $lastmod = !empty($cat['created_at']) ? date('Y-m-d', strtotime($cat['created_at'])) : $today;
            $urls[] = [
                'loc' => "{$baseUrl}/catalog.php?cat={$cat['id']}",
                'lastmod' => $lastmod,
                'changefreq' => 'weekly',
                'priority' => '0.8'
            ];
        }

        // 4. Products
        $products = db_fetch_all("SELECT id, slug, updated_at, created_at FROM products WHERE is_active = 1 ORDER BY id ASC");
        foreach ($products as $prod) {
            $rawDate = !empty($prod['updated_at']) ? $prod['updated_at'] : (!empty($prod['created_at']) ? $prod['created_at'] : null);
            $lastmod = $rawDate ? date('Y-m-d', strtotime($rawDate)) : $today;
            $urls[] = [
                'loc' => "{$baseUrl}/product.php?id={$prod['id']}",
                'lastmod' => $lastmod,
                'changefreq' => 'weekly',
                'priority' => '0.7'
            ];
        }

        // 5. CMS Pages
        $pages = db_fetch_all("SELECT slug, updated_at, created_at FROM pages WHERE is_active = 1");
        foreach ($pages as $pg) {
            $rawDate = !empty($pg['updated_at']) ? $pg['updated_at'] : (!empty($pg['created_at']) ? $pg['created_at'] : null);
            $lastmod = $rawDate ? date('Y-m-d', strtotime($rawDate)) : $today;
            $urls[] = [
                'loc' => "{$baseUrl}/pages.php?slug=" . urlencode($pg['slug']),
                'lastmod' => $lastmod,
                'changefreq' => 'monthly',
                'priority' => '0.6'
            ];
        }
    } catch (\Throwable $e) {
        error_log("Sitemap generation error: " . $e->getMessage());
    }

    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

    foreach ($urls as $entry) {
        $xml .= "  <url>\n";
        $xml .= "    <loc>" . htmlspecialchars($entry['loc'], ENT_XML1, 'UTF-8') . "</loc>\n";
        $xml .= "    <lastmod>" . $entry['lastmod'] . "</lastmod>\n";
        $xml .= "    <changefreq>" . $entry['changefreq'] . "</changefreq>\n";
        $xml .= "    <priority>" . $entry['priority'] . "</priority>\n";
        $xml .= "  </url>\n";
    }

    $xml .= "</urlset>\n";
    return $xml;
}

// If invoked via CLI, output or dump to sitemap.xml
if (php_sapi_name() === 'cli') {
    $xml = build_sitemap_xml();
    $dest = ROOT_PATH . 'sitemap.xml';
    file_put_contents($dest, $xml);
    echo "Dynamic sitemap successfully written to {$dest} (" . strlen($xml) . " bytes)\n";
    exit(0);
}

// Web delivery
header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');
echo build_sitemap_xml();
