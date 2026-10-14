<?php
/**
 * BAPS Store - Product Catalog Page
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Filter parameters
$deptId = isset($_GET['dept']) ? (int)$_GET['dept'] : 0;
$catId = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$subId = isset($_GET['sub']) ? (int)$_GET['sub'] : 0;
$onSale = !empty($_GET['on_sale']);
$searchQuery = trim($_GET['q'] ?? '');
$sortBy = $_GET['sort'] ?? 'featured';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Build SQL conditions
$where = ["p.is_active = 1"];
$params = [];

if ($deptId > 0) {
    $where[] = "p.department_id = ?";
    $params[] = $deptId;
}
if ($catId > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $catId;
}
if ($subId > 0) {
    $where[] = "p.subcategory_id = ?";
    $params[] = $subId;
}
if ($onSale) {
    $where[] = "p.on_sale = 1";
}
if (!empty($searchQuery)) {
    $where[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.short_description LIKE ?)";
    $like = "%$searchQuery%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = implode(' AND ', $where);

// Sorting
$orderSql = "p.is_featured DESC, p.id DESC";
switch ($sortBy) {
    case 'price_asc':
        $orderSql = "COALESCE(p.sale_price, p.price) ASC";
        break;
    case 'price_desc':
        $orderSql = "COALESCE(p.sale_price, p.price) DESC";
        break;
    case 'newest':
        $orderSql = "p.created_at DESC";
        break;
    case 'name_asc':
        $orderSql = "p.name ASC";
        break;
}

// Total count
$countSql = "SELECT COUNT(*) as total FROM products p WHERE $whereSql";
$totalRow = db_fetch_one($countSql, $params);
$totalProducts = (int)($totalRow['total'] ?? 0);
$totalPages = max(1, ceil($totalProducts / $perPage));

// Fetch page products
$sql = "SELECT p.*, d.name as dept_name, c.name as cat_name 
        FROM products p 
        LEFT JOIN departments d ON p.department_id = d.id 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE $whereSql 
        ORDER BY $orderSql 
        LIMIT $perPage OFFSET $offset";
$products = db_fetch_all($sql, $params);

// Fetch all departments & categories for filter sidebar
$allDepts = db_fetch_all("SELECT * FROM departments WHERE is_active = 1 ORDER BY sort_order ASC");
$allCats = [];
if ($deptId > 0) {
    $allCats = db_fetch_all("SELECT * FROM categories WHERE department_id = ? AND is_active = 1 ORDER BY sort_order ASC", [$deptId]);
}

$pageTitle = 'Catalog — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<main class="container">
  
  <!-- Breadcrumbs -->
  <div style="padding: 1.25rem 0 0.5rem; font-size: 0.875rem; color: var(--text-muted); display: flex; align-items: center; gap: 0.5rem;">
    <a href="index.php">Home</a>
    <span>/</span>
    <a href="catalog.php">Catalog</a>
    <?php if ($deptId > 0): ?>
      <?php $activeDept = db_fetch_one("SELECT name FROM departments WHERE id = ?", [$deptId]); ?>
      <?php if ($activeDept): ?>
        <span>/</span>
        <span style="color: var(--heading); font-weight: 600;"><?= htmlspecialchars($activeDept['name']) ?></span>
      <?php endif; ?>
    <?php endif; ?>
    <?php if (!empty($searchQuery)): ?>
      <span>/</span>
      <span style="color: var(--heading); font-weight: 600;">Search: "<?= htmlspecialchars($searchQuery) ?>"</span>
    <?php endif; ?>
  </div>

  <div class="catalog-layout">
    
    <!-- Sidebar Filters -->
    <aside class="filter-sidebar">
      <div class="filter-title">
        <span>Filters</span>
        <a href="catalog.php" style="font-size: 0.8125rem; color: var(--red-text); font-weight: 600;">Reset All</a>
      </div>

      <!-- Department Filter -->
      <div class="filter-group">
        <div class="filter-label">Departments</div>
        <ul class="filter-list">
          <li class="filter-item">
            <a href="catalog.php<?= $onSale ? '?on_sale=1' : '' ?>" style="display: block; font-size: 0.875rem; font-weight: <?= $deptId === 0 ? '700' : '500' ?>; color: <?= $deptId === 0 ? 'var(--red-text)' : 'var(--text)' ?>;">
              All Departments
            </a>
          </li>
          <?php foreach ($allDepts as $d): ?>
            <li class="filter-item">
              <a href="catalog.php?dept=<?= $d['id'] ?><?= $onSale ? '&on_sale=1' : '' ?>" style="display: block; font-size: 0.875rem; font-weight: <?= $deptId === (int)$d['id'] ? '700' : '500' ?>; color: <?= $deptId === (int)$d['id'] ? 'var(--red-text)' : 'var(--text)' ?>;">
                <?= htmlspecialchars($d['name']) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- Categories Filter (if Dept selected) -->
      <?php if (!empty($allCats)): ?>
        <div class="filter-group">
          <div class="filter-label">Categories</div>
          <ul class="filter-list">
            <?php foreach ($allCats as $c): ?>
              <li class="filter-item">
                <a href="catalog.php?dept=<?= $deptId ?>&cat=<?= $c['id'] ?><?= $onSale ? '&on_sale=1' : '' ?>" style="display: block; font-size: 0.875rem; font-weight: <?= $catId === (int)$c['id'] ? '700' : '500' ?>; color: <?= $catId === (int)$c['id'] ? 'var(--red-text)' : 'var(--text)' ?>;">
                  <?= htmlspecialchars($c['name']) ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <!-- Quick Toggle: On Sale -->
      <div class="filter-group">
        <div class="filter-label">Special Deals</div>
        <label class="filter-checkbox">
          <input type="checkbox" onchange="window.location.href='catalog.php?dept=<?= $deptId ?>&on_sale=' + (this.checked ? '1' : '0')" <?= $onSale ? 'checked' : '' ?>>
          <span style="color: var(--text);">Discounted / On Sale Only</span>
        </label>
      </div>

    </aside>

    <!-- Main Products Listing -->
    <section>
      
      <!-- Catalog Controls Header -->
      <div class="catalog-header">
        <div style="font-size: 0.9375rem; color: var(--text-muted);">
          Showing <strong style="color: var(--heading);"><?= $totalProducts ?></strong> items
          <?php if (!empty($searchQuery)): ?>
            matching "<strong style="color: var(--heading);"><?= htmlspecialchars($searchQuery) ?></strong>"
          <?php endif; ?>
        </div>

        <form method="GET" style="display: flex; align-items: center; gap: 0.75rem;">
          <?php if ($deptId): ?><input type="hidden" name="dept" value="<?= $deptId ?>"><?php endif; ?>
          <?php if ($catId): ?><input type="hidden" name="cat" value="<?= $catId ?>"><?php endif; ?>
          <?php if ($onSale): ?><input type="hidden" name="on_sale" value="1"><?php endif; ?>
          <?php if ($searchQuery): ?><input type="hidden" name="q" value="<?= htmlspecialchars($searchQuery) ?>"><?php endif; ?>
          
          <label for="sort-select" style="font-size: 0.875rem; color: var(--text-muted);">Sort By:</label>
          <select id="sort-select" name="sort" class="sort-select" onchange="this.form.submit()">
            <option value="featured" <?= $sortBy === 'featured' ? 'selected' : '' ?>>Featured</option>
            <option value="price_asc" <?= $sortBy === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
            <option value="price_desc" <?= $sortBy === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
            <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Newest Arrivals</option>
            <option value="name_asc" <?= $sortBy === 'name_asc' ? 'selected' : '' ?>>Alphabetical (A-Z)</option>
          </select>
        </form>
      </div>

      <!-- Products Grid -->
      <?php if (empty($products)): ?>
        <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-card); padding: 4rem 2rem; text-align: center;">
          <div style="font-size: 3rem; margin-bottom: 1rem; color: var(--text-muted);">🔍</div>
          <h3 style="font-size: 1.35rem; margin-bottom: 0.5rem; color: var(--heading);">No products found</h3>
          <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Try adjusting your filters or search keywords.</p>
          <a href="catalog.php" class="btn btn-primary">View All Products</a>
        </div>
      <?php else: ?>
        <div class="products-grid">
          <?php foreach ($products as $product): ?>
            <?php
              $price = (float)$product['price'];
              $salePrice = (float)($product['sale_price'] ?? 0);
              $hasDiscount = $salePrice > 0 && $salePrice < $price;
              $displayPrice = $hasDiscount ? $salePrice : $price;
              $discountPct = $hasDiscount ? round((($price - $salePrice) / $price) * 100) : 0;
              $imgUrl = get_product_image_url($product['image'], $product['name']);
              $inWish = is_in_wishlist((int)$product['id']);
            ?>
            <article class="product-card">
              <div class="card-img-wrap">
                <?php if ((int)$product['stock_qty'] <= 0): ?>
                  <span class="card-badge" style="background: #475569; color: #fff;">Out of Stock</span>
                <?php elseif ($hasDiscount): ?>
                  <span class="card-badge badge-sale"><?= $discountPct ?>% OFF</span>
                <?php elseif ($product['is_featured']): ?>
                  <span class="card-badge badge-featured">Featured</span>
                <?php endif; ?>

                <button type="button" class="card-wishlist-btn <?= $inWish ? 'active' : '' ?>" data-product-id="<?= $product['id'] ?>" aria-label="Add to wishlist">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="<?= $inWish ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                </button>

                <a href="product.php?id=<?= $product['id'] ?>">
                  <?= render_picture($product['image'] ?? '', $product['name'], ['width' => 300, 'height' => 300, 'loading' => 'lazy']) ?>
                </a>
              </div>

              <div class="card-body">
                <span class="card-dept"><?= htmlspecialchars($product['dept_name'] ?? 'SSK Store') ?></span>
                <h3 class="card-title">
                  <a href="product.php?id=<?= $product['id'] ?>" title="<?= htmlspecialchars($product['name']) ?>">
                    <?= htmlspecialchars($product['name']) ?>
                  </a>
                </h3>

                <div class="card-pricing">
                  <span class="current-price"><?= format_price($displayPrice) ?></span>
                  <?php if ($hasDiscount): ?>
                    <span class="old-price"><?= format_price($price) ?></span>
                  <?php endif; ?>
                </div>

                <div class="card-actions">
                  <?php if ((int)$product['stock_qty'] <= 0): ?>
                    <button type="button" class="btn btn-secondary btn-sm btn-block" disabled style="opacity: 0.65; cursor: not-allowed;">
                      Out of Stock
                    </button>
                  <?php else: ?>
                    <button type="button" class="btn btn-primary btn-sm btn-block btn-add-cart" data-product-id="<?= $product['id'] ?>">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-2z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                      Add to Cart
                    </button>
                  <?php endif; ?>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
          <div style="display: flex; justify-content: center; gap: 0.5rem; margin: 2rem 0;">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
              <?php
                $queryParams = $_GET;
                $queryParams['page'] = $p;
                $pageUrl = 'catalog.php?' . http_build_query($queryParams);
              ?>
              <a href="<?= $pageUrl ?>" class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-secondary' ?>" style="min-width: 38px;">
                <?= $p ?>
              </a>
            <?php endfor; ?>
          </div>
        <?php endif; ?>

      <?php endif; ?>

    </section>

  </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
