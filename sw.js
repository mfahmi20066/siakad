const CACHE_NAME = 'sia-sman4palopo-v2';

// cache statis minimal, biar offline ringan
const STATIC_ASSETS = [
  '/siakad/',
  '/siakad/index.php',
  '/siakad/assets/css/style.css',
  '/siakad/assets/img/logo-sekolah.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(STATIC_ASSETS);
    }).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) return caches.delete(key);
        })
      );
    }).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;

  // cuma GET aja yang diproses
  if (req.method !== 'GET') return;

  const url = new URL(req.url);

  // di luar /siakad/ ga usah disentuh
  if (!url.pathname.startsWith('/siakad/')) return;

  // halaman & PDF pake network-first biar selalu fresh, kalo offline baru fallback ke cache
  if (req.mode === 'navigate' || url.pathname.match(/\.(php|pdf)$/)) {
    event.respondWith(
      fetch(req)
        .then((res) => {
          const copy = res.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
          return res;
        })
        .catch(() => {
          return caches.match(req).then((cached) => {
            return cached || caches.match('/siakad/index.php');
          });
        })
    );
    return;
  }

  // aset statis pake cache-first, biar cepet & bisa offline
  event.respondWith(
    caches.match(req).then((cached) => {
      if (cached) return cached;

      return fetch(req)
        .then((res) => {
          const copy = res.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
          return res;
        })
        .catch(() => {
          return undefined;
        });
    })
  );
});
