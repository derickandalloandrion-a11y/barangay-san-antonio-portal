'use strict';
const CACHE = 'san-antonio-static-v1';
const STATIC = ['./assets/css/site.css','./assets/js/site.js','./assets/images/barangay-seal.png','./manifest.webmanifest'];
self.addEventListener('install', event => event.waitUntil(caches.open(CACHE).then(cache => cache.addAll(STATIC))));
self.addEventListener('activate', event => event.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(key => key !== CACHE).map(key => caches.delete(key))))));
self.addEventListener('fetch', event => {
  const request = event.request;
  if (request.method !== 'GET' || new URL(request.url).origin !== self.location.origin) return;
  const url = new URL(request.url);
  // Never cache PHP pages, account data, forms, or authenticated responses.
  if (url.pathname.endsWith('.php') || url.pathname.includes('/storage/')) return;
  event.respondWith(caches.match(request).then(cached => cached || fetch(request).then(response => {
    if (response.ok && (url.pathname.includes('/assets/') || url.pathname.endsWith('manifest.webmanifest'))) {
      const copy = response.clone(); caches.open(CACHE).then(cache => cache.put(request, copy));
    }
    return response;
  })));
});
