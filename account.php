<?php
/**
 * SSK Store - Customer Account Portal & Order History
 * Shree Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$isLoggedIn = is_logged_in();
$user = $isLoggedIn ? current_user() : null;
$customerId = $user ? (int)$user['id'] : 0;

// Handle Profile Update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile']) && $isLoggedIn) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Security token expired. Please refresh and try again.');
        header('Location: account.php#profile');
        exit;
    }

    $fn = trim($_POST['first_name'] ?? '');
    $ln = trim($_POST['last_name'] ?? '');
    $ph = trim($_POST['phone'] ?? '');

    // Normalize phone
    $cleanPhone = preg_replace('/[^0-9]/', '', $ph);
    if (strlen($cleanPhone) === 12 && substr($cleanPhone, 0, 2) === '91') {
        $cleanPhone = substr($cleanPhone, 2);
    }

    if ($fn && $ln) {
        db_query("UPDATE customers SET first_name = ?, last_name = ?, phone = ? WHERE id = ?", [$fn, $ln, $cleanPhone, $customerId]);
        set_flash('success', 'Profile details updated successfully.');
        header('Location: account.php#profile');
        exit;
    }
}

// Fetch Customer Orders if logged in
$orders = [];
$wishlistItems = [];
if ($isLoggedIn) {
    $orders = db_fetch_all("SELECT * FROM orders WHERE customer_id = ? ORDER BY id DESC", [$customerId]);
    $wishlistItems = db_fetch_all("SELECT p.*, d.name as dept_name 
                                   FROM wishlist w 
                                   JOIN products p ON w.product_id = p.id 
                                   LEFT JOIN departments d ON p.department_id = d.id 
                                   WHERE w.customer_id = ? AND p.is_active = 1 
                                   ORDER BY w.id DESC", [$customerId]);
}

$pageTitle = ($isLoggedIn ? 'My Account' : 'Sign In / Register') . ' — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<main class="container" style="margin-top: 1.5rem; margin-bottom: 4rem;">

  <?= render_breadcrumb([
    ['label' => 'Home', 'url' => 'index.php'],
    ['label' => $isLoggedIn ? 'My Account' : 'Customer Sign In']
  ]) ?>

  <?php if (!$isLoggedIn): ?>
    <!-- Non-Logged-In View: Dedicated Login & Register Form Cards -->
    <div style="max-width: 520px; margin: 2rem auto;">
      <div class="ui-card">
        <div style="text-align: center; margin-bottom: 2rem;">
          <h1 style="font-size: 1.75rem; font-weight: 800; color: var(--heading); margin: 0 0 0.5rem;">Customer Account</h1>
          <p style="color: var(--text-muted); font-size: 0.9375rem; margin: 0;">Sign in to view your orders, addresses, and wishlist</p>
        </div>

        <div style="display: flex; border-bottom: 1.5px solid var(--card-border); margin-bottom: 1.75rem;">
          <button type="button" id="tabBtnSignIn" onclick="switchAuthView('signin')" style="flex: 1; padding: 0.75rem; border: none; background: none; font-weight: 700; font-size: 1rem; color: var(--red-text); border-bottom: 3px solid var(--red-text); cursor: pointer;">
            Sign In
          </button>
          <button type="button" id="tabBtnRegister" onclick="switchAuthView('register')" style="flex: 1; padding: 0.75rem; border: none; background: none; font-weight: 600; font-size: 1rem; color: var(--text-muted); border-bottom: 3px solid transparent; cursor: pointer;">
            Create Account
          </button>
        </div>

        <!-- Inline Sign In Form -->
        <div id="authViewSignIn">
          <form id="inlineLoginForm" onsubmit="handleInlineAuth(event, 'login')">
            <div class="form-group">
              <label class="form-label form-label-required" for="acc-email">Email Address</label>
              <input type="email" id="acc-email" name="email" class="form-input" placeholder="name@example.com" autocomplete="email" required>
            </div>

            <div class="form-group">
              <label class="form-label form-label-required" for="acc-password">Password</label>
              <div style="position: relative;">
                <input type="password" id="acc-password" name="password" class="form-input" placeholder="••••••••" autocomplete="current-password" required style="padding-right: 44px;">
                <button type="button" onclick="togglePasswordVisibility('acc-password', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 4px;" aria-label="Toggle password visibility">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
              </div>
              <div style="display: flex; justify-content: flex-end; margin-top: 0.5rem; font-size: 0.8125rem;">
                <a href="forgot-password.php" style="color: var(--link); font-weight: 600;">Forgot password?</a>
              </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 1rem;">
              Sign In to Account &rarr;
            </button>
          </form>
        </div>

        <!-- Inline Register Form -->
        <div id="authViewRegister" style="display: none;">
          <form id="inlineRegisterForm" onsubmit="handleInlineAuth(event, 'register')">
            <div class="form-grid-2">
              <div class="form-group">
                <label class="form-label form-label-required" for="reg-fn">First Name</label>
                <input type="text" id="reg-fn" name="first_name" class="form-input" placeholder="Rajesh" autocomplete="given-name" required>
              </div>
              <div class="form-group">
                <label class="form-label form-label-required" for="reg-ln">Last Name</label>
                <input type="text" id="reg-ln" name="last_name" class="form-input" placeholder="Patel" autocomplete="family-name" required>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label form-label-required" for="reg-em">Email Address</label>
              <input type="email" id="reg-em" name="email" class="form-input" placeholder="name@example.com" autocomplete="email" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="reg-ph">Mobile Phone <span class="form-label-optional">(Optional)</span></label>
              <div class="input-phone-wrap">
                <div class="phone-prefix-chip">+91</div>
                <input type="tel" id="reg-ph" name="phone" class="form-input" placeholder="9876543210" pattern="[0-9]{10}" inputmode="numeric" maxlength="10">
              </div>
            </div>

            <div class="form-group">
              <label class="form-label form-label-required" for="reg-pw">Create Password</label>
              <div style="position: relative;">
                <input type="password" id="reg-pw" name="password" class="form-input" placeholder="Minimum 6 characters" autocomplete="new-password" required minlength="6" style="padding-right: 44px;">
                <button type="button" onclick="togglePasswordVisibility('reg-pw', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 4px;" aria-label="Toggle password visibility">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
              </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 1rem;">
              Create My Account &rarr;
            </button>
          </form>
        </div>

      </div>
    </div>

    <script>
    function switchAuthView(view) {
      const isSignIn = view === 'signin';
      document.getElementById('authViewSignIn').style.display = isSignIn ? 'block' : 'none';
      document.getElementById('authViewRegister').style.display = isSignIn ? 'none' : 'block';
      document.getElementById('tabBtnSignIn').style.color = isSignIn ? 'var(--red-text)' : 'var(--text-muted)';
      document.getElementById('tabBtnSignIn').style.borderBottomColor = isSignIn ? 'var(--red-text)' : 'transparent';
      document.getElementById('tabBtnRegister').style.color = !isSignIn ? 'var(--red-text)' : 'var(--text-muted)';
      document.getElementById('tabBtnRegister').style.borderBottomColor = !isSignIn ? 'var(--red-text)' : 'transparent';
    }

    function handleInlineAuth(e, action) {
      e.preventDefault();
      const form = e.target;
      const formData = new FormData(form);
      formData.append('action', action);

      fetch('api/auth.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            showToast(res.message || 'Authentication successful', 'success');
            setTimeout(() => window.location.href = 'account.php', 600);
          } else {
            showToast(res.message || 'Error occurred', 'error');
          }
        })
        .catch(() => showToast('Network error, please try again', 'error'));
    }
    </script>

  <?php else: ?>
    <!-- Logged-In Account Management View -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1.5px solid var(--card-border); padding-bottom: 1rem;">
      <div>
        <h1 style="font-size: 2rem; font-weight: 800; color: var(--heading); margin: 0 0 0.35rem;">My Account</h1>
        <p style="color: var(--text-muted); font-size: 0.9375rem; margin: 0;">
          Welcome back, <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong> (<?= htmlspecialchars($user['email']) ?>)
        </p>
      </div>
      <a href="#" onclick="event.preventDefault(); fetch('api/auth.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': '<?= get_csrf_token() ?>' }, body: 'action=logout&csrf_token=<?= get_csrf_token() ?>' }).then(() => window.location.href='index.php');" class="btn btn-secondary btn-sm" style="color: var(--danger); border-color: rgba(239, 68, 68, 0.3);">
        Sign Out
      </a>
    </div>

    <div style="display: grid; grid-template-columns: 260px 1fr; gap: 2rem; align-items: start;">
      
      <!-- Account Sidebar Menu -->
      <aside class="ui-card" style="padding: 1.25rem; height: fit-content;">
        <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.5rem; margin: 0; padding: 0;">
          <li>
            <a href="#orders" onclick="switchAccountTab('orders')" id="nav-tab-orders" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-radius: 10px; font-weight: 700; background: var(--surface-2); color: var(--red-text); text-decoration: none;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-2z"></path><line x1="3" y1="6" x2="21" y2="6"></line></svg>
              My Orders (<?= count($orders) ?>)
            </a>
          </li>
          <li>
            <a href="#wishlist" onclick="switchAccountTab('wishlist')" id="nav-tab-wishlist" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-radius: 10px; font-weight: 600; color: var(--text); text-decoration: none;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
              Wishlist (<?= count($wishlistItems) ?>)
            </a>
          </li>
          <li>
            <a href="#profile" onclick="switchAccountTab('profile')" id="nav-tab-profile" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-radius: 10px; font-weight: 600; color: var(--text); text-decoration: none;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
              Profile Settings
            </a>
          </li>
        </ul>
      </aside>

      <!-- Main Account Content Panels -->
      <div>
        
        <!-- Panel 1: Orders -->
        <section id="panel-orders">
          <h2 style="font-size: 1.35rem; font-weight: 800; margin-bottom: 1.25rem; color: var(--heading);">Order History</h2>
          <?php if (empty($orders)): ?>
            <div class="ui-card" style="padding: 3.5rem 2rem; text-align: center;">
              <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
              <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--heading); margin-bottom: 0.5rem;">No orders placed yet</h3>
              <p style="color: var(--text-muted); margin-bottom: 1.5rem;">When you complete purchases, your order receipts and tracking appear here.</p>
              <a href="catalog.php" class="btn btn-primary btn-md">Explore Devotional Products</a>
            </div>
          <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
              <?php foreach ($orders as $ord): ?>
                <div class="ui-card" style="padding: 1.5rem;">
                  <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1.5px solid var(--card-border); padding-bottom: 0.75rem; margin-bottom: 1rem;">
                    <div>
                      <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Order #</span>
                      <strong style="color: var(--red-text); font-size: 1rem;"><?= htmlspecialchars($ord['order_number']) ?></strong>
                      <span style="color: var(--text-muted); margin-left: 0.75rem; font-size: 0.8125rem;">Placed on <?= date('d M Y', strtotime($ord['created_at'])) ?></span>
                    </div>
                    <div>
                      <span style="display: inline-block; padding: 0.3rem 0.75rem; border-radius: var(--radius-full); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; background: var(--surface-2); color: var(--info); border: 1px solid var(--card-border);">
                        <?= htmlspecialchars($ord['status']) ?>
                      </span>
                    </div>
                  </div>

                  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div>
                      <div style="font-size: 0.875rem;"><strong>Shipped To:</strong> <?= htmlspecialchars($ord['shipping_name']) ?> (<?= htmlspecialchars($ord['shipping_city']) ?>)</div>
                      <div style="font-size: 0.8125rem; color: var(--text-muted); margin-top: 2px;">Payment: <?= htmlspecialchars($ord['payment_method']) ?> (<?= htmlspecialchars($ord['payment_status']) ?>)</div>
                    </div>
                    <div style="text-align: right;">
                      <div style="font-size: 1.25rem; font-weight: 800; color: var(--heading);"><?= format_price($ord['total']) ?></div>
                      <a href="order-success.php?id=<?= $ord['id'] ?>" class="btn btn-secondary btn-sm" style="margin-top: 0.35rem;">View Invoice & Details</a>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>

        <!-- Panel 2: Wishlist -->
        <section id="panel-wishlist" style="display: none;">
          <h2 style="font-size: 1.35rem; font-weight: 800; margin-bottom: 1.25rem; color: var(--heading);">My Wishlist</h2>
          <?php if (empty($wishlistItems)): ?>
            <div class="ui-card" style="padding: 3.5rem 2rem; text-align: center;">
              <div style="font-size: 3rem; margin-bottom: 1rem;">💖</div>
              <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--heading); margin-bottom: 0.5rem;">Your wishlist is empty</h3>
              <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Save your favorite scriptures and items to revisit anytime.</p>
              <a href="catalog.php" class="btn btn-primary btn-md">Browse Catalog</a>
            </div>
          <?php else: ?>
            <div class="products-grid" style="grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));">
              <?php foreach ($wishlistItems as $wItem): ?>
                <?php
                  $wPrice = (float)$wItem['price'];
                  $wSale = (float)($wItem['sale_price'] ?? 0);
                  $wDisplay = ($wSale > 0 && $wSale < $wPrice) ? $wSale : $wPrice;
                  $wImg = get_product_image_url($wItem['image'], $wItem['name']);
                ?>
                <article class="product-card">
                  <div class="card-img-wrap">
                    <a href="product.php?id=<?= $wItem['id'] ?>">
                      <img src="<?= $wImg ?>" alt="<?= htmlspecialchars($wItem['name']) ?>" loading="lazy">
                    </a>
                  </div>
                  <div class="card-body">
                    <h3 class="card-title">
                      <a href="product.php?id=<?= $wItem['id'] ?>"><?= htmlspecialchars($wItem['name']) ?></a>
                    </h3>
                    <div class="card-pricing">
                      <span class="current-price"><?= format_price($wDisplay) ?></span>
                    </div>
                    <div style="display: flex; gap: 0.5rem; margin-top: 0.75rem;">
                      <?php if ((int)$wItem['stock_qty'] <= 0): ?>
                        <button type="button" class="btn btn-secondary btn-sm" disabled style="flex: 1; opacity: 0.65; cursor: not-allowed;">Out of Stock</button>
                      <?php else: ?>
                        <button type="button" class="btn btn-primary btn-sm btn-add-cart" data-product-id="<?= $wItem['id'] ?>" style="flex: 1;">Add to Cart</button>
                      <?php endif; ?>
                      <button type="button" class="btn btn-secondary btn-sm card-wishlist-btn active" data-product-id="<?= $wItem['id'] ?>" title="Remove" style="position: static; width: auto; height: auto;">✕</button>
                    </div>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>

        <!-- Panel 3: Profile Settings -->
        <section id="panel-profile" style="display: none;">
          <h2 style="font-size: 1.35rem; font-weight: 800; margin-bottom: 1.25rem; color: var(--heading);">Profile Settings</h2>
          <div class="ui-card" style="padding: 2rem;">
            <form method="POST" action="account.php">
              <?= csrf_field() ?>
              <input type="hidden" name="update_profile" value="1">
              
              <div class="form-grid-2">
                <div class="form-group">
                  <label class="form-label form-label-required" for="prof-fn">First Name</label>
                  <input type="text" id="prof-fn" name="first_name" class="form-input" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                </div>
                <div class="form-group">
                  <label class="form-label form-label-required" for="prof-ln">Last Name</label>
                  <input type="text" id="prof-ln" name="last_name" class="form-input" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label" for="prof-em">Email Address</label>
                <input type="email" id="prof-em" class="form-input" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Email is linked to your account and cannot be modified.</div>
              </div>

              <div class="form-group">
                <label class="form-label" for="prof-ph">Mobile Phone</label>
                <div class="input-phone-wrap">
                  <div class="phone-prefix-chip">+91</div>
                  <input type="tel" id="prof-ph" name="phone" class="form-input" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="9876543210" pattern="[0-9]{10}" inputmode="numeric" maxlength="10">
                </div>
              </div>

              <button type="submit" class="btn btn-primary btn-md">✓ Save Changes</button>
            </form>
          </div>
        </section>

      </div>

    </div>

    <script>
    function switchAccountTab(tab) {
      ['orders', 'wishlist', 'profile'].forEach(t => {
        const panel = document.getElementById('panel-' + t);
        const navBtn = document.getElementById('nav-tab-' + t);
        if (panel) panel.style.display = (t === tab) ? 'block' : 'none';
        if (navBtn) {
          if (t === tab) {
            navBtn.style.background = 'var(--surface-2)';
            navBtn.style.color = 'var(--red-text)';
            navBtn.style.fontWeight = '700';
          } else {
            navBtn.style.background = 'none';
            navBtn.style.color = 'var(--text)';
            navBtn.style.fontWeight = '600';
          }
        }
      });
    }

    if (window.location.hash) {
      const hash = window.location.hash.substring(1);
      if (['orders', 'wishlist', 'profile'].includes(hash)) {
        switchAccountTab(hash);
      }
    }
    </script>
  <?php endif; ?>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
