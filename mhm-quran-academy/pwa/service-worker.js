const STATIC_CACHE='mhm-static-v2';
const DYNAMIC_CACHE='mhm-dyn-v2';
const OFFLINE_URL='/';
self.addEventListener('install',e=>{e.waitUntil(caches.open(STATIC_CACHE).then(c=>c.addAll([OFFLINE_URL,'/wp-content/themes/mhm-quran-academy/style.css'])));self.skipWaiting();});
self.addEventListener('activate',e=>{e.waitUntil(caches.keys().then(keys=>Promise.all(keys.filter(k=>![STATIC_CACHE,DYNAMIC_CACHE].includes(k)).map(k=>caches.delete(k)))));self.clients.claim();});
self.addEventListener('fetch',e=>{const req=e.request;if(req.method!=='GET')return;e.respondWith(caches.match(req).then(c=>c||fetch(req).then(r=>{const rc=r.clone();caches.open(DYNAMIC_CACHE).then(cache=>cache.put(req,rc));return r;}).catch(()=>caches.match(OFFLINE_URL))));});
self.addEventListener('sync',e=>{if(e.tag==='mhm-sync-progress'){e.waitUntil(Promise.resolve());}});
self.addEventListener('push',e=>{const data=e.data?e.data.json():{title:'MHM Quran',body:'Continue your Quran journey'};e.waitUntil(self.registration.showNotification(data.title,{body:data.body,icon:'/wp-content/themes/mhm-quran-academy/assets/img/icon-192.png'}));});
