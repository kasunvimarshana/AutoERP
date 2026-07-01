import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { RouterProvider, createMemoryRouter } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { NavigationSection } from '@/app/navigation/navigationTypes';
import { WorkspaceLayout } from './WorkspaceLayout';

const auth = vi.hoisted(() => ({
    user: { id: 1, name: 'Admin User', email: 'admin@example.test' },
    token: 'token',
    tenant: { id: 1, name: 'AutoERP' },
    organizationUnit: null,
    roles: [] as string[],
    permissions: [] as string[],
    permissionsLoaded: true,
    enabledModules: [] as string[],
    enabledModulesLoaded: true,
    isPlatformOperator: true,
    authMode: 'platform',
    isAuthenticated: true,
    isLoading: false,
    bootstrapError: null,
    login: vi.fn(),
    logout: vi.fn(),
    loadCurrentUser: vi.fn(),
    switchOrganizationUnit: vi.fn(),
}));

vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => auth,
}));

const sections: NavigationSection[] = [
    {
        id: 'operations',
        label: 'Operations',
        items: [
            {
                id: 'purchase',
                type: 'module',
                label: 'Purchase',
                icon: 'purchase',
                access: { requiresPlatformOperator: true },
                children: [
                    { id: 'purchase-orders', type: 'link', label: 'Purchase Orders', to: '/purchase/orders', access: { requiresPlatformOperator: true } },
                ],
            },
            {
                id: 'sales',
                type: 'module',
                label: 'Sales',
                icon: 'sales',
                access: { requiresPlatformOperator: true },
                children: [
                    { id: 'sales-orders', type: 'link', label: 'Sales Orders', to: '/sales/orders', access: { requiresPlatformOperator: true } },
                ],
            },
        ],
    },
];

describe('WorkspaceLayout sidebar expansion', () => {
    beforeEach(() => {
        window.localStorage.removeItem('autoerp.platform.sidebar.collapsed');
    });

    it('lets a clicked module open even when another module contains the active route', async () => {
        const user = userEvent.setup();
        const router = renderWorkspace('/purchase/orders');

        expect(screen.getByText('Purchase page')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Purchase Orders' })).toHaveAttribute('aria-current', 'page');
        expect(screen.queryByRole('link', { name: 'Sales Orders' })).not.toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Sales' }));

        expect(screen.getByRole('link', { name: 'Sales Orders' })).toBeInTheDocument();
        expect(screen.queryByRole('link', { name: 'Purchase Orders' })).not.toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Purchase' })).toHaveAttribute('aria-expanded', 'false');
        expect(screen.getByRole('button', { name: 'Sales' })).toHaveAttribute('aria-expanded', 'true');

        await act(async () => {
            await router.navigate('/purchase/orders?refresh=1');
        });

        expect(screen.getByRole('link', { name: 'Purchase Orders' })).toHaveAttribute('aria-current', 'page');
        expect(screen.queryByRole('link', { name: 'Sales Orders' })).not.toBeInTheDocument();
    });
});

function renderWorkspace(initialEntry: string) {
    const router = createMemoryRouter([
        {
            path: '/',
            element: (
                <WorkspaceLayout
                    sections={sections}
                    homePath="/purchase/orders"
                    workspaceLabel="Test workspace"
                    mode="platform"
                />
            ),
            children: [
                { path: 'purchase/orders', element: <div>Purchase page</div> },
                { path: 'sales/orders', element: <div>Sales page</div> },
            ],
        },
    ], { initialEntries: [initialEntry] });

    render(<RouterProvider router={router} />);

    return router;
}
