<?php
/**
 * SSK Store - Admin Bootstrap & Security Gateway
 * Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 *
 * MUST be required at line 1 of EVERY admin page before any POST/GET parameters are evaluated.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');

// Whitelist pages that bypass login checks
$publicAdminPages = ['login.php'];

if (!in_array($currentPage, $publicAdminPages, true)) {
    // 1. Authentication Check
    if (!is_admin_logged_in()) {
        header('Location: login.php');
        exit;
    }

    // 2. Active Account Check (Ensure deactivated admins are instantly ejected)
    $currentAdmin = current_admin();
    if (!$currentAdmin || empty($currentAdmin['is_active'])) {
        logout_admin();
        session_destroy();
        session_start();
        set_flash('error', 'Your admin account has been deactivated or is invalid. Please contact the temple superadmin.');
        header('Location: login.php');
        exit;
    }

    // 3. Session Idle Timeout Check (30 minutes = 1800 seconds)
    $idleTimeout = 1800;
    if (isset($_SESSION['admin_last_activity']) && (time() - (int)$_SESSION['admin_last_activity']) > $idleTimeout) {
        logout_admin();
        session_destroy();
        session_start();
        set_flash('error', 'Session timed out due to 30 minutes of inactivity. Please sign in again.');
        header('Location: login.php');
        exit;
    }
    $_SESSION['admin_last_activity'] = time();

    // 4. Role-Based Access Control (RBAC) Page Permission Enforcement
    $pagePermMap = [
        'index.php' => 'dashboard',
        'orders.php' => 'orders',
        'products.php' => 'products',
        'bulk_import.php' => 'products',
        'categories.php' => 'categories',
        'customers.php' => 'customers',
        'coupons.php' => 'coupons',
        'homepage_sections.php' => 'homepage_sections',
        'banners.php' => 'banners',
        'messages.php' => 'messages',
        'admin_users.php' => 'admin_users',
    ];

    if ($currentPage !== 'logout.php' && isset($pagePermMap[$currentPage])) {
        $requiredPerm = $pagePermMap[$currentPage];
        if (!admin_has_permission($requiredPerm, $currentAdmin)) {
            // Find first permitted page to redirect to
            $allPerms = get_all_admin_permissions();
            $redirectTarget = 'logout.php';
            foreach ($allPerms as $pKey => $pInfo) {
                if ($pKey !== $requiredPerm && admin_has_permission($pKey, $currentAdmin)) {
                    $redirectTarget = $pInfo['file'];
                    break;
                }
            }
            set_flash('error', 'Access Denied: You do not have permission to view that section.');
            if ($redirectTarget !== $currentPage) {
                header('Location: ' . $redirectTarget);
                exit;
            }
        }
    }

    // 5. Automatic CSRF Protection for state-changing POST requests in admin
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $currentPage !== 'logout.php') {
        require_csrf();
    }
}
