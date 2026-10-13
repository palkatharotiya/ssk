<?php
/**
 * BAPS Store - Admin Coupons Management
 */

$adminPageTitle = 'Coupons & Promotions';
require_once __DIR__ . '/includes/header.php';

// Handle Add Coupon POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coupon'])) {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $type = $_POST['type'] ?? 'percentage';
    $val = (float)($_POST['value'] ?? 0);
    $minOrder = (float)($_POST['min_order_amount'] ?? 0);

    if ($code && $val > 0) {
        db_query("INSERT INTO coupons (code, type, value, min_order_amount, is_active) VALUES (?, ?, ?, ?, 1)", [
            $code, $type, $val, $minOrder
        ]);
        set_flash('success', 'Coupon code added successfully.');
        header('Location: coupons.php');
        exit;
    }
}

// Handle Delete Coupon
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    db_query("DELETE FROM coupons WHERE id = ?", [$delId]);
    set_flash('success', 'Coupon removed.');
    header('Location: coupons.php');
    exit;
}

$coupons = db_fetch_all("SELECT * FROM coupons ORDER BY id DESC");
?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
  
  <!-- Add Coupon Form -->
  <div class="admin-card">
    <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1.25rem;">Create New Coupon</h3>

    <form method="POST">
      <input type="hidden" name="add_coupon" value="1">

      <div class="admin-form-group">
        <label class="admin-label" for="cp-code">Coupon Code *</label>
        <input type="text" id="cp-code" name="code" class="admin-input" placeholder="e.g. UTSAV15" style="text-transform: uppercase;" required>
      </div>

      <div class="admin-form-group">
        <label class="admin-label" for="cp-type">Discount Type</label>
        <select id="cp-type" name="type" class="admin-select">
          <option value="percentage">Percentage (%)</option>
          <option value="fixed">Fixed Amount (₹)</option>
        </select>
      </div>

      <div class="admin-form-group">
        <label class="admin-label" for="cp-val">Discount Value *</label>
        <input type="number" step="0.01" id="cp-val" name="value" class="admin-input" placeholder="10 or 100" required>
      </div>

      <div class="admin-form-group">
        <label class="admin-label" for="cp-min">Min Order Amount (₹)</label>
        <input type="number" step="0.01" id="cp-min" name="min_order_amount" class="admin-input" value="500.00">
      </div>

      <button type="submit" class="admin-btn admin-btn-primary" style="width: 100%; justify-content: center;">
        Save Promo Coupon
      </button>
    </form>
  </div>

  <!-- Coupon List -->
  <div class="admin-card">
    <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1.25rem;">Active Coupons</h3>

    <table class="admin-table">
      <thead>
        <tr>
          <th>Code</th>
          <th>Discount</th>
          <th>Min Order</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($coupons as $cp): ?>
          <tr>
            <td>
              <strong style="color: var(--admin-primary); font-family: monospace; font-size: 1rem;"><?= htmlspecialchars($cp['code']) ?></strong>
            </td>
            <td>
              <?= $cp['type'] === 'percentage' ? $cp['value'] . '%' : format_price($cp['value']) ?>
            </td>
            <td><?= format_price($cp['min_order_amount']) ?></td>
            <td>
              <span class="status-pill status-delivered">Active</span>
            </td>
            <td>
              <a href="coupons.php?delete=<?= $cp['id'] ?>" onclick="return confirm('Delete coupon?')" class="admin-btn admin-btn-danger admin-btn-sm">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
