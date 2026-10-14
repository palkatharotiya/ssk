/**
 * SSK Store - Unified Pop-up Modal & Toast Notification System
 * Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 */

(function () {
  'use strict';

  function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/[&<>"']/g, function (m) {
      switch (m) {
        case '&': return '&amp;';
        case '<': return '&lt;';
        case '>': return '&gt;';
        case '"': return '&quot;';
        case "'": return '&#039;';
        default: return m;
      }
    });
  }

  window.showNoticeModal = function (title, message, type = 'warning', confirmText = 'OK, Got It', onConfirm = null) {
    const modal = document.getElementById('sskSystemModal');
    if (!modal) {
      alert(message);
      return;
    }

    const iconBadge = document.getElementById('sskModalIconBadge');
    const icon = document.getElementById('sskModalIcon');
    const titleEl = document.getElementById('sskModalTitle');
    const bodyEl = document.getElementById('sskModalMessage');
    const confirmBtn = document.getElementById('sskModalConfirmBtn');
    const cancelBtn = document.getElementById('sskModalCancelBtn');

    titleEl.textContent = title || (type === 'error' ? 'Notice' : 'Information');
    bodyEl.textContent = message || '';
    confirmBtn.textContent = confirmText;
    if (cancelBtn) cancelBtn.style.display = 'none';

    if (iconBadge) {
      iconBadge.className = 'ssk-modal-icon-badge type-' + type;
    }
    if (icon) {
      if (type === 'error' || type === 'danger' || type === 'warning') {
        icon.textContent = '⚠️';
      } else if (type === 'success') {
        icon.textContent = '✅';
      } else {
        icon.textContent = 'ℹ️';
      }
    }

    modal.style.display = 'flex';
    requestAnimationFrame(() => modal.classList.add('active'));

    confirmBtn.onclick = function () {
      closeSSKModal();
      if (typeof onConfirm === 'function') onConfirm();
    };
  };

  window.showConfirmModal = function (title, message, onConfirm, confirmText = 'Yes, Proceed', cancelText = 'Cancel') {
    const modal = document.getElementById('sskSystemModal');
    if (!modal) {
      if (confirm(message)) onConfirm();
      return;
    }

    const iconBadge = document.getElementById('sskModalIconBadge');
    const icon = document.getElementById('sskModalIcon');
    const titleEl = document.getElementById('sskModalTitle');
    const bodyEl = document.getElementById('sskModalMessage');
    const confirmBtn = document.getElementById('sskModalConfirmBtn');
    const cancelBtn = document.getElementById('sskModalCancelBtn');

    titleEl.textContent = title || 'Please Confirm';
    bodyEl.textContent = message;
    confirmBtn.textContent = confirmText;
    if (cancelBtn) {
      cancelBtn.textContent = cancelText;
      cancelBtn.style.display = 'inline-block';
      cancelBtn.onclick = closeSSKModal;
    }

    if (iconBadge) {
      iconBadge.className = 'ssk-modal-icon-badge type-error';
    }
    if (icon) {
      icon.textContent = '⚠️';
    }

    modal.style.display = 'flex';
    requestAnimationFrame(() => modal.classList.add('active'));

    confirmBtn.onclick = function () {
      closeSSKModal();
      if (typeof onConfirm === 'function') onConfirm();
    };
  };

  window.closeSSKModal = function () {
    const modal = document.getElementById('sskSystemModal');
    if (!modal) return;
    modal.classList.remove('active');
    setTimeout(() => {
      modal.style.display = 'none';
    }, 220);
  };

  window.showToast = function (message, type = 'success') {
    let shelf = document.getElementById('sskToastContainer');
    if (!shelf) {
      shelf = document.createElement('div');
      shelf.id = 'sskToastContainer';
      shelf.className = 'ssk-toast-shelf';
      document.body.appendChild(shelf);
    }

    const toast = document.createElement('div');
    toast.className = 'ssk-toast-item type-' + type;

    let icon = type === 'error' ? '❌' : (type === 'info' ? 'ℹ️' : '✅');
    toast.innerHTML = `<span>${icon}</span><span style="flex:1;">${escapeHtml(message)}</span><button type="button" class="ssk-toast-close" aria-label="Close notification" onclick="this.parentElement.remove()">&times;</button>`;

    shelf.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('show'));

    setTimeout(() => {
      toast.classList.remove('show');
      setTimeout(() => toast.remove(), 250);
    }, 4200);
  };
})();
