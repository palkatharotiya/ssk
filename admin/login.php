<?php
/**
 * SSK Store - Secure Admin Authentication
 * Shree Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_admin_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $error = 'Please enter both your administrator username and password.';
        } else {
            $result = login_admin($username, $password);
            if ($result['success']) {
                header('Location: index.php');
                exit;
            } else {
                $error = $result['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — SSK Store Portal (Kalakunj Mandir)</title>
  <script>
    (function() {
      var stored = localStorage.getItem('ssk-theme');
      var theme = stored || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
      document.documentElement.setAttribute('data-theme', theme);
    })();
  </script>
  <link rel="stylesheet" href="../assets/css/theme.css">
  <link rel="stylesheet" href="css/admin.css">
  <link rel="icon" type="image/png" href="../assets/images/logo.png">
  <style>
    body {
      background: var(--bg);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 1.5rem;
      margin: 0;
    }
    .login-box {
      background: var(--surface);
      width: 100%;
      max-width: 420px;
      padding: 2.5rem 2rem;
      border-radius: 16px;
      border: 1.5px solid var(--card-border);
      box-shadow: var(--shadow);
      color: var(--text);
      position: relative;
    }
    .password-wrap {
      position: relative;
      display: flex;
      align-items: center;
    }
    .password-wrap .admin-input {
      padding-right: 46px;
    }
    .password-toggle-btn {
      position: absolute;
      right: 12px;
      background: none;
      border: none;
      color: var(--text-muted);
      cursor: pointer;
      padding: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 6px;
      transition: color 0.15s ease;
    }
    .password-toggle-btn:hover {
      color: var(--text);
    }
    .theme-toggle-top {
      position: absolute;
      top: 1rem;
      right: 1rem;
    }
  </style>
</head>
<body>

  <div class="theme-toggle-top">
    <button type="button" class="theme-toggle-btn" id="adminThemeToggle" aria-label="Toggle Theme" title="Toggle Light/Dark Theme">
      <svg class="sun-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
      <svg class="moon-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
    </button>
  </div>

  <div class="login-box">
    <div style="text-align: center; margin-bottom: 2rem;">
      <div style="display: inline-block; background: #FFFFFF; padding: 8px 16px; border-radius: 12px; margin-bottom: 1rem; box-shadow: var(--shadow-sm); border: 1px solid rgba(1, 23, 96, 0.08);">
        <img src="../assets/images/logo.png" alt="SSK Logo" style="max-height: 54px; max-width: 220px; object-fit: contain;">
      </div>
      <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--heading); margin: 0 0 0.35rem;">Admin Management Portal</h1>
      <p style="font-size: 0.875rem; color: var(--text-muted); margin: 0;">Shri Sahajanand Sahitya Kendra • Kalakunj Mandir</p>
    </div>

    <?php if ($error): ?>
      <div style="background: rgba(239, 68, 68, 0.12); color: var(--danger); padding: 0.85rem 1rem; border-radius: 8px; font-size: 0.875rem; margin-bottom: 1.5rem; font-weight: 600; border: 1px solid rgba(239, 68, 68, 0.25); display: flex; align-items: center; gap: 8px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php" id="adminLoginForm">
      <?= csrf_field() ?>

      <div class="admin-form-group">
        <label class="admin-label admin-label-required" for="login-user">Username or Administrator Email</label>
        <input type="text" id="login-user" name="username" class="admin-input" placeholder="admin or name@sskstore.in" autocomplete="username" required autofocus>
      </div>

      <div class="admin-form-group">
        <label class="admin-label admin-label-required" for="login-pass">Password</label>
        <div class="password-wrap">
          <input type="password" id="login-pass" name="password" class="admin-input" placeholder="Enter administrator password" autocomplete="current-password" required>
          <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('login-pass', this)" aria-label="Toggle password visibility" title="Show/Hide password">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
          </button>
        </div>
      </div>

      <button type="submit" class="admin-btn admin-btn-primary" style="width: 100%; justify-content: center; min-height: 48px; margin-top: 0.5rem; font-size: 1rem;">
        Sign In to Portal &rarr;
      </button>

      <div style="margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px solid var(--card-border); text-align: center; font-size: 0.75rem; color: var(--text-muted); display: flex; align-items: center; justify-content: center; gap: 6px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
        Authorized Personnel Only • 256-Bit SSL Encrypted
      </div>
    </form>
  </div>

  <script>
  function togglePasswordVisibility(fieldId, btn) {
    const input = document.getElementById(fieldId);
    if (!input) return;
    if (input.type === 'password') {
      input.type = 'text';
      btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';
    } else {
      input.type = 'password';
      btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
    }
  }

  // Theme Toggle
  document.getElementById('adminThemeToggle')?.addEventListener('click', function() {
    const current = document.documentElement.getAttribute('data-theme') || 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('ssk-theme', next);
  });
  </script>

</body>
</html>
