// Service Worker for Web Push Notifications
// This file handles push notifications in the background

const CACHE_VERSION = 'v1';
const CACHE_NAME = `autoerp-${CACHE_VERSION}`;

// Install Service Worker
self.addEventListener('install', (event) => {
  console.log('[Service Worker] Installing...');
  self.skipWaiting();
});

// Activate Service Worker
self.addEventListener('activate', (event) => {
  console.log('[Service Worker] Activating...');
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter((cacheName) => cacheName !== CACHE_NAME)
          .map((cacheName) => caches.delete(cacheName))
      );
    })
  );
  return self.clients.claim();
});

// Handle Push Notifications
self.addEventListener('push', (event) => {
  console.log('[Service Worker] Push received:', event);

  let notificationData = {
    title: 'AutoERP Notification',
    body: 'You have a new notification',
    icon: '/images/logo.png',
    badge: '/images/badge.png',
    data: {},
  };

  if (event.data) {
    try {
      const payload = event.data.json();
      notificationData = {
        title: payload.title || notificationData.title,
        body: payload.body || notificationData.body,
        icon: payload.icon || notificationData.icon,
        badge: payload.badge || notificationData.badge,
        data: payload.data || {},
        tag: payload.tag || 'default',
        requireInteraction: payload.requireInteraction || false,
        actions: payload.actions || [],
      };
    } catch (error) {
      console.error('[Service Worker] Error parsing notification data:', error);
      notificationData.body = event.data.text();
    }
  }

  const promiseChain = self.registration.showNotification(
    notificationData.title,
    {
      body: notificationData.body,
      icon: notificationData.icon,
      badge: notificationData.badge,
      data: notificationData.data,
      tag: notificationData.tag,
      requireInteraction: notificationData.requireInteraction,
      actions: notificationData.actions,
      vibrate: [200, 100, 200], // Vibration pattern
      timestamp: Date.now(),
    }
  );

  event.waitUntil(promiseChain);
});

// Handle Notification Click
self.addEventListener('notificationclick', (event) => {
  console.log('[Service Worker] Notification clicked:', event);

  event.notification.close();

  // Determine the URL to open
  let urlToOpen = '/';
  
  if (event.notification.data && event.notification.data.url) {
    urlToOpen = event.notification.data.url;
  } else if (event.action) {
    // Handle action buttons
    const action = event.notification.actions.find((a) => a.action === event.action);
    if (action && action.url) {
      urlToOpen = action.url;
    }
  }

  // Open the URL in a new window or focus existing window
  const promiseChain = clients
    .matchAll({
      type: 'window',
      includeUncontrolled: true,
    })
    .then((clientList) => {
      // Check if there's already a window open
      for (const client of clientList) {
        if (client.url === urlToOpen && 'focus' in client) {
          return client.focus();
        }
      }

      // Open a new window if none found
      if (clients.openWindow) {
        return clients.openWindow(urlToOpen);
      }
    });

  event.waitUntil(promiseChain);
});

// Handle Notification Close
self.addEventListener('notificationclose', (event) => {
  console.log('[Service Worker] Notification closed:', event);
  
  // Optional: Track notification dismissals
  if (event.notification.data && event.notification.data.trackClose) {
    // Send analytics or tracking data
  }
});

// Handle Background Sync (for offline support)
self.addEventListener('sync', (event) => {
  console.log('[Service Worker] Background sync:', event);
  
  if (event.tag === 'sync-notifications') {
    event.waitUntil(syncNotifications());
  }
});

// Sync notifications when back online
async function syncNotifications() {
  try {
    // Fetch pending notifications from the server
    const response = await fetch('/api/v1/notifications/pending', {
      credentials: 'include',
    });
    
    if (response.ok) {
      const notifications = await response.json();
      
      // Show each notification
      for (const notification of notifications.data || []) {
        await self.registration.showNotification(notification.title, {
          body: notification.body,
          icon: notification.icon,
          badge: notification.badge,
          data: notification.data,
        });
      }
    }
  } catch (error) {
    console.error('[Service Worker] Error syncing notifications:', error);
  }
}

// Handle Messages from Main Thread
self.addEventListener('message', (event) => {
  console.log('[Service Worker] Message received:', event.data);

  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }

  if (event.data && event.data.type === 'GET_VERSION') {
    event.ports[0].postMessage({ version: CACHE_VERSION });
  }
});

// Fetch Event (for offline support - optional)
self.addEventListener('fetch', (event) => {
  // Only handle GET requests
  if (event.request.method !== 'GET') {
    return;
  }

  // Skip API requests from caching
  if (event.request.url.includes('/api/')) {
    return;
  }

  // Cache-first strategy for static assets
  event.respondWith(
    caches.match(event.request).then((response) => {
      if (response) {
        return response;
      }

      return fetch(event.request).then((response) => {
        // Don't cache if not a success response
        if (!response || response.status !== 200 || response.type !== 'basic') {
          return response;
        }

        // Clone the response
        const responseToCache = response.clone();

        caches.open(CACHE_NAME).then((cache) => {
          cache.put(event.request, responseToCache);
        });

        return response;
      });
    })
  );
});
