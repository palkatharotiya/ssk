<?php
/**
 * BAPS Store - Core Functions & Helpers
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/form_helpers.php';

// Flash Messages
function set_flash(string $arg1, string $arg2 = ''): void {
    if (in_array(strtolower($arg1), ['success', 'error', 'danger', 'warning', 'info'], true)) {
        $type = strtolower($arg1);
        $message = $arg2;
    } else {
        $type = in_array(strtolower($arg2), ['success', 'error', 'danger', 'warning', 'info'], true) ? strtolower($arg2) : 'info';
        $message = $arg1;
    }
    $_SESSION['flash'] = [
        'type' => $type === 'danger' ? 'error' : $type,
        'message' => $message
    ];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// CSRF Protection
function get_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(get_csrf_token()) . '">';
}

// Settings
function get_setting(string $key, string $default = ''): string {
    static $settingsCache = null;
    if ($settingsCache === null) {
        $settingsCache = [];
        try {
            $rows = db_fetch_all("SELECT setting_key, setting_value FROM settings");
            foreach ($rows as $r) {
                $settingsCache[$r['setting_key']] = $r['setting_value'];
            }
        } catch (Exception $e) {
            // fallback
        }
    }
    return $settingsCache[$key] ?? $default;
}

// Currency & Price
function format_price(float|int|string|null $amount): string {
    $num = (float)($amount ?? 0);
    return CURRENCY_SYMBOL . number_format($num, 2);
}

// Session helper for Cart Session ID
function get_session_id(): string {
    if (empty($_SESSION['cart_session_id'])) {
        $_SESSION['cart_session_id'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['cart_session_id'];
}

// Navigation Tree
function get_navigation_departments(): array {
    static $nav = null;
    if ($nav !== null) {
        return $nav;
    }

    $depts = db_fetch_all("SELECT * FROM departments WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
    $allCats = db_fetch_all("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
    $allSubcats = db_fetch_all("SELECT * FROM subcategories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");

    // Index subcats by category_id
    $subcatMap = [];
    foreach ($allSubcats as $sub) {
        $subcatMap[$sub['category_id']][] = $sub;
    }

    // Index cats by department_id
    $catMap = [];
    foreach ($allCats as $cat) {
        $cat['subcategories'] = $subcatMap[$cat['id']] ?? [];
        $catMap[$cat['department_id']][] = $cat;
    }

    foreach ($depts as &$dept) {
        $dept['categories'] = $catMap[$dept['id']] ?? [];
    }

    $nav = $depts;
    return $nav;
}

// Cart Operations
function get_cart_items(): array {
    $sessionId = get_session_id();
    $customerId = $_SESSION['customer_id'] ?? null;

    if ($customerId) {
        $sql = "SELECT c.id as cart_id, c.quantity, p.*, 
                       (CASE WHEN p.sale_price IS NOT NULL AND p.sale_price > 0 THEN p.sale_price ELSE p.price END) as unit_price
                FROM cart c
                JOIN products p ON c.product_id = p.id
                WHERE c.customer_id = ? AND p.is_active = 1
                ORDER BY c.id DESC";
        return db_fetch_all($sql, [$customerId]);
    } else {
        $sql = "SELECT c.id as cart_id, c.quantity, p.*, 
                       (CASE WHEN p.sale_price IS NOT NULL AND p.sale_price > 0 THEN p.sale_price ELSE p.price END) as unit_price
                FROM cart c
                JOIN products p ON c.product_id = p.id
                WHERE c.session_id = ? AND c.customer_id IS NULL AND p.is_active = 1
                ORDER BY c.id DESC";
        return db_fetch_all($sql, [$sessionId]);
    }
}

function get_cart_count(): int {
    $sessionId = get_session_id();
    $customerId = $_SESSION['customer_id'] ?? null;

    if ($customerId) {
        $row = db_fetch_one("SELECT SUM(quantity) as total_qty FROM cart WHERE customer_id = ?", [$customerId]);
    } else {
        $row = db_fetch_one("SELECT SUM(quantity) as total_qty FROM cart WHERE session_id = ? AND customer_id IS NULL", [$sessionId]);
    }

    return (int)($row['total_qty'] ?? 0);
}

function get_cart_subtotal(): float {
    $items = get_cart_items();
    $subtotal = 0.0;
    foreach ($items as $item) {
        $price = (float)$item['unit_price'];
        $subtotal += $price * (int)$item['quantity'];
    }
    return $subtotal;
}

function calculate_shipping(float $subtotal, string $state = 'Gujarat'): float {
    if ($subtotal <= 0) return 0.0;
    $stateNorm = strtolower(trim($state));
    $gujaratFreeLimit = (float)get_setting('free_shipping_gujarat', '500');
    $indiaFreeLimit = (float)get_setting('free_shipping_india', '750');

    if ($stateNorm === 'gujarat') {
        return ($subtotal >= $gujaratFreeLimit) ? 0.0 : 50.0;
    } else {
        return ($subtotal >= $indiaFreeLimit) ? 0.0 : 80.0;
    }
}

function add_to_cart(int $productId, int $qty = 1): array {
    $product = db_fetch_one("SELECT id, stock_qty, name, price, sale_price FROM products WHERE id = ? AND is_active = 1", [$productId]);
    if (!$product) {
        return ['success' => false, 'message' => 'Product not found or inactive.'];
    }

    if ($qty < 1) $qty = 1;
    $sessionId = get_session_id();
    $customerId = $_SESSION['customer_id'] ?? null;

    if ($customerId) {
        $existing = db_fetch_one("SELECT id, quantity FROM cart WHERE customer_id = ? AND product_id = ?", [$customerId, $productId]);
    } else {
        $existing = db_fetch_one("SELECT id, quantity FROM cart WHERE session_id = ? AND customer_id IS NULL AND product_id = ?", [$sessionId, $productId]);
    }

    if ($existing) {
        $newQty = $existing['quantity'] + $qty;
        if ($product['stock_qty'] > 0 && $newQty > $product['stock_qty']) {
            $newQty = $product['stock_qty'];
        }
        db_query("UPDATE cart SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$newQty, $existing['id']]);
    } else {
        $initialQty = min($qty, max(1, $product['stock_qty']));
        if ($customerId) {
            db_query("INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, ?)", [$customerId, $productId, $initialQty]);
        } else {
            db_query("INSERT INTO cart (session_id, product_id, quantity) VALUES (?, ?, ?)", [$sessionId, $productId, $initialQty]);
        }
    }

    return [
        'success' => true,
        'message' => 'Added "' . htmlspecialchars($product['name']) . '" to cart.',
        'cart_count' => get_cart_count(),
        'subtotal' => format_price(get_cart_subtotal())
    ];
}

function update_cart_quantity(int $productId, int $qty): array {
    $sessionId = get_session_id();
    $customerId = $_SESSION['customer_id'] ?? null;

    if ($qty <= 0) {
        return remove_from_cart($productId);
    }

    $product = db_fetch_one("SELECT id, stock_qty FROM products WHERE id = ?", [$productId]);
    if ($product && $product['stock_qty'] > 0 && $qty > $product['stock_qty']) {
        $qty = $product['stock_qty'];
    }

    if ($customerId) {
        db_query("UPDATE cart SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE customer_id = ? AND product_id = ?", [$qty, $customerId, $productId]);
    } else {
        db_query("UPDATE cart SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE session_id = ? AND customer_id IS NULL AND product_id = ?", [$qty, $sessionId, $productId]);
    }

    return [
        'success' => true,
        'cart_count' => get_cart_count(),
        'subtotal' => format_price(get_cart_subtotal())
    ];
}

function remove_from_cart(int $productId): array {
    $sessionId = get_session_id();
    $customerId = $_SESSION['customer_id'] ?? null;

    if ($customerId) {
        db_query("DELETE FROM cart WHERE customer_id = ? AND product_id = ?", [$customerId, $productId]);
    } else {
        db_query("DELETE FROM cart WHERE session_id = ? AND customer_id IS NULL AND product_id = ?", [$sessionId, $productId]);
    }

    return [
        'success' => true,
        'message' => 'Item removed from cart.',
        'cart_count' => get_cart_count(),
        'subtotal' => format_price(get_cart_subtotal())
    ];
}

function clear_cart(): void {
    $sessionId = get_session_id();
    $customerId = $_SESSION['customer_id'] ?? null;
    if ($customerId) {
        db_query("DELETE FROM cart WHERE customer_id = ?", [$customerId]);
    } else {
        db_query("DELETE FROM cart WHERE session_id = ? AND customer_id IS NULL", [$sessionId]);
    }
}

// User Authentication
function is_logged_in(): bool {
    return !empty($_SESSION['customer_id']);
}

function current_user(): ?array {
    if (!is_logged_in()) return null;
    static $user = null;
    if ($user === null) {
        $user = db_fetch_one("SELECT id, first_name, last_name, email, phone FROM customers WHERE id = ? AND is_active = 1", [$_SESSION['customer_id']]);
    }
    return $user;
}

function login_user(string $email, string $password): array {
    $email = trim(strtolower($email));
    $user = db_fetch_one("SELECT * FROM customers WHERE email = ? AND is_active = 1", [$email]);

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid email address or password.'];
    }

    session_regenerate_id(true);
    $_SESSION['customer_id'] = $user['id'];
    $_SESSION['customer_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['customer_email'] = $user['email'];

    // Merge session cart into user's DB cart
    $sessionId = get_session_id();
    $guestCart = db_fetch_all("SELECT product_id, quantity FROM cart WHERE session_id = ? AND customer_id IS NULL", [$sessionId]);
    foreach ($guestCart as $item) {
        $userItem = db_fetch_one("SELECT id, quantity FROM cart WHERE customer_id = ? AND product_id = ?", [$user['id'], $item['product_id']]);
        if ($userItem) {
            db_query("UPDATE cart SET quantity = quantity + ? WHERE id = ?", [$item['quantity'], $userItem['id']]);
        } else {
            db_query("INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, ?)", [$user['id'], $item['product_id'], $item['quantity']]);
        }
    }
    db_query("DELETE FROM cart WHERE session_id = ? AND customer_id IS NULL", [$sessionId]);

    return ['success' => true, 'message' => 'Welcome back, ' . htmlspecialchars($user['first_name']) . '!'];
}

function register_user(string $firstName, string $lastName, string $email, string $password, string $phone = ''): array {
    $email = trim(strtolower($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please provide a valid email address.'];
    }
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
    }

    $existing = db_fetch_one("SELECT id FROM customers WHERE email = ?", [$email]);
    if ($existing) {
        return ['success' => false, 'message' => 'An account with this email already exists.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    db_query("INSERT INTO customers (first_name, last_name, email, password, phone, is_active, email_verified) VALUES (?, ?, ?, ?, ?, 1, 1)", [
        trim($firstName), trim($lastName), $email, $hash, trim($phone)
    ]);
    $newId = (int)db_last_insert_id();

    // Auto login
    $_SESSION['customer_id'] = $newId;
    $_SESSION['customer_name'] = trim($firstName) . ' ' . trim($lastName);
    $_SESSION['customer_email'] = $email;

    return ['success' => true, 'message' => 'Account created successfully!'];
}

function logout_user(): void {
    unset($_SESSION['customer_id'], $_SESSION['customer_name'], $_SESSION['customer_email']);
}

// Admin Authentication
function is_admin_logged_in(): bool {
    return !empty($_SESSION['admin_user_id']);
}

function current_admin(): ?array {
    if (!is_admin_logged_in()) return null;
    return db_fetch_one("SELECT id, username, email, full_name, role, permissions FROM admin_users WHERE id = ? AND is_active = 1", [$_SESSION['admin_user_id']]);
}

function get_all_admin_permissions(): array {
    return [
        'dashboard' => ['title' => 'Dashboard & Analytics', 'icon' => '📊', 'file' => 'index.php', 'desc' => 'View sales figures, recent orders and statistics'],
        'orders' => ['title' => 'Orders & Shipping', 'icon' => '📦', 'file' => 'orders.php', 'desc' => 'View, update order status, tracking & customer shipments'],
        'products' => ['title' => 'Products & Stock', 'icon' => '📚', 'file' => 'products.php', 'desc' => 'Add, edit, manage product inventory and pricing'],
        'categories' => ['title' => 'Departments & Categories', 'icon' => '🏷️', 'file' => 'categories.php', 'desc' => 'Manage store departments, subcategories and sort order'],
        'customers' => ['title' => 'Customers', 'icon' => '👥', 'file' => 'customers.php', 'desc' => 'View customer registrations and accounts'],
        'coupons' => ['title' => 'Coupons & Promo', 'icon' => '🎟️', 'file' => 'coupons.php', 'desc' => 'Create and configure promo discount codes'],
        'homepage_sections' => ['title' => 'Homepage Layout Sections', 'icon' => '✨', 'file' => 'homepage_sections.php', 'desc' => 'Manage display order of Festive, New, Top Selling & Offer sections'],
        'banners' => ['title' => 'Hero Slideshow', 'icon' => '🖼️', 'file' => 'banners.php', 'desc' => 'Add, edit, reorder hero banner slides on homepage'],
        'messages' => ['title' => 'Inquiries & Contact', 'icon' => '✉️', 'file' => 'messages.php', 'desc' => 'View contact form messages sent by devotees'],
        'admin_users' => ['title' => 'Admin Roles & Permissions', 'icon' => '🛡️', 'file' => 'admin_users.php', 'desc' => 'Manage admin accounts and assign granular page access'],
    ];
}

function admin_has_permission(string $permKey, ?array $adminUser = null): bool {
    if (!$adminUser) {
        $adminUser = current_admin();
    }
    if (!$adminUser) return false;
    
    // Superadmin has unrestricted master access to everything
    if (($adminUser['role'] ?? '') === 'super_admin') {
        return true;
    }

    $perms = $adminUser['permissions'] ?? '[]';
    if (empty($perms)) {
        return false;
    }
    if (is_string($perms)) {
        $decoded = json_decode($perms, true);
        $perms = is_array($decoded) ? $decoded : explode(',', $perms);
    }
    if (!is_array($perms)) {
        return false;
    }
    if (in_array('*', $perms, true)) {
        return true;
    }
    return in_array($permKey, $perms, true);
}

function login_admin(string $username, string $password): array {
    $username = trim($username);
    $admin = db_fetch_one("SELECT * FROM admin_users WHERE username = ? OR email = ?", [$username, $username]);
    if (!$admin || !password_verify($password, $admin['password'])) {
        return ['success' => false, 'message' => 'Invalid admin username or password.'];
    }

    if (empty($admin['is_active'])) {
        return ['success' => false, 'message' => 'This administrator account has been deactivated. Please contact the temple superadmin.'];
    }

    session_regenerate_id(true);
    $_SESSION['admin_user_id'] = (int)$admin['id'];
    $_SESSION['admin_name'] = $admin['full_name'];
    $_SESSION['admin_role'] = $admin['role'];
    $_SESSION['admin_last_activity'] = time();
    db_query("UPDATE admin_users SET last_login = CURRENT_TIMESTAMP WHERE id = ?", [$admin['id']]);

    return ['success' => true, 'message' => 'Login successful.'];
}

function logout_admin(): void {
    unset(
        $_SESSION['admin_user_id'],
        $_SESSION['admin_name'],
        $_SESSION['admin_role'],
        $_SESSION['admin_last_activity']
    );
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

// Wishlist
function toggle_wishlist(int $productId): array {
    if (!is_logged_in()) {
        return ['success' => false, 'require_login' => true, 'message' => 'Please login to add items to your wishlist.'];
    }
    $customerId = (int)$_SESSION['customer_id'];
    $exists = db_fetch_one("SELECT id FROM wishlist WHERE customer_id = ? AND product_id = ?", [$customerId, $productId]);
    if ($exists) {
        db_query("DELETE FROM wishlist WHERE id = ?", [$exists['id']]);
        return ['success' => true, 'action' => 'removed', 'message' => 'Removed from wishlist.', 'count' => get_wishlist_count()];
    } else {
        db_query("INSERT INTO wishlist (customer_id, product_id) VALUES (?, ?)", [$customerId, $productId]);
        return ['success' => true, 'action' => 'added', 'message' => 'Added to wishlist!', 'count' => get_wishlist_count()];
    }
}

function is_in_wishlist(int $productId): bool {
    if (!is_logged_in()) return false;
    $row = db_fetch_one("SELECT id FROM wishlist WHERE customer_id = ? AND product_id = ?", [$_SESSION['customer_id'], $productId]);
    return !empty($row);
}

function get_wishlist_count(): int {
    if (!is_logged_in()) return 0;
    $row = db_fetch_one("SELECT COUNT(*) as count FROM wishlist WHERE customer_id = ?", [$_SESSION['customer_id']]);
    return (int)($row['count'] ?? 0);
}

// Image Placeholder Resolver
function get_product_image_url(?string $imageFilename, string $productName = ''): string {
    if (!empty($imageFilename)) {
        // If external URL
        if (filter_var($imageFilename, FILTER_VALIDATE_URL)) {
            return $imageFilename;
        }

        // Uploads folder
        $uploadPath = ROOT_PATH . 'uploads/products/' . $imageFilename;
        if (file_exists($uploadPath)) {
            return SITE_URL . '/uploads/products/' . $imageFilename;
        }

        // Products assets folder
        $localPath = ROOT_PATH . 'assets/images/products/' . $imageFilename;
        if (file_exists($localPath)) {
            return ASSETS_URL . '/images/products/' . $imageFilename;
        }

        // Main assets images folder
        $rootAssets = ROOT_PATH . 'assets/images/' . $imageFilename;
        if (file_exists($rootAssets)) {
            return ASSETS_URL . '/images/' . $imageFilename;
        }
    }
    // Dynamic SVG placeholder
    $nameEncoded = urlencode($productName ?: 'SSK Store');
    return ASSETS_URL . '/images/placeholder.php?title=' . $nameEncoded;
}
