<?php
/**
 * BAPS Store - Main Header Template
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
$isNoIndex = in_array($scriptName, ['cart.php', 'checkout.php', 'account.php', 'order-success.php']) || !empty($_GET['q']);
$metaDescription = $metaDescription ?? 'SSK Store - Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat). Explore sacred scriptures, Vachanamrut, handcrafted tulsi kanthis, ghar mandirs, brass puja items, and devotional accessories.';
$currentCleanUrl = SITE_URL . '/' . ltrim(strtok($_SERVER['REQUEST_URI'] ?? '', '?'), '/');
$ogImage = $ogImage ?? (SITE_URL . '/assets/brand/logo.png');
?>
  <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
  <?php if ($isNoIndex): ?>
    <meta name="robots" content="noindex, nofollow">
  <?php else: ?>
    <meta name="robots" content="index, follow, max-image-preview:large">
    <link rel="canonical" href="<?= htmlspecialchars($currentCleanUrl) ?>">
  <?php endif; ?>

  <!-- Open Graph & Social Sharing -->
  <meta property="og:type" content="<?= isset($product) ? 'product' : 'website' ?>">
  <meta property="og:site_name" content="SSK Store — Shri Sahajanand Sahitya Kendra">
  <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
  <meta property="og:url" content="<?= htmlspecialchars($currentCleanUrl) ?>">
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
      "url": "<?= htmlspecialchars($currentCleanUrl . '?id=' . $product['id']) ?>"
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

  <link rel="stylesheet" href="assets/css/theme.css?v=<?= file_exists(ROOT_PATH . 'assets/css/theme.css') ? filemtime(ROOT_PATH . 'assets/css/theme.css') : 1 ?>">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= file_exists(ROOT_PATH . 'assets/css/style.css') ? filemtime(ROOT_PATH . 'assets/css/style.css') : 1 ?>">
</head>
<body>

  <!-- Top Announcement Bar -->
  <div class="announcement-bar">
    <div class="container announcement-inner">
      <span>Jay Swaminarayan 🙏 — Shree Swaminarayan Mandir, Kalakunj, Surat</span>
      <div class="announcement-links">
        <a href="tel:+919426551098">📞 +91 9426-55-1098</a>
        <span class="announcement-sep">•</span>
        <span>Daily: 8:00 AM – 8:00 PM</span>
      </div>
    </div>
  </div>

  <!-- Main Header -->
  <header class="main-header">
    <div class="container header-inner">
      
      <!-- Mobile Menu Toggle Button -->
      <button class="mobile-toggle" aria-label="Toggle navigation">☰</button>

      <!-- Brand Logo -->
      <a href="index.php" class="brand-logo" title="Shri Sahajanand Sahitya Kendra — Kalakunj Mandir, Surat">
        <img src="assets/brand/logo.png" alt="Shri Sahajanand Sahitya Kendra - Kalakunj Mandir" class="brand-logo-img" width="151" height="56" fetchpriority="high">
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
        <button type="button" class="btn btn-secondary btn-sm pwa-header-install-btn" id="pwaHeaderInstallBtn" style="display: none;" title="Download as Mobile Application">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
          <span class="pwa-install-lbl">Install App</span>
        </button>

        <!-- Light / Dark Theme Toggle Button -->
        <button type="button" class="theme-toggle-btn" aria-label="Toggle color theme" title="Toggle Light / Dark Mode">
          <span class="theme-toggle-icon"></span>
          <span class="theme-toggle-text">Dark</span>
        </button>

        <!-- Direct WhatsApp Chat -->
        <a href="https://wa.me/919426551098?text=Jai%20Swaminarayan" target="_blank" rel="noopener" class="action-item" title="Direct WhatsApp Support: +91 9426-55-1098">
          <div class="action-icon-wrap" style="color: #25D366;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
              <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
            </svg>
          </div>
          <span class="action-label" style="font-weight: 700; color: var(--wa-text);">WhatsApp</span>
        </a>

        <!-- Wishlist -->
        <a href="<?= $isLoggedIn ? 'account.php#wishlist' : 'javascript:openAuthModal()' ?>" class="action-item" title="My Wishlist">
          <div class="action-icon-wrap">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
            <span class="badge-count badge-wishlist-count" style="<?= $wishlistCount > 0 ? '' : 'display:none;' ?>"><?= $wishlistCount ?></span>
          </div>
        </a>

        <!-- Cart -->
        <a href="cart.php" class="action-item" title="Shopping Cart">
          <div class="action-icon-wrap">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-2z"></path>
              <line x1="3" y1="6" x2="21" y2="6"></line>
              <path d="M16 10a4 4 0 0 1-8 0"></path>
            </svg>
            <span class="badge-count badge-cart-count" style="<?= $cartCount > 0 ? '' : 'display:none;' ?>"><?= $cartCount ?></span>
          </div>
          <span class="action-label">Cart</span>
        </a>

        <!-- User Auth -->
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
                <?php foreach (array_slice($dept['categories'], 0, 3) as $cat): ?>
                  <div class="dropdown-col">
                    <div class="dropdown-col-title">
                      <a href="catalog.php?dept=<?= $dept['id'] ?>&cat=<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></a>
                    </div>
                    <?php if (!empty($cat['subcategories'])): ?>
                      <ul class="dropdown-list">
                        <?php foreach (array_slice($cat['subcategories'], 0, 5) as $sub): ?>
                          <li>
                            <a href="catalog.php?dept=<?= $dept['id'] ?>&cat=<?= $cat['id'] ?>&sub=<?= $sub['id'] ?>">
                              <?= htmlspecialchars($sub['name']) ?>
                            </a>
                          </li>
                        <?php endforeach; ?>
                      </ul>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>

        <li class="nav-item">
          <a href="catalog.php?on_sale=1" class="nav-link" style="color: var(--danger);">
            Offers <span class="tag-sale">Sale</span>
          </a>
        </li>
      </ul>

      <!-- Quick Track / Link -->
      <div style="font-size: 0.8125rem; font-weight: 600;">
        <a href="account.php" style="color: var(--gold); display: flex; align-items: center; gap: 0.35rem;">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
          Track My Order
        </a>
      </div>

    </div>
  </nav>

  <!-- Flash Notification Message if any -->
  <?php if ($flash = get_flash()): ?>
    <div class="container" style="margin-top: 1rem;">
      <div style="padding: 0.85rem 1.25rem; border-radius: var(--radius-md); background: <?= $flash['type'] === 'success' ? '#dcfce7' : '#fee2e2' ?>; color: <?= $flash['type'] === 'success' ? '#166534' : '#991b1b' ?>; font-weight: 600; display: flex; justify-content: space-between; align-items: center;">
        <span><?= htmlspecialchars($flash['message']) ?></span>
        <button style="background: none; border: none; cursor: pointer; font-size: 1.1rem; color: inherit;" onclick="this.parentElement.remove();">&times;</button>
      </div>
    </div>
  <?php endif; ?>
