/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactElement } from 'react';
import { Route, Routes } from 'react-router-dom';
import { TestRouter } from '@/test/TestRouter';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import CreateUserPage from './CreateUserPage';
import PermissionCataloguePage from './PermissionCataloguePage';
import RoleDetailPage from './RoleDetailPage';
import RoleEditPage from './RoleEditPage';
import RoleListPage from './RoleListPage';
import UserDetailPage from './UserDetailPage';
import UserListPage from './UserListPage';
import { accessPermissions } from './accessPermissions';
import { organizationUnitPermissions } from '@/modules/organization-unit/organizationUnitPermissions';
const apiMocks = vi.hoisted(() => ({
    archiveUser: vi.fn(),
    createUser: vi.fn(),
    changeUserStatus: vi.fn(),
    getRole: vi.fn(),
    getUser: vi.fn(),
    getUserPasswordPolicy: vi.fn(),
    listAllOrganizationUnits: vi.fn(),
    listAllPermissions: vi.fn(),
    listPermissions: vi.fn(),
    listPermissionModules: vi.fn(),
    listAllRoles: vi.fn(),
    listRoles: vi.fn(),
    listUsers: vi.fn(),
    listUserDocuments: vi.fn(),
    listUserDevices: vi.fn(),
}));
const authState = vi.hoisted(() => ({
    permissions: [] as string[],
    roles: [] as string[],
}));
vi.mock('./accessApi', () => ({
    accessApi: apiMocks,
}));
vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => ({
        user: { id: 1, name: 'Admin User', email: 'admin@example.test' },
        token: 'token',
        tenant: { id: 1, name: 'Tenant' },
        organizationUnit: { id: 1, name: 'Head Office' },
        roles: authState.roles,
        permissions: authState.permissions,
        permissionsLoaded: true,
        enabledModules: ['user'],
        isAuthenticated: true,
        isLoading: false,
        login: vi.fn(),
        logout: vi.fn(),
        loadCurrentUser: vi.fn(),
    }),
}));
describe('Access pages', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        authState.roles = [];
        const excludedPermissions = new Set<string>([
            accessPermissions.userDocumentsManage,
            accessPermissions.userDevicesManage,
        ]);
        authState.permissions = [
            ...Object.values(accessPermissions).filter((permission) => !excludedPermissions.has(permission)),
            organizationUnitPermissions.view,
        ];
        apiMocks.listUsers.mockResolvedValue(collection([accessUser()]));
        apiMocks.getRole.mockResolvedValue(accessRole());
        apiMocks.getUser.mockResolvedValue(accessUser());
        apiMocks.getUserPasswordPolicy.mockResolvedValue({ minimum_length: 12, mixed_case: true, numbers: true, symbols: true });
        apiMocks.listAllRoles.mockResolvedValue([{ id: 5, row_version: 1, name: 'Manager' }]);
        apiMocks.listRoles.mockResolvedValue(collection([accessRole()]));
        apiMocks.listAllOrganizationUnits.mockResolvedValue([
            { id: 1, name: 'Head Office', is_default: true },
            { id: 2, name: 'Branch Office', is_default: false },
        ]);
        apiMocks.listAllPermissions.mockResolvedValue([
            {
                id: 10,
                name: accessPermissions.usersView,
                module: 'Users',
                resource: 'users',
                action: 'view',
                description: 'View users.',
                status: 'system_defined',
                is_read_only: true,
            },
        ]);
        apiMocks.listPermissions.mockResolvedValue(collection([{
            id: 10,
            name: accessPermissions.usersView,
            module: 'Users',
            resource: 'users',
            action: 'view',
            description: 'View users.',
            status: 'system_defined',
            is_read_only: true,
        }]));
        apiMocks.listPermissionModules.mockResolvedValue(['User']);
        apiMocks.listUserDocuments.mockResolvedValue(collection([]));
        apiMocks.listUserDevices.mockResolvedValue(collection([]));
    });
    it('renders create user as a focused direct account flow with password entry', async () => {
        renderPage(<CreateUserPage />, ['/access/users/create']);
        expect(screen.getByRole('heading', { name: 'Create User' })).toBeInTheDocument();
        expect(await screen.findByText('Initial password')).toBeInTheDocument();
        expect(screen.getByText('Initial roles')).toBeInTheDocument();
        expect(screen.getByText('Organization access')).toBeInTheDocument();
        expect(screen.getByLabelText(/^Password/)).toBeInTheDocument();
        expect(screen.getByLabelText(/^Confirm password/)).toBeInTheDocument();
        expect(screen.queryByRole('tab')).not.toBeInTheDocument();
    });
    it('uses server-side user filters without sending organization_unit_id as context', async () => {
        const user = userEvent.setup();
        renderPage(<UserListPage />, ['/access/users']);
        expect(await screen.findAllByText('Ada User')).not.toHaveLength(0);
        await user.selectOptions(screen.getByLabelText('Organization unit'), '2');
        await waitFor(() => {
            expect(apiMocks.listUsers).toHaveBeenLastCalledWith(
                expect.objectContaining({ organization_unit_filter_id: 2 }),
                expect.any(AbortSignal),
            );
        });
        expect(apiMocks.listUsers).toHaveBeenLastCalledWith(
            expect.not.objectContaining({ organization_unit_id: 2 }),
            expect.any(AbortSignal),
        );
    });
    it('renders user detail read-only', async () => {
        renderPage(<RoutePage page={<UserDetailPage />} path="/access/users/:id" />, ['/access/users/7']);
        expect(await screen.findByRole('heading', { name: 'Ada User' })).toBeInTheDocument();
        expect(screen.getByText('Role-based access')).toBeInTheDocument();
        expect(screen.getByText('Direct permission exceptions')).toBeInTheDocument();
        expect(screen.getByText('Organization access')).toBeInTheDocument();
        expect(screen.queryByLabelText('Temporary password')).not.toBeInTheDocument();
        expect(screen.queryByRole('textbox')).not.toBeInTheDocument();
        expect(screen.queryByRole('checkbox')).not.toBeInTheDocument();
    });
    it('renders permissions as a read-only catalogue', async () => {
        renderPage(<PermissionCataloguePage />, ['/access/permissions']);
        expect(screen.getByRole('heading', { name: 'Permissions' })).toBeInTheDocument();
        expect(await screen.findAllByText(accessPermissions.usersView)).not.toHaveLength(0);
        expect(screen.getAllByText('System Defined')).not.toHaveLength(0);
        expect(screen.queryByRole('button', { name: /add|edit|delete/i })).not.toBeInTheDocument();
    });
    it('skips role and organization unit filter fetches for users.view-only actors on UserListPage', async () => {
        authState.permissions = [accessPermissions.usersView];
        renderPage(<UserListPage />, ['/access/users']);
        expect(await screen.findAllByText('Ada User')).not.toHaveLength(0);
        expect(apiMocks.listAllRoles).not.toHaveBeenCalled();
        expect(apiMocks.listAllOrganizationUnits).not.toHaveBeenCalled();
    });
    it('allows roles.assign_permissions actors to reach role editing and tracks permission dirty state', async () => {
        const user = userEvent.setup();
        authState.permissions = [accessPermissions.rolesView, accessPermissions.rolesAssignPermissions];

        renderPage(<RoleListPage />, ['/access/roles']);
        const editLinks = await screen.findAllByRole('link', { name: 'Edit' });
        expect(editLinks.length).toBeGreaterThan(0);
        expect(editLinks[0]).toHaveAttribute('href', '/access/roles/5/edit');

        renderPage(<RoutePage page={<RoleDetailPage />} path="/access/roles/:id" />, ['/access/roles/5']);
        expect(await screen.findAllByRole('link', { name: 'Edit' })).not.toHaveLength(0);

        renderPage(<RoutePage page={<RoleEditPage />} path="/access/roles/:id/edit" />, ['/access/roles/5/edit']);
        const savePermissionsButton = await screen.findByRole('button', { name: 'Save Permissions' });
        expect(savePermissionsButton).toBeDisabled();

        await user.click(screen.getByRole('checkbox'));
        expect(savePermissionsButton).toBeEnabled();
    });
});
function RoutePage({ page, path }: { page: ReactElement; path: string }) {
    return (
        <Routes>
            <Route path={path} element={page} />
        </Routes>
    );
}
function renderPage(page: ReactElement, initialEntries: string[]) {
    return render(
        <TestRouter initialEntries={initialEntries}>
            {page}
        </TestRouter>,
    );
}
function accessUser() {
    return {
        id: 7,
        row_version: 1,
        name: 'Ada User',
        first_name: 'Ada',
        last_name: 'User',
        username: 'ada',
        email: 'ada@example.test',
        phone: '+94110000000',
        status: 'active',
        roles: [{ id: 5, name: 'Manager' }],
        direct_permissions: [{ id: 10, name: accessPermissions.usersView, module: 'Users' }],
        organization_units: [{ id: 1, name: 'Head Office', is_default: true }],
        default_organization_unit_id: 1,
        credentials_ready: true,
        last_login_at: '2026-06-17T10:00:00.000000Z',
    };
}
function accessRole() {
    return {
        id: 5,
        row_version: 1,
        name: 'Manager',
        description: 'Branch manager role',
        system_key: null,
        is_system: false,
        assigned_users_count: 1,
        permissions_count: 0,
        permissions: [],
    };
}
function collection<T>(data: T[]) {
    return {
        data,
        meta: { current_page: 1, from: data.length ? 1 : null, last_page: 1, per_page: 25, to: data.length, total: data.length },
    };
}
