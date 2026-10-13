/**
 * SSK Store - Admin UI Interactions & Live Filter Scripts
 * Shree Sahajanand Sahitya Kendra (Kalakunj Mandir)
 */

document.addEventListener('DOMContentLoaded', function () {
  initSidebar();
  initLiveProductFilter();
  initCategorySearch();
});

/**
 * Collapsible Sidebar Setup with LocalStorage Persistence
 */
function initSidebar() {
  const toggleBtn = document.getElementById('sidebarToggleBtn');
  const closeBtn = document.getElementById('sidebarCloseBtn');
  const backdrop = document.getElementById('sidebarBackdrop');

  // Check saved state for desktop
  const isCollapsed = localStorage.getItem('ssk_admin_sidebar_collapsed') === '1';
  if (isCollapsed && window.innerWidth > 992) {
    document.body.classList.add('sidebar-collapsed');
  }

  function toggleSidebar() {
    if (window.innerWidth <= 992) {
      document.body.classList.remove('sidebar-collapsed');
      document.body.classList.toggle('sidebar-open');
    } else {
      document.body.classList.remove('sidebar-open');
      document.body.classList.toggle('sidebar-collapsed');
      const collapsedNow = document.body.classList.contains('sidebar-collapsed');
      localStorage.setItem('ssk_admin_sidebar_collapsed', collapsedNow ? '1' : '0');
    }
  }

  function closeSidebar() {
    document.body.classList.remove('sidebar-open');
    if (window.innerWidth > 992) {
      document.body.classList.add('sidebar-collapsed');
      localStorage.setItem('ssk_admin_sidebar_collapsed', '1');
    }
  }

  if (toggleBtn) {
    toggleBtn.addEventListener('click', function (e) {
      e.preventDefault();
      toggleSidebar();
    });
  }

  if (closeBtn) {
    closeBtn.addEventListener('click', function (e) {
      e.preventDefault();
      closeSidebar();
    });
  }

  if (backdrop) {
    backdrop.addEventListener('click', closeSidebar);
  }

  // Keyboard shortcut: Ctrl + B to toggle sidebar
  window.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
      e.preventDefault();
      toggleSidebar();
    }
  });
}

/**
 * Real-time Instant Product Filtering (No page reload needed!)
 */
function initLiveProductFilter() {
  const searchInput = document.getElementById('liveProductSearch');
  const clearBtn = document.getElementById('clearSearchBtn');
  const deptFilter = document.getElementById('filterDept');
  const stockFilter = document.getElementById('filterStock');
  const statusFilter = document.getElementById('filterStatus');
  const countBadge = document.getElementById('filterCountBadge');
  const table = document.getElementById('productsTable');

  if (!searchInput || !table) return;

  const rows = Array.from(table.querySelectorAll('tbody tr.product-row'));

  function filterRows() {
    const q = searchInput.value.trim().toLowerCase();
    const selectedDept = deptFilter ? deptFilter.value : '';
    const selectedStock = stockFilter ? stockFilter.value : '';
    const selectedStatus = statusFilter ? statusFilter.value : '';

    if (clearBtn) {
      clearBtn.style.display = q ? 'block' : 'none';
    }

    let matchCount = 0;

    rows.forEach(function (row) {
      const name = (row.getAttribute('data-name') || '').toLowerCase();
      const sku = (row.getAttribute('data-sku') || '').toLowerCase();
      const dept = row.getAttribute('data-dept') || '';
      const stock = parseInt(row.getAttribute('data-stock') || '0', 10);
      const status = row.getAttribute('data-status') || '';

      let matchesSearch = !q || name.includes(q) || sku.includes(q);
      let matchesDept = !selectedDept || dept === selectedDept;
      let matchesStatus = !selectedStatus || status === selectedStatus;
      let matchesStock = true;

      if (selectedStock === 'in_stock') {
        matchesStock = stock > 15;
      } else if (selectedStock === 'low_stock') {
        matchesStock = stock > 0 && stock <= 15;
      } else if (selectedStock === 'out_of_stock') {
        matchesStock = stock === 0;
      }

      const isVisible = matchesSearch && matchesDept && matchesStatus && matchesStock;
      row.style.display = isVisible ? '' : 'none';
      if (isVisible) matchCount++;
    });

    if (countBadge) {
      countBadge.textContent = 'Showing ' + matchCount + ' of ' + rows.length + ' products';
    }

    // Toggle empty match row
    const noMatchRow = document.getElementById('noMatchRow');
    if (noMatchRow) {
      noMatchRow.style.display = matchCount === 0 ? '' : 'none';
    }
  }

  searchInput.addEventListener('input', filterRows);
  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      searchInput.value = '';
      searchInput.focus();
      filterRows();
    });
  }

  if (deptFilter) deptFilter.addEventListener('change', filterRows);
  if (stockFilter) stockFilter.addEventListener('change', filterRows);
  if (statusFilter) statusFilter.addEventListener('change', filterRows);
}

/**
 * Instant Search in Categories Page
 */
function initCategorySearch() {
  const catSearch = document.getElementById('categoryLiveSearch');
  if (!catSearch) return;

  const cards = Array.from(document.querySelectorAll('.category-tree-card'));
  const chips = Array.from(document.querySelectorAll('.subcat-chip'));

  catSearch.addEventListener('input', function () {
    const q = this.value.trim().toLowerCase();

    if (!q) {
      cards.forEach(c => c.style.display = '');
      chips.forEach(c => c.style.display = '');
      return;
    }

    cards.forEach(card => {
      const deptName = (card.getAttribute('data-dept-name') || '').toLowerCase();
      const cardChips = Array.from(card.querySelectorAll('.subcat-chip'));
      let anyChipVisible = false;

      cardChips.forEach(chip => {
        const catName = (chip.getAttribute('data-cat-name') || '').toLowerCase();
        const chipMatches = catName.includes(q) || deptName.includes(q);
        chip.style.display = chipMatches ? '' : 'none';
        if (chipMatches) anyChipVisible = true;
      });

      const cardMatches = deptName.includes(q) || anyChipVisible;
      card.style.display = cardMatches ? '' : 'none';
    });
  });
}
