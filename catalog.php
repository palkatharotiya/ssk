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

// Fetch all departments & categories with real-time product counts
$allDepts = db_fetch_all("
    SELECT d.id, d.name, d.slug, COUNT(p.id) as product_count 
    FROM departments d 
    LEFT JOIN products p ON p.department_id = d.id AND p.is_active = 1 
    WHERE d.is_active = 1 
    GROUP BY d.id 
    ORDER BY d.sort_order ASC, d.name ASC
");

// Resolve active category if passed
$activeCat = null;
if ($catId > 0) {
    $activeCat = db_fetch_one("SELECT * FROM categories WHERE id = ?", [$catId]);
    if ($activeCat && $deptId === 0 && !empty($activeCat['department_id'])) {
        $deptId = (int)$activeCat['department_id'];
    }
}

// Resolve active department & its categories
$activeDept = null;
$allCats = [];
if ($deptId > 0) {
    $activeDept = db_fetch_one("SELECT * FROM departments WHERE id = ?", [$deptId]);
    $allCats = db_fetch_all("
        SELECT c.id, c.name, c.slug, c.department_id, COUNT(p.id) as product_count 
        FROM categories c 
        LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1 
        WHERE c.department_id = ? AND c.is_active = 1 
        GROUP BY c.id 
        ORDER BY c.sort_order ASC, c.name ASC
    ", [$deptId]);
}

$pageTitle = ($activeCat ? htmlspecialchars($activeCat['name']) . ' — ' : '') . 
             ($activeDept ? htmlspecialchars($activeDept['name']) . ' — ' : '') . 
             'Catalog — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<main class="container">
  
  <!-- Breadcrumbs -->
  <div style="padding: 1.25rem 0 0.5rem; font-size: 0.875rem; color: var(--text-muted); display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
    <a href="index.php" style="color: var(--text-muted); text-decoration: none;">Home</a>
    <span>/</span>
    <a href="catalog.php" style="color: var(--text-muted); text-decoration: none;">Catalog</a>
    <?php if ($activeDept): ?>
      <span>/</span>
      <?php if ($activeCat): ?>
        <a href="catalog.php?dept=<?= $activeDept['id'] ?>" style="color: var(--text-muted); text-decoration: none;"><?= htmlspecialchars($activeDept['name']) ?></a>
        <span>/</span>
        <span style="color: var(--heading); font-weight: 700;"><?= htmlspecialchars($activeCat['name']) ?></span>
      <?php else: ?>
        <span style="color: var(--heading); font-weight: 700;"><?= htmlspecialchars($activeDept['name']) ?></span>
      <?php endif; ?>
    <?php endif; ?>
    <?php if (!empty($searchQuery)): ?>
      <span>/</span>
      <span style="color: var(--heading); font-weight: 700;">Search: "<?= htmlspecialchars($searchQuery) ?>"</span>
    <?php endif; ?>
  </div>

  <div class="catalog-layout">
    
    <!-- Sidebar Filters -->
    <aside class="filter-sidebar">
      <div class="filter-title">
        <span>Filters</span>
        <a href="catalog.php" style="font-size: 0.8125rem; color: var(--red-text); font-weight: 600;">Reset All</a>
      </div>

      <!-- Department Filter with Nested Categories Accordion -->
      <div class="filter-group">
        <div class="filter-label">Departments</div>
        <ul class="filter-list">
          <li class="filter-item">
            <a href="catalog.php<?= $onSale ? '?on_sale=1' : '' ?>" class="filter-dept-link <?= $deptId === 0 ? 'active' : '' ?>">
              <span>All Departments</span>
              <span class="count-badge"><?= array_sum(array_column($allDepts, 'product_count')) ?></span>
            </a>
          </li>
          <?php foreach ($allDepts as $d): ?>
            <?php $isCurrentDept = ($deptId === (int)$d['id']); ?>
            <li class="filter-item <?= $isCurrentDept ? 'is-active-dept' : '' ?>">
              <a href="catalog.php?dept=<?= $d['id'] ?><?= $onSale ? '&on_sale=1' : '' ?>" class="filter-dept-link <?= $isCurrentDept ? 'active' : '' ?>">
                <span><?= htmlspecialchars($d['name']) ?></span>
                <span class="count-badge"><?= (int)$d['product_count'] ?></span>
              </a>

              <!-- Nested Subcategories for the Active Department -->
              <?php if ($isCurrentDept && !empty($allCats)): ?>
                <ul class="filter-subcat-list">
                  <li>
                    <a href="catalog.php?dept=<?= $d['id'] ?><?= $onSale ? '&on_sale=1' : '' ?>" class="<?= $catId === 0 ? 'active' : '' ?>">
                      <span>• All <?= htmlspecialchars($d['name']) ?></span>
                    </a>
                  </li>
                  <?php foreach ($allCats as $c): ?>
                    <li>
                      <a href="catalog.php?dept=<?= $d['id'] ?>&cat=<?= $c['id'] ?><?= $onSale ? '&on_sale=1' : '' ?>" class="<?= $catId === (int)$c['id'] ? 'active' : '' ?>">
                        <span>• <?= htmlspecialchars($c['name']) ?></span>
                        <span class="count-badge"><?= (int)$c['product_count'] ?></span>
                      </a>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

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
      
      <!-- Horizontal Category Pills for Active Department -->
      <?php if (!empty($allCats)): ?>
        <div class="category-pills-bar" aria-label="Filter by Category">
          <a href="catalog.php?dept=<?= $deptId ?><?= $onSale ? '&on_sale=1' : '' ?>" class="category-pill <?= $catId === 0 ? 'active' : '' ?>">
            <span>All <?= htmlspecialchars($activeDept['name'] ?? 'Items') ?></span>
            <span class="pill-count"><?= array_sum(array_column($allCats, 'product_count')) ?></span>
          </a>
          <?php foreach ($allCats as $c): ?>
            <a href="catalog.php?dept=<?= $deptId ?>&cat=<?= $c['id'] ?><?= $onSale ? '&on_sale=1' : '' ?>" class="category-pill <?= $catId === (int)$c['id'] ? 'active' : '' ?>">
              <span><?= htmlspecialchars($c['name']) ?></span>
              <span class="pill-count"><?= (int)$c['product_count'] ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

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
