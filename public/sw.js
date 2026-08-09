/*
 * Service Worker de Wellness Circle Academy.
 *
 * Alcance: solo dos responsabilidades.
 *  1) Requisito técnico para que el sitio sea instalable (PWA) en Android/iOS.
 *  2) Recibir y mostrar Push Notifications, y abrir la app al tocar una.
 *
 * A propósito NO cachea páginas HTML dinámicas (llevan token CSRF y datos
 * de sesión por usuario — cachearlas podría servir contenido obsoleto o de
 * otra sesión). Solo aplica cache-first a assets estáticos (CSS/íconos) para
 * acelerar cargas repetidas, con fallback a red si no están en cache.
 */

const STATIC_CACHE = 'wca-static-v1';

self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== STATIC_CACHE).map((k) => caches.delete(k)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);
  const isStaticAsset = event.request.method === 'GET' && url.origin === self.location.origin
    && (url.pathname.startsWith('/assets/css/') || url.pathname.startsWith('/assets/img/'));

  if (!isStaticAsset) {
    return; // deja pasar todo lo demás directo a la red (páginas dinámicas, POSTs, etc).
  }

  event.respondWith(
    caches.open(STATIC_CACHE).then((cache) =>
      cache.match(event.request).then((cached) => {
        const network = fetch(event.request)
          .then((response) => {
            if (response && response.status === 200) {
              cache.put(event.request, response.clone());
            }
            return response;
          })
          .catch(() => cached);
        return cached || network;
      })
    )
  );
});

self.addEventListener('push', (event) => {
  let data = { title: 'Wellness Circle Academy', body: '', url: '/dashboard' };
  if (event.data) {
    try {
      data = { ...data, ...event.data.json() };
    } catch (e) {
      data.body = event.data.text();
    }
  }

  event.waitUntil(
    self.registration.showNotification(data.title, {
      body: data.body,
      icon: '/assets/img/icons/icon-192.png',
      badge: '/assets/img/icons/icon-192.png',
      data: { url: data.url || '/dashboard' },
    })
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const targetUrl = (event.notification.data && event.notification.data.url) || '/dashboard';

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        const clientUrl = new URL(client.url);
        if (clientUrl.origin === self.location.origin && 'focus' in client) {
          client.navigate(targetUrl);
          return client.focus();
        }
      }
      return self.clients.openWindow(targetUrl);
    })
  );
});
