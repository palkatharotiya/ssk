<?php
/**
 * SSK Store - Customer Password Reset Confirmation
 * Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

$rawToken = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$error = '';
$success = false;

if (empty($rawToken)) {
    $tokenValid = false;
} else {
    $tokenHash = hash('sha256', $rawToken);
    $driver = DB::getDriver();
    $cutoffCheck = ($driver === 'sqlite') 
        ? "expires_at >= datetime('now')" 
        : "expires_at >= NOW()";

    $resetRecord = db_fetch_one("SELECT * FROM password_resets WHERE token_hash = ? AND $cutoffCheck", [$tokenHash]);
    $tokenValid = ($resetRecord !== false && !empty($resetRecord));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    require_csrf();

    $newPassword = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if (strlen($newPassword) < 8) {
        $error = 'Your new password must be at least 8 characters long.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Password confirmation does not match. Please re-enter both fields.';
    } else {
        $customerEmail = $resetRecord['email'];
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update customer password, clear guest status if applicable
        db_query("UPDATE customers SET password = ?, is_guest = 0, is_active = 1 WHERE email = ?", [
            $newHash, $customerEmail
        ]);

        // Consume all tokens for this email (single-use token enforcement)
        db_query("DELETE FROM password_resets WHERE email = ?", [$customerEmail]);

        // Clear any login attempt lockouts
        if (function_exists('clear_login_attempts')) {
            clear_login_attempts($customerEmail);
        }

        $success = true;
    }
}

$pageTitle = 'Choose New Password — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<main class="container" style="max-width: 520px; margin-top: 2.5rem; margin-bottom: 5rem;">
  
  <?= render_breadcrumb([
    ['label' => 'Home', 'url' => 'index.php'],
    ['label' => 'Account', 'url' => 'account.php'],
    ['label' => 'Reset Password']
  ]) ?>

  <div class="ui-card" style="padding: 2.25rem 2rem;">

    <div style="text-align: center; margin-bottom: 2rem;">
      <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">🔐</div>
      <h1 style="font-size: 1.65rem; font-weight: 800; color: var(--heading); margin: 0 0 0.4rem;">
        Set New Password
      </h1>
      <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0;">
        Choose a strong password of at least 8 characters for your account.
      </p>
    </div>

    <?php if ($success): ?>
      <div style="background: rgba(16, 185, 129, 0.12); color: var(--success); border: 1.5px solid var(--success); padding: 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; text-align: center;">
        <div style="font-weight: 800; font-size: 1.15rem; margin-bottom: 0.5rem;">Password Updated Successfully!</div>
        <p style="margin: 0 0 1.25rem 0; font-size: 0.925rem;">
          Your new password is now active. You can sign in to your SSK account immediately.
        </p>
        <a href="account.php" class="btn btn-primary btn-md">Sign In Now &rarr;</a>
      </div>

    <?php elseif (!$tokenValid): ?>
      <div style="background: rgba(239, 68, 68, 0.12); color: var(--danger); border: 1.5px solid var(--danger); padding: 1.5rem; border-radius: 12px; text-align: center; margin-bottom: 1.5rem;">
        <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">⚠️</div>
        <h3 style="margin: 0 0 0.5rem 0; font-size: 1.15rem; color: var(--danger);">Reset Link Invalid or Expired</h3>
        <p style="margin: 0 0 1.25rem 0; font-size: 0.9rem; color: var(--text);">
          This password reset token has expired or has already been used. Please request a fresh reset link.
        </p>
        <a href="forgot-password.php" class="btn btn-primary btn-md">Request New Reset Link</a>
      </div>

    <?php else: ?>

      <?php if ($error): ?>
        <div style="background: rgba(239, 68, 68, 0.12); color: var(--danger); border: 1.5px solid var(--danger); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; font-size: 0.9rem; font-weight: 600;">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="reset-password.php">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= htmlspecialchars($rawToken) ?>">

        <div class="form-group" style="margin-bottom: 1.25rem;">
          <label class="form-label form-label-required" for="new-password">New Password (min 8 chars)</label>
          <div style="position: relative;">
            <input type="password" name="password" id="new-password" class="form-input" required minlength="8" placeholder="Enter at least 8 characters" autocomplete="new-password">
            <button type="button" onclick="togglePasswordVisibility('new-password', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1rem;" aria-label="Show password">👁️</button>
          </div>
        </div>

        <div class="form-group" style="margin-bottom: 1.75rem;">
          <label class="form-label form-label-required" for="confirm-password">Confirm New Password</label>
          <div style="position: relative;">
            <input type="password" name="confirm_password" id="confirm-password" class="form-input" required minlength="8" placeholder="Re-enter your new password" autocomplete="new-password">
            <button type="button" onclick="togglePasswordVisibility('confirm-password', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1rem;" aria-label="Show password">👁️</button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">
          Update Password &rarr;
        </button>
      </form>

    <?php endif; ?>

  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
