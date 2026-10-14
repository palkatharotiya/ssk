<?php
/**
 * SSK Store - Admin Logout
 * Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    logout_admin();
    header('Location: login.php');
    exit;
}

// If accessed via GET, render a secure confirmation page instead of mutating state
$admin = current_admin();
if (!$admin) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Confirm Sign Out — SSK Admin</title>
  <link rel="stylesheet" href="../assets/css/theme.css">
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #0b1329; font-family: system-ui, sans-serif;">
  <div style="background: #1e293b; color: #f8fafc; border-radius: 12px; padding: 2rem; max-width: 420px; width: 90%; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.4);">
    <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">🚪</div>
    <h2 style="margin: 0 0 0.5rem 0; font-size: 1.35rem;">Sign Out of SSK Admin?</h2>
    <p style="color: #94a3b8; font-size: 0.9rem; margin-bottom: 1.5rem;">
      Are you sure you want to end your administrative session?
    </p>
    <div style="display: flex; gap: 0.75rem; justify-content: center;">
      <a href="index.php" class="admin-btn admin-btn-secondary" style="padding: 0.6rem 1.25rem;">Cancel</a>
      <form method="POST" action="logout.php" style="display: inline;">
        <?= csrf_field() ?>
        <button type="submit" class="admin-btn admin-btn-danger" style="padding: 0.6rem 1.25rem;">Confirm Sign Out</button>
      </form>
    </div>
  </div>
</body>
</html>
