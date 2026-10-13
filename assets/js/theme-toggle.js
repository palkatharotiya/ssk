/**
 * SSK Store - Unified Theme Switcher Engine
 * Handles Light / Dark Mode with System Preference & localStorage Sync
 */
(function() {
  'use strict';

  const STORAGE_KEY = 'ssk-theme';

  function getSystemPreference() {
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }

  function getSavedTheme() {
    return localStorage.getItem(STORAGE_KEY) || 'system';
  }

  function resolveEffectiveTheme(themeSetting) {
    if (themeSetting === 'dark' || themeSetting === 'light') {
      return themeSetting;
    }
    return getSystemPreference();
  }

  function updateThemeColorMeta(effectiveTheme) {
    const color = effectiveTheme === 'dark' ? '#060B1F' : '#011760';
    const metas = document.querySelectorAll('meta[name="theme-color"]');
    metas.forEach(m => m.setAttribute('content', color));
  }

  function applyTheme(themeSetting) {
    const effective = resolveEffectiveTheme(themeSetting);
    document.documentElement.setAttribute('data-theme', effective);
    document.documentElement.style.colorScheme = effective;
    updateThemeColorMeta(effective);
    updateToggleButtons(effective, themeSetting);
  }

  function setTheme(themeSetting) {
    if (themeSetting === 'system') {
      localStorage.removeItem(STORAGE_KEY);
    } else {
      localStorage.setItem(STORAGE_KEY, themeSetting);
    }
    applyTheme(themeSetting);
  }

  function toggleTheme() {
    const currentEffective = document.documentElement.getAttribute('data-theme') || resolveEffectiveTheme(getSavedTheme());
    const nextTheme = currentEffective === 'dark' ? 'light' : 'dark';
    setTheme(nextTheme);
  }

  // Sun and Moon inline SVG icons
  const SUN_SVG = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>`;
  const MOON_SVG = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>`;

  function updateToggleButtons(effectiveTheme) {
    const buttons = document.querySelectorAll('.theme-toggle-btn, [data-theme-toggle]');
    buttons.forEach(btn => {
      const isDark = effectiveTheme === 'dark';
      btn.setAttribute('aria-label', isDark ? 'Switch to Light theme' : 'Switch to Dark theme');
      btn.setAttribute('title', isDark ? 'Click to switch to Light Theme' : 'Click to switch to Dark Theme');
      
      const iconSpan = btn.querySelector('.theme-toggle-icon');
      const textSpan = btn.querySelector('.theme-toggle-text');
      
      if (iconSpan) {
        iconSpan.innerHTML = isDark ? SUN_SVG : MOON_SVG;
      } else {
        btn.innerHTML = `${isDark ? SUN_SVG : MOON_SVG} <span class="theme-toggle-text" style="font-size: 0.8125rem;">${isDark ? 'Light' : 'Dark'}</span>`;
      }

      if (textSpan) {
        textSpan.textContent = isDark ? 'Light' : 'Dark';
      }
    });
  }

  // Initialize on DOM load
  document.addEventListener('DOMContentLoaded', function() {
    const saved = getSavedTheme();
    applyTheme(saved);

    // Bind click events on all theme buttons
    document.querySelectorAll('.theme-toggle-btn, [data-theme-toggle]').forEach(btn => {
      btn.removeEventListener('click', toggleTheme);
      btn.addEventListener('click', toggleTheme);
    });
  });

  // Listen for OS system theme changes
  if (window.matchMedia) {
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
      if (!localStorage.getItem(STORAGE_KEY)) {
        applyTheme('system');
      }
    });
  }

  // Sync across tabs & windows
  window.addEventListener('storage', function(e) {
    if (e.key === STORAGE_KEY) {
      applyTheme(e.newValue || 'system');
    }
  });

  // Expose to window
  window.SSKTheme = {
    setTheme: setTheme,
    toggleTheme: toggleTheme,
    getTheme: function() {
      return document.documentElement.getAttribute('data-theme') || 'light';
    }
  };
})();
