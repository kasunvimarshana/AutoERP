import axios, { type AxiosInstance, type InternalAxiosRequestConfig, type AxiosResponse } from 'axios'
import type { ApiResponse } from '@/types/api'

const BASE_URL = (import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000') + '/api/v1'

/** Singleton Axios instance pre-configured for the ERP/CRM API. */
const httpClient: AxiosInstance = axios.create({
  baseURL: BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  timeout: 30_000,
})

// ── Request interceptor — attach Bearer token ─────────────────────────────────
httpClient.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = localStorage.getItem('access_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }

    // Forward tenant resolution header when present
    const tenantSlug = localStorage.getItem('tenant_slug')
    if (tenantSlug) {
      config.headers['X-Tenant-Slug'] = tenantSlug
    }

    return config
  },
  (error) => Promise.reject(error),
)

// ── Response interceptor — unwrap envelope / handle 401 ──────────────────────
httpClient.interceptors.response.use(
  (response: AxiosResponse<ApiResponse>) => response,
  async (error) => {
    if (error.response?.status === 401) {
      // Clear stored tokens and redirect to login on 401
      localStorage.removeItem('access_token')
      localStorage.removeItem('tenant_slug')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  },
)

export default httpClient
