import { ref, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/authStore'

/**
 * Composable for Server-Sent Events (SSE) real-time updates
 * Provides live data streaming from the server for real-time notifications and updates
 */
export function useServerEvents(options = {}) {
  const {
    endpoint = '/api/v1/events/stream',
    autoConnect = true,
    reconnectInterval = 5000,
    maxReconnectAttempts = 10
  } = options
  
  const authStore = useAuthStore()
  
  const isConnected = ref(false)
  const isConnecting = ref(false)
  const error = ref(null)
  const reconnectAttempts = ref(0)
  const lastEventId = ref(null)
  
  let eventSource = null
  let reconnectTimer = null
  
  // Event listeners
  const listeners = new Map()
  
  // Add event listener
  const addEventListener = (event, handler) => {
    if (!listeners.has(event)) {
      listeners.set(event, [])
    }
    listeners.get(event).push(handler)
    
    // If already connected, register with EventSource
    if (eventSource && isConnected.value) {
      eventSource.addEventListener(event, handler)
    }
  }
  
  // Remove event listener
  const removeEventListener = (event, handler) => {
    const handlers = listeners.get(event)
    if (handlers) {
      const index = handlers.indexOf(handler)
      if (index > -1) {
        handlers.splice(index, 1)
      }
    }
    
    if (eventSource) {
      eventSource.removeEventListener(event, handler)
    }
  }
  
  // Emit event to all listeners
  const emit = (event, data) => {
    const handlers = listeners.get(event)
    if (handlers) {
      handlers.forEach(handler => {
        try {
          handler(data)
        } catch (err) {
          console.error(`[SSE] Error in ${event} handler:`, err)
        }
      })
    }
  }
  
  // Build connection URL with auth and params
  const buildUrl = () => {
    const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'
    const url = new URL(endpoint, baseUrl)
    
    // Add auth token
    if (authStore.token) {
      url.searchParams.set('token', authStore.token)
    }
    
    // Add last event ID for reconnection
    if (lastEventId.value) {
      url.searchParams.set('lastEventId', lastEventId.value)
    }
    
    return url.toString()
  }
  
  // Connect to SSE endpoint
  const connect = () => {
    if (isConnected.value || isConnecting.value) {
      return
    }
    
    if (!authStore.isAuthenticated) {
      console.warn('[SSE] Not authenticated, skipping connection')
      return
    }
    
    isConnecting.value = true
    error.value = null
    
    try {
      const url = buildUrl()
      console.log('[SSE] Connecting to:', endpoint)
      
      eventSource = new EventSource(url)
      
      // Connection opened
      eventSource.onopen = () => {
        console.log('[SSE] Connected successfully')
        isConnected.value = true
        isConnecting.value = false
        reconnectAttempts.value = 0
        error.value = null
        
        // Register all existing listeners
        listeners.forEach((handlers, event) => {
          handlers.forEach(handler => {
            eventSource.addEventListener(event, handler)
          })
        })
        
        emit('connected', { timestamp: Date.now() })
      }
      
      // Default message handler
      eventSource.onmessage = (event) => {
        lastEventId.value = event.lastEventId
        
        try {
          const data = JSON.parse(event.data)
          console.log('[SSE] Message received:', data)
          emit('message', data)
        } catch (err) {
          console.error('[SSE] Error parsing message:', err)
        }
      }
      
      // Error handler
      eventSource.onerror = (err) => {
        console.error('[SSE] Connection error:', err)
        
        isConnected.value = false
        isConnecting.value = false
        error.value = 'Connection error'
        
        emit('error', { error: err })
        
        // Attempt reconnection
        if (reconnectAttempts.value < maxReconnectAttempts) {
          scheduleReconnect()
        } else {
          console.error('[SSE] Max reconnection attempts reached')
          error.value = 'Max reconnection attempts reached'
          emit('disconnected', { reason: 'max_attempts' })
        }
      }
      
      // Common events
      setupCommonEvents()
    } catch (err) {
      console.error('[SSE] Connection failed:', err)
      isConnecting.value = false
      error.value = err.message
      scheduleReconnect()
    }
  }
  
  // Setup handlers for common events
  const setupCommonEvents = () => {
    // Notification event
    addEventListener('notification', (event) => {
      const data = typeof event.data === 'string' ? JSON.parse(event.data) : event.data
      console.log('[SSE] Notification:', data)
      emit('notification', data)
    })
    
    // Stock update event
    addEventListener('stock.updated', (event) => {
      const data = typeof event.data === 'string' ? JSON.parse(event.data) : event.data
      console.log('[SSE] Stock updated:', data)
      emit('stock.updated', data)
    })
    
    // Order event
    addEventListener('order.created', (event) => {
      const data = typeof event.data === 'string' ? JSON.parse(event.data) : event.data
      console.log('[SSE] Order created:', data)
      emit('order.created', data)
    })
    
    // Invoice event
    addEventListener('invoice.created', (event) => {
      const data = typeof event.data === 'string' ? JSON.parse(event.data) : event.data
      console.log('[SSE] Invoice created:', data)
      emit('invoice.created', data)
    })
    
    // Payment event
    addEventListener('payment.received', (event) => {
      const data = typeof event.data === 'string' ? JSON.parse(event.data) : event.data
      console.log('[SSE] Payment received:', data)
      emit('payment.received', data)
    })
  }
  
  // Schedule reconnection attempt
  const scheduleReconnect = () => {
    if (reconnectTimer) {
      clearTimeout(reconnectTimer)
    }
    
    reconnectAttempts.value++
    const delay = reconnectInterval * Math.min(reconnectAttempts.value, 5)
    
    console.log(`[SSE] Reconnecting in ${delay}ms (attempt ${reconnectAttempts.value})`)
    
    reconnectTimer = setTimeout(() => {
      if (!isConnected.value) {
        connect()
      }
    }, delay)
  }
  
  // Disconnect from SSE
  const disconnect = () => {
    if (reconnectTimer) {
      clearTimeout(reconnectTimer)
      reconnectTimer = null
    }
    
    if (eventSource) {
      console.log('[SSE] Disconnecting')
      eventSource.close()
      eventSource = null
    }
    
    isConnected.value = false
    isConnecting.value = false
    reconnectAttempts.value = 0
    
    emit('disconnected', { reason: 'manual' })
  }
  
  // Manually trigger reconnection
  const reconnect = () => {
    disconnect()
    setTimeout(() => connect(), 1000)
  }
  
  // Subscribe to specific event
  const on = (event, handler) => {
    addEventListener(event, handler)
    
    // Return unsubscribe function
    return () => removeEventListener(event, handler)
  }
  
  // Initialize
  onMounted(() => {
    if (autoConnect) {
      connect()
    }
  })
  
  // Cleanup
  onUnmounted(() => {
    disconnect()
  })
  
  return {
    // State
    isConnected,
    isConnecting,
    error,
    
    // Methods
    connect,
    disconnect,
    reconnect,
    on,
    
    // Shorthand subscriptions
    onNotification: (handler) => on('notification', handler),
    onStockUpdate: (handler) => on('stock.updated', handler),
    onOrderCreated: (handler) => on('order.created', handler),
    onInvoiceCreated: (handler) => on('invoice.created', handler),
    onPaymentReceived: (handler) => on('payment.received', handler)
  }
}
