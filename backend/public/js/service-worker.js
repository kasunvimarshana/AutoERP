/**
 * AutoERP Service Worker
 *
 * Handles push notifications using native Web Push API
 * No third-party services required
 */

// Service worker version - update this when making changes
const VERSION = '1.0.0';

// Install event
self.addEventListener('install', (event) => {
  console.log('Service Worker installing version:', VERSION);
  // Skip waiting to activate immediately
  self.skipWaiting();
});

// Activate event
self.addEventListener('activate', (event) => {
  console.log('Service Worker activating version:', VERSION);
  // Claim all clients immediately
  event.waitUntil(self.clients.claim());
});

// Push event - handle incoming push notifications
self.addEventListener('push', (event) => {
  console.log('Push notification received');

  if (!event.data) {
    console.warn('Push event has no data');
    return;
  }

  try {
    const data = event.data.json();
    console.log('Push notification data:', data);

    const title = data.title || 'AutoERP';
    const options = {
      body: data.body || data.message || 'New notification',
      icon: data.icon || '/images/logo.png',
      badge: data.badge || '/images/badge.png',
      tag: data.tag || 'autoerp-notification',
      requireInteraction: data.requireInteraction || false,
      data: data.data || {},
      actions: [],
      vibrate: [200, 100, 200],
    };

    // Add action buttons if URL is provided
    if (data.data && data.data.url) {
      options.actions.push({
        action: 'open',
        title: 'View',
      });
    }

    options.actions.push({
      action: 'close',
      title: 'Dismiss',
    });

    event.waitUntil(
      self.registration.showNotification(title, options)
    );
  } catch (error) {
    console.error('Error processing push notification:', error);
  }
});

// Notification click event
self.addEventListener('notificationclick', (event) => {
  console.log('Notification clicked:', event.action);

  event.notification.close();

  if (event.action === 'close') {
    return;
  }

  // Open the app or navigate to specific URL
  const urlToOpen = event.notification.data.url || '/';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then((windowClients) => {
        // Check if there's already a window open
        for (let client of windowClients) {
          if (client.url === urlToOpen && 'focus' in client) {
            return client.focus();
          }
        }

        // No existing window, open a new one
        if (clients.openWindow) {
          return clients.openWindow(urlToOpen);
        }
      })
  );
});

// Handle push subscription change
self.addEventListener('pushsubscriptionchange', (event) => {
  console.log('Push subscription changed');

  event.waitUntil(
    self.registration.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlBase64ToUint8Array(
        // This should be replaced with your actual VAPID public key
        self.registration.scope + '/api/v1/push/public-key'
      ),
    })
      .then((subscription) => {
        console.log('Re-subscribed to push notifications');
        // Update subscription on server
        return fetch('/api/v1/push/subscribe', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            subscription: subscription.toJSON(),
          }),
        });
      })
      .catch((error) => {
        console.error('Failed to re-subscribe:', error);
      })
  );
});

// Utility function to convert VAPID key
function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding)
    .replace(/\-/g, '+')
    .replace(/_/g, '/');

  const rawData = atob(base64);
  const outputArray = new Uint8Array(rawData.length);

  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }

  return outputArray;
}

// Log service worker status
console.log('AutoERP Service Worker loaded, version:', VERSION);
