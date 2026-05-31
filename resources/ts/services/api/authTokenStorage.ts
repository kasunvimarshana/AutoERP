import type { AuthSession, AuthUser } from '../../modules/auth/types/auth.types';

const AUTH_KEYS = {
    accessToken: 'auth_token',
    refreshToken: 'auth_refresh_token',
    sessionId: 'auth_session_id',
    tokenType: 'auth_token_type',
    user: 'auth_user',
    tenantId: 'tenant_id',
    organizationUnitId: 'organization_unit_id',
} as const;

type StoredAuthSession = {
    accessToken: string | null;
    organizationUnitId: string | null;
    refreshToken: string | null;
    sessionId: string | null;
    tenantId: string | null;
    tokenType: string | null;
    user: AuthUser | null;
};

function storageTargets(): Storage[] {
    if (typeof window === 'undefined') {
        return [];
    }

    return [window.localStorage, window.sessionStorage];
}

function readValue(key: string): string | null {
    for (const storage of storageTargets()) {
        const value = storage.getItem(key);
        if (value !== null && value !== '') {
            return value;
        }
    }

    return null;
}

function writeValue(storage: Storage, key: string, value: string | null | undefined): void {
    if (value === null || value === undefined || value === '') {
        storage.removeItem(key);

        return;
    }

    storage.setItem(key, value);
}

function parseStoredUser(value: string | null): AuthUser | null {
    if (!value) {
        return null;
    }

    try {
        const parsed = JSON.parse(value) as AuthUser;

        return parsed && typeof parsed === 'object' && typeof parsed.id === 'string' ? parsed : null;
    } catch {
        return null;
    }
}

export function getStoredAuthSession(): StoredAuthSession {
    return {
        accessToken: readValue(AUTH_KEYS.accessToken),
        organizationUnitId: readValue(AUTH_KEYS.organizationUnitId),
        refreshToken: readValue(AUTH_KEYS.refreshToken),
        sessionId: readValue(AUTH_KEYS.sessionId),
        tenantId: readValue(AUTH_KEYS.tenantId),
        tokenType: readValue(AUTH_KEYS.tokenType),
        user: parseStoredUser(readValue(AUTH_KEYS.user)),
    };
}

export function getStoredAccessToken(): string | null {
    return getStoredAuthSession().accessToken;
}

export function getStoredTenantId(): string | null {
    return getStoredAuthSession().tenantId;
}

export function getStoredOrganizationUnitId(): string | null {
    return getStoredAuthSession().organizationUnitId;
}

export function persistAuthSession(session: AuthSession, remember: boolean): void {
    if (typeof window === 'undefined') {
        return;
    }

    clearAuthSession();

    const storage = remember ? window.localStorage : window.sessionStorage;

    writeValue(storage, AUTH_KEYS.accessToken, session.accessToken);
    writeValue(storage, AUTH_KEYS.refreshToken, session.refreshToken);
    writeValue(storage, AUTH_KEYS.sessionId, session.sessionId);
    writeValue(storage, AUTH_KEYS.tokenType, session.tokenType);
    writeValue(storage, AUTH_KEYS.tenantId, session.tenantId);
    writeValue(storage, AUTH_KEYS.organizationUnitId, session.organizationUnitId);
    writeValue(storage, AUTH_KEYS.user, JSON.stringify(session.user));
}

export function updateStoredAuthUser(user: AuthUser): void {
    if (typeof window === 'undefined') {
        return;
    }

    const storage = window.localStorage.getItem(AUTH_KEYS.accessToken) ? window.localStorage : window.sessionStorage;
    writeValue(storage, AUTH_KEYS.user, JSON.stringify(user));
}

export function clearAuthSession(): void {
    for (const storage of storageTargets()) {
        Object.values(AUTH_KEYS).forEach((key) => storage.removeItem(key));
    }
}
