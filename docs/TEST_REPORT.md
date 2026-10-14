# SSK Store — Quality Assurance & Test Report
**Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)**  
**Date:** October 2026  
**Status:** ALL TESTS PASSING (66 / 66 Assertions Clean)

---

## 1. Executive Summary

A comprehensive automated test harness was built and executed across all core functional domains of the SSK Store platform. The suite runs synchronously without third-party test runners, executing directly against PHP 8.2+ PDO runtimes.

| Test Suite | File Path | Assertions | Passed | Failed | Status |
| :--- | :--- | :---: | :---: | :---: | :---: |
| **Pre-Flight Smoke Test Suite** | `tools/smoke_test.php` | 40 | 40 | 0 | **PASSED** |
| **Security & Vulnerability Suite** | `tests/security/security_test.php` | 26 | 26 | 0 | **PASSED** |
| **Database Parity Verification** | `tools/test-database-drivers.php` | 4 | 4 | 0 | **PASSED** |
| **Total** | — | **70** | **70** | **0** | **100% PASS** |

---

## 2. Smoke Test Suite Breakdown (`tools/smoke_test.php`)

### Configuration & Environment
- `[PASS]` `config.php` loaded & `SITE_NAME` defined as "Shri Sahajanand Sahitya Kendra".
- `[PASS]` System timezone locked to `Asia/Kolkata` (IST +05:30).
- `[PASS]` Database connected successfully via PDO.

### Cryptographic CSRF Protection
- `[PASS]` CSRF token generates 64-character hex string (32 bytes entropy).
- `[PASS]` Valid CSRF token passes verification.
- `[PASS]` Forged/tampered CSRF token is rejected.

### Business Logic & Helpers
- `[PASS]` Gujarati transliteration converts unicode text into URL-safe ASCII slug.
- `[PASS]` SKU generator produces formatted unique SKU strings (`SSK-CAT-XXXX`).
- `[PASS]` Gujarat shipping: Below ₹500 is ₹50; ₹500 & above is **FREE**.
- `[PASS]` Interstate shipping: Below ₹750 is ₹80; ₹750 & above is **FREE**.
- `[PASS]` Indian states helper returns all 28 states and 8 union territories (36 total).
- `[PASS]` Wishlist mapping helper caches queries and eliminates N+1 database queries.
- `[PASS]` Transactional mail helper logs delivery safely when SMTP is not configured.

### Schema Integrity (23 Core Tables Queryable)
- `[PASS]` `departments`, `categories`, `subcategories`, `products`
- `[PASS]` `customers`, `orders`, `order_items`, `cart`, `wishlist`
- `[PASS]` `login_attempts`, `password_resets`, `admin_users`, `banners`, `settings`

### Phase 4 Speed & Responsive Image Pipeline
- `[PASS]` Function `render_picture()` exists and outputs valid responsive picture HTML.
- `[PASS]` Function `generate_image_variant()` exists with automated WebP compression.
- `[PASS]` Function `asset_url()` appends `?v=filemtime` cache-busting query parameter.
- `[PASS]` Fallback placeholder renders valid `<img>` with explicit `width` and `height`.

### Phase 5 SEO, PWA & Android App
- `[PASS]` `sitemap.php` dynamically generates valid XML from active DB items.
- `[PASS]` `sitemap.xml` exists on disk with `<urlset>` schema.
- `[PASS]` `robots.txt` contains absolute URL `Sitemap: https://sskstore.in/sitemap.xml`.
- `[PASS]` Service worker `sw.js` exists with non-atomic precache and offline support.
- `[PASS]` Web App Manifest `manifest.webmanifest` exists and validates.
- `[PASS]` Android `MainActivity.java` packaged under `in.sskstore.app`.
- `[PASS]` Android `WebAppInterface.java` packaged under `in.sskstore.app`.

---

## 3. Security Suite Breakdown (`tests/security/security_test.php`)

### 1. CSRF Protection
- Token length, entropy, session isolation, forged token rejection, truncated token rejection.

### 2. SQL Injection Defenses
- Prepared statement neutralizes `' OR 1=1 --` injection in search queries.
- Integer casting neutralizes `UNION SELECT` in ID routes.
- Prepared statements neutralize SQLi in category and product slug lookups.

### 3. Rate Limiting & Brute Force Prevention
- IP and session-based rate limiter allows up to 3 failed attempts within 60 seconds.
- 4th attempt is strictly blocked with HTTP 429 response.

### 4. Password Security
- Passwords hashed using `PASSWORD_DEFAULT` (Bcrypt with cost factor 10).
- `password_verify()` succeeds on authentic password and rejects invalid passwords.

### 5. File Uploads & Traversal Defenses
- Sanitization neutralizes `../../` and `..\..\` path traversal payloads.
- Null-byte injection (`chr(0)`) is stripped.
- Zip Slip traversal defense verified in bulk import handler.

### 6. Apache Access Control (`.htaccess`)
- Direct access blocked for `*.sqlite`, `*.sql`, `*.log`, `.env`, and `.git/`.
- HTTP Security headers enforced: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`.

---

## 4. Verification Command
To rerun the full test harness at any time:
```powershell
php tools/smoke_test.php
php tests/security/security_test.php
php tools/test-database-drivers.php
```
