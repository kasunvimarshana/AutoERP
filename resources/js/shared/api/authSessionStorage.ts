export type AuthMode = 'tenant' | 'platform';

export interface StoredApiContext {
    accessToken: string | null;
    refreshToken: string | null;
    sessionId: number | null;
    tenantId: number | null;
    organizationUnitId: number | null;
    authMode: AuthMode;
}

const TOKEN_KEY = 'autoerp.access_token';
const REFRESH_TOKEN_KEY = 'autoerp.refresh_token';
const SESSION_KEY = 'autoerp.session_id';
const TENANT_KEY = 'autoerp.tenant_id';
const ORG_KEY = 'autoerp.organization_unit_id';
const AUTH_MODE_KEY = 'autoerp.auth_mode';
const LEGACY_AUTH_KEYS = [
    'autoerp.user',
    'autoerp.current_user',
    'autoerp.permissions',
    'autoerp.roles',
    'autoerp.tenant',
    'autoerp.organization_unit',
];

function storedNumber(key: string): number | null {
    const value = window.localStorage.getItem(key);
    return value && Number.isFinite(Number(value)) ? Number(value) : null;
}

function storedAuthMode(): AuthMode {
    return window.localStorage.getItem(AUTH_MODE_KEY) === 'platform' ? 'platform' : 'tenant';
}

export function getStoredApiContext(): StoredApiContext {
    return {
        accessToken: window.localStorage.getItem(TOKEN_KEY),
        refreshToken: window.localStorage.getItem(REFRESH_TOKEN_KEY),
        sessionId: storedNumber(SESSION_KEY),
        tenantId: storedNumber(TENANT_KEY),
        organizationUnitId: storedNumber(ORG_KEY),
        authMode: storedAuthMode(),
    };
}

export function storeAuthSession(context: StoredApiContext): void {
    setString(TOKEN_KEY, context.accessToken);
    setString(REFRESH_TOKEN_KEY, context.refreshToken);
    setNumber(SESSION_KEY, context.sessionId);
    setNumber(TENANT_KEY, context.tenantId);
    setNumber(ORG_KEY, context.organizationUnitId);
    window.localStorage.setItem(AUTH_MODE_KEY, context.authMode);
}

export function clearStoredAuthSession(): void {
    window.localStorage.removeItem(TOKEN_KEY);
    window.localStorage.removeItem(REFRESH_TOKEN_KEY);
    window.localStorage.removeItem(SESSION_KEY);
    window.localStorage.removeItem(TENANT_KEY);
    window.localStorage.removeItem(ORG_KEY);
    window.localStorage.removeItem(AUTH_MODE_KEY);
    LEGACY_AUTH_KEYS.forEach((key) => window.localStorage.removeItem(key));
}

function setString(key: string, value: string | null): void {
    if (value) {
        window.localStorage.setItem(key, value);
    } else {
        window.localStorage.removeItem(key);
    }
}

function setNumber(key: string, value: number | null): void {
    if (value) {
        window.localStorage.setItem(key, String(value));
    } else {
        window.localStorage.removeItem(key);
    }
}
