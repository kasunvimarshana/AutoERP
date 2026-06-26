import { fireEvent, render, screen } from '@testing-library/react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ApiError } from '@/shared/api/apiError';
import { useAuth } from './AuthProvider';
import { ProtectedRoute } from './ProtectedRoute';

vi.mock('./AuthProvider', () => ({ useAuth: vi.fn() }));

function authState(overrides: Record<string, unknown> = {}) {
    return {
        user: null,
        token: null,
        tenant: null,
        organizationUnit: null,
        roles: [],
        permissions: [],
        permissionsLoaded: false,
        enabledModules: null,
        enabledModulesLoaded: false,
        isPlatformOperator: false,
        authMode: 'platform',
        isAuthenticated: false,
        isLoading: false,
        bootstrapError: null,
        login: vi.fn(),
        logout: vi.fn().mockResolvedValue(undefined),
        loadCurrentUser: vi.fn().mockResolvedValue(undefined),
        switchOrganizationUnit: vi.fn(),
        ...overrides,
    };
}

function renderRoute() {
    return render(
        <MemoryRouter initialEntries={['/platform/tenants']}>
            <Routes>
                <Route element={<ProtectedRoute />}>
                    <Route path="/platform/tenants" element={<div>Tenant administration</div>} />
                </Route>
                <Route path="/login" element={<div>Platform login</div>} />
            </Routes>
        </MemoryRouter>,
    );
}

describe('ProtectedRoute session recovery', () => {
    beforeEach(() => vi.mocked(useAuth).mockReset());

    it('offers retry, explicit sign-out, and a safe return to login for temporary failures', () => {
        const loadCurrentUser = vi.fn().mockResolvedValue(undefined);
        const logout = vi.fn().mockResolvedValue(undefined);
        vi.mocked(useAuth).mockReturnValue(authState({
            bootstrapError: new ApiError('Unexpected server error.', 500, 'UNEXPECTED_ERROR', 'infrastructure', {}, {
                correlation_id: '01JTESTCORRELATION',
            }),
            loadCurrentUser,
            logout,
        }) as ReturnType<typeof useAuth>);

        renderRoute();

        expect(screen.getByText(/01JTESTCORRELATION/)).toBeInTheDocument();
        fireEvent.click(screen.getByRole('button', { name: 'Retry' }));
        fireEvent.click(screen.getByRole('button', { name: 'Sign out and clear session' }));
        expect(loadCurrentUser).toHaveBeenCalledOnce();
        expect(logout).toHaveBeenCalledOnce();
        expect(screen.getByRole('link', { name: 'Return to login' })).toHaveAttribute('href', '/login');
    });

    it('redirects an unauthenticated user to login instead of trapping them on recovery', () => {
        vi.mocked(useAuth).mockReturnValue(authState() as ReturnType<typeof useAuth>);
        renderRoute();
        expect(screen.getByText('Platform login')).toBeInTheDocument();
    });
});
