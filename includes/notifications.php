<?php
/**
 * SSK Store - Unified Pop-up Modal & Toast Notification Template
 * Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 */
$flashNotice = get_flash();
?>
<!-- Reusable SSK System Pop-up Modal -->
<div id="sskSystemModal" class="ssk-modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="sskModalTitle">
  <div class="ssk-modal-card">
    <div class="ssk-modal-icon-badge" id="sskModalIconBadge">
      <span id="sskModalIcon" style="font-size: 2rem;">⚠️</span>
    </div>
    <h3 id="sskModalTitle" class="ssk-modal-title">Notice</h3>
    <div id="sskModalMessage" class="ssk-modal-message">Notification details...</div>
    <div class="ssk-modal-actions" id="sskModalActions">
      <button type="button" class="ssk-modal-btn ssk-modal-btn-confirm" id="sskModalConfirmBtn" onclick="closeSSKModal()">OK, Got It</button>
      <button type="button" class="ssk-modal-btn ssk-modal-btn-cancel" id="sskModalCancelBtn" style="display: none;" onclick="closeSSKModal()">Cancel</button>
    </div>
  </div>
</div>

<!-- Reusable SSK Toast Shelf -->
<div id="sskToastContainer" class="ssk-toast-shelf" aria-live="polite"></div>

<?php if ($flashNotice): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  <?php if (in_array($flashNotice['type'] ?? '', ['error', 'danger', 'warning'], true)): ?>
    if (typeof showNoticeModal === 'function') {
      showNoticeModal('Important Notice', <?= json_encode($flashNotice['message'] ?? '') ?>, 'error');
    } else {
      alert(<?= json_encode($flashNotice['message'] ?? '') ?>);
    }
  <?php else: ?>
    if (typeof showToast === 'function') {
      showToast(<?= json_encode($flashNotice['message'] ?? '') ?>, 'success');
    }
  <?php endif; ?>
});
</script>
<?php endif; ?>
