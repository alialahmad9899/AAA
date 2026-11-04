const cacheName = 'wesal-v1';
const assets = [
  '/',
  '/index.html',
  '/assets/img/app-icon-512.png',
  // أضف هنا مسارات ملفات الـ CSS والـ JS الرئيسية لموقعك
];

// تثبيت الـ Service Worker وتخزين الملفات
self.addEventListener('install', evt => {
  evt.waitUntil(
    caches.open(cacheName).then(cache => {
      cache.addAll(assets);
    })
  );
});

// جلب الملفات من الكاش لتسريع الفتح
self.addEventListener('fetch', evt => {
  evt.respondWith(
    caches.match(evt.request).then(cacheRes => {
      return cacheRes || fetch(evt.request);
    })
  );
});