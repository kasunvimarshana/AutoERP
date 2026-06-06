import axios from 'axios';
import { getStoredApiContext } from '@/app/providers/AppProviders';
import { toApiError } from './apiError';

export const apiClient = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || '/',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
    timeout: 30_000,
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
    (error: unknown) => Promise.reject(toApiError(error)),
);
