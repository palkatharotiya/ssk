<?php
/**
 * SSK Store - Automated Image Optimization CLI Tool
 * Shree Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 *
 * Compresses oversized logos, hero sliders, banners, and product photos
 * into optimized WebP and high-efficiency PNG/JPEG assets.
 * Usage: php tools/optimize-images.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/images.php';

echo "====================================================\n";
echo " SSK Store — Mobile 4G Image Optimization Pipeline \n";
echo "====================================================\n\n";

if (!extension_loaded('gd') || !function_exists('imagewebp')) {
    echo "ERROR: PHP GD extension with WebP support is required to run image optimization.\n";
    exit(1);
}

$dirs = [
    ROOT_PATH . 'assets/images/',
    ROOT_PATH . 'assets/images/products/',
    ROOT_PATH . 'uploads/products/'
];

$totalOriginalSize = 0;
$totalOptimizedSize = 0;
$processedCount = 0;

// 1. Optimize Logo specifically (Resize 2000px wide 1.16 MB down to crisp 400px wide transparent WebP + PNG)
$logoFiles = [
    ROOT_PATH . 'assets/brand/logo.png',
    ROOT_PATH . 'assets/brand/logo-light.png',
    ROOT_PATH . 'assets/images/logo.png',
    ROOT_PATH . 'assets/images/logo-light.png',
    ROOT_PATH . 'assets/images/logo_original_transparent.png',
    ROOT_PATH . 'assets/images/ssk-logo.png'
];

foreach ($logoFiles as $logoPath) {
    if (!file_exists($logoPath)) continue;
    $origSize = filesize($logoPath);
    $totalOriginalSize += $origSize;

    // Load original PNG
    $srcImg = @imagecreatefrompng($logoPath);
    if ($srcImg) {
        $w = imagesx($srcImg);
        $h = imagesy($srcImg);
        // Desired display width: max 380px for 2x retina display (navbar height is 40px)
        $targetW = min(380, $w);
        $targetH = (int)round($h * ($targetW / $w));

        $dstImg = imagecreatetruecolor($targetW, $targetH);
        imagealphablending($dstImg, false);
        imagesavealpha($dstImg, true);
        $transparent = imagecolorallocatealpha($dstImg, 255, 255, 255, 127);
        imagefilledrectangle($dstImg, 0, 0, $targetW, $targetH, $transparent);

        imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $targetW, $targetH, $w, $h);

        // Generate optimized WebP
        $webpPath = preg_replace('/\.png$/i', '.webp', $logoPath);
        @imagewebp($dstImg, $webpPath, 85);

        // Overwrite logo.png with optimized, resized version (level 9 compression)
        @imagepng($dstImg, $logoPath, 9);

        imagedestroy($srcImg);
        imagedestroy($dstImg);

        clearstatcache(true, $logoPath);
        $newSize = filesize($logoPath);
        $webpSize = file_exists($webpPath) ? filesize($webpPath) : 0;
        $totalOptimizedSize += $newSize;

        $reduction = round((1 - ($newSize / $origSize)) * 100, 1);
        echo " [LOGO] " . basename($logoPath) . ": " . round($origSize / 1024, 1) . " KB -> " . round($newSize / 1024, 1) . " KB (-{$reduction}%) | WebP: " . round($webpSize / 1024, 1) . " KB\n";
        $processedCount++;
    }
}

// 2. Optimize Hero Sliders & Banners
$bannerFiles = glob(ROOT_PATH . 'assets/images/*.{jpg,jpeg,png}', GLOB_BRACE);
foreach ($bannerFiles as $bannerPath) {
    $base = basename($bannerPath);
    if (in_array($bannerPath, $logoFiles, true)) continue;
    if (str_contains($base, 'favicon') || str_contains($base, 'logo-icon')) continue;

    $origSize = filesize($bannerPath);
    $totalOriginalSize += $origSize;

    // Generate full-size WebP
    generate_image_variant($bannerPath, 0, 'webp', 82);
    // Generate mobile 4G optimized 640w WebP
    generate_image_variant($bannerPath, 640, 'webp', 80);

    $webpPath = preg_replace('/\.(jpe?g|png)$/i', '.webp', $bannerPath);
    $webpSize = file_exists($webpPath) ? filesize($webpPath) : $origSize;
    $totalOptimizedSize += $webpSize;

    $reduction = round((1 - ($webpSize / $origSize)) * 100, 1);
    echo " [BANNER] {$base}: " . round($origSize / 1024, 1) . " KB -> WebP: " . round($webpSize / 1024, 1) . " KB (-{$reduction}%)\n";
    $processedCount++;
}

// 3. Optimize Product Images
$prodFiles = array_merge(
    glob(ROOT_PATH . 'assets/images/products/*.{jpg,jpeg,png}', GLOB_BRACE) ?: [],
    glob(ROOT_PATH . 'uploads/products/*.{jpg,jpeg,png}', GLOB_BRACE) ?: []
);

foreach ($prodFiles as $prodPath) {
    $base = basename($prodPath);
    if (str_contains($base, '_w')) continue;

    $origSize = filesize($prodPath);
    $totalOriginalSize += $origSize;

    // Generate WebP
    generate_image_variant($prodPath, 0, 'webp', 82);
    // Generate mobile thumbnail (350px)
    generate_image_variant($prodPath, 350, 'webp', 80);

    $webpPath = preg_replace('/\.(jpe?g|png)$/i', '.webp', $prodPath);
    $webpSize = file_exists($webpPath) ? filesize($webpPath) : $origSize;
    $totalOptimizedSize += $webpSize;

    $reduction = round((1 - ($webpSize / $origSize)) * 100, 1);
    echo " [PRODUCT] {$base}: " . round($origSize / 1024, 1) . " KB -> WebP: " . round($webpSize / 1024, 1) . " KB (-{$reduction}%)\n";
    $processedCount++;
}

echo "\n----------------------------------------------------\n";
echo " Optimization Summary:\n";
echo " Processed files: {$processedCount}\n";
echo " Original total: " . round($totalOriginalSize / (1024 * 1024), 2) . " MB\n";
echo " Optimized total: " . round($totalOptimizedSize / (1024 * 1024), 2) . " MB\n";
$overallReduction = $totalOriginalSize > 0 ? round((1 - ($totalOptimizedSize / $totalOriginalSize)) * 100, 1) : 0;
echo " Net Bandwidth Saved: {$overallReduction}%\n";
echo "====================================================\n";
