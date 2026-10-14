<?php
/**
 * SSK Store - Main Header Template
 * Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$cartCount = get_cart_count();
$wishlistCount = get_wishlist_count();
$navDepartments = get_navigation_departments();
$isLoggedIn = is_logged_in();
$currentUser = current_user();
$pageTitle = $pageTitle ?? (SITE_NAME . ' — ' . SITE_TAGLINE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title><?= htmlspecialchars($pageTitle) ?></title>
<?php
$scriptName = basename($_SERVER['PHP_SELF'] ?? '');
$isNoIndex = in_array($scriptName, ['cart.php', 'checkout.php', 'account.php', 'order-success.php', 'login.php', 'register.php', 'forgot-password.php', 'reset-password.php']) || !empty($_GET['q']);

// Accurate canonical URL calculation (Finding SEO-1)
if (!isset($canonicalUrl)) {
    if (isset($product) && !empty($product['id'])) {
        $canonicalUrl = SITE_URL . '/product.php?id=' . (int)$product['id'];
    } elseif ($scriptName === 'catalog.php') {
        $params = [];
        if (!empty($_GET['cat'])) $params['cat'] = (int)$_GET['cat'];
        elseif (!empty($_GET['dept'])) $params['dept'] = (int)$_GET['dept'];
        $canonicalUrl = SITE_URL . '/catalog.php' . (!empty($params) ? '?' . http_build_query($params) : '');
    } elseif ($scriptName === 'pages.php' && !empty($_GET['slug'])) {
        $canonicalUrl = SITE_URL . '/pages.php?slug=' . urlencode($_GET['slug']);
    } elseif ($scriptName === 'index.php') {
        $canonicalUrl = SITE_URL . '/';
    } else {
        $canonicalUrl = SITE_URL . '/' . ltrim(strtok($_SERVER['REQUEST_URI'] ?? '', '?'), '/');
    }
}

// Per-product meta description and OG image
if (isset($product)) {
    if (empty($metaDescription) && !empty($product['short_description'])) {
        $metaDescription = strip_tags($product['short_description']);
    }
    if (empty($ogImage) && !empty($product['image'])) {
        $ogImage = get_product_image_url($product['image'], $product['name']);
    }
}
$metaDescription = $metaDescription ?? 'SSK Store - Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat). Explore sacred scriptures, Vachanamrut, handcrafted tulsi kanthis, ghar mandirs, brass puja items, and devotional accessories.';
$ogImage = $ogImage ?? (SITE_URL . '/assets/brand/logo.png');
?>
  <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
  <?php if ($isNoIndex): ?>
    <meta name="robots" content="noindex, nofollow">
  <?php else: ?>
    <meta name="robots" content="index, follow, max-image-preview:large">
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">
  <?php endif; ?>

  <!-- Open Graph & Social Sharing -->
  <meta property="og:type" content="<?= isset($product) ? 'product' : 'website' ?>">
  <meta property="og:site_name" content="SSK Store — Shri Sahajanand Sahitya Kendra">
  <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
  <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl) ?>">
  <meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($metaDescription) ?>">
  <meta name="twitter:image" content="<?= htmlspecialchars($ogImage) ?>">

  <!-- JSON-LD Structured Data: Organization & Mandir -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "Shri Sahajanand Sahitya Kendra",
    "alternateName": "SSK Store",
    "url": "<?= SITE_URL ?>",
    "logo": "<?= SITE_URL ?>/assets/brand/logo.png",
    "contactPoint": {
      "@type": "ContactPoint",
      "telephone": "+91-9426-55-1098",
      "contactType": "customer service",
      "areaServed": "IN",
      "availableLanguage": ["en", "gu", "hi"]
    },
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "Varachha Main Rd, opp. Water Tank, Kalakunj Society, Swaminarayan Nagar-2, Kapodra Patiya",
      "addressLocality": "Surat",
      "addressRegion": "Gujarat",
      "postalCode": "395006",
      "addressCountry": "IN"
    }
  }
  </script>

  <?php if (isset($product)): ?>
  <!-- JSON-LD Structured Data: Product -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Product",
    "name": <?= json_encode($product['name']) ?>,
    "image": <?= json_encode(get_product_image_url($product['image'], $product['name'])) ?>,
    "description": <?= json_encode($product['short_description'] ?: $product['name']) ?>,
    "sku": <?= json_encode($product['sku'] ?? 'SSK-' . $product['id']) ?>,
    "offers": {
      "@type": "Offer",
      "priceCurrency": "INR",
      "price": "<?= number_format((float)($product['sale_price'] ?: $product['price']), 2, '.', '') ?>",
      "availability": "<?= $product['stock_qty'] > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' ?>",
      "url": "<?= htmlspecialchars($canonicalUrl) ?>"
    }
  }
  </script>
  <?php endif; ?>

  <!-- PWA & Mobile Web App Manifest & Meta Tags -->
  <link rel="manifest" href="manifest.webmanifest">
  <link rel="apple-touch-icon" sizes="180x180" href="assets/icons/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="assets/icons/favicon-32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="assets/icons/favicon-16.png">
  <link rel="shortcut icon" href="assets/brand/favicon.ico">
  <meta name="theme-color" media="(prefers-color-scheme: light)" content="#011760">
  <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#060B1F">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="SSK Store">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">

  <!-- Immediate Theme Initializer (Prevents Theme Flash Before Render) -->
  <script>
    (function() {
      try {
        var saved = localStorage.getItem('ssk-theme');
        var theme = saved || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        if (theme === 'system') {
          theme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        }
        document.documentElement.setAttribute('data-theme', theme);
        document.documentElement.style.colorScheme = theme;
      } catch (e) {}
    })();
  </script>

  <!-- Preconnect and non-blocking Web Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400;500;600;700&family=Noto+Sans+Gujarati:wght@400;500;600;700&family=Outfit:wght@400;600;700;800&family=Plus+Jakarta+Sans:ital,wght@0,400..800;1,400..800&display=swap">

  <link rel="stylesheet" href="assets/css/theme.css?v=<?= file_exists(ROOT_PATH . 'assets/css/theme.css') ? filemtime(ROOT_PATH . 'assets/css/theme.css') : 1 ?>">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= file_exists(ROOT_PATH . 'assets/css/style.css') ? filemtime(ROOT_PATH . 'assets/css/style.css') : 1 ?>">
</head>
<body>

  <!-- Top Announcement Bar -->
  <div class="announcement-bar">
    <div class="container announcement-inner">
      <span class="announcement-brand">Jay Swaminarayan 🙏 — Shree Swaminarayan Mandir, Kalakunj, Surat</span>
      <div class="announcement-links">
        <a href="tel:+919426551098" class="announcement-phone" style="white-space: nowrap;">📞 +91 9426-55-1098</a>
        <span class="announcement-sep">•</span>
        <span class="announcement-hours" style="white-space: nowrap;">🕒 8:00 AM – 8:00 PM</span>
      </div>
    </div>
  </div>

  <!-- Slide-Out Mobile Navigation Drawer -->
  <div class="mobile-drawer-backdrop" id="mobileDrawerBackdrop" onclick="closeMobileDrawer()"></div>
  <aside class="mobile-drawer" id="mobileDrawer" aria-label="Mobile Navigation Menu">
    <div class="mobile-drawer-header">
      <div class="mobile-drawer-brand">
        <img src="assets/brand/logo.png" alt="SSK Store" class="mobile-drawer-logo">
      </div>
      <button type="button" class="mobile-drawer-close" id="mobileDrawerClose" onclick="closeMobileDrawer()" aria-label="Close Navigation Menu">&times;</button>
    </div>

    <div class="mobile-drawer-body">
      <!-- User Account Card -->
      <div class="mobile-drawer-user-box">
        <?php if ($isLoggedIn): ?>
          <div style="display: flex; align-items: center; gap: 10px;">
            <div class="mobile-drawer-avatar">
              <?= strtoupper(substr($currentUser['first_name'] ?? 'U', 0, 1)) ?>
            </div>
            <div style="flex: 1; min-width: 0;">
              <div style="font-size: 0.75rem; color: var(--text-muted);">Jai Swaminarayan 🙏</div>
              <div style="font-weight: 700; font-size: 0.9375rem; color: var(--heading); text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                <?= htmlspecialchars(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? '')) ?>
              </div>
            </div>
          </div>
          <div style="display: flex; gap: 8px; margin-top: 10px;">
            <a href="account.php" class="btn btn-secondary btn-sm" style="flex: 1; justify-content: center; font-size: 0.7813rem; padding: 6px 10px;">My Account</a>
            <a href="account.php#orders" class="btn btn-secondary btn-sm" style="flex: 1; justify-content: center; font-size: 0.7813rem; padding: 6px 10px;">Orders</a>
            <a href="logout.php" class="btn btn-danger btn-sm" style="font-size: 0.7813rem; padding: 6px 10px;">Logout</a>
          </div>
        <?php else: ?>
          <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
            <div class="mobile-drawer-avatar" style="background: rgba(1, 23, 96, 0.08); color: var(--heading);">🙏</div>
            <div>
              <div style="font-weight: 700; font-size: 0.9375rem; color: var(--heading);">Welcome to SSK Store</div>
              <div style="font-size: 0.75rem; color: var(--text-muted);">Shree Swaminarayan Mandir, Kalakunj</div>
            </div>
          </div>
          <button type="button" class="btn btn-primary btn-block btn-sm" onclick="closeMobileDrawer(); openAuthModal('login');">
            Sign In / Register &rarr;
          </button>
        <?php endif; ?>
      </div>

      <!-- Quick Utility Row (Theme Toggle & Wishlist) -->
      <div class="mobile-drawer-utilities">
        <button type="button" class="mobile-drawer-util-btn theme-toggle-btn" aria-label="Toggle theme">
          <span class="theme-toggle-icon"></span>
          <span class="theme-toggle-text">Dark Mode</span>
        </button>
        <a href="<?= $isLoggedIn ? 'account.php#wishlist' : 'javascript:closeMobileDrawer(); openAuthModal();' ?>" class="mobile-drawer-util-btn">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
          <span>Wishlist (<?= $wishlistCount ?>)</span>
        </a>
      </div>

      <!-- PWA Install Button inside Drawer -->
      <button type="button" class="mobile-drawer-install-btn pwa-header-install-btn" id="pwaDrawerInstallBtn" style="display: none;" title="Download SSK App">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
        <span>Install SSK Mobile App</span>
      </button>

      <!-- Department & Category Links -->
      <div class="mobile-drawer-section-title">Store Departments</div>
      <nav class="mobile-drawer-nav">
        <a href="index.php" class="mobile-drawer-link <?= !isset($_GET['dept']) && basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
          <span>🏠</span> Home
        </a>
        <a href="catalog.php" class="mobile-drawer-link <?= !isset($_GET['dept']) && !isset($_GET['on_sale']) && basename($_SERVER['PHP_SELF']) === 'catalog.php' ? 'active' : '' ?>">
          <span>📖</span> All Sacred Items
        </a>
        <?php foreach ($navDepartments as $dept): ?>
          <div class="mobile-drawer-dept-item">
            <div style="display: flex; align-items: center; justify-content: space-between;">
              <a href="catalog.php?dept=<?= $dept['id'] ?>" class="mobile-drawer-link <?= (isset($_GET['dept']) && $_GET['dept'] == $dept['id']) ? 'active' : '' ?>" style="flex: 1;">
                <span>🪷</span> <?= htmlspecialchars($dept['name']) ?>
              </a>
              <?php if (!empty($dept['categories'])): ?>
                <button type="button" class="mobile-cat-expand-btn" onclick="var el = document.getElementById('mob-cats-<?= $dept['id'] ?>'); if(el){ el.style.display = (el.style.display === 'block' ? 'none' : 'block'); }" aria-label="Toggle <?= htmlspecialchars($dept['name']) ?> categories" style="background: none; border: none; padding: 0.6rem 0.85rem; color: var(--text-muted); cursor: pointer;">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>
              <?php endif; ?>
            </div>
            <?php if (!empty($dept['categories'])): ?>
              <div class="mobile-drawer-cat-list" id="mob-cats-<?= $dept['id'] ?>" style="display: <?= (isset($_GET['dept']) && $_GET['dept'] == $dept['id']) ? 'block' : 'none' ?>; padding: 0.2rem 0 0.5rem 2.2rem; background: rgba(0,0,0,0.02);">
                <?php foreach ($dept['categories'] as $cat): ?>
                  <a href="catalog.php?dept=<?= $dept['id'] ?>&cat=<?= $cat['id'] ?>" class="mobile-drawer-sub-link <?= (isset($_GET['cat']) && $_GET['cat'] == $cat['id']) ? 'active' : '' ?>" style="display: block; padding: 0.35rem 0; font-size: 0.8125rem; color: var(--text-muted); text-decoration: none;">
                    • <?= htmlspecialchars($cat['name']) ?>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
        <a href="catalog.php?on_sale=1" class="mobile-drawer-link" style="color: var(--danger); font-weight: 700;">
          <span>🔥</span> Special Offers & Sale
        </a>
        <a href="account.php" class="mobile-drawer-link">
          <span>🚚</span> Track My Order
        </a>
      </nav>

      <!-- Temple Connect & Social Support -->
      <div class="mobile-drawer-section-title">Temple Support & Connect</div>
      <div class="mobile-drawer-socials">
        <a href="https://wa.me/919426551098?text=Jai%20Swaminarayan" target="_blank" rel="noopener" class="mobile-drawer-social-btn wa">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
          <span>WhatsApp Support</span>
        </a>
        <a href="https://www.instagram.com/s_s_sahitya_kendra/" target="_blank" rel="noopener" class="mobile-drawer-social-btn ig">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
          <span>Instagram Profile</span>
        </a>
      </div>

      <div class="mobile-drawer-footer">
        <a href="tel:+919426551098" style="color: inherit; text-decoration: none; font-weight: 700;">📞 +91 9426-55-1098</a><br>
        Daily: 8:00 AM – 8:00 PM<br>
        Shree Swaminarayan Mandir, Kalakunj, Surat
      </div>
    </div>
  </aside>

  <!-- Main Header -->
  <header class="main-header">
    <div class="container header-inner">
      
      <!-- Mobile Menu Toggle Button -->
      <button class="mobile-toggle" id="mobileToggleBtn" onclick="openMobileDrawer()" aria-label="Toggle navigation menu" title="Open Menu">
        <span></span>
        <span></span>
        <span></span>
      </button>

      <!-- Brand Logo -->
      <a href="index.php" class="brand-logo" title="Shri Sahajanand Sahitya Kendra — Kalakunj Mandir, Surat">
        <picture>
          <source type="image/webp" srcset="assets/brand/logo.webp">
          <img src="assets/brand/logo.png" alt="Shri Sahajanand Sahitya Kendra - Kalakunj Mandir" class="brand-logo-img" width="151" height="56" fetchpriority="high">
        </picture>
      </a>

      <!-- Search Bar with Live Suggestions -->
      <div class="header-search">
        <form action="catalog.php" method="GET" class="search-form">
          <input type="text" name="q" class="search-input" placeholder="Search scriptures, kanthi, mandirs, puja items..." autocomplete="off" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
          <button type="submit" class="search-btn" aria-label="Search">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
          </button>
        </form>
        <div class="search-suggestions"></div>
      </div>

      <!-- Action Items (Theme Toggle, Install App, Wishlist, Cart, User) -->
      <div class="header-actions">
        
        <!-- PWA Install Button (Shown on desktop & supported mobile browsers) -->
        <button type="button" class="btn btn-secondary btn-sm pwa-header-install-btn desktop-only" id="pwaHeaderInstallBtn" style="display: none;" title="Download as Mobile Application">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
          <span class="pwa-install-lbl">Install App</span>
        </button>

        <!-- Light / Dark Theme Toggle Button -->
        <button type="button" class="theme-toggle-btn desktop-only" aria-label="Toggle color theme" title="Toggle Light / Dark Mode">
          <span class="theme-toggle-icon"></span>
          <span class="theme-toggle-text">Dark</span>
        </button>

        <!-- Direct WhatsApp Chat (Icon only) -->
        <a href="https://wa.me/919426551098?text=Jai%20Swaminarayan" target="_blank" rel="noopener" class="action-item action-item-wa desktop-only" title="Direct WhatsApp Support: +91 9426-55-1098" aria-label="Direct WhatsApp Support">
          <div class="action-icon-wrap" style="color: #25D366;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
              <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
            </svg>
          </div>
        </a>

        <!-- Direct Instagram Profile (Icon only) -->
        <a href="https://www.instagram.com/s_s_sahitya_kendra/" target="_blank" rel="noopener" class="action-item action-item-ig desktop-only" title="Follow Instagram: @s_s_sahitya_kendra" aria-label="Official Instagram">
          <div class="action-icon-wrap" style="color: #E1306C;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
            </svg>
          </div>
        </a>

        <!-- Wishlist -->
        <a href="<?= $isLoggedIn ? 'account.php#wishlist' : 'javascript:openAuthModal()' ?>" class="action-item action-item-wishlist desktop-only" title="My Wishlist">
          <div class="action-icon-wrap">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
            <span class="badge-count badge-wishlist-count" style="<?= $wishlistCount > 0 ? '' : 'display:none;' ?>"><?= $wishlistCount ?></span>
          </div>
        </a>

        <!-- Cart (Always visible in Header) -->
        <a href="cart.php" class="action-item action-item-cart" title="Shopping Cart">
          <div class="action-icon-wrap">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-2z"></path>
              <line x1="3" y1="6" x2="21" y2="6"></line>
              <path d="M16 10a4 4 0 0 1-8 0"></path>
            </svg>
            <span class="badge-count badge-cart-count" style="<?= $cartCount > 0 ? '' : 'display:none;' ?>"><?= $cartCount ?></span>
          </div>
          <span class="action-label desktop-only">Cart</span>
        </a>

        <!-- User Auth -->
        <div class="action-item-auth desktop-only">
          <?php if ($isLoggedIn): ?>
            <a href="account.php" class="action-item">
              <div class="action-icon-wrap">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                  <circle cx="12" cy="7" r="4"></circle>
                </svg>
              </div>
              <span class="action-label"><?= htmlspecialchars($currentUser['first_name'] ?? 'Account') ?></span>
            </a>
          <?php else: ?>
            <button type="button" class="btn btn-primary btn-sm" onclick="openAuthModal('login')">
              Sign In
            </button>
          <?php endif; ?>
        </div>

      </div>

    </div>
  </header>

  <!-- Mega Menu Navigation Bar -->
  <nav class="nav-bar">
    <div class="container nav-inner">
      <ul class="nav-menu">
        <li class="nav-item <?= !isset($_GET['dept']) && basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
          <a href="index.php" class="nav-link">Home</a>
        </li>

        <?php foreach (array_slice($navDepartments, 0, 7) as $dept): ?>
          <li class="nav-item <?= (isset($_GET['dept']) && $_GET['dept'] == $dept['id']) ? 'active' : '' ?>">
            <a href="catalog.php?dept=<?= $dept['id'] ?>" class="nav-link">
              <?= htmlspecialchars($dept['name']) ?>
              <?php if (!empty($dept['categories'])): ?>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
              <?php endif; ?>
            </a>

            <?php if (!empty($dept['categories'])): ?>
              <div class="mega-dropdown">
                <div class="dropdown-header-strip">
                  <span class="dropdown-header-title"><span style="color:var(--gold); margin-right:5px;">✦</span><?= htmlspecialchars($dept['name']) ?> Categories</span>
                  <a href="catalog.php?dept=<?= $dept['id'] ?>" class="dropdown-view-all">View All Products &rarr;</a>
                </div>
                <div class="dropdown-categories-grid">
                  <?php foreach ($dept['categories'] as $cat): ?>
                    <a href="catalog.php?dept=<?= $dept['id'] ?>&cat=<?= $cat['id'] ?>" class="dropdown-cat-chip">
                      <span class="chip-text"><?= htmlspecialchars($cat['name']) ?></span>
                    </a>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>

        <?php if (count($navDepartments) > 7): ?>
          <li class="nav-item nav-item-more">
            <a href="catalog.php" class="nav-link">
              More <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </a>
            <div class="mega-dropdown mega-dropdown-more">
              <div class="dropdown-header-strip">
                <span class="dropdown-header-title"><span style="color:var(--gold); margin-right:5px;">✦</span>More Temple Departments</span>
                <a href="catalog.php" class="dropdown-view-all">All Sacred Items &rarr;</a>
              </div>
              <div class="dropdown-more-departments">
                <?php foreach (array_slice($navDepartments, 7) as $extraDept): ?>
                  <div class="more-dept-card">
                    <a href="catalog.php?dept=<?= $extraDept['id'] ?>" class="more-dept-heading">
                      <span style="color:var(--gold); margin-right:4px;">✦</span><?= htmlspecialchars($extraDept['name']) ?>
                    </a>
                    <?php if (!empty($extraDept['categories'])): ?>
                      <div class="more-dept-subcats">
                        <?php foreach ($extraDept['categories'] as $extraCat): ?>
                          <a href="catalog.php?dept=<?= $extraDept['id'] ?>&cat=<?= $extraCat['id'] ?>" class="more-subcat-link">
                            <?= htmlspecialchars($extraCat['name']) ?>
                          </a>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </li>
        <?php endif; ?>

        <li class="nav-item">
          <a href="catalog.php?on_sale=1" class="nav-link" style="color: var(--danger);">
            Offers <span class="tag-sale">Sale</span>
          </a>
        </li>
      </ul>

      <!-- Quick Track / Link -->
      <div class="nav-track-order">
        <a href="account.php">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
          Track My Order
        </a>
      </div>

    </div>
  </nav>

  <!-- Unified Pop-up Modals & Toasts System -->
  <?php require_once __DIR__ . '/notifications.php'; ?>
