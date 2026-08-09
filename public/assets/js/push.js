/*
 * Registro del Service Worker + suscripción a Web Push + botón de
 * instalación en Android. Única pieza de JavaScript de negocio del sitio
 * (autorizado explícitamente por Rub — ver conversación del 2026-08-09).
 * No toca la interactividad del resto del sitio, que sigue siendo 100%
 * HTML+CSS ("radio hack").
 */
(function () {
  'use strict';

  function base64UrlToUint8Array(base64Url) {
    const padding = '='.repeat((4 - (base64Url.length % 4)) % 4);
    const base64 = (base64Url + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = window.atob(base64);
    const out = new Uint8Array(raw.length);
    for (let i = 0; i < raw.length; i++) {
      out[i] = raw.charCodeAt(i);
    }
    return out;
  }

  function meta(name) {
    const el = document.querySelector('meta[name="' + name + '"]');
    return el ? el.getAttribute('content') : '';
  }

  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(function () {
      // Silencioso: la app sigue funcionando como sitio web normal sin SW.
    });
  }

  const enableBtn = document.getElementById('btn-enable-push');
  const installBtn = document.getElementById('btn-install-app');
  const vapidKey = meta('vapid-public-key');
  const csrfToken = meta('csrf-token');

  // ---------- Push notifications ----------
  if (enableBtn && 'serviceWorker' in navigator && 'PushManager' in window && vapidKey) {
    navigator.serviceWorker.ready.then(function (registration) {
      registration.pushManager.getSubscription().then(function (existing) {
        if (!existing && Notification.permission !== 'denied') {
          enableBtn.hidden = false;
        }
      });
    });

    enableBtn.addEventListener('click', function () {
      enableBtn.disabled = true;
      Notification.requestPermission().then(function (permission) {
        if (permission !== 'granted') {
          enableBtn.disabled = false;
          return;
        }
        navigator.serviceWorker.ready.then(function (registration) {
          registration.pushManager
            .subscribe({ userVisibleOnly: true, applicationServerKey: base64UrlToUint8Array(vapidKey) })
            .then(function (subscription) {
              return fetch('/push/suscribir', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ...subscription.toJSON(), _csrf: csrfToken }),
              });
            })
            .then(function () {
              enableBtn.hidden = true;
            })
            .catch(function () {
              enableBtn.disabled = false;
            });
        });
      });
    });
  }

  // ---------- Botón "Instalar app" (Android/Chrome) ----------
  if (installBtn) {
    window.addEventListener('beforeinstallprompt', function (event) {
      event.preventDefault();
      installBtn.hidden = false;
      installBtn.addEventListener('click', function () {
        installBtn.hidden = true;
        event.prompt();
      }, { once: true });
    });
    window.addEventListener('appinstalled', function () {
      installBtn.hidden = true;
    });
  }
})();
