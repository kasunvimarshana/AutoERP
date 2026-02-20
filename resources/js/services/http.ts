/**
 * Singleton Axios instance for all API calls.
 * Attaches JWT Bearer token on every request.
 * On 401: attempts a silent token refresh once, queuing concurrent requests,
 * then retries — or redirects to /login if refresh also fails.
 */
import axios, { type AxiosInstance, type InternalAxiosRequestConfig } from 'axios';

const BASE_URL = `${(import.meta.env.VITE_APP_URL as string | undefined) ?? ''}/api/v1`;

// Extend config to track retry attempts
interface RetryConfig extends InternalAxiosRequestConfig {
  _retry?: boolean;
}

let _isRefreshing = false;
let _pendingQueue: Array<(token: string) => void> = [];

function drainQueue(token: string): void {
  _pendingQueue.forEach((cb) => cb(token));
  _pendingQueue = [];
}

const http: AxiosInstance = axios.create({
  baseURL: BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
});

http.interceptors.request.use((config) => {
  const jwt = localStorage.getItem('jwt_token');
  if (jwt) {
    config.headers.Authorization = `Bearer ${jwt}`;
  }
  return config;
});

http.interceptors.response.use(
  (response) => response,
  async (error: unknown) => {
    if (!axios.isAxiosError(error)) return Promise.reject(error);

    const originalConfig = error.config as RetryConfig | undefined;
    if (!originalConfig) return Promise.reject(error);

    if (error.response?.status === 401 && !originalConfig._retry) {
      // Queue concurrent requests while a refresh is in progress
      if (_isRefreshing) {
        return new Promise<unknown>((resolve) => {
          _pendingQueue.push((token: string) => {
            originalConfig.headers.Authorization = `Bearer ${token}`;
            resolve(http(originalConfig));
          });
        });
      }

      originalConfig._retry = true;
      _isRefreshing = true;

      const currentToken = localStorage.getItem('jwt_token');

      try {
        // Use a plain axios call to avoid triggering this interceptor again
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
        return http(originalConfig);
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

export default http;
