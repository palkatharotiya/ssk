<?php
/**
 * Dynamic SVG Placeholder Generator for BAPS Store
 * Renders beautiful themed SVG graphic for products, banners, and categories
 */

header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: public, max-age=86400');

$title = isset($_GET['title']) ? trim($_GET['title']) : 'SSK.STORE';
$w = isset($_GET['w']) ? (int)$_GET['w'] : 500;
$h = isset($_GET['h']) ? (int)$_GET['h'] : 500;
$type = isset($_GET['type']) ? $_GET['type'] : 'product';

// Pick pleasant harmonic color scheme based on title hash
$hash = crc32($title);
$themes = [
    ['from' => '#991b1b', 'to' => '#7f1d1d', 'accent' => '#fbbf24', 'badge' => 'SSK PUBLICATION'], // Crimson
    ['from' => '#c2410c', 'to' => '#9a3412', 'accent' => '#fef08a', 'badge' => 'SANCTIFIED'],       // Saffron
    ['from' => '#854d0e', 'to' => '#713f12', 'accent' => '#fde047', 'badge' => 'DEVOTIONAL'],       // Gold/Ochre
    ['from' => '#431407', 'to' => '#290e05', 'accent' => '#f59e0b', 'badge' => 'AUTHENTIC'],         // Teak
    ['from' => '#b45309', 'to' => '#78350f', 'accent' => '#fde68a', 'badge' => 'KALAKUNJ MANDIR']    // Amber
];
$theme = $themes[abs($hash) % count($themes)];

$titleSafe = htmlspecialchars(mb_substr($title, 0, 40));
$subSafe = htmlspecialchars($theme['badge']);
?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 <?= $w ?> <?= $h ?>" width="<?= $w ?>" height="<?= $h ?>">
  <defs>
    <linearGradient id="bgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="<?= $theme['from'] ?>" />
      <stop offset="100%" stop-color="<?= $theme['to'] ?>" />
    </linearGradient>
    <radialGradient id="glow" cx="50%" cy="40%" r="50%">
      <stop offset="0%" stop-color="<?= $theme['accent'] ?>" stop-opacity="0.3" />
      <stop offset="100%" stop-color="<?= $theme['accent'] ?>" stop-opacity="0" />
    </radialGradient>
    <filter id="shadow" x="-10%" y="-10%" width="120%" height="120%">
      <feDropShadow dx="0" dy="8" stdDeviation="12" flood-color="#000000" flood-opacity="0.35"/>
    </filter>
  </defs>

  <!-- Background -->
  <rect width="100%" height="100%" fill="url(#bgGrad)" />
  <circle cx="<?= $w/2 ?>" cy="<?= $h*0.42 ?>" r="<?= min($w,$h)*0.38 ?>" fill="url(#glow)" />

  <!-- Subtle ornamental border -->
  <rect x="16" y="16" width="<?= $w-32 ?>" height="<?= $h-32 ?>" fill="none" stroke="<?= $theme['accent'] ?>" stroke-width="1.5" stroke-opacity="0.3" rx="8" />
  <rect x="22" y="22" width="<?= $w-44 ?>" height="<?= $h-44 ?>" fill="none" stroke="<?= $theme['accent'] ?>" stroke-width="0.75" stroke-opacity="0.15" rx="6" />

  <!-- Center Decorative Icon: Sacred Lotus / Temple Emblem -->
  <g transform="translate(<?= $w/2 ?>, <?= $h*0.38 ?>)" filter="url(#shadow)">
    <!-- Aura disc -->
    <circle cx="0" cy="0" r="54" fill="#ffffff" fill-opacity="0.12" stroke="<?= $theme['accent'] ?>" stroke-width="2" />
    <circle cx="0" cy="0" r="46" fill="#000000" fill-opacity="0.18" />
    
    <!-- Stylized Tilak / Flame / Lotus -->
    <path d="M 0 -30 C 14 -12 18 10 0 26 C -18 10 -14 -12 0 -30 Z" fill="<?= $theme['accent'] ?>" />
    <circle cx="0" cy="10" r="4.5" fill="#ffffff" />
    <path d="M -16 6 C -28 16 -12 28 0 28 C 12 28 28 16 16 6 C 10 16 -10 16 -16 6 Z" fill="<?= $theme['accent'] ?>" fill-opacity="0.7" />
  </g>

  <!-- Badge Pill -->
  <g transform="translate(<?= $w/2 ?>, <?= $h*0.62 ?>)">
    <rect x="-70" y="-12" width="140" height="24" rx="12" fill="<?= $theme['accent'] ?>" fill-opacity="0.2" stroke="<?= $theme['accent'] ?>" stroke-width="1" />
    <text x="0" y="4" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="10" font-weight="700" letter-spacing="1.5" fill="<?= $theme['accent'] ?>" text-anchor="middle">
      <?= $subSafe ?>
    </text>
  </g>

  <!-- Title Text -->
  <text x="<?= $w/2 ?>" y="<?= $h*0.75 ?>" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="17" font-weight="600" fill="#ffffff" text-anchor="middle" letter-spacing="0.3">
    <?= $titleSafe ?>
  </text>

  <!-- Store Tag -->
  <text x="<?= $w/2 ?>" y="<?= $h*0.84 ?>" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="12" font-weight="500" fill="#ffffff" fill-opacity="0.6" text-anchor="middle" letter-spacing="0.8">
    SSK.STORE • KALAKUNJ MANDIR SURAT
  </text>
</svg>
