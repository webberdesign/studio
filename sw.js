/*  PAGE NAME: sw.js
    SECTION: Barebones SW
------------------------------------------------------------*/
self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(clients.claim());
});

self.addEventListener('fetch', () => {
  // passthrough for now – you can add caching later
});