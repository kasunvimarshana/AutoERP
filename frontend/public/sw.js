// AutoERP - Service Worker
// Handles push notifications, background sync, and offline caching

const CACHE_NAME = 'autoerp-v1'
const RUNTIME_CACHE = 'autoerp-runtime-v1'

// Assets to cache on install
const STATIC_ASSETS = [
  '/',
  '/index.html',
  '/manifest.json'
]

// Install event - cache static assets
self.addEventListener('install', (event) => {
  console.log('[Service Worker] Installing...')
  
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('[Service Worker] Caching static assets')
        return cache.addAll(STATIC_ASSETS)
      })
      .then(() => self.skipWaiting())
  )
})

// Activate event - clean old caches
self.addEventListener('activate', (event) => {
  console.log('[Service Worker] Activating...')
  
  event.waitUntil(
    caches.keys()
      .then((cacheNames) => {
        return Promise.all(
          cacheNames
            .filter((name) => name !== CACHE_NAME && name !== RUNTIME_CACHE)
            .map((name) => {
              console.log('[Service Worker] Deleting old cache:', name)
              return caches.delete(name)
            })
        )
      })
      .then(() => self.clients.claim())
  )
})

// Fetch event - network first, then cache
self.addEventListener('fetch', (event) => {
  const { request } = event
  const url = new URL(request.url)
  
  // Skip non-GET requests and external requests
  if (request.method !== 'GET' || !url.origin.includes(self.location.origin)) {
    return
  }
  
  // API requests - network first, cache fallback
  if (url.pathname.startsWith('/api/')) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          // Clone response before caching
          const responseClone = response.clone()
          caches.open(RUNTIME_CACHE).then((cache) => {
            cache.put(request, responseClone)
          })
          return response
        })
        .catch(() => {
          return caches.match(request)
        })
    )
    return
  }
  
  // Static assets - cache first, network fallback
  event.respondWith(
    caches.match(request)
      .then((cachedResponse) => {
        if (cachedResponse) {
          return cachedResponse
        }
        
        return fetch(request)
          .then((response) => {
            // Cache successful responses
            if (response.status === 200) {
              const responseClone = response.clone()
              caches.open(RUNTIME_CACHE).then((cache) => {
                cache.put(request, responseClone)
              })
            }
            return response
          })
      })
  )
})

// Push notification event
self.addEventListener('push', (event) => {
  console.log('[Service Worker] Push notification received')
  
  let notification = {
    title: 'AutoERP',
    body: 'You have a new notification',
    icon: '/icon-192.png',
    badge: '/badge-72.png',
    tag: 'default',
    requireInteraction: false,
    data: {}
  }
  
  // Parse notification data
  if (event.data) {
    try {
      const data = event.data.json()
      notification = {
        title: data.title || notification.title,
        body: data.body || data.message || notification.body,
        icon: data.icon || notification.icon,
        badge: data.badge || notification.badge,
        tag: data.tag || data.type || notification.tag,
        requireInteraction: data.requireInteraction || false,
        data: {
          url: data.url || data.action_url || '/',
          notificationId: data.id,
          type: data.type,
          ...data
        },
        actions: data.actions || []
      }
      
      // Add vibration pattern for high priority
      if (data.priority === 'high') {
        notification.vibrate = [200, 100, 200]
        notification.requireInteraction = true
      }
    } catch (error) {
      console.error('[Service Worker] Error parsing push data:', error)
    }
  }
  
  event.waitUntil(
    self.registration.showNotification(notification.title, notification)
  )
})

// Notification click event
self.addEventListener('notificationclick', (event) => {
  console.log('[Service Worker] Notification clicked:', event.notification.tag)
  
  event.notification.close()
  
  const urlToOpen = event.notification.data?.url || '/'
  const notificationId = event.notification.data?.notificationId
  
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then((clientList) => {
        // Check if there's already a window open
        for (const client of clientList) {
          if (client.url.includes(self.location.origin) && 'focus' in client) {
            // Mark notification as read via API
            if (notificationId) {
              markNotificationAsRead(notificationId)
            }
            // Navigate to URL and focus window
            return client.navigate(urlToOpen).then(client => client.focus())
          }
        }
        
        // Open new window if none exists
        if (clients.openWindow) {
          return clients.openWindow(urlToOpen).then((client) => {
            if (notificationId) {
              markNotificationAsRead(notificationId)
            }
            return client
          })
        }
      })
  )
})

// Notification close event
self.addEventListener('notificationclose', (event) => {
  console.log('[Service Worker] Notification closed:', event.notification.tag)
  
  // Track notification dismissal if needed
  const notificationId = event.notification.data?.notificationId
  if (notificationId) {
    // Could track dismissal analytics here
    console.log('[Service Worker] Notification dismissed:', notificationId)
  }
})

// Background sync event
self.addEventListener('sync', (event) => {
  console.log('[Service Worker] Background sync:', event.tag)
  
  if (event.tag === 'sync-notifications') {
    event.waitUntil(syncNotifications())
  }
})

// Helper: Mark notification as read
async function markNotificationAsRead(notificationId) {
  try {
    const token = await getAuthToken()
    if (!token) return
    
    await fetch(`/api/v1/notifications/${notificationId}/read`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    })
    console.log('[Service Worker] Notification marked as read:', notificationId)
  } catch (error) {
    console.error('[Service Worker] Error marking notification as read:', error)
  }
}

// Helper: Get auth token from IndexedDB or localStorage
async function getAuthToken() {
  try {
    // Try to get from clients
    const clients = await self.clients.matchAll()
    if (clients.length > 0) {
      // Send message to client to get token
      return new Promise((resolve) => {
        const messageChannel = new MessageChannel()
        messageChannel.port1.onmessage = (event) => {
          resolve(event.data.token)
        }
        clients[0].postMessage({ type: 'GET_AUTH_TOKEN' }, [messageChannel.port2])
      })
    }
  } catch (error) {
    console.error('[Service Worker] Error getting auth token:', error)
  }
  return null
}

// Helper: Sync notifications from server
async function syncNotifications() {
  try {
    const token = await getAuthToken()
    if (!token) {
      console.log('[Service Worker] No auth token, skipping sync')
      return
    }
    
    const response = await fetch('/api/v1/notifications?unread=true&limit=10', {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      console.log('[Service Worker] Synced notifications:', data.data?.length || 0)
      
      // Show notifications if any
      if (data.data && data.data.length > 0) {
        for (const notification of data.data.slice(0, 3)) {
          await self.registration.showNotification(notification.title || 'New Notification', {
            body: notification.message || notification.body,
            icon: '/icon-192.png',
            badge: '/badge-72.png',
            tag: notification.id,
            data: {
              url: notification.action_url || '/',
              notificationId: notification.id,
              type: notification.type
            }
          })
        }
      }
    }
  } catch (error) {
    console.error('[Service Worker] Error syncing notifications:', error)
  }
}

console.log('[Service Worker] Loaded successfully')
