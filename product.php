<?php
/**
 * SSK Store - Product Detail Page
 * Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$product = db_fetch_one("SELECT p.*, d.name as dept_name, c.name as cat_name 
                         FROM products p 
                         LEFT JOIN departments d ON p.department_id = d.id 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         WHERE p.id = ? AND p.is_active = 1", [$id]);

if (!$product) {
    header('Location: catalog.php');
    exit;
}

// Increment views
db_query("UPDATE products SET views = views + 1 WHERE id = ?", [$id]);

$price = (float)$product['price'];
$salePrice = (float)($product['sale_price'] ?? 0);
$hasDiscount = $salePrice > 0 && $salePrice < $price;
$displayPrice = $hasDiscount ? $salePrice : $price;
$discountPct = $hasDiscount ? round((($price - $salePrice) / $price) * 100) : 0;
$imgUrl = get_product_image_url($product['image'], $product['name']);
$inWish = is_in_wishlist($id);

// Fetch reviews
$reviews = db_fetch_all("SELECT r.*, c.first_name, c.last_name 
                         FROM product_reviews r 
                         JOIN customers c ON r.customer_id = c.id 
                         WHERE r.product_id = ? AND r.is_approved = 1 
                         ORDER BY r.created_at DESC", [$id]);

// Related products
$relatedProducts = db_fetch_all("SELECT p.*, d.name as dept_name 
                                FROM products p 
                                LEFT JOIN departments d ON p.department_id = d.id 
                                WHERE p.department_id = ? AND p.id != ? AND p.is_active = 1 
                                ORDER BY p.is_featured DESC LIMIT 4", [$product['department_id'], $id]);

$pageTitle = htmlspecialchars($product['name']) . ' — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<main class="container">
  
  <?= render_breadcrumb([
    ['label' => 'Home', 'url' => 'index.php'],
    ['label' => 'Catalog', 'url' => 'catalog.php'],
    ['label' => $product['dept_name'], 'url' => 'catalog.php?dept=' . $product['department_id']],
    ['label' => $product['name']]
  ]) ?>

  <!-- Product Detail Section -->
  <div class="product-detail-layout">
    
    <!-- Image Gallery -->
    <div class="product-gallery">
      <div class="main-img-box">
        <?= render_picture($product['image'] ?? '', $product['name'], [
          'id' => 'product-main-img',
          'width' => 600,
          'height' => 600,
          'loading' => 'eager',
          'fetchpriority' => 'high'
        ]) ?>
      </div>
    </div>

    <!-- Product Details Content -->
    <div class="product-info-wrap">
      <div style="font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; color: var(--gold); letter-spacing: 0.5px; margin-bottom: 0.4rem;">
        <?= htmlspecialchars($product['dept_name']) ?> <?= $product['cat_name'] ? '• ' . htmlspecialchars($product['cat_name']) : '' ?>
      </div>

      <h1 class="detail-title"><?= htmlspecialchars($product['name']) ?></h1>

      <div class="detail-meta">
        <div>
          SKU: <strong><?= htmlspecialchars($product['sku'] ?? 'SSK-' . $product['id']) ?></strong>
        </div>
        <div>
          Availability: 
          <?php if ($product['stock_qty'] > 0): ?>
            <span style="color: var(--success); font-weight: 700;">In Stock (<?= $product['stock_qty'] ?> units)</span>
          <?php else: ?>
            <span style="color: var(--danger); font-weight: 700;">Out of Stock</span>
          <?php endif; ?>
        </div>
        <div>
          Publisher: <strong>Shri Sahajanand Sahitya Kendra</strong>
        </div>
      </div>

      <!-- Pricing -->
      <div class="detail-price-box">
        <span class="detail-price"><?= format_price($displayPrice) ?></span>
        <?php if ($hasDiscount): ?>
          <span class="detail-old-price"><?= format_price($price) ?></span>
          <span class="discount-pill" style="font-size: 1rem;"><?= $discountPct ?>% OFF</span>
        <?php endif; ?>
      </div>

      <p style="color: var(--text-muted); font-size: 0.9375rem; margin-bottom: 1.75rem; line-height: 1.6;">
        <?= htmlspecialchars($product['short_description'] ?? 'Authentic devotional release from Shri Sahajanand Sahitya Kendra (Kalakunj Mandir).') ?>
      </p>

      <!-- Quantity & Add to Cart -->
      <div class="detail-qty-box">
        <div class="qty-stepper">
          <button type="button" class="qty-btn qty-minus">&minus;</button>
          <input type="number" id="product-qty" class="qty-input" value="1" min="1" max="<?= max(1, $product['stock_qty']) ?>">
          <button type="button" class="qty-btn qty-plus">&plus;</button>
        </div>

        <?php $isOutOfStock = ((int)$product['stock_qty'] <= 0); ?>
        <button type="button" class="btn btn-primary btn-lg btn-add-cart" data-product-id="<?= $product['id'] ?>" style="flex: 1;" <?= $isOutOfStock ? 'disabled' : '' ?>>
          <?php if ($isOutOfStock): ?>
            Out of Stock
          <?php else: ?>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-2z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
            Add to Cart
          <?php endif; ?>
        </button>

        <button type="button" class="btn btn-secondary btn-lg card-wishlist-btn <?= $inWish ? 'active' : '' ?>" data-product-id="<?= $product['id'] ?>" title="Save to Wishlist" style="position: static; width: auto; height: auto; border-radius: var(--radius-md); padding: 0 1.25rem;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="<?= $inWish ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
        </button>
      </div>

      <!-- Quick Delivery Check -->
      <div class="pincode-checker">
        <div style="font-size: 0.875rem; font-weight: 700; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
          Estimated Delivery Options
        </div>
        <div style="display: flex; gap: 0.5rem;">
          <input type="text" placeholder="Enter 6-digit Pincode" maxlength="6" id="pincode-input" class="form-input" style="max-width: 220px;">
          <button type="button" class="btn btn-secondary btn-sm" onclick="checkPincode()">Check</button>
        </div>
        <div id="pincode-result" style="margin-top: 0.5rem; font-size: 0.8125rem; color: var(--text-muted);">
          Free delivery on orders above ₹500 in Gujarat & ₹750 across India.
        </div>
      </div>

    </div>

  </div>

  <!-- Product Details Tabs -->
  <section style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-card); padding: 2.5rem; margin-bottom: 3.5rem;">
    <h2 style="font-size: 1.35rem; margin-bottom: 1.25rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--border); color: var(--heading);">
      Product Description & Details
    </h2>
    <div style="color: var(--text); line-height: 1.8; font-size: 0.95rem; margin-bottom: 2rem;">
      <?= nl2br(htmlspecialchars($product['description'] ?? 'No detailed description available.')) ?>
    </div>

    <h3 style="font-size: 1.15rem; margin-bottom: 1rem;">Sanctity & Authenticity Guarantee</h3>
    <ul style="padding-left: 1.25rem; color: var(--text-muted); font-size: 0.9rem; line-height: 1.8;">
      <li>100% Genuine product published & produced under the divine guidance of Shree Swaminarayan Mandir, Kalakunj.</li>
      <li>All sacred kanthis are made from natural holy tulsi wood.</li>
      <li>Ghar Mandirs and metal murtis are inspected for authentic temple design accuracy.</li>
    </ul>
  </section>

  <!-- Related Products Carousel -->
  <?php if (!empty($relatedProducts)): ?>
    <section style="margin-bottom: 3.5rem;">
      <div class="section-header">
        <div>
          <h2 class="section-title">You May Also Like</h2>
          <div class="section-subtitle">Recommended items in this department</div>
        </div>
        <a href="catalog.php?dept=<?= $product['department_id'] ?>" class="view-all-link">Browse More &rarr;</a>
      </div>

      <div class="products-grid">
        <?php foreach ($relatedProducts as $rel): ?>
          <?php
            $rPrice = (float)$rel['price'];
            $rSale = (float)($rel['sale_price'] ?? 0);
            $rHasDisc = $rSale > 0 && $rSale < $rPrice;
            $rDisplay = $rHasDisc ? $rSale : $rPrice;
            $rImg = get_product_image_url($rel['image'], $rel['name']);
          ?>
          <article class="product-card">
            <div class="card-img-wrap">
              <a href="product.php?id=<?= $rel['id'] ?>">
                <?= render_picture($rel['image'] ?? '', $rel['name'], ['width' => 300, 'height' => 300, 'loading' => 'lazy']) ?>
              </a>
            </div>
            <div class="card-body">
              <span class="card-dept"><?= htmlspecialchars($rel['dept_name']) ?></span>
              <h3 class="card-title">
                <a href="product.php?id=<?= $rel['id'] ?>"><?= htmlspecialchars($rel['name']) ?></a>
              </h3>
              <div class="card-pricing">
                <span class="current-price"><?= format_price($rDisplay) ?></span>
                <?php if ($rHasDisc): ?>
                  <span class="old-price"><?= format_price($rPrice) ?></span>
                <?php endif; ?>
              </div>
              <?php if ((int)$rel['stock_qty'] <= 0): ?>
                <button type="button" class="btn btn-secondary btn-sm btn-block" disabled style="opacity: 0.65; cursor: not-allowed;">
                  Out of Stock
                </button>
              <?php else: ?>
                <button type="button" class="btn btn-primary btn-sm btn-block btn-add-cart" data-product-id="<?= $rel['id'] ?>">
                  Add to Cart
                </button>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- Mobile Sticky Add to Cart Bar (Visible on mobile screens < 768px, sits above bottom tab bar) -->
  <div class="mobile-sticky-cart-bar">
    <div class="mobile-sticky-cart-info">
      <div class="mobile-sticky-cart-title"><?= htmlspecialchars($product['name']) ?></div>
      <div class="mobile-sticky-cart-price">
        <span class="price-current"><?= format_price($displayPrice) ?></span>
        <?php if ($hasDiscount): ?>
          <span class="price-old" style="font-size: 0.8125rem; color: var(--text-muted); text-decoration: line-through; margin-left: 0.35rem;"><?= format_price($price) ?></span>
        <?php endif; ?>
      </div>
    </div>
    <button type="button" class="btn btn-primary btn-add-cart" data-product-id="<?= $product['id'] ?>" <?= $isOutOfStock ? 'disabled' : '' ?> style="min-height: 42px; padding: 0.5rem 1rem;">
      <?php if ($isOutOfStock): ?>
        Out of Stock
      <?php else: ?>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-2z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
        Add to Cart
      <?php endif; ?>
    </button>
  </div>

</main>

<script>
function checkPincode() {
  const pin = document.getElementById('pincode-input').value.trim();
  const res = document.getElementById('pincode-result');
  if (pin.length !== 6 || isNaN(pin)) {
    res.innerHTML = '<span style="color: var(--danger)">Please enter a valid 6-digit Indian PIN code.</span>';
    return;
  }
  if (pin.startsWith('38') || pin.startsWith('39') || pin.startsWith('36') || pin.startsWith('37')) {
    res.innerHTML = '<span style="color: var(--success); font-weight: 600;">✓ Gujarat Region: Estimated delivery within 2-4 business days. Free shipping on ₹500+</span>';
  } else {
    res.innerHTML = '<span style="color: var(--primary); font-weight: 600;">✓ All-India Delivery: Estimated delivery within 4-7 business days. Free shipping on ₹750+</span>';
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
