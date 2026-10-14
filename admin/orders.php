<?php
/**
 * BAPS Store - Admin Order Management
 */

require_once __DIR__ . '/includes/admin_bootstrap.php';
admin_require_auth('orders');

// Handle Status Update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = (string)($_POST['status'] ?? '');
    $newPaymentStatus = (string)($_POST['payment_status'] ?? '');

    $allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    $allowedPaymentStatuses = ['pending', 'paid', 'failed', 'refunded', 'pending_verification'];

    if (!in_array($newStatus, $allowedStatuses, true) || !in_array($newPaymentStatus, $allowedPaymentStatuses, true)) {
        set_flash('error', 'Invalid status value provided.');
        header('Location: orders.php' . ($orderId > 0 ? '?order_id=' . $orderId : ''));
        exit;
    }

    db_query("UPDATE orders SET status = ?, payment_status = ? WHERE id = ?", [$newStatus, $newPaymentStatus, $orderId]);
    
    // Dispatch customer status update email
    require_once __DIR__ . '/../includes/mail.php';
    $updatedOrder = db_fetch_one("SELECT o.*, c.email as customer_email FROM orders o LEFT JOIN customers c ON o.customer_id = c.id WHERE o.id = ?", [$orderId]);
    if ($updatedOrder) {
        send_order_status_update_mail($updatedOrder, $newStatus);
    }

    set_flash('success', 'Order status updated successfully.');
    header('Location: orders.php?order_id=' . $orderId);
    exit;
}

$adminPageTitle = 'Customer Orders';
require_once __DIR__ . '/includes/header.php';

$statusFilter = $_GET['status'] ?? 'all';
$params = [];
$whereSql = "1=1";

if ($statusFilter !== 'all') {
    $whereSql .= " AND o.status = ?";
    $params[] = $statusFilter;
}

$orders = db_fetch_all("SELECT o.*, c.email as customer_email 
                        FROM orders o 
                        LEFT JOIN customers c ON o.customer_id = c.id 
                        WHERE $whereSql 
                        ORDER BY o.id DESC", $params);

// Check if viewing single order details
$viewId = (int)($_GET['order_id'] ?? 0);
$viewOrder = null;
$viewItems = [];
if ($viewId > 0) {
    $viewOrder = db_fetch_one("SELECT * FROM orders WHERE id = ?", [$viewId]);
    if ($viewOrder) {
        $viewItems = db_fetch_all("SELECT * FROM order_items WHERE order_id = ?", [$viewId]);
    }
}
?>

<?php if ($viewOrder): ?>
  <!-- Single Order Details View -->
  <div class="admin-card" style="max-width: 860px; margin: 0 auto 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--admin-border); padding-bottom: 1rem; margin-bottom: 1.5rem;">
      <div>
        <h3 style="font-size: 1.35rem; font-weight: 800; color: var(--admin-primary);">
          Order #<?= htmlspecialchars($viewOrder['order_number']) ?>
        </h3>
        <span style="font-size: 0.8125rem; color: var(--admin-text-muted);">
          Placed on <?= date('d M Y, h:i A', strtotime($viewOrder['created_at'])) ?>
        </span>
      </div>
      <a href="orders.php" class="admin-btn admin-btn-secondary admin-btn-sm">&larr; Back to All Orders</a>
    </div>

    <!-- Quick Status Update Form -->
    <form method="POST" style="background: var(--surface-2); border: 1.5px solid var(--card-border); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
      <?= csrf_field() ?>
      <input type="hidden" name="update_status" value="1">
      <input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>">

      <div style="flex: 1;">
        <label class="admin-label" for="stat-select">Fulfillment Status</label>
        <select id="stat-select" name="status" class="admin-select">
          <option value="pending" <?= $viewOrder['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
          <option value="processing" <?= $viewOrder['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
          <option value="shipped" <?= $viewOrder['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
          <option value="delivered" <?= $viewOrder['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
          <option value="cancelled" <?= $viewOrder['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
      </div>

      <div style="flex: 1;">
        <label class="admin-label" for="paystat-select">Payment Status</label>
        <select id="paystat-select" name="payment_status" class="admin-select">
          <option value="pending" <?= $viewOrder['payment_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
          <option value="paid" <?= $viewOrder['payment_status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
          <option value="failed" <?= $viewOrder['payment_status'] === 'failed' ? 'selected' : '' ?>>Failed</option>
          <option value="refunded" <?= $viewOrder['payment_status'] === 'refunded' ? 'selected' : '' ?>>Refunded</option>
        </select>
      </div>

      <button type="submit" class="admin-btn admin-btn-primary">Update Status</button>
    </form>

    <!-- Shipping Address & Info Grid -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
      <div style="background: var(--surface-2); border: 1px solid var(--border); padding: 1.25rem; border-radius: var(--radius-card); color: var(--text);">
        <h4 style="font-size: 0.95rem; margin-bottom: 0.5rem; color: var(--heading);">Customer & Shipping</h4>
        <div style="font-size: 0.875rem;"><strong>Name:</strong> <?= htmlspecialchars($viewOrder['shipping_name']) ?></div>
        <div style="font-size: 0.875rem;"><strong>Phone:</strong> <?= htmlspecialchars($viewOrder['shipping_phone']) ?></div>
        <div style="font-size: 0.875rem; margin-top: 0.5rem;"><strong>Address:</strong><br><?= nl2br(htmlspecialchars($viewOrder['shipping_address'])) ?>, <?= htmlspecialchars($viewOrder['shipping_city']) ?>, <?= htmlspecialchars($viewOrder['shipping_state']) ?> - <?= htmlspecialchars($viewOrder['shipping_pincode']) ?></div>
      </div>

      <div style="background: var(--surface-2); border: 1px solid var(--border); padding: 1.25rem; border-radius: var(--radius-card); color: var(--text);">
        <h4 style="font-size: 0.95rem; margin-bottom: 0.5rem; color: var(--heading);">Payment Summary</h4>
        <div style="font-size: 0.875rem;"><strong>Method:</strong> <?= htmlspecialchars($viewOrder['payment_method']) ?></div>
        <div style="font-size: 0.875rem;"><strong>Subtotal:</strong> <?= format_price($viewOrder['subtotal']) ?></div>
        <div style="font-size: 0.875rem;"><strong>Shipping Charge:</strong> <?= format_price($viewOrder['shipping_charge']) ?></div>
        <div style="font-size: 0.875rem;"><strong>Discount Applied:</strong> -<?= format_price($viewOrder['discount']) ?></div>
        <div style="font-size: 1.15rem; font-weight: 800; color: var(--heading); margin-top: 0.5rem;">
          Total: <?= format_price($viewOrder['total']) ?>
        </div>
      </div>
    </div>

    <!-- Order Items Table -->
    <div class="table-responsive" style="overflow-x: auto; width: 100%; -webkit-overflow-scrolling: touch;">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Item Name</th>
            <th>SKU</th>
            <th>Qty</th>
            <th>Unit Price</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($viewItems as $vi): ?>
            <tr>
              <td><strong><?= htmlspecialchars($vi['product_name']) ?></strong></td>
              <td><?= htmlspecialchars($vi['product_sku'] ?? 'N/A') ?></td>
              <td><?= $vi['quantity'] ?></td>
              <td><?= format_price($vi['price']) ?></td>
              <td style="font-weight: 700; color: var(--admin-primary);"><?= format_price($vi['total']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php else: ?>

  <!-- Orders Filter Strip -->
  <div class="admin-card">
    <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
      <a href="orders.php" class="admin-btn <?= $statusFilter === 'all' ? 'admin-btn-primary' : 'admin-btn-secondary' ?> admin-btn-sm">All Orders</a>
      <a href="orders.php?status=pending" class="admin-btn <?= $statusFilter === 'pending' ? 'admin-btn-primary' : 'admin-btn-secondary' ?> admin-btn-sm">Pending</a>
      <a href="orders.php?status=processing" class="admin-btn <?= $statusFilter === 'processing' ? 'admin-btn-primary' : 'admin-btn-secondary' ?> admin-btn-sm">Processing</a>
      <a href="orders.php?status=shipped" class="admin-btn <?= $statusFilter === 'shipped' ? 'admin-btn-primary' : 'admin-btn-secondary' ?> admin-btn-sm">Shipped</a>
      <a href="orders.php?status=delivered" class="admin-btn <?= $statusFilter === 'delivered' ? 'admin-btn-primary' : 'admin-btn-secondary' ?> admin-btn-sm">Delivered</a>
    </div>

    <div class="table-responsive" style="overflow-x: auto; width: 100%; -webkit-overflow-scrolling: touch;">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Order #</th>
            <th>Customer Name</th>
            <th>Date</th>
            <th>City / State</th>
            <th>Payment</th>
            <th>Status</th>
            <th>Amount</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($orders)): ?>
            <tr>
              <td colspan="8" style="text-align: center; color: var(--admin-text-muted); padding: 2rem;">No orders found matching criteria.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($orders as $o): ?>
              <tr>
                <td>
                  <strong style="color: var(--admin-primary);"><?= htmlspecialchars($o['order_number']) ?></strong>
                </td>
                <td>
                  <div style="font-weight: 600;"><?= htmlspecialchars($o['shipping_name']) ?></div>
                  <div style="font-size: 0.75rem; color: var(--admin-text-muted);"><?= htmlspecialchars($o['shipping_phone']) ?></div>
                </td>
                <td>
                  <?= date('d M Y, h:i A', strtotime($o['created_at'])) ?>
                </td>
                <td>
                  <?= htmlspecialchars($o['shipping_city'] . ', ' . $o['shipping_state']) ?>
                </td>
                <td>
                  <span style="font-size: 0.8125rem; font-weight: 700; text-transform: uppercase;">
                    <?= htmlspecialchars($o['payment_method']) ?>
                  </span>
                </td>
                <td>
                  <span class="status-pill status-<?= strtolower($o['status']) ?>">
                    <?= htmlspecialchars($o['status']) ?>
                  </span>
                </td>
                <td style="font-weight: 800;">
                  <?= format_price($o['total']) ?>
                </td>
                <td>
                  <a href="orders.php?order_id=<?= $o['id'] ?>" class="admin-btn admin-btn-secondary admin-btn-sm">
                    View & Update
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
