<?php
/**
 * SSK Store - Admin Coupons Management
 * Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 */

require_once __DIR__ . '/includes/admin_bootstrap.php';
$adminPageTitle = 'Coupons & Promotions';
require_once __DIR__ . '/includes/header.php';

// Handle Add Coupon POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add_coupon') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $type = in_array($_POST['type'] ?? '', ['percentage', 'fixed'], true) ? $_POST['type'] : 'percentage';
        $val = (float)($_POST['value'] ?? 0);
        $minOrder = max(0.0, (float)($_POST['min_order_amount'] ?? 0));
        $maxUses = !empty($_POST['max_uses']) ? (int)$_POST['max_uses'] : null;
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

        if ($code && $val > 0) {
            db_query("INSERT INTO coupons (code, type, value, min_order_amount, max_uses, start_date, end_date, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)", [
                $code, $type, $val, $minOrder, $maxUses, $startDate, $endDate
            ]);
            set_flash('success', 'Coupon code added successfully.');
            header('Location: coupons.php');
            exit;
        } else {
            set_flash('error', 'Please provide a valid coupon code and discount value.');
        }
    }

    if ($action === 'delete_coupon') {
        $delId = (int)($_POST['coupon_id'] ?? 0);
        if ($delId > 0) {
            db_query("DELETE FROM coupons WHERE id = ?", [$delId]);
            set_flash('success', 'Coupon removed.');
        }
        header('Location: coupons.php');
        exit;
    }
}

$coupons = db_fetch_all("SELECT * FROM coupons ORDER BY id DESC");
?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem;">
  
  <!-- Add Coupon Form -->
  <div class="admin-card">
    <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1.25rem;">Create New Coupon</h3>

    <form method="POST" action="coupons.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_coupon">

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
        <input type="number" id="cp-val" name="value" class="admin-input" step="0.01" min="0" placeholder="e.g. 15 or 100" required>
      </div>

      <div class="admin-form-group">
        <label class="admin-label" for="cp-min">Min Order Subtotal (₹)</label>
        <input type="number" id="cp-min" name="min_order_amount" class="admin-input" step="0.01" min="0" value="0.00">
      </div>

      <div class="admin-form-group">
        <label class="admin-label" for="cp-max">Max Redemptions <span style="font-size: 0.75rem; color: var(--admin-text-muted);">(Optional)</span></label>
        <input type="number" id="cp-max" name="max_uses" class="admin-input" min="1" placeholder="Leave empty for unlimited">
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
        <div class="admin-form-group">
          <label class="admin-label" for="cp-start">Start Date</label>
          <input type="date" id="cp-start" name="start_date" class="admin-input">
        </div>
        <div class="admin-form-group">
          <label class="admin-label" for="cp-end">End Date</label>
          <input type="date" id="cp-end" name="end_date" class="admin-input">
        </div>
      </div>

      <button type="submit" class="admin-btn admin-btn-primary admin-btn-block">
        Add Promotional Coupon
      </button>
    </form>
  </div>

  <!-- Coupons List Table -->
  <div class="admin-card">
    <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1.25rem;">Active Coupons (<?= count($coupons) ?>)</h3>

    <table class="admin-table">
      <thead>
        <tr>
          <th>Code</th>
          <th>Discount</th>
          <th>Min Order</th>
          <th>Usage</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($coupons)): ?>
          <tr><td colspan="5" style="text-align: center; color: var(--admin-text-muted);">No coupons created yet.</td></tr>
        <?php else: ?>
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
                <?= (int)$cp['used_count'] ?><?= !empty($cp['max_uses']) ? ' / ' . $cp['max_uses'] : '' ?>
              </td>
              <td>
                <form method="POST" action="coupons.php" style="display: inline;" onsubmit="return confirm('Delete coupon <?= htmlspecialchars(addslashes($cp['code'])) ?>?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete_coupon">
                  <input type="hidden" name="coupon_id" value="<?= $cp['id'] ?>">
                  <button type="submit" class="admin-btn admin-btn-danger admin-btn-sm">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
