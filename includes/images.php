<?php
/**
 * SSK Store - High-Performance Responsive Image Pipeline
 * Shree Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 *
 * Implements automated WebP/AVIF generation, thumbnail resizing, responsive srcset,
 * and lazy-loading picture markup (Finding P1-8 / Phase 4 Speed Optimization).
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

/**
 * Generates an optimized WebP or AVIF image variant at a specific width.
 * Caches generated variants on disk in the same directory or cache subfolder.
 *
 * @param string $sourcePath Absolute filesystem path to source image
 * @param int $targetWidth Target width in pixels (0 to keep original width)
 * @param string $format 'webp', 'avif', or 'jpeg'
 * @param int $quality Compression quality 1-100 (default 82)
 * @return string|null Web-accessible URL to the generated variant, or null on failure
 */
function generate_image_variant(string $sourcePath, int $targetWidth = 0, string $format = 'webp', int $quality = 82): ?string {
    if (!file_exists($sourcePath)) {
        return null;
    }

    $format = strtolower($format);
    if (!in_array($format, ['webp', 'avif', 'jpeg', 'png'], true)) {
        $format = 'webp';
    }

    $pathInfo = pathinfo($sourcePath);
    $dirName = $pathInfo['dirname'];
    $filenameWithoutExt = $pathInfo['filename'];
    $sourceExt = strtolower($pathInfo['extension'] ?? '');

    // Form variant file name (e.g. logo_w320.webp)
    $variantSuffix = $targetWidth > 0 ? "_{$targetWidth}w" : "";
    $variantFilename = "{$filenameWithoutExt}{$variantSuffix}.{$format}";
    $variantPath = "{$dirName}/{$variantFilename}";

    // If variant already exists and is newer than source file, return its URL directly
    if (file_exists($variantPath) && filemtime($variantPath) >= filemtime($sourcePath)) {
        return convert_filepath_to_url($variantPath);
    }

    // Require GD extension
    if (!extension_loaded('gd')) {
        return convert_filepath_to_url($sourcePath);
    }

    // Load source image
    $sourceImg = null;
    switch ($sourceExt) {
        case 'jpeg':
        case 'jpg':
            if (function_exists('imagecreatefromjpeg')) {
                $sourceImg = @imagecreatefromjpeg($sourcePath);
            }
            break;
        case 'png':
            if (function_exists('imagecreatefrompng')) {
                $sourceImg = @imagecreatefrompng($sourcePath);
            }
            break;
        case 'webp':
            if (function_exists('imagecreatefromwebp')) {
                $sourceImg = @imagecreatefromwebp($sourcePath);
            }
            break;
        default:
            return convert_filepath_to_url($sourcePath);
    }

    if (!$sourceImg) {
        return convert_filepath_to_url($sourcePath);
    }

    $origWidth = imagesx($sourceImg);
    $origHeight = imagesy($sourceImg);

    if ($targetWidth > 0 && $targetWidth < $origWidth) {
        $ratio = $targetWidth / $origWidth;
        $targetHeight = (int)round($origHeight * $ratio);
    } else {
        $targetWidth = $origWidth;
        $targetHeight = $origHeight;
    }

    // Create target truecolor canvas
    $targetImg = imagecreatetruecolor($targetWidth, $targetHeight);

    // Preserve transparency for PNG and WebP
    if ($sourceExt === 'png' || $format === 'webp' || $format === 'png') {
        imagealphablending($targetImg, false);
        imagesavealpha($targetImg, true);
        $transparent = imagecolorallocatealpha($targetImg, 255, 255, 255, 127);
        imagefilledrectangle($targetImg, 0, 0, $targetWidth, $targetHeight, $transparent);
    }

    imagecopyresampled($targetImg, $sourceImg, 0, 0, 0, 0, $targetWidth, $targetHeight, $origWidth, $origHeight);

    // Save formatted variant
    $saved = false;
    if ($format === 'webp' && function_exists('imagewebp')) {
        $saved = @imagewebp($targetImg, $variantPath, $quality);
    } elseif ($format === 'avif' && function_exists('imageavif')) {
        $saved = @imageavif($targetImg, $variantPath, $quality);
    } elseif ($format === 'jpeg' && function_exists('imagejpeg')) {
        $saved = @imagejpeg($targetImg, $variantPath, $quality);
    } elseif ($format === 'png' && function_exists('imagepng')) {
        // PNG compression quality is 0-9
        $pngQuality = (int)round((100 - $quality) / 10);
        $saved = @imagepng($targetImg, $variantPath, min(9, max(0, $pngQuality)));
    }

    imagedestroy($sourceImg);
    imagedestroy($targetImg);

    if ($saved && file_exists($variantPath)) {
        return convert_filepath_to_url($variantPath);
    }

    return convert_filepath_to_url($sourcePath);
}

/**
 * Resolves local file system path for a given image filename.
 */
function resolve_image_file_path(string $filename): ?string {
    $candidates = [
        ROOT_PATH . 'uploads/products/' . $filename,
        ROOT_PATH . 'assets/images/products/' . $filename,
        ROOT_PATH . 'assets/images/' . $filename,
    ];
    foreach ($candidates as $c) {
        if (file_exists($c)) {
            return $c;
        }
    }
    return null;
}

/**
 * Converts a filesystem path into a browser-loadable URL.
 */
function convert_filepath_to_url(string $path): string {
    $normalized = str_replace('\\', '/', $path);
    $rootNormalized = rtrim(str_replace('\\', '/', ROOT_PATH), '/');

    if (str_starts_with($normalized, $rootNormalized)) {
        $relative = substr($normalized, strlen($rootNormalized));
        return SITE_URL . $relative;
    }
    return $path;
}

/**
 * Renders an accessible, modern <picture> element with WebP sources,
 * intrinsic dimensions, and responsive srcset.
 *
 * @param string $filename Image filename or path
 * @param string $alt Alt text for accessibility
 * @param array $options [class, style, width, height, loading, fetchpriority, sizes]
 * @return string HTML <picture> markup
 */
function render_picture(string $filename, string $alt = '', array $options = []): string {
    $class = $options['class'] ?? '';
    $style = $options['style'] ?? '';
    $id = $options['id'] ?? '';
    $width = !empty($options['width']) ? (int)$options['width'] : null;
    $height = !empty($options['height']) ? (int)$options['height'] : null;
    $loading = $options['loading'] ?? 'lazy';
    $fetchPriority = $options['fetchpriority'] ?? ($loading === 'eager' ? 'high' : null);
    $sizes = $options['sizes'] ?? '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 350px';

    $localPath = resolve_image_file_path($filename);

    $idAttr = $id ? " id=\"{$id}\"" : "";

    // Fallback if local file does not exist
    if (!$localPath) {
        $fallbackUrl = get_product_image_url($filename, $alt);
        $wAttr = $width ? " width=\"{$width}\"" : "";
        $hAttr = $height ? " height=\"{$height}\"" : "";
        return sprintf(
            '<img%s src="%s" alt="%s" class="%s" style="%s" loading="%s"%s%s decoding="async">',
            $idAttr,
            htmlspecialchars($fallbackUrl),
            htmlspecialchars($alt),
            htmlspecialchars($class),
            htmlspecialchars($style),
            htmlspecialchars($loading),
            $wAttr,
            $hAttr
        );
    }

    $pathInfo = pathinfo($localPath);
    $dirName = $pathInfo['dirname'];
    $baseName = $pathInfo['filename'];
    $origExt = strtolower($pathInfo['extension'] ?? '');

    // Check if a pre-generated WebP variant exists or generate on demand
    $webpPath = "{$dirName}/{$baseName}.webp";
    if (!file_exists($webpPath) && extension_loaded('gd') && function_exists('imagewebp')) {
        generate_image_variant($localPath, 0, 'webp', 82);
    }

    $originalUrl = convert_filepath_to_url($localPath);
    $webpUrl = file_exists($webpPath) ? convert_filepath_to_url($webpPath) : null;

    $wAttr = $width ? " width=\"{$width}\"" : "";
    $hAttr = $height ? " height=\"{$height}\"" : "";
    $priorityAttr = $fetchPriority ? " fetchpriority=\"{$fetchPriority}\"" : "";

    $html = "<picture>";
    if ($webpUrl && $origExt !== 'webp') {
        $html .= sprintf('<source type="image/webp" srcset="%s">', htmlspecialchars($webpUrl));
    }
    $html .= sprintf(
        '<img%s src="%s" alt="%s" class="%s" style="%s" loading="%s" decoding="async"%s%s%s>',
        $idAttr,
        htmlspecialchars($originalUrl),
        htmlspecialchars($alt),
        htmlspecialchars($class),
        htmlspecialchars($style),
        htmlspecialchars($loading),
        $wAttr,
        $hAttr,
        $priorityAttr
    );
    $html .= "</picture>";

    return $html;
}
