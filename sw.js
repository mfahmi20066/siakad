const CACHE_NAME = 'sia-sman4palopo-v2';

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

  const url = new URL(req.url);

  // hanya dalam scope /siakad/
  if (!url.pathname.startsWith('/siakad/')) return;

  // Navigasi halaman & PDF: NETWORK-FIRST supaya halaman cetak/rapor dan PDF
  // SELALU terbaru (tidak tertimpa cache lama). Jaringan gagal -> fallback cache.
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

  // Aset statis (css/js/gambar): cache-first untuk performa & offline
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
