<?php
/**
 * SSK Store - Homepage
 * Shree Swaminarayan Mandir, Kalakunj, Surat
 * Shri Sahajanand Sahitya Kendra
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'SSK Store — Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat) | Sacred Books, Kanthis, Mandirs & Puja Items';

// Fetch Hero Banners
$banners = db_fetch_all("SELECT * FROM banners WHERE is_active = 1 ORDER BY sort_order ASC");

// Fetch Departments
$departments = db_fetch_all("SELECT * FROM departments WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 6");

// Fetch Active Homepage Sections (Order managed by admin)
$sections = db_fetch_all("SELECT * FROM homepage_sections WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");

// Helper function to fetch products for a dynamic section
function get_section_products(string $key, int $limit = 4): array {
    switch ($key) {
        case 'festive_specials':
            // Ghar Mandirs, Murtis, Puja items & festival sets
            return db_fetch_all("
                SELECT p.*, d.name as dept_name 
                FROM products p 
                JOIN departments d ON p.department_id = d.id 
                WHERE p.is_active = 1 AND (p.department_id IN (4, 5, 6) OR p.is_featured = 1) 
                ORDER BY p.price DESC 
                LIMIT ?
            ", [$limit]);

        case 'new_arrivals':
            // Latest additions by product ID
            return db_fetch_all("
                SELECT p.*, d.name as dept_name 
                FROM products p 
                JOIN departments d ON p.department_id = d.id 
                WHERE p.is_active = 1 
                ORDER BY p.id DESC 
                LIMIT ?
            ", [$limit]);

        case 'top_selling':
            // Most revered classic scriptures & sanctified tulsi kanthis
            return db_fetch_all("
                SELECT p.*, d.name as dept_name 
                FROM products p 
                JOIN departments d ON p.department_id = d.id 
                WHERE p.is_active = 1 AND p.department_id IN (1, 2) 
                ORDER BY p.id ASC 
                LIMIT ?
            ", [$limit]);

        case 'best_offer':
            // Products with active discounts / highest discount savings
            return db_fetch_all("
                SELECT p.*, d.name as dept_name 
                FROM products p 
                JOIN departments d ON p.department_id = d.id 
                WHERE p.is_active = 1 AND p.on_sale = 1 
                ORDER BY (p.price - COALESCE(p.sale_price, p.price)) DESC, p.id DESC 
                LIMIT ?
            ", [$limit]);

        default:
            return db_fetch_all("
                SELECT p.*, d.name as dept_name 
                FROM products p 
                JOIN departments d ON p.department_id = d.id 
                WHERE p.is_active = 1 
                ORDER BY p.id ASC 
                LIMIT ?
            ", [$limit]);
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Full-Width Hero Banner Image Slideshow (Auto-Scroll, Managed by Admin) -->
<section class="hero-slider-section">
  <div class="slider-container">
    
    <!-- Prev & Next Arrow Controls -->
    <button class="slider-arrow-btn slider-arrow-prev" aria-label="Previous Slide">&lsaquo;</button>
    <button class="slider-arrow-btn slider-arrow-next" aria-label="Next Slide">&rsaquo;</button>

    <?php foreach ($banners as $idx => $b): 
      $imgSrc = $b['image'];
      $webpSrc = preg_replace('/\.(jpe?g|png)$/i', '.webp', $imgSrc);
      $hasWebp = file_exists(ROOT_PATH . ltrim($webpSrc, '/'));
    ?>
      <div class="slide <?= $idx === 0 ? 'active' : '' ?>">
        <a href="<?= htmlspecialchars(!empty($b['link']) ? $b['link'] : 'catalog.php') ?>" class="slide-link">
          <picture>
            <?php if ($hasWebp): ?>
              <source type="image/webp" srcset="<?= htmlspecialchars($webpSrc) ?>">
            <?php endif; ?>
            <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($b['title'] ?? 'SSK Store Banner') ?>" class="hero-banner-img" <?= $idx === 0 ? 'fetchpriority="high"' : 'loading="lazy" decoding="async"' ?>>
          </picture>
        </a>
      </div>
    <?php endforeach; ?>

    <!-- Bottom Pagination Dots -->
    <div class="slider-dots">
      <?php foreach ($banners as $idx => $b): ?>
        <div class="dot <?= $idx === 0 ? 'active' : '' ?>"></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<main class="container">

  <!-- Free Shipping Strip -->
  <section class="ssk-shipping-strip">
    <div class="ssk-shipping-badge">
      <div class="ssk-truck-icon">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
      </div>
      <div>
        <span class="ssk-free-title">FREE*</span>
        <span class="ssk-ship-title">SHIPPING</span>
      </div>
    </div>
    <div class="ssk-shipping-details">
      <div>* <strong>Within Gujarat</strong> : For Order of ₹500 &amp; Above</div>
      <div>* <strong>Metro City</strong> : For Order of ₹750 &amp; Above</div>
      <div>* <strong>Within India</strong> : For Order of ₹750 &amp; Above</div>
      <div style="margin-top: 0.35rem; color: var(--text-muted); font-size: 0.825rem;">
        * Stay updated with sacred literature &amp; puja items from SSK Store — <a href="https://wa.me/919426551098" target="_blank" style="color: #16a34a; font-weight: 700; text-decoration: underline;">Click here to join our WhatsApp Channel</a>.
      </div>
    </div>
  </section>

  <!-- Shop by Sacred Department Strip -->
  <?php 
    $homeDepts = db_fetch_all("
      SELECT d.*, COUNT(p.id) as product_count 
      FROM departments d 
      LEFT JOIN products p ON p.department_id = d.id AND p.is_active = 1 
      WHERE d.is_active = 1 
      GROUP BY d.id 
      ORDER BY d.sort_order ASC, d.name ASC
    ");
    $deptIcons = [
      'Books' => '📖',
      'Kanthis' => '📿',
      'Japamalas' => '📿',
      'Ghar Mandirs' => '🛕',
      'Puja Items' => '🪔',
      'Murtis' => '🕉️',
      'Agarbatti & Dhoop' => '🌺',
      'Clothing' => '👕',
      'Calendars & Diaries' => '📅',
      'Audio & Video' => '🎵',
    ];
  ?>
  <section class="ssk-section-container" style="margin-top: 1.5rem; margin-bottom: 2rem;">
    <div class="ssk-section-header">
      <h2>Explore Sacred Departments</h2>
      <a href="catalog.php" class="ssk-view-all">All Departments &rarr;</a>
    </div>
    <div class="dept-cards-grid">
      <?php foreach ($homeDepts as $hd): ?>
        <a href="catalog.php?dept=<?= $hd['id'] ?>" class="dept-card-item">
          <span class="dept-card-icon"><?= $deptIcons[$hd['name']] ?? '🪷' ?></span>
          <span class="dept-card-title"><?= htmlspecialchars($hd['name']) ?></span>
          <span class="dept-card-count"><?= (int)$hd['product_count'] ?> items</span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Dynamic Admin-Ordered Product Showcase Sections -->
  <?php foreach ($sections as $sec): ?>
    <?php
      $limit = max((int)($sec['items_limit'] ?? 5), 5);
      $secProducts = get_section_products($sec['section_key'], $limit);
      if (empty($secProducts)) continue;
    ?>
    <section class="ssk-section-container">
      <div class="ssk-section-header">
        <h2><?= htmlspecialchars($sec['section_title']) ?></h2>
        <a href="catalog.php" class="ssk-view-all">View all</a>
      </div>

      <div class="ssk-products-grid">
        <?php foreach ($secProducts as $product): ?>
          <?php
            $price = (float)$product['price'];
            $salePrice = (float)($product['sale_price'] ?? 0);
            $hasDiscount = $salePrice > 0 && $salePrice < $price;
            $displayPrice = $hasDiscount ? $salePrice : $price;
            $discountPct = $hasDiscount ? round((($price - $salePrice) / $price) * 100) : 0;
            $imgUrl = get_product_image_url($product['image'], $product['name']);
          ?>
          <a href="product.php?id=<?= $product['id'] ?>" class="ssk-product-card" title="<?= htmlspecialchars($product['name']) ?>">
            <div class="ssk-product-thumb">
              <?php if ($hasDiscount): ?>
                <span class="ssk-product-badge sale"><?= $discountPct ?>% OFF</span>
              <?php elseif ($product['is_featured']): ?>
                <span class="ssk-product-badge featured">New</span>
              <?php endif; ?>
              <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy" decoding="async" width="200" height="200">
            </div>

            <div class="ssk-product-info">
              <h3 class="ssk-product-name"><?= htmlspecialchars($product['name']) ?></h3>
              <div class="ssk-product-sub"><?= htmlspecialchars($product['dept_name']) ?></div>
              <div class="ssk-product-price">
                <?= format_price($displayPrice) ?>
                <?php if ($hasDiscount): ?>
                  <span class="old-price"><?= format_price($price) ?></span>
                <?php endif; ?>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endforeach; ?>

  <!-- Category Quick Browse Strip -->
  <section class="category-section" style="margin-top: 2rem;">
    <div class="section-header">
      <div>
        <h2 class="section-title">Shop by Category</h2>
        <div class="section-subtitle">Explore sacred scriptures, handcrafted kanthis, and mandir accessories</div>
      </div>
      <a href="catalog.php" class="view-all-link">
        View All Categories <span>&rarr;</span>
      </a>
    </div>

    <div class="categories-carousel">
      <?php foreach ($departments as $dept): ?>
        <a href="catalog.php?dept=<?= $dept['id'] ?>" class="cat-card">
          <div class="cat-avatar">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
              <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
            </svg>
          </div>
          <span class="cat-name"><?= htmlspecialchars($dept['name']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <?php
    $totalCatalogCount = (int)(db_fetch_one("SELECT COUNT(*) as c FROM products WHERE is_active = 1")['c'] ?? 0);
    if ($totalCatalogCount === 0):
  ?>
    <section style="background: var(--surface); border: 2px dashed var(--stat-amber); border-radius: var(--radius-card); padding: 3.5rem 1.5rem; text-align: center; margin-bottom: 3.5rem; box-shadow: var(--shadow-sm);">
      <div style="font-size: 2.75rem; margin-bottom: 0.75rem;">🙏</div>
      <h3 style="font-size: 1.5rem; font-weight: 800; color: var(--heading); margin-bottom: 0.5rem;">
        New Spiritual Collection Arriving Soon!
      </h3>
      <p style="color: var(--text-muted); max-width: 580px; margin: 0 auto 1.5rem; font-size: 0.95rem; line-height: 1.6;">
        Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat) is preparing our catalog of sacred scriptures, sanctified tulsi kanthis, and ghar mandirs. Devotees can contact us directly on WhatsApp for orders and inquiries.
      </p>
      <div style="display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap;">
        <a href="https://wa.me/919426551098?text=Jai%20Swaminarayan" target="_blank" class="btn btn-primary" style="background: #16a34a; border-color: #16a34a;">
          Direct WhatsApp: +91 9426-55-1098
        </a>
        <a href="catalog.php" class="btn btn-secondary">
          Explore Departments
        </a>
      </div>
    </section>
  <?php endif; ?>


</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
