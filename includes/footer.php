<?php
/**
 * SSK Store - Main Footer Template
 * Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 */
?>
  <!-- Footer -->
  <footer class="main-footer">
    <div class="container">
      
      <!-- Newsletter Row -->
      <div style="background: rgba(255, 255, 255, 0.05); padding: 2rem 2.5rem; border-radius: var(--radius-lg); margin-bottom: 3.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: gap: 1.5rem; border: 1px solid rgba(255, 255, 255, 0.1);">
        <div>
          <h3 style="color: #ffffff; font-size: 1.35rem; margin-bottom: 0.25rem;">Subscribe to Satsang Newsletter</h3>
          <p style="color: #a8a29e; font-size: 0.875rem;">Get updates on new spiritual publications, festival specials, and devotional releases.</p>
        </div>
        <form style="display: flex; gap: 0.5rem; max-width: 420px; width: 100%;" onsubmit="event.preventDefault(); showToast('Thank you for subscribing!', 'success'); this.reset();">
          <input type="email" placeholder="Enter your email address" required style="flex: 1; padding: 0.7rem 1rem; border-radius: var(--radius-md); border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.3); color: #fff; outline: none;">
          <button type="submit" class="btn btn-gold btn-sm">Subscribe</button>
        </form>
      </div>

      <!-- Main Footer Columns -->
      <div class="footer-grid">
        
        <!-- Col 1: About -->
        <div class="footer-col">
          <div style="margin-bottom: 1.25rem;">
            <div class="footer-logo-plate" style="display: inline-block; background: #ffffff; padding: 6px 14px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
              <img src="assets/brand/logo.png" alt="Shri Sahajanand Sahitya Kendra — Kalakunj Mandir" style="height: 48px; width: auto; max-width: 210px; object-fit: contain;">
            </div>
          </div>
          <p style="font-size: 0.875rem; color: #a8a29e; margin-bottom: 1.25rem; line-height: 1.6;">
            <strong>Shri Sahajanand Sahitya Kendra (Kalakunj Mandir)</strong> — delivering authentic spiritual scriptures, sanctified tulsi kanthis, handcrafted ghar mandirs, and sacred puja samagri to devotees worldwide.
          </p>
          <div style="display: flex; gap: 0.75rem; align-items: center;">
            <!-- Direct WhatsApp Chat -->
            <a href="https://wa.me/919426551098?text=Jai%20Swaminarayan" target="_blank" rel="noopener" class="social-icon-btn social-whatsapp" title="Direct WhatsApp Chat: +91 9426-55-1098">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
              </svg>
            </a>
            <!-- Direct Instagram Profile -->
            <a href="https://www.instagram.com/s_s_sahitya_kendra" target="_blank" rel="noopener" class="social-icon-btn social-instagram" title="Follow Instagram: @s_s_sahitya_kendra">
              <svg width="19" height="19" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
              </svg>
            </a>
            <!-- Facebook -->
            <a href="https://www.facebook.com" target="_blank" rel="noopener" class="social-icon-btn social-facebook" title="Facebook">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
              </svg>
            </a>
            <!-- YouTube -->
            <a href="https://www.youtube.com" target="_blank" rel="noopener" class="social-icon-btn social-youtube" title="YouTube">
              <svg width="19" height="19" viewBox="0 0 24 24" fill="currentColor">
                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
              </svg>
            </a>
          </div>
        </div>

        <!-- Col 2: Categories -->
        <div class="footer-col">
          <h4>Spiritual Categories</h4>
          <ul class="footer-links">
            <li><a href="catalog.php?dept=1">Books & Publications</a></li>
            <li><a href="catalog.php?dept=2">Tulsi Kanthis</a></li>
            <li><a href="catalog.php?dept=3">Devotional Japamalas</a></li>
            <li><a href="catalog.php?dept=4">Carved Ghar Mandirs</a></li>
            <li><a href="catalog.php?dept=5">Brass Puja Items</a></li>
            <li><a href="catalog.php?dept=6">Sacred Murtis</a></li>
            <li><a href="catalog.php?dept=7">Agarbatti & Dhoop</a></li>
          </ul>
        </div>

        <!-- Col 3: Customer Care -->
        <div class="footer-col">
          <h4>Customer Care</h4>
          <ul class="footer-links">
            <li><a href="account.php">Order Tracking</a></li>
            <li><a href="pages.php?slug=about-us">About Kalakunj Mandir</a></li>
            <li><a href="pages.php?slug=shipping-policy">Shipping & Delivery Policy</a></li>
            <li><a href="pages.php?slug=return-policy">Return & Refund Policy</a></li>
            <li><a href="pages.php?slug=privacy-policy">Privacy Policy</a></li>
            <li><a href="pages.php?slug=terms-conditions">Terms & Conditions</a></li>
            <li><a href="pages.php?slug=contact-us">Customer Support</a></li>
            <li><a href="admin/login.php" style="color: var(--gold);">Admin Portal</a></li>
          </ul>
        </div>

        <!-- Col 4: Contact & Mandir Info -->
        <div class="footer-col">
          <h4>Headquarters</h4>
          <div style="font-size: 0.875rem; color: #a8a29e; display: flex; flex-direction: column; gap: 0.75rem;">
            <p><strong>Shree Swaminarayan Mandir, Kalakunj</strong><br>
            Varachha Main Rd, opp. Water Tank, Kalakunj Society, Swaminarayan Nagar-2, Kapodra Patiya, Surat, Gujarat 395006, India</p>
            <p>
              <strong>Helpline:</strong> <a href="tel:+919426551098" style="color: #fff; font-weight: 700;">+91 9426-55-1098</a><br>
              <strong>WhatsApp:</strong> <a href="https://wa.me/919426551098?text=Jai%20Swaminarayan" target="_blank" rel="noopener" style="color: #25D366; font-weight: 700;">+91 9426-55-1098 (Click to Chat)</a><br>
              <strong>Instagram:</strong> <a href="https://www.instagram.com/s_s_sahitya_kendra" target="_blank" rel="noopener" style="color: #f09433; font-weight: 700;">@s_s_sahitya_kendra</a><br>
              <strong>Email:</strong> contact@sskstore.in
            </p>
            <div style="background: rgba(255,255,255,0.06); padding: 0.6rem 0.85rem; border-radius: 6px; border-left: 3px solid var(--gold);">
              <p style="margin: 0; color: #e2b143; font-weight: 700; font-size: 0.8125rem;">Mandir & Store Operating Hours:</p>
              <p style="margin: 0.25rem 0 0 0; color: #fff; font-size: 0.8125rem;">
                <strong>Daily (Mon – Sun):</strong><br>
                8:00 AM – 12:00 PM & 4:00 PM – 8:00 PM IST
              </p>
            </div>
            <div style="margin-top: 0.5rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
              <span style="background: rgba(255,255,255,0.1); padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.75rem; color: #fff;">100% Authentic</span>
              <span style="background: rgba(255,255,255,0.1); padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.75rem; color: #fff;">Sanctified</span>
              <span style="background: rgba(255,255,255,0.1); padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.75rem; color: #fff;">Secure SSL</span>
            </div>
          </div>
        </div>

      </div>

    </div>

    <!-- Bottom Copyright & Payments -->
    <div class="footer-bottom">
      <div class="container footer-bottom-inner">
        <div>
          &copy; <?= date('Y') ?> Shri Sahajanand Sahitya Kendra (Kalakunj Mandir). All Rights Reserved.
        </div>
        <div style="display: flex; align-items: center; gap: 1rem;">
          <span style="font-size: 0.75rem; color: #a8a29e;">Accepted Payments:</span>
          <span style="font-weight: 700; color: #fff; letter-spacing: 1px;">UPI • RuPay • VISA • MASTERCARD • NETBANKING</span>
        </div>
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

  <!-- Theme Switcher, PWA Engine & Main JavaScript Files -->
  <script src="<?= asset_url('assets/js/theme-toggle.js') ?>"></script>
  <script src="<?= asset_url('assets/js/pwa-strings.js') ?>"></script>
  <script src="<?= asset_url('assets/js/pwa-install.js') ?>"></script>
  <script src="<?= asset_url('assets/js/main.js') ?>"></script>
</body>
</html>
