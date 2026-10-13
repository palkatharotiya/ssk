# MASTER IMPLEMENTATION PLAN — SSK STORE
**Branch:** `audit/hardening-and-speed`  
**Reference Document:** `MASTER_PROMPT_ANTIGRAVITY.md`  
**Review Status:** PENDING USER APPROVAL (Hard Rule 12)  

---

## 1. Overview & Strategy

This Implementation Plan defines the phased roadmap to resolve all findings from `docs/AUDIT_REPORT.md` safely and systematically. 

### Core Engineering Principles
1. **Zero Regressions:** Maintain existing visual presentation, brand color tokens (`#011760` navy, `#E70207` red), English + Gujarati bilingual support, and all working storefront/admin features.
2. **Minimal, Targeted Diffs:** No unnecessary refactoring.
3. **Database Portability:** All SQL queries and migrations must execute flawlessly on both MySQL 8.x / MariaDB 11.x and SQLite 3.
4. **Defensive Security:** Every state change requires POST + CSRF + Auth + RBAC. All input validated, all output escaped.
5. **Measurable Performance:** Target mobile Lighthouse ≥ 90, LCP ≤ 2.0s, total home payload ≤ 1 MB.

---

## 2. Phased Execution Roadmap

### Phase 2: P0 Fixes (Site-Breaking Bugs & Critical Security)

#### Step 2.1 — Core Security & Infrastructure Foundations
- Create `includes/security.php`:
  - `require_csrf()` and `csrf_field()` token verification with timing-attack resistant comparisons (`hash_equals`).
  - Origin / `Sec-Fetch-Site` validation for JSON API endpoints.
  - Safe HTML escaping helper `e()` with UTF-8 support and null-coalescing.
  - Request rate-limiting helper for sensitive endpoints.
  - Security headers injector (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`).
- Create `admin/includes/admin_bootstrap.php`:
  - Strict authentication check (`is_admin_logged_in()`).
  - Active account check (`is_active = 1`).
  - Page-level RBAC enforcement (`admin_has_permission()`).
  - Session idle timeout (30 minutes).
  - Include this as line 1 of **every** admin file before any POST/GET parameter processing.

#### Step 2.2 — Database Layer Hardening & Migration Engine
- Update `includes/db.php`:
  - Implement missing `db_insert(string $sql, array $params): int`.
  - Implement `db_transaction(callable $callback)` supporting nested savepoints.
  - Fix MySQL timezone synchronization (`SET time_zone = '+05:30'`).
  - Restrict SQLite fallback strictly to non-production environments when `DB_DRIVER=sqlite` is explicitly specified. In production, fail gracefully with HTTP 503 and log to a secure file.
- Create migration runner `tools/migrate.php` and directory `database/migrations/`:
  - Migration `001_initial_schema_fixes.sql`:
    - Add `pending_verification` to orders `payment_status` ENUM for MySQL.
    - Add `is_guest` column to `customers` table.
    - Create `login_attempts` table.
    - Create `password_resets` table.

#### Step 2.3 — Checkout Transaction & Stock Decoupling
- Refactor `checkout.php`:
  - Wrap order insertion, order items, stock decrement, coupon usage, and cart clearance in a single atomic `db_transaction()`.
  - Replace SQLite-only `MAX(0, stock_qty - ?)` with portable atomic query:
    `UPDATE products SET stock_qty = stock_qty - ? WHERE id = ? AND stock_qty >= ?`
    Check affected rows to prevent overselling.
  - Enforce server-side cart subtotal calculation for coupons; disallow client-forged discounts.
  - Provide customer-friendly error page with rollback if order placement encounters a race condition.

#### Step 2.4 — Admin Authentication, RBAC & CSRF Protection
- Resolve admin auth bypass in `admin/products.php`: Move delete and edit logic behind `admin_bootstrap.php`.
- Convert all GET state mutations across admin to POST with CSRF tokens:
  - `admin/products.php?delete=` -> POST `action=delete_product` + CSRF.
  - `admin/coupons.php?delete=` -> POST `action=delete_coupon` + CSRF.
  - `admin/categories.php?delete_dept=` & `?delete_cat=` -> POST + CSRF.
  - `admin/logout.php` -> POST + CSRF.
  - `api/auth.php?action=logout` -> POST.
  - `api/cart.php?action=clear` -> POST.
- Add CSRF verification to forms in `admin/admin_users.php`, `admin/banners.php`, `admin/coupons.php`, `admin/homepage_sections.php`, and `admin/bulk_import.php`.
- Replace SQLite-only `datetime('now')` in `admin/homepage_sections.php` with portable `CURRENT_TIMESTAMP`.

#### Step 2.5 — Default Credentials & Sensitive File Protection
- Remove default user seeds from `database.sql` and `includes/db.php`.
- Create CLI utility `tools/create-admin.php` prompting for username, email, full name, and min 12-char password.
- Add admin dashboard alert if an active account is using the legacy default bcrypt hash.
- Update root `.htaccess` to block direct HTTP access to `.sqlite`, `.sql`, `.log`, `.git`, `.env`, and sensitive directories (`includes/`, `logs/`, `database/`).

---

### Phase 3: P1 + P2 Fixes (High/Medium Vulnerabilities & Logic Gaps)

#### Step 3.1 — Business Logic & Cart Hardening
- Enforce inventory constraints on `add_to_cart` and `update_cart_quantity`: clamp quantities to `min(stock_qty, 20)` and disallow adding products when `stock_qty <= 0`.
- Display "Out of Stock" badges and disable cart buttons on catalog and product pages when inventory is zero.
- Fix guest checkout account collisions: set `is_guest = 1`, allow guests to claim accounts upon future verified registration.
- Build transactional email service using PHPMailer (falling back to `logs/mail.log` if SMTP credentials are not configured).
- Add live shipping fee updates when the customer changes states in checkout. Include all 28 states and 8 union territories.

#### Step 3.2 — Admin UX & Data Integrity
- Fix `admin/categories.php`: add `is_active` checkbox to category modal; build subcategory management UI; create dependent department/category/subcategory dropdowns.
- Fix Gujarati slug transliteration in `admin/products.php`, `categories.php`, and `bulk_import.php`: transliterate Gujarati script to clean ASCII slugs with fallback and `-2`, `-3` collision handling.
- Replace product hard delete with soft delete (`is_active = 0`, `deleted_at`) to preserve order history.
- Harden file uploads in `admin/banners.php` and `bulk_import.php`: validate MIME types via `finfo` and `getimagesize`, generate cryptographically random filenames, re-encode via GD to strip EXIF/GPS, update `uploads/.htaccess` with Apache 2.4 `Require all denied` for execution scripts.

#### Step 3.3 — Frontend Security, Mobile Layouts & CSS Fixes
- Fix XSS vulnerabilities in `assets/js/main.js`: use `textContent` and `escapeHtml()` for search suggestions and notifications; escape image URLs and attributes in all PHP templates.
- Fix double-submit guard in `main.js`: check `if (e.defaultPrevented) return;` and implement unified `setLoading(btn, bool)` with `pageshow` restoration.
- Resolve CSS defects in `assets/css/style.css`: eliminate self-referential variable cycles (`--border: var(--border)`), remove syntax error on line 706, eliminate duplicate definitions with `theme.css`.
- Fix mobile grid overflows: replace inline `1fr auto 1fr` grids with responsive classes; verify viewport rendering at 360px width.

#### Step 3.4 — Database Optimization & N+1 Query Elimination
- Eliminate N+1 wishlist queries: load customer wishlist IDs as a single indexed array during page initialization.
- Add database indexes via migration:
  - `cart(session_id, customer_id, product_id)`
  - `products(is_active, department_id, category_id, is_featured, on_sale, created_at)`
  - `orders(customer_id, status, created_at)`
  - `contact_messages(is_read)`
- Optimize product list queries to select only required display columns rather than `SELECT p.*`.

---

### Phase 4: Performance & Image Pipeline (Photos First)

#### Step 4.1 — Image Processing Pipeline
- Create `includes/images.php`:
  - `process_uploaded_image()`: resize, auto-orient, strip metadata, convert to WebP (quality 80) and AVIF where supported, generate responsive widths (240px, 480px, 720px, 960px, 1280px).
  - Helper `render_picture()`: outputs `<picture>` elements with WebP/AVIF `srcset`, proper `sizes`, width/height attributes, and `loading="lazy"` / `decoding="async"`.
- Compress existing static assets:
  - Optimize `assets/brand/logo.png` from 1.14 MB down to < 40 KB WebP/PNG.
  - Convert hero slide banners down to < 120 KB WebP.
- Build CLI tool `tools/optimize-images.php` to batch-convert all existing catalog product images.
- Configure aggressive browser caching in `.htaccess`: `Cache-Control: public, max-age=31536000, immutable` for static hashed assets.

#### Step 4.2 — Critical Rendering Path & Asset Optimization
- Eliminate render-blocking `@import` font chains; self-host or preload modern WOFF2 subsets with `font-display: swap`.
- Minify CSS and JS; append version hashes (`?v=filemtime`) to cache-bust cleanly upon updates.

---

### Phase 5: SEO, PWA, Android App & Branding Polish

#### Step 5.1 — Search Engine Optimization (SEO)
- Implement clean, search-friendly URLs with backwards-compatible 301 redirects (`/product/{slug}-{id}`, `/shop/{dept-slug}`).
- Fix canonical URL logic in `includes/header.php` to point to unique product/category pages instead of generic scripts.
- Implement dynamic `sitemap.xml` generated directly from active database records; update `robots.txt` with absolute sitemap URL.
- Implement schema.org `Product` and `BreadcrumbList` JSON-LD microdata.

#### Step 5.2 — Progressive Web App (PWA) & Android App
- Harden `sw.js`: precache assets with individual error catch blocks; exclude authenticated user state; cache visited product and catalog pages via stale-while-revalidate; provide robust offline fallback.
- Harden Android app:
  - Update `applicationId` to `in.sskstore.app` and app name to "SSK Store".
  - Parameterize URL using Gradle build flavors (debug -> emulator, release -> production domain).
  - Restrict WebView navigation strictly to store domain; open external links (WhatsApp, Maps) in external browser.
  - Disable cleartext traffic in release mode.

#### Step 5.3 — Branding Cleanup
- Remove lingering "BAPS" naming references across SQLite seeds, comments, Android package, and database configuration, establishing pure "Shri Sahajanand Sahitya Kendra" identity.

---

### Phase 6: Self-Verification & Testing Matrix
- Static verification: `php -l` on all PHP files, `node --check` on JS, SQL syntax scanner for driver incompatibilities.
- Automated tests: execute functional and security test suites in `tests/security/` and `tests/functional/`.
- Re-measure performance: verify mobile Lighthouse score ≥ 90 and transfer size budget compliance.
- Deliver `docs/TEST_REPORT.md`, `docs/PERFORMANCE_REPORT.md`, `docs/DEPLOYMENT.md`, and final summary report.

---

## 3. Risk Assessment & Rollback Plan

| Risk Description | Probability | Impact | Mitigation Strategy | Rollback Procedure |
|---|---|---|---|---|
| Database migration failure on MySQL | Low | High | All migrations tested locally on MariaDB and SQLite; idempotent syntax (`IF NOT EXISTS`). | Every migration file includes a corresponding rollback script (`database/migrations/NNN_rollback.sql`). |
| Checkout transaction deadlock or error | Low | High | Use short lock timeouts and atomic single-statement updates with row-count verification. | Previous tested checkout script backed up; can revert immediately via git. |
| Image pipeline breaks on servers lacking WebP GD support | Low | Medium | Pipeline checks `function_exists('imagewebp')` and falls back gracefully to optimized JPEG. | Original images preserved in dedicated backup folder before optimization. |
| Cache header issues causing stale assets | Low | Low | Append file modification timestamp `?v=filemtime` to all script and stylesheet tags. | Remove `.htaccess` aggressive caching rules if needed. |

---

## 4. Checkpoint & Sign-off

Per Hard Rule 12:
**Execution will pause here for user approval of this Implementation Plan before altering any application code.**
