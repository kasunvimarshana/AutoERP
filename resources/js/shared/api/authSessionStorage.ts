export interface StoredApiContext {
    accessToken: string | null;
    refreshToken: string | null;
    sessionId: number | null;
    tenantId: number | null;
    organizationUnitId: number | null;
}

const TOKEN_KEY = 'autoerp.access_token';
const REFRESH_TOKEN_KEY = 'autoerp.refresh_token';
const SESSION_KEY = 'autoerp.session_id';
const TENANT_KEY = 'autoerp.tenant_id';
const ORG_KEY = 'autoerp.organization_unit_id';

function storedNumber(key: string): number | null {
    const value = window.localStorage.getItem(key);
    return value && Number.isFinite(Number(value)) ? Number(value) : null;
}

export function getStoredApiContext(): StoredApiContext {
    return {
        accessToken: window.localStorage.getItem(TOKEN_KEY),
        refreshToken: window.localStorage.getItem(REFRESH_TOKEN_KEY),
        sessionId: storedNumber(SESSION_KEY),
        tenantId: storedNumber(TENANT_KEY),
        organizationUnitId: storedNumber(ORG_KEY),
    };
}

export function storeAuthSession(context: StoredApiContext): void {
    setString(TOKEN_KEY, context.accessToken);
    setString(REFRESH_TOKEN_KEY, context.refreshToken);
    setNumber(SESSION_KEY, context.sessionId);
    setNumber(TENANT_KEY, context.tenantId);
    setNumber(ORG_KEY, context.organizationUnitId);
}

export function clearStoredAuthSession(): void {
    window.localStorage.removeItem(TOKEN_KEY);
    window.localStorage.removeItem(REFRESH_TOKEN_KEY);
    window.localStorage.removeItem(SESSION_KEY);
    window.localStorage.removeItem(TENANT_KEY);
    window.localStorage.removeItem(ORG_KEY);
}

function setString(key: string, value: string | null): void {
    value ? window.localStorage.setItem(key, value) : window.localStorage.removeItem(key);
}

function setNumber(key: string, value: number | null): void {
    value ? window.localStorage.setItem(key, String(value)) : window.localStorage.removeItem(key);
}
