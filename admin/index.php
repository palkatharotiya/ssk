<?php
/**
 * SSK Store - Modern Admin Dashboard
 * Shree Sahajanand Sahitya Kendra (Kalakunj Mandir)
 */

$adminPageTitle = 'Dashboard Overview';
require_once __DIR__ . '/includes/header.php';

// Calculate Key Metrics
$revRow = db_fetch_one("SELECT SUM(total) as revenue FROM orders WHERE status != 'cancelled'");
$totalRevenue = (float)($revRow['revenue'] ?? 0);

$ordRow = db_fetch_one("SELECT COUNT(*) as count FROM orders");
$totalOrders = (int)($ordRow['count'] ?? 0);

$prodRow = db_fetch_one("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
$totalProducts = (int)($prodRow['count'] ?? 0);

$custRow = db_fetch_one("SELECT COUNT(*) as count FROM customers");
$totalCustomers = (int)($custRow['count'] ?? 0);

$deptRow = db_fetch_one("SELECT COUNT(*) as count FROM departments");
$totalDepts = (int)($deptRow['count'] ?? 0);

$catRow = db_fetch_one("SELECT COUNT(*) as count FROM categories");
$totalCats = (int)($catRow['count'] ?? 0);

// Fetch Recent Orders
$recentOrders = db_fetch_all("SELECT o.*, c.first_name, c.last_name 
                              FROM orders o 
                              LEFT JOIN customers c ON o.customer_id = c.id 
                              ORDER BY o.id DESC LIMIT 5");

// Low Stock Alert (stock <= 15)
$lowStockProducts = db_fetch_all("SELECT id, name, sku, stock_qty, price 
                                  FROM products 
                                  WHERE is_active = 1 AND stock_qty <= 15 
                                  ORDER BY stock_qty ASC LIMIT 5");
?>

<!-- Welcome Banner -->
<div class="welcome-banner">
  <div>
    <div class="greeting">
      Jay Swaminarayan 🙏
    </div>
    <h2 style="font-size: 1.6rem; font-weight: 800; color: #ffffff; margin-bottom: 0.35rem;">
      Welcome back, <?= htmlspecialchars($admin['full_name'] ?? 'Admin') ?>
    </h2>
    <p style="font-size: 0.9375rem; color: rgba(255,255,255,0.92); margin: 0;">
      Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat) — Store Management Portal
    </p>
  </div>
  <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
    <a href="products.php?action=create" class="admin-btn btn-white">
      + Add New Product
    </a>
    <a href="categories.php" class="admin-btn btn-translucent">
      🏷️ Manage Categories
    </a>
  </div>
</div>

<!-- 4 Modern Metric Cards -->
<div class="metrics-grid">
  
  <div class="metric-card card-revenue">
    <div class="metric-icon">₹</div>
    <div>
      <div class="metric-val"><?= format_price($totalRevenue) ?></div>
      <div class="metric-label">Total Revenue</div>
      <div class="metric-sub">UPI & COD Ready</div>
    </div>
  </div>

  <div class="metric-card card-orders">
    <div class="metric-icon">📦</div>
    <div>
      <div class="metric-val"><?= $totalOrders ?></div>
      <div class="metric-label">Total Orders</div>
      <div class="metric-sub"><?= $totalOrders === 0 ? 'Awaiting First Order' : 'All Orders Tracked' ?></div>
    </div>
  </div>

  <div class="metric-card card-products">
    <div class="metric-icon">📚</div>
    <div>
      <div class="metric-val"><?= $totalProducts ?></div>
      <div class="metric-label">Catalog Products</div>
      <div class="metric-sub"><?= $totalDepts ?> Depts • <?= $totalCats ?> Categories</div>
    </div>
  </div>

  <div class="metric-card card-customers">
    <div class="metric-icon">👥</div>
    <div>
      <div class="metric-val"><?= $totalCustomers ?></div>
      <div class="metric-label">Registered Devotees</div>
      <div class="metric-sub">Customer Accounts</div>
    </div>
  </div>

</div>

<!-- Catalog Fresh Onboarding Callout (if 0 products) -->
<?php if ($totalProducts === 0): ?>
  <div class="notice-card">
    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
      <div>
        <h4 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 0.35rem;">
          🌟 Product Catalog is Clean & Ready for Upload!
        </h4>
        <p style="font-size: 0.9375rem; max-width: 720px; line-height: 1.6; margin-bottom: 0.75rem;">
          All demo placeholder items have been cleared as requested. You can now start uploading your authentic spiritual scriptures, sacred tulsi kanthis, carved mandirs, and puja accessories.
        </p>
      </div>
      <div style="display: flex; gap: 0.75rem;">
        <a href="products.php?action=create" class="admin-btn admin-btn-primary">
          + Add First Product
        </a>
        <a href="categories.php" class="admin-btn admin-btn-secondary">
          Organize Categories
        </a>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Quick Shortcuts Grid -->
<div class="admin-card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
    <h3 style="font-size: 1.15rem; font-weight: 700;">Quick Action Center</h3>
    <span style="font-size: 0.8125rem; color: var(--admin-text-muted);">One-click administrative shortcuts</span>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
    
    <a href="products.php" class="admin-shortcut-card">
      <div class="admin-shortcut-icon" style="background: rgba(231, 2, 7, 0.15); color: var(--red);">
        📚
      </div>
      <div>
        <div style="font-weight: 700; color: var(--text);">Products & Stock</div>
        <div style="font-size: 0.8125rem; color: var(--text-muted);">Live search & upload</div>
      </div>
    </a>

    <a href="categories.php" class="admin-shortcut-card">
      <div class="admin-shortcut-icon" style="background: var(--stat-amber-bg); color: var(--stat-amber);">
        🏷️
      </div>
      <div>
        <div style="font-weight: 700; color: var(--text);">Depts & Categories</div>
        <div style="font-size: 0.8125rem; color: var(--text-muted);">Organize store catalog</div>
      </div>
    </a>

    <a href="banners.php" class="admin-shortcut-card">
      <div class="admin-shortcut-icon" style="background: var(--stat-blue-bg); color: var(--stat-blue);">
        🖼️
      </div>
      <div>
        <div style="font-weight: 700; color: var(--text);">Hero Slideshow</div>
        <div style="font-size: 0.8125rem; color: var(--text-muted);">Main homepage banners</div>
      </div>
    </a>

    <a href="homepage_sections.php" class="admin-shortcut-card">
      <div class="admin-shortcut-icon" style="background: rgba(139, 92, 246, 0.15); color: #8B5CF6;">
        ✨
      </div>
      <div>
        <div style="font-weight: 700; color: var(--text);">Homepage Layout</div>
        <div style="font-size: 0.8125rem; color: var(--text-muted);">Festive, Top Selling order</div>
      </div>
    </a>

  </div>
</div>

<!-- Recent Orders & Inventory / Mandir Support Grid -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
  
  <!-- Recent Orders Table -->
  <div class="admin-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
      <h3 style="font-size: 1.15rem; font-weight: 700;">Recent Orders</h3>
      <a href="orders.php" class="admin-btn admin-btn-secondary admin-btn-sm">View All Orders &rarr;</a>
    </div>

    <?php if (empty($recentOrders)): ?>
      <div class="admin-empty-state" style="padding: 2rem 1rem;">
        <div class="admin-empty-state-icon">📦</div>
        <h4 style="font-weight: 700; color: var(--admin-text);">No Orders Placed Yet</h4>
        <p style="color: var(--admin-text-muted); font-size: 0.875rem; max-width: 380px; margin: 0.35rem auto 0;">
          When devotees place orders online or via UPI, they will appear here in real time.
        </p>
      </div>
    <?php else: ?>
      <div style="overflow-x: auto;">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Order #</th>
              <th>Customer</th>
              <th>Date</th>
              <th>Status</th>
              <th>Total</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentOrders as $ro): ?>
              <tr>
                <td>
                  <strong style="color: var(--admin-primary);"><?= htmlspecialchars($ro['order_number']) ?></strong>
                </td>
                <td>
                  <?= htmlspecialchars($ro['shipping_name'] ?? ($ro['first_name'] . ' ' . $ro['last_name'])) ?>
                </td>
                <td>
                  <?= date('d M Y, h:i A', strtotime($ro['created_at'])) ?>
                </td>
                <td>
                  <span class="status-pill status-<?= strtolower($ro['status']) ?>">
                    <?= htmlspecialchars($ro['status']) ?>
                  </span>
                </td>
                <td style="font-weight: 700;">
                  <?= format_price($ro['total']) ?>
                </td>
                <td>
                  <a href="orders.php?order_id=<?= $ro['id'] ?>" class="admin-btn admin-btn-secondary admin-btn-sm">
                    Manage
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Mandir Headquarters Info Card -->
  <div class="admin-card">
    <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1.15rem;">
      Mandir Store Information
    </h3>
    
    <div style="font-size: 0.875rem; line-height: 1.6; color: var(--admin-text-muted);">
      <p style="margin-bottom: 0.75rem;">
        <strong style="color: var(--admin-text);">Shree Swaminarayan Mandir, Kalakunj</strong><br>
        Varachha Main Rd, opp. Water Tank, Kalakunj Society, Swaminarayan Nagar-2, Kapodra Patiya, Surat, Gujarat 395006, India
      </p>
      
      <div style="padding: 0.75rem 0; border-top: 1px solid var(--admin-border); border-bottom: 1px solid var(--admin-border); margin-bottom: 0.75rem;">
        <div style="margin-bottom: 0.25rem;">
          <strong style="color: var(--text);">Helpline:</strong> <a href="tel:+919426551098" style="color: var(--link); font-weight: 700;">+91 9426-55-1098</a>
        </div>
        <div style="margin-bottom: 0.25rem;">
          <strong style="color: var(--text);">WhatsApp Support:</strong> <a href="https://wa.me/919426551098" target="_blank" style="color: var(--stat-green); font-weight: 700;">+91 9426-55-1098</a>
        </div>
        <div>
          <strong style="color: var(--text);">Instagram:</strong> <a href="https://www.instagram.com/s_s_sahitya_kendra" target="_blank" style="color: var(--stat-pink); font-weight: 700;">@s_s_sahitya_kendra</a>
        </div>
      </div>

      <div>
        <div style="font-weight: 700; color: var(--admin-text); margin-bottom: 0.25rem;">Operating Timings:</div>
        <div>Morning: 8:00 AM – 12:00 PM IST</div>
        <div>Evening: 4:00 PM – 8:00 PM IST</div>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
