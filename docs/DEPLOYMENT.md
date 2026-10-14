# SSK Store — Production Deployment Guide
**Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)**  
**Version:** 1.0.0-production  
**Date:** October 2026

---

## 1. System Requirements

| Component | Minimum Specification | Recommended Production |
| :--- | :--- | :--- |
| **Operating System** | Linux (Ubuntu 22.04 / 24.04 LTS, Debian 12, RHEL 9, AlmaLinux) | Ubuntu 24.04 LTS |
| **PHP Runtime** | PHP 8.1+ | **PHP 8.2 or 8.3** |
| **Required PHP Extensions** | `pdo`, `pdo_mysql`, `pdo_sqlite`, `gd` (with WebP/AVIF), `intl`, `mbstring`, `curl`, `json`, `openssl`, `session` | All verified enabled |
| **Database Engine** | MySQL 8.0+ or MariaDB 10.6+ | MySQL 8.0 with InnoDB utf8mb4 |
| **Web Server** | Apache 2.4+ with `mod_rewrite`, `mod_deflate`, `mod_headers`, `mod_expires` | Apache 2.4 or Nginx + PHP-FPM |
| **SSL / TLS** | TLS 1.2+ Certificate (Let's Encrypt / Cloudflare SSL) | Mandatory for checkout & PWA |

---

## 2. Directory Structure & Permissions

Ensure proper ownership and permissions are applied after uploading files to `/var/www/sskstore`:

```bash
# Assign web server user ownership (e.g. www-data on Ubuntu)
sudo chown -R www-data:www-data /var/www/sskstore

# Directory permissions: 755
sudo find /var/www/sskstore -type d -exec chmod 755 {} \;

# File permissions: 644
sudo find /var/www/sskstore -type f -exec chmod 644 {} \;

# Writable directories for uploads, cached images, and logs: 775
sudo chmod -R 775 /var/www/sskstore/uploads
sudo chmod -R 775 /var/www/sskstore/logs
```

---

## 3. Environment & Database Configuration

### Step 3.1: Environment File Setup
Create or edit `.env` in the project root (never committed to git):

```ini
APP_ENV=production
SITE_URL=https://sskstore.in

DB_HOST=localhost
DB_NAME=ssk_store
DB_USER=ssk_dbuser
DB_PASS=YourSecureDatabasePassword123!
DB_CHARSET=utf8mb4

# Mail / SMTP Settings (Optional)
SMTP_HOST=smtp.sendgrid.net
SMTP_PORT=587
SMTP_USER=apikey
SMTP_PASS=your-smtp-api-key
SMTP_FROM=orders@sskstore.in
```

### Step 3.2: Database Initialization (MySQL)
Import the schema and initial seed data:
```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS ssk_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p ssk_store < database.sql
```

### Step 3.3: Apply Performance Indexes & Fixes
Execute database migrations:
```bash
php tools/migrate.php
```

---

## 4. Provision Initial Administrator Account

In compliance with Security Policy P0-4, default administrative passwords have been removed from database seed files. Provision your administrator account securely via CLI:

```bash
php tools/create-admin.php
```
Follow the interactive prompt to define:
- **Username:** (e.g., `mandir_admin`)
- **Email:** (e.g., `admin@sskstore.in`)
- **Role:** `super_admin`
- **Password:** Strong password (min 8 chars with mixed case and symbols)

---

## 5. Web Server Configuration

### Apache 2.4 VirtualHost Example
The included `.htaccess` file provides complete security rules, rewrite rules, gzip compression, and caching headers.

```apache
<VirtualHost *:80>
    ServerName sskstore.in
    ServerAlias www.sskstore.in
    Redirect permanent / https://sskstore.in/
</VirtualHost>

<VirtualHost *:443>
    ServerName sskstore.in
    ServerAlias www.sskstore.in
    DocumentRoot /var/www/sskstore

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/sskstore.in/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/sskstore.in/privkey.pem

    <Directory /var/www/sskstore>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/sskstore_error.log
    CustomLog ${APACHE_LOG_DIR}/sskstore_access.log combined
</VirtualHost>
```

### Nginx + PHP-FPM Configuration Example
If running on Nginx, include the following directive block:

```nginx
server {
    listen 80;
    server_name sskstore.in www.sskstore.in;
    return 301 https://sskstore.in$request_uri;
}

server {
    listen 443 ssl http2;
    server_name sskstore.in;
    root /var/www/sskstore;
    index index.php index.html;

    ssl_certificate /etc/letsencrypt/live/sskstore.in/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/sskstore.in/privkey.pem;

    # Security Headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Block sensitive files and directories
    location ~* \.(env|sql|sqlite|sqlite3|db|bak|log|md|sh|lock|json)$ {
        deny all;
    }
    location ~ ^/(includes|database|logs|tools|vendor|\.git)/ {
        deny all;
    }

    # Clean SEO Slugs
    location /sitemap.xml {
        try_files /sitemap.php =404;
    }
    location ~ ^/product/([a-zA-Z0-9_-]+)/?$ {
        rewrite ^/product/([a-zA-Z0-9_-]+)/?$ /product.php?slug=$1 last;
    }
    location ~ ^/page/([a-zA-Z0-9_-]+)/?$ {
        rewrite ^/page/([a-zA-Z0-9_-]+)/?$ /pages.php?slug=$1 last;
    }

    # Static assets cache
    location ~* \.(jpg|jpeg|png|webp|avif|gif|ico|svg|css|js|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, no-transform";
    }

    # Service Worker must never be cached
    location = /sw.js {
        expires off;
        add_header Cache-Control "no-cache, no-store, must-revalidate";
    }

    # PHP Handler
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

## 6. Post-Deployment Verification

Run the automated smoke test and security suite on the live host:
```bash
php tools/smoke_test.php
php tests/security/security_test.php
```

Ensure output displays `Passed: 40 | Failed: 0` and `Security Results: Passed: 26 | Failed: 0`.

---

## 7. Backup & Rollback Plan

### Regular Backups (Automated Daily Cron)
```bash
# Database Dump
mysqldump -u ssk_dbuser -p ssk_store | gzip > /backups/ssk_store_$(date +%F).sql.gz

# Uploads & Media Backup
tar -czf /backups/uploads_$(date +%F).tar.gz /var/www/sskstore/uploads
```

### Rollback Process
1. Restore the previous code release directory.
2. In case of database rollback, restore the database backup:
   `gunzip < /backups/ssk_store_PREV.sql.gz | mysql -u ssk_dbuser -p ssk_store`
3. Purge browser and CDN caches.
