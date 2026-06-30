import axios from 'axios';
import { ApiError, toApiError } from './apiError';
import {
    getStoredApiContext,
    invalidateStoredAuthSession,
    updateRefreshedSession,
} from './authSessionStorage';
import { isPublicApiRequest } from './requestClassification';

interface RefreshResponse {
    access_token?: string;
    token?: string;
}

interface RefreshLease {
    owner: string;
    expiresAt: number;
}

interface RefreshBroadcastMessage {
    type: 'refreshed' | 'failed';
    owner: string;
    accessToken?: string;
}

const AUTH_REFRESH_LOCK_NAME = 'autoerp-auth-refresh';
const AUTH_REFRESH_LEASE_KEY = 'autoerp.auth_refresh_lease';
const AUTH_REFRESH_CHANNEL = 'autoerp.auth_refresh';
const REFRESH_LEASE_DURATION_MS = 35_000;
const REFRESH_WAIT_GRACE_MS = 2_000;

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
    if (isPublicApiRequest(url)) return false;
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
                ...(context.authMode === 'tenant' && context.tenantId ? { 'X-Tenant-Id': String(context.tenantId) } : {}),
            },
        });
        const rawToken = typeof data.access_token === 'string' ? data.access_token : data.token;
        const token = typeof rawToken === 'string' ? rawToken.trim() : '';
        if (token === '') {
            throw new ApiError('The refresh response did not include an access token.', 502, 'INVALID_REFRESH_RESPONSE', 'infrastructure');
        }

        updateRefreshedSession(token);
        return token;
    } catch (error: unknown) {
        const apiError = toApiError(error);
        if (isTerminalRefreshFailure(apiError)) {
            invalidateStoredAuthSession();
        }

        throw apiError;
    }
}

async function withCrossTabRefreshLock(task: () => Promise<string>): Promise<string> {
    if (typeof navigator !== 'undefined' && 'locks' in navigator && navigator.locks) {
        return navigator.locks.request(AUTH_REFRESH_LOCK_NAME, task);
    }

    if (typeof window === 'undefined' || typeof window.localStorage === 'undefined') {
        return task();
    }

    const owner = createCoordinatorId();
    const existingLease = readRefreshLease();
    if (existingLease && existingLease.expiresAt > Date.now()) {
        return waitForPeerRefresh(existingLease, owner, task);
    }

    writeRefreshLease({ owner, expiresAt: Date.now() + REFRESH_LEASE_DURATION_MS });
    const acquired = readRefreshLease();
    if (acquired?.owner !== owner) {
        return acquired ? waitForPeerRefresh(acquired, owner, task) : withCrossTabRefreshLock(task);
    }

    try {
        const accessToken = await task();
        broadcastRefresh({
            type: 'refreshed',
            owner,
            accessToken,
        });
        return accessToken;
    } catch (error) {
        broadcastRefresh({ type: 'failed', owner });
        throw error;
    } finally {
        releaseRefreshLease(owner);
    }
}

function waitForPeerRefresh(
    lease: RefreshLease,
    waiterId: string,
    task: () => Promise<string>,
): Promise<string> {
    if (typeof BroadcastChannel === 'undefined') {
        return waitForLeaseExpiry(lease).then(() => withCrossTabRefreshLock(task));
    }

    return new Promise<string>((resolve, reject) => {
        const channel = new BroadcastChannel(AUTH_REFRESH_CHANNEL);
        const remaining = Math.max(0, lease.expiresAt - Date.now());
        const timeout = window.setTimeout(() => {
            channel.close();
            void withCrossTabRefreshLock(task).then(resolve, reject);
        }, remaining + REFRESH_WAIT_GRACE_MS);

        channel.onmessage = (event: MessageEvent<RefreshBroadcastMessage>) => {
            const message = event.data;
            if (!message || message.owner !== lease.owner) return;

            window.clearTimeout(timeout);
            channel.close();
            if (message.type === 'refreshed' && message.accessToken) {
                updateRefreshedSession(message.accessToken);
                resolve(message.accessToken);
                return;
            }

            void withCrossTabRefreshLock(task).then(resolve, reject);
        };

        // Keep the identifier in the closure so parallel waiters remain distinct in diagnostics.
        void waiterId;
    });
}

function waitForLeaseExpiry(lease: RefreshLease): Promise<void> {
    const delay = Math.max(0, lease.expiresAt - Date.now()) + REFRESH_WAIT_GRACE_MS;
    return new Promise((resolve) => window.setTimeout(resolve, delay));
}

function readRefreshLease(): RefreshLease | null {
    const raw = window.localStorage.getItem(AUTH_REFRESH_LEASE_KEY);
    if (!raw) return null;

    try {
        const parsed = JSON.parse(raw) as Partial<RefreshLease>;
        if (typeof parsed.owner !== 'string' || typeof parsed.expiresAt !== 'number') return null;
        return { owner: parsed.owner, expiresAt: parsed.expiresAt };
    } catch {
        return null;
    }
}

function writeRefreshLease(lease: RefreshLease): void {
    window.localStorage.setItem(AUTH_REFRESH_LEASE_KEY, JSON.stringify(lease));
}

function releaseRefreshLease(owner: string): void {
    if (readRefreshLease()?.owner === owner) {
        window.localStorage.removeItem(AUTH_REFRESH_LEASE_KEY);
    }
}

function broadcastRefresh(message: RefreshBroadcastMessage): void {
    if (typeof BroadcastChannel === 'undefined') return;
    const channel = new BroadcastChannel(AUTH_REFRESH_CHANNEL);
    channel.postMessage(message);
    channel.close();
}

function createCoordinatorId(): string {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }

    return `${Date.now()}-${Math.random().toString(36).slice(2)}`;
}

function isTerminalRefreshFailure(error: ApiError): boolean {
    return error.status === 401 || error.status === 403 || error.code === 'AUTH_TOKEN_INVALID' || error.code === 'AUTH_TOKEN_REVOKED';
}
