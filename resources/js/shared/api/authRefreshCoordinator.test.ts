import { afterEach, describe, expect, it } from 'vitest';
import { AUTH_SESSION_MARKER_KEY, clearStoredAuthSession } from './authSessionStorage';
import { shouldAttemptAuthRefresh } from './authRefreshCoordinator';

describe('shouldAttemptAuthRefresh', () => {
    afterEach(() => {
        clearStoredAuthSession();
    });

    it('never refreshes an authenticated session for public login endpoints', () => {
        window.localStorage.setItem(AUTH_SESSION_MARKER_KEY, 'test-session');

        expect(shouldAttemptAuthRefresh('/api/v1/platform/auth/login')).toBe(false);
    });

    it('allows one refresh attempt for a protected endpoint when a session exists', () => {
        window.localStorage.setItem(AUTH_SESSION_MARKER_KEY, 'test-session');

        expect(shouldAttemptAuthRefresh('/api/v1/items')).toBe(true);
    });
});
