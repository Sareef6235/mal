const CACHE_NAME = 'fluxstudio-static-v1';
const URLS_TO_CACHE = [
  '/index.php',
  '/assets/css/app.css',
  '/assets/js/app.js',
  '/manifest.json',
];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(URLS_TO_CACHE)));
});

self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request).then((response) => response || fetch(event.request))
  );
});
