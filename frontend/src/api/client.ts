import axios, { AxiosError, InternalAxiosRequestConfig } from 'axios';

const BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000';

export const apiClient = axios.create({
  baseURL: BASE_URL,
  headers: { 'Content-Type': 'application/json' },
  timeout: 30000,
});

const getToken = () => localStorage.getItem('kv_access_token');
const getRefreshToken = () => localStorage.getItem('kv_refresh_token');
const getTenantId = () => localStorage.getItem('kv_tenant_id') || process.env.REACT_APP_TENANT_ID;

apiClient.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  const token = getToken();
  const tenantId = getTenantId();
  if (token) config.headers.Authorization = `Bearer ${token}`;
  if (tenantId) config.headers['X-Tenant-ID'] = tenantId;
  return config;
});

let isRefreshing = false;
let failedQueue: Array<{ resolve: (v: string) => void; reject: (e: any) => void }> = [];

const processQueue = (error: any, token: string | null = null) => {
  failedQueue.forEach(({ resolve, reject }) => {
    if (error) reject(error);
    else resolve(token!);
  });
  failedQueue = [];
};

apiClient.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const originalRequest = error.config as InternalAxiosRequestConfig & { _retry?: boolean };
    if (error.response?.status === 401 && !originalRequest._retry) {
      if (isRefreshing) {
        return new Promise((resolve, reject) => {
          failedQueue.push({ resolve, reject });
        }).then((token) => {
          originalRequest.headers.Authorization = `Bearer ${token}`;
          return apiClient(originalRequest);
        });
      }
      originalRequest._retry = true;
      isRefreshing = true;
      const refreshToken = getRefreshToken();
      if (!refreshToken) {
        localStorage.removeItem('kv_access_token');
        localStorage.removeItem('kv_refresh_token');
        localStorage.removeItem('kv_user');
        window.location.href = '/login';
        return Promise.reject(error);
      }
      try {
        const res = await axios.post(`${BASE_URL}/api/auth/refresh`, { refresh_token: refreshToken });
        const newToken = res.data.data?.access_token || res.data.access_token;
        localStorage.setItem('kv_access_token', newToken);
        apiClient.defaults.headers.common.Authorization = `Bearer ${newToken}`;
        processQueue(null, newToken);
        originalRequest.headers.Authorization = `Bearer ${newToken}`;
        return apiClient(originalRequest);
      } catch (err) {
        processQueue(err, null);
        localStorage.removeItem('kv_access_token');
        localStorage.removeItem('kv_refresh_token');
        localStorage.removeItem('kv_user');
        window.location.href = '/login';
        return Promise.reject(err);
      } finally {
        isRefreshing = false;
      }
    }
    return Promise.reject(error);
  }
);

export default apiClient;
