# SSK Store — Go-Live Publishing Guide
**Website**: Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)  
**Target Audience**: Non-technical Store Owner & Mandir Seva Committee  
**Version**: 2.0 (Production Release)

---

## 1. Hosting Requirements Checklist

Before deploying, ensure your web hosting environment meets these requirements:
* **PHP Version**: PHP 8.0, 8.1, 8.2, or 8.3 (PHP 8.2+ recommended).
* **PHP Extensions**:
  * `pdo`, `pdo_sqlite` (if running SQLite), and `pdo_mysql` (if using MySQL/MariaDB).
  * `gd` or `imagick` (for product image processing and resizing).
  * `mbstring` (for multilingual Gujarati text handling).
  * `curl`, `json`, `openssl`, `session`.
* **Database**:
  * Default Option: SQLite 3 (portable local fallback in `ssk_store.sqlite`).
  * Scalable Option: MySQL 5.7+ or MariaDB 10.3+.
* **Web Server**: Apache 2.4+ (with `mod_rewrite`, `mod_deflate`, `mod_headers`, `mod_expires` enabled) or Nginx.
* **SSL Certificate**: HTTPS is mandatory for secure checkout, cookies, and PWA installation. Free Let's Encrypt SSL is sufficient.

> [!NOTE]
> **Which hosting do you use?**
> This guide provides step-by-step instructions for the two most common hosting setups:
> - **Path A: Shared Web Hosting with cPanel** (e.g. Hostinger, GoDaddy, Bluehost, Namecheap, BigRock).
> - **Path B: Virtual Private Server (VPS / Cloud)** (e.g. DigitalOcean, Linode, AWS EC2, Hetzner, Hostinger VPS).
> Please review Path A or Path B based on your hosting provider.

---

## 2. Deployment Path A: Shared Hosting with cPanel

### Step 1: Prepare the Files
1. On your computer, open the `Sahajanand_Sahitya_Kendra` project folder.
2. Ensure you **exclude** any local test scripts or scratch folders (e.g., `tests/`, `.git/`, `.gemini/`).
3. Select all files and folders and compress them into a single zip file (e.g., `ssk_store_release.zip`).

### Step 2: Upload Files via cPanel File Manager
1. Log in to your cPanel dashboard.
2. Click **File Manager** and navigate to your domain's root folder:
   * Main domain: `public_html/`
   * Subdomain or addon domain: `public_html/store/` or your addon root directory.
3. Click **Upload** in the top toolbar, select `ssk_store_release.zip`, and wait for the upload to reach 100% (green bar).
4. Go back to File Manager, right-click `ssk_store_release.zip`, and click **Extract**.
5. Delete the `.zip` file once extraction is complete.

### Step 3: Set Upload Folder Permissions
1. In File Manager, locate the `uploads/` folder.
2. Right-click `uploads/` and select **Change Permissions**.
3. Set the permission to `755` (or `775` if required by your host). Ensure the `logs/` directory also has write permissions (`755`).

### Step 4: Configure Production Settings
1. Open `includes/config.php` in the cPanel File Editor.
2. Update the following lines with your live details:
   ```php
   // Update to your real live domain (with https:// and no trailing slash)
   define('SITE_URL', 'https://yourmandirstore.org');

   // Central Mandir Payment & Contact Information
   define('STORE_UPI_ID', '9426551098@upi');
   define('STORE_UPI_NAME', 'Shree Sahajanand Sahitya Kendra');
   define('STORE_WHATSAPP_NUMBER', '+91 9426-55-1098');
   ```
3. Save and close the file.

---

## 3. Deployment Path B: VPS / Cloud Server (Ubuntu / Debian / Apache / Nginx)

### Step 1: Upload Files via SFTP or SCP
Upload your application files to `/var/www/sskstore`:
```bash
rsync -avz --exclude='.git' --exclude='node_modules' ./ user@your-vps-ip:/var/www/sskstore/
```

### Step 2: Set Proper Linux Permissions
Grant ownership to the web server user (`www-data`):
```bash
sudo chown -R www-data:www-data /var/www/sskstore
sudo find /var/www/sskstore -type d -exec chmod 755 {} \;
sudo find /var/www/sskstore -type f -exec chmod 644 {} \;
sudo chmod -R 775 /var/www/sskstore/uploads
sudo chmod -R 775 /var/www/sskstore/logs
```

### Step 3: Apache VirtualHost Configuration
Ensure your Apache site config (`/etc/apache2/sites-available/sskstore.conf`) has `AllowOverride All` enabled so `.htaccess` rules take effect:
```apache
<VirtualHost *:80>
    ServerName yourdomain.org
    ServerAlias www.yourdomain.org
    DocumentRoot /var/www/sskstore

    <Directory /var/www/sskstore>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/sskstore_error.log
    CustomLog ${APACHE_LOG_DIR}/sskstore_access.log combined
</VirtualHost>
```
Enable modules and restart Apache:
```bash
sudo a2enmod rewrite headers expires deflate ssl
sudo a2ensite sskstore.conf
sudo systemctl reload apache2
```

---

## 4. Domain, SSL & URL Redirection

### Free SSL Certificate (Let's Encrypt)
* **cPanel**: Go to **SSL/TLS Status** in cPanel, select your domain, and click **Run AutoSSL**. Within a few minutes, a green padlock will be active.
* **VPS**: Run Certbot:
  ```bash
  sudo apt install certbot python3-certbot-apache
  sudo certbot --apache -d yourdomain.org -d www.yourdomain.org
  ```

### Canonical Redirect (Enforce HTTPS & Preferred Domain)
The platform includes built-in `.htaccess` rules that automatically force HTTPS.
To enforce non-www (`https://yourdomain.org`), uncomment the canonical redirect in `.htaccess`:
```apache
RewriteCond %{HTTP_HOST} ^www\.(.*)$ [NC]
RewriteRule ^(.*)$ https://%1/$1 [R=301,L]
```

---

## 5. First-Time Admin Account Setup

1. In your browser, open `https://yourdomain.org/admin/login.php`.
2. Sign in with the temporary setup credentials:
   * **Username**: `admin`
   * **Password**: `admin123`
3. Immediately navigate to **Admin Users & RBAC** (`admin_users.php`).
4. Click **Edit** on Administrator, update the username and set a **strong password** (at least 12 characters with letters, numbers, and symbols).
5. Log out and log back in with your new credentials to verify.

---

## 6. Live Smoke Test Checklist

Once files are live, walk through this checklist on both Desktop and Mobile:
* [ ] Open `https://yourdomain.org/` and verify the homepage loads with sacred banners and products.
* [ ] Switch between **Light** and **Dark** modes — check that contrast remains high and zero flash of unstyled theme occurs.
* [ ] Switch language to **Gujarati** — check that fonts render smoothly without broken conjuncts or clipped matras.
* [ ] Search for a product (e.g. "Vachanamrut") using the top search bar.
* [ ] Open a product page, select quantity, and click **Add to Cart**.
* [ ] Visit Cart (`cart.php`), verify items, test pincode delivery estimate.
* [ ] Proceed to **Checkout** (`checkout.php`):
  * Check that all form boxes have crisp visible borders and clear labels above.
  * Enter contact details, delivery address, and select **UPI / QR Code**.
  * Click **Place Order & Pay**.
* [ ] Verify the **Order Confirmation** receipt displays order reference `#SSK-...` and the WhatsApp payment verification button works.
* [ ] Check that the new order appears immediately inside the **Admin Portal** (`/admin/orders.php`).
* [ ] **PWA Test**: On mobile Chrome or desktop Edge, verify the "Install App" button appears and the app installs successfully onto the home screen.

---

## 7. Search Console & Sitemap Submission

1. Go to [Google Search Console](https://search.google.com/search-console).
2. Add your property `https://yourdomain.org`.
3. Verify ownership using HTML tag or DNS TXT record.
4. Navigate to **Sitemaps** on the left menu.
5. Enter `sitemap.xml` and click **Submit**.
6. Verify that all 47 storefront URLs are recognized and scheduled for indexing.

---

## 8. Rollback Plan

If you ever need to roll back a release:
1. Keep the previous version's zip archive and database backup saved safely (see `BACKUP.md`).
2. If an issue occurs, simply restore the previous directory files via File Manager or SFTP.
3. Restore `ssk_store.sqlite` from the pre-deployment backup copy.
4. Clear your browser cache and Service Worker to verify restoration.
