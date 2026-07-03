import { beforeEach, describe, expect, it } from 'vitest';
import {
    AUTH_SESSION_MARKER_KEY,
    clearStoredAuthSession,
    commitAuthSession,
    getStoredApiContext,
    setTransientAccessToken,
    updateRefreshedSession,
} from './authSessionStorage';

describe('authSessionStorage', () => {
    beforeEach(() => {
        window.localStorage.clear();
        clearStoredAuthSession();
    });

    it('keeps the access token in memory and stores only non-secret session context', () => {
        commitAuthSession({ accessToken: ' access-token ', tenantId: 5, authMode: 'tenant' });

        expect(getStoredApiContext()).toMatchObject({
            accessToken: 'access-token',
            tenantId: 5,
            authMode: 'tenant',
            hasSession: true,
        });
        expect(window.localStorage.getItem(AUTH_SESSION_MARKER_KEY)).toBeTruthy();
        expect(window.localStorage.getItem('autoerp.access_token')).toBeNull();
        expect(window.localStorage.getItem('autoerp.refresh_token')).toBeNull();
        expect(window.localStorage.getItem('autoerp.session_id')).toBeNull();
    });

    it('does not retain tenant context for a platform session', () => {
        commitAuthSession({ accessToken: 'platform-token', tenantId: 5, authMode: 'platform' });

        expect(getStoredApiContext()).toMatchObject({ tenantId: null, authMode: 'platform' });
    });

    it('clears legacy browser authentication state', () => {
        window.localStorage.setItem('autoerp.access_token', 'legacy');
        window.localStorage.setItem('autoerp.refresh_token', 'legacy-refresh');
        window.localStorage.setItem('autoerp.session_id', '42');

        commitAuthSession({ accessToken: 'new-token', tenantId: 8, authMode: 'tenant' });

        expect(window.localStorage.getItem('autoerp.access_token')).toBeNull();
        expect(window.localStorage.getItem('autoerp.refresh_token')).toBeNull();
        expect(window.localStorage.getItem('autoerp.session_id')).toBeNull();
    });

    it('updates a rotated access token without persisting it', () => {
        commitAuthSession({ accessToken: 'old-token', tenantId: 8, authMode: 'tenant' });
        updateRefreshedSession('new-token');

        expect(getStoredApiContext().accessToken).toBe('new-token');
        expect(window.localStorage.getItem('autoerp.access_token')).toBeNull();
    });

    it('can restore session context without rotating the cross-tab session marker', () => {
        commitAuthSession({ accessToken: 'old-token', tenantId: 8, authMode: 'tenant' });
        const marker = window.localStorage.getItem(AUTH_SESSION_MARKER_KEY);

        commitAuthSession(
            { accessToken: 'restored-token', tenantId: 8, authMode: 'tenant' },
            { notifyOtherTabs: false },
        );

        expect(getStoredApiContext()).toMatchObject({
            accessToken: 'restored-token',
            tenantId: 8,
            authMode: 'tenant',
            hasSession: true,
        });
        expect(window.localStorage.getItem(AUTH_SESSION_MARKER_KEY)).toBe(marker);
    });

    it('still creates a session marker when a silent commit starts from missing storage', () => {
        commitAuthSession(
            { accessToken: 'token', tenantId: null, authMode: 'platform' },
            { notifyOtherTabs: false },
        );

        expect(getStoredApiContext()).toMatchObject({
            accessToken: 'token',
            tenantId: null,
            authMode: 'platform',
            hasSession: true,
        });
        expect(window.localStorage.getItem(AUTH_SESSION_MARKER_KEY)).toBeTruthy();
    });

    it('clears all session state', () => {
        commitAuthSession({ accessToken: 'token', tenantId: 8, authMode: 'tenant' });
        setTransientAccessToken('other-token');
        clearStoredAuthSession();

        expect(getStoredApiContext()).toEqual({
            accessToken: null,
            tenantId: null,
            authMode: 'tenant',
            hasSession: false,
        });
    });
});
