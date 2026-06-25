import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import {
    AUTH_SESSION_INVALIDATED_EVENT,
    AUTH_SESSION_MARKER_KEY,
    clearStoredAuthSession,
    commitAuthSession,
    getStoredApiContext,
    invalidateStoredAuthSession,
    setTransientAccessToken,
    updateRefreshedSession,
} from './authSessionStorage';

beforeEach(() => {
    window.localStorage.clear();
    setTransientAccessToken(null);
});

afterEach(() => {
    clearStoredAuthSession();
    vi.restoreAllMocks();
});

describe('auth session storage', () => {
    it('keeps access tokens in memory and stores only non-secret session context', () => {
        commitAuthSession({
            accessToken: ' access-token ',
            sessionId: 10,
            tenantId: 20,
            authMode: 'tenant',
        });

        expect(getStoredApiContext()).toMatchObject({
            accessToken: 'access-token',
            sessionId: 10,
            tenantId: 20,
            authMode: 'tenant',
            hasSession: true,
        });
        expect(window.localStorage.getItem('autoerp.access_token')).toBeNull();
        expect(window.localStorage.getItem('autoerp.refresh_token')).toBeNull();
        expect(window.localStorage.getItem('autoerp.organization_unit_id')).toBeNull();
        expect(window.localStorage.getItem(AUTH_SESSION_MARKER_KEY)).not.toBeNull();
    });

    it('does not persist tenant context for platform sessions', () => {
        commitAuthSession({
            accessToken: 'platform-token',
            sessionId: 11,
            tenantId: 22,
            authMode: 'platform',
        });

        expect(getStoredApiContext()).toMatchObject({
            tenantId: null,
            authMode: 'platform',
        });
    });

    it('rejects invalid session identifiers read from browser storage', () => {
        window.localStorage.setItem('autoerp.session_id', '-1');
        window.localStorage.setItem('autoerp.tenant_id', 'not-a-number');
        window.localStorage.setItem('autoerp.organization_unit_id', '1.5');

        expect(getStoredApiContext()).toMatchObject({
            sessionId: null,
            tenantId: null,
        });
        expect(getStoredApiContext()).not.toHaveProperty('organizationUnitId');
    });

    it('rotates only the in-memory access token during refresh', () => {
        commitAuthSession({
            accessToken: 'old-token',
            sessionId: 10,
            tenantId: 20,
            authMode: 'tenant',
        });

        updateRefreshedSession('new-token', 12);

        expect(getStoredApiContext()).toMatchObject({ accessToken: 'new-token', sessionId: 12 });
    });

    it('clears all context and announces definitive invalidation', () => {
        const listener = vi.fn();
        window.addEventListener(AUTH_SESSION_INVALIDATED_EVENT, listener);
        commitAuthSession({
            accessToken: 'token',
            sessionId: 10,
            tenantId: 20,
            authMode: 'tenant',
        });

        invalidateStoredAuthSession();

        expect(getStoredApiContext()).toMatchObject({
            accessToken: null,
            sessionId: null,
            tenantId: null,
            hasSession: false,
        });
        expect(listener).toHaveBeenCalledOnce();
        window.removeEventListener(AUTH_SESSION_INVALIDATED_EVENT, listener);
    });
});
