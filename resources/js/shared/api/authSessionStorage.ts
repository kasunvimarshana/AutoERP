export type AuthMode = 'tenant' | 'platform';

export interface StoredApiContext {
    accessToken: string | null;
    sessionId: number | null;
    tenantId: number | null;
    organizationUnitId: number | null;
    authMode: AuthMode;
    hasSession: boolean;
}

export interface AuthSessionContext {
    accessToken: string;
    sessionId: number | null;
    tenantId: number | null;
    organizationUnitId: number | null;
    authMode: AuthMode;
}

export const AUTH_SESSION_MARKER_KEY = 'autoerp.auth_session';
export const AUTH_SESSION_INVALIDATED_EVENT = 'autoerp:auth-session-invalidated';

const SESSION_KEY = 'autoerp.session_id';
const TENANT_KEY = 'autoerp.tenant_id';
const ORG_KEY = 'autoerp.organization_unit_id';
const AUTH_MODE_KEY = 'autoerp.auth_mode';
const LEGACY_AUTH_KEYS = [
    'autoerp.access_token',
    'autoerp.refresh_token',
    'autoerp.user',
    'autoerp.current_user',
    'autoerp.permissions',
    'autoerp.roles',
    'autoerp.tenant',
    'autoerp.organization_unit',
];

let accessTokenInMemory: string | null = null;

export function getStoredApiContext(): StoredApiContext {
    return {
        accessToken: accessTokenInMemory,
        sessionId: storedPositiveInteger(SESSION_KEY),
        tenantId: storedPositiveInteger(TENANT_KEY),
        organizationUnitId: storedPositiveInteger(ORG_KEY),
        authMode: storedAuthMode(),
        hasSession: window.localStorage.getItem(AUTH_SESSION_MARKER_KEY) !== null,
    };
}

export function setTransientAccessToken(accessToken: string | null): void {
    accessTokenInMemory = normalizeToken(accessToken);
}

export function commitAuthSession(context: AuthSessionContext): void {
    const accessToken = normalizeToken(context.accessToken);
    if (accessToken === null) {
        throw new Error('An access token is required to commit an authenticated session.');
    }

    accessTokenInMemory = accessToken;
    setPositiveInteger(SESSION_KEY, context.sessionId);
    setPositiveInteger(TENANT_KEY, context.authMode === 'tenant' ? context.tenantId : null);
    setPositiveInteger(ORG_KEY, context.authMode === 'tenant' ? context.organizationUnitId : null);
    window.localStorage.setItem(AUTH_MODE_KEY, context.authMode);
    LEGACY_AUTH_KEYS.forEach((key) => window.localStorage.removeItem(key));
    window.localStorage.setItem(AUTH_SESSION_MARKER_KEY, createSessionMarker());
}

export function updateRefreshedSession(accessToken: string, sessionId?: number | null): void {
    const normalizedToken = normalizeToken(accessToken);
    if (normalizedToken === null) {
        throw new Error('A refreshed access token is required.');
    }

    accessTokenInMemory = normalizedToken;
    if (sessionId !== undefined) {
        setPositiveInteger(SESSION_KEY, sessionId);
    }
}

export function clearStoredAuthSession(): void {
    accessTokenInMemory = null;
    window.localStorage.removeItem(SESSION_KEY);
    window.localStorage.removeItem(TENANT_KEY);
    window.localStorage.removeItem(ORG_KEY);
    window.localStorage.removeItem(AUTH_MODE_KEY);
    LEGACY_AUTH_KEYS.forEach((key) => window.localStorage.removeItem(key));
    window.localStorage.removeItem(AUTH_SESSION_MARKER_KEY);
}

export function invalidateStoredAuthSession(): void {
    clearStoredAuthSession();
    window.dispatchEvent(new Event(AUTH_SESSION_INVALIDATED_EVENT));
}

function storedPositiveInteger(key: string): number | null {
    const rawValue = window.localStorage.getItem(key);
    if (rawValue === null) return null;

    const value = Number(rawValue);
    return Number.isSafeInteger(value) && value > 0 ? value : null;
}

function storedAuthMode(): AuthMode {
    return window.localStorage.getItem(AUTH_MODE_KEY) === 'platform' ? 'platform' : 'tenant';
}

function setPositiveInteger(key: string, value: number | null): void {
    if (Number.isSafeInteger(value) && Number(value) > 0) {
        window.localStorage.setItem(key, String(value));
        return;
    }

    window.localStorage.removeItem(key);
}

function normalizeToken(value: string | null): string | null {
    if (typeof value !== 'string') return null;
    const normalized = value.trim();
    return normalized !== '' ? normalized : null;
}

function createSessionMarker(): string {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }

    return `${Date.now()}-${Math.random().toString(36).slice(2)}`;
}
