import axios from 'axios';
import { ApiError, toApiError } from './apiError';
import {
    getStoredApiContext,
    invalidateStoredAuthSession,
    updateRefreshedSession,
} from './authSessionStorage';

interface RefreshResponse {
    access_token?: string;
    token?: string;
    session_id?: number | null;
}

const refreshClient = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || '/',
    headers: { Accept: 'application/json' },
    timeout: 30_000,
    withCredentials: true,
});

let refreshPromise: Promise<string> | null = null;

export function refreshAccessToken(): Promise<string> {
    if (refreshPromise) return refreshPromise;

    refreshPromise = withCrossTabRefreshLock(performRefresh)
        .finally(() => {
            refreshPromise = null;
        });

    return refreshPromise;
}

export function shouldAttemptAuthRefresh(url: string | undefined): boolean {
    if (!getStoredApiContext().hasSession) return false;
    if (!url) return true;

    return !/\/auth\/(?:login|refresh|token\/refresh|logout)(?:\?|$)/.test(url);
}

async function performRefresh(): Promise<string> {
    const context = getStoredApiContext();
    if (!context.hasSession) {
        throw new ApiError('Authenticated session is not available.', 401, 'AUTH_SESSION_MISSING', 'authentication');
    }

    const endpoint = context.authMode === 'platform'
        ? '/api/v1/platform/auth/refresh'
        : '/api/v1/auth/refresh';

    try {
        const { data } = await refreshClient.post<RefreshResponse>(endpoint, undefined, {
            headers: {
                ...(context.tenantId ? { 'X-Tenant-Id': String(context.tenantId) } : {}),
                ...(context.organizationUnitId
                    ? { 'X-Organization-Unit-Id': String(context.organizationUnitId) }
                    : {}),
            },
        });
        const rawToken = typeof data.access_token === 'string' ? data.access_token : data.token;
        const token = typeof rawToken === 'string' ? rawToken.trim() : '';
        if (token === '') {
            throw new ApiError('The refresh response did not include an access token.', 502, 'INVALID_REFRESH_RESPONSE', 'infrastructure');
        }

        updateRefreshedSession(token, normalizePositiveInteger(data.session_id));

        return token;
    } catch (error: unknown) {
        const apiError = toApiError(error);
        if (isTerminalRefreshFailure(apiError)) {
            invalidateStoredAuthSession();
        }

        throw apiError;
    }
}

async function withCrossTabRefreshLock<T>(task: () => Promise<T>): Promise<T> {
    if (typeof navigator !== 'undefined' && 'locks' in navigator && navigator.locks) {
        return navigator.locks.request('autoerp-auth-refresh', task);
    }

    return task();
}

function isTerminalRefreshFailure(error: ApiError): boolean {
    return error.status === 401 || error.status === 403 || error.code === 'TOKEN_INVALID' || error.code === 'TOKEN_REVOKED';
}

function normalizePositiveInteger(value: number | null | undefined): number | null | undefined {
    if (value === undefined) return undefined;
    return Number.isSafeInteger(value) && Number(value) > 0 ? Number(value) : null;
}
