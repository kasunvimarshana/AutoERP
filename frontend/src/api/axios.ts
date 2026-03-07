import axios, { AxiosError, InternalAxiosRequestConfig } from 'axios';

const BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000';

export const apiClient = axios.create({
  baseURL: `${BASE_URL}/api`,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  timeout: 30000,
});

// Token storage helpers
export const tokenStorage = {
  get: (): string | null => localStorage.getItem('auth_token'),
  set: (token: string): void => localStorage.setItem('auth_token', token),
  remove: (): void => localStorage.removeItem('auth_token'),
};

export const tenantStorage = {
  get: (): string | null => localStorage.getItem('tenant_id'),
  set: (id: string): void => localStorage.setItem('tenant_id', id),
  remove: (): void => localStorage.removeItem('tenant_id'),
};

// Request interceptor – attach auth token and tenant ID
apiClient.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = tokenStorage.get();
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    const tenantId = tenantStorage.get();
    if (tenantId) {
      config.headers['X-Tenant-ID'] = tenantId;
    }
    return config;
  },
  (error: AxiosError) => Promise.reject(error),
);

// Response interceptor – handle 401 and redirect to login
apiClient.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    if (error.response?.status === 401) {
      tokenStorage.remove();
      tenantStorage.remove();
      // Only redirect if we are not already on the login page
      if (!window.location.pathname.startsWith('/login')) {
        window.location.href = '/login';
      }
    }
    return Promise.reject(error);
  },
);

export default apiClient;
