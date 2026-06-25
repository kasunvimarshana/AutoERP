import axios from 'axios';
import { clearStoredAuthSession, getStoredApiContext } from './authSessionStorage';
import { toApiError } from './apiError';
import { serializeQueryParams } from './queryParams';

export const apiClient = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || '/',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
    timeout: 30_000,
    paramsSerializer: {
        serialize: serializeQueryParams,
    },
});

apiClient.interceptors.request.use((config) => {
    const { accessToken, tenantId, organizationUnitId } = getStoredApiContext();
    if (accessToken) {
        config.headers.Authorization = `Bearer ${accessToken}`;
    }
    if (tenantId) {
        config.headers['X-Tenant-Id'] = String(tenantId);
    }
    if (organizationUnitId) {
        config.headers['X-Organization-Unit-Id'] = String(organizationUnitId);
    }
    return config;
});

apiClient.interceptors.response.use(
    (response) => response,
    (error: unknown) => {
        if (axios.isAxiosError(error) && error.response?.status === 401) {
            clearStoredAuthSession();
            window.dispatchEvent(new Event('autoerp:auth-unauthorized'));
        }

        return Promise.reject(toApiError(error));
    },
);
