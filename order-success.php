<?php
/**
 * SSK Store - Order Confirmation & Invoice Receipt
 * Shree Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$orderId = (int)($_GET['id'] ?? 0);
$order = db_fetch_one("SELECT * FROM orders WHERE id = ?", [$orderId]);

if (!$order) {
    header('Location: index.php');
    exit;
}

// Security Check: Customers can only view their own orders (placed in session, logged-in customer match, or admin)
$canAccess = false;
if (isset($_SESSION['last_order_id']) && (int)$_SESSION['last_order_id'] === $orderId) {
    $canAccess = true;
} elseif (isset($_SESSION['customer_id']) && (int)$order['customer_id'] === (int)$_SESSION['customer_id'] && (int)$order['customer_id'] > 0) {
    $canAccess = true;
} elseif (is_admin_logged_in()) {
    $canAccess = true;
}

if (!$canAccess) {
    header('Location: index.php');
    exit;
}

$orderItems = db_fetch_all("SELECT oi.*, p.image 
                            FROM order_items oi 
                            LEFT JOIN products p ON oi.product_id = p.id 
                            WHERE oi.order_id = ?", [$orderId]);

$pageTitle = 'Order Confirmed #' . $order['order_number'] . ' — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';

$isUpi = strtoupper($order['payment_method']) === 'UPI';
$waText = urlencode("Jai Swaminarayan! I have placed Order #" . $order['order_number'] . " for " . format_price($order['total']) . ". Please verify my payment.");
$waUrl = "https://wa.me/919426551098?text=" . $waText;
?>

<main class="container" style="max-width: 880px; margin-top: 1.5rem; margin-bottom: 4rem;">
  
  <?= render_breadcrumb([
    ['label' => 'Home', 'url' => 'index.php'],
    ['label' => 'Order #' . $order['order_number']]
  ]) ?>

  <div class="ui-card" style="padding: 3rem 2rem; text-align: center;">
    
    <!-- Success Icon -->
    <div style="width: 76px; height: 76px; border-radius: 50%; background: rgba(16, 185, 129, 0.12); color: var(--success); display: flex; align-items: center; justify-content: center; font-size: 2.25rem; margin: 0 auto 1.25rem; border: 2.5px solid var(--success);">
      ✓
    </div>

    <span style="display: inline-block; background: var(--stat-amber-bg); color: var(--stat-amber); padding: 0.3rem 0.85rem; border-radius: var(--radius-full); font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.75rem; border: 1px solid var(--stat-amber);">
      Jai Swaminarayan
    </span>

    <h1 style="font-size: 2.25rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--heading);">Order Placed Successfully!</h1>
    <p style="color: var(--text-muted); font-size: 1.05rem; margin-bottom: 2rem; max-width: 620px; margin-left: auto; margin-right: auto; line-height: 1.6;">
      Thank you for your order. We have received your request and the seva team at Shri Sahajanand Sahitya Kendra (Kalakunj Mandir) will process dispatch.
    </p>

    <!-- Key Info Grid -->
    <div style="background: var(--surface-2); border: 1.5px solid var(--card-border); border-radius: 12px; padding: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.25rem; text-align: left; margin-bottom: 2.5rem;">
      <div>
        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Order Reference</div>
        <div style="font-weight: 800; color: var(--red-text); font-size: 1.05rem; margin-top: 2px;"><?= htmlspecialchars($order['order_number']) ?></div>
      </div>
      <div>
        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Date Placed</div>
        <div style="font-weight: 700; color: var(--heading); font-size: 0.95rem; margin-top: 2px;"><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></div>
      </div>
      <div>
        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Total Payable</div>
        <div style="font-weight: 800; color: var(--heading); font-size: 1.05rem; margin-top: 2px;"><?= format_price($order['total']) ?></div>
      </div>
      <div>
        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Payment Status</div>
        <div style="font-weight: 700; font-size: 0.95rem; margin-top: 2px; color: <?= $isUpi ? 'var(--stat-amber)' : 'var(--info)' ?>;">
          <?= $isUpi ? 'Verification Pending' : htmlspecialchars($order['payment_status']) ?>
        </div>
      </div>
    </div>

    <?php if ($isUpi): ?>
      <!-- Honest UPI Verification Notice Box -->
      <div style="background: rgba(245, 158, 11, 0.1); border: 1.5px solid var(--stat-amber); border-radius: 12px; padding: 1.25rem 1.5rem; text-align: left; margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
        <div>
          <div style="font-weight: 700; color: var(--stat-amber); font-size: 1rem;">UPI / QR Payment Verification</div>
          <div style="font-size: 0.875rem; color: var(--text); margin-top: 4px;">
            Please send your UPI payment screenshot or UTR reference number to our temple WhatsApp support for instant confirmation.
          </div>
        </div>
        <a href="<?= $waUrl ?>" target="_blank" rel="noopener" class="btn btn-primary" style="background: #166534; border-color: #166534; min-height: 44px; display: inline-flex; align-items: center; gap: 8px;">
          <span>📱 Confirm on WhatsApp</span>
        </a>
      </div>
    <?php endif; ?>

    <!-- Items List Table -->
    <div style="text-align: left; margin-bottom: 2rem;">
      <h2 style="font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem; border-bottom: 1.5px solid var(--card-border); padding-bottom: 0.5rem; color: var(--heading);">Ordered Items</h2>
      <table style="width: 100%; border-collapse: collapse;">
        <thead>
          <tr style="border-bottom: 1.5px solid var(--card-border); font-size: 0.8125rem; color: var(--text-muted);">
            <th style="padding: 0.6rem 0; text-align: left;">Item</th>
            <th style="padding: 0.6rem 0; text-align: center;">Qty</th>
            <th style="padding: 0.6rem 0; text-align: right;">Unit Price</th>
            <th style="padding: 0.6rem 0; text-align: right;">Line Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orderItems as $item): ?>
            <tr style="border-bottom: 1px solid var(--card-border); font-size: 0.875rem;">
              <td style="padding: 0.85rem 0;">
                <div style="font-weight: 700; color: var(--heading);"><?= htmlspecialchars($item['product_name']) ?></div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">SKU: <?= htmlspecialchars($item['product_sku']) ?></div>
              </td>
              <td style="padding: 0.85rem 0; text-align: center; color: var(--text);"><?= $item['quantity'] ?></td>
              <td style="padding: 0.85rem 0; text-align: right; color: var(--text);"><?= format_price($item['price']) ?></td>
              <td style="padding: 0.85rem 0; text-align: right; font-weight: 800; color: var(--heading);"><?= format_price($item['total']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Delivery Address Info -->
    <div style="text-align: left; background: var(--surface-2); border: 1.5px solid var(--card-border); border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 2.5rem; font-size: 0.875rem;">
      <div style="font-weight: 700; margin-bottom: 0.35rem; color: var(--red-text);">Delivery Destination:</div>
      <div style="color: var(--text); font-weight: 600;"><?= htmlspecialchars($order['shipping_name']) ?> (Phone: <?= htmlspecialchars($order['shipping_phone']) ?>)</div>
      <div style="color: var(--text-muted); margin-top: 4px;"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?>, <?= htmlspecialchars($order['shipping_city']) ?>, <?= htmlspecialchars($order['shipping_state']) ?> - <?= htmlspecialchars($order['shipping_pincode']) ?></div>
    </div>

    <!-- Action Buttons -->
    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
      <a href="catalog.php" class="btn btn-primary btn-lg">Browse More Publications &rarr;</a>
      <button type="button" class="btn btn-secondary btn-lg" onclick="window.print()">Print Official Receipt</button>
    </div>

  </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
