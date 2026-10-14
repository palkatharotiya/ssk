<?php
/**
 * SSK Store - Development Router for PHP Built-in Web Server
 * Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $uri;

// 1. Directory index handling (e.g. /admin/ -> /admin/index.php)
if (is_dir($file)) {
    $indexCandidate = rtrim($file, '/\\') . '/index.php';
    if (file_exists($indexCandidate)) {
        require $indexCandidate;
        return true;
    }
}

// 2. Serve static files directly
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    // Execute PHP files directly
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        require $file;
        return true;
    }
    // Set appropriate MIME types for webmanifest and webp if needed
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($ext === 'webmanifest') {
        header('Content-Type: application/manifest+json');
        readfile($file);
        return true;
    }
    return false;
}

// 2. Dynamic XML Sitemap
if ($uri === '/sitemap.xml') {
    require __DIR__ . '/sitemap.php';
    return true;
}

// 3. Clean Product URLs
if (preg_match('#^/product/([a-zA-Z0-9_-]+)/?$#', $uri, $m)) {
    $_GET['slug'] = $m[1];
    require __DIR__ . '/product.php';
    return true;
}

// 4. Clean Page URLs
if (preg_match('#^/page/([a-zA-Z0-9_-]+)/?$#', $uri, $m)) {
    $_GET['slug'] = $m[1];
    require __DIR__ . '/pages.php';
    return true;
}

// 5. Root & Fallback to index.php
if ($uri === '/' || !file_exists($file)) {
    require __DIR__ . '/index.php';
    return true;
}

return false;
