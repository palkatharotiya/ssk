/**
 * BAPS Store - Main JavaScript & Interactivity
 */

document.addEventListener('DOMContentLoaded', () => {
    initHeaderSearch();
    initHeroSlider();
    initCartActions();
    initWishlistActions();
    initAuthModal();
    initQuantitySteppers();
    initMobileNav();
    initBackToTop();
    initNavDropdowns();
});

// Safe HTML Escaping Helper (XSS Prevention)
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/[&<>"']/g, function(m) {
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

// Toast Notifications
function showToast(message, type = 'success') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    let icon = '✓';
    if (type === 'error') icon = '✕';
    if (type === 'info') icon = 'ℹ';

    const textSpan = document.createElement('span');
    textSpan.textContent = String(message || '');
    toast.innerHTML = `<span style="font-weight: bold;">${icon}</span> `;
    toast.appendChild(textSpan);
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

// Live Autocomplete Search
function initHeaderSearch() {
    const searchInput = document.querySelector('.search-input');
    const suggestionsBox = document.querySelector('.search-suggestions');
    if (!searchInput || !suggestionsBox) return;

    let debounceTimer;

    searchInput.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        const q = e.target.value.trim();

        if (q.length < 2) {
            suggestionsBox.innerHTML = '';
            suggestionsBox.classList.remove('active');
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch(`api/search.php?q=${encodeURIComponent(q)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.results && data.results.length > 0) {
                        suggestionsBox.innerHTML = data.results.map(item => `
                            <div class="suggestion-item" onclick="window.location.href='product.php?id=${encodeURIComponent(item.id)}'">
                                <img src="${escapeHtml(item.image_url)}" class="suggestion-img" alt="${escapeHtml(item.name)}">
                                <div class="suggestion-info">
                                    <div class="suggestion-title">${escapeHtml(item.name)}</div>
                                    <div class="suggestion-price">${escapeHtml(item.formatted_price)}</div>
                                </div>
                            </div>
                        `).join('');
                        suggestionsBox.classList.add('active');
                    } else {
                        suggestionsBox.innerHTML = `<div style="padding: 1rem; color: #71717a; font-size: 0.875rem;">No products found for "${escapeHtml(q)}"</div>`;
                        suggestionsBox.classList.add('active');
                    }
                })
                .catch(() => {
                    suggestionsBox.classList.remove('active');
                });
        }, 250);
    });

    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
            suggestionsBox.classList.remove('active');
        }
    });
}

// Hero Banner Slider
function initHeroSlider() {
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.slider-dots .dot');
    if (slides.length <= 1) return;

    let currentSlide = 0;
    let slideInterval;

    function goToSlide(n) {
        slides[currentSlide].classList.remove('active');
        if (dots[currentSlide]) dots[currentSlide].classList.remove('active');
        currentSlide = (n + slides.length) % slides.length;
        slides[currentSlide].classList.add('active');
        if (dots[currentSlide]) dots[currentSlide].classList.add('active');
    }

    dots.forEach((dot, idx) => {
        dot.addEventListener('click', () => {
            clearInterval(slideInterval);
            goToSlide(idx);
            startAutoSlide();
        });
    });

    const prevBtn = document.querySelector('.slider-arrow-prev');
    const nextBtn = document.querySelector('.slider-arrow-next');
    if (prevBtn) {
        prevBtn.addEventListener('click', (e) => {
            e.preventDefault();
            clearInterval(slideInterval);
            goToSlide(currentSlide - 1);
            startAutoSlide();
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', (e) => {
            e.preventDefault();
            clearInterval(slideInterval);
            goToSlide(currentSlide + 1);
            startAutoSlide();
        });
    }

    const sliderContainer = document.querySelector('.slider-container');
    if (sliderContainer) {
        sliderContainer.addEventListener('mouseenter', () => clearInterval(slideInterval));
        sliderContainer.addEventListener('mouseleave', () => startAutoSlide());
    }

    function startAutoSlide() {
        clearInterval(slideInterval);
        slideInterval = setInterval(() => {
            goToSlide(currentSlide + 1);
        }, 5500);
    }

    startAutoSlide();
}

// Cart Operations via AJAX
function initCartActions() {
    document.addEventListener('click', (e) => {
        const addBtn = e.target.closest('.btn-add-cart');
        if (addBtn) {
            e.preventDefault();
            const productId = addBtn.dataset.productId;
            const qtyInput = document.querySelector(`.qty-input[data-product-id="${productId}"]`) || document.querySelector('#product-qty');
            const qty = qtyInput ? parseInt(qtyInput.value) || 1 : 1;

            addBtn.disabled = true;
            const origHtml = addBtn.innerHTML;
            addBtn.innerHTML = 'Adding...';

            fetch('api/cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'add', product_id: productId, quantity: qty })
            })
            .then(res => res.json())
            .then(data => {
                addBtn.disabled = false;
                addBtn.innerHTML = origHtml;
                if (data.success) {
                    showToast(data.message, 'success');
                    updateCartBadges(data.cart_count);
                } else {
                    showToast(data.message || 'Error adding to cart', 'error');
                }
            })
            .catch(() => {
                addBtn.disabled = false;
                addBtn.innerHTML = origHtml;
                showToast('Unable to connect to server', 'error');
            });
        }
    });
}

function updateCartBadges(count) {
    document.querySelectorAll('.badge-cart-count').forEach(badge => {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'flex' : 'none';
    });
}

// Wishlist
function initWishlistActions() {
    document.addEventListener('click', (e) => {
        const wishBtn = e.target.closest('.card-wishlist-btn');
        if (wishBtn) {
            e.preventDefault();
            const productId = wishBtn.dataset.productId;

            fetch('api/wishlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ product_id: productId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.require_login) {
                    showToast(data.message, 'info');
                    openAuthModal('login');
                } else if (data.success) {
                    showToast(data.message, 'success');
                    if (data.action === 'added') {
                        wishBtn.classList.add('active');
                    } else {
                        wishBtn.classList.remove('active');
                    }
                    document.querySelectorAll('.badge-wishlist-count').forEach(badge => {
                        badge.textContent = data.count;
                        badge.style.display = data.count > 0 ? 'flex' : 'none';
                    });
                }
            })
            .catch(() => {
                showToast('Action failed', 'error');
            });
        }
    });
}

// Auth Modal
function initAuthModal() {
    const modal = document.getElementById('auth-modal');
    if (!modal) return;

    window.openAuthModal = function(tab = 'login') {
        modal.classList.add('active');
        switchAuthTab(tab);
    };

    window.closeAuthModal = function() {
        modal.classList.remove('active');
    };

    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeAuthModal();
    });

    const loginTab = document.getElementById('tab-login');
    const registerTab = document.getElementById('tab-register');
    const loginForm = document.getElementById('form-login');
    const registerForm = document.getElementById('form-register');

    function switchAuthTab(active) {
        if (active === 'login') {
            loginTab.classList.add('active');
            registerTab.classList.remove('active');
            loginForm.style.display = 'block';
            registerForm.style.display = 'none';
        } else {
            registerTab.classList.add('active');
            loginTab.classList.remove('active');
            registerForm.style.display = 'block';
            loginForm.style.display = 'none';
        }
    }

    if (loginTab) loginTab.addEventListener('click', () => switchAuthTab('login'));
    if (registerTab) registerTab.addEventListener('click', () => switchAuthTab('register'));

    // Handle AJAX Login Submit
    if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const btn = loginForm.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.textContent = 'Logging in...';

            const formData = new FormData(loginForm);
            formData.append('action', 'login');

            fetch('api/auth.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    btn.textContent = 'Sign In';
                    if (data.success) {
                        showToast(data.message, 'success');
                        closeAuthModal();
                        setTimeout(() => window.location.reload(), 600);
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.textContent = 'Sign In';
                    showToast('Connection error', 'error');
                });
        });
    }

    // Handle AJAX Register Submit
    if (registerForm) {
        registerForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const btn = registerForm.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.textContent = 'Creating account...';

            const formData = new FormData(registerForm);
            formData.append('action', 'register');

            fetch('api/auth.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    btn.textContent = 'Create Account';
                    if (data.success) {
                        showToast(data.message, 'success');
                        closeAuthModal();
                        setTimeout(() => window.location.reload(), 600);
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.textContent = 'Create Account';
                    showToast('Connection error', 'error');
                });
        });
    }
}

// Steppers (+ / -)
function initQuantitySteppers() {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.qty-btn');
        if (!btn) return;
        const stepper = btn.closest('.qty-stepper');
        const input = stepper.querySelector('.qty-input');
        if (!input) return;

        let val = parseInt(input.value) || 1;
        if (btn.classList.contains('qty-plus')) {
            val += 1;
        } else if (btn.classList.contains('qty-minus')) {
            val = Math.max(1, val - 1);
        }
        input.value = val;

        // If on cart page, trigger update
        if (input.dataset.cartUpdate) {
            updateCartItemOnServer(input.dataset.productId, val);
        }
    });
}

function updateCartItemOnServer(productId, qty) {
    fetch('api/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'update', product_id: productId, quantity: qty })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        }
    });
}

// Mobile Off-Canvas Drawer Navigation
function openMobileDrawer() {
    document.body.classList.add('mobile-drawer-open');
}

function closeMobileDrawer() {
    document.body.classList.remove('mobile-drawer-open');
}

function initMobileNav() {
    const toggle = document.getElementById('mobileToggleBtn') || document.querySelector('.mobile-toggle');
    const backdrop = document.getElementById('mobileDrawerBackdrop');
    const closeBtn = document.getElementById('mobileDrawerClose');

    if (toggle) {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            openMobileDrawer();
        });
    }
    if (backdrop) {
        backdrop.addEventListener('click', () => closeMobileDrawer());
    }
    if (closeBtn) {
        closeBtn.addEventListener('click', () => closeMobileDrawer());
    }

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && document.body.classList.contains('mobile-drawer-open')) {
            closeMobileDrawer();
        }
    });
}

// Back to Top Button
function initBackToTop() {
    const btn = document.getElementById('backToTop');
    if (!btn) return;

    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            btn.classList.add('show');
        } else {
            btn.classList.remove('show');
        }
    });

    btn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

// Password Visibility Toggle
function togglePasswordVisibility(fieldId, btn) {
    const input = document.getElementById(fieldId);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = '🙈';
        btn.setAttribute('aria-label', 'Hide password');
    } else {
        input.type = 'password';
        btn.textContent = '👁️';
        btn.setAttribute('aria-label', 'Show password');
    }
}

// Single-Submit Guard for Primary Form Buttons (Hardened against premature disable & bfcache)
document.addEventListener('submit', (e) => {
    // If submission was intercepted or cancelled by client-side logic, do NOT disable
    if (e.defaultPrevented) return;
    const form = e.target;
    if (!form || form.dataset.submitting) return;

    // Check HTML5 validity
    if (form.checkValidity && !form.checkValidity()) return;

    const submitBtn = form.querySelector('button[type="submit"]:not([data-no-disable])');
    if (submitBtn) {
        submitBtn.dataset.originalText = submitBtn.innerHTML;
        setTimeout(() => {
            if (!e.defaultPrevented) {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.75';
                submitBtn.style.cursor = 'wait';
                submitBtn.innerHTML = `<span>⏳</span> Processing...`;

                // Recovery timeout: restore button after 12s if page hasn't navigated
                setTimeout(() => {
                    if (submitBtn && submitBtn.dataset.originalText) {
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '';
                        submitBtn.style.cursor = '';
                        submitBtn.innerHTML = submitBtn.dataset.originalText;
                    }
                }, 12000);
            }
        }, 50);
    }
});

// Restore submit buttons on bfcache page restore
window.addEventListener('pageshow', () => {
    document.querySelectorAll('button[type="submit"]').forEach(btn => {
        if (btn.dataset.originalText) {
            btn.disabled = false;
            btn.style.opacity = '';
            btn.style.cursor = '';
            btn.innerHTML = btn.dataset.originalText;
        }
    });
});

// Navigation Mega Dropdowns Interaction Support
function initNavDropdowns() {
    const navItems = document.querySelectorAll('.nav-bar .nav-item');
    if (!navItems.length) return;

    navItems.forEach(item => {
        const dropdown = item.querySelector('.mega-dropdown');
        if (!dropdown) return;

        // Mouse hover
        item.addEventListener('mouseenter', () => {
            navItems.forEach(other => { if (other !== item) other.classList.remove('is-open'); });
            item.classList.add('is-open');
        });
        item.addEventListener('mouseleave', () => {
            item.classList.remove('is-open');
        });
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.nav-item')) {
            navItems.forEach(item => item.classList.remove('is-open'));
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            navItems.forEach(item => item.classList.remove('is-open'));
        }
    });
}


