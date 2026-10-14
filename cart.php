<?php
/**
 * BAPS Store - Shopping Cart Page
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$cartItems = get_cart_items();
$subtotal = get_cart_subtotal();

// Handle coupon in session if any
$coupon = $_SESSION['applied_coupon'] ?? null;
$discount = 0.0;
if ($coupon && $subtotal >= (float)($coupon['min_order_amount'] ?? 0)) {
    if (($coupon['type'] ?? '') === 'percentage') {
        $discount = round(($subtotal * (float)$coupon['value']) / 100, 2);
    } else {
        $discount = min($subtotal, (float)$coupon['value']);
    }
} else {
    unset($_SESSION['applied_coupon']);
    $coupon = null;
}

$shippingState = $_SESSION['shipping_state'] ?? 'Gujarat';
$shippingCharge = calculate_shipping($subtotal - $discount, $shippingState);
$finalTotal = max(0, $subtotal - $discount + $shippingCharge);

$pageTitle = 'Shopping Cart — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<main class="container" style="margin-top: 1.5rem; margin-bottom: 4rem;">
  
  <?= render_breadcrumb([
    ['label' => 'Home', 'url' => 'index.php'],
    ['label' => 'Shopping Cart']
  ]) ?>

  <div class="ui-page-header">
    <h1>Shopping Cart</h1>
    <p>Review your selected devotional scriptures, sacred kanthis, and puja items.</p>
  </div>

  <?php if (empty($cartItems)): ?>
    <div class="ui-card" style="padding: 4.5rem 2rem; text-align: center; max-width: 640px; margin: 2rem auto;">
      <div style="font-size: 3.5rem; margin-bottom: 1rem;">🛒</div>
      <h2 style="font-size: 1.6rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--heading);">Your Cart is Currently Empty</h2>
      <p style="color: var(--text-muted); font-size: 1rem; margin-bottom: 2rem; line-height: 1.6;">
        Discover authentic scriptures, pure tulsi kanthis, and sacred ghar mandirs published by Kalakunj Mandir.
      </p>
      <a href="catalog.php" class="btn btn-primary btn-lg">Browse Devotional Catalog &rarr;</a>
    </div>
  <?php else: ?>
    
    <div class="ui-two-col-layout">
      
      <!-- Left Column: Cart Items List -->
      <div class="ui-main-col">
        <div class="ui-card" style="padding: 1.5rem;">
          <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1.5px solid var(--card-border); padding-bottom: 0.85rem; margin-bottom: 1.25rem;">
            <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--heading); margin: 0;">Cart Items (<?= count($cartItems) ?>)</h2>
            <button type="button" class="btn btn-secondary btn-sm" onclick="clearFullCart()" style="color: var(--danger); border-color: rgba(239, 68, 68, 0.3);">
              Clear Cart
            </button>
          </div>

          <table class="cart-table">
            <thead>
              <tr>
                <th>Product</th>
                <th>Price</th>
                <th style="text-align: center;">Quantity</th>
                <th style="text-align: right;">Total</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($cartItems as $item): ?>
                <?php
                  $itemPrice = (float)$item['unit_price'];
                  $itemTotal = $itemPrice * (int)$item['quantity'];
                  $imgUrl = get_product_image_url($item['image'], $item['name']);
                ?>
                <tr>
                  <td>
                    <div class="cart-product-cell">
                      <img src="<?= $imgUrl ?>" class="cart-thumb" alt="<?= htmlspecialchars($item['name']) ?>">
                      <div>
                        <a href="product.php?id=<?= $item['id'] ?>" style="font-weight: 700; color: var(--heading); font-size: 0.95rem; display: block; margin-bottom: 0.25rem; text-decoration: none;">
                          <?= htmlspecialchars($item['name']) ?>
                        </a>
                        <span style="font-size: 0.8125rem; color: var(--text-muted);">SKU: <?= htmlspecialchars($item['sku'] ?? 'SSK-' . $item['id']) ?></span>
                      </div>
                    </div>
                  </td>
                  <td style="font-weight: 600;">
                    <?= format_price($itemPrice) ?>
                  </td>
                  <td style="text-align: center;">
                    <div class="qty-stepper">
                      <button type="button" class="qty-btn qty-minus" aria-label="Decrease quantity">&minus;</button>
                      <input type="number" class="qty-input" value="<?= $item['quantity'] ?>" min="1" max="99" data-cart-update="true" data-product-id="<?= $item['id'] ?>" aria-label="Item quantity">
                      <button type="button" class="qty-btn qty-plus" aria-label="Increase quantity">&plus;</button>
                    </div>
                  </td>
                  <td style="text-align: right; font-weight: 800; color: var(--red-text); font-size: 1.05rem;">
                    <?= format_price($itemTotal) ?>
                  </td>
                  <td style="text-align: center;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="removeCartItem(<?= $item['id'] ?>)" title="Remove item" aria-label="Remove item" style="padding: 0.4rem 0.65rem; color: var(--danger); border-color: var(--input-border);">
                      &times;
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

          <!-- Bottom Cart Continue Link -->
          <div style="display: flex; justify-content: flex-start; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1.5px solid var(--card-border);">
            <a href="catalog.php" class="btn btn-secondary btn-sm">
              &larr; Continue Shopping
            </a>
          </div>
        </div>
      </div>

      <!-- Right Column: Sticky Order Summary & Coupon Card -->
      <div class="ui-side-col">
        <div class="ui-card ui-sticky-side">
          <h2 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 1.25rem; color: var(--heading);">Order Summary</h2>

          <!-- Coupon Code Form -->
          <form id="coupon-form" style="margin-bottom: 1.5rem; padding-bottom: 1.25rem; border-bottom: 1.5px solid var(--card-border);">
            <label class="form-label" for="coupon-code">Promo / Coupon Code</label>
            <div style="display: flex; gap: 0.5rem;">
              <input type="text" id="coupon-code" name="code" class="form-input" placeholder="e.g. SSK10" value="<?= htmlspecialchars($coupon['code'] ?? '') ?>" style="text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">
              <button type="submit" class="btn btn-secondary btn-sm" style="min-width: 80px;">Apply</button>
            </div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 6px;">
              Apply code <strong style="color: var(--primary);">SSK10</strong> for 10% off (orders above ₹500).
            </div>
          </form>

          <!-- Summary Breakdown Rows -->
          <div class="summary-row">
            <span style="color: var(--text-muted);">Subtotal</span>
            <span style="font-weight: 600;"><?= format_price($subtotal) ?></span>
          </div>

          <?php if ($discount > 0): ?>
            <div class="summary-row" style="color: var(--success);">
              <span>Coupon Discount (<?= htmlspecialchars($coupon['code']) ?>)</span>
              <span style="font-weight: 700;">-<?= format_price($discount) ?></span>
            </div>
          <?php endif; ?>

          <!-- Shipping Destination Selector -->
          <div class="summary-row" style="align-items: center;">
            <span style="color: var(--text-muted);">Shipping To</span>
            <select id="state-select" class="form-select" onchange="changeShippingState(this.value)" style="min-height: 38px; padding: 6px 12px; font-size: 0.8125rem; width: auto; max-width: 170px;">
              <option value="Gujarat" <?= $shippingState === 'Gujarat' ? 'selected' : '' ?>>Gujarat (Free ₹500+)</option>
              <option value="Maharashtra" <?= $shippingState !== 'Gujarat' ? 'selected' : '' ?>>Other States (Free ₹750+)</option>
            </select>
          </div>

          <div class="summary-row">
            <span style="color: var(--text-muted);">Delivery Fee</span>
            <span style="font-weight: 700; color: <?= $shippingCharge == 0 ? 'var(--success)' : 'inherit' ?>;">
              <?= $shippingCharge == 0 ? 'FREE' : format_price($shippingCharge) ?>
            </span>
          </div>

          <div class="summary-row summary-total">
            <span>Total Amount</span>
            <span><?= format_price($finalTotal) ?></span>
          </div>

          <div style="margin-top: 1.5rem;">
            <a href="checkout.php" class="btn btn-primary btn-block btn-lg" style="font-size: 1.05rem;">
              Proceed to Checkout &rarr;
            </a>
          </div>

          <div style="margin-top: 1.25rem; text-align: center; font-size: 0.75rem; color: var(--text-muted); display: flex; align-items: center; justify-content: center; gap: 6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            256-Bit SSL Encrypted & Verified
          </div>
        </div>
      </div>

    </div>

    <!-- Mobile Sticky Checkout Action Bar -->
    <div class="mobile-sticky-checkout-bar">
      <div>
        <div style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Total</div>
        <div style="font-size: 18px; font-weight: 800; color: var(--heading);"><?= format_price($finalTotal) ?></div>
      </div>
      <a href="checkout.php" class="btn btn-primary" style="min-height: 44px; padding: 0.6rem 1.5rem; font-size: 15px;">
        Checkout &rarr;
      </a>
    </div>

  <?php endif; ?>

</main>

<script>
function removeCartItem(productId) {
  if (!confirm('Remove this product from your cart?')) return;
  fetch('api/cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ action: 'remove', product_id: productId })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      window.location.reload();
    }
  });
}

function clearFullCart() {
  if (!confirm('Are you sure you want to clear your cart?')) return;
  fetch('api/cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ action: 'clear' })
  })
  .then(r => r.json())
  .then(() => window.location.reload());
}

function changeShippingState(state) {
  // Simple state toggle via URL or reload
  fetch('cart.php')
    .then(() => {
      document.cookie = "shipping_state=" + encodeURIComponent(state) + "; path=/";
      window.location.reload();
    });
}

// Coupon submit
document.getElementById('coupon-form')?.addEventListener('submit', (e) => {
  e.preventDefault();
  const code = document.getElementById('coupon-code').value.trim();
  if (!code) return;

  fetch('api/coupon.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ code: code })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      showToast(data.message, 'success');
      setTimeout(() => window.location.reload(), 600);
    } else {
      showToast(data.message, 'error');
    }
  });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
