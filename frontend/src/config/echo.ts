import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// Make Pusher available globally for Echo
window.Pusher = Pusher

/**
 * Laravel Echo Configuration
 * 
 * Configures WebSocket connection for real-time events.
 * Uses Pusher protocol with Redis broadcaster on the backend.
 */
export const configureEcho = (authToken: string) => {
  const isSecure = import.meta.env.VITE_PUSHER_SCHEME === 'https'
  const defaultPort = isSecure ? 6001 : 6001
  const defaultWssPort = isSecure ? 443 : 6001

  const echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY || 'local-key',
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
    wsHost: import.meta.env.VITE_PUSHER_HOST || window.location.hostname,
    wsPort: import.meta.env.VITE_PUSHER_PORT ? parseInt(import.meta.env.VITE_PUSHER_PORT) : defaultPort,
    wssPort: import.meta.env.VITE_PUSHER_WSS_PORT ? parseInt(import.meta.env.VITE_PUSHER_WSS_PORT) : defaultWssPort,
    forceTLS: isSecure,
    encrypted: isSecure,
    disableStats: true,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: `${import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${authToken}`,
        Accept: 'application/json',
      },
    },
  })

  return echo
}

/**
 * Get or create Echo instance
 */
let echoInstance: Echo | null = null

export const getEcho = (): Echo | null => {
  return echoInstance
}

export const setEcho = (echo: Echo | null) => {
  echoInstance = echo
}

/**
 * Disconnect Echo and cleanup
 */
export const disconnectEcho = () => {
  if (echoInstance) {
    echoInstance.disconnect()
    echoInstance = null
  }
}
