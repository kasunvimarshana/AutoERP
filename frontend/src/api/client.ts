import axios, { AxiosInstance, AxiosError, InternalAxiosRequestConfig } from 'axios';
import type { ApiResponse, ApiError } from '@/types';
import type { ApiErrorResponse } from '@/types/api';
import type { Router } from 'vue-router';

class ApiClient {
  private instance: AxiosInstance;
  private authToken: string | null = null;
  private tenantId: number | null = null;
  private router: Router | null = null;
  private tokenExpiresAt: number | null = null;
  private refreshPromise: Promise<string> | null = null;
  private refreshTimerId: ReturnType<typeof setInterval> | null = null;

  constructor() {
    this.instance = axios.create({
      baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      timeout: 30000,
    });

    this.setupInterceptors();
    this.startTokenRefreshTimer();
  }

  /**
   * Initialize router instance for navigation
   * Must be called during app initialization
   */
  public initializeRouter(router: Router): void {
    this.router = router;
  }

  private setupInterceptors(): void {
    // Request interceptor
    this.instance.interceptors.request.use(
      async (config: InternalAxiosRequestConfig) => {
        // Check if token needs refresh (5 minutes before expiry)
        if (this.authToken && this.tokenExpiresAt) {
          const now = Date.now();
          const timeUntilExpiry = this.tokenExpiresAt - now;
          const fiveMinutes = 5 * 60 * 1000;

          // Refresh if token expires in less than 5 minutes
          if (timeUntilExpiry < fiveMinutes && timeUntilExpiry > 0) {
            try {
              await this.refreshToken();
            } catch (err) {
              console.error('Token refresh failed:', err);
            }
          }
        }

        // Add auth token
        if (this.authToken) {
          config.headers.Authorization = `Bearer ${this.authToken}`;
        }

        // Add tenant context
        if (this.tenantId) {
          config.headers['X-Tenant-ID'] = this.tenantId.toString();
        }

        // Add request timestamp for debugging
        if (import.meta.env.DEV) {
          config.headers['X-Request-Time'] = new Date().toISOString();
        }

        return config;
      },
      (error) => {
        return Promise.reject(error);
      }
    );

    // Response interceptor
    this.instance.interceptors.response.use(
      (response) => {
        return response;
      },
      (error: AxiosError) => {
        const apiError = this.handleError(error);

        // Handle 401 Unauthorized - token expired
        if (error.response?.status === 401) {
          this.clearAuth();
          // Use router if available, fallback to window.location
          if (this.router) {
            // Don't await - let navigation happen asynchronously
            this.router.push({ name: 'login', query: { redirect: this.router.currentRoute.value.fullPath } }).catch(console.error);
          } else {
            window.location.href = '/login';
          }
        }

        // Handle 403 Forbidden - insufficient permissions
        if (error.response?.status === 403) {
          console.error('Access denied:', apiError.message);
          if (this.router) {
            // Don't await - let navigation happen asynchronously
            this.router.push({ name: 'unauthorized' }).catch(console.error);
          }
        }

        // Handle 419 - CSRF token mismatch
        if (error.response?.status === 419) {
          console.error('CSRF token mismatch - refreshing page');
          window.location.reload();
        }

        // Handle timeout errors
        if (error.code === 'ECONNABORTED') {
          apiError.message = 'Request timeout. Please try again.';
        }

        // Handle network errors
        if (!error.response) {
          apiError.message = 'Network error. Please check your connection.';
        }

        return Promise.reject(apiError);
      }
    );
  }

  private handleError(error: AxiosError): ApiError {
    if (error.response) {
      const errorData = error.response.data as ApiErrorResponse;
      return {
        message: errorData.message || 'An error occurred',
        errors: errorData.errors,
        status: error.response.status,
      };
    }

    if (error.request) {
      return {
        message: 'No response from server. Please check your connection.',
        status: 0,
      };
    }

    return {
      message: error.message || 'An unexpected error occurred',
      status: 0,
    };
  }

  // Auth methods
  setAuthToken(token: string, expiresIn: number = 3600): void {
    this.authToken = token;
    // Calculate expiry timestamp (expiresIn is in seconds)
    this.tokenExpiresAt = Date.now() + (expiresIn * 1000);
    localStorage.setItem('auth_token', token);
    localStorage.setItem('token_expires_at', this.tokenExpiresAt.toString());
  }

  getAuthToken(): string | null {
    if (!this.authToken) {
      this.authToken = localStorage.getItem('auth_token');
      const expiresAt = localStorage.getItem('token_expires_at');
      this.tokenExpiresAt = expiresAt ? parseInt(expiresAt, 10) : null;
    }
    return this.authToken;
  }

  clearAuth(): void {
    this.stopTokenRefreshTimer(); // Clear the refresh timer
    this.authToken = null;
    this.tenantId = null;
    this.tokenExpiresAt = null;
    this.refreshPromise = null;
    localStorage.removeItem('auth_token');
    localStorage.removeItem('tenant_id');
    localStorage.removeItem('token_expires_at');
  }

  /**
   * Refresh the authentication token
   * Uses a promise to prevent multiple simultaneous refresh requests
   */
  private async refreshToken(): Promise<string> {
    // If a refresh is already in progress, return that promise
    if (this.refreshPromise) {
      return this.refreshPromise;
    }

    // Create a new refresh promise
    this.refreshPromise = (async () => {
      try {
        const response = await this.instance.post('/auth/refresh');
        const newToken = response.data.data.access_token;
        const expiresIn = response.data.data.expires_in || 3600;
        
        this.setAuthToken(newToken, expiresIn);
        
        return newToken;
      } catch (error) {
        // If refresh fails, clear auth and redirect to login
        this.clearAuth();
        if (this.router) {
          this.router.push({ name: 'login' }).catch(console.error);
        }
        throw error;
      } finally {
        this.refreshPromise = null;
      }
    })();

    return this.refreshPromise;
  }

  /**
   * Start a timer to automatically refresh the token before it expires
   */
  private startTokenRefreshTimer(): void {
    // Clear any existing timer first to avoid duplicates
    this.stopTokenRefreshTimer();
    
    this.refreshTimerId = setInterval(() => {
      if (this.authToken && this.tokenExpiresAt) {
        const now = Date.now();
        const timeUntilExpiry = this.tokenExpiresAt - now;
        const tenMinutes = 10 * 60 * 1000;

        // Refresh if token expires in less than 10 minutes
        if (timeUntilExpiry < tenMinutes && timeUntilExpiry > 0) {
          this.refreshToken().catch(console.error);
        }
      }
    }, 60000); // Check every minute
  }

  /**
   * Stop the token refresh timer
   */
  private stopTokenRefreshTimer(): void {
    if (this.refreshTimerId !== null) {
      clearInterval(this.refreshTimerId);
      this.refreshTimerId = null;
    }
  }

  // Tenant methods
  setTenantId(tenantId: number): void {
    this.tenantId = tenantId;
    localStorage.setItem('tenant_id', tenantId.toString());
  }

  getTenantId(): number | null {
    if (!this.tenantId) {
      const stored = localStorage.getItem('tenant_id');
      this.tenantId = stored ? parseInt(stored, 10) : null;
    }
    return this.tenantId;
  }

  // HTTP methods
  async get<T = any>(url: string, config = {}): Promise<ApiResponse<T>> {
    const response = await this.instance.get<ApiResponse<T>>(url, config);
    return response.data;
  }

  async post<T = any>(url: string, data = {}, config = {}): Promise<ApiResponse<T>> {
    const response = await this.instance.post<ApiResponse<T>>(url, data, config);
    return response.data;
  }

  async put<T = any>(url: string, data = {}, config = {}): Promise<ApiResponse<T>> {
    const response = await this.instance.put<ApiResponse<T>>(url, data, config);
    return response.data;
  }

  async patch<T = any>(url: string, data = {}, config = {}): Promise<ApiResponse<T>> {
    const response = await this.instance.patch<ApiResponse<T>>(url, data, config);
    return response.data;
  }

  async delete<T = any>(url: string, config = {}): Promise<ApiResponse<T>> {
    const response = await this.instance.delete<ApiResponse<T>>(url, config);
    return response.data;
  }

  // File upload
  async uploadFile<T = any>(url: string, file: File, onProgress?: (progress: number) => void): Promise<ApiResponse<T>> {
    const formData = new FormData();
    formData.append('file', file);

    const response = await this.instance.post<ApiResponse<T>>(url, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
      onUploadProgress: (progressEvent) => {
        if (onProgress && progressEvent.total) {
          const progress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
          onProgress(progress);
        }
      },
    });

    return response.data;
  }

  // Get raw instance for advanced usage
  getInstance(): AxiosInstance {
    return this.instance;
  }
}

// Export singleton instance
export const apiClient = new ApiClient();

// Export default for convenience
export default apiClient;
