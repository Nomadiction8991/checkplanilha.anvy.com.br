// Production Service Worker for CheckPlanilha - registered only on /prod/login.php
const CACHE_NAME = 'checkplanilha-prod-v1';
const CORE_ASSETS = [
  '/prod/',
  '/prod/index.php',
  '/prod/login.php',
  '/logo.png',
  '/manifest-prod.json',
  '/prod/manifest.json'
];

// On install, cache core assets
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(CORE_ASSETS.map(p => new Request(p, {cache: 'reload'}))).catch(err => {
        console.warn('Precache failed:', err);
      });
    })
  );
});

// Activate - cleanup old caches
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => Promise.all(
      keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
    ))
  );
});

// Fetch - serve from cache first for navigation and cache runtime for GET requests
self.addEventListener('fetch', event => {
  const req = event.request;

  // Only handle same-origin GET requests
  if (req.method !== 'GET' || new URL(req.url).origin !== self.location.origin) {
    return;
  }

  // Navigation requests (HTML) -> try cache first, then network and cache
  if (req.mode === 'navigate' || (req.headers.get('accept') || '').includes('text/html')) {
    event.respondWith(
      caches.match(req).then(cached => {
        if (cached) return cached;
        return fetch(req).then(networkRes => {
          // cache response clone
          const copy = networkRes.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(req, copy));
          return networkRes;
        }).catch(() => caches.match('/prod/index.php'));
      })
    );
    return;
  }

  // For other GET requests (assets), use cache-first, fallback to network and cache
  event.respondWith(
    caches.match(req).then(cached => cached || fetch(req).then(networkRes => {
      caches.open(CACHE_NAME).then(cache => cache.put(req, networkRes.clone()));
      return networkRes;
    }).catch(() => cached))
  );
});
