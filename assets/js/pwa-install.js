/**
 * SSK Store — PWA Installation & Service Worker Client
 * Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 */

(function() {
  'use strict';

  // 1. Standalone Mode Detection
  const isStandalone = window.matchMedia('(display-mode: standalone)').matches || 
                       window.navigator.standalone === true ||
                       document.referrer.includes('android-app://');

  if (isStandalone) {
    document.documentElement.classList.add('pwa-standalone');
  }

  // 2. Localization helper
  function getLang() {
    return document.documentElement.lang === 'gu' ? 'gu' : 'en';
  }

  function getString(key) {
    const lang = getLang();
    const strings = window.SSK_PWA_STRINGS || {};
    return (strings[lang] && strings[lang][key]) || (strings.en && strings.en[key]) || '';
  }

  // 3. Register Service Worker with safe update detection (Runs even in standalone mode)
  if ('serviceWorker' in navigator && window.location.protocol.startsWith('http')) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/sw.js', { scope: '/' })
        .then((reg) => {
          // Check for pending updates
          reg.addEventListener('updatefound', () => {
            const newWorker = reg.installing;
            if (newWorker) {
              newWorker.addEventListener('statechange', () => {
                // Never interrupt or force swap during active checkout flow
                if (window.location.pathname.includes('checkout.php')) {
                  return;
                }
                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                  showUpdateToast(newWorker);
                }
              });
            }
          });
        })
        .catch((err) => {
          console.warn('[PWA] Service Worker registration failed:', err);
        });
    });
  }

  // If already running inside installed standalone PWA, suppress installation banners
  if (isStandalone) {
    return;
  }

  function showUpdateToast(worker) {
    const toast = document.createElement('div');
    toast.className = 'pwa-update-toast';
    toast.innerHTML = `
      <div style="display:flex; align-items:center; gap:0.75rem; justify-content:space-between;">
        <span>🔄 ${getString('updateAvailable')}</span>
        <button type="button" class="btn btn-primary btn-sm" id="pwaReloadBtn">
          ${getString('refreshNow')}
        </button>
      </div>
    `;
    document.body.appendChild(toast);

    document.getElementById('pwaReloadBtn').addEventListener('click', () => {
      worker.postMessage({ type: 'SKIP_WAITING' });
      window.location.reload();
    });
  }

  // 4. Dismissal Tracking (14 Days)
  const DISMISS_KEY = 'ssk-pwa-dismissed';
  const FOURTEEN_DAYS_MS = 14 * 24 * 60 * 60 * 1000;

  function isDismissedRecently() {
    const dismissedAt = localStorage.getItem(DISMISS_KEY);
    if (!dismissedAt) return false;
    return (Date.now() - parseInt(dismissedAt, 10)) < FOURTEEN_DAYS_MS;
  }

  function markDismissed() {
    localStorage.setItem(DISMISS_KEY, Date.now().toString());
  }

  // 5. iOS Detection
  const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

  let deferredPrompt = null;

  // 6. Native Chrome / Edge / Android Installation
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;

    // Show Header Install button
    const headerBtn = document.getElementById('pwaHeaderInstallBtn');
    if (headerBtn) {
      headerBtn.style.display = 'inline-flex';
      headerBtn.onclick = triggerInstall;
    }

    // Show Mobile Bottom Banner if on mobile and not dismissed recently
    if (window.innerWidth <= 768 && !isDismissedRecently()) {
      showInstallBanner();
    }
  });

  // 7. Handle iOS Safari Instruction Sheet
  if (isIOS && !isDismissedRecently()) {
    const headerBtn = document.getElementById('pwaHeaderInstallBtn');
    if (headerBtn) {
      headerBtn.style.display = 'inline-flex';
      headerBtn.onclick = showIOSModal;
    }
  }

  function triggerInstall() {
    if (deferredPrompt) {
      deferredPrompt.prompt();
      deferredPrompt.userChoice.then((choiceResult) => {
        if (choiceResult.outcome === 'accepted') {
          hideInstallBanner();
        }
        deferredPrompt = null;
      });
    } else if (isIOS) {
      showIOSModal();
    }
  }

  function showInstallBanner() {
    let banner = document.getElementById('pwaInstallBanner');
    if (!banner) {
      banner = document.createElement('div');
      banner.id = 'pwaInstallBanner';
      banner.className = 'pwa-install-banner';
      banner.innerHTML = `
        <div class="pwa-banner-content">
          <div class="pwa-banner-logo">
            <img src="/assets/icons/icon-192.png" alt="SSK Store" width="44" height="44">
          </div>
          <div class="pwa-banner-text">
            <div class="pwa-banner-title">${getString('installTitle')}</div>
            <div class="pwa-banner-desc">${getString('installDesc')}</div>
          </div>
        </div>
        <div class="pwa-banner-actions">
          <button type="button" class="btn btn-secondary btn-sm" id="pwaBannerDismissBtn">${getString('notNow')}</button>
          <button type="button" class="btn btn-primary btn-sm" id="pwaBannerInstallBtn">${getString('installButton')}</button>
        </div>
      `;
      document.body.appendChild(banner);

      document.getElementById('pwaBannerDismissBtn').addEventListener('click', () => {
        hideInstallBanner();
        markDismissed();
      });

      document.getElementById('pwaBannerInstallBtn').addEventListener('click', () => {
        triggerInstall();
      });
    }
    banner.style.display = 'flex';
  }

  function hideInstallBanner() {
    const banner = document.getElementById('pwaInstallBanner');
    if (banner) {
      banner.style.display = 'none';
    }
  }

  function showIOSModal() {
    let modal = document.getElementById('pwaIOSModal');
    if (!modal) {
      modal = document.createElement('div');
      modal.id = 'pwaIOSModal';
      modal.className = 'pwa-ios-modal-overlay';
      modal.innerHTML = `
        <div class="pwa-ios-sheet">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1rem;">
            <h3 style="margin:0; font-size:1.15rem; color:var(--heading); font-weight:800;">
              ${getString('iosInstructionsTitle')}
            </h3>
            <button type="button" id="pwaIOSCloseBtn" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-muted);">&times;</button>
          </div>
          <div style="display:flex; flex-direction:column; gap:0.85rem; font-size:0.95rem; color:var(--text);">
            <div style="display:flex; align-items:center; gap:0.75rem;">
              <span style="display:inline-flex; width:32px; height:32px; border-radius:50%; background:var(--surface-2); align-items:center; justify-content:center;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path><polyline points="16 6 12 2 8 6"></polyline><line x1="12" y1="2" x2="12" y2="15"></line></svg>
              </span>
              <span>${getString('iosStep1')}</span>
            </div>
            <div style="display:flex; align-items:center; gap:0.75rem;">
              <span style="display:inline-flex; width:32px; height:32px; border-radius:50%; background:var(--surface-2); align-items:center; justify-content:center;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
              </span>
              <span>${getString('iosStep2')}</span>
            </div>
            <div style="display:flex; align-items:center; gap:0.75rem;">
              <span style="display:inline-flex; width:32px; height:32px; border-radius:50%; background:var(--surface-2); align-items:center; justify-content:center;">
                ✓
              </span>
              <span>${getString('iosStep3')}</span>
            </div>
          </div>
          <div style="margin-top:1.5rem;">
            <button type="button" class="btn btn-primary btn-block" id="pwaIOSGotItBtn">
              ${getString('iosClose')}
            </button>
          </div>
        </div>
      `;
      document.body.appendChild(modal);

      const closeHandler = () => {
        modal.style.display = 'none';
        markDismissed();
      };
      document.getElementById('pwaIOSCloseBtn').addEventListener('click', closeHandler);
      document.getElementById('pwaIOSGotItBtn').addEventListener('click', closeHandler);
      modal.addEventListener('click', (ev) => {
        if (ev.target === modal) closeHandler();
      });
    }
    modal.style.display = 'flex';
  }

  // 8. Event when app finishes installation
  window.addEventListener('appinstalled', () => {
    hideInstallBanner();
    const headerBtn = document.getElementById('pwaHeaderInstallBtn');
    if (headerBtn) headerBtn.style.display = 'none';
    console.log('[PWA] SSK Store installed successfully');
  });

})();
