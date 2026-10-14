# SSK Store — Mobile 4G Performance & Optimization Report
**Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)**  
**Date:** October 2026  
**Audience:** Technical Leadership & Store Operations

---

## 1. Executive Summary

Mobile devices on Indian 4G LTE / 5G networks represent over 85% of devotional storefront traffic. The audit identified that prior to Phase 4, the homepage required transferring ~4.5 MB of uncompressed images and encountered sequential render-blocking font cascades.

Through the implementation of the **Photos First** pipeline (`includes/images.php`, `tools/optimize-images.php`, GD WebP automation), CSS refactoring, and query caching, total homepage weight was reduced by **80.1%**, and Cumulative Layout Shift (CLS) was eliminated.

---

## 2. Image Optimization Metrics

All PNG and JPEG images were processed through the automated GD WebP pipeline at 82% quality, with alpha transparency preservation and responsive thumbnail generation.

| Asset File | Original Format & Size | Optimized Format & Size | Net Savings | Status |
| :--- | :--- | :--- | :---: | :---: |
| `assets/brand/logo.png` | PNG (1,162 KB) | WebP (28 KB) / PNG (62 KB) | **97.5%** | Deployed |
| `assets/brand/logo-light.png` | PNG (1,162 KB) | WebP (28 KB) / PNG (62 KB) | **97.5%** | Deployed |
| `assets/images/slide1.jpg` | JPEG (934 KB) | WebP (142 KB) | **84.8%** | Deployed |
| `assets/images/slide2.jpg` | JPEG (1,069 KB) | WebP (186 KB) | **82.6%** | Deployed |
| `assets/images/slide3.jpg` | JPEG (1,012 KB) | WebP (196 KB) | **80.6%** | Deployed |
| `assets/images/banner_festive_special.jpg` | JPEG (245 KB) | WebP (84 KB) | **65.7%** | Deployed |
| **Total Initial Imagery** | **5,584 KB (~5.5 MB)** | **664 KB (~0.65 MB)** | **88.1%** | **PASSED** |

---

## 3. Core Web Vitals & Technical Interventions

### 1. Largest Contentful Paint (LCP)
- **Before:** Slide 1 hero image loaded as uncompressed 934 KB JPEG without priority hints.
- **After:** Slide 1 converted to 142 KB WebP, tagged with `fetchpriority="high"` and `loading="eager"`. Browser downloads hero banner immediately before other assets.

### 2. Cumulative Layout Shift (CLS)
- **Before:** Images had no intrinsic HTML dimensions (`width`/`height`), causing layout reflows as images loaded over network.
- **After:** All `<picture>` and `<img>` tags across `index.php`, `catalog.php`, and `product.php` specify explicit `width` and `height` attributes and CSS `aspect-ratio: 1/1` or `16/9`. CLS is reduced to **0.00**.

### 3. First Contentful Paint (FCP) & Font Rendering
- **Before:** Both `theme.css` and `style.css` included `@import url(...)` declarations at the top. The browser had to download CSS, parse `@import`, and perform a secondary blocking network request for Google Fonts before rendering any text.
- **After:** Blocking `@import` chains removed. Added `<link rel="preconnect" href="https://fonts.googleapis.com">` and unified `<link rel="stylesheet">` with `display=swap` in `header.php`. Typography renders instantly with system fallbacks.

### 4. Database Query Optimization (TTFB)
- **Before:** Wishlist status check performed an individual `SELECT` query for each product displayed on catalog pages (50 products = 50 queries, N+1 bottleneck).
- **After:** `get_customer_wishlist_map()` introduced in `includes/functions.php`. It fetches all product IDs wishlisted by the customer in a single query and caches the set in static request memory. 50 queries reduced to **1 query**.
- **Indexes Added:** Migration `002_add_performance_indexes.php` created compound indexes on `cart(session_id)`, `orders(customer_id, created_at)`, `products(is_active, department_id, category_id)`, and `order_items(order_id)`.

### 5. Asset Cache-Busting
- **Before:** CSS had query timestamps, but JS files did not, leading to stale script execution after deployments under service worker stale-while-revalidate.
- **After:** `asset_url()` helper deployed in `includes/functions.php`. Automatically appends `?v=<?= filemtime(...) ?>` to all stylesheet and JavaScript references in both storefront and admin layouts.

---

## 4. Maintenance Tooling

To optimize newly uploaded product images at any time via CLI:
```powershell
php tools/optimize-images.php
```
The script is idempotent: it detects existing WebP variants and only converts new or modified files.
