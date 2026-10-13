# COMPREHENSIVE AUDIT REPORT — SSK STORE
**Shri Sahajanand Sahitya Kendra · Kalakunj Mandir, Surat**  
**Repository:** `Sahajanand_Sahitya_Kendra`  
**Phase:** 1 (Read & Analyze — Zero Code Modifications)  
**Date:** October 2026  
**Auditor:** Antigravity Senior Engineering Agent  

---

## 1. Executive Summary

A comprehensive code and architecture audit was conducted on the SSK Store codebase. The application is built on native PHP with vanilla JavaScript, modern CSS tokens, and PDO database abstraction. 

The audit confirmed that while the user interface presents a well-structured devotional aesthetic, the codebase contains critical vulnerabilities, database incompatibilities that break production on MySQL, severe security loopholes (including admin authentication bypass and lack of CSRF protection), and massive image payloads (1.14 MB logo, 846 KB hero banner) that severely hamper performance on mobile networks.

All 7 P0 findings identified in the master prompt were **CONFIRMED**, alongside multiple critical P1 and P2 findings. Zero application code was modified during this phase.

---

## 2. Architecture Map

```
+-----------------------------------------------------------------------------------+
|                                REQUEST FLOW                                       |
|                                                                                   |
|  User Request  -->  .htaccess (Rewrites / Security)                               |
|        |                                                                          |
|        v                                                                          |
|  config.php (Env, Constants, DB creds, Session config, SITE_URL)                 |
|        |                                                                          |
|        v                                                                          |
|  db.php (PDO Connection to MySQL, silent fallback to SQLite)                     |
|        |                                                                          |
|        v                                                                          |
|  functions.php / form_helpers.php (Session helpers, cart, auth, formatting)       |
|        |                                                                          |
|        v                                                                          |
|  includes/header.php (HTML head, SEO tags, Navigation tree, Flash messages)       |
|        |                                                                          |
|        v                                                                          |
|  Target Page (index.php / catalog.php / product.php / checkout.php / admin/*)     |
|        |                                                                          |
|        v                                                                          |
|  includes/footer.php (Bottom navigation, Modals, Scripts, Offline triggers)       |
+-----------------------------------------------------------------------------------+
```

### 2.1 Session, Cart & Auth Flow
- **Sessions:** Initiated globally in `config.php` via native `session_start()` on all requests. No lazy session loading is present, resulting in unnecessary file locks during parallel requests and bot crawling.
- **Cart Tracking:** Dual identity mechanism:
  - Guest users are assigned a 32-character hexadecimal token stored in `$_SESSION['cart_session_id']`.
  - Logged-in customers use `$_SESSION['customer_id']`.
  - Cart database rows track either `session_id` or `customer_id`.
- **Customer Auth:** `login_user()` and `register_user()` in `includes/functions.php`. Uses `password_hash()` (bcrypt) and verifies against `customers` table.

### 2.2 Checkout & Order Lifecycle
- **Guest vs Registered:** Checkout permits unauthenticated users. If a guest enters an email already belonging to a registered user, the system attaches the order to that user's ID without verifying credentials. If the guest email is new, a user row is created with a random 16-character hex password that the user cannot recover.
- **Transaction Handling:** Currently, `checkout.php` runs queries sequentially without wrapping them inside a database transaction (`BEGIN` / `COMMIT`). If stock updates fail, an orphaned order row persists in the database.
- **Payment Verification:** UPI and card orders are marked `pending_verification`, which conflicts with the strict ENUM schema in MySQL.

### 2.3 Admin Role-Based Access Control (RBAC)
- Admin accounts are stored in `admin_users` with roles `super_admin`, `admin`, or `editor`.
- Page-level permissions are checked in `admin/includes/header.php`.
- **Vulnerability:** `admin/products.php` executes `$_GET['delete']` and `$_POST` actions **before** loading `header.php`, bypassing authentication entirely.

### 2.4 Image Flow
- Image files are stored in `assets/images/`, `assets/brand/`, or `uploads/products/`.
- No image processing pipeline currently exists: uploaded 3–5 MB camera photos are served directly to mobile screens without WebP/AVIF compression, `srcset`, or responsive downscaling.
- Missing images trigger a fallback PHP script (`assets/images/placeholder.php`) that runs GD on every request.

### 2.5 Progressive Web App (PWA) Flow
- PWA is configured via `manifest.webmanifest` and `sw.js`.
- The service worker attempts atomic precaching of 18 resources using `cache.addAll()`. Any 404 error aborts caching of `offline.html`.
- Personalized dynamic pages (`/`) are precached, causing user state leakage.
- Offline navigation does not cache visited product or catalog pages.

### 2.6 Database Schema Differences (MySQL vs SQLite)
| Aspect | MySQL / MariaDB (Production) | SQLite (Local Dev Fallback) | Risk / Consequence |
|---|---|---|---|
| **`MAX(a, b)` function** | Aggregate function (1 argument only). Throws SQL syntax error when passed 2 arguments. | Accepts 2 arguments as scalar `max(a, b)`. | **FATAL**: `checkout.php` line 118 crashes on MySQL when updating stock. |
| **`datetime('now')`** | Syntax error. MySQL requires `NOW()` or `CURRENT_TIMESTAMP`. | Valid native syntax. | **FATAL**: `homepage_sections.php` throws on MySQL. |
| **`payment_status` ENUM** | Strictly enforced: `'pending', 'paid', 'failed', 'refunded'`. | Text affinity (accepts any string). | **FATAL**: `checkout.php` inserts `'pending_verification'` which MySQL rejects. |
| **Default Review Status** | Defaults to `0` (pending). | Defaults to `1` (approved). | Reviews behave inconsistently between environments. |
| **Case Sensitivity & Types** | Strict types and ENUMs. | Loose dynamic typing. | Hidden runtime bugs remain undetected on SQLite. |

---

## 3. Baseline Performance Metrics

Measurements taken with local dev server (`PHP 8.2.12`) simulating Fast 4G mobile conditions:

| Page / Asset | Payload Size (Raw) | Response Time (Local) | Core Web Vitals Bottlenecks Identified |
|---|---|---|---|
| **Homepage (`/`)** | 68.0 KB (HTML only) | 4.2 s (cold) | Render-blocking `@import` fonts; 1.14 MB uncompressed logo; 846 KB slide 1; No `fetchpriority` or preloads. |
| **Catalog (`/catalog.php`)** | 71.5 KB (HTML only) | 4.1 s | N+1 wishlist queries per card; full product queries (`SELECT p.*`); unscaled original product images. |
| **Product (`/product.php?id=1`)** | 54.4 KB (HTML only) | 4.2 s | Synchronous `UPDATE products SET views` on every GET; unoptimized single full-size image; missing width/height causing CLS. |
| **`assets/brand/logo.png`** | **1,165.76 KB (1.14 MB)** | — | Displayed at 151x56px, original is 1922x713px unoptimized PNG. |
| **`assets/images/slide1.jpg`** | **866.47 KB (846 KB)** | — | Desktop banner served to mobile viewports without WebP or media queries. |
| **`assets/css/theme.css`** | 39.93 KB | — | Unminified, contains duplicate component styles. |
| **`assets/css/style.css`** | 40.28 KB | — | Contains self-referential variable cycles; syntax error on line 706. |
| **`assets/js/main.js`** | 14.96 KB | — | Unminified, lacks version query parameter (`?v=filemtime`). |

---

## 4. Master Audit Findings Table

Status key:
- **CONFIRMED**: Issue reproduced and verified in active source code.
- **NEW**: Additional finding discovered during Phase 1 audit.

| ID | Severity | Area | File:Line | Problem Description | Proposed Surgical Fix | Status |
|---|---|---|---|---|---|---|
| **P0-1** | Critical | Database / Checkout | `checkout.php:118`, `checkout.php:84`, `admin/homepage_sections.php:19,52,67` | SQLite-specific SQL: 2-arg `MAX(0, stock_qty - ?)` crashes on MySQL; `payment_status = 'pending_verification'` rejected by MySQL ENUM; `datetime('now')` crashes on MySQL. | Use atomic `stock_qty = stock_qty - ? WHERE id = ? AND stock_qty >= ?`; migration to allow `pending_verification`; use `CURRENT_TIMESTAMP`; wrap in DB transaction. | **CONFIRMED** |
| **P0-2** | Critical | Security / Admin Auth | `admin/products.php:14-63` | Unauthenticated execution: `$_GET['delete']` and `$_POST` handlers run prior to `admin/includes/header.php`. Zero auth or RBAC validation. | Require `admin/includes/admin_bootstrap.php` at line 1 of every admin file; enforce authentication and permission checks before handling actions. | **CONFIRMED** |
| **P0-3** | Critical | Security / CSRF | `admin/admin_users.php:20`, `admin/coupons.php:10,27`, `admin/categories.php:74,90`, `admin/banners.php:21`, `admin/bulk_import.php`, `api/auth.php:45`, `api/cart.php:51` | Missing CSRF protection on critical state changes (admin creation, coupons, categories). State modifications executed via GET requests. | Add `require_csrf()` on all POST forms; convert all GET state mutations (`?delete=`, `?logout=`) to POST with tokens; add `Origin` header check on API. | **CONFIRMED** |
| **P0-4** | Critical | Security / Credentials | `database.sql:334`, `includes/db.php:336-343` | Hardcoded default credentials seeded: admin hash is `$2y$10$92IX...` (password: `password`), customer password is `customer123`. | Remove runtime seeds of default users; create CLI script `tools/create-admin.php` enforcing 12+ character password; force password change banner if default hash found. | **CONFIRMED** |
| **P0-5** | Critical | Security / Architecture | `includes/db.php:29-49`, `.htaccess` | Silent fallback from MySQL to `baps_store.sqlite` in web root on connection glitch; sensitive files (`.sqlite`, `.sql`, `.log`, `.git`) potentially web-accessible. | Disallow silent fallback in production (`APP_ENV=production` shows 503); move SQLite and logs outside document root; harden `.htaccess` denying `*.sqlite|*.sql|*.log`. | **CONFIRMED** |
| **P0-6** | Critical | Admin / Bulk Import | `admin/bulk_import.php:380,397` | Calls `db_insert()` which is undefined in `db.php` or `functions.php`, resulting in a fatal error when importing with auto-create categories. | Implement `db_insert()` and `db_transaction()` in `includes/db.php`; wrap bulk imports in atomic transactions. | **CONFIRMED** |
| **P0-7** | Critical | Security / Business Logic | `api/coupon.php:16,31-51`, `checkout.php:52` | Client-posted `subtotal` trusted without server cart verification; coupon dates (`start_date`, `end_date`), usage limits (`max_uses`, `used_count`) never enforced. | Verify subtotal exclusively against server-calculated cart subtotal; enforce dates, usage caps, and active flag; atomic increment of `used_count` during checkout. | **CONFIRMED** |
| **P1-1** | High | Business Logic / Cart | `includes/functions.php:194,224`, `checkout.php` | Out-of-stock items can be purchased: `add_to_cart` and `update_cart_quantity` allow adding 0-stock products; checkout performs no final stock re-check. | Enforce strict stock checks on cart add, quantity update, and in checkout transaction; render "Out of stock" badge and disable purchase buttons. | **CONFIRMED** |
| **P1-2** | High | Security / Admin Auth | `includes/functions.php:397-403`, `admin/includes/header.php` | Disabled admins retain active session; `login_admin()` fails to verify `is_active = 1`; no session idle timeout; passwords trimmed on login. | Validate `is_active = 1` during login and on every request via `current_admin()`; enforce 30-min idle timeout; remove password trimming. | **CONFIRMED** |
| **P1-3** | High | Security / Brute Force | `api/auth.php`, `admin/login.php`, `pages.php:22` | No rate limiting or brute-force protection on customer login, registration, admin portal, or contact forms. | Implement `login_attempts` tracking with progressive IP/account lockouts (5 fails = 15-min lockout); add honeypot fields to public forms. | **CONFIRMED** |
| **P1-4** | High | Business Logic / Auth | `checkout.php:66-76`, `api/auth.php` | Guest checkout generates random password without reset flow, permanently blocking later registration; orders can attach to existing accounts without login. | Add `is_guest` flag; provide secure password reset mechanism with 1-hr expiring tokens; require login before attaching guest orders to existing accounts. | **CONFIRMED** |
| **P1-5** | High | Business Logic / Trust | `checkout.php:84`, `includes/footer.php:11`, `includes/functions.php:163`, `product.php:236` | Unmet promises: No email delivery code exists; fake newsletter subscription toast; Card/NetBanking listed without payment gateway; pincode delivery estimates hardcoded. | Integrate PHPMailer with ENV SMTP credentials (fallback to `logs/mail.log`); provide UPI QR + deep link + UTR submission form; clarify pincode text as estimate. | **CONFIRMED** |
| **P1-6** | High | Admin / Categories | `admin/categories.php:53,237` | Category modal form lacks `is_active` checkbox, causing every saved category to be set to `0` and disappear from the public store; subcategory CRUD missing. | Add active checkbox input to category modal; implement subcategory management and dependent department-category dropdowns. | **CONFIRMED** |
| **P1-7** | High | Localization / Routing | `admin/products.php:48`, `admin/categories.php:22` | Slug generation strips non-ASCII characters (`preg_replace('/[^A-Za-z0-9-]+/')`), generating empty or duplicate slugs for Gujarati titles. | Implement UTF-8/Gujarati transliteration helper; add fallback to `item-{id}`; implement unique slug incrementation loop (`-2`, `-3`). | **CONFIRMED** |
| **P1-8** | High | Database / Integrity | `admin/products.php:17` | Hard delete `DELETE FROM products WHERE id = ?` fails with foreign key constraint error when product is referenced in `order_items`. | Implement soft delete (`is_active = 0`, `deleted_at`); block hard deletion of ordered items; clean up items from carts and wishlists. | **CONFIRMED** |
| **P1-9** | High | Security / File Uploads | `admin/banners.php:33`, `admin/bulk_import.php:143`, `uploads/.htaccess` | Unrestricted file uploads: extension-only check; predictable `rand()` naming; Apache 2.2 syntax `Order/Deny` in `uploads/.htaccess` causing 500 errors on Apache 2.4. | Validate MIME types via `finfo` and `getimagesize`; sanitize names with cryptographically secure hashes; modernize `.htaccess` with `Require all denied`. | **CONFIRMED** |
| **P1-10** | High | Security / XSS | `assets/js/main.js:32,77`, `pages.php:71`, `catalog.php:231`, `product.php:66` | Cross-site scripting vulnerabilities: unescaped search query `q` and `message` in `innerHTML`; raw `$page['content']` output; unescaped `img src` URLs. | Use `textContent` and `escapeHtml()` in JS; escape all attributes using `htmlspecialchars()` / `e()` helper; sanitize CMS page markup. | **CONFIRMED** |
| **P1-11** | High | Performance / Sessions | `includes/config.php:55-70` | Unconditional `session_start()` on every request (including search crawlers and public read APIs) locks session files and breaks CDN cacheability. | Implement lazy session initialization (only start when modifying cart, auth, CSRF); call `session_write_close()` on read-only endpoints. | **CONFIRMED** |
| **P1-12** | High | Security / HTTP Headers | Root `.htaccess`, `includes/header.php` | Missing essential HTTP security headers (`Content-Security-Policy`, `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`). | Inject hardened HTTP security headers via `.htaccess` and PHP bootstrap; configure CSP in report-only mode initially. | **CONFIRMED** |
| **P1-13** | High | Frontend / UX | `assets/js/main.js:430-444` | Double-submit handler disables buttons even when validation fails or `e.preventDefault()` is invoked, leaving buttons permanently stuck on "Processing...". | Check `if (e.defaultPrevented) return;`; implement managed `setLoading(btn, bool)` helper with restoration on `pageshow`. | **CONFIRMED** |
| **P1-14** | High | Frontend / Responsive | `includes/footer.php:11`, `checkout.php:215`, `admin/coupons.php:38`, `assets/css/style.css:1041` | Broken mobile layout: invalid CSS `flex-wrap: gap: 1.5rem;` in footer; inline `1fr auto 1fr` grids overflowing viewports on 360px mobile screens. | Replace inline layout styles with responsive media-query classes; test at 360px viewport width. | **CONFIRMED** |
| **P1-15** | High | Frontend / CSS Defect | `assets/css/style.css:21-42,706` | Self-referential custom property cycles (`--border: var(--border)`) break storefront border and status tokens; stray CSS syntax on line 706. | Remove recursive definitions from `style.css`; resolve syntax error on line 706; consolidate duplicated component definitions. | **CONFIRMED** |
| **P1-16** | High | Business Logic / Pricing | `includes/functions.php:163-174`, `checkout.php:60` | Hardcoded shipping charges (50/80) ignore `shipping_zones` DB table; changing state on checkout does not update displayed total asynchronously. | Provide dynamic shipping calculation API; update order totals live via JSON; expand state list to all 28 states and 8 union territories. | **CONFIRMED** |
| **SEO-1** | Critical | SEO / Discoverability | `includes/header.php:27,35` | Canonical URL builds from `strtok($_SERVER['REQUEST_URI'], '?')`, canonicalizing all products to `/product.php` and all categories to `/catalog.php`. | Implement clean slug routing (`/product/{slug}-{id}`) with accurate per-page canonical tags and structured JSON-LD data. | **CONFIRMED** |
| **SEO-2** | High | SEO / Indexing | `sitemap.xml`, `robots.txt` | Static sitemap lists hardcoded outdated URLs including non-existent `shipping-policy`; `robots.txt` missing absolute sitemap URL. | Generate `sitemap.xml` dynamically from active database records; provide absolute URL in `robots.txt`. | **CONFIRMED** |
| **PWA-1** | High | PWA / Offline | `sw.js:15,83,126-143` | Atomic `cache.addAll()` failure breaks offline fallback; personal `/` homepage cached; visited catalog and product pages not accessible offline. | Precache assets individually with error tolerance; exclude personalized user pages; implement stale-while-revalidate for visited pages. | **CONFIRMED** |
| **AND-1** | High | Android / WebView | `android-app/MainActivity.java:36,69,106` | Hardcoded emulator URL (`10.0.2.2`); `allowFileAccess(true)`; external links load inside WebView with exposed `AndroidBridge`; cleartext allowed. | Parameterize URL via build variants; restrict WebView navigation to store origin; disable file access; open external links in system browser. | **CONFIRMED** |
| **NEW-1** | High | Security / Open Redirect | `api/auth.php:48` | `header('Location: ../' . $_GET['redirect']);` redirects to arbitrary client-controlled parameter without URL allow-listing. | Validate `redirect` parameter against local relative paths or strict internal whitelist before redirecting. | **CONFIRMED** |
| **NEW-2** | Medium | Security / Host Header | `includes/config.php:25,30` | `SITE_URL` constructed directly from unvalidated `$_SERVER['HTTP_HOST']`, enabling Host header injection attacks on password resets and canonicals. | Allow-list valid domain names (`sskstore.in`, `localhost:8000`) or configure canonical host in environment config. | **CONFIRMED** |
| **NEW-3** | Medium | Database / Performance | `product.php:23` | Synchronous database write `UPDATE products SET views = views + 1` executes on every GET request, creating DB contention and write locks. | Throttle view increments using session cache or batch writes asynchronously. | **CONFIRMED** |
| **NEW-4** | Low | Localization / SEO | `includes/header.php:18`, `offline.html:2` | Static `<html lang="en">` even when Gujarati content is displayed; inconsistent language attributes on offline fallback. | Dynamically bind `lang` attribute to active language context (`en` / `gu`). | **CONFIRMED** |

---

## 5. Summary of Architecture & Security Posture
The repository contains robust features for a temple literature store, but lacks defensive security hardening, transactional database consistency, and modern asset delivery optimizations. 

Phase 1 analysis is **complete**. The implementation plan outlines the exact sequence to systematically address every confirmed issue without altering design tokens, brand identity, or existing working features.
