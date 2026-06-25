import axios, { type InternalAxiosRequestConfig } from 'axios';
import { refreshAccessToken, shouldAttemptAuthRefresh } from './authRefreshCoordinator';
import { getStoredApiContext } from './authSessionStorage';
import { toApiError } from './apiError';
import { serializeQueryParams } from './queryParams';

interface AuthRetryConfig extends InternalAxiosRequestConfig {
    autoerpAuthRetried?: boolean;
}

export const apiClient = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || '/',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
    timeout: 30_000,
    withCredentials: true,
    paramsSerializer: {
        serialize: serializeQueryParams,
    },
});

apiClient.interceptors.request.use((config) => {
    if (typeof FormData !== 'undefined' && config.data instanceof FormData) {
        config.headers.delete('Content-Type');
    }
    const { accessToken, tenantId } = getStoredApiContext();
    if (accessToken) {
        config.headers.Authorization = `Bearer ${accessToken}`;
    }
    if (tenantId) {
        config.headers['X-Tenant-Id'] = String(tenantId);
    }
    return config;
});

apiClient.interceptors.response.use(
    (response) => response,
    async (error: unknown) => {
        if (!axios.isAxiosError(error) || error.response?.status !== 401 || !error.config) {
            return Promise.reject(toApiError(error));
        }

        const config = error.config as AuthRetryConfig;
        if (config.autoerpAuthRetried || !shouldAttemptAuthRefresh(config.url)) {
            return Promise.reject(toApiError(error));
        }

        config.autoerpAuthRetried = true;
        try {
            const accessToken = await refreshAccessToken();
            config.headers.Authorization = `Bearer ${accessToken}`;
            return await apiClient.request(config);
        } catch (refreshError: unknown) {
            return Promise.reject(toApiError(refreshError));
        }
    },
);
