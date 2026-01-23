import axios, { type AxiosInstance, type AxiosError, type InternalAxiosRequestConfig } from 'axios'
import { appConfig } from '@/config/app'
import type { ApiResponse, ApiError } from '@/types/api'

/**
 * Enhanced API client with comprehensive error handling,
 * tenant awareness, and token management
 */

class ApiClient {
  private client: AxiosInstance
  private isRefreshing = false
  private failedQueue: Array<{
    resolve: (value?: unknown) => void
    reject: (reason?: unknown) => void
  }> = []

  constructor() {
    this.client = axios.create({
      baseURL: appConfig.apiUrl,
      timeout: 30000,
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
    })

    this.setupInterceptors()
  }

  private setupInterceptors() {
    // Request interceptor
    this.client.interceptors.request.use(
      (config: InternalAxiosRequestConfig) => {
        // Add authentication token
        const token = this.getToken()
        if (token) {
          config.headers.Authorization = `Bearer ${token}`
        }

        // Add tenant ID if available
        const tenantId = this.getTenantId()
        if (tenantId) {
          config.headers[appConfig.tenant.headerKey] = tenantId
        }

        return config
      },
      (error) => {
        return Promise.reject(this.handleError(error))
      },
    )

    // Response interceptor
    this.client.interceptors.response.use(
      (response) => response,
      async (error: AxiosError) => {
        const originalRequest = error.config as InternalAxiosRequestConfig & { _retry?: boolean }

        // Handle 401 Unauthorized
        if (error.response?.status === 401 && !originalRequest._retry) {
          if (this.isRefreshing) {
            // Queue requests while refreshing token
            return new Promise((resolve, reject) => {
              this.failedQueue.push({ resolve, reject })
            })
              .then((token) => {
                originalRequest.headers.Authorization = `Bearer ${token}`
                return this.client(originalRequest)
              })
              .catch((err) => Promise.reject(err))
          }

          originalRequest._retry = true
          this.isRefreshing = true

          // Try to refresh token (if refresh endpoint exists)
          // For now, just logout
          this.handleLogout()
          return Promise.reject(this.handleError(error))
        }

        return Promise.reject(this.handleError(error))
      },
    )
  }

  private handleError(error: AxiosError<ApiResponse>): ApiError {
    const apiError: ApiError = {
      message: 'An unexpected error occurred',
      status: error.response?.status,
    }

    if (error.response) {
      // Server responded with error
      const data = error.response.data
      apiError.message = data?.message || error.message
      apiError.errors = data?.errors
      apiError.status = error.response.status
    } else if (error.request) {
      // Request made but no response
      apiError.message = 'Network error. Please check your connection.'
    } else {
      // Error in request configuration
      apiError.message = error.message
    }

    return apiError
  }

  private handleLogout() {
    localStorage.removeItem(appConfig.auth.tokenKey)
    localStorage.removeItem(appConfig.auth.userKey)
    localStorage.removeItem(appConfig.tenant.storageKey)
    
    // Redirect to login
    if (typeof window !== 'undefined') {
      window.location.href = '/login'
    }
  }

  private getToken(): string | null {
    return localStorage.getItem(appConfig.auth.tokenKey)
  }

  private getTenantId(): string | null {
    return localStorage.getItem(appConfig.tenant.storageKey)
  }

  // Public methods
  public setToken(token: string): void {
    localStorage.setItem(appConfig.auth.tokenKey, token)
  }

  public removeToken(): void {
    localStorage.removeItem(appConfig.auth.tokenKey)
  }

  public setTenantId(tenantId: string): void {
    localStorage.setItem(appConfig.tenant.storageKey, tenantId)
  }

  public removeTenantId(): void {
    localStorage.removeItem(appConfig.tenant.storageKey)
  }

  // Expose axios instance
  public get instance(): AxiosInstance {
    return this.client
  }
}

// Export singleton instance
const apiClient = new ApiClient()
export default apiClient.instance
export { apiClient }
