// Service worker for Weather Forecaster Agent
// This allows the application to work offline by caching assets and API responses

const CACHE_NAME = 'weather-forecaster-cache-v1';
const STATIC_ASSETS = [
  '/',
  '/index.php',
  '/build/assets/app.css',
  '/build/assets/app.js',
  '/favicon.ico',
  '/offline.html' // Fallback page when offline
];

// Install event - cache static assets
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Caching static assets');
        return cache.addAll(STATIC_ASSETS);
      })
      .then(() => self.skipWaiting())
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  const cacheWhitelist = [CACHE_NAME];
  
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            console.log('Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', event => {
  // Skip non-GET requests
  if (event.request.method !== 'GET') return;
  
  // Handle API requests differently
  if (event.request.url.includes('/api/')) {
    handleApiRequest(event);
  } else {
    handleStaticRequest(event);
  }
});

// Handle API requests with a network-first strategy
function handleApiRequest(event) {
  event.respondWith(
    fetch(event.request)
      .then(response => {
        // Clone the response to store in cache
        const responseToCache = response.clone();
        
        // Only cache successful responses
        if (response.status === 200) {
          caches.open(CACHE_NAME)
            .then(cache => {
              // Store the response in cache with an expiration time
              cache.put(event.request, responseToCache);
              
              // Also store timestamp for cache invalidation
              const timestamp = Date.now();
              const requestUrl = event.request.url;
              localStorage.setItem(`${requestUrl}_timestamp`, timestamp);
            });
        }
        
        return response;
      })
      .catch(() => {
        // If network request fails, try to get from cache
        return caches.match(event.request)
          .then(cachedResponse => {
            if (cachedResponse) {
              // Check if cached response is still valid
              const requestUrl = event.request.url;
              const timestamp = localStorage.getItem(`${requestUrl}_timestamp`);
              const now = Date.now();
              
              // If cache is less than 3 hours old, use it
              if (timestamp && now - timestamp < 3 * 60 * 60 * 1000) {
                return cachedResponse;
              }
            }
            
            // If no cache or expired, return offline JSON for API
            return new Response(
              JSON.stringify({
                message: "You're currently offline. Please reconnect to get the latest weather data.",
                status: "offline"
              }),
              {
                headers: { 'Content-Type': 'application/json' }
              }
            );
          });
      })
  );
}

// Handle static assets with a cache-first strategy
function handleStaticRequest(event) {
  event.respondWith(
    caches.match(event.request)
      .then(cachedResponse => {
        if (cachedResponse) {
          return cachedResponse;
        }
        
        return fetch(event.request)
          .then(response => {
            // Don't cache non-successful responses
            if (!response || response.status !== 200) {
              return response;
            }
            
            // Clone the response to store in cache
            const responseToCache = response.clone();
            caches.open(CACHE_NAME)
              .then(cache => {
                cache.put(event.request, responseToCache);
              });
            
            return response;
          })
          .catch(() => {
            // If both cache and network fail for HTML, show offline page
            if (event.request.headers.get('accept').includes('text/html')) {
              return caches.match('/offline.html');
            }
            
            return new Response('Offline content unavailable');
          });
      })
  );
}

// Listen for messages from the client
self.addEventListener('message', event => {
  if (event.data === 'skipWaiting') {
    self.skipWaiting();
  }
});
