import axios from 'axios'
import toast from 'react-hot-toast'

const BASE_URL = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000'

const api = axios.create({
  baseURL: BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  timeout: 15000,
})

// ── Request interceptors ───────────────────────────────────────────────────────

api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers['Authorization'] = `Bearer ${token}`
    }

    const tenantId = localStorage.getItem('tenant_id')
    if (tenantId) {
      config.headers['X-Tenant-ID'] = tenantId
    }

    return config
  },
  (error) => Promise.reject(error),
)

// ── Response interceptors ──────────────────────────────────────────────────────

let isRefreshing = false
let failedQueue = []

function processQueue(error, token = null) {
  failedQueue.forEach((prom) => {
    if (error) {
      prom.reject(error)
    } else {
      prom.resolve(token)
    }
  })
  failedQueue = []
}

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config

    if (!error.response) {
      toast.error('Network error – please check your connection.')
      return Promise.reject(error)
    }

    const { status } = error.response

    // ── 401: attempt silent token refresh ────────────────────────────────────
    if (status === 401 && !originalRequest._retry) {
      const refreshToken = localStorage.getItem('refresh_token')

      if (!refreshToken) {
        clearAuthAndRedirect()
        return Promise.reject(error)
      }

      if (isRefreshing) {
        // Queue subsequent requests while a refresh is in flight
        return new Promise((resolve, reject) => {
          failedQueue.push({ resolve, reject })
        })
          .then((token) => {
            originalRequest.headers['Authorization'] = `Bearer ${token}`
            return api(originalRequest)
          })
          .catch((err) => Promise.reject(err))
      }

      originalRequest._retry = true
      isRefreshing = true

      try {
        const { data } = await axios.post(
          `${BASE_URL}/auth/refresh`,
          { refresh_token: refreshToken },
          { headers: { 'Content-Type': 'application/json', Accept: 'application/json' } },
        )

        const newToken = data.access_token ?? data.token
        const newRefreshToken = data.refresh_token ?? refreshToken

        localStorage.setItem('auth_token', newToken)
        localStorage.setItem('refresh_token', newRefreshToken)

        api.defaults.headers.common['Authorization'] = `Bearer ${newToken}`
        originalRequest.headers['Authorization'] = `Bearer ${newToken}`

        processQueue(null, newToken)
        return api(originalRequest)
      } catch (refreshError) {
        processQueue(refreshError, null)
        clearAuthAndRedirect()
        return Promise.reject(refreshError)
      } finally {
        isRefreshing = false
      }
    }

    // ── 403: permission denied ────────────────────────────────────────────────
    if (status === 403) {
      toast.error('Permission denied – you do not have access to this resource.')
      return Promise.reject(error)
    }

    // ── 404: not found ────────────────────────────────────────────────────────
    if (status === 404) {
      console.warn('[API] 404 Not Found:', originalRequest.url)
      return Promise.reject(error)
    }

    // ── 422: validation errors ────────────────────────────────────────────────
    if (status === 422) {
      return Promise.reject(error)
    }

    // ── 500: server error ─────────────────────────────────────────────────────
    if (status >= 500) {
      console.error('[API] Server error:', error.response.data)
      toast.error('An unexpected server error occurred. Please try again later.')
      return Promise.reject(error)
    }

    return Promise.reject(error)
  },
)

function clearAuthAndRedirect() {
  localStorage.removeItem('auth_token')
  localStorage.removeItem('refresh_token')
  localStorage.removeItem('tenant_id')
  localStorage.removeItem('auth_store')
  window.location.href = '/login'
}

export default api
