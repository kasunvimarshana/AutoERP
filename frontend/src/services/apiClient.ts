import axios, { AxiosError } from 'axios';

/**
 * Pre-configured Axios instance for the API Gateway.
 *
 * Automatically injects:
 *   - Bearer token from localStorage
 *   - X-Tenant-ID header from localStorage
 */
const apiClient = axios.create({
  baseURL: '/api/v1',
  headers: {
    'Content-Type': 'application/json',
    Accept:         'application/json',
  },
  timeout: 15000,
});

// ── Request interceptor: attach auth headers ─────────────────────────────────
apiClient.interceptors.request.use((config) => {
  const token    = localStorage.getItem('auth_token');
  const authUser = localStorage.getItem('auth_user');

  if (token) {
    config.headers['Authorization'] = `Bearer ${token}`;
  }

  if (authUser) {
    try {
      const user = JSON.parse(authUser) as { tenantId?: string };
      if (user.tenantId) {
        config.headers['X-Tenant-ID'] = user.tenantId;
      }
    } catch {
      // Malformed stored user – ignore
    }
  }

  return config;
});

// ── Response interceptor: handle 401 ─────────────────────────────────────────
apiClient.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('auth_user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default apiClient;
