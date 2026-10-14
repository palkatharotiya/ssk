<?php
/**
 * SSK Store - Main Footer Template
 * Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 */
?>
  <!-- Footer -->
  <!-- Footer (BAPS.store style sleek 2-tier horizontal layout) -->
  <footer class="main-footer">
    <div class="container footer-tier-top">
      <nav class="footer-links-row" aria-label="Footer Policy Links">
        <a href="pages.php?slug=privacy-policy">Privacy Policy</a>
        <span class="footer-bullet" aria-hidden="true">•</span>
        <a href="pages.php?slug=return-policy">Return Policy</a>
        <span class="footer-bullet" aria-hidden="true">•</span>
        <a href="pages.php?slug=terms-conditions">Terms & Conditions</a>
        <span class="footer-bullet" aria-hidden="true">•</span>
        <a href="pages.php?slug=about-us">About Us</a>
        <span class="footer-bullet" aria-hidden="true">•</span>
        <a href="pages.php?slug=contact-us">Contact Us</a>
        <span class="footer-bullet" aria-hidden="true">•</span>
        <a href="mailto:contact@sskstore.in" class="footer-email-link">Email: contact@sskstore.in</a>
      </nav>
    </div>

    <!-- Bottom Copyright -->
    <div class="footer-bottom">
      <div class="container text-center">
        &copy; 2026 Shri Sahajanand Sahitya Kendra (Kalakunj Mandir), Shree Swaminarayan Mandir, Kalakunj, Surat. All Rights Reserved.
      </div>
    </div>
  </footer>


  <!-- Mobile Bottom Navigation Bar (Fixed 56px + Safe-Area) -->
  <nav class="mobile-bottom-nav" aria-label="Mobile Navigation">
    <div class="mobile-nav-items">
      <a href="index.php" class="mobile-nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
        <span>Home</span>
      </a>
      <a href="catalog.php" class="mobile-nav-link <?= basename($_SERVER['PHP_SELF']) === 'catalog.php' ? 'active' : '' ?>">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
        <span>Categories</span>
      </a>
      <a href="cart.php" class="mobile-nav-link <?= basename($_SERVER['PHP_SELF']) === 'cart.php' ? 'active' : '' ?>">
        <div class="mobile-nav-icon-wrap" style="position: relative; display: inline-flex;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-2z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
          <span class="badge-count badge-cart-count mobile-badge-count" style="<?= ($cartCount ?? 0) > 0 ? '' : 'display:none;' ?>"><?= $cartCount ?? 0 ?></span>
        </div>
        <span>Cart</span>
      </a>
      <a href="<?= is_logged_in() ? 'account.php#orders' : 'javascript:openAuthModal(\'login\')' ?>" class="mobile-nav-link <?= (basename($_SERVER['PHP_SELF']) === 'account.php' && strpos($_SERVER['REQUEST_URI'] ?? '', 'orders') !== false) ? 'active' : '' ?>">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
        <span>Orders</span>
      </a>
      <a href="<?= is_logged_in() ? 'account.php' : 'javascript:openAuthModal(\'login\')' ?>" class="mobile-nav-link <?= (basename($_SERVER['PHP_SELF']) === 'account.php' && strpos($_SERVER['REQUEST_URI'] ?? '', 'orders') === false) ? 'active' : '' ?>">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        <span>Account</span>
      </a>
    </div>
  </nav>

  <!-- Login / Register Modal Dialog -->
  <div class="modal-overlay" id="auth-modal">
    <div class="modal-box">
      <button class="modal-close" onclick="closeAuthModal()">&times;</button>
      
      <div class="modal-tabs">
        <div class="modal-tab active" id="tab-login">Sign In</div>
        <div class="modal-tab" id="tab-register">New Account</div>
      </div>

      <!-- Login Form -->
      <form id="form-login">
        <div class="form-group">
          <label class="form-label form-label-required" for="login-email">Email Address</label>
          <input type="email" id="login-email" name="email" class="form-input" placeholder="name@example.com" autocomplete="email" required>
        </div>
        <div class="form-group">
          <label class="form-label form-label-required" for="login-password">Password</label>
          <div style="position: relative;">
            <input type="password" id="login-password" name="password" class="form-input" placeholder="••••••••" autocomplete="current-password" required style="padding-right: 44px;">
            <button type="button" onclick="togglePasswordVisibility('login-password', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 4px;" aria-label="Toggle password visibility">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
            </button>
          </div>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; font-size: 0.8125rem;">
          <label style="display: flex; align-items: center; gap: 0.35rem; cursor: pointer; color: var(--text);">
            <input type="checkbox" checked style="width: 16px; height: 16px; accent-color: var(--primary);"> Remember me
          </label>
          <a href="forgot-password.php" style="color: var(--link); font-weight: 600;">Forgot password?</a>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg" id="modalLoginSubmitBtn">Sign In to Account</button>
      </form>

      <!-- Register Form -->
      <form id="form-register" style="display: none;">
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label form-label-required" for="reg-first">First Name</label>
            <input type="text" id="reg-first" name="first_name" class="form-input" placeholder="Rajesh" autocomplete="given-name" required>
          </div>
          <div class="form-group">
            <label class="form-label form-label-required" for="reg-last">Last Name</label>
            <input type="text" id="reg-last" name="last_name" class="form-input" placeholder="Patel" autocomplete="family-name" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label form-label-required" for="reg-email">Email Address</label>
          <input type="email" id="reg-email" name="email" class="form-input" placeholder="name@example.com" autocomplete="email" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="reg-phone">Mobile Phone <span class="form-label-optional">(Optional)</span></label>
          <div class="input-phone-wrap">
            <div class="phone-prefix-chip">+91</div>
            <input type="tel" id="reg-phone" name="phone" class="form-input" placeholder="9876543210" pattern="[0-9]{10}" inputmode="numeric" maxlength="10">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label form-label-required" for="reg-password">Create Password</label>
          <div style="position: relative;">
            <input type="password" id="reg-password" name="password" class="form-input" placeholder="Minimum 6 characters" autocomplete="new-password" required minlength="6" style="padding-right: 44px;">
            <button type="button" onclick="togglePasswordVisibility('reg-password', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 4px;" aria-label="Toggle password visibility">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
            </button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg" id="modalRegSubmitBtn">Create Sacred Account</button>
      </form>

    </div>
  </div>

  <!-- Direct WhatsApp Floating Action Button (Icon-Only Circle, Lifted, Never Covering Content) -->
  <a href="<?= defined('STORE_WHATSAPP_URL') ? STORE_WHATSAPP_URL : 'https://wa.me/919426551098?text=Jai%20Swaminarayan' ?>" target="_blank" rel="noopener" class="whatsapp-float" aria-label="Chat on WhatsApp" title="Chat on WhatsApp (<?= defined('STORE_WHATSAPP_NUMBER') ? STORE_WHATSAPP_NUMBER : '+91 9426-55-1098' ?>)">
    <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor">
      <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
    </svg>
  </a>

  <!-- Back to Top Floating Button -->
  <button id="backToTop" class="back-to-top" aria-label="Back to top" title="Back to Top">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
      <polyline points="18 15 12 9 6 15"></polyline>
    </svg>
  </button>

  <!-- Toast Alerts Container -->
  <div class="toast-container"></div>

  <!-- Theme Switcher, Notifications, PWA Engine & Main JavaScript Files -->
  <script src="<?= asset_url('assets/js/theme-toggle.js') ?>"></script>
  <script src="<?= asset_url('assets/js/notifications.js') ?>"></script>
  <script src="<?= asset_url('assets/js/pwa-strings.js') ?>"></script>
  <script src="<?= asset_url('assets/js/pwa-install.js') ?>"></script>
  <script src="<?= asset_url('assets/js/main.js') ?>"></script>
</body>
</html>
