self.addEventListener('install', () => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  event.waitUntil((async () => {
    const clientList = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });

    for (const client of clientList) {
      try {
        if ('navigate' in client) {
          await client.navigate('/qwe1/admin.php#message-notifications');
        }
        if ('focus' in client) {
          await client.focus();
          return;
        }
      } catch (error) {
        // swallow navigation/focus errors to avoid noisy runtime.lastError-like warnings
      }
    }

    if (self.clients.openWindow) {
      try {
        await self.clients.openWindow('/qwe1/admin.php#message-notifications');
      } catch (error) {
        // ignored on purpose
      }
    }
  })());
});
