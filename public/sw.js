/**
 * Minimal service worker for PWA: offline fallback and caching of static assets.
 * Scope: same-origin. Register from your app (e.g. app.blade.php or app.js).
 */
const CACHE_NAME = 'admin-panel-v1';
const OSM_TILE_CACHE = 'osm-tiles-v1';
const OSM_TILE_HOSTS = new Set([
  'tile.openstreetmap.org',
  'a.tile.openstreetmap.org',
  'b.tile.openstreetmap.org',
  'c.tile.openstreetmap.org',
  'api.mapbox.com',
  'tile.thunderforest.com',
  'tiles.stadiamaps.com'
]);
const OSM_MAX_ENTRIES = 800;

const isOsmTileRequest = (requestUrl) => {
  try {
    const url = new URL(requestUrl);
    return OSM_TILE_HOSTS.has(url.hostname);
  } catch {
    return false;
  }
};

const limitCacheEntries = async (cacheName, maxEntries) => {
  const cache = await caches.open(cacheName);
  const keys = await cache.keys();
  if (keys.length <= maxEntries) return;
  const excess = keys.length - maxEntries;
  await Promise.all(keys.slice(0, excess).map((key) => cache.delete(key)));
};

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll([
        '/',
        '/manifest.json'
      ]).catch(() => {});
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  if (isOsmTileRequest(event.request.url)) {
    event.respondWith((async () => {
      const cache = await caches.open(OSM_TILE_CACHE);
      const cached = await cache.match(event.request, { ignoreVary: true, ignoreSearch: false });
      const networkFetch = fetch(event.request)
        .then((response) => {
          if (response && response.ok) {
            cache.put(event.request, response.clone());
            limitCacheEntries(OSM_TILE_CACHE, OSM_MAX_ENTRIES).catch(() => {});
          }
          return response;
        })
        .catch(() => cached);

      return cached || networkFetch;
    })());
    return;
  }

  if (event.request.mode !== 'navigate') return;
  event.respondWith(
    fetch(event.request).catch(() => {
      return caches.match(event.request).then((cached) => {
        return cached || caches.match('/').then((r) => r || new Response('Offline', { status: 503, statusText: 'Service Unavailable' }));
      });
    })
  );
});
