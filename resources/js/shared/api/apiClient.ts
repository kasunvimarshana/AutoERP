import axios, { type InternalAxiosRequestConfig } from 'axios';
import { refreshAccessToken, shouldAttemptAuthRefresh } from './authRefreshCoordinator';
import { getStoredApiContext } from './authSessionStorage';
import { toApiError } from './apiError';
import { isPublicApiRequest, requestPath } from './requestClassification';
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

    const context = getStoredApiContext();
    if (isPublicApiRequest(config.url)) {
        config.headers.delete('Authorization');
    } else if (shouldAttachAuthorizationHeader(config.url, context.accessToken)) {
        config.headers.Authorization = `Bearer ${context.accessToken}`;
    }
    if (shouldAttachTenantHeader(config.url, context.authMode, context.tenantId)) {
        config.headers['X-Tenant-Id'] = String(context.tenantId);
    } else {
        config.headers.delete('X-Tenant-Id');
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

export function shouldAttachAuthorizationHeader(
    url: string | undefined,
    accessToken: string | null,
): boolean {
    return typeof accessToken === 'string'
        && accessToken.trim() !== ''
        && !isPublicApiRequest(url);
}

export function shouldAttachTenantHeader(
    url: string | undefined,
    authMode: 'tenant' | 'platform',
    tenantId: number | null,
): boolean {
    if (authMode !== 'tenant' || tenantId === null || !Number.isSafeInteger(tenantId) || tenantId < 1) {
        return false;
    }

    const path = requestPath(url);
    if (!path.startsWith('/api/v1/')) return false;
    if (path.startsWith('/api/v1/platform/')) return false;
    if (isPublicApiRequest(path)) return false;

    return true;
}
