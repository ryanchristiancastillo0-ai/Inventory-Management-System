/**
 * Inventory Management System — service worker.
 *
 * Strategy:
 *  - Page navigations: network-first (fresh data whenever the server is
 *    reachable), falling back to the cached copy of a previously visited
 *    page, then to the offline page.
 *  - Static assets (CSS/JS/images/fonts): cache-first, populated on first
 *    use — fast repeat loads and offline shell.
 *  - Non-GET requests (form POSTs etc.) always go straight to the network
 *    and are never cached.
 *
 * Bump CACHE_VERSION whenever you deploy changed static assets so old
 * caches are cleaned up on activation.
 */
const CACHE_VERSION = 'ims-v1';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const PAGES_CACHE = `${CACHE_VERSION}-pages`;

/** Precached at install: things needed even before the user visits anything. */
const PRECACHE = [
    './offline.html',
    './manifest.webmanifest',
    './assets/icons/pwa-icon-192.png',
    './assets/icons/pwa-icon-512.png',
    './assets/icons/favicon-32.png'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(PRECACHE))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys
                    .filter((key) => key !== STATIC_CACHE && key !== PAGES_CACHE)
                    .map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Only manage GET requests; form submissions always hit the network.
    if (request.method !== 'GET') {
        return;
    }

    // Page navigations: network-first with cached fallback.
    if (request.mode === 'navigate') {
        event.respondWith(networkFirstPage(request));
        return;
    }

    const url = new URL(request.url);

    // Same-origin static assets: cache-first.
    if (url.origin === self.location.origin) {
        event.respondWith(cacheFirstStatic(request));
        return;
    }

    // Google Fonts: cache-first (opaque responses are fine to cache).
    if (
        url.hostname === 'fonts.googleapis.com' ||
        url.hostname === 'fonts.gstatic.com'
    ) {
        event.respondWith(cacheFirstStatic(request));
        return;
    }
});

/** Network-first for navigations; cache and offline page as fallbacks. */
async function networkFirstPage(request) {
    const cache = await caches.open(PAGES_CACHE);

    try {
        const response = await fetch(request);
        if (response && response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        const cached = await cache.match(request);
        if (cached) {
            return cached;
        }
        return caches.match('./offline.html');
    }
}

/** Cache-first for static assets; network populates the cache. */
async function cacheFirstStatic(request) {
    const cached = await caches.match(request);
    if (cached) {
        return cached;
    }

    const response = await fetch(request);
    if (response && (response.ok || response.type === 'opaque')) {
        const cache = await caches.open(STATIC_CACHE);
        cache.put(request, response.clone());
    }
    return response;
}
