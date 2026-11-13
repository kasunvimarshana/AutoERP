import { fireEvent, render, screen } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import { describe, expect, it, vi } from 'vitest';
import { PLATFORM_HOME_PATH } from '@/app/routePaths';
import { AppHeader } from './AppHeader';

const mockAuth = vi.hoisted(() => ({
    user: { id: 1, name: 'Admin Operator', email: 'admin@autoerp.test' },
    token: 'token',
    tenant: null,
    organizationUnit: null,
    roles: ['super-admin'],
    permissions: [],
    permissionsLoaded: true,
    enabledModules: [],
    enabledModulesLoaded: true,
    isPlatformOperator: true,
    authMode: 'platform' as const,
    isAuthenticated: true,
    isLoading: false,
    bootstrapError: null,
    login: vi.fn(),
    logout: vi.fn(),
    loadCurrentUser: vi.fn(),
    switchOrganizationUnit: vi.fn(),
}));

vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => mockAuth,
}));

describe('AppHeader', () => {
    it('wires the home breadcrumb to the authoritative platform home path in platform mode', () => {
        render(
            <BrowserRouter>
                <AppHeader
                    collapsed={false}
                    match={null}
                    mode="platform"
                    homePath={PLATFORM_HOME_PATH}
                    homeLabel="Platform"
                    onToggleCollapsed={vi.fn()}
                    onOpenMobile={vi.fn()}
                />
            </BrowserRouter>,
        );

        const homeLink = screen.getByRole('link', { name: 'Platform' });
        expect(homeLink).toHaveAttribute('href', PLATFORM_HOME_PATH);
    });

    it('closes the user profile menu on Escape and restores focus to summary', () => {
        render(
            <BrowserRouter>
                <AppHeader
                    collapsed={false}
                    match={null}
                    mode="tenant"
                    onToggleCollapsed={vi.fn()}
                    onOpenMobile={vi.fn()}
                />
            </BrowserRouter>,
        );

        const summary = screen.getByLabelText('User account menu');
        const details = summary.closest('details');
        expect(details).toBeInTheDocument();

        // Open the details dropdown
        details?.setAttribute('open', '');
        expect(details).toHaveAttribute('open');

        // Fire Escape keydown
        fireEvent.keyDown(document, { key: 'Escape' });

        expect(details).not.toHaveAttribute('open');
        expect(document.activeElement).toBe(summary);
    });
});
