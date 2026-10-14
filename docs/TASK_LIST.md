# MASTER TASK LIST — SSK STORE
**Branch:** `audit/hardening-and-speed`  
**Tracking Document:** `MASTER_PROMPT_ANTIGRAVITY.md`  

---

## Phase 1: Read & Analyze (No Code Changes) — [DONE]
- [x] Initialized Git repository, committed baseline on `main`, tagged `pre-audit`.
- [x] Created and checked out working branch `audit/hardening-and-speed`.
- [x] Verified PHP syntax (`php -l`) on all 36 PHP files.
- [x] Verified JavaScript syntax (`node --check`) on all 6 JS files.
- [x] Measured baseline performance, initial response times, and static asset weights.
- [x] Verified and confirmed all P0 vulnerabilities and logic bugs.
- [x] Created `docs/DECISIONS_NEEDED.md`.
- [x] Created `docs/AUDIT_REPORT.md`.
- [x] Created `docs/IMPLEMENTATION_PLAN.md`.
- [x] Created `docs/PROGRESS.md`.
- [x] Created `docs/TASK_LIST.md`.

---

## Phase 2: P0 Fixes (Site-Breaking & Security) — [COMPLETED]
- [x] **Task 2.1:** Create `includes/security.php` (CSRF, timing-safe checks, API origin checks, `e()` escape helper, security headers).
- [x] **Task 2.2:** Create `admin/includes/admin_bootstrap.php` (Auth check, `is_active` check, page-level RBAC, session idle timeout).
- [x] **Task 2.3:** Add `db_insert()` and `db_transaction()` to `includes/db.php`; set session timezone to `+05:30`.
- [x] **Task 2.4:** Build migration runner `tools/migrate.php` and migration `database/migrations/001_initial_schema_fixes.sql`.
- [x] **Task 2.5:** Fix `checkout.php` transaction, atomic portable stock decrement query, and server-side coupon verification.
- [x] **Task 2.6:** Fix `admin/products.php` authentication bypass and move action handlers behind `admin_bootstrap.php`.
- [x] **Task 2.7:** Convert all admin GET state mutations (`delete`, `logout`) to POST + CSRF token verification.
- [x] **Task 2.8:** Fix SQLite-only `datetime('now')` in `admin/homepage_sections.php`.
- [x] **Task 2.9:** Remove hardcoded default credentials from database seeds and create CLI tool `tools/create-admin.php`.
- [x] **Task 2.10:** Restrict SQLite fallback strictly to non-production environments and harden `.htaccess` against direct file downloads.

---

## Phase 3: P1 + P2 Fixes (High/Medium Vulnerabilities & Logic Gaps)
- [ ] **Task 3.1:** Enforce stock boundaries in `add_to_cart` and `update_cart_quantity`; add out-of-stock badges and disable buttons.
- [ ] **Task 3.2:** Resolve disabled admin session persistence; enforce active status on every request.
- [ ] **Task 3.3:** Add brute-force login throttling (`login_attempts` table) and contact form honeypots.
- [ ] **Task 3.4:** Fix guest checkout account collisions and build password reset flow.
- [ ] **Task 3.5:** Build transactional email sender (PHPMailer with fallback to `logs/mail.log`).
- [ ] **Task 3.6:** Fix `admin/categories.php` missing `is_active` input; add subcategory management UI.
- [ ] **Task 3.7:** Implement Gujarati/Unicode transliteration helper for clean URL slugs.
- [ ] **Task 3.8:** Replace product hard delete with soft delete (`is_active = 0`, `deleted_at`).
- [ ] **Task 3.9:** Harden file uploads in `banners.php` and `bulk_import.php` (MIME validation, random names, GD re-encoding).
- [ ] **Task 3.10:** Fix XSS vulnerabilities in `main.js`, `pages.php`, and image attributes.
- [ ] **Task 3.11:** Implement lazy session initialization in `config.php`.
- [ ] **Task 3.12:** Fix double-submit button disable bug in `main.js`.
- [ ] **Task 3.13:** Resolve CSS self-referential variable cycles and syntax errors in `style.css`.
- [ ] **Task 3.14:** Fix mobile layout overflows on 360px viewports (checkout, account, admin).
- [ ] **Task 3.15:** Add dynamic shipping calculation by state across all 28 states and 8 union territories.
- [ ] **Task 3.16:** Eliminate N+1 wishlist queries and add database indexes.

---

## Phase 4: Speed Optimization (Photos First)
- [ ] **Task 4.1:** Build image pipeline `includes/images.php` (WebP/AVIF generation, responsive variants).
- [ ] **Task 4.2:** Create `<picture>` helper `render_picture()` with responsive `srcset` and lazy loading.
- [ ] **Task 4.3:** Compress existing static assets (optimize 1.14 MB logo down to < 40 KB, banners down to < 120 KB).
- [ ] **Task 4.4:** Create CLI image conversion script `tools/optimize-images.php`.
- [ ] **Task 4.5:** Remove render-blocking Google Font chains; preload self-hosted WOFF2 fonts.
- [ ] **Task 4.6:** Minify CSS/JS and append cache-busting timestamps (`?v=filemtime`).

---

## Phase 5: SEO, PWA, Android App & Branding Polish
- [ ] **Task 5.1:** Clean SEO URL routing with 301 redirects; fix canonical tags in `header.php`.
- [ ] **Task 5.2:** Generate dynamic `sitemap.xml` from active database items.
- [ ] **Task 5.3:** Harden service worker `sw.js` (non-atomic precache, offline page caching, safe update flow).
- [ ] **Task 5.4:** Harden Android WebView app (parameterize URLs, secure origins, disable cleartext).
- [ ] **Task 5.5:** Remove all remaining "BAPS" naming artifacts across code, comments, and seeds.

---

## Phase 6: Self-Verification & Final Report
- [ ] **Task 6.1:** Run static linting and security test suite (`tests/security/`).
- [ ] **Task 6.2:** Verify database operations on both MySQL and SQLite.
- [ ] **Task 6.3:** Re-measure performance under mobile 4G simulation and verify Lighthouse ≥ 90.
- [ ] **Task 6.4:** Deliver `docs/TEST_REPORT.md`, `docs/PERFORMANCE_REPORT.md`, and `docs/DEPLOYMENT.md`.
