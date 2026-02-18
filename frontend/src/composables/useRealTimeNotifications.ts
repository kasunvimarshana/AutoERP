import { ref, onMounted, onUnmounted } from 'vue'
import { useNotificationStore } from '@/stores/notifications'
import type { Notification } from '@/api/notifications'
import { getEcho } from '@/config/echo'

/**
 * Composable for WebSocket/real-time notifications
 * 
 * Uses Laravel Echo with Pusher for real-time updates.
 * Falls back to polling if WebSocket connection fails.
 */
export function useRealTimeNotifications() {
  const notificationStore = useNotificationStore()
  const isConnected = ref(false)
  const connectionError = ref<string | null>(null)
  let pollInterval: number | null = null
  let channels: any[] = []

  /**
   * Initialize real-time connection
   */
  function connect(userId: number, tenantId: number) {
    try {
      const echo = getEcho()

      if (echo) {
        // Listen to user-specific notifications
        const userChannel = echo.private(`App.Models.User.${userId}`)
          .notification((notification: Notification) => {
            handleNewNotification(notification)
          })
        channels.push(userChannel)

        // Listen to tenant-wide notifications
        const tenantChannel = echo.private(`tenant.${tenantId}.notifications`)
          .listen('.notification.created', (event: { notification: Notification }) => {
            handleNewNotification(event.notification)
          })
        channels.push(tenantChannel)

        // Listen to inventory-specific notifications
        const inventoryChannel = echo.private(`tenant.${tenantId}.inventory`)
          .listen('.inventory.update', (event: any) => {
            console.log('Inventory update:', event)
            // Handle inventory updates (e.g., refresh product list)
          })
          .listen('.product.created', (event: any) => {
            console.log('Product created:', event)
            if (event.notification) {
              handleNewNotification(event.notification)
            }
          })
          .listen('.product.updated', (event: any) => {
            console.log('Product updated:', event)
          })
        channels.push(inventoryChannel)

        // Listen to stock alerts
        const stockAlertsChannel = echo.private(`tenant.${tenantId}.stock-alerts`)
          .listen('.stock.low', (event: any) => {
            console.log('Low stock alert:', event)
            if (event.notification) {
              handleNewNotification(event.notification)
            }
          })
          .listen('.stock.adjustment', (event: any) => {
            console.log('Stock adjustment:', event)
            if (event.notification) {
              handleNewNotification(event.notification)
            }
          })
        channels.push(stockAlertsChannel)

        isConnected.value = true
        connectionError.value = null
        console.log('Real-time notifications connected via WebSocket')
      } else {
        // Echo not available, fallback to polling
        console.warn('Laravel Echo not initialized, falling back to polling')
        startPolling()
      }
    } catch (error: any) {
      connectionError.value = error.message || 'Failed to connect'
      console.error('Failed to connect to real-time notifications:', error)
      
      // Fallback to polling
      startPolling()
    }
  }

  /**
   * Start polling for new notifications (fallback)
   */
  function startPolling() {
    // Clear any existing interval first
    stopPolling();
    
    // Poll every 30 seconds
    pollInterval = window.setInterval(() => {
      notificationStore.updateUnreadCount()
    }, 30000)

    isConnected.value = true
    console.log('Started polling for notifications (fallback mode)')
  }

  /**
   * Stop polling for notifications
   */
  function stopPolling() {
    if (pollInterval !== null) {
      clearInterval(pollInterval);
      pollInterval = null;
    }
  }

  /**
   * Handle incoming notification
   */
  function handleNewNotification(notification: Notification) {
    // Add to store
    notificationStore.addNotification(notification)

    // Play notification sound (if enabled)
    playNotificationSound()

    // Show browser notification (if permitted)
    showBrowserNotification(notification)
  }

  /**
   * Play notification sound
   */
  function playNotificationSound() {
    try {
      const audio = new Audio('/sounds/notification.mp3')
      audio.volume = 0.3
      audio.play().catch(err => {
        // Autoplay might be blocked by browser
        console.log('Could not play notification sound:', err)
      })
    } catch (error) {
      // Sound file might not exist yet
      console.log('Notification sound not available')
    }
  }

  /**
   * Show browser notification
   */
  function showBrowserNotification(notification: Notification) {
    if ('Notification' in window && Notification.permission === 'granted') {
      try {
        new Notification(notification.data.title, {
          body: notification.data.message,
          icon: '/favicon.ico',
          badge: '/favicon.ico',
          tag: notification.id,
          requireInteraction: false,
        })
      } catch (error) {
        console.log('Could not show browser notification:', error)
      }
    }
  }

  /**
   * Request browser notification permission
   */
  async function requestNotificationPermission(): Promise<boolean> {
    if (!('Notification' in window)) {
      return false
    }

    if (Notification.permission === 'granted') {
      return true
    }

    if (Notification.permission !== 'denied') {
      const permission = await Notification.requestPermission()
      return permission === 'granted'
    }

    return false
  }

  /**
   * Disconnect from real-time notifications
   */
  function disconnect() {
    const echo = getEcho()
    
    if (echo && channels.length > 0) {
      // Leave all channels
      channels.forEach(channel => {
        if (channel && typeof channel.stopListening === 'function') {
          channel.stopListening()
        }
      })
      channels = []
    }

    // Stop polling interval if active
    stopPolling()

    isConnected.value = false
    console.log('Real-time notifications disconnected')
  }

  /**
   * Auto-disconnect on unmount
   */
  onUnmounted(() => {
    disconnect()
  })

  return {
    isConnected,
    connectionError,
    connect,
    disconnect,
    requestNotificationPermission,
  }
}
