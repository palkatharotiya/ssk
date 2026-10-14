<?php
/**
 * BAPS Store - Admin Inquiries & Messages
 */

require_once __DIR__ . '/includes/admin_bootstrap.php';
admin_require_auth('messages');

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'mark_read') {
        $msgId = (int)($_POST['id'] ?? 0);
        if ($msgId > 0) {
            db_query("UPDATE contact_messages SET is_read = 1 WHERE id = ?", [$msgId]);
            set_flash('success', 'Message marked as read.');
        }
        header('Location: messages.php');
        exit;
    }
}

$adminPageTitle = 'Customer Inquiries';
require_once __DIR__ . '/includes/header.php';

$messages = db_fetch_all("SELECT * FROM contact_messages ORDER BY id DESC");
?>

<div class="admin-card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h3 style="font-size: 1.25rem; font-weight: 700;">Customer Inquiries & Messages</h3>
    <span style="font-size: 0.875rem; color: var(--admin-text-muted);"><?= count($messages) ?> inquiries received</span>
  </div>

  <?php if (empty($messages)): ?>
    <p style="color: var(--admin-text-muted); padding: 2rem 0; text-align: center;">No contact messages received yet.</p>
  <?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 1rem;">
      <?php foreach ($messages as $m): ?>
        <div style="border: 1px solid var(--admin-border); border-radius: 8px; padding: 1.25rem; background: <?= $m['is_read'] ? '#ffffff' : '#f0fdf4' ?>;">
          <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
            <div>
              <strong><?= htmlspecialchars($m['name']) ?></strong> &lt;<?= htmlspecialchars($m['email']) ?>&gt;
              <?php if (!empty($m['phone'])): ?> | Phone: <?= htmlspecialchars($m['phone']) ?><?php endif; ?>
            </div>
            <div style="font-size: 0.75rem; color: var(--admin-text-muted);">
              <?= date('d M Y, h:i A', strtotime($m['created_at'])) ?>
            </div>
          </div>
          <?php if (!empty($m['subject'])): ?>
            <div style="font-weight: 600; font-size: 0.875rem; color: var(--admin-primary); margin-bottom: 0.35rem;">
              Subject: <?= htmlspecialchars($m['subject']) ?>
            </div>
          <?php endif; ?>
          <p style="font-size: 0.875rem; color: var(--admin-text); line-height: 1.6; margin-bottom: 0.75rem;">
            <?= nl2br(htmlspecialchars($m['message'])) ?>
          </p>
          <?php if (!$m['is_read']): ?>
            <form method="POST" action="messages.php" style="display: inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="mark_read">
              <input type="hidden" name="id" value="<?= $m['id'] ?>">
              <button type="submit" class="admin-btn admin-btn-secondary admin-btn-sm">
                Mark as Read
              </button>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
