import { render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
    AUTH_SESSION_MARKER_KEY,
    clearStoredAuthSession,
    commitAuthSession,
} from '@/shared/api/authSessionStorage';
import { authApi } from './authApi';
import { AuthProvider, useAuth } from './AuthProvider';

vi.mock('./authApi', () => ({
    authApi: {
        login: vi.fn(),
        logout: vi.fn(),
        me: vi.fn(),
        organizationUnitOptions: vi.fn(),
        switchOrganizationUnit: vi.fn(),
    },
}));

function AuthStateProbe() {
    const auth = useAuth();

    return (
        <output data-testid="auth-state">
            {auth.isLoading ? 'loading' : auth.isAuthenticated ? 'ready' : 'guest'}
        </output>
    );
}

describe('AuthProvider session marker handling', () => {
    beforeEach(() => {
        window.localStorage.clear();
        clearStoredAuthSession();
        vi.mocked(authApi.me).mockReset();
    });

    it('restores the current user without rotating the cross-tab session marker', async () => {
        vi.mocked(authApi.me).mockResolvedValue({
            user: {
                id: 1,
                name: 'Platform Administrator',
                email: 'admin@example.com',
                is_platform_operator: true,
            },
            tenant: null,
            organization_unit: null,
            roles: ['Platform Operator'],
            permissions: ['platform.tenants.view'],
            enabled_modules: null,
            is_platform_operator: true,
        });
        commitAuthSession({ accessToken: 'platform-token', tenantId: null, authMode: 'platform' });
        const marker = window.localStorage.getItem(AUTH_SESSION_MARKER_KEY);

        render(
            <AuthProvider>
                <AuthStateProbe />
            </AuthProvider>,
        );

        await waitFor(() => expect(screen.getByTestId('auth-state')).toHaveTextContent('ready'));

        expect(authApi.me).toHaveBeenCalledOnce();
        expect(window.localStorage.getItem(AUTH_SESSION_MARKER_KEY)).toBe(marker);
    });
});
