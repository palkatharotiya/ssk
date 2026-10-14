<?php
/**
 * SSK Store - User-Friendly Departments & Categories Management
 * Shree Sahajanand Sahitya Kendra (Kalakunj Mandir)
 */

require_once __DIR__ . '/includes/admin_bootstrap.php';
admin_require_auth('categories');
$adminPageTitle = 'Departments & Categories';

// Handle Add / Edit Department POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_dept'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Security token expired. Please refresh and try again.');
        header('Location: categories.php');
        exit;
    }
    $deptId = (int)($_POST['dept_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $sortOrder = (int)($_POST['sort_order'] ?? 10);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $slug = generate_unique_slug($name, 'departments', $deptId);

    if ($name) {
        if ($deptId > 0) {
            db_query("UPDATE departments SET name = ?, slug = ?, description = ?, sort_order = ?, is_active = ? WHERE id = ?", [
                $name, $slug, $desc, $sortOrder, $isActive, $deptId
            ]);
            set_flash('success', "Department '$name' updated successfully.");
        } else {
            db_query("INSERT INTO departments (name, slug, description, sort_order, is_active) VALUES (?, ?, ?, ?, ?)", [
                $name, $slug, $desc, $sortOrder, $isActive
            ]);
            set_flash('success', "New department '$name' created successfully.");
        }
        header('Location: categories.php');
        exit;
    }
}

// Handle Add / Edit Category POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_cat'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Security token expired. Please refresh and try again.');
        header('Location: categories.php');
        exit;
    }
    $catId = (int)($_POST['cat_id'] ?? 0);
    $deptId = (int)($_POST['department_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $sortOrder = (int)($_POST['sort_order'] ?? 10);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $slug = generate_unique_slug($name, 'categories', $catId);

    if ($name && $deptId > 0) {
        if ($catId > 0) {
            db_query("UPDATE categories SET department_id = ?, name = ?, slug = ?, description = ?, sort_order = ?, is_active = ? WHERE id = ?", [
                $deptId, $name, $slug, $desc, $sortOrder, $isActive, $catId
            ]);
            set_flash('success', "Category '$name' updated successfully.");
        } else {
            db_query("INSERT INTO categories (department_id, name, slug, description, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)", [
                $deptId, $name, $slug, $desc, $sortOrder, $isActive
            ]);
            set_flash('success', "New category '$name' created successfully.");
        }
        header('Location: categories.php');
        exit;
    }
}

// Handle Delete Department via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_dept'])) {
    require_csrf();
    $delDeptId = (int)$_POST['dept_id'];
    $prodCount = (int)(db_fetch_one("SELECT COUNT(*) as c FROM products WHERE department_id = ?", [$delDeptId])['c'] ?? 0);
    if ($prodCount > 0) {
        set_flash('error', "Cannot delete department: It currently contains $prodCount products. Please reassign or delete the products first.");
    } else {
        db_query("DELETE FROM categories WHERE department_id = ?", [$delDeptId]);
        db_query("DELETE FROM departments WHERE id = ?", [$delDeptId]);
        set_flash('success', "Department and its subcategories were removed.");
    }
    header('Location: categories.php');
    exit;
}

// Handle Delete Category via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_cat'])) {
    require_csrf();
    $delCatId = (int)$_POST['cat_id'];
    $prodCount = (int)(db_fetch_one("SELECT COUNT(*) as c FROM products WHERE category_id = ?", [$delCatId])['c'] ?? 0);
    if ($prodCount > 0) {
        set_flash('error', "Cannot delete category: It currently contains $prodCount products.");
    } else {
        db_query("DELETE FROM categories WHERE id = ?", [$delCatId]);
        set_flash('success', "Category removed successfully.");
    }
    header('Location: categories.php');
    exit;
}

// Handle Add / Edit Subcategory POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_subcat'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Security token expired. Please refresh and try again.');
        header('Location: categories.php');
        exit;
    }
    $subcatId = (int)($_POST['subcat_id'] ?? 0);
    $catId = (int)($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $sortOrder = (int)($_POST['sort_order'] ?? 10);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $slug = generate_unique_slug($name, 'subcategories', $subcatId);

    if ($name && $catId > 0) {
        if ($subcatId > 0) {
            db_query("UPDATE subcategories SET category_id = ?, name = ?, slug = ?, description = ?, sort_order = ?, is_active = ? WHERE id = ?", [
                $catId, $name, $slug, $desc, $sortOrder, $isActive, $subcatId
            ]);
            set_flash('success', "Subcategory '$name' updated successfully.");
        } else {
            db_query("INSERT INTO subcategories (category_id, name, slug, description, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)", [
                $catId, $name, $slug, $desc, $sortOrder, $isActive
            ]);
            set_flash('success', "New subcategory '$name' created successfully.");
        }
        header('Location: categories.php');
        exit;
    }
}

// Handle Delete Subcategory via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_subcat'])) {
    require_csrf();
    $delSubId = (int)$_POST['subcat_id'];
    $prodCount = (int)(db_fetch_one("SELECT COUNT(*) as c FROM products WHERE subcategory_id = ?", [$delSubId])['c'] ?? 0);
    if ($prodCount > 0) {
        db_query("UPDATE products SET subcategory_id = NULL WHERE subcategory_id = ?", [$delSubId]);
    }
    db_query("DELETE FROM subcategories WHERE id = ?", [$delSubId]);
    set_flash('success', "Subcategory removed successfully (unlinked from $prodCount product(s)).");
    header('Location: categories.php');
    exit;
}

// Check for edit query params
$editDept = null;
if (isset($_GET['edit_dept'])) {
    $editDept = db_fetch_one("SELECT * FROM departments WHERE id = ?", [(int)$_GET['edit_dept']]);
}

$editCat = null;
if (isset($_GET['edit_cat'])) {
    $editCat = db_fetch_one("SELECT * FROM categories WHERE id = ?", [(int)$_GET['edit_cat']]);
}

$editSubcat = null;
if (isset($_GET['edit_subcat'])) {
    $editSubcat = db_fetch_one("SELECT * FROM subcategories WHERE id = ?", [(int)$_GET['edit_subcat']]);
}

// Fetch all departments with their categories & product counts
$departments = db_fetch_all("SELECT d.*, 
                                    COUNT(DISTINCT c.id) as category_count,
                                    COUNT(DISTINCT p.id) as product_count 
                             FROM departments d 
                             LEFT JOIN categories c ON d.id = c.department_id 
                             LEFT JOIN products p ON d.id = p.department_id 
                             GROUP BY d.id 
                             ORDER BY d.sort_order ASC, d.name ASC");

// Fetch all categories indexed by department_id
$rawCats = db_fetch_all("SELECT c.*, COUNT(p.id) as product_count 
                         FROM categories c 
                         LEFT JOIN products p ON c.id = p.category_id 
                         GROUP BY c.id 
                         ORDER BY c.sort_order ASC, c.name ASC");

$catsByDept = [];
$allCategoriesFlat = [];
foreach ($rawCats as $rc) {
    $catsByDept[$rc['department_id']][] = $rc;
    $allCategoriesFlat[] = $rc;
}

// Fetch all subcategories indexed by category_id
$rawSubcats = db_fetch_all("SELECT sc.*, COUNT(p.id) as product_count 
                            FROM subcategories sc 
                            LEFT JOIN products p ON sc.id = p.subcategory_id 
                            GROUP BY sc.id 
                            ORDER BY sc.sort_order ASC, sc.name ASC");

$subcatsByCat = [];
foreach ($rawSubcats as $rsc) {
    $subcatsByCat[$rsc['category_id']][] = $rsc;
}

// Dept icons helper
$deptIcons = [
    'books' => '📖',
    'kanthis' => '📿',
    'japamalas' => '📿',
    'ghar-mandirs' => '🏛️',
    'puja-items' => '🪔',
    'murtis' => '🕉️',
    'agarbatti-dhoop' => '🌸',
    'clothing' => '👘',
    'calendars-diaries' => '📅',
];
require_once __DIR__ . '/includes/header.php';
?>

<!-- Action Bar & Live Search -->
<div class="filter-toolbar" style="margin-bottom: 1.5rem;">
  <div class="filter-row-top">
    
    <!-- Instant Search across all Departments and Categories -->
    <div class="search-box-wrap" style="max-width: 420px;">
      <svg class="search-icon-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
      </svg>
      <input type="text" id="categoryLiveSearch" class="admin-input" placeholder="Type to filter departments & categories..." autocomplete="off">
    </div>

    <!-- Quick Action Buttons -->
    <div style="display: flex; gap: 0.65rem; flex-wrap: wrap;">
      <button type="button" onclick="toggleDeptForm()" class="admin-btn admin-btn-primary">
        + Add New Department
      </button>
      <button type="button" onclick="openCatModal(0)" class="admin-btn admin-btn-secondary">
        + Add Category
      </button>
      <button type="button" onclick="openSubcatModal(0)" class="admin-btn admin-btn-secondary">
        + Add Subcategory
      </button>
    </div>

  </div>
</div>

<!-- Add / Edit Department Card Form (Toggled or active if editing) -->
<div id="deptFormCard" class="admin-card" style="display: <?= $editDept ? 'block' : 'none' ?>; background: var(--surface-2); border: 1.5px solid var(--card-border); margin-bottom: 2rem;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
    <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--heading);">
      <?= $editDept ? 'Edit Department: ' . htmlspecialchars($editDept['name']) : 'Create New Department' ?>
    </h3>
    <button type="button" onclick="toggleDeptForm()" class="admin-btn admin-btn-secondary admin-btn-sm">&times; Close</button>
  </div>

  <form method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="save_dept" value="1">
    <input type="hidden" name="dept_id" value="<?= $editDept['id'] ?? 0 ?>">

    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
      <div>
        <label class="admin-label admin-label-required">Department Name</label>
        <input type="text" name="name" class="admin-input" placeholder="e.g. Sacred Scriptures & Literature" value="<?= htmlspecialchars($editDept['name'] ?? '') ?>" required>
      </div>
      <div>
        <label class="admin-label">Sort Order</label>
        <input type="number" name="sort_order" class="admin-input" value="<?= htmlspecialchars($editDept['sort_order'] ?? 10) ?>">
      </div>
      <div>
        <label class="admin-label">Status</label>
        <label style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.6rem; cursor: pointer;">
          <input type="checkbox" name="is_active" value="1" <?= (!$editDept || $editDept['is_active']) ? 'checked' : '' ?>>
          <strong>Active</strong>
        </label>
      </div>
    </div>

    <div style="margin-bottom: 1rem;">
      <label class="admin-label">Description <span class="admin-label-optional">(Optional)</span></label>
      <input type="text" name="description" class="admin-input" placeholder="Short description of items in this department" value="<?= htmlspecialchars($editDept['description'] ?? '') ?>">
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
      <button type="button" onclick="toggleDeptForm()" class="admin-btn admin-btn-secondary">Cancel</button>
      <button type="submit" class="admin-btn admin-btn-primary">
        <?= $editDept ? 'Update Department' : 'Create Department' ?>
      </button>
    </div>
  </form>
</div>

<!-- Add / Edit Category Card Form (Toggled or active if editing) -->
<div id="catFormCard" class="admin-card" style="display: <?= $editCat ? 'block' : 'none' ?>; background: var(--surface-2); border: 1.5px solid var(--card-border); margin-bottom: 2rem;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
    <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--heading);">
      <?= $editCat ? 'Edit Category: ' . htmlspecialchars($editCat['name']) : 'Add Category to Department' ?>
    </h3>
    <button type="button" onclick="closeCatModal()" class="admin-btn admin-btn-secondary admin-btn-sm">&times; Close</button>
  </div>

  <form method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="save_cat" value="1">
    <input type="hidden" name="cat_id" id="modalCatId" value="<?= $editCat['id'] ?? 0 ?>">

    <div style="display: grid; grid-template-columns: 1fr 1fr 100px 100px; gap: 1rem; margin-bottom: 1rem;">
      <div>
        <label class="admin-label">Parent Department *</label>
        <select name="department_id" id="modalDeptSelect" class="admin-select" required>
          <?php foreach ($departments as $d): ?>
            <option value="<?= $d['id'] ?>" <?= ($editCat && $editCat['department_id'] == $d['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($d['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="admin-label">Category Name *</label>
        <input type="text" name="name" id="modalCatName" class="admin-input" placeholder="e.g. Gujarati Books, Plated Kanthi" value="<?= htmlspecialchars($editCat['name'] ?? '') ?>" required>
      </div>
      <div>
        <label class="admin-label">Sort Order</label>
        <input type="number" name="sort_order" class="admin-input" value="<?= htmlspecialchars($editCat['sort_order'] ?? 10) ?>">
      </div>
      <div>
        <label class="admin-label">Status</label>
        <label style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.6rem; cursor: pointer;">
          <input type="checkbox" name="is_active" value="1" <?= (!$editCat || !empty($editCat['is_active'])) ? 'checked' : '' ?>>
          <strong>Active</strong>
        </label>
      </div>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
      <button type="button" onclick="closeCatModal()" class="admin-btn admin-btn-secondary">Cancel</button>
      <button type="submit" class="admin-btn admin-btn-primary">
        <?= $editCat ? 'Update Category' : '+ Add Category' ?>
      </button>
    </div>
  </form>
</div>

<!-- Add / Edit Subcategory Card Form (Toggled or active if editing) -->
<div id="subcatFormCard" class="admin-card" style="display: <?= $editSubcat ? 'block' : 'none' ?>; background: var(--surface-2); border: 1.5px solid var(--card-border); margin-bottom: 2rem;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
    <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--heading);">
      <?= $editSubcat ? 'Edit Subcategory: ' . htmlspecialchars($editSubcat['name']) : 'Add Subcategory under Category' ?>
    </h3>
    <button type="button" onclick="closeSubcatModal()" class="admin-btn admin-btn-secondary admin-btn-sm">&times; Close</button>
  </div>

  <form method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="save_subcat" value="1">
    <input type="hidden" name="subcat_id" id="modalSubcatId" value="<?= $editSubcat['id'] ?? 0 ?>">

    <div style="display: grid; grid-template-columns: 1fr 1fr 100px 100px; gap: 1rem; margin-bottom: 1rem;">
      <div>
        <label class="admin-label">Parent Category *</label>
        <select name="category_id" id="modalSubcatCatSelect" class="admin-select" required>
          <?php foreach ($allCategoriesFlat as $cf): ?>
            <option value="<?= $cf['id'] ?>" <?= ($editSubcat && $editSubcat['category_id'] == $cf['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($cf['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="admin-label">Subcategory Name *</label>
        <input type="text" name="name" id="modalSubcatName" class="admin-input" placeholder="e.g. Vachanamrut (Gujarati)" value="<?= htmlspecialchars($editSubcat['name'] ?? '') ?>" required>
      </div>
      <div>
        <label class="admin-label">Sort Order</label>
        <input type="number" name="sort_order" class="admin-input" value="<?= htmlspecialchars($editSubcat['sort_order'] ?? 10) ?>">
      </div>
      <div>
        <label class="admin-label">Status</label>
        <label style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.6rem; cursor: pointer;">
          <input type="checkbox" name="is_active" value="1" <?= (!$editSubcat || !empty($editSubcat['is_active'])) ? 'checked' : '' ?>>
          <strong>Active</strong>
        </label>
      </div>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
      <button type="button" onclick="closeSubcatModal()" class="admin-btn admin-btn-secondary">Cancel</button>
      <button type="submit" class="admin-btn admin-btn-primary">
        <?= $editSubcat ? 'Update Subcategory' : '+ Add Subcategory' ?>
      </button>
    </div>
  </form>
</div>

<!-- Hierarchical Departments & Categories Listing -->
<div class="departments-container">
  
  <?php foreach ($departments as $d): 
    $dId = $d['id'];
    $subCats = $catsByDept[$dId] ?? [];
    $icon = $deptIcons[$d['slug']] ?? '📦';
  ?>
    <div class="category-tree-card" data-dept-name="<?= htmlspecialchars($d['name']) ?>">
      
      <!-- Department Header -->
      <div class="dept-header">
        <div class="dept-title-group">
          <div class="dept-icon-badge">
            <?= $icon ?>
          </div>
          <div>
            <div style="font-size: 1.15rem; font-weight: 800; color: var(--admin-text); display: flex; align-items: center; gap: 0.5rem;">
              <?= htmlspecialchars($d['name']) ?>
              <span style="font-size: 0.75rem; font-weight: 700; color: #16a34a; background: #dcfce7; padding: 0.15rem 0.5rem; border-radius: 999px;">
                <?= count($subCats) ?> Categories
              </span>
              <span style="font-size: 0.75rem; font-weight: 700; color: #2563eb; background: #eff6ff; padding: 0.15rem 0.5rem; border-radius: 999px;">
                <?= $d['product_count'] ?> Products
              </span>
            </div>
            <div style="font-size: 0.75rem; color: var(--admin-text-muted); margin-top: 0.15rem;">
              Slug: <code style="background: #f1f5f9; padding: 0.1rem 0.35rem; border-radius: 4px;"><?= htmlspecialchars($d['slug']) ?></code> • Sort Order: <?= $d['sort_order'] ?>
            </div>
          </div>
        </div>

        <div style="display: flex; align-items: center; gap: 0.5rem;">
          <button type="button" onclick="openCatModal(<?= $dId ?>)" class="admin-btn admin-btn-secondary admin-btn-sm" title="Add new category directly under this department">
            + Add Category
          </button>
          <a href="categories.php?edit_dept=<?= $dId ?>" class="admin-btn admin-btn-secondary admin-btn-sm" title="Edit department">
            Edit
          </a>
          <form method="POST" action="categories.php" style="display: inline;" onsubmit="return confirm('Delete this department? Warning: Categories must have 0 products.');">
            <?= csrf_field() ?>
            <input type="hidden" name="delete_dept" value="1">
            <input type="hidden" name="dept_id" value="<?= $dId ?>">
            <button type="submit" class="admin-btn admin-btn-danger admin-btn-sm" title="Delete department">Del</button>
          </form>
        </div>
      </div>

      <!-- Categories List inside this Department -->
      <div class="dept-subcat-list">
        <?php if (empty($subCats)): ?>
          <div style="grid-column: 1 / -1; padding: 0.75rem 0; color: var(--admin-text-muted); font-size: 0.8125rem;">
            No categories defined yet under this department. 
            <button type="button" onclick="openCatModal(<?= $dId ?>)" style="background: none; border: none; color: var(--admin-primary); font-weight: 700; cursor: pointer; text-decoration: underline;">
              + Add first category
            </button>
          </div>
        <?php else: ?>
          <?php foreach ($subCats as $sc): 
            $subcatList = $subcatsByCat[$sc['id']] ?? [];
          ?>
            <div class="subcat-chip" data-cat-name="<?= htmlspecialchars($sc['name']) ?>" style="display: flex; flex-direction: column; align-items: stretch; gap: 0.5rem;">
              <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem;">
                <div>
                  <strong style="color: var(--admin-text); font-size: 0.875rem;">
                    <?= htmlspecialchars($sc['name']) ?>
                  </strong>
                  <div style="font-size: 0.75rem; color: var(--admin-text-muted);">
                    <?= $sc['product_count'] ?> items • <code><?= htmlspecialchars($sc['slug']) ?></code>
                    <?php if (!empty($subcatList)): ?>
                      • <span style="background: rgba(1, 23, 96, 0.08); padding: 0.1rem 0.4rem; border-radius: 4px; font-weight: 700; color: var(--admin-primary);"><?= count($subcatList) ?> subcategories</span>
                    <?php endif; ?>
                  </div>
                </div>
                <div style="display: flex; gap: 0.25rem;">
                  <button type="button" onclick="openSubcatModal(<?= $sc['id'] ?>)" class="admin-btn admin-btn-secondary admin-btn-sm" style="padding: 0.2rem 0.45rem; font-size: 0.7rem;" title="Add Subcategory under this category">
                    + Sub
                  </button>
                  <a href="categories.php?edit_cat=<?= $sc['id'] ?>" class="admin-btn admin-btn-secondary admin-btn-sm" style="padding: 0.2rem 0.45rem; font-size: 0.7rem;" title="Edit category">
                    ✎
                  </a>
                  <form method="POST" action="categories.php" style="display: inline;" onsubmit="return confirm('Delete category: <?= htmlspecialchars(addslashes($sc['name'])) ?>?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="delete_cat" value="1">
                    <input type="hidden" name="cat_id" value="<?= $sc['id'] ?>">
                    <button type="submit" class="admin-btn admin-btn-danger admin-btn-sm" style="padding: 0.2rem 0.45rem; font-size: 0.7rem;" title="Delete category">&times;</button>
                  </form>
                </div>
              </div>

              <?php if (!empty($subcatList)): ?>
                <div style="display: flex; flex-wrap: wrap; gap: 0.35rem; padding-top: 0.35rem; border-top: 1px dashed var(--card-border);">
                  <?php foreach ($subcatList as $sub): ?>
                    <span style="display: inline-flex; align-items: center; gap: 0.35rem; background: var(--surface); padding: 0.15rem 0.45rem; border-radius: 6px; border: 1px solid var(--card-border); font-size: 0.75rem; color: var(--admin-text);">
                      <span><?= htmlspecialchars($sub['name']) ?></span>
                      <span style="font-size: 0.65rem; color: var(--admin-text-muted);">(<?= $sub['product_count'] ?>)</span>
                      <a href="categories.php?edit_subcat=<?= $sub['id'] ?>" style="color: var(--link); text-decoration: none; font-size: 0.7rem;" title="Edit subcategory">✎</a>
                      <form method="POST" action="categories.php" style="display: inline;" onsubmit="return confirm('Delete subcategory <?= htmlspecialchars(addslashes($sub['name'])) ?>?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="delete_subcat" value="1">
                        <input type="hidden" name="subcat_id" value="<?= $sub['id'] ?>">
                        <button type="submit" style="background: none; border: none; color: #dc2626; cursor: pointer; padding: 0; font-size: 0.75rem; line-height: 1;" title="Delete subcategory">&times;</button>
                      </form>
                    </span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

    </div>
  <?php endforeach; ?>

</div>

<script>
function toggleDeptForm() {
  const form = document.getElementById('deptFormCard');
  if (form) {
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
    if (form.style.display === 'block') {
      form.scrollIntoView({ behavior: 'smooth' });
    }
  }
}

function openCatModal(deptId) {
  const card = document.getElementById('catFormCard');
  if (card) {
    card.style.display = 'block';
    if (deptId > 0) {
      const select = document.getElementById('modalDeptSelect');
      if (select) select.value = deptId;
    }
    document.getElementById('modalCatId').value = 0;
    document.getElementById('modalCatName').value = '';
    card.scrollIntoView({ behavior: 'smooth' });
    document.getElementById('modalCatName').focus();
  }
}

function closeCatModal() {
  const card = document.getElementById('catFormCard');
  if (card) card.style.display = 'none';
}

function openSubcatModal(catId) {
  const card = document.getElementById('subcatFormCard');
  if (card) {
    card.style.display = 'block';
    if (catId > 0) {
      const select = document.getElementById('modalSubcatCatSelect');
      if (select) select.value = catId;
    }
    document.getElementById('modalSubcatId').value = 0;
    document.getElementById('modalSubcatName').value = '';
    card.scrollIntoView({ behavior: 'smooth' });
    document.getElementById('modalSubcatName').focus();
  }
}

function closeSubcatModal() {
  const card = document.getElementById('subcatFormCard');
  if (card) card.style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
