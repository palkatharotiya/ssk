# MASTER PROMPT — SSK STORE
**Shri Sahajanand Sahitya Kendra · Kalakunj Mandir, Surat (https://sskstore.in)**
**Audit → Fix bugs & security holes → Make everything fast (photos first) → Test it yourself**

Use this file as your single source of truth. Re-read it whenever you lose context. The repository is already open in this workspace — do not ask me for files.

---

## 0. YOUR ROLE AND MISSION

You are a senior engineer, expert in PHP 8, MySQL/MariaDB + SQLite, vanilla JavaScript, CSS, PWA/service workers, Android WebView, web security (OWASP Top 10) and web performance (Core Web Vitals).

Mission, in this order:
1. READ and understand the whole repository before changing anything.
2. FIND every bug, error, security hole and business-logic loophole. The "Known Findings" in section 5 are only a head start from a first review — find more.
3. FIX them safely, without changing the look, brand or features that already work.
4. Make the site clearly FASTER — especially photo loading on mobile 4G — and prove it with before/after numbers.
5. TEST everything yourself (code, database, browser, phone sizes, dark mode, security) and report honestly.

---

## 1. CONTEXT YOU MUST KNOW

- **Project:** "SSK Store", online store of Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat): books, tulsi kanthi, japamala, ghar mandir, murtis, puja items, agarbatti, clothing, calendars.
- **Stack:** plain PHP (no framework) + PDO. MySQL/MariaDB in production (`database.sql`) with a silent SQLite fallback (`includes/db.php`). Vanilla JS (`assets/js/main.js`, `theme-toggle.js`, `pwa-install.js`, `pwa-strings.js`). CSS: design tokens in `assets/css/theme.css`, storefront components in `style.css`, admin in `admin.css`. PWA (`sw.js`, `manifest.webmanifest`, `offline.html`). Android WebView app in `android-app/` (package `store.baps`).
- **Folders:** storefront in root, admin in `/admin`, JSON endpoints in `/api`, shared code in `/includes`.
- **Users:** customers are mostly on mobile phones with normal 4G in Gujarat/India. English + Gujarati both matter. Temple volunteers (not programmers) run the admin, so admin screens must stay simple and safe.
- **IMPORTANT:** local development runs with `php -S 0.0.0.0:8000`. With no MySQL available, `db.php` silently switches to SQLite. Production uses MySQL. Several bugs below exist ONLY on MySQL, so they were never seen. You must test on BOTH.
- The code was cloned from a "BAPS Store" project. "BAPS" leftovers are everywhere (see P3-1).
- `THEME.md` contains "done" checklists. Do NOT trust them. Verify by running.
- **Files the first review could NOT see — read them first:** storefront `index.php`, storefront `cart.php`, `admin/includes/header.php`, `admin/includes/footer.php`, root `.htaccess` (if any), `android-app/app/src/main/AndroidManifest.xml`, `res/layout/activity_main.xml`, root `build.gradle`, and any other file in `/assets`, `/api`, `/includes`, `/admin` not mentioned here.

---

## 2. HARD RULES

1. Create a new git branch `audit/hardening-and-speed`. Never commit to `main`. Tag the starting point `pre-audit`. Small commits, clear messages (e.g. `fix(security): add CSRF to admin_users`). After EVERY commit the site must still start with `php -S` and show no fatal errors.
2. Phase 1 changes NO application code — it only creates files in `docs/`.
3. Keep plain PHP, code must run on PHP 8.0–8.3. No framework rewrite. No new Composer/npm dependency unless truly needed and shared-hosting-friendly (if you add PHPMailer, commit `vendor/`). Node tools are allowed only at dev time; commit the built files; the server must never need Node.
4. Keep the existing look, brand tokens (navy `#011760`, red `#E70207`), light/dark themes, English + Gujarati support and every working feature. If you remove or change a feature, say so in the report.
5. **Database:** every schema change = an idempotent migration file `database/migrations/NNN_name.sql` (+ `tools/migrate.php`) that works on MySQL/MariaDB AND SQLite, with a rollback note. Back up first. Use only portable SQL outside driver-specific branches (no `datetime('now')`, no 2-argument `MAX()`, no `INSERT OR IGNORE`).
6. Secrets (DB password, SMTP, payment keys, UPI ID, admin password) come from environment variables or a git-ignored `config.local.php`, never from committed code. Extend `.gitignore`: `uploads/`, `*.sqlite`, `logs/`, `.env`, `config.local.php`, `keystore.properties`, `*.jks`.
7. Every state-changing action = POST + CSRF token + authentication + permission check. Every output is escaped for its context. Every SQL is a prepared statement.
8. Never show raw exception messages to users. Log them to a file outside the web root.
9. Minimal, targeted diffs. If you change a function signature, `grep` and update every caller. No unrelated refactors, no commented-out dead code, no debug leftovers.
10. Never say "fixed" without proof (command output, screenshot or test result).
11. If you need a decision from me, write it in `docs/DECISIONS_NEEDED.md` with a safe default and keep going.
12. **Stop and wait for me ONLY for:** (a) approval of the Implementation Plan, (b) running migrations on any non-local database or changing production config, (c) deleting files/tables/data history or adding a paid third-party service.
13. **Talk to me in very simple English** (short sentences, no jargon). Code, comments and technical docs can use normal technical English.

---

## 3. WORKFLOW (use Planning mode; create the Task List, Implementation Plan and Walkthrough artifacts if your environment supports them)

| Phase | What | Output |
|---|---|---|
| 1 | Read & Analyze (no code changes) | `docs/AUDIT_REPORT.md`, Implementation Plan, Task List — **STOP for my approval** |
| 2 | P0 fixes (site-breaking + security) | tested commits |
| 3 | P1 + P2 fixes | tested commits |
| 4 | Speed (section 6): measure first, photos first | tested commits + numbers |
| 5 | SEO, PWA, Android, branding, polish (section 7) | tested commits |
| 6 | Self-verification (section 8) + final report (section 10) | `docs/TEST_REPORT.md`, Walkthrough with screenshots |

Keep `docs/PROGRESS.md` updated after every phase (done / in progress / next / decisions) so work can resume in a new session.

**Suggested order inside Phase 2:** (1) shared helpers: `includes/security.php` (CSRF, rate-limit, headers, `e()` escape helper), `admin/includes/admin_bootstrap.php` (auth + active check + permission + CSRF); (2) DB layer: `db_insert()`, `db_transaction()`, portable SQL, timezone, migration runner; (3) checkout transaction + stock + coupon; (4) admin auth/CSRF/permissions on every admin file; (5) default credentials + exposed files + SQLite fallback; (6) the rest of P0/P1.

---

## 4. PHASE 1 — READ & ANALYZE

1. List the repo tree. Read EVERY file — PHP, JS, CSS, SQL, XML/JSON, Java/Gradle, MD. Do not skim.
2. Write a short architecture map: request flow (config → db → functions → header → page → footer), session/cart/auth flow, checkout/order flow, admin RBAC flow, image flow, PWA flow, DB schema (MySQL vs SQLite differences).
3. Run quick static checks: `php -l` on every PHP file; `node --check` on JS; compare every called PHP function with the defined ones (find undefined ones); list every SQL string and mark driver-specific ones; list every `$_GET/$_POST/$_FILES/$_SERVER` use; list every `header('Location')`; count inline `onclick=`, `javascript:` and `style=`.
4. Verify each Known Finding in section 5 and 7 (mark CONFIRMED / NOT REPRODUCED / PARTIAL) and add every new finding. Use the checklist below.
5. Baseline performance (section 6.1): Lighthouse mobile for Home, Catalog, Product + network weight per page. Save the numbers.
6. Write `docs/AUDIT_REPORT.md`: table `ID | Severity | Area | File:line | Problem | Proposed fix | Status`. Then write the Implementation Plan (order of work, risks, rollback). Then STOP for my approval.

**Bug-hunt checklist (apply to every file):**
- **SQL:** prepared? ints cast? driver-specific function? missing index? query inside a loop (N+1)? `SELECT *` with big TEXT columns in lists?
- **Output:** escaped for HTML text / attribute / JS string / URL / JSON? double-escaped?
- **Endpoint:** POST? CSRF? auth? RBAC? rate-limited? idempotent? safe error text?
- **Upload:** real content check, random name, size cap, extension allow-list, storage path, execution blocked?
- **Redirects:** allow-listed target, `exit` after `header()`.
- **Input:** type/range/allow-list; negative or huge numbers; empty; Unicode (Gujarati) → use `mb_*`.
- **Business logic:** stock, price, discount, shipping, coupon limits, order status changes, double submit, race conditions.
- **Sessions:** lazy start, regenerate on login/logout/register, cookie flags, timeout, file locking.
- **Time:** PHP timezone vs DB timezone (`CURRENT_TIMESTAMP` is UTC on SQLite).
- **Frontend:** render-blocking resources, layout shift, labels, keyboard use, focus, contrast, reduced-motion, 360 px width, dark mode.
- **PWA:** private pages cached? update flow? offline fallback really cached?
- **Android:** navigation limits, JS bridge exposure, cleartext, hard-coded URLs, release config.
- **PHP 8.0–8.3:** run with `E_ALL`; fix notices/deprecations (e.g. `htmlspecialchars(null)`).

---

## 5. KNOWN FINDINGS (head start from a first review — verify each, then fix)

IDs are stable. Use them in commit messages and in the final report.

### P0 — site-breaking or takeover-level (fix first)

**P0-1 · MySQL-incompatible SQL breaks checkout and one admin page.**
- `checkout.php`: `UPDATE products SET stock_qty = MAX(0, stock_qty - ?)` — 2-argument `MAX()` exists only in SQLite. On MySQL/MariaDB it throws AFTER the order row was inserted → half-created order + white page.
- `checkout.php`: inserts `payment_status = 'pending_verification'` (UPI/Card/NetBanking — the default path) but `database.sql` defines the ENUM as `pending, paid, failed, refunded` → error in strict mode or an empty value.
- `admin/homepage_sections.php`: `updated_at = datetime('now')` in 3 queries → SQLite only.
- **Fix:** portable SQL (`CURRENT_TIMESTAMP`); atomic `UPDATE products SET stock_qty = stock_qty - ? WHERE id = ? AND stock_qty >= ?` and check affected rows; migration adding `pending_verification` (or map to `pending` + a separate flag); wrap order + items + stock + coupon usage + cart clearing in ONE DB transaction with rollback and a friendly error page.

**P0-2 · Probable admin authentication bypass in `admin/products.php`.** The delete handler (`GET ?delete=ID`) and the create/edit POST handler run BEFORE `includes/header.php` (where the admin login gate appears to live), and the file has no explicit `is_admin_logged_in()` / permission check. `banners.php` and `bulk_import.php` check explicitly; this file does not. A CSRF token can be obtained anonymously from any public form (e.g. the contact page), so even the POST may pass. Test with curl and no cookie.
**Fix:** one shared `admin/includes/admin_bootstrap.php` (auth + active-account check + per-page permission from `get_all_admin_permissions()` + CSRF helper) required as the FIRST line of every admin file. Verify RBAC is enforced server-side on every admin page, not only by hiding menu links.

**P0-3 · Missing CSRF + state changes through GET.**
- No CSRF check or token at all in: `admin_users.php` (create/edit/disable/delete admins — an attacker can make a new super_admin), `banners.php` (all actions), `coupons.php` (add), `homepage_sections.php` (all), `bulk_import.php` (import).
- State changes through GET: `products.php?delete=`, `coupons.php?delete=`, `categories.php?delete_dept=` and `?delete_cat=`, `messages.php?mark_read=`, `admin/logout.php`, `api/auth.php?action=logout`, `api/cart.php?action=clear`. SameSite=Lax does NOT stop top-level GET navigations.
- **Fix:** all become POST + token (helper `require_csrf()`; JS sends `X-CSRF-Token`); GET never changes state; add `Origin`/`Sec-Fetch-Site` check on API POSTs.

**P0-4 · Default / known credentials.** `database.sql` seeds admin `admin` and customer `rajesh@example.com` with the bcrypt hash of the word **`password`** (verified: the file comment says "admin123" but the hash is "password"). `db.php` (SQLite seed) auto-creates `admin / admin123` and `customer / customer123`.
**Fix:** no seeded users at runtime or in SQL; `tools/create-admin.php` (CLI, asks for a strong password, min 12 chars); force "change password" for any account still using a known hash; red warning on the dashboard if a default account exists; admin passwords min 12 chars, customer passwords min 8.

**P0-5 · Silent MySQL→SQLite fallback + sensitive files possibly exposed.** If MySQL is unreachable for a moment, `db.php` creates/uses `baps_store.sqlite` in the web root: orders and customers go to the wrong DB, the seeded default admin appears, and the `.sqlite` file may be downloadable (it holds password hashes). Also `database.sql`, `includes/`, `logs/`, `.git/`, `*.md` may be web-reachable.
**Fix:** fallback only when `APP_ENV != production` AND `DB_DRIVER=sqlite` is set explicitly; in production show a 503 maintenance page and log; keep DB/log files outside the web root (or protected); root `.htaccess` (+ nginx notes) denying `*.sqlite|*.sql|*.md|*.log|.git|includes|logs|vendor|database`; `display_errors` OFF unless `APP_ENV=development`.

**P0-6 · Undefined function `db_insert()`.** `admin/bulk_import.php` calls `db_insert()` (auto-create departments/categories — ticked by default) but it is not defined in any file reviewed → fatal error. **Fix:** add `db_insert()` and `db_transaction(callable)` to `db.php`; run imports in a transaction.

**P0-7 · Coupon abuse (money loss).** `api/coupon.php` trusts the client-sent `subtotal` (min-order bypass with POST `subtotal=99999`). The session copy has no `min_order_amount`, so the re-check in `checkout.php` (`?? 0`) always passes. `start_date`, `end_date`, `max_uses`, `used_count`, `is_active` are never enforced. Percentage above 100 is not capped.
**Fix:** use the server cart subtotal only; re-validate the coupon from the DB inside the checkout transaction (active, dates, min order, usage limit, per-customer limit); cap discount ≤ subtotal; increment `used_count` atomically; admin form gets dates/limits/active toggle.

### P1 — high

**P1-1 · Out-of-stock items can be bought / overselling.** `add_to_cart` and `update_cart_quantity` clamp only when `stock_qty > 0`, so stock-0 items can be added with any quantity. `checkout.php` never re-checks stock. Catalog cards show "Add to Cart" even when out of stock. No max quantity per line. Race between two buyers. **Fix:** enforce on add/update/checkout (transaction + conditional decrement), "Out of stock" badge + disabled button, per-line cap (e.g. min(stock, 20)).

**P1-2 · Disabled/removed admins keep access; admin privilege rules are loose.** `login_admin()` does not check `is_active`; `is_admin_logged_in()` only checks a session key; disabling an admin or changing permissions does not affect an open session; no idle timeout; `admin/login.php` `trim()`s passwords. In `admin_users.php`, anyone with the `admin_users` permission can create `super_admin` accounts or reset a super admin's password; role and permission keys are not allow-listed.
**Fix:** check `is_active` at login and on every request (`current_admin()` null → destroy session); 30-min idle timeout; regenerate session id on privilege change; only `super_admin` may create/edit `super_admin`; allow-list `role` and permission keys; never trim passwords.

**P1-3 · No brute-force / spam protection** on customer login, register, admin login, contact form, coupon check, search. **Fix:** `login_attempts` table (IP + account), progressive delay/lockout (e.g. 5 fails → 15 min), uniform error messages, honeypot + CAPTCHA hook (Cloudflare Turnstile ready), rate limits on contact/coupon/search.

**P1-4 · Guest checkout locks real customers out and mixes orders.** A guest order creates a `customers` row with a random password. If that person later registers → "account already exists", and there is NO password-reset flow ("Forgot password?" just opens the contact page). A guest who types someone else's email attaches the order to that person's account. Checkout only checks the email with `empty()`.
**Fix:** `is_guest` flag; password reset (hashed token table, 1 h expiry, single use) + email verification; "claim guest account" on registration after email verification; never attach guest orders to an existing registered account unless logged in; `FILTER_VALIDATE_EMAIL` server-side; `session_regenerate_id(true)` after registration.

**P1-5 · Promises the site does not keep.**
(a) UI says order confirmation/tracking "will be sent" by email — there is no mail code anywhere.
(b) Footer "Subscribe to Satsang Newsletter" shows a toast and stores nothing.
(c) Checkout offers Card and NetBanking and the footer lists VISA/Mastercard/RuPay/Netbanking, but there is no payment gateway — orders are created with nothing to pay.
(d) `STORE_UPI_ID` is `9426551098@upi` — owner must confirm it is the temple's real, active UPI ID (test with a ₹1 payment).
(e) Product-page "pincode checker" only guesses from the first 2 digits and promises "2-3 business days".
**Fix:** SMTP mailer (env config; PHPMailer or minimal SMTP; dev fallback logs to `logs/mail.log`) for order confirmation, status change, password reset, new-order alert to the temple, contact-form alert. Newsletter table with double opt-in, or remove the box. Payments default: keep UPI (QR + `upi://pay?pa=…&pn=…&am=<amount>&cu=INR&tn=<ORDER>` deep-link button + "I have paid" form to submit the UTR) and COD; remove Card/NetBanking unless I supply gateway keys — then add a gateway adapter (Razorpay, webhook signature verified, idempotent). Make the pincode text honest ("estimate").

**P1-6 · Admin categories bug hides categories.** The category form has no `is_active` field but the handler does `$isActive = isset($_POST['is_active']) ? 1 : 0;` → every category added/edited becomes INACTIVE and disappears from nav/filters; its description is wiped to ''. Also: no admin UI for subcategories (storefront nav/filter use `sub=`), the product form cannot set a subcategory, and the category dropdown is not filtered by department. **Fix all** (checkbox/hidden default, subcategory CRUD, dependent dropdowns + server-side dept↔category↔subcategory validation).

**P1-7 · Slug bug with Gujarati/Hindi names.** The slug regex strips non-ASCII → `-` or empty → the second Gujarati-named product/department hits the UNIQUE constraint → fatal error. Also random SKU collisions (`rand(100,999)`) and duplicate names. **Fix:** transliteration (intl `Transliterator`, with fallback) → fallback `item-<id>`; trim hyphens; uniqueness loop (`-2`, `-3`); one helper used by products, categories, departments, bulk import.

**P1-8 · Hard delete of products breaks.** `DELETE FROM products` fails when the product exists in `order_items` (FK) → 500, and would destroy order history. **Fix:** soft delete (`deleted_at` / `is_active=0`), block hard delete when referenced, remove from carts/wishlists.

**P1-9 · Upload hardening (`banners.php`, `bulk_import.php`).** Extension-only check; `mkdir(...,0777)`; predictable `rand()` names; same-name overwrite; double extension `x.php.jpg` can execute on some Apache setups; ZIP bomb; no MIME/dimension/size check. The shipped `.htaccess` uses Apache 2.2 syntax (`Order/Deny`) → 500 on Apache 2.4 without `mod_access_compat`, and it only blocks names ending in `.php`. A `post_max_size` overflow silently empties `$_POST` (the admin sees nothing).
**Fix:** random server-generated name + extension from the detected type; `getimagesize` + `finfo`; size/dimension caps; re-encode with GD/Imagick (kills payloads, strips EXIF/GPS); 0755/0644; Apache 2.4 syntax with `<IfModule>` fallback + `SetHandler None`/`php_flag engine off` in upload dirs; ZIP entry count/size limit; detect and report `post_max_size` overflow.

**P1-10 · XSS / escaping gaps.**
- `main.js` builds HTML with unescaped `item.name`, `item.image_url`, toast `message`, and the typed search term (`No products found for "${q}"`).
- `checkout.php` `confirmGuestEmail()` puts the typed email into `innerHTML`.
- `<img src="<?= $imgUrl ?>">` is printed without `htmlspecialchars` in catalog/product/account/checkout/admin; `FILTER_VALIDATE_URL` is not an XSS defence (odd schemes/quotes can pass).
- `pages.php` prints `$page['content']` raw (no admin editor exists; if you add one, sanitize with an allow-list).
- JSON-LD prints `SITE_URL` raw and `SITE_URL` is built from the `Host` header (host-header injection).
- All APIs return `$e->getMessage()` to the browser.
**Fix:** `textContent`/`escapeHtml()` in JS, an `e()` helper in PHP everywhere, fixed `SITE_URL`/allowed-hosts list + `json_encode`, generic errors + server log.

**P1-11 · Sessions hurt security AND speed.** `session_start()` runs on every request, even for bots and `api/search.php`. PHP locks the session file, so parallel AJAX calls from one user queue up; every crawler gets a cookie + session file; responses with `Set-Cookie` are usually not cached by CDNs. The cookie `Secure` flag fails behind a proxy (no `X-Forwarded-Proto` handling).
**Fix:** lazy session start (only for cart/login/CSRF/admin), `session_write_close()` right after reading in read-only endpoints, no session in `api/search.php`, strict mode + cookies-only + HttpOnly + SameSite, trusted-proxy HTTPS detection, `session_regenerate_id(true)` on login/logout/register/privilege change.

**P1-12 · No security headers.** Add (via `.htaccess` and/or PHP): `X-Content-Type-Options: nosniff`, `X-Frame-Options`/`frame-ancestors`, `Referrer-Policy`, `Permissions-Policy`, HSTS (only after HTTPS is confirmed), CSP in Report-Only first (inline `onclick`/`style`/`javascript:` links must be refactored over time; use nonces), hide `X-Powered-By`, HTTPS redirect, an `error_log` path that exists and is not web-reachable.

**P1-13 · Double-submit guard bug (`main.js`, bottom).** The document-level `submit` listener disables the button and writes "⏳ Processing…" even when the form's own handler called `preventDefault()` (AJAX login/register in `account.php`, checkout login, modal). After a wrong password the button stays disabled forever; with a fast response the 50 ms timeout fires after the reset; bfcache "Back" restores a disabled button. **Fix:** skip when `e.defaultPrevented`; one `setLoading(btn, bool)` helper that always restores; reset on `pageshow`.

**P1-14 · Broken mobile layouts from inline grids (no media queries).** `account.php` (260 px sidebar + content grid), `checkout.php` step 1 (`1fr auto 1fr` three-column login/guest grid), plus admin grids (`coupons.php`, `orders.php`, `index.php`, `categories.php`, `bulk_import.php`, `products.php`). The footer newsletter row has invalid CSS `flex-wrap: gap: 1.5rem;` → no wrapping, horizontal overflow on phones. **Fix:** classes + responsive rules instead of inline layout styles; verify at 360 px.

**P1-15 · CSS defects.**
- (a) `style.css :root` contains self-referencing variables: `--border: var(--border)`, `--text-muted`, `--success`, `--warning`, `--danger`, `--shadow-sm/md/lg`, `--radius-sm`, `--radius-full`. A custom property that references itself is a cycle → "invalid at computed-value time". Because `style.css` loads after `theme.css`, it overrides the real values, so borders, shadows, rounded corners, muted text and status colors can silently disappear on the storefront. Verify with `getComputedStyle(document.documentElement).getPropertyValue('--border')`, then delete those lines.
- (b) stray `color: var(--text); }` after the first `.feature-box:hover` block (syntax error swallows the next rule).
- (c) duplicate component rules in `theme.css` and `style.css` fighting each other (`.btn`, `.form-input`, `.badge`, `.whatsapp-float` ×3, `.back-to-top` ×2) — keep one definition each.
- (d) undefined names used: `--admin-text-main`, `--primary` (in admin), `.admin-form-input`, `.admin-card-header/-body`, `.sun-icon/.moon-icon` (both icons show on the admin login page), `.status-refunded`.
- (e) hard-coded light colors in admin pages (`#f8fafc`, `#ffffff`, `#f0fdf4`, `#f1f5f9`, …) make text unreadable in dark mode (messages, banners, bulk import, admin users) — use tokens.
- (f) amber text and white-on-amber badges (`#F59E0B` on white ≈ 2.1:1) fail WCAG AA although THEME.md claims otherwise.

**P1-16 · Price, shipping and money consistency.** Shipping is computed for Gujarat at first render; changing State does not update the shown total, so the customer pays a different amount than the button says. Flat 50/80 is hard-coded while `shipping_zones` and `settings` are ignored. Sorting uses `COALESCE(sale_price, price)` but display uses `sale_price < price`; the `on_sale` flag can disagree with the real sale price; `search.php` uses another price rule. Client-posted `payment_method` and `state` are not allow-listed; PIN↔state is not validated; the state dropdown has only 10 options.
**Fix:** one `effective_price()` helper; live shipping/total update (small JSON call); all 28 states + 8 UTs; allow-lists; PIN-prefix check.

### P2 — medium

- **P2-1 PHP performance bugs:** `is_in_wishlist()` per product card (N+1: 12+ queries per catalog page); admin categories query multiplies rows (two LEFT JOINs with `COUNT(DISTINCT)`); `products.views` is a DB write on every GET, bots included; navigation tree = 3 queries per request; list queries use `SELECT p.*` (long TEXT); `get_product_image_url()` does up to 3 `file_exists()` per image per request; `placeholder.php` runs PHP for every missing image.
- **P2-2 Database:** add indexes — `cart(session_id, customer_id, product_id)` (+ UNIQUE per owner/product), `products(is_active, department_id, category_id, subcategory_id, is_featured, on_sale, created_at)`, `orders(customer_id, status, created_at)`, `contact_messages(is_read)`. `LIKE '%q%'` on name/sku/description → MySQL FULLTEXT or prefix search. Pagination (`LIMIT`) for admin products/orders/customers/messages and account orders. Cron cleanup of guest carts older than 30 days. `get_cart_count()` counts inactive products while `get_cart_items()` hides them. SQLite: `PRAGMA foreign_keys=ON`, WAL, busy_timeout. MySQL: set session time zone.
- **P2-3 Timezone:** PHP uses Asia/Kolkata but DB `CURRENT_TIMESTAMP` is UTC on SQLite (server zone on MySQL) → order times can be 5½ h wrong. Pick one rule (store UTC and convert on output, or set the DB session zone to +05:30).
- **P2-4 Admin dashboard:** revenue sums all non-cancelled orders (counts unpaid and "refunded"); `$lowStockProducts` is queried but never shown. **Fix:** revenue = paid only, show pending payments separately, render the low-stock widget, add last 7/30-day sales + top products.
- **P2-5 Admin orders:** status/payment values not allow-listed; filter buttons lack Cancelled/Refunded; `pending_verification` not selectable; no pagination/search; cancelling doesn't restock; no tracking number/courier field; no status history; no customer notification; no printable packing slip.
- **P2-6 Admin UX for volunteers:** the product form has NO image upload (admin types a filename and must FTP the file); the live preview looks in the wrong folder; no duplicate-product button; CSV import doesn't validate `price > 0` (₹0 products can be imported and sold), reports row errors poorly, isn't transactional, breaks on Excel "ANSI" CSV with Gujarati (detect/convert encoding), and doesn't support `subcategory`; no unread-message or low-stock badge.
- **P2-7 Text/Unicode:** `substr()` on Gujarati (admin product list) cuts characters → `mb_substr`; `strtolower/strtoupper` → `mb_*` where relevant. `<title>`/OG show `&amp;` because `product.php` and `pages.php` call `htmlspecialchars()` and `header.php` escapes again (e.g. "Terms &amp; Conditions").
- **P2-8 Phone numbers** are stored in 3 formats (`+91 98…`, `+9198…`, `98…`) so the profile form's `pattern="[0-9]{10}"` rejects stored values. One `normalize_phone()`; store 10 digits; show `+91` in the UI.
- **P2-9 Pages/CMS/contact:** `pages.php?slug=shipping-policy` is linked from footer, checkout and sitemap but exists in neither seed → silently redirects to home (soft 404). The `database.sql` Contact page has a different phone/hours than the rest of the site. The contact form has no Post/Redirect/Get (refresh = duplicate message). Unknown slug/product should return a real 404 (`404.php` exists but nothing uses it; no `ErrorDocument`). Add an admin "Pages (CMS)" editor with sanitization and seed Shipping / Return / Privacy (India DPDP-aware) / Terms drafts for the temple to review.
- **P2-10 Accessibility:** search suggestions are not keyboard-accessible (use ARIA combobox, arrows, Esc, `aria-live`); modals lack `role="dialog"`, focus trap, Esc, focus return; `javascript:` hrefs; `.skip-link` CSS exists but is never rendered; hero autoplay ignores `prefers-reduced-motion` and has no pause; 11 px mobile-nav labels; the hack `[style*="font-size: 0.75rem"] { font-size: .875rem !important }` in `theme.css` must go (fix the real classes); `<html lang="en">` is fixed although Gujarati strings exist.
- **P2-11 `main.js` details:** related-product "Add to Cart" uses the MAIN product's `#product-qty` (adds the wrong quantity); search responses can arrive out of order (use `AbortController` + small cache); scroll listener not passive; toasts overlap the mobile bottom nav/floating buttons; `togglePasswordVisibility()` replaces SVG icons with emoji; cart page reloads the whole page on every quantity click.
- **P2-12 `pwa-install.js` / `theme-toggle.js`:** `pwa-install.js` returns early in standalone mode → skips Service-Worker registration and the update toast for installed-app users; unguarded `localStorage` (throws in private mode/blocked storage) in both files; iPadOS 13+ (reports as Mac) not detected as iOS; iOS sheet has no focus trap/Esc; old Safari lacks `matchMedia().addEventListener`.
- **P2-13 `sw.js`:** `cache.addAll()` is atomic — one missing file (e.g. `logo-light.png`) silently aborts the whole precache, including `offline.html`; precached URLs have no `?v=` so they never match the versioned CSS/JS actually requested; `'/'` (a personalized page showing the user's name) is precached; navigations are never stored although the UI says "previously visited pages may still be viewable"; `STATIC_CACHE` grows without limit; `skipWaiting()+clients.claim()` can swap code mid-checkout; the 60-entry image cache is tiny. See PWA-1.

### P3 — low / polish

- **P3-1 Branding & legal:** remove all "BAPS" leftovers — DB name `baps_store`, `baps_store.sqlite`, file headers, SQLite seed (`BAPS Administrator`, `admin@baps.store`, coupons `BAPS10`/`DIWALI50`, category "BAPS Kirtans", products "BAPS Premium Agarbatti / Wall Calendar / Planner", text mentioning BAPS gurus/Akshardham), `style.css` `.baps-*` classes, Android package `store.baps` and app name "BAPS Store" (trademark risk for a Play Store release). Make sure publisher/legal wording is accurate for SSK.
- **P3-2 Housekeeping:** `manifest.json` duplicates `manifest.webmanifest` (keep one, serve as `application/manifest+json`); `README.md` leaks a local Windows path; the footer links publicly to `admin/login.php` (remove the link; rate-limit; optional IP allow-list/2FA); `offline.html` uses `<html lang="gu">` for mixed content, relative asset URLs and `.btn-lg` that exists only in `style.css`; PHP 8.1+ deprecations (`htmlspecialchars(null)`, e.g. `product_sku`, `payment_method`); `product_reviews.is_approved` default differs (SQLite 1 / MySQL 0) and the reviews query in `product.php` has no UI; hard-coded phone/UPI/address in 10+ places → one config + an admin "Store Settings" page; unused columns (`departments.image`, `categories.image`, `products.gallery`).
- **P3-3 COD abuse:** consider a COD limit and OTP/WhatsApp confirmation for large first orders.
- **P3-4 Language:** EN/ગુજરાતી switcher (nav, product, cart, checkout, emails) using the `lang` attribute + strings files (see section 12).

---

## 6. SPEED PLAN (after P0/P1; measure before and after)

### 6.1 Targets (mobile Lighthouse, Fast 4G, 4× CPU slowdown)

| Metric | Target |
|---|---|
| Lighthouse Performance (Home, Catalog, Product) | ≥ 90 |
| LCP | ≤ 2.5 s (aim ≤ 2.0 s) |
| CLS | ≤ 0.1 |
| TBT / INP | ≤ 200 ms |
| Home page total transfer | ≤ 1 MB (images ≤ 600 KB) |
| Hero image on mobile | ≤ 120 KB |
| Product card image | ≤ 30 KB (≈ 480 px WebP) |
| JS / CSS (gzip) | ≤ 60 KB / ≤ 50 KB |
| Accessibility / Best Practices / SEO | ≥ 95 |

Save baseline + final numbers in `docs/PERFORMANCE_REPORT.md`.

### 6.2 Photos — the biggest win, do this properly

Today every `<img>` loads the original uploaded file (possibly a 3–5 MB phone photo) at any screen size. There is no WebP, no `srcset`, no `width`/`height`. The logo is 1922×713 px but shown at about 151×56 px. Hero images are one big file for phone and desktop. Missing images are generated by a PHP script per image.

Build an image pipeline:
1. `includes/images.php` (GD; Imagick if present; check `imagewebp`/`imageavif` and fall back gracefully to optimized progressive JPEG). `process_uploaded_image()`: validate real type/size/dimensions, auto-rotate by EXIF then strip EXIF/GPS, cap the original at ≈ 2000 px, create variants (≈ 240, 480, 720, 960, 1280 px wide) as WebP (quality ≈ 75–80) and AVIF if available, random file names, store relative paths in the DB so no `file_exists()` per request.
2. `render_picture($img, $alt, $sizes, $eager)` helper: `<picture>` with AVIF/WebP `srcset` + fallback `<img>`, correct `sizes` for each grid (5/4/3/2 columns), explicit `width`/`height`, `decoding="async"`, `loading="lazy"` below the fold. First row of cards, the product main image and hero slide 1 are eager with `fetchpriority="high"` (+ `<link rel="preload" as="image" imagesrcset=…>` for the LCP image). Dominant-colour or tiny blurred background to avoid flashes.
3. Admin: product form gets drag-and-drop multi-image upload (preview, reorder, alt text, delete) wired to the pipeline. Banners get separate mobile (≈ 800×500) and desktop (≈ 1920×600) crops. Bulk-import ZIP/multi-file images go through the same pipeline. Old filename-based images keep working.
4. `tools/optimize-images.php` (idempotent, `--dry-run`, batch/resumable) converts all existing images in `uploads/products`, `assets/images` and banners.
5. Fix static brand images: logo exported at 2× display size (WebP/PNG-8 or SVG), OG image 1200×630, icons correctly sized.
6. Missing-image fallback = one static file or inline SVG data-URI, not a PHP request per image (keep `placeholder.php` only as a rare fallback and clamp `w`/`h`).
7. Delivery rules in `.htaccess` (+ nginx snippet in docs): `Cache-Control: public, max-age=31536000, immutable` for versioned/hashed images and assets; `AddType image/webp .webp` and `image/avif .avif`; gzip/brotli for text; ETag/304.
8. Document (do not depend on) free Cloudflare in front: Brotli, HTTP/3, edge image cache.

### 6.3 Fonts, CSS, JS

- Remove both CSS `@import` Google-Fonts chains (render-blocking and sequential). Use `<link rel="preconnect">` + one `<link>` with `display=swap`, or better self-host WOFF2 (Plus Jakarta Sans Latin subset + Noto Sans Gujarati subset), preload the main weight, add a fallback with `size-adjust`. Drop unused fonts (`Outfit`, Devanagari).
- Merge/minify CSS and JS (dev-time build; commit the output, or a PHP build script). `defer` all scripts. Put a version query (`?v=filemtime`) on JS too (CSS has it, JS does not, and the service worker serves scripts stale-while-revalidate, so users run old JS after a deploy). Inline critical CSS for above-the-fold. Remove unused CSS (check Coverage). Replace inline-style bloat with classes (smaller HTML, CSP-friendly).

### 6.4 PHP / database / caching

- Remove N+1 (load wishlist ids once as a set), add indexes (P2-2), select only needed columns, throttle `views` (once per session/product or batched), cache navigation tree / settings / homepage-section queries (APCu or file cache, TTL 5–10 min, invalidated by admin saves), lazy sessions (P1-11).
- Recommend/document: PHP ≥ 8.2, OPcache on, gzip/brotli on, HTTP/2.
- Stretch, only if budgets are still missed: short-TTL cache for anonymous HTML (`Cache-Control: public, s-maxage=…, stale-while-revalidate`); this needs cart count / user name / CSRF to move into a tiny JSON call.

### 6.5 Perceived speed

Prefetch product pages on hover/touch-start (same-origin), skeleton boxes with fixed aspect ratio, optimistic add-to-cart UI, update cart rows via JSON instead of `location.reload()`, search debounce (exists) + `AbortController` + small client cache.

---

## 7. SEO · PWA · ANDROID

**SEO-1 · Wrong canonical on every page (critical for Google).** `includes/header.php` builds the canonical from `strtok(REQUEST_URI,'?')`, so ALL products canonicalise to `/product.php`, all categories to `/catalog.php`, all CMS pages to `/pages.php`. **Fix:** clean slug URLs (`/product/{slug}-{id}`, `/shop/{dept-slug}`, `/page/{slug}`) with 301 redirects from the old URLs (old URLs keep working); correct canonical and `og:url`; per-product `<meta description>` and `og:image` (product photo, 1200×630); JSON-LD `Product` (brand, sku, offers with price/availability/`priceValidUntil`) and `BreadcrumbList`; `noindex` or canonical-to-base for sort/filter/`page>1` variants; `hreflang` if Gujarati pages are added.

**SEO-2 · Sitemap & robots.** `sitemap.xml` is static (product IDs 1–29, identical `lastmod`) and includes the broken shipping-policy page → generate it dynamically from the DB (active products/categories/pages, real `lastmod`). `robots.txt` `Sitemap:` must be an absolute URL.

**PWA-1 · Service worker.** Precache each critical asset individually (one 404 must never kill `offline.html`); match versioned URLs (`ignoreSearch` or a versioned list); don't precache the personalized `/`; cache successful anonymous GET navigations (catalog/product/pages) with a size/age limit, or remove the "previously visited pages" claim; enable navigation preload; limit/expire STATIC and IMAGE caches (stale-while-revalidate for images); choose ONE update strategy (prompt the user, no forced swap during checkout); serve `sw.js` with `Cache-Control: no-cache`; move SW registration out of the standalone early-return. Manifest: one file, `application/manifest+json`, add screenshots, verify maskable safe-zone.

**AND-1 · Android app.**
- `STORE_URL` is hard-coded to the emulator (`http://10.0.2.2:8000/`) → a release build would show nothing. Use `buildConfigField` per build type (debug = emulator, release = `https://sskstore.in/`).
- Rename `applicationId` and app name away from BAPS (e.g. `in.sskstore.app`).
- Restrict WebView navigation to the store's own host (open other links in the external browser) and expose `AndroidBridge` only to the trusted origin — today the footer links to Facebook/YouTube load INSIDE the WebView with the bridge exposed.
- `setAllowFileAccess(false)`, `MIXED_CONTENT_NEVER_ALLOW`, no cleartext in release (network security config), handle `wa.me`/`intent:`/`market:`/`sms:`/`geo:`, `onShowFileChooser` (admin uploads), `onReceivedHttpError`, replace deprecated `getActiveNetworkInfo()`.
- Enable R8 + `shrinkResources`, signing via git-ignored `keystore.properties`, `allowBackup=false`, bump `compileSdk/targetSdk` to what Google Play currently requires (check the current policy page).
- The README promises "UPI intent routing" but the website never emits a `upi://` link — add it (P1-5).
- In the report compare keeping the WebView app vs. a Trusted Web Activity (PWABuilder/Bubblewrap). Do not switch without my approval.

---

## 8. SELF-VERIFICATION — DO ALL OF THIS BEFORE YOU SAY "DONE"

**8.1 Static:** `php -l` on every PHP file (PHP 8.0 and the latest available); PHPStan level ≥ 5 (or a script proving no undefined functions/variables/constants); `node --check` / ESLint on JS; CSS parses with no errors and a script proves no custom property references itself or is undefined; `html-validate` / W3C on key pages; JSON files valid; no `var_dump`/`print_r`/stray `TODO`; a script scans all SQL strings for SQLite-only / MySQL-only syntax.

**8.2 Database matrix:** run everything on SQLite AND MariaDB/MySQL (Docker `mariadb:11` or XAMPP). Fresh install via migrations; upgrade from the old schema; checkout with UPI/COD/guest/logged-in; `EXPLAIN` the main catalog/search/admin queries on MySQL after adding indexes. If MySQL truly cannot be run, say so in the report and list what is unverified.

**8.3 Functional (script + browser):**
Home · catalog filters/sort/pagination/search · product · add to cart (qty, out-of-stock, max qty) · cart update/remove · coupon (valid, invalid, expired, below min order, forged subtotal, over-use) · guest checkout and logged-in checkout (stock decrement, totals, shipping by state, order row, rollback on a forced failure) · order-success (owner only) · account (profile save with each phone format, wishlist toggle, orders) · register / login / logout / password reset · contact form · PWA install / offline / update.
Admin: login, wrong-password lockout, RBAC (a staff user with limited permissions cannot open other pages BY URL), products CRUD + image upload + delete-with-orders, categories + subcategories, banners (add/edit/move up/down/toggle/delete), homepage sections, coupons, orders (status, payment, restock on cancel), customers, messages, bulk import (valid, invalid, Gujarati, ANSI-encoded CSV, ZIP of images, huge file), logout.

**8.4 Security tests** (scripts in `tests/security/`, results in the report):
- Unauthenticated request to every admin URL incl. `products.php?delete=1` → redirect to login and NO side effect.
- Every POST without / with a wrong CSRF token → 403. GET never changes state.
- Coupon forged-subtotal attack. `order-success.php?id=` of another user.
- SQLi probes on `q`, `id`, `dept`, `cat`, `sort`, CSV cells. XSS probes in search box, product name, contact form, guest email, image URL.
- Uploads: `.php`, `.php.jpg`, polyglot GIF+PHP, SVG with script, 20 MB file, ZIP slip, ZIP bomb.
- Session cookie flags; response headers.
- Direct access to `/database.sql`, `/*.sqlite`, `/.git/config`, `/includes/config.php`, `/logs/`, `/THEME.md` → 403/404.
- Brute-force lockout; generic error messages; Host-header injection; a disabled admin cannot log in or keep a session.

**8.5 Visual / responsive / accessibility** (use the browser tool, light AND dark): widths 360, 390, 768, 1024, 1280, 1440 for Home, Catalog, Product, Cart, Checkout, Account, login modal, Contact, 404, Offline and every admin page. No horizontal scroll, no overlapping floating buttons/toasts, borders/shadows/radius visible (P1-15 fixed), keyboard-only path through search → product → cart → checkout, `axe` run with zero serious/critical issues, screenshots saved in `docs/screenshots/`.

**8.6 Performance re-measure:** repeat 6.1 on the same pages. Network-panel proof that images are WebP/AVIF at the right size, lazy/eager as designed, `Cache-Control` present, no render-blocking font `@import`; SW offline fallback and update flow work.

**8.7 Review your own diff:** `git diff main...HEAD`. Look for unescaped output, missing CSRF/auth/permission, string-built SQL, new N+1, new inline JS, broken relative URLs, PHP 8.0 incompatibilities, leftover debug code.

**8.8 Second bug-hunt pass:** after all fixes, read every changed AND unchanged PHP/JS/CSS file once more looking for anything new or missed. List what is still open — honestly.

---

## 9. DELIVERABLES (all committed on the branch)

- Code fixes, migrations (`database/migrations/`), tools (`tools/migrate.php`, `create-admin.php`, `optimize-images.php`, `cron-cleanup.php`, `smoke-test.*`), tests (`tests/`).
- Docs: `docs/AUDIT_REPORT.md` (every ID with status), `docs/PERFORMANCE_REPORT.md` (before/after), `docs/TEST_REPORT.md` (what ran + results), `docs/DEPLOYMENT.md`, `docs/DECISIONS_NEEDED.md`, `docs/PROGRESS.md`, updated `README.md` and `THEME.md`, `CHANGELOG.md`.
- `docs/DEPLOYMENT.md` must give non-technical, step-by-step instructions for cPanel/FTP hosting: back up files + DB → upload → set environment variables / `config.local.php` → run migrations → create admin → run the image optimizer → set PHP version and extensions (gd with WebP, mbstring, intl, pdo_mysql, zip, fileinfo, opcache) → cron jobs → verification checklist → rollback plan. Include Apache `.htaccess` and nginx equivalents.

---

## 10. FINAL REPORT FORMAT (what you tell me at the end, in simple English)

1. One short paragraph: what was wrong, what is better now.
2. Table of every finding ID → Fixed / Partly / Not fixed (reason) / Needs my decision (+ commit hash).
3. Before/after performance table.
4. Test summary: what ran, what passed, what could not be verified and why.
5. **"What YOU must do"** list (confirm the real UPI ID, SMTP/Razorpay keys, set environment variables, enable Cloudflare, upload to hosting, run migrations, create the admin, set cron jobs).
6. Remaining risks and suggested next steps.

---

## 11. DEFINITION OF DONE

- [ ] Every ID in sections 5 and 7 has a status in the report; new findings were added.
- [ ] No known P0/P1 left open without my explicit decision.
- [ ] Checkout works end-to-end on MySQL AND SQLite; a failed order leaves no half-created data.
- [ ] No unauthenticated or CSRF-able admin action; RBAC enforced server-side.
- [ ] No default credentials or sensitive files reachable.
- [ ] Images served as right-sized WebP/AVIF with lazy/eager rules; budgets in 6.1 met (or the gap is explained).
- [ ] Mobile layouts OK at 360 px in light and dark; contrast issues fixed.
- [ ] Service worker / PWA / offline verified; Android app builds for debug and release URLs.
- [ ] All checks in section 8 executed, with evidence stored in `docs/`.
- [ ] Final report delivered.

---

## 12. OWNER DECISIONS — DEFAULTS TO USE IF I DO NOT ANSWER

- **Payments:** UPI (QR + deep link + UTR confirmation) + COD; remove Card/NetBanking; gateway adapter ready for Razorpay behind an env flag.
- **Email:** SMTP from env (PHPMailer); no credentials → log to `logs/mail.log`.
- **Clean URLs with 301 redirects:** YES.
- **EN/GU switcher for the storefront:** YES (admin stays English).
- **Android:** keep WebView, fix it, document the TWA option.
- **Cloudflare:** document only, do not depend on it.
- **Anonymous full-page cache:** only if budgets are still missed after everything else.

---

**BEGIN NOW with Phase 1.** Do not change application code until I approve the Implementation Plan.
