<?php
/**
 * SSK Store - Advanced Admin Product Management & Instant Search
 * Shree Sahajanand Sahitya Kendra (Kalakunj Mandir)
 */

require_once __DIR__ . '/includes/admin_bootstrap.php';

$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['edit'] ?? 0);

// API Endpoint: Media Library JSON for visual image picker
if (isset($_GET['api']) && $_GET['api'] === 'media_library') {
    header('Content-Type: application/json');
    $images = [];
    $seen = [];
    $dirs = [
        ROOT_PATH . 'assets/images/products/',
        ROOT_PATH . 'uploads/products/',
    ];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) continue;
        $files = scandir($dir);
        foreach ($files as $f) {
            if ($f === '.' || $f === '..' || isset($seen[$f])) continue;
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) continue;
            $seen[$f] = true;
            $images[] = [
                'filename' => $f,
                'url' => get_product_image_url($f),
                'name' => ucwords(str_replace(['-', '_'], ' ', pathinfo($f, PATHINFO_FILENAME)))
            ];
        }
    }
    echo json_encode($images);
    exit;
}

// Handle Product Delete via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_product') {
    require_csrf();
    $delId = (int)($_POST['product_id'] ?? 0);
    if ($delId > 0) {
        $hasOrders = (int)(db_fetch_one("SELECT COUNT(*) as c FROM order_items WHERE product_id = ?", [$delId])['c'] ?? 0);
        if ($hasOrders > 0) {
            db_query("UPDATE products SET is_active = 0 WHERE id = ?", [$delId]);
            set_flash('success', "Product #{$delId} is referenced in customer orders and has been set to Draft/Archived.");
        } else {
            db_query("DELETE FROM cart WHERE product_id = ?", [$delId]);
            db_query("DELETE FROM wishlist WHERE product_id = ?", [$delId]);
            db_query("DELETE FROM products WHERE id = ?", [$delId]);
            set_flash('success', 'Product deleted successfully.');
        }
    }
    header('Location: products.php');
    exit;
}

// Handle Form Submit (Create / Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Security token expired. Please refresh and try again.');
        header('Location: products.php' . ($editId > 0 ? '?edit=' . $editId : '?action=create'));
        exit;
    }
    $name = trim($_POST['name'] ?? '');
    $deptId = (int)($_POST['department_id'] ?? 1);
    $catId = (int)($_POST['category_id'] ?? 1);
    $subcatId = !empty($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : null;
    $sku = trim($_POST['sku'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $salePrice = !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null;
    $stock = (int)($_POST['stock_qty'] ?? 0);
    $shortDesc = trim($_POST['short_description'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $onSale = isset($_POST['on_sale']) ? 1 : 0;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $image = trim($_POST['image'] ?? 'vachanamrut-english.jpg');

    // Handle Direct File Upload if user selected or dropped a new photo
    if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image_file'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];
        if (isset($allowedMimes[$mime])) {
            $ext = $allowedMimes[$mime];
            $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($file['name'], PATHINFO_FILENAME));
            $cleanName = substr($cleanName, 0, 30);
            $newFilename = ($cleanName ?: 'prod') . '_' . time() . '.' . $ext;
            $targetDir = ROOT_PATH . 'uploads/products/';
            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }
            $dest = $targetDir . $newFilename;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                @chmod($dest, 0644);
                $image = $newFilename;
            }
        }
    }

    if (empty($sku)) {
        $sku = generate_unique_sku($name, $editId);
    }

    $slug = generate_unique_slug($name, 'products', $editId);

    if ($editId > 0) {
        db_query("UPDATE products SET department_id = ?, category_id = ?, subcategory_id = ?, name = ?, slug = ?, sku = ?, price = ?, sale_price = ?, stock_qty = ?, short_description = ?, description = ?, image = ?, is_featured = ?, on_sale = ?, is_active = ? WHERE id = ?", [
            $deptId, $catId, $subcatId, $name, $slug, $sku, $price, $salePrice, $stock, $shortDesc, $desc, $image, $isFeatured, $onSale, $isActive, $editId
        ]);
        set_flash('success', 'Product updated successfully.');
    } else {
        db_query("INSERT INTO products (department_id, category_id, subcategory_id, name, slug, sku, price, sale_price, stock_qty, short_description, description, image, is_featured, on_sale, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
            $deptId, $catId, $subcatId, $name, $slug, $sku, $price, $salePrice, $stock, $shortDesc, $desc, $image, $isFeatured, $onSale, $isActive
        ]);
        set_flash('success', 'New sacred product added successfully.');
    }
    header('Location: products.php');
    exit;
}
 
$adminPageTitle = 'Products & Inventory';
require_once __DIR__ . '/includes/header.php';

// Fetch single product for edit
$editProduct = null;
if ($editId > 0) {
    $editProduct = db_fetch_one("SELECT * FROM products WHERE id = ?", [$editId]);
}

// Fetch all departments, categories & subcategories for dropdowns
$departments = db_fetch_all("SELECT * FROM departments ORDER BY sort_order ASC, name ASC");
$categories = db_fetch_all("SELECT * FROM categories ORDER BY department_id ASC, sort_order ASC, name ASC");
$subcategories = db_fetch_all("SELECT * FROM subcategories ORDER BY category_id ASC, sort_order ASC, name ASC");

// Search & Filter parameters
$searchQ = trim($_GET['q'] ?? '');
$filterDept = (int)($_GET['dept_id'] ?? 0);
$filterStock = trim($_GET['stock'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');
$sortBy = trim($_GET['sort'] ?? 'id_desc');

$params = [];
$whereSql = "1=1";

if ($searchQ !== '') {
    $whereSql .= " AND (p.name LIKE ? OR p.sku LIKE ? OR p.short_description LIKE ?)";
    $like = "%$searchQ%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($filterDept > 0) {
    $whereSql .= " AND p.department_id = ?";
    $params[] = $filterDept;
}

if ($filterStock === 'in_stock') {
    $whereSql .= " AND p.stock_qty > 15";
} elseif ($filterStock === 'low_stock') {
    $whereSql .= " AND p.stock_qty > 0 AND p.stock_qty <= 15";
} elseif ($filterStock === 'out_of_stock') {
    $whereSql .= " AND p.stock_qty = 0";
}

if ($filterStatus === 'active') {
    $whereSql .= " AND p.is_active = 1";
} elseif ($filterStatus === 'draft') {
    $whereSql .= " AND p.is_active = 0";
}

$orderBy = "p.id DESC";
if ($sortBy === 'name_asc') $orderBy = "p.name ASC";
elseif ($sortBy === 'price_asc') $orderBy = "p.price ASC";
elseif ($sortBy === 'price_desc') $orderBy = "p.price DESC";
elseif ($sortBy === 'stock_asc') $orderBy = "p.stock_qty ASC";

$products = db_fetch_all("SELECT p.*, d.name as dept_name, c.name as cat_name, sc.name as subcat_name 
                          FROM products p 
                          LEFT JOIN departments d ON p.department_id = d.id 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
                          WHERE $whereSql 
                          ORDER BY $orderBy", $params);

$totalStoreProducts = (int)(db_fetch_one("SELECT COUNT(*) as c FROM products")['c'] ?? 0);
?>

<?php if ($action === 'create' || $editProduct): ?>
  <!-- Breadcrumb & Page Header -->
  <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
      <div style="font-size: 0.8125rem; color: var(--admin-text-muted); margin-bottom: 0.25rem;">
        <a href="products.php" style="color: var(--link); text-decoration: none;">&larr; Products & Inventory</a> / <?= $editProduct ? 'Edit' : 'Create' ?>
      </div>
      <h2 style="font-size: 1.65rem; font-weight: 800; color: var(--heading); margin: 0;">
        <?= $editProduct ? 'Edit Product: ' . htmlspecialchars($editProduct['name']) : 'Add New Sacred Scripture or Item' ?>
      </h2>
      <p style="font-size: 0.875rem; color: var(--admin-text-muted); margin: 0.25rem 0 0;">
        Fill out all details below. Changes will immediately sync to the live store.
      </p>
    </div>
    <a href="products.php" class="admin-btn admin-btn-secondary admin-btn-sm">&larr; Back to Products</a>
  </div>

  <form method="POST" id="productEditForm" enctype="multipart/form-data" style="max-width: 900px; margin: 0 auto 3rem;">
    <?= csrf_field() ?>

    <!-- Card 1: Basic Information -->
    <div class="admin-card">
      <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--card-border);">
        <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(1, 23, 96, 0.08); color: var(--heading); display: flex; align-items: center; justify-content: center; font-size: 1rem; font-weight: 700;">
          1
        </div>
        <div>
          <h3 style="font-size: 1.15rem; font-weight: 700; margin: 0; color: var(--heading);">Basic Information</h3>
          <p style="font-size: 0.8125rem; color: var(--admin-text-muted); margin: 0;">Primary title, identification, and catalog labels</p>
        </div>
      </div>

      <div class="admin-form-group">
        <label class="admin-label admin-label-required" for="p-name">Product Title / Name</label>
        <input type="text" id="p-name" name="name" class="admin-input" placeholder="e.g. Shri Vachanamrut (Gujarati Hardcover), Pure Tulsi Kanthi 2-Round" value="<?= htmlspecialchars($editProduct['name'] ?? '') ?>" required>
      </div>

      <div class="admin-form-group">
        <label class="admin-label" for="p-sku">SKU Code <span class="admin-label-optional">(Optional)</span></label>
        <input type="text" id="p-sku" name="sku" class="admin-input" placeholder="e.g. SSK-BK-101" value="<?= htmlspecialchars($editProduct['sku'] ?? '') ?>">
        <div style="color: var(--admin-text-muted); font-size: 0.75rem; margin-top: 4px;">Leave blank to automatically generate an authentic SSK SKU code.</div>
      </div>
    </div>

    <!-- Card 2: Pricing & Inventory -->
    <div class="admin-card">
      <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--card-border);">
        <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(1, 23, 96, 0.08); color: var(--heading); display: flex; align-items: center; justify-content: center; font-size: 1rem; font-weight: 700;">
          2
        </div>
        <div>
          <h3 style="font-size: 1.15rem; font-weight: 700; margin: 0; color: var(--heading);">Pricing & Stock</h3>
          <p style="font-size: 0.8125rem; color: var(--admin-text-muted); margin: 0;">Regular price, sale offer, and temple warehouse stock</p>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
        <div class="admin-form-group">
          <label class="admin-label admin-label-required" for="p-price">Regular Price (₹)</label>
          <input type="number" step="0.01" min="0" id="p-price" name="price" class="admin-input" placeholder="0.00" value="<?= htmlspecialchars($editProduct['price'] ?? '') ?>" required>
        </div>

        <div class="admin-form-group">
          <label class="admin-label" for="p-sale">Discount Sale Price (₹) <span class="admin-label-optional">(Optional)</span></label>
          <input type="number" step="0.01" min="0" id="p-sale" name="sale_price" class="admin-input" placeholder="Leave empty if regular" value="<?= htmlspecialchars($editProduct['sale_price'] ?? '') ?>">
        </div>

        <div class="admin-form-group">
          <label class="admin-label admin-label-required" for="p-stock">Stock Quantity</label>
          <input type="number" min="0" id="p-stock" name="stock_qty" class="admin-input" placeholder="e.g. 50" value="<?= htmlspecialchars($editProduct['stock_qty'] ?? 100) ?>" required>
        </div>
      </div>
    </div>

    <!-- Card 3: Categorization & Status -->
    <div class="admin-card">
      <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--card-border);">
        <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(1, 23, 96, 0.08); color: var(--heading); display: flex; align-items: center; justify-content: center; font-size: 1rem; font-weight: 700;">
          3
        </div>
        <div>
          <h3 style="font-size: 1.15rem; font-weight: 700; margin: 0; color: var(--heading);">Category & Store Status</h3>
          <p style="font-size: 0.8125rem; color: var(--admin-text-muted); margin: 0;">Department taxonomy and storefront visibility flags</p>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
        <div class="admin-form-group">
          <label class="admin-label admin-label-required" for="p-dept">Department</label>
          <select id="p-dept" name="department_id" class="admin-select" onchange="filterProductTaxonomy()" required>
            <?php foreach ($departments as $d): ?>
              <option value="<?= $d['id'] ?>" <?= ($editProduct && $editProduct['department_id'] == $d['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($d['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="admin-form-group">
          <label class="admin-label admin-label-required" for="p-cat">Category</label>
          <select id="p-cat" name="category_id" class="admin-select" onchange="filterProductSubcategories()" required>
            <?php foreach ($categories as $c): ?>
              <option value="<?= $c['id'] ?>" data-dept-id="<?= $c['department_id'] ?>" <?= ($editProduct && $editProduct['category_id'] == $c['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="admin-form-group">
          <label class="admin-label" for="p-subcat">Subcategory</label>
          <select id="p-subcat" name="subcategory_id" class="admin-select">
            <option value="">None (Optional)</option>
            <?php foreach ($subcategories as $sc): ?>
              <option value="<?= $sc['id'] ?>" data-cat-id="<?= $sc['category_id'] ?>" <?= ($editProduct && ($editProduct['subcategory_id'] ?? null) == $sc['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($sc['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; padding: 1rem 1.25rem; background: var(--surface-2); border-radius: 12px; border: 1px solid var(--card-border);">
        <label style="display: flex; align-items: center; gap: 0.6rem; font-size: 0.875rem; cursor: pointer; font-weight: 600; color: var(--text);">
          <input type="checkbox" name="is_active" value="1" style="width: 18px; height: 18px; accent-color: var(--primary);" <?= (!$editProduct || $editProduct['is_active']) ? 'checked' : '' ?>>
          <span>Active (Publish to Store)</span>
        </label>
        <label style="display: flex; align-items: center; gap: 0.6rem; font-size: 0.875rem; cursor: pointer; color: var(--text);">
          <input type="checkbox" name="is_featured" value="1" style="width: 18px; height: 18px; accent-color: var(--primary);" <?= ($editProduct && $editProduct['is_featured']) ? 'checked' : '' ?>>
          <span>Featured on Homepage</span>
        </label>
        <label style="display: flex; align-items: center; gap: 0.6rem; font-size: 0.875rem; cursor: pointer; color: var(--text);">
          <input type="checkbox" name="on_sale" value="1" style="width: 18px; height: 18px; accent-color: var(--primary);" <?= ($editProduct && $editProduct['on_sale']) ? 'checked' : '' ?>>
          <span>Mark as Special Offer</span>
        </label>
      </div>
    </div>

    <!-- Card 4: Images & Visuals -->
    <div class="admin-card">
      <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--card-border);">
        <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(1, 23, 96, 0.08); color: var(--heading); display: flex; align-items: center; justify-content: center; font-size: 1rem; font-weight: 700;">
          4
        </div>
        <div>
          <h3 style="font-size: 1.15rem; font-weight: 700; margin: 0; color: var(--heading);">Images & Visuals</h3>
          <p style="font-size: 0.8125rem; color: var(--admin-text-muted); margin: 0;">Upload a product photo from your device, or choose from your existing photo library</p>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 180px; gap: 1.5rem; align-items: start;">
        <div>
          <!-- Drag & Drop Zone -->
          <div class="admin-dropzone" id="imageDropzone" onclick="document.getElementById('p-image-file').click()" title="Click to browse file">
            <input type="file" id="p-image-file" name="image_file" accept="image/png,image/jpeg,image/jpg,image/webp" style="display: none;" onchange="handleFileSelect(this)">
            
            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; pointer-events: none;">
              <div style="width: 48px; height: 48px; border-radius: 50%; background: rgba(1, 23, 96, 0.08); display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem; color: var(--heading);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                  <polyline points="17 8 12 3 7 8"></polyline>
                  <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
              </div>
              <div style="font-weight: 700; font-size: 0.9375rem; color: var(--heading); margin-bottom: 0.25rem;">
                Click to browse photo, or drag and drop here
              </div>
              <div style="font-size: 0.8125rem; color: var(--admin-text-muted);">
                Supports PNG, JPG, JPEG, WEBP (High-res product photos)
              </div>
            </div>
          </div>

          <!-- File upload / Library selection status message -->
          <div id="imageStatusBanner" style="display: none; margin-top: 0.75rem; padding: 0.65rem 0.9rem; border-radius: 8px; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); font-size: 0.8125rem; color: #065f46; align-items: center; justify-content: space-between; gap: 8px;">
            <div style="display: flex; align-items: center; gap: 8px;">
              <span id="imageStatusIcon" style="font-size: 1.1rem;">📷</span>
              <span id="imageStatusText">New image chosen</span>
            </div>
            <button type="button" onclick="clearSelectedImage()" style="background: none; border: none; color: #b91c1c; font-size: 0.8125rem; font-weight: 600; cursor: pointer; text-decoration: underline;">
              Remove / Reset
            </button>
          </div>

          <!-- Photo Library Picker Action Button -->
          <div style="margin-top: 0.85rem; display: flex; align-items: center; gap: 10px;">
            <div style="flex: 1; height: 1px; background: var(--card-border);"></div>
            <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--admin-text-muted); font-weight: 600;">OR</span>
            <div style="flex: 1; height: 1px; background: var(--card-border);"></div>
          </div>

          <button type="button" class="admin-btn admin-btn-secondary" onclick="openMediaLibraryModal()" style="width: 100%; margin-top: 0.85rem; display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 600;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
              <circle cx="8.5" cy="8.5" r="1.5"></circle>
              <polyline points="21 15 16 10 5 21"></polyline>
            </svg>
            Choose from Existing Photo Library (500+ Items)
          </button>

          <!-- Collapsible Advanced Filename Input (for Power Users) -->
          <details style="margin-top: 1rem; font-size: 0.8125rem;">
            <summary style="color: var(--admin-text-muted); cursor: pointer; user-select: none;">
              ⚙️ Advanced: View or manually edit image filename
            </summary>
            <div style="margin-top: 0.6rem; padding: 0.75rem; background: var(--surface-2); border-radius: 8px; border: 1px solid var(--card-border);">
              <label class="admin-label" for="p-image" style="font-size: 0.75rem; margin-bottom: 4px;">Image Filename in Database</label>
              <input type="text" id="p-image" name="image" class="admin-input" placeholder="e.g. vachanamrut-gujarati.jpg" value="<?= htmlspecialchars($editProduct['image'] ?? 'vachanamrut-english.jpg') ?>" oninput="updateImagePreview(this.value)">
              <div style="color: var(--admin-text-muted); font-size: 0.7188rem; margin-top: 4px;">
                Stored in <code>assets/images/products/</code> or <code>uploads/products/</code>
              </div>
            </div>
          </details>
        </div>

        <!-- Live Preview Card -->
        <div style="text-align: center;">
          <div style="font-size: 0.75rem; font-weight: 700; color: var(--admin-text-muted); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em;">
            Live Preview
          </div>
          <div style="width: 160px; height: 160px; border-radius: 14px; border: 2px solid var(--input-border); overflow: hidden; background: #ffffff; display: flex; align-items: center; justify-content: center; margin: 0 auto; box-shadow: var(--shadow-sm); position: relative;">
            <img id="imgPreview" src="<?= get_product_image_url($editProduct['image'] ?? 'vachanamrut-english.jpg', $editProduct['name'] ?? '') ?>" alt="Product Preview" style="width: 100%; height: 100%; object-fit: contain; padding: 6px;">
          </div>
          <div id="previewFilenameBadge" style="margin-top: 8px; font-size: 0.75rem; color: var(--admin-text-muted); word-break: break-all; max-width: 160px; margin-left: auto; margin-right: auto;">
            <?= htmlspecialchars($editProduct['image'] ?? 'vachanamrut-english.jpg') ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Card 5: Descriptions -->
    <div class="admin-card">
      <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--card-border);">
        <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(1, 23, 96, 0.08); color: var(--heading); display: flex; align-items: center; justify-content: center; font-size: 1rem; font-weight: 700;">
          5
        </div>
        <div>
          <h3 style="font-size: 1.15rem; font-weight: 700; margin: 0; color: var(--heading);">Description & Spiritual Significance</h3>
          <p style="font-size: 0.8125rem; color: var(--admin-text-muted); margin: 0;">Short summary for catalog cards and comprehensive details</p>
        </div>
      </div>

      <div class="admin-form-group">
        <label class="admin-label" for="p-sdesc">Short Subtitle / Summary</label>
        <input type="text" id="p-sdesc" name="short_description" class="admin-input" placeholder="Brief one-line summary (e.g. Authentic Gujarati hardcover with gold foil lettering)" value="<?= htmlspecialchars($editProduct['short_description'] ?? '') ?>">
      </div>

      <div class="admin-form-group" style="margin-bottom: 0;">
        <label class="admin-label" for="p-desc">Detailed Description</label>
        <textarea id="p-desc" name="description" rows="5" class="admin-textarea" placeholder="Comprehensive product details, publisher notes, chapters, material specifications..."><?= htmlspecialchars($editProduct['description'] ?? '') ?></textarea>
      </div>
    </div>

    <!-- Sticky Save / Cancel Action Bar with Unsaved Changes Warning -->
    <div class="admin-sticky-bar">
      <div style="display: flex; align-items: center; gap: 12px;">
        <a href="products.php" class="admin-btn admin-btn-secondary" id="cancelBtn">
          Cancel & Return
        </a>
        <div class="admin-unsaved-warning" id="unsavedBadge">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
          Unsaved Changes
        </div>
      </div>
      <div style="display: flex; align-items: center; gap: 12px;">
        <button type="submit" class="admin-btn admin-btn-primary" id="saveProductBtn" style="min-width: 160px;">
          <?= $editProduct ? '✓ Save Changes' : '+ Add Product to Store' ?>
        </button>
      </div>
    </div>

  </form>

  <!-- Visual Photo Library Picker Modal -->
  <div class="media-modal-backdrop" id="mediaModal" onclick="handleModalBackdropClick(event)">
    <div class="media-modal-box">
      <div class="media-modal-header">
        <div>
          <h3 style="font-size: 1.15rem; font-weight: 700; margin: 0; color: var(--heading);">SSK Product Photo Library</h3>
          <p style="font-size: 0.8125rem; color: var(--admin-text-muted); margin: 0.15rem 0 0;">
            Select any authentic photograph from the catalog library below.
          </p>
        </div>
        <button type="button" onclick="closeMediaLibraryModal()" class="admin-btn admin-btn-secondary admin-btn-sm" style="font-size: 1.25rem; line-height: 1; padding: 4px 10px;">&times;</button>
      </div>

      <!-- Search in Media Library -->
      <div style="padding: 0.85rem 1.5rem; border-bottom: 1px solid var(--admin-border); background: var(--surface-2); display: flex; gap: 10px; align-items: center;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--admin-text-muted); flex-shrink: 0;">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input type="text" id="mediaSearchInput" class="admin-input" placeholder="Type to filter photos (e.g. vachanamrut, mala, kanthi, mandir, shikshapatri)..." oninput="filterMediaLibrary(this.value)" style="margin: 0;">
        <span id="mediaItemCount" style="font-size: 0.8125rem; color: var(--admin-text-muted); white-space: nowrap;">Loading...</span>
      </div>

      <div class="media-modal-body">
        <div id="mediaGrid" class="media-grid">
          <div style="grid-column: 1 / -1; text-align: center; padding: 3rem 1rem; color: var(--admin-text-muted);">
            Loading photo library...
          </div>
        </div>
      </div>

      <div style="padding: 0.75rem 1.5rem; border-top: 1px solid var(--admin-border); background: var(--surface-2); display: flex; justify-content: flex-end; gap: 8px;">
        <button type="button" class="admin-btn admin-btn-secondary" onclick="closeMediaLibraryModal()">Cancel</button>
      </div>
    </div>
  </div>

  <script>
  let formIsDirty = false;
  const form = document.getElementById('productEditForm');
  const unsavedBadge = document.getElementById('unsavedBadge');

  if (form) {
    form.addEventListener('input', () => {
      formIsDirty = true;
      if (unsavedBadge) unsavedBadge.classList.add('active');
    });
    form.addEventListener('change', () => {
      formIsDirty = true;
      if (unsavedBadge) unsavedBadge.classList.add('active');
    });
    form.addEventListener('submit', () => {
      formIsDirty = false;
      const saveBtn = document.getElementById('saveProductBtn');
      if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.innerText = 'Saving...';
      }
    });
  }

  window.addEventListener('beforeunload', (e) => {
    if (formIsDirty) {
      e.preventDefault();
      e.returnValue = '';
    }
  });

  // --- Enhanced Image Upload & Media Library Logic ---
  const defaultInitialImage = "<?= htmlspecialchars($editProduct['image'] ?? 'vachanamrut-english.jpg') ?>";
  const defaultInitialUrl = "<?= get_product_image_url($editProduct['image'] ?? 'vachanamrut-english.jpg', $editProduct['name'] ?? '') ?>";
  let mediaLibraryCache = null;

  function updateImagePreview(fileName) {
    const preview = document.getElementById('imgPreview');
    const badge = document.getElementById('previewFilenameBadge');
    if (!preview) return;
    fileName = (fileName || '').trim();
    if (!fileName) return;

    if (badge) badge.innerText = fileName;

    if (fileName.startsWith('http://') || fileName.startsWith('https://') || fileName.startsWith('data:')) {
      preview.src = fileName;
    } else {
      preview.src = '../assets/images/products/' + fileName;
      preview.onerror = function() {
        this.src = '../uploads/products/' + fileName;
        this.onerror = function() {
          this.src = '../assets/images/placeholder.php?title=' + encodeURIComponent(fileName);
        };
      };
    }
  }

  function handleFileSelect(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];

    if (file.size > 10 * 1024 * 1024) {
      alert('Selected file is too large. Please select an image under 10MB.');
      input.value = '';
      return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
      const preview = document.getElementById('imgPreview');
      if (preview) preview.src = e.target.result;

      const banner = document.getElementById('imageStatusBanner');
      const text = document.getElementById('imageStatusText');
      const badge = document.getElementById('previewFilenameBadge');

      const sizeStr = file.size > 1024 * 1024
        ? (file.size / (1024 * 1024)).toFixed(1) + ' MB'
        : Math.round(file.size / 1024) + ' KB';

      if (text) text.innerHTML = '<strong>' + escapeHtml(file.name) + '</strong> (' + sizeStr + ') ready to upload';
      if (banner) banner.style.display = 'flex';
      if (badge) badge.innerText = file.name;

      formIsDirty = true;
      if (unsavedBadge) unsavedBadge.classList.add('active');
    };
    reader.readAsDataURL(file);
  }

  function clearSelectedImage() {
    const fileInput = document.getElementById('p-image-file');
    if (fileInput) fileInput.value = '';

    const banner = document.getElementById('imageStatusBanner');
    if (banner) banner.style.display = 'none';

    const pImage = document.getElementById('p-image');
    if (pImage) pImage.value = defaultInitialImage;

    const preview = document.getElementById('imgPreview');
    if (preview) preview.src = defaultInitialUrl;

    const badge = document.getElementById('previewFilenameBadge');
    if (badge) badge.innerText = defaultInitialImage;
  }

  // Setup Drag and Drop listeners
  const dropzone = document.getElementById('imageDropzone');
  if (dropzone) {
    ['dragenter', 'dragover'].forEach(eventName => {
      dropzone.addEventListener(eventName, (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.add('dragover');
      }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
      dropzone.addEventListener(eventName, (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.remove('dragover');
      }, false);
    });

    dropzone.addEventListener('drop', (e) => {
      const dt = e.dataTransfer;
      if (dt && dt.files && dt.files.length) {
        const fileInput = document.getElementById('p-image-file');
        fileInput.files = dt.files;
        handleFileSelect(fileInput);
      }
    }, false);
  }

  function openMediaLibraryModal() {
    const modal = document.getElementById('mediaModal');
    if (!modal) return;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    if (!mediaLibraryCache) {
      loadMediaLibrary();
    } else {
      renderMediaGrid(mediaLibraryCache);
    }
  }

  function closeMediaLibraryModal() {
    const modal = document.getElementById('mediaModal');
    if (!modal) return;
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }

  function handleModalBackdropClick(e) {
    if (e.target && e.target.id === 'mediaModal') {
      closeMediaLibraryModal();
    }
  }

  async function loadMediaLibrary() {
    const grid = document.getElementById('mediaGrid');
    const countEl = document.getElementById('mediaItemCount');
    try {
      const res = await fetch('products.php?api=media_library');
      if (!res.ok) throw new Error('Failed to load media library');
      const data = await res.json();
      mediaLibraryCache = data;
      renderMediaGrid(data);
    } catch (err) {
      console.error(err);
      if (grid) grid.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; color: #b91c1c; padding: 2rem;">Failed to load photos. Please try again.</div>';
    }
  }

  function renderMediaGrid(items) {
    const grid = document.getElementById('mediaGrid');
    const countEl = document.getElementById('mediaItemCount');
    if (!grid) return;

    if (countEl) countEl.innerText = items.length + ' photos';

    if (!items || items.length === 0) {
      grid.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; color: var(--admin-text-muted); padding: 3rem 1rem;">No matching photographs found.</div>';
      return;
    }

    const currentVal = (document.getElementById('p-image')?.value || '').toLowerCase();

    let html = '';
    items.forEach(item => {
      const isSelected = (item.filename.toLowerCase() === currentVal);
      html += `
        <div class="media-thumb-card ${isSelected ? 'selected' : ''}" onclick="selectLibraryImage('${escapeHtml(item.filename)}', '${escapeHtml(item.url)}')">
          <img src="${escapeHtml(item.url)}" alt="${escapeHtml(item.name)}" class="media-thumb-img" loading="lazy">
          <div class="media-thumb-title" title="${escapeHtml(item.filename)}">${escapeHtml(item.filename)}</div>
        </div>
      `;
    });
    grid.innerHTML = html;
  }

  function filterMediaLibrary(query) {
    if (!mediaLibraryCache) return;
    query = (query || '').toLowerCase().trim();
    if (!query) {
      renderMediaGrid(mediaLibraryCache);
      return;
    }
    const filtered = mediaLibraryCache.filter(item => {
      return item.filename.toLowerCase().includes(query) || item.name.toLowerCase().includes(query);
    });
    renderMediaGrid(filtered);
  }

  function selectLibraryImage(filename, url) {
    const fileInput = document.getElementById('p-image-file');
    if (fileInput) fileInput.value = '';

    const pImage = document.getElementById('p-image');
    if (pImage) pImage.value = filename;

    const preview = document.getElementById('imgPreview');
    if (preview) preview.src = url;

    const badge = document.getElementById('previewFilenameBadge');
    if (badge) badge.innerText = filename;

    const banner = document.getElementById('imageStatusBanner');
    const text = document.getElementById('imageStatusText');
    if (text) text.innerHTML = 'Selected from library: <strong>' + escapeHtml(filename) + '</strong>';
    if (banner) banner.style.display = 'flex';

    formIsDirty = true;
    if (unsavedBadge) unsavedBadge.classList.add('active');

    closeMediaLibraryModal();
  }

  function escapeHtml(str) {
    return String(str || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function filterProductTaxonomy() {
    const deptSelect = document.getElementById('p-dept');
    const catSelect = document.getElementById('p-cat');
    if (!deptSelect || !catSelect) return;
    const selectedDept = deptSelect.value;
    let firstVisibleCat = null;
    let hasSelected = false;

    Array.from(catSelect.options).forEach(opt => {
      const deptId = opt.getAttribute('data-dept-id');
      if (!deptId || deptId === selectedDept) {
        opt.hidden = false;
        opt.disabled = false;
        if (!firstVisibleCat) firstVisibleCat = opt;
        if (opt.selected) hasSelected = true;
      } else {
        opt.hidden = true;
        opt.disabled = true;
        if (opt.selected) opt.selected = false;
      }
    });

    if (!hasSelected && firstVisibleCat) {
      firstVisibleCat.selected = true;
    }
    filterProductSubcategories();
  }

  function filterProductSubcategories() {
    const catSelect = document.getElementById('p-cat');
    const subcatSelect = document.getElementById('p-subcat');
    if (!catSelect || !subcatSelect) return;
    const selectedCat = catSelect.value;
    let hasSelected = false;

    Array.from(subcatSelect.options).forEach(opt => {
      const catId = opt.getAttribute('data-cat-id');
      if (!catId) {
        opt.hidden = false;
        opt.disabled = false;
        if (opt.selected) hasSelected = true;
      } else if (catId === selectedCat) {
        opt.hidden = false;
        opt.disabled = false;
        if (opt.selected) hasSelected = true;
      } else {
        opt.hidden = true;
        opt.disabled = true;
        if (opt.selected) opt.selected = false;
      }
    });

    if (!hasSelected) {
      subcatSelect.value = '';
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('p-dept')) {
      filterProductTaxonomy();
    }
  });
  </script>

<?php else: ?>

  <!-- Advanced Product Filter & Search Toolbar -->
  <div class="filter-toolbar">
    
    <div class="filter-row-top">
      
      <!-- Live Search Box with Real-time Instant Filter & Clear Button -->
      <div class="search-box-wrap">
        <svg class="search-icon-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input type="text" id="liveProductSearch" class="admin-input" placeholder="Easy search: type product name, SKU, or keyword..." value="<?= htmlspecialchars($searchQ) ?>" autocomplete="off">
        <button type="button" id="clearSearchBtn" class="search-clear-btn" title="Clear search">&times;</button>
      </div>

      <!-- Action Buttons -->
      <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <a href="bulk_import.php" class="admin-btn admin-btn-secondary" title="Import multiple products via CSV with images">
          📥 Bulk CSV Import
        </a>
        <a href="bulk_import.php?action=export" class="admin-btn admin-btn-secondary" title="Export current catalog to CSV">
          📤 Export CSV
        </a>
        <a href="products.php?action=create" class="admin-btn admin-btn-primary">
          + Add New Product
        </a>
      </div>

    </div>

    <!-- Dropdown Filters Row -->
    <div class="filter-row-controls">
      
      <!-- Department Filter -->
      <select id="filterDept" class="filter-select" onchange="applyServerFilters()">
        <option value="">All Departments (<?= count($departments) ?>)</option>
        <?php foreach ($departments as $d): ?>
          <option value="<?= $d['id'] ?>" <?= $filterDept === (int)$d['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($d['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <!-- Stock Status Filter -->
      <select id="filterStock" class="filter-select" onchange="applyServerFilters()">
        <option value="">All Stock Levels</option>
        <option value="in_stock" <?= $filterStock === 'in_stock' ? 'selected' : '' ?>>In Stock (&gt;15)</option>
        <option value="low_stock" <?= $filterStock === 'low_stock' ? 'selected' : '' ?>>Low Stock (1–15)</option>
        <option value="out_of_stock" <?= $filterStock === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock (0)</option>
      </select>

      <!-- Active / Draft Status Filter -->
      <select id="filterStatus" class="filter-select" onchange="applyServerFilters()">
        <option value="">All Statuses</option>
        <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Active Only</option>
        <option value="draft" <?= $filterStatus === 'draft' ? 'selected' : '' ?>>Draft / Inactive</option>
      </select>

      <!-- Sort By -->
      <select id="filterSort" class="filter-select" onchange="applyServerFilters()">
        <option value="id_desc" <?= $sortBy === 'id_desc' ? 'selected' : '' ?>>Sort: Newest First</option>
        <option value="name_asc" <?= $sortBy === 'name_asc' ? 'selected' : '' ?>>Sort: Name (A to Z)</option>
        <option value="price_asc" <?= $sortBy === 'price_asc' ? 'selected' : '' ?>>Sort: Price (Low to High)</option>
        <option value="price_desc" <?= $sortBy === 'price_desc' ? 'selected' : '' ?>>Sort: Price (High to Low)</option>
        <option value="stock_asc" <?= $sortBy === 'stock_asc' ? 'selected' : '' ?>>Sort: Stock (Low to High)</option>
      </select>

      <!-- Reset Filters Link -->
      <?php if ($searchQ || $filterDept || $filterStock || $filterStatus || $sortBy !== 'id_desc'): ?>
        <a href="products.php" class="admin-btn admin-btn-secondary admin-btn-sm" style="color: #ef4444;">
          ✕ Reset Filters
        </a>
      <?php endif; ?>

      <!-- Results Count Badge -->
      <span id="filterCountBadge" class="filter-count-badge" style="margin-left: auto;">
        Showing <?= count($products) ?> of <?= $totalStoreProducts ?> products
      </span>

    </div>

  </div>

  <!-- Product Listing Table Card -->
  <div class="admin-card" style="padding: 0; overflow: hidden;">
    
    <?php if (empty($products)): ?>
      <div class="admin-empty-state">
        <div class="admin-empty-state-icon">📚</div>
        <h4 style="font-size: 1.15rem; font-weight: 700; color: var(--admin-text); margin-bottom: 0.35rem;">
          <?= $totalStoreProducts === 0 ? 'No Products in Catalog Yet' : 'No Products Match Your Filter' ?>
        </h4>
        <p style="color: var(--admin-text-muted); font-size: 0.875rem; max-width: 440px; margin: 0 auto 1.25rem;">
          <?= $totalStoreProducts === 0 
              ? 'All demo items have been cleared. Upload your sacred scriptures, tulsi kanthis, carved ghar mandirs, and puja items now!' 
              : 'Try clearing your search query or choosing "All Departments" to see all products.' ?>
        </p>
        <div style="display: flex; gap: 0.5rem; justify-content: center;">
          <?php if ($totalStoreProducts > 0): ?>
            <a href="products.php" class="admin-btn admin-btn-secondary">Clear All Filters</a>
          <?php endif; ?>
          <a href="bulk_import.php" class="admin-btn admin-btn-secondary">📥 Bulk Import via CSV</a>
          <a href="products.php?action=create" class="admin-btn admin-btn-primary">+ Add New Product</a>
        </div>
      </div>
    <?php else: ?>
      <div class="table-responsive" style="overflow-x: auto; width: 100%; -webkit-overflow-scrolling: touch;">
        <table class="admin-table" id="productsTable">
          <thead>
            <tr>
              <th style="width: 60px;">Image</th>
              <th>Product Name & Details</th>
              <th>Department / Category</th>
              <th>Price</th>
              <th>Stock</th>
              <th>Status</th>
              <th style="text-align: right;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($products as $p): ?>
              <tr class="product-row" 
                  data-name="<?= htmlspecialchars(strtolower($p['name'])) ?>" 
                  data-sku="<?= htmlspecialchars(strtolower($p['sku'] ?? '')) ?>" 
                  data-dept="<?= $p['department_id'] ?>" 
                  data-stock="<?= (int)$p['stock_qty'] ?>" 
                  data-status="<?= $p['is_active'] ? 'active' : 'draft' ?>">
                <td>
                  <img src="<?= get_product_image_url($p['image'], $p['name']) ?>" style="width: 46px; height: 46px; border-radius: 8px; object-fit: cover; border: 1px solid var(--admin-border);">
                </td>
                <td>
                  <div style="font-weight: 700; color: var(--admin-text); font-size: 0.95rem;">
                    <a href="products.php?edit=<?= $p['id'] ?>" style="color: inherit; text-decoration: none;" onmouseover="this.style.color='var(--admin-primary)'" onmouseout="this.style.color='inherit'">
                      <?= htmlspecialchars($p['name']) ?>
                    </a>
                  </div>
                  <div style="font-size: 0.75rem; color: var(--admin-text-muted); margin-top: 0.15rem;">
                    SKU: <strong><?= htmlspecialchars($p['sku'] ?? 'N/A') ?></strong>
                    <?php if (!empty($p['short_description'])): ?>
                      • <?= htmlspecialchars(substr($p['short_description'], 0, 50)) ?>...
                    <?php endif; ?>
                  </div>
                </td>
                <td>
                  <div style="font-size: 0.8125rem; font-weight: 700; color: var(--admin-text);">
                    <?= htmlspecialchars($p['dept_name'] ?? 'General') ?>
                  </div>
                  <div style="font-size: 0.75rem; color: var(--admin-text-muted);">
                    <?= htmlspecialchars($p['cat_name'] ?? 'Standard') ?>
                  </div>
                </td>
                <td>
                  <div style="font-weight: 800; color: var(--admin-primary); font-size: 0.95rem;">
                    <?= format_price($p['sale_price'] ?: $p['price']) ?>
                  </div>
                  <?php if ($p['sale_price']): ?>
                    <div style="font-size: 0.75rem; text-decoration: line-through; color: var(--admin-text-muted);">
                      <?= format_price($p['price']) ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($p['stock_qty'] <= 0): ?>
                    <span style="font-weight: 700; color: #dc2626; font-size: 0.8125rem; background: #fee2e2; padding: 0.2rem 0.5rem; border-radius: 4px;">
                      Out of Stock
                    </span>
                  <?php elseif ($p['stock_qty'] <= 15): ?>
                    <span style="font-weight: 700; color: #d97706; font-size: 0.8125rem; background: #fef3c7; padding: 0.2rem 0.5rem; border-radius: 4px;">
                      <?= $p['stock_qty'] ?> (Low)
                    </span>
                  <?php else: ?>
                    <span style="font-weight: 700; color: #16a34a; font-size: 0.8125rem;">
                      <?= $p['stock_qty'] ?> in stock
                    </span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="status-pill <?= $p['is_active'] ? 'status-delivered' : 'status-cancelled' ?>">
                    <?= $p['is_active'] ? 'Active' : 'Draft' ?>
                  </span>
                </td>
                <td style="text-align: right;">
                  <div style="display: inline-flex; gap: 0.4rem;">
                    <a href="../product.php?id=<?= $p['id'] ?>" target="_blank" class="admin-btn admin-btn-secondary admin-btn-sm" title="View on Live Store">
                      👁️
                    </a>
                    <a href="products.php?edit=<?= $p['id'] ?>" class="admin-btn admin-btn-secondary admin-btn-sm" title="Edit Product">
                      Edit
                    </a>
                    <form method="POST" action="products.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="delete_product">
                      <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                      <button type="submit" class="admin-btn admin-btn-danger admin-btn-sm" title="Delete Product">Del</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            
            <!-- Real-time dynamic No Match row (controlled via JS) -->
            <tr id="noMatchRow" style="display: none;">
              <td colspan="7" style="text-align: center; padding: 2rem; color: var(--admin-text-muted);">
                <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">🔍</div>
                <strong>No products matched your live search term.</strong>
                <div style="margin-top: 0.35rem;">Check your spelling or clear the search input.</div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

  </div>

  <script>
  function applyServerFilters() {
    const dept = document.getElementById('filterDept').value;
    const stock = document.getElementById('filterStock').value;
    const status = document.getElementById('filterStatus').value;
    const sort = document.getElementById('filterSort').value;
    const q = document.getElementById('liveProductSearch').value;

    const params = new URLSearchParams();
    if (q) params.set('q', q);
    if (dept) params.set('dept_id', dept);
    if (stock) params.set('stock', stock);
    if (status) params.set('status', status);
    if (sort) params.set('sort', sort);

    window.location.href = 'products.php?' + params.toString();
  }
  </script>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
