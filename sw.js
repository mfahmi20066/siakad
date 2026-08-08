const CACHE_NAME = 'sia-sman4palopo-v1';

// Cache statis minimal (untuk performa & offline ringan)
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

  // hanya tangani GET
  if (req.method !== 'GET') return;

  event.respondWith(
    caches.match(req).then((cached) => {
      if (cached) return cached;

      return fetch(req)
        .then((res) => {
          // Cache response untuk request yang sama dengan aplikasi (path dalam scope)
          const url = new URL(req.url);
          if (url.pathname.startsWith('/siakad/')) {
            const copy = res.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
          }
          return res;
        })
        .catch(() => {
          // fallback: kalau offline dan request adalah root, kirim halaman awal
          if (req.mode === 'navigate') {
            return caches.match('/siakad/index.php');
          }
          return undefined;
        });
    })
  );
});

