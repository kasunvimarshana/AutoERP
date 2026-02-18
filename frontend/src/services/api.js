import axios from 'axios'

// Create axios instance with default config
const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1',
  timeout: 30000, // Increased timeout for production
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Generate unique request ID for tracking
const generateRequestId = () => {
  return `req_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
}

// Request interceptor
apiClient.interceptors.request.use(
  (config) => {
    // Add unique request ID for tracking
    config.headers['X-Request-ID'] = generateRequestId()
    
    // Add auth token if available
    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    
    // Add tenant context if available
    const user = JSON.parse(localStorage.getItem('user') || '{}')
    if (user.tenant_id) {
      config.headers['X-Tenant-ID'] = user.tenant_id
    }
    if (user.tenant?.slug) {
      config.headers['X-Tenant-Slug'] = user.tenant.slug
    }
    
    // Add timezone header
    config.headers['X-Timezone'] = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC'
    
    // Add locale header
    const locale = localStorage.getItem('locale') || 'en'
    config.headers['X-Locale'] = locale
    
    // Log request in development
    if (import.meta.env.DEV) {
      console.log('[API Request]', {
        method: config.method?.toUpperCase(),
        url: config.url,
        requestId: config.headers['X-Request-ID'],
        data: config.data,
        params: config.params
      })
    }
    
    return config
  },
  (error) => {
    console.error('[API Request Error]', error)
    return Promise.reject(error)
  }
)

// Response interceptor
apiClient.interceptors.response.use(
  (response) => {
    // Log response in development
    if (import.meta.env.DEV) {
      console.log('[API Response]', {
        url: response.config.url,
        requestId: response.config.headers['X-Request-ID'],
        status: response.status,
        data: response.data
      })
    }
    
    return response.data
  },
  (error) => {
    // Log error in development
    if (import.meta.env.DEV) {
      console.error('[API Error]', {
        url: error.config?.url,
        requestId: error.config?.headers['X-Request-ID'],
        status: error.response?.status,
        message: error.response?.data?.message || error.message,
        errors: error.response?.data?.errors
      })
    }
    
    // Handle specific error cases
    if (error.response) {
      const { status, data } = error.response
      
      switch (status) {
        case 401:
          // Unauthorized - clear auth data and redirect to login
          localStorage.removeItem('auth_token')
          localStorage.removeItem('user')
          
          // Avoid redirect loop if already on login page
          if (window.location.pathname !== '/login') {
            window.location.href = `/login?redirect=${encodeURIComponent(window.location.pathname)}`
          }
          break
          
        case 403:
          // Forbidden - user authenticated but lacks permission
          console.warn('[Access Denied]', data?.message || 'Permission denied')
          break
          
        case 404:
          // Not found
          console.warn('[Resource Not Found]', error.config?.url)
          break
          
        case 422:
          // Validation error - already handled by response data
          break
          
        case 429:
          // Rate limit exceeded
          console.warn('[Rate Limit]', 'Too many requests')
          break
          
        case 500:
        case 502:
        case 503:
        case 504:
          // Server errors
          console.error('[Server Error]', {
            status,
            message: data?.message || 'Server error occurred'
          })
          break
      }
    } else if (error.request) {
      // Request made but no response received
      console.error('[Network Error]', 'No response received from server')
    }
    
    return Promise.reject(error)
  }
)

export default apiClient

