/**
 * Service Worker
 * نظام إدارة الموظفين
 */

const CACHE_NAME = 'em-system-v1';
const urlsToCache = [
  '/EM_pro/',
  '/EM_pro/assets/css/style.css',
  '/EM_pro/assets/js/main.js',
  '/EM_pro/assets/js/mobile-enhancements.js',
  '/EM_pro/assets/js/dark-mode.js',
  '/EM_pro/includes/header.php',
  '/EM_pro/includes/footer.php'
];

// التثبيت
self.addEventListener('install', function(event) {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(function(cache) {
        return cache.addAll(urlsToCache);
      })
      .catch(function(error) {
        console.log('Cache install failed:', error);
      })
  );
});

// التنشيط
self.addEventListener('activate', function(event) {
  event.waitUntil(
    caches.keys().then(function(cacheNames) {
      return Promise.all(
        cacheNames.map(function(cacheName) {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// جلب البيانات
self.addEventListener('fetch', function(event) {
  // تجاهل طلبات POST و PUT و DELETE
  if (event.request.method !== 'GET') {
    return;
  }
  
  event.respondWith(
    caches.match(event.request)
      .then(function(response) {
        // إرجاع من الـ cache إذا كان موجوداً
        if (response) {
          return response;
        }
        
        // جلب من الشبكة
        return fetch(event.request).then(
          function(response) {
            // التحقق من صحة الاستجابة
            if (!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }
            
            // نسخ الاستجابة للـ cache
            const responseToCache = response.clone();
            
            caches.open(CACHE_NAME)
              .then(function(cache) {
                cache.put(event.request, responseToCache);
              });
            
            return response;
          }
        ).catch(function() {
          // في حالة فشل الاتصال، يمكن إرجاع صفحة offline
          if (event.request.destination === 'document') {
            return caches.match('/EM_pro/offline.html');
          }
        });
      })
  );
});

