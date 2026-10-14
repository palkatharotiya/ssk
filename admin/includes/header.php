<?php
/**
 * SSK Store - Admin Header & Layout with Role-Based Access Control (RBAC)
 */

// Start output buffering to prevent headers already sent errors
if (!ob_get_level()) {
    ob_start();
}

require_once __DIR__ . '/admin_bootstrap.php';

$admin = $currentAdmin ?? current_admin();
$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title><?= $adminPageTitle ?? 'Admin Dashboard' ?> — SSK Store Portal (Kalakunj Mandir)</title>

  <!-- PWA & Mobile Web App Manifest & Meta Tags -->
  <link rel="manifest" href="../manifest.webmanifest">
  <link rel="apple-touch-icon" sizes="180x180" href="../assets/icons/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="../assets/icons/favicon-32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="../assets/icons/favicon-16.png">
  <link rel="shortcut icon" href="../assets/brand/favicon.ico">
  <meta name="theme-color" media="(prefers-color-scheme: light)" content="#011760">
  <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#060B1F">
  <!-- Immediate Theme Initializer (Prevents Theme Flash Before Render) -->
  <script>
    (function() {
      try {
        var saved = localStorage.getItem('ssk-theme');
        var theme = saved || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        if (theme === 'system') {
          theme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        }
        document.documentElement.setAttribute('data-theme', theme);
        document.documentElement.style.colorScheme = theme;
      } catch (e) {}
    })();
  </script>

  <link rel="stylesheet" href="../assets/css/theme.css?v=<?= file_exists(ROOT_PATH . 'assets/css/theme.css') ? filemtime(ROOT_PATH . 'assets/css/theme.css') : 1 ?>">
  <link rel="stylesheet" href="css/admin.css?v=<?= file_exists(__DIR__ . '/../css/admin.css') ? filemtime(__DIR__ . '/../css/admin.css') : 1 ?>">
</head>
<body>

  <!-- Admin Sidebar -->
  <aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
      <a href="index.php" class="sidebar-brand-box" title="SSK Store Portal (Kalakunj Mandir)">
        <div style="background: #ffffff; padding: 5px 12px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
          <img src="../assets/brand/logo.png" alt="SSK Portal Logo" style="height: 36px; width: auto; max-width: 175px; object-fit: contain; display: block;">
        </div>
      </a>
      <button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close side panel" title="Close Side Panel">&times;</button>
    </div>

    <nav class="sidebar-nav">
      <?php if (admin_has_permission('dashboard', $admin)): ?>
        <a href="index.php" class="sidebar-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
          <span>📊</span> Dashboard
        </a>
      <?php endif; ?>

      <?php if (admin_has_permission('orders', $admin)): ?>
        <a href="orders.php" class="sidebar-link <?= $currentPage === 'orders.php' ? 'active' : '' ?>">
          <span>📦</span> Orders & Shipping
        </a>
      <?php endif; ?>

      <?php if (admin_has_permission('products', $admin)): ?>
        <a href="products.php" class="sidebar-link <?= $currentPage === 'products.php' ? 'active' : '' ?>">
          <span>📚</span> Products & Stock
        </a>
        <a href="bulk_import.php" class="sidebar-link <?= $currentPage === 'bulk_import.php' ? 'active' : '' ?>" style="padding-left: 2rem; font-size: 0.8125rem;">
          <span>📥</span> Bulk CSV Import
        </a>
      <?php endif; ?>

      <?php if (admin_has_permission('categories', $admin)): ?>
        <a href="categories.php" class="sidebar-link <?= $currentPage === 'categories.php' ? 'active' : '' ?>">
          <span>🏷️</span> Departments & Categories
        </a>
      <?php endif; ?>

      <?php if (admin_has_permission('customers', $admin)): ?>
        <a href="customers.php" class="sidebar-link <?= $currentPage === 'customers.php' ? 'active' : '' ?>">
          <span>👥</span> Customers
        </a>
      <?php endif; ?>

      <?php if (admin_has_permission('coupons', $admin)): ?>
        <a href="coupons.php" class="sidebar-link <?= $currentPage === 'coupons.php' ? 'active' : '' ?>">
          <span>🎟️</span> Coupons & Promo
        </a>
      <?php endif; ?>

      <?php if (admin_has_permission('homepage_sections', $admin)): ?>
        <a href="homepage_sections.php" class="sidebar-link <?= $currentPage === 'homepage_sections.php' ? 'active' : '' ?>">
          <span>✨</span> Homepage Layout
        </a>
      <?php endif; ?>

      <?php if (admin_has_permission('banners', $admin)): ?>
        <a href="banners.php" class="sidebar-link <?= $currentPage === 'banners.php' ? 'active' : '' ?>">
          <span>🖼️</span> Hero Slideshow
        </a>
      <?php endif; ?>

      <?php if (admin_has_permission('messages', $admin)): ?>
        <a href="messages.php" class="sidebar-link <?= $currentPage === 'messages.php' ? 'active' : '' ?>">
          <span>✉️</span> Inquiries & Contact
        </a>
      <?php endif; ?>

      <?php if (admin_has_permission('admin_users', $admin)): ?>
        <a href="admin_users.php" class="sidebar-link <?= $currentPage === 'admin_users.php' ? 'active' : '' ?>" style="border-left-color: #8b5cf6;">
          <span>🛡️</span> Roles & Permissions
        </a>
      <?php endif; ?>

      <a href="../index.php" target="_blank" class="sidebar-link" style="margin-top: auto;">
        <span>🌐</span> View Live Website ↗
      </a>
      <form id="adminLogoutForm" method="POST" action="logout.php" style="display: none;">
        <?= csrf_field() ?>
      </form>
      <a href="logout.php" onclick="event.preventDefault(); document.getElementById('adminLogoutForm').submit();" class="sidebar-link" style="color: #ef4444; cursor: pointer;">
        <span>🚪</span> Sign Out
      </a>
    </nav>

    <div class="sidebar-footer">
      <div style="font-size: 0.8125rem;">Signed in as:</div>
      <div style="font-weight: 700; color: #fff; margin-top: 0.15rem;"><?= htmlspecialchars($admin['full_name'] ?? 'Admin') ?></div>
      <div style="margin-top: 0.35rem;">
        <?php if (($admin['role'] ?? '') === 'super_admin'): ?>
          <span style="display: inline-block; font-size: 0.6875rem; font-weight: 700; padding: 0.15rem 0.55rem; border-radius: 999px; background: rgba(226, 177, 67, 0.2); color: #e2b143; border: 1px solid rgba(226, 177, 67, 0.4);">
            👑 Super Admin
          </span>
        <?php else: ?>
          <span style="display: inline-block; font-size: 0.6875rem; font-weight: 700; padding: 0.15rem 0.55rem; border-radius: 999px; background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.4);">
            👤 <?= ucfirst(htmlspecialchars($admin['role'] ?? 'Staff')) ?>
          </span>
        <?php endif; ?>
      </div>
    </div>
  </aside>

  <!-- Mobile Drawer Backdrop -->
  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

  <!-- Admin Main -->
  <main class="admin-main">
    
    <!-- Topbar -->
    <header class="admin-topbar">
      <div style="display: flex; align-items: center; gap: 0.75rem;">
        <button type="button" id="sidebarToggleBtn" class="sidebar-toggle-btn" aria-label="Toggle Side Panel" title="Click to Open/Close Side Panel (Ctrl+B)">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
          </svg>
        </button>
        <a href="index.php" class="admin-mobile-topbar-logo" title="SSK Store" aria-label="SSK Store Portal">
          <img src="../assets/brand/logo-mark.png" alt="SSK" width="30" height="30" style="height: 30px; width: 30px; border-radius: 6px; object-fit: contain; background: #FFFFFF; padding: 2px; box-shadow: 0 1px 4px rgba(0,0,0,0.2);">
        </a>
        <div style="font-size: 1.15rem; font-weight: 700; color: var(--admin-text);">
          <?= $adminPageTitle ?? 'Dashboard' ?>
        </div>
      </div>
      <div style="display: flex; align-items: center; gap: 0.75rem;">
        <span style="font-size: 0.8125rem; color: var(--admin-text-muted);" class="admin-topbar-subtitle">
          Kalakunj Mandir Management
        </span>
        <button type="button" class="theme-toggle-btn" aria-label="Toggle color theme" title="Toggle Light / Dark Mode">
          <span class="theme-toggle-icon"></span>
          <span class="theme-toggle-text">Dark</span>
        </button>
        <a href="../index.php" target="_blank" class="admin-btn admin-btn-secondary admin-btn-sm" style="display: inline-flex; align-items: center; gap: 0.4rem;">
          <span>🌐</span> Visit Store ↗
        </a>
      </div>
    </header>

    <!-- Content Area -->
    <div class="admin-content">
      <!-- Reusable Pop-up Modals & Toasts System -->
      <?php require_once __DIR__ . '/../../includes/notifications.php'; ?>
