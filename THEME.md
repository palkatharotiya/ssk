# SSK Store — Unified Design System & PWA Architecture Guide
**Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)**

---

## 1. Executive Summary

This architecture establishes a single source of truth (`assets/css/theme.css`) shared by both the **Customer Storefront** and the **Admin Portal** (`/admin`). It delivers:
1. Strict adherence to SSK brand colors (Navy `#011760` & Crimson Red `#E70207`).
2. Full Light & Dark modes with zero flash of unstyled theme (Zero-FOUC).
3. Bilingual English and Gujarati typography with conjunct and vowel mark (matra) integrity.
4. Mobile-first app-like experience (<768px) and desktop layouts (>=1024px, max width 1280px).
5. Progressive Web App (PWA) installability with offline fallback, versioned service worker, and dual-platform install UX (Chrome/Edge/desktop & iOS Safari).
6. WCAG 2.1 AA accessibility contrast compliance (>= 4.5:1 body, >= 3:1 UI elements/icons).

---

## 2. Brand Identity & Design Tokens

### 2.1 Primary Brand Palette (Derived from the Official Logo)
* **Navy (`#011760`)**: Primary structure, headings, admin sidebar, announcement bar, navigation, and footer.
* **Red (`#E70207`)**: Accent color, primary call-to-actions (CTAs), badges, active tabs, and interactive highlights.
* **Navy-2 (`#0B2A9E`)**: Secondary brand link and gradient transition tone.
* **Gold / Amber (`#F59E0B`)**: Devotional accents, notice card borders, sacred icons, and ratings.

### 2.2 Complete Design Token Registry (`assets/css/theme.css`)

| Token | Light Mode (`:root`) | Dark Mode (`[data-theme="dark"]`) | Purpose / Usage |
| :--- | :--- | :--- | :--- |
| `--bg` | `#F6F7FB` | `#060B1F` | Viewport and page background |
| `--surface` | `#FFFFFF` | `#0D1533` | Card backgrounds, modals, inputs, dropdowns |
| `--surface-2` | `#EEF1F8` | `#141E45` | Table headers, secondary card panels, inset containers |
| `--border` | `#E2E6F0` | `#243061` | Dividers, card borders, input borders |
| `--text` | `#0B1437` | `#F2F4FF` | Default body copy (Contrast >= 11:1) |
| `--text-muted` | `#4A5578` | `#AAB4DA` | Secondary labels, SKU, timestamps (Contrast >= 4.6:1) |
| `--heading` | `#011760` | `#FFFFFF` | Page titles, card headings, modal titles |
| `--link` | `#0B2A9E` | `#9DB2FF` | Interactive links and navigation text |
| `--navy` | `#011760` | `#011760` | Structural brand navy |
| `--navy-2` | `#0B2A9E` | `#0B2A9E` | Secondary brand navy |
| `--red` | `#E70207` | `#E70207` | Primary brand crimson red |
| `--red-hover` | `#C40005` | `#C40005` | Primary CTA hover state |
| `--red-text` | `#C40005` | `#FF5A5F` | Red text on standard light/dark surfaces (Contrast >= 4.5:1) |
| `--grad-hero` | `linear-gradient(120deg, #9B111E 0%, #B91C1C 55%, #C2410C 100%)` | `linear-gradient(120deg, #6E0C14 0%, #8F1318 55%, #9A3412 100%)` | Admin hero banner, storefront promotional banners |
| `--grad-navy` | `linear-gradient(135deg, #011760 0%, #0B2A9E 100%)` | `linear-gradient(135deg, #02103F 0%, #0A1F7A 100%)` | Admin sidebar, announcement bar, footer |
| `--grad-cta` | `linear-gradient(135deg, #E70207 0%, #B80005 100%)` | `linear-gradient(135deg, #E70207 0%, #B80005 100%)` | Primary button background |
| `--success` | `#166534` | `#4ADE80` | Success badges, completed orders, in-stock indicator |
| `--warning` | `#92400E` | `#FBBF24` | Warning messages, low stock alerts |
| `--info` | `#1D4ED8` | `#7AA2FF` | Informational callouts, active filters |
| `--danger` | `#B91C1C` | `#FF6B6F` | Error alerts, delete actions, cancelled status |
| `--notice-bg` | `#FFF8E1` | `rgba(245, 158, 11, 0.12)` | Notice / attention banner container background |
| `--notice-border` | `#F59E0B` | `#F59E0B` | Left stripe and border for notices |
| `--notice-title` | `#92400E` | `#FCD34D` | Heading inside notice banners |
| `--notice-text` | `#78350F` | `#F3D9A6` | Body text inside notice banners |
| `--focus` | `#1D4ED8` | `#9DB2FF` | 3px keyboard outline for accessible focus |
| `--shadow` | `0 6px 20px rgba(1, 23, 96, 0.08)` | `0 6px 24px rgba(0, 0, 0, 0.45)` | Elevation shadow for cards and modals |

### 2.3 Stat Cards Palette (Admin Portal)
* **Revenue (Green)**: `#10B981` with `rgba(16, 185, 129, 0.14)` tile background.
* **Orders (Blue)**: `#3B82F6` with `rgba(59, 130, 246, 0.14)` tile background.
* **Products (Pink)**: `#EC4899` with `rgba(236, 72, 153, 0.14)` tile background.
* **Customers (Amber)**: `#F59E0B` with `rgba(245, 158, 11, 0.14)` tile background.

---

## 3. Typography & Multi-Script (English + Gujarati)

### 3.1 Font Stack
```css
--font-sans: 'Plus Jakarta Sans', 'Noto Sans Gujarati', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
--font-gu: 'Noto Sans Gujarati', var(--font-sans);
```
Both fonts are loaded via Google Fonts with `display=swap`.

### 3.2 Rules for Gujarati Content (`:lang(gu)`)
```css
:lang(gu) {
  font-family: var(--font-gu);
  line-height: 1.75;
  letter-spacing: 0 !important;
  text-transform: none !important;
}
```
* **Conjunct & Ligature Safety**: `letter-spacing` is forced to `0` because positive letter-spacing breaks Gujarati complex conjuncts (e.g. "સ્વામિનારાયણ", "કેન્દ્ર").
* **No Text Transform**: Uppercase transform (`text-transform: uppercase`) is English-only and disabled on Gujarati.
* **Vowel Mark (Matra) Clipping Prevention**: Vowel marks (િ, ી, ુ, ૂ, ે, ૈ, ો, ૌ, ં, ઃ) extend above and below the baseline. All buttons, tabs, table cells, and cards use flexible min-heights (`min-height: 44px`), flexible line wrapping, and allow `overflow: visible` so no matras are ever clipped.
* **Test Verification Strings**:
  - "જય સ્વામિનારાયણ" (Jay Swaminarayan)
  - "શ્રી સહજાનંદ સાહિત્ય કેન્દ્ર" (Shri Sahajanand Sahitya Kendra)
  - "કલાકુંજ મંદિર, સુરત" (Kalakunj Mandir, Surat)

---

## 4. Brand Logo Assets & Usage Rules

### 4.1 Asset Registry
All brand logos and PWA icons are organized in dedicated asset folders:
* `/assets/brand/logo.png` — Full primary transparent PNG (`1922x713`).
* `/assets/brand/logo-light.png` — Light variant for deep dark backgrounds (`#F2F4FF` lettering with red retained).
* `/assets/brand/logo-mark.png` — Centered sacred "श्री" glyph with mala beads on a pure white square canvas.
* `/assets/brand/favicon.ico` — Multi-size root favicon (`16x16`, `32x32`, `48x48`).
* `/assets/icons/icon-192.png` — 192x192 PWA standalone app icon.
* `/assets/icons/icon-512.png` — 512x512 PWA standalone app icon.
* `/assets/icons/icon-512-maskable.png` — 512x512 maskable icon with artwork in the central 80% safe zone.
* `/assets/icons/apple-touch-icon.png` — 180x180 iOS home screen icon.
* `/assets/icons/favicon-32.png` & `/assets/icons/favicon-16.png` — High-DPI browser tab icons.

### 4.2 Display Rules & Dimensions
* **Storefront Header**: 56px height on desktop, 40px on mobile (`.brand-logo-img`), loaded eagerly with `fetchpriority="high"`.
* **Footer**: Placed on `.footer-logo-plate` (white rounded plate, `padding: 8px 14px`, `border-radius: 12px`), max-height 64px.
* **Admin Sidebar**: Placed on `.sidebar-brand-box` inside a white rounded plate (`padding: 5px 12px`, `border-radius: 10px`), ensuring high contrast against the `--grad-navy` sidebar in both themes.
* **Admin Mobile Topbar**: Shows `/assets/brand/logo-mark.png` (30x30px) immediately to the left of the page title on screens `< 768px`.
* **Admin Login**: Placed on a white rounded plate with shadow above the login card.
* **Offline Page**: Prominently centered on a white rounded plate above the offline status banner.

---

## 5. Responsive Layout Architecture

### 5.1 Breakpoints
* **Phone / Mobile**: `< 768px` (Tested at 360px & 390px)
* **Tablet**: `768px – 1023px`
* **Desktop**: `1024px – 1439px` (Tested at 1280px)
* **Wide Desktop**: `>= 1440px` (Max content container width: 1280px)

### 5.2 Mobile-First App Experience (<768px)
* **Fixed Bottom Tab Bar**: 56px height + `env(safe-area-inset-bottom)`. Features 5 core tabs:
  1. **Home** (`index.php`)
  2. **Categories** (`catalog.php`)
  3. **Cart** (`cart.php`) with real-time numeric counter badge
  4. **Orders** (`account.php#orders`)
  5. **Account** (`account.php` / login modal)
* **Mobile Sticky Add to Cart Bar**: On `product.php`, a fixed bar sits at `bottom: calc(56px + env(safe-area-inset-bottom))` displaying product title, live price, and a full-size "Add to Cart" button.
* **Drawer Admin Navigation**: Admin sidebar smoothly transforms into a slide-out drawer with a dark backdrop, triggered by the topbar hamburger button.
* **Touch Targets & Form Safety**:
  - Interactive elements have minimum `44px` touch targets.
  - Form inputs, selects, and textareas have `font-size: 16px !important` on mobile to prevent iOS Safari auto-zooming.
  - Safe-area insets (`env(safe-area-inset-*)`) and `100dvh` are strictly respected.

---

## 6. Progressive Web App (PWA) Architecture

### 6.1 Web App Manifest (`manifest.webmanifest`)
Linked on every storefront and admin page:
```html
<link rel="manifest" href="manifest.webmanifest">
```
* **Application Name**: "Shri Sahajanand Sahitya Kendra"
* **Short Name**: "SSK Store"
* **Display Mode**: `standalone`
* **Theme Color**: `#011760` (Light) / `#060B1F` (Dark)
* **Background Color**: `#F6F7FB`
* **Start URL**: `/?source=pwa`
* **App Shortcuts**: Quick links to Catalog, Cart, and My Orders.

### 6.2 Service Worker Strategy (`sw.js`)
* **App Shell Pre-caching**: Precaches `offline.html`, CSS tokens, core JS engines, brand logos, and icons.
* **Static Assets**: Stale-while-revalidate for styles and scripts.
* **Images**: Cache-first with a strict 60-item LRU quota.
* **Navigations**: Network-first with graceful fallback to `/offline.html` when offline.
* **Strict Cache Exclusion Policy**:
  - Never intercepts or caches `/admin/*`, `/cart.php`, `/checkout.php`, `/order-success.php`, `/account.php`, `/api/*`, or auth requests.
  - Never caches non-GET requests or queries with session, csrf, or token parameters.
  - Guarantees fresh real-time pricing, stock quantities, and customer privacy.

### 6.3 Dedicated Offline Page (`/offline.html`)
* Fully styled with design tokens for Light and Dark modes.
* Bilingual messaging in English and Gujarati ("તમે ઑફલાઇન છો").
* One-click "Try Again • ફરી પ્રયાસ કરો" reload action.

### 6.4 Install UX & Dual-Platform Support (`pwa-install.js`)
* **Centralized Strings (`assets/js/pwa-strings.js`)**: All user-facing strings are defined in a single dictionary for English and Gujarati.
* **Android / Chrome / Desktop**: Captures `beforeinstallprompt`, displays an "Install App" button in the header, and displays a dismissible bottom banner on mobile with the SSK logo.
* **iOS Safari**: Displays an intuitive bottom sheet illustrating the 3-step action: Share → Add to Home Screen → Add.
* **Standalone Suppression**: Completely suppresses install prompts when already installed or running inside standalone mode.
* **14-Day Dismissal Cooldown**: Dismissed banners stay hidden for 14 days in `localStorage`.
* **Update Notification**: Automatically shows a toast when a new Service Worker is ready, prompting a one-click refresh.

---

## 8. Phase 2 — Form Field System (`assets/css/theme.css`)

### 8.1 Dedicated Form Tokens
To eliminate low-contrast form borders and unattached labels, Phase 2 established dedicated, global tokens:

| Token | Light Mode (`:root`) | Dark Mode (`[data-theme="dark"]`) | Purpose / Contrast |
| :--- | :--- | :--- | :--- |
| `--input-bg` | `#FFFFFF` | `#081028` | Crisp input background distinct from page & card |
| `--input-border` | `#7C88AD` | `#5B6B9E` | High-visibility field border (**>= 3:1 contrast**) |
| `--input-border-hover` | `#4A5578` | `#8C9AC8` | Interactive hover state border |
| `--input-placeholder` | `#5F6B8F` | `#8E9BC4` | Legible hint placeholder |
| `--input-disabled-bg` | `#EEF1F8` | `#141E45` | Distinct disabled / read-only background |
| `--card-border` | `#D8DEEC` | `#2A3768` | Subtle card outline distinguishing cards from `--bg` |
| `--wa-text` | `#166534` | `#4ADE80` | High-contrast forest green / bright emerald text |

### 8.2 Strict Field Anatomy
Every field is constructed as a cohesive unit (`.form-group` / `.admin-form-group`):
* **Label**: Always placed strictly **ABOVE** the input box. `14px`, `font-weight: 600`, color `var(--text)`, with a fixed `6px` bottom margin. Required fields display a red asterisk (`.form-label-required` with `var(--red-text)`). Optional fields append `(optional)`.
* **Input Box (`.form-input`, `.form-select`, `.form-textarea`)**:
  * Minimum height `48px` (touch-friendly, WCAG 2.5.5).
  * `16px` base font size (prevents automatic iOS Safari zoom-in on focus).
  * `padding: 12px 14px`.
  * `border: 1.5px solid var(--input-border)`.
  * `border-radius: 10px`.
  * **Focus**: `border-color: var(--link)` with a `3px` outline (`outline: 3px solid var(--focus); outline-offset: 2px`).
  * **Error**: `2px solid var(--danger)` with an immediate error message rendered below.
* **Vertical Spacing Scale**:
  * Label-to-box gap: **6px**.
  * Field-to-field vertical gap: **20px** (guaranteeing that label-to-box gap is much smaller than field-to-field gap).
  * Subgroup-to-subgroup gap: **28px – 32px**.
  * Card interior padding: **24px – 32px desktop** / **16px – 20px mobile**.

### 8.3 Special Input Controls
* **Phone Inputs (`.input-phone-wrap`)**: Features a fixed `+91` national prefix chip embedded in the bordered box without cluttering user input.
* **Pincode Inputs (`.input-pin`)**: Formatted for 6 numeric digits with `inputmode="numeric"`.
* **Select Dropdowns (`.form-select`)**: Standard 48px box styled with custom SVG chevron and consistent focus ring.
* **Password Fields (`.password-wrap`)**: Includes an integrated show/hide toggle eye button.
* **Quantity Steppers (`.qty-stepper`)**: Touch-accessible 44px `+` and `−` buttons with numeric input.

---

## 9. Page Anatomy & Layout Hierarchy

### 9.1 Header, Breadcrumbs & Headings
* Every page starts with a semantic breadcrumb (`.ui-breadcrumb`) generated via `render_breadcrumb()`.
* Single semantic `<h1>` (24px mobile / 32px desktop) followed by a 1-line helper explanation.
* Content is grouped into titled cards (`.ui-card` / `.admin-card`).

### 9.2 Two-Column Commerce Grid (`.ui-two-col-layout`)
* **Primary Column**: Max-width `760px` for smooth form completion.
* **Secondary Column (`.ui-sticky-side`)**: `340px – 400px` sticky sidebar below header on desktop.
* **Mobile Experience**: Sidebar collapses into a top summary drawer and a sticky bottom checkout bar.

---

## 10. Shared PHP Helper Library (`includes/form_helpers.php`)

All form fields across storefront and admin use centralized render helpers:
1. `render_form_field(array $config)`: Generates complete input, label, hint, error, and prefix chips with proper ARIA attributes.
2. `render_card_start(string $title, string $desc, ?string $badge)`: Renders titled UI card containers.
3. `render_card_end()`: Closes card containers.
4. `render_breadcrumb(array $crumbs)`: Emits accessible, SEO-ready breadcrumb trails.
5. `render_error_summary(array $errors)`: Generates accessible error summaries linking to invalid fields on submit.

---

## 11. Floating UI & WhatsApp Rules

* **WhatsApp Floating Button**: Converted to a clean, icon-only 48px circle with an SVG icon and `aria-label="Chat on WhatsApp"`.
* **Collision Avoidance**:
  * Positioned at `bottom: calc(16px + env(safe-area-inset-bottom))`.
  * Lifted above mobile bottom navigation bars.
  * Completely suppressed on `checkout.php` to prevent obscuring payment options.
  * Main content container includes bottom padding (`padding-bottom: 5rem`) ensuring no floating element obscures inputs or submit buttons.

---

## 12. Acceptance Checklist Status

- [x] Full Light and Dark mode across storefront and `/admin` portal.
- [x] Zero flash of unstyled theme (FOUC) via head theme initializer.
- [x] Brand colors exact: Navy `#011760` & Crimson Red `#E70207`.
- [x] Text contrast >= 4.5:1 body, >= 3:1 UI in both themes.
- [x] Gujarati and English typography rules with zero broken ligatures or clipped matras.
- [x] Form input boxes clearly visible with >= 3:1 border contrast in both themes.
- [x] Every form label attached strictly 6px above its field.
- [x] Floating WhatsApp button never obscures checkout or form controls.
- [x] Header search box styled as high-contrast pill with visible boundaries.
- [x] Header WhatsApp text passes high-contrast color standards.
- [x] Cards clearly stand out from `--bg` with `--card-border` and elevation shadows.
- [x] Shared form partials implemented (`includes/form_helpers.php`).
- [x] Checkout works end-to-end on mobile & desktop in English and Gujarati.
- [x] Pre-publish audit pack delivered (`PRE-PUBLISH-REPORT.md`, `PUBLISH.md`, `BACKUP.md`, `OWNER-TODO.md`).
- [x] Verified at 360px, 390px, 768px, 1024px, 1280px, and 1440px.

