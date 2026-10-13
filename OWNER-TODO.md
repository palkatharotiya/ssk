# SSK Store — Owner Pre-Launch Action Checklist
**Website**: Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)  
**Target**: Mandir Management & Store Owner

---

This document outlines the **critical actions that only you (the store owner/mandir management)** can perform before opening the store to public devotees. Complete these items in order:

---

### [ ] 1. Domain & Hosting Setup
* **Action**: Purchase or connect your official domain name (e.g., `sskstore.in` or `sahajanandsahitya.org`) to your web hosting provider.
* **SSL Certificate**: Ensure HTTPS (green lock) is active on your live domain using free Let's Encrypt / AutoSSL in your hosting control panel.
* **Update Config**: In `includes/config.php`, set `define('SITE_URL', 'https://your-live-domain.com');`.

---

### [ ] 2. Update Default Admin Account Password
* **Action**: Log in to `/admin/login.php` with default username `admin` and password `admin123`.
* **Security**: Go to **Roles & Permissions / Admin Users** (`admin_users.php`), edit the administrator account, and change the password to a strong, private password known only to authorized mandir trustees.

---

### [ ] 3. Verify Mandir UPI ID & WhatsApp Number
* **Action**: Open `includes/config.php` and verify these central payment constants match the temple's official bank account:
  ```php
  define('STORE_UPI_ID', '9426551098@upi');               // Must match your registered temple UPI handle
  define('STORE_UPI_NAME', 'Shree Sahajanand Sahitya Kendra'); // Name registered with your bank
  define('STORE_WHATSAPP_NUMBER', '+91 9426-55-1098');     // Active phone for receiving payment screenshots
  ```
* **Verify QR Code**: If you have an official bank QR code image, place it in `assets/images/temple_qr.png`.

---

### [ ] 4. Perform a Real ₹1 Test Order
* **Action**: As a customer, open the store in your browser or phone, add any scripture or puja item, and proceed to checkout.
* **Payment**: Choose **UPI / QR Code**, scan the QR code or pay using your phone's GPay/PhonePe/Paytm app with a real test payment of ₹1.
* **Confirmation**: Click **Place Order & Pay**, verify the receipt page appears, and click the **Send Payment Screenshot on WhatsApp** button.
* **Seva Staff Check**: Verify that your mandir seva WhatsApp receives the message and that the order appears inside `/admin/orders.php`.

---

### [ ] 5. Review Legal, Trust & Return Policies
* **Action**: Open each policy page and ensure the text aligns with your temple's policies:
  * `/pages.php?slug=about-us` (History and mission of Kalakunj Mandir Kendra)
  * `/pages.php?slug=contact-us` (Physical address, office hours, dispatch days)
  * `/pages.php?slug=shipping-policy` (Delivery timeframes: 2-4 days Gujarat, 4-7 days rest of India)
  * `/pages.php?slug=return-policy` (Policy on damaged scriptures or sacred items)
  * `/pages.php?slug=terms-conditions` & `/pages.php?slug=privacy-policy`

---

### [ ] 6. Real Phone & Device Testing
* **Action**: Test the store on real Android and iPhone mobile devices:
  * Open in mobile Chrome and test the **"Install App"** PWA button.
  * Open in iPhone Safari, tap **Share** → **Add to Home Screen**, and verify the app icon launches cleanly in standalone mode.
  * Test dialing the mandir helpline directly from the header phone link (`+91 9426-55-1098`).
  * Verify that Gujarati devotional fonts (`જય સ્વામિનારાયણ`) render legibly on older mobile handsets.

---

### [ ] 7. Submit Sitemap to Google Search Console
* **Action**: Sign in to [Google Search Console](https://search.google.com/search-console), verify your domain, and submit `https://your-domain.com/sitemap.xml`.
* This will allow devotees searching for "Sahajanand Sahitya Kendra Kalakunj Mandir" or specific scriptures like "Vachanamrut Study Guide" to find the store on Google.
