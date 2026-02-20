/**
 * Core API service — singleton Axios instance with:
 * - JWT Bearer token injection
 * - Correlation ID (X-Correlation-ID) on every request
 * - Idempotency key (Idempotency-Key) on mutating requests (POST/PUT/PATCH)
 * - Silent token refresh with concurrent-request queuing
 * - Automatic redirect to /login on irrecoverable 401
 */
import axios, { type AxiosInstance, type InternalAxiosRequestConfig } from 'axios';
import { generateUUID } from '@/core/utils/uuid';

export const BASE_URL = `${(import.meta.env.VITE_APP_URL as string | undefined) ?? ''}/api/v1`;

// ─── Retry config ──────────────────────────────────────────────────────────
interface RetryConfig extends InternalAxiosRequestConfig {
  _retry?: boolean;
}

let _isRefreshing = false;
let _pendingQueue: Array<(token: string) => void> = [];

function drainQueue(token: string): void {
  _pendingQueue.forEach((cb) => cb(token));
  _pendingQueue = [];
}

/** HTTP methods that mutate state and require idempotency keys (lowercase for matching). */
const MUTATING_METHODS = new Set(['post', 'put', 'patch', 'delete']);

// ─── Axios instance ─────────────────────────────────────────────────────────
const api: AxiosInstance = axios.create({
  baseURL: BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
});

// ─── Request interceptor ────────────────────────────────────────────────────
api.interceptors.request.use((config) => {
  // JWT
  const jwt = localStorage.getItem('jwt_token');
  if (jwt) {
    config.headers.Authorization = `Bearer ${jwt}`;
  }

  // Correlation ID — every request gets a unique trace ID
  config.headers['X-Correlation-ID'] = generateUUID();

  // Idempotency key — mutating requests only
  const method = config.method?.toLowerCase() ?? '';
  if (MUTATING_METHODS.has(method) && !config.headers['Idempotency-Key']) {
    config.headers['Idempotency-Key'] = generateUUID();
  }

  return config;
});

// ─── Response interceptor ───────────────────────────────────────────────────
api.interceptors.response.use(
  (response) => response,
  async (error: unknown) => {
    if (!axios.isAxiosError(error)) return Promise.reject(error);

    const originalConfig = error.config as RetryConfig | undefined;
    if (!originalConfig) return Promise.reject(error);

    if (error.response?.status === 401 && !originalConfig._retry) {
      if (_isRefreshing) {
        return new Promise<unknown>((resolve) => {
          _pendingQueue.push((token: string) => {
            originalConfig.headers.Authorization = `Bearer ${token}`;
            resolve(api(originalConfig));
          });
        });
      }

      originalConfig._retry = true;
      _isRefreshing = true;
      const currentToken = localStorage.getItem('jwt_token');

      try {
        const { data } = await axios.post<{ access_token?: string }>(
          `${BASE_URL}/auth/refresh`,
          null,
          { headers: { Authorization: `Bearer ${currentToken}` } },
        );

        const newToken = data.access_token;
        if (!newToken) throw new Error('Refresh response missing access_token');
        localStorage.setItem('jwt_token', newToken);

        drainQueue(newToken);
        originalConfig.headers.Authorization = `Bearer ${newToken}`;
        return api(originalConfig);
      } catch {
        localStorage.removeItem('jwt_token');
        _pendingQueue = [];
        window.location.href = '/login';
        return Promise.reject(error);
      } finally {
        _isRefreshing = false;
      }
    }

    return Promise.reject(error);
  },
);

export default api;
