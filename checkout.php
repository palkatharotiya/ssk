<?php
/**
 * SSK Store - Secure Checkout Flow
 * Shree Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$cartItems = get_cart_items();
$subtotal = get_cart_subtotal();

if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}

$currentUser = current_user();
$coupon = $_SESSION['applied_coupon'] ?? null;
$discount = 0.0;
if ($coupon && $subtotal >= (float)($coupon['min_order_amount'] ?? 0)) {
    if (($coupon['type'] ?? '') === 'percentage') {
        $discount = round(($subtotal * (float)$coupon['value']) / 100, 2);
    } else {
        $discount = min($subtotal, (float)$coupon['value']);
    }
}

$error = '';

// Handle Checkout POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired. Please refresh the page and try again.';
    } else {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? 'Gujarat');
        $pincode = trim($_POST['pincode'] ?? '');
        $paymentMethod = strtoupper(trim($_POST['payment_method'] ?? 'UPI'));

        if (!in_array($paymentMethod, ['UPI', 'COD'], true)) {
            $paymentMethod = 'UPI';
        }

        // Clean phone digits
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($cleanPhone) === 12 && substr($cleanPhone, 0, 2) === '91') {
            $cleanPhone = substr($cleanPhone, 2);
        }

        if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || empty($address) || empty($city) || empty($pincode)) {
            $error = 'Please fill in all required shipping and contact details.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please provide a valid email address.';
        } elseif (strlen($cleanPhone) < 10) {
            $error = 'Please enter a valid 10-digit mobile phone number.';
        } elseif (strlen(preg_replace('/[^0-9]/', '', $pincode)) !== 6) {
            $error = 'Please enter a valid 6-digit postal PIN code.';
        } else {
            try {
                $orderId = db_transaction(function(PDO $pdo) use (
                    $cartItems, $subtotal, $currentUser, $firstName, $lastName, $email,
                    $cleanPhone, $address, $city, $state, $pincode, $paymentMethod
                ) {
                    // 1. Re-validate inventory for each cart item inside transaction
                    foreach ($cartItems as $item) {
                        $prod = db_fetch_one("SELECT id, name, stock_qty, price, sale_price, is_active FROM products WHERE id = ?", [$item['id']]);
                        if (!$prod || !$prod['is_active']) {
                            throw new RuntimeException("Sorry, '{$item['name']}' is no longer available.");
                        }
                        if ($prod['stock_qty'] < (int)$item['quantity']) {
                            throw new RuntimeException("Sorry, only {$prod['stock_qty']} unit(s) of '{$item['name']}' are available in stock.");
                        }
                    }

                    // 2. Re-validate coupon from database
                    $discount = 0.0;
                    if (!empty($_SESSION['applied_coupon']['code'])) {
                        $cCode = trim(strtoupper($_SESSION['applied_coupon']['code']));
                        $cRow = db_fetch_one("SELECT * FROM coupons WHERE code = ? AND is_active = 1", [$cCode]);
                        if ($cRow) {
                            $today = date('Y-m-d');
                            $validDates = (empty($cRow['start_date']) || $cRow['start_date'] <= $today) &&
                                         (empty($cRow['end_date']) || $cRow['end_date'] >= $today);
                            $validUses = empty($cRow['max_uses']) || ((int)$cRow['used_count'] < (int)$cRow['max_uses']);
                            $validMin = $subtotal >= (float)$cRow['min_order_amount'];

                            if ($validDates && $validUses && $validMin) {
                                if ($cRow['type'] === 'percentage') {
                                    $discount = round(($subtotal * (float)$cRow['value']) / 100, 2);
                                } else {
                                    $discount = min($subtotal, (float)$cRow['value']);
                                }
                                $discount = min($discount, $subtotal);
                                // Increment used_count atomically
                                db_query("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?", [$cRow['id']]);
                            }
                        }
                    }

                    // 3. Calculate shipping and final total
                    $shipping = calculate_shipping($subtotal - $discount, $state);
                    $total = max(0, $subtotal - $discount + $shipping);
                    $orderNumber = 'SSK-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

                    // 4. Resolve Customer ID safely
                    $customerId = $currentUser['id'] ?? null;
                    if (!$customerId) {
                        $existingGuest = db_fetch_one("SELECT id FROM customers WHERE email = ? AND is_guest = 1", [$email]);
                        if ($existingGuest) {
                            $customerId = (int)$existingGuest['id'];
                        } else {
                            $existingReg = db_fetch_one("SELECT id FROM customers WHERE email = ? AND (is_guest IS NULL OR is_guest = 0)", [$email]);
                            if ($existingReg) {
                                $randPass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                                $customerId = db_insert("INSERT INTO customers (first_name, last_name, email, password, phone, is_active, is_guest) VALUES (?, ?, ?, ?, ?, 1, 1)", [
                                    $firstName, $lastName, 'guest_' . bin2hex(random_bytes(4)) . '_' . $email, $randPass, '+91 ' . $cleanPhone
                                ]);
                            } else {
                                $randPass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                                $customerId = db_insert("INSERT INTO customers (first_name, last_name, email, password, phone, is_active, is_guest) VALUES (?, ?, ?, ?, ?, 1, 1)", [
                                    $firstName, $lastName, $email, $randPass, '+91 ' . $cleanPhone
                                ]);
                            }
                        }
                    }

                    // 5. Insert Order
                    $paymentStatus = ($paymentMethod === 'COD') ? 'pending' : 'pending_verification';
                    $fullName = $firstName . ' ' . $lastName;
                    $formattedPhone = '+91 ' . $cleanPhone;

                    $orderId = db_insert("INSERT INTO orders (customer_id, order_number, status, payment_status, payment_method, subtotal, shipping_charge, discount, total, shipping_name, shipping_phone, shipping_address, shipping_city, shipping_state, shipping_pincode) 
                                          VALUES (?, ?, 'processing', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
                        $customerId,
                        $orderNumber,
                        $paymentStatus,
                        $paymentMethod,
                        $subtotal,
                        $shipping,
                        $discount,
                        $total,
                        $fullName,
                        $formattedPhone,
                        $address,
                        $city,
                        $state,
                        $pincode
                    ]);

                    // 6. Insert Order Items & decrement stock atomically
                    foreach ($cartItems as $item) {
                        $itemTotal = (float)$item['unit_price'] * (int)$item['quantity'];
                        db_query("INSERT INTO order_items (order_id, product_id, product_name, product_sku, quantity, price, total) VALUES (?, ?, ?, ?, ?, ?, ?)", [
                            $orderId,
                            $item['id'],
                            $item['name'],
                            $item['sku'] ?? ('SSK-' . $item['id']),
                            $item['quantity'],
                            $item['unit_price'],
                            $itemTotal
                        ]);

                        // Portable atomic decrement query (works on MySQL AND SQLite)
                        $updateStmt = db_query("UPDATE products SET stock_qty = stock_qty - ? WHERE id = ? AND stock_qty >= ?", [
                            $item['quantity'], $item['id'], $item['quantity']
                        ]);

                        if ($updateStmt->rowCount() === 0) {
                            throw new RuntimeException("Insufficient stock for item: '{$item['name']}'.");
                        }
                    }

                    return $orderId;
                });

                // Clear Cart & Coupon upon confirmed transaction
                clear_cart();
                unset($_SESSION['applied_coupon']);
                $_SESSION['last_order_id'] = $orderId;

                // Dispatch order confirmation email
                require_once __DIR__ . '/includes/mail.php';
                $placedOrder = db_fetch_one("SELECT * FROM orders WHERE id = ?", [$orderId]);
                $placedItems = db_fetch_all("SELECT * FROM order_items WHERE order_id = ?", [$orderId]);
                if ($placedOrder) {
                    send_order_confirmation_mail($placedOrder, $placedItems);
                }

                // Redirect to confirmation receipt page
                header('Location: order-success.php?id=' . $orderId);
                exit;

            } catch (\Throwable $e) {
                error_log("Checkout Transaction Failure: " . $e->getMessage());
                $error = $e->getMessage();
            }
        }
    }
}

$shippingState = $_POST['state'] ?? 'Gujarat';
$shippingCharge = calculate_shipping($subtotal - $discount, $shippingState);
$finalTotal = max(0, $subtotal - $discount + $shippingCharge);

$pageTitle = 'Secure Checkout — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<main class="container" style="padding-top: 1.5rem; padding-bottom: 5rem;">
  
  <!-- Breadcrumb -->
  <nav class="ui-breadcrumb" aria-label="Breadcrumb">
    <a href="index.php">Home</a>
    <span class="ui-breadcrumb-sep">/</span>
    <a href="cart.php">Shopping Cart</a>
    <span class="ui-breadcrumb-sep">/</span>
    <span class="ui-breadcrumb-current">Secure Checkout</span>
  </nav>

  <!-- Page Header -->
  <div class="ui-page-header">
    <h1 class="ui-page-title">Secure Checkout</h1>
    <p class="ui-page-subtitle">Complete your sacred order with encrypted dispatch from Kalakunj Mandir, Surat</p>
  </div>

  <?php if (!empty($error)): ?>
    <?= render_error_summary(['checkoutError' => $error]) ?>
  <?php endif; ?>

  <!-- Mobile Collapsible Order Summary Bar -->
  <div class="mobile-summary-toggle" onclick="toggleMobileSummary()" style="display: none; background: var(--surface-2); border: 1.5px solid var(--card-border); border-radius: 12px; padding: 12px 16px; margin-bottom: 20px; cursor: pointer; align-items: center; justify-content: space-between;">
    <div style="display: flex; align-items: center; gap: 8px; font-weight: 700; color: var(--heading); font-size: 14px;">
      <span>🛒</span>
      <span id="mobileSummaryText">Show order summary (<?= count($cartItems) ?> items)</span>
      <span id="mobileSummaryChevron" style="font-size: 11px;">▼</span>
    </div>
    <div style="font-weight: 800; color: var(--red-text); font-size: 16px;">
      <?= format_price($finalTotal) ?>
    </div>
  </div>

  <div class="ui-two-col-layout">
    
    <!-- LEFT COLUMN: The 3-Step Purchase Process -->
    <div>
      
      <!-- STEP 1: Customer Information Card -->
      <div class="ui-card" id="step1Card">
        <div class="ui-card-header">
          <h2 class="ui-card-title">
            <span class="ui-card-step-num">1</span>
            Customer Information
          </h2>
          <?php if ($currentUser): ?>
            <span style="display: inline-flex; align-items: center; gap: 5px; font-size: 13px; font-weight: 700; color: #166534; background: rgba(34, 197, 94, 0.15); padding: 4px 10px; border-radius: 999px;">
              ✓ Signed In
            </span>
          <?php endif; ?>
        </div>

        <div id="step1Body">
          <?php if ($currentUser): ?>
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
              <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="width: 44px; height: 44px; border-radius: 50%; background: var(--surface-2); display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                  👤
                </div>
                <div>
                  <div style="font-weight: 700; font-size: 1rem; color: var(--heading);">
                    <?= htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?>
                  </div>
                  <div style="font-size: 0.875rem; color: var(--text-muted);">
                    <?= htmlspecialchars($currentUser['email']) ?>
                    <?php if (!empty($currentUser['phone'])): ?>
                      • <?= htmlspecialchars($currentUser['phone']) ?>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <a href="account.php" class="btn btn-secondary btn-sm">Manage Account</a>
            </div>
          <?php else: ?>
            <div style="display: grid; grid-template-columns: 1fr auto 1fr; gap: 1.5rem; align-items: start;">
              
              <!-- Left Option: Sign in to your account -->
              <div>
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                  <span style="font-size: 18px;">👤</span>
                  <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: var(--heading);">Sign in to your account</h3>
                </div>
                <p style="font-size: 13px; color: var(--text-muted); margin: 0 0 14px 0;">Track orders and access saved addresses</p>
                
                <form id="chkLoginForm" onsubmit="handleCheckoutLogin(event)">
                  <div class="form-group">
                    <label class="form-label" for="chkLoginEmail">E-Mail Address</label>
                    <input type="email" id="chkLoginEmail" class="form-input" placeholder="name@example.com" autocomplete="email" required>
                  </div>
                  <div class="form-group">
                    <label class="form-label" for="chkLoginPass">Password</label>
                    <div class="input-password-wrap">
                      <input type="password" id="chkLoginPass" class="form-input" placeholder="••••••••" autocomplete="current-password" required>
                      <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('chkLoginPass', this)" aria-label="Toggle password visibility">👁️</button>
                    </div>
                  </div>
                  <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 14px;">
                    <button type="submit" class="btn btn-primary btn-sm" id="chkLoginSubmitBtn">Sign in</button>
                    <a href="javascript:openAuthModal('register')" style="font-size: 13px; color: var(--link); font-weight: 600;">New Customer?</a>
                  </div>
                </form>
              </div>

              <!-- Divider -->
              <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; min-height: 180px;">
                <div style="width: 1px; flex: 1; background: var(--card-border);"></div>
                <span style="font-size: 13px; font-weight: 700; color: var(--text-muted); padding: 8px 0;">or</span>
                <div style="width: 1px; flex: 1; background: var(--card-border);"></div>
              </div>

              <!-- Right Option: Check out as guest -->
              <div>
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                  <span style="font-size: 18px;">🛒</span>
                  <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: var(--heading);">Check out as a guest</h3>
                </div>
                <p style="font-size: 13px; color: var(--text-muted); margin: 0 0 14px 0;">Quick checkout without creating a password</p>

                <div class="form-group">
                  <label class="form-label" for="chkGuestEmailInput">Your Email Address <span class="form-label-required">*</span></label>
                  <input type="email" id="chkGuestEmailInput" class="form-input" placeholder="Enter your email" autocomplete="email" required>
                  <div class="form-hint">Order confirmation & tracking will be sent here</div>
                </div>
                <div style="margin-top: 14px;">
                  <button type="button" class="btn btn-secondary btn-sm" onclick="confirmGuestEmail()" style="font-weight: 700; min-height: 40px; padding: 0 20px;">
                    Continue as Guest &rarr;
                  </button>
                </div>
              </div>

            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- MAIN CHECKOUT FORM (Steps 2 & 3) -->
      <form method="POST" action="checkout.php" id="mainCheckoutForm">
        <?= csrf_field() ?>

        <!-- Hidden Email Input synced from Login or Guest -->
        <input type="hidden" id="ship-email" name="email" value="<?= htmlspecialchars($currentUser['email'] ?? '') ?>" required>

        <!-- STEP 2: Shipping Address Card -->
        <div class="ui-card" id="step2Card">
          <div class="ui-card-header">
            <h2 class="ui-card-title">
              <span class="ui-card-step-num">2</span>
              Delivery Address
            </h2>
            <span style="font-size: 12px; color: var(--text-muted); font-weight: 500;">
              <span class="form-label-required">*</span> Required fields
            </span>
          </div>

          <!-- Name fields grid -->
          <div class="form-grid-2">
            <div class="form-group">
              <label class="form-label" for="ship-first">
                First Name <span class="form-label-required">*</span>
              </label>
              <input type="text" id="ship-first" name="first_name" class="form-input" required value="<?= htmlspecialchars($currentUser['first_name'] ?? '') ?>" placeholder="e.g. Rajesh" autocomplete="given-name" enterkeyhint="next">
            </div>

            <div class="form-group">
              <label class="form-label" for="ship-last">
                Last Name <span class="form-label-required">*</span>
              </label>
              <input type="text" id="ship-last" name="last_name" class="form-input" required value="<?= htmlspecialchars($currentUser['last_name'] ?? '') ?>" placeholder="e.g. Patel" autocomplete="family-name" enterkeyhint="next">
            </div>
          </div>

          <!-- Phone field with +91 chip -->
          <div class="form-group">
            <label class="form-label" for="ship-phone">
              Contact Phone Number <span class="form-label-required">*</span>
            </label>
            <div class="input-phone-wrap">
              <span class="phone-prefix-chip">🇮🇳 +91</span>
              <input type="tel" id="ship-phone" name="phone" class="phone-input" required value="<?= htmlspecialchars(preg_replace('/^\+91\s*/', '', $currentUser['phone'] ?? '')) ?>" placeholder="98765 43210" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" autocomplete="tel-national" enterkeyhint="next">
            </div>
            <div class="form-hint">Used for order tracking, courier updates, and Mandir dispatch notifications</div>
          </div>

          <!-- Street Address -->
          <div class="form-group">
            <label class="form-label" for="ship-addr">
              Street Address / House / Flat No. <span class="form-label-required">*</span>
            </label>
            <textarea id="ship-addr" name="address" class="form-textarea" rows="3" placeholder="House No, Apartment/Society Name, Landmark, Road, Area" autocomplete="street-address" required></textarea>
          </div>

          <!-- City, State, PIN grid -->
          <div class="form-grid-3">
            <div class="form-group">
              <label class="form-label" for="ship-city">
                City <span class="form-label-required">*</span>
              </label>
              <input type="text" id="ship-city" name="city" class="form-input" placeholder="Surat" autocomplete="address-level2" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="ship-state">
                State <span class="form-label-required">*</span>
              </label>
              <select id="ship-state" name="state" class="form-select" autocomplete="address-level1" onchange="updateShippingState(this.value)" required>
                <?php foreach (get_indian_states() as $stateCode => $stateLabel): ?>
                  <option value="<?= htmlspecialchars($stateCode) ?>" <?= ($shippingState === $stateCode) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($stateLabel) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label" for="ship-pin">
                PIN Code <span class="form-label-required">*</span>
              </label>
              <input type="text" id="ship-pin" name="pincode" class="form-input input-pin" placeholder="395006" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="postal-code" required>
            </div>
          </div>
        </div>

        <!-- STEP 3: Payment Method Card -->
        <div class="ui-card" id="step3Card">
          <div class="ui-card-header">
            <h2 class="ui-card-title">
              <span class="ui-card-step-num">3</span>
              Payment Method
            </h2>
            <span style="font-size: 13px; color: #166534; font-weight: 700;">
              🔒 256-Bit SSL Encrypted
            </span>
          </div>

          <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px;">
            
            <!-- Option 1: Instant UPI / QR -->
            <label class="payment-radio-card selected" onclick="selectPaymentMethod(this)">
              <span class="badge-recommended">Recommended</span>
              <input type="radio" name="payment_method" value="UPI" checked class="form-check-input" style="accent-color: var(--red);">
              <div style="flex: 1;">
                <div style="display: flex; align-items: center; gap: 8px;">
                  <span style="font-size: 18px;">📱</span>
                  <div style="font-weight: 800; font-size: 15px; color: var(--heading);">Instant UPI / QR Code</div>
                </div>
                <div style="font-size: 13px; color: var(--text-muted); margin-top: 3px;">
                  Google Pay, PhonePe, Paytm, BHIM UPI • Pay to: <strong style="color: var(--heading);"><?= defined('STORE_UPI_ID') ? STORE_UPI_ID : '9426551098@upi' ?></strong>
                </div>
              </div>
            </label>

            <!-- Option 2: Credit / Debit Card -->
            <label class="payment-radio-card" onclick="selectPaymentMethod(this)">
              <input type="radio" name="payment_method" value="Card" class="form-check-input" style="accent-color: var(--red);">
              <div style="flex: 1;">
                <div style="display: flex; align-items: center; gap: 8px;">
                  <span style="font-size: 18px;">💳</span>
                  <div style="font-weight: 800; font-size: 15px; color: var(--heading);">Credit / Debit Card</div>
                </div>
                <div style="font-size: 13px; color: var(--text-muted); margin-top: 3px;">
                  Visa, MasterCard, RuPay, Maestro
                </div>
              </div>
            </label>

            <!-- Option 3: Net Banking -->
            <label class="payment-radio-card" onclick="selectPaymentMethod(this)">
              <input type="radio" name="payment_method" value="NetBanking" class="form-check-input" style="accent-color: var(--red);">
              <div style="flex: 1;">
                <div style="display: flex; align-items: center; gap: 8px;">
                  <span style="font-size: 18px;">🏦</span>
                  <div style="font-weight: 800; font-size: 15px; color: var(--heading);">Net Banking</div>
                </div>
                <div style="font-size: 13px; color: var(--text-muted); margin-top: 3px;">
                  SBI, HDFC, ICICI, Axis, Bank of Baroda & 50+ banks
                </div>
              </div>
            </label>

            <!-- Option 4: Cash on Delivery -->
            <label class="payment-radio-card" onclick="selectPaymentMethod(this)">
              <input type="radio" name="payment_method" value="COD" class="form-check-input" style="accent-color: var(--red);">
              <div style="flex: 1;">
                <div style="display: flex; align-items: center; gap: 8px;">
                  <span style="font-size: 18px;">💵</span>
                  <div style="font-weight: 800; font-size: 15px; color: var(--heading);">Cash on Delivery (COD)</div>
                </div>
                <div style="font-size: 13px; color: var(--text-muted); margin-top: 3px;">
                  Pay upon holy delivery at your doorstep
                </div>
              </div>
            </label>

          </div>

          <div style="background: var(--surface-2); border: 1px solid var(--card-border); border-radius: 12px; padding: 14px 18px; margin-bottom: 24px; font-size: 13px; color: var(--text-muted); line-height: 1.5;">
            ℹ️ <strong>Order Process:</strong> Once submitted, your items will be sanctified and dispatched from Shree Swaminarayan Mandir (Kalakunj, Surat). For UPI orders, you can share the transaction screenshot directly on WhatsApp for expedited processing.
          </div>

          <!-- Primary Submit Action Button -->
          <div>
            <button type="submit" class="btn btn-primary btn-block btn-lg" style="height: 52px; font-size: 1.1rem; font-weight: 800; box-shadow: 0 4px 14px var(--primary-glow);">
              Place Order &amp; Pay <?= format_price($finalTotal) ?> &rarr;
            </button>
            <div style="margin-top: 12px; text-align: center; font-size: 12px; color: var(--text-muted);">
              By placing your order, you agree to our <a href="pages.php?slug=terms-conditions" target="_blank" style="color: var(--link); text-decoration: underline;">Terms of Service</a>, <a href="pages.php?slug=privacy-policy" target="_blank" style="color: var(--link); text-decoration: underline;">Privacy Policy</a>, and <a href="pages.php?slug=shipping-policy" target="_blank" style="color: var(--link); text-decoration: underline;">Shipping Policy</a>.
            </div>
          </div>
        </div>

      </form>

    </div>

    <!-- RIGHT COLUMN: Sticky Order Summary (Shopify-Style) -->
    <div class="ui-sticky-side">
      <div class="ui-card" id="desktopSummaryCard">
        
        <div class="ui-card-header">
          <h3 class="ui-card-title" style="font-size: 16px; text-transform: uppercase; letter-spacing: 0.5px;">
            Order Summary
          </h3>
          <a href="cart.php" style="font-size: 13px; font-weight: 700; color: var(--link); text-decoration: underline;">Edit Cart</a>
        </div>

        <!-- Price Breakdown -->
        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px;">
          <span style="color: var(--text-muted);">Subtotal (<?= count($cartItems) ?> items):</span>
          <span style="font-weight: 700; color: var(--text);"><?= format_price($subtotal) ?></span>
        </div>

        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px;">
          <span style="color: var(--text-muted);">Shipping:</span>
          <span id="summaryShippingCharge" style="font-weight: 700; color: <?= $shippingCharge == 0 ? '#166534' : 'var(--text)' ?>;">
            <?= $shippingCharge == 0 ? 'FREE' : format_price($shippingCharge) ?>
          </span>
        </div>

        <?php if ($discount > 0): ?>
          <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; color: #166534;">
            <span>Coupon (<?= htmlspecialchars($coupon['code'] ?? '') ?>):</span>
            <span style="font-weight: 700;">-<?= format_price($discount) ?></span>
          </div>
        <?php endif; ?>

        <div style="display: flex; justify-content: space-between; border-top: 2px solid var(--card-border); padding-top: 14px; margin-top: 12px; font-size: 18px; font-weight: 800; color: var(--heading);">
          <span>Total:</span>
          <span id="summaryFinalTotal" style="color: var(--red-text);"><?= format_price($finalTotal) ?></span>
        </div>

        <div style="font-size: 12px; color: var(--text-muted); margin: 8px 0 16px 0; line-height: 1.4;">
          * Free shipping on orders ₹500+ in Gujarat / ₹750+ across India.
        </div>

        <!-- Product Thumbnails List -->
        <div style="border-top: 1px solid var(--card-border); padding-top: 16px; max-height: 320px; overflow-y: auto;">
          <?php foreach ($cartItems as $it): ?>
            <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 12px;">
              <div style="width: 48px; height: 48px; border-radius: 8px; border: 1.5px solid var(--card-border); background: var(--surface-2); overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <img src="<?= get_product_image_url($it['image'], $it['name']) ?>" alt="<?= htmlspecialchars($it['name']) ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
              </div>
              <div style="flex: 1; min-width: 0;">
                <div style="font-size: 13px; font-weight: 700; color: var(--heading); line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                  <?= htmlspecialchars($it['name']) ?>
                </div>
                <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                  Qty: <?= (int)$it['quantity'] ?> × <?= format_price($it['unit_price']) ?>
                </div>
              </div>
              <div style="font-size: 13px; font-weight: 700; color: var(--text); flex-shrink: 0;">
                <?= format_price($it['unit_price'] * $it['quantity']) ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Trust Badges Row -->
        <div style="margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--card-border); text-align: center; font-size: 12px; color: var(--text-muted); line-height: 1.5;">
          🔒 <strong>Secure Checkout</strong><br>
          100% Authentic Scriptures &amp; Blessed Puja Samagri
        </div>

      </div>
    </div>

  </div>

  <!-- Mobile Sticky Bottom Checkout Bar -->
  <div class="mobile-sticky-checkout-bar" style="display: none; position: fixed; bottom: 0; left: 0; right: 0; background: var(--surface); border-top: 2px solid var(--card-border); padding: 12px 16px; padding-bottom: calc(12px + env(safe-area-inset-bottom)); box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.12); z-index: 1000; align-items: center; justify-content: space-between;">
    <div>
      <div style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Total Amount</div>
      <div id="mobileFinalTotal" style="font-size: 18px; font-weight: 900; color: var(--red-text); line-height: 1.1;"><?= format_price($finalTotal) ?></div>
    </div>
    <button type="button" class="btn btn-primary" onclick="submitMainFormFromMobile()" style="min-height: 44px; padding: 0 24px; font-weight: 800; font-size: 15px;">
      Place Order &rarr;
    </button>
  </div>

</main>

<script>
// Mark page body for floating UI suppression on mobile
document.body.classList.add('page-checkout');

const orderNetSubtotal = <?= (float)max(0, $subtotal - $discount) ?>;
const gujaratFreeLimit = <?= (float)get_setting('free_shipping_gujarat', '500') ?>;
const indiaFreeLimit = <?= (float)get_setting('free_shipping_india', '750') ?>;

function updateShippingState(selectedState) {
  const isGujarat = (selectedState || '').toLowerCase().trim() === 'gujarat';
  let shipping = 0;
  if (isGujarat) {
    shipping = (orderNetSubtotal >= gujaratFreeLimit) ? 0 : 50;
  } else {
    shipping = (orderNetSubtotal >= indiaFreeLimit) ? 0 : 80;
  }
  const total = orderNetSubtotal + shipping;
  
  const shipEl = document.getElementById('summaryShippingCharge');
  if (shipEl) {
    if (shipping === 0) {
      shipEl.textContent = 'FREE';
      shipEl.style.color = '#166534';
    } else {
      shipEl.textContent = '₹' + shipping.toFixed(2);
      shipEl.style.color = 'var(--text)';
    }
  }
  const totalEl = document.getElementById('summaryFinalTotal');
  if (totalEl) {
    totalEl.textContent = '₹' + total.toFixed(2);
  }
  const mobTotalEl = document.getElementById('mobileFinalTotal');
  if (mobTotalEl) {
    mobTotalEl.textContent = '₹' + total.toFixed(2);
  }
}

// Payment Card Selection
function selectPaymentMethod(labelEl) {
  document.querySelectorAll('.payment-radio-card').forEach(c => c.classList.remove('selected'));
  labelEl.classList.add('selected');
  const radio = labelEl.querySelector('input[type="radio"]');
  if (radio) radio.checked = true;
}

// Guest Email Confirmation
function confirmGuestEmail() {
  const emailInput = document.getElementById('chkGuestEmailInput');
  const email = emailInput ? emailInput.value.trim() : '';
  if (!email || !email.includes('@')) {
    alert('Please enter a valid email address.');
    emailInput.focus();
    return;
  }
  document.getElementById('ship-email').value = email;
  
  // Update Step 1 View
  document.getElementById('step1Body').innerHTML = `
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
      <div style="display: flex; align-items: center; gap: 0.75rem;">
        <span style="font-size: 1.5rem;">✓</span>
        <div>
          <div style="font-weight: 700; color: var(--heading);">Guest Checkout Email Confirmed</div>
          <div style="font-size: 0.875rem; color: var(--text-muted);">${email}</div>
        </div>
      </div>
      <button type="button" class="btn btn-secondary btn-sm" onclick="window.location.reload()">Change</button>
    </div>
  `;
  
  // Smooth scroll to Delivery Address
  document.getElementById('step2Card').scrollIntoView({ behavior: 'smooth' });
}

// AJAX Login inside Checkout
function handleCheckoutLogin(e) {
  e.preventDefault();
  const email = document.getElementById('chkLoginEmail').value.trim();
  const password = document.getElementById('chkLoginPass').value;
  const btn = document.getElementById('chkLoginSubmitBtn');
  
  if (btn) btn.disabled = true;
  
  fetch('api/auth.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ action: 'login', email: email, password: password })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      window.location.reload();
    } else {
      alert(data.message || 'Login failed. Please check your credentials.');
      if (btn) btn.disabled = false;
    }
  })
  .catch(() => {
    alert('Network error. Please try again.');
    if (btn) btn.disabled = false;
  });
}

// Mobile Summary Toggle
function toggleMobileSummary() {
  const card = document.getElementById('desktopSummaryCard');
  const chevron = document.getElementById('mobileSummaryChevron');
  const txt = document.getElementById('mobileSummaryText');
  if (!card) return;
  if (card.style.display === 'block') {
    card.style.display = 'none';
    chevron.textContent = '▼';
    txt.textContent = 'Show order summary (<?= count($cartItems) ?> items)';
  } else {
    card.style.display = 'block';
    chevron.textContent = '▲';
    txt.textContent = 'Hide order summary';
  }
}

// Mobile Bottom Bar Form Submission Trigger
function submitMainFormFromMobile() {
  const form = document.getElementById('mainCheckoutForm');
  if (form) {
    if (form.reportValidity()) {
      form.submit();
    }
  }
}

// Responsive Summary & Bottom Bar Visibility
function adjustMobileCheckoutUI() {
  const isMobile = window.innerWidth < 992;
  const mobToggle = document.querySelector('.mobile-summary-toggle');
  const mobBottomBar = document.querySelector('.mobile-sticky-checkout-bar');
  const summaryCard = document.getElementById('desktopSummaryCard');

  if (isMobile) {
    if (mobToggle) mobToggle.style.display = 'flex';
    if (mobBottomBar) mobBottomBar.style.display = 'flex';
    if (summaryCard) summaryCard.style.display = 'none';
  } else {
    if (mobToggle) mobToggle.style.display = 'none';
    if (mobBottomBar) mobBottomBar.style.display = 'none';
    if (summaryCard) summaryCard.style.display = 'block';
  }
}

window.addEventListener('resize', adjustMobileCheckoutUI);
document.addEventListener('DOMContentLoaded', adjustMobileCheckoutUI);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
