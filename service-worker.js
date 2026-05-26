const CACHE_NAME='fee-dashboard-v1';
const urls=['/monthly_fee_report.php','/manifest.json','/icons/icon-192.svg','/icons/icon-512.svg'];
self.addEventListener('install',e=>e.waitUntil(caches.open(CACHE_NAME).then(c=>c.addAll(urls))));
self.addEventListener('fetch',e=>e.respondWith(caches.match(e.request).then(r=>r||fetch(e.request).catch(()=>caches.match('/monthly_fee_report.php')))));
