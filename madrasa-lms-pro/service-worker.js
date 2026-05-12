const MLP_CACHE = 'madrasa-lms-pro-v1';
self.addEventListener('install', event => {
  event.waitUntil(caches.open(MLP_CACHE).then(cache => cache.addAll(['/'])));
});
self.addEventListener('fetch', event => {
  event.respondWith(fetch(event.request).catch(() => caches.match(event.request)));
});
