/**
 * SSK Store — Progressive Web App Service Worker
 * Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 */

const CACHE_VERSION = 'ssk-v1.0.0';
const SHELL_CACHE = `ssk-shell-${CACHE_VERSION}`;
const STATIC_CACHE = `ssk-static-${CACHE_VERSION}`;
const IMAGE_CACHE = `ssk-images-${CACHE_VERSION}`;

const MAX_IMAGE_CACHE_ENTRIES = 60;

// App Shell assets precached on install
const PRECACHE_ASSETS = [
  '/',
  '/offline.html',
  '/manifest.webmanifest',
  '/assets/css/theme.css',
  '/assets/css/style.css',
  '/assets/js/theme-toggle.js',
  '/assets/js/pwa-strings.js',
  '/assets/js/pwa-install.js',
  '/assets/brand/logo.png',
  '/assets/brand/logo-light.png',
  '/assets/brand/logo-mark.png',
  '/assets/brand/favicon.ico',
  '/assets/icons/icon-192.png',
  '/assets/icons/icon-512.png',
  '/assets/icons/icon-512-maskable.png',
  '/assets/icons/apple-touch-icon.png',
  '/assets/icons/favicon-32.png',
  '/assets/icons/favicon-16.png'
];

// URLs that MUST NEVER be cached or intercepted (always go directly to network)
const EXCLUDED_PATHS = [
  '/admin',
  '/cart.php',
  '/checkout.php',
  '/order-success.php',
  '/account.php',
  '/api/',
  '/login.php',
  '/register.php'
];

/**
 * Determine if request should bypass Service Worker caching completely
 */
function shouldBypassCache(url, request) {
  // 1. Only GET requests are eligible for caching
  if (request.method !== 'GET') return true;

  const pathname = url.pathname.toLowerCase();

  // 2. Never cache admin portal, checkout, cart, or user accounts
  for (const path of EXCLUDED_PATHS) {
    if (pathname.startsWith(path) || pathname === path) {
      return true;
    }
  }

  // 3. Never cache dynamic session queries or API actions
  const search = url.search.toLowerCase();
  if (
    search.includes('action=') ||
    search.includes('token=') ||
    search.includes('csrf') ||
    search.includes('session') ||
    search.includes('logout') ||
    search.includes('payment')
  ) {
    return true;
  }

  return false;
}

// 1. Install Event: Precache app shell
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(SHELL_CACHE).then((cache) => {
      return cache.addAll(PRECACHE_ASSETS).catch((err) => {
        console.warn('[SW] Precache non-critical failure:', err);
      });
    }).then(() => self.skipWaiting())
  );
});

// 2. Activate Event: Cleanup stale caches and claim clients
self.addEventListener('activate', (event) => {
  const currentCaches = [SHELL_CACHE, STATIC_CACHE, IMAGE_CACHE];
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (!currentCaches.includes(key)) {
            return caches.delete(key);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Helper: Trim cache to max size
async function trimCache(cacheName, maxItems) {
  const cache = await caches.open(cacheName);
  const keys = await cache.keys();
  if (keys.length > maxItems) {
    await cache.delete(keys[0]);
    trimCache(cacheName, maxItems);
  }
}

// 3. Fetch Event
self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);

  // Bypass third-party domains (analytics, fonts, cdn) or excluded internal paths
  if (url.origin !== self.location.origin || shouldBypassCache(url, request)) {
    return; // Browser performs default network fetch
  }

  // A. Navigations (HTML pages): Network-First with Offline Page Fallback
  if (request.mode === 'navigate' || request.destination === 'document') {
    event.respondWith(
      fetch(request)
        .then((response) => {
          // If valid, return live response
          return response;
        })
        .catch(async () => {
          // Network failed: return cached page if available or offline.html
          const cached = await caches.match(request);
          if (cached) return cached;
          const offlinePage = await caches.match('/offline.html');
          return offlinePage || new Response('You are offline.', { status: 503, headers: { 'Content-Type': 'text/plain' } });
        })
    );
    return;
  }

  // B. Images: Cache-First with size limit
  if (request.destination === 'image') {
    event.respondWith(
      caches.match(request).then((cachedResponse) => {
        if (cachedResponse) return cachedResponse;

        return fetch(request).then((networkResponse) => {
          if (!networkResponse || networkResponse.status !== 200) {
            return networkResponse;
          }
          const responseToCache = networkResponse.clone();
          caches.open(IMAGE_CACHE).then((cache) => {
            cache.put(request, responseToCache);
            trimCache(IMAGE_CACHE, MAX_IMAGE_CACHE_ENTRIES);
          });
          return networkResponse;
        }).catch(() => {
          // Placeholder or empty response on network failure
          return caches.match('/assets/icons/icon-192.png');
        });
      })
    );
    return;
  }

  // C. Static Stylesheets & Scripts: Stale-While-Revalidate
  if (request.destination === 'style' || request.destination === 'script' || request.destination === 'font') {
    event.respondWith(
      caches.match(request).then((cachedResponse) => {
        const fetchPromise = fetch(request).then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200) {
            const responseToCache = networkResponse.clone();
            caches.open(STATIC_CACHE).then((cache) => cache.put(request, responseToCache));
          }
          return networkResponse;
        }).catch(() => cachedResponse);

        return cachedResponse || fetchPromise;
      })
    );
    return;
  }

  // D. Default: Cache falling back to network
  event.respondWith(
    caches.match(request).then((cachedResponse) => {
      return cachedResponse || fetch(request);
    })
  );
});

// 4. Message Event: Allow skip waiting on user click
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
