<?php
/**
 * SSK Store - Customer Password Reset Request
 * Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

$submitted = false;
$error = '';
$devResetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    if (!check_rate_limit('forgot_pwd', 3, 300)) {
        $error = 'Too many password reset requests. Please wait 5 minutes before trying again.';
    } else {
        $email = trim(strtolower((string)($_POST['email'] ?? '')));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $customer = db_fetch_one("SELECT id, first_name, email FROM customers WHERE email = ? AND is_active = 1", [$email]);

            if ($customer) {
                $rawToken = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $rawToken);
                $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour validity

                // Invalidate any older unused reset tokens for this email
                db_query("DELETE FROM password_resets WHERE email = ?", [$email]);

                db_query("INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, ?)", [
                    $email, $tokenHash, $expiresAt
                ]);

                $resetUrl = SITE_URL . '/reset-password.php?token=' . $rawToken;

                // Log email to mail.log
                $logDir = ROOT_PATH . 'logs/';
                if (!is_dir($logDir)) {
                    mkdir($logDir, 0750, true);
                }
                $logMessage = sprintf(
                    "[%s] Password Reset Dispatched to %s (%s)\nReset Link: %s\nExpires: %s\n\n",
                    date('Y-m-d H:i:s'),
                    $customer['first_name'],
                    $email,
                    $resetUrl,
                    $expiresAt
                );
                file_put_contents($logDir . 'mail.log', $logMessage, FILE_APPEND | LOCK_EX);

                if (!IS_PRODUCTION) {
                    $devResetLink = $resetUrl;
                }
            }

            // Always show identical message to prevent user enumeration
            $submitted = true;
        }
    }
}

$pageTitle = 'Forgot Password — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<main class="container" style="max-width: 520px; margin-top: 2.5rem; margin-bottom: 5rem;">
  
  <?= render_breadcrumb([
    ['label' => 'Home', 'url' => 'index.php'],
    ['label' => 'Account', 'url' => 'account.php'],
    ['label' => 'Forgot Password']
  ]) ?>

  <div class="ui-card" style="padding: 2.25rem 2rem;">
    
    <div style="text-align: center; margin-bottom: 2rem;">
      <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">🔑</div>
      <h1 style="font-size: 1.65rem; font-weight: 800; color: var(--heading); margin: 0 0 0.4rem;">
        Reset Your Password
      </h1>
      <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0; line-height: 1.5;">
        Enter your registered email address and we will generate a secure link to reset your account password.
      </p>
    </div>

    <?php if ($submitted): ?>
      <div style="background: rgba(16, 185, 129, 0.12); color: var(--success); border: 1.5px solid var(--success); padding: 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; line-height: 1.6;">
        <div style="font-weight: 700; font-size: 1.05rem; margin-bottom: 0.25rem;">✓ Reset Link Dispatched</div>
        If an active customer account exists with this email address, a password reset link has been dispatched. Please check your inbox (and spam folder). The link will expire in 1 hour.
      </div>

      <?php if (!empty($devResetLink)): ?>
        <div style="background: #eff6ff; border: 1.5px solid #3b82f6; border-radius: 10px; padding: 1rem; margin-bottom: 1.5rem; font-size: 0.85rem; word-break: break-all;">
          <strong style="color: #1d4ed8;">Local Dev Quick Link:</strong><br>
          <a href="<?= htmlspecialchars($devResetLink) ?>" style="color: #2563eb; text-decoration: underline;">
            <?= htmlspecialchars($devResetLink) ?>
          </a>
        </div>
      <?php endif; ?>

      <div style="text-align: center; margin-top: 1.5rem;">
        <a href="account.php" class="btn btn-secondary btn-md">Back to Sign In</a>
      </div>

    <?php else: ?>

      <?php if ($error): ?>
        <div style="background: rgba(239, 68, 68, 0.12); color: var(--danger); border: 1.5px solid var(--danger); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; font-size: 0.9rem; font-weight: 600;">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="forgot-password.php">
        <?= csrf_field() ?>

        <div class="form-group" style="margin-bottom: 1.5rem;">
          <label class="form-label form-label-required" for="reset-email">Registered Email Address</label>
          <input type="email" name="email" id="reset-email" class="form-input" required placeholder="name@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autocomplete="email">
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-bottom: 1rem;">
          Send Reset Link &rarr;
        </button>

        <div style="text-align: center; font-size: 0.875rem; color: var(--text-muted); margin-top: 1.25rem;">
          Remember your password? <a href="account.php" style="color: var(--link); font-weight: 700;">Sign In</a>
        </div>
      </form>

    <?php endif; ?>

  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
