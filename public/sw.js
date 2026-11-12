// Minimal service worker — enables PWA installability.
// Deliberately caches NOTHING: this is a private healthcare app and patient
// data, documents and authenticated responses must never be stored offline.
self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', () => {
    // Network-only. No caching of app data by design.
});
