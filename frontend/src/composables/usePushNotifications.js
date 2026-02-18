import { ref, computed, onMounted } from 'vue'
import apiClient from '@/services/api'

/**
 * Composable for managing Web Push notifications
 * Handles service worker registration, push subscriptions, and notification permissions
 */
export function usePushNotifications() {
  const isSupported = ref(false)
  const isSubscribed = ref(false)
  const isLoading = ref(false)
  const permission = ref('default')
  const subscription = ref(null)
  const error = ref(null)
  
  // Check if push notifications are supported
  const checkSupport = () => {
    isSupported.value = (
      'serviceWorker' in navigator &&
      'PushManager' in window &&
      'Notification' in window
    )
    
    if (isSupported.value) {
      permission.value = Notification.permission
    }
    
    return isSupported.value
  }
  
  // Register service worker
  const registerServiceWorker = async () => {
    if (!isSupported.value) {
      throw new Error('Service Worker not supported')
    }
    
    try {
      const registration = await navigator.serviceWorker.register('/sw.js', {
        scope: '/'
      })
      
      console.log('[Push] Service Worker registered:', registration.scope)
      
      // Wait for service worker to be ready
      await navigator.serviceWorker.ready
      
      // Setup message handler for auth token requests
      navigator.serviceWorker.addEventListener('message', (event) => {
        if (event.data?.type === 'GET_AUTH_TOKEN') {
          const token = localStorage.getItem('auth_token')
          event.ports[0].postMessage({ token })
        }
      })
      
      return registration
    } catch (err) {
      console.error('[Push] Service Worker registration failed:', err)
      error.value = err.message
      throw err
    }
  }
  
  // Request notification permission
  const requestPermission = async () => {
    if (!isSupported.value) {
      throw new Error('Notifications not supported')
    }
    
    try {
      const result = await Notification.requestPermission()
      permission.value = result
      
      if (result === 'granted') {
        console.log('[Push] Notification permission granted')
        return true
      } else if (result === 'denied') {
        console.warn('[Push] Notification permission denied')
        error.value = 'Notification permission denied'
        return false
      } else {
        console.log('[Push] Notification permission dismissed')
        return false
      }
    } catch (err) {
      console.error('[Push] Error requesting permission:', err)
      error.value = err.message
      throw err
    }
  }
  
  // Convert VAPID public key to Uint8Array
  const urlBase64ToUint8Array = (base64String) => {
    const padding = '='.repeat((4 - base64String.length % 4) % 4)
    const base64 = (base64String + padding)
      .replace(/-/g, '+')
      .replace(/_/g, '/')
    
    const rawData = window.atob(base64)
    const outputArray = new Uint8Array(rawData.length)
    
    for (let i = 0; i < rawData.length; ++i) {
      outputArray[i] = rawData.charCodeAt(i)
    }
    return outputArray
  }
  
  // Subscribe to push notifications
  const subscribe = async () => {
    if (!isSupported.value) {
      throw new Error('Push notifications not supported')
    }
    
    if (permission.value !== 'granted') {
      const granted = await requestPermission()
      if (!granted) {
        throw new Error('Notification permission not granted')
      }
    }
    
    isLoading.value = true
    error.value = null
    
    try {
      // Get service worker registration
      const registration = await navigator.serviceWorker.ready
      
      // Check if already subscribed
      let pushSubscription = await registration.pushManager.getSubscription()
      
      if (!pushSubscription) {
        // Get VAPID public key from backend
        const { data } = await apiClient.get('/notifications/push/vapid-key')
        const vapidPublicKey = data.public_key
        
        // Subscribe to push
        pushSubscription = await registration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
        })
      }
      
      // Send subscription to backend
      const subscriptionData = {
        endpoint: pushSubscription.endpoint,
        keys: {
          p256dh: btoa(String.fromCharCode(...new Uint8Array(pushSubscription.getKey('p256dh')))),
          auth: btoa(String.fromCharCode(...new Uint8Array(pushSubscription.getKey('auth'))))
        }
      }
      
      await apiClient.post('/notifications/push/subscribe', subscriptionData)
      
      subscription.value = pushSubscription
      isSubscribed.value = true
      
      console.log('[Push] Successfully subscribed to push notifications')
      return pushSubscription
    } catch (err) {
      console.error('[Push] Error subscribing:', err)
      error.value = err.message || 'Failed to subscribe to notifications'
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  // Unsubscribe from push notifications
  const unsubscribe = async () => {
    if (!isSupported.value || !isSubscribed.value) {
      return
    }
    
    isLoading.value = true
    error.value = null
    
    try {
      const registration = await navigator.serviceWorker.ready
      const pushSubscription = await registration.pushManager.getSubscription()
      
      if (pushSubscription) {
        // Unsubscribe from push
        await pushSubscription.unsubscribe()
        
        // Notify backend
        await apiClient.post('/notifications/push/unsubscribe', {
          endpoint: pushSubscription.endpoint
        })
        
        subscription.value = null
        isSubscribed.value = false
        
        console.log('[Push] Successfully unsubscribed from push notifications')
      }
    } catch (err) {
      console.error('[Push] Error unsubscribing:', err)
      error.value = err.message || 'Failed to unsubscribe from notifications'
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  // Check if user is already subscribed
  const checkSubscription = async () => {
    if (!isSupported.value) {
      return false
    }
    
    try {
      const registration = await navigator.serviceWorker.ready
      const pushSubscription = await registration.pushManager.getSubscription()
      
      if (pushSubscription) {
        subscription.value = pushSubscription
        isSubscribed.value = true
        return true
      }
      
      isSubscribed.value = false
      return false
    } catch (err) {
      console.error('[Push] Error checking subscription:', err)
      return false
    }
  }
  
  // Send test notification
  const sendTestNotification = async () => {
    if (!isSubscribed.value) {
      throw new Error('Not subscribed to push notifications')
    }
    
    try {
      await apiClient.post('/notifications/push/test', {
        title: 'Test Notification',
        body: 'This is a test notification from AutoERP',
        icon: '/icon-192.png'
      })
      
      console.log('[Push] Test notification sent')
    } catch (err) {
      console.error('[Push] Error sending test notification:', err)
      error.value = err.message || 'Failed to send test notification'
      throw err
    }
  }
  
  // Request notification permission and subscribe
  const enableNotifications = async () => {
    try {
      await registerServiceWorker()
      await subscribe()
      return true
    } catch (err) {
      console.error('[Push] Error enabling notifications:', err)
      return false
    }
  }
  
  // Computed
  const canEnableNotifications = computed(() => {
    return isSupported.value && !isSubscribed.value && permission.value !== 'denied'
  })
  
  const isBlocked = computed(() => {
    return permission.value === 'denied'
  })
  
  // Initialize on mount
  onMounted(async () => {
    checkSupport()
    
    if (isSupported.value) {
      try {
        await registerServiceWorker()
        await checkSubscription()
      } catch (err) {
        console.error('[Push] Initialization error:', err)
      }
    }
  })
  
  return {
    // State
    isSupported,
    isSubscribed,
    isLoading,
    permission,
    subscription,
    error,
    
    // Computed
    canEnableNotifications,
    isBlocked,
    
    // Methods
    registerServiceWorker,
    requestPermission,
    subscribe,
    unsubscribe,
    checkSubscription,
    sendTestNotification,
    enableNotifications
  }
}
