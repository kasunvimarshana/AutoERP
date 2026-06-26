import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactElement } from 'react';
import { Route, Routes } from 'react-router-dom';
import { TestRouter } from '@/test/TestRouter';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import CreateUserPage from './CreateUserPage';
import PermissionCataloguePage from './PermissionCataloguePage';
import UserDetailPage from './UserDetailPage';
import UserListPage from './UserListPage';
import { accessPermissions } from './accessPermissions';
const apiMocks = vi.hoisted(() => ({
    archiveUser: vi.fn(),
    createUser: vi.fn(),
    changeUserStatus: vi.fn(),
    getUser: vi.fn(),
    listAllOrganizationUnits: vi.fn(),
    listAllPermissions: vi.fn(),
    listPermissions: vi.fn(),
    listPermissionModules: vi.fn(),
    listAllRoles: vi.fn(),
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
        authState.permissions = Object.values(accessPermissions).filter((permission) => ![
            accessPermissions.userDocumentsManage,
            accessPermissions.userDevicesManage,
        ].includes(permission));
        apiMocks.listUsers.mockResolvedValue(collection([accessUser()]));
        apiMocks.getUser.mockResolvedValue(accessUser());
        apiMocks.listAllRoles.mockResolvedValue([{ id: 5, row_version: 1, name: 'Manager' }]);
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
    it('renders create user as a focused invitation flow without password entry', async () => {
        renderPage(<CreateUserPage />, ['/access/users/create']);
        expect(screen.getByRole('heading', { name: 'Invite User' })).toBeInTheDocument();
        expect(await screen.findByText('Invitation workflow')).toBeInTheDocument();
        expect(screen.getByText('Role assignments')).toBeInTheDocument();
        expect(screen.getByText('Organization access')).toBeInTheDocument();
        expect(screen.queryByLabelText('Temporary password')).not.toBeInTheDocument();
        expect(screen.queryByRole('tab')).not.toBeInTheDocument();
    });
    it('uses server-side user filters without sending organization_unit_id as context', async () => {
        const user = userEvent.setup();
        renderPage(<UserListPage />, ['/access/users']);
        expect(await screen.findAllByText('Ada User')).not.toHaveLength(0);
        await user.selectOptions(screen.getByLabelText('Organization Unit'), '2');
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
function collection<T>(data: T[]) {
    return {
        data,
        meta: { current_page: 1, from: data.length ? 1 : null, last_page: 1, per_page: 25, to: data.length, total: data.length },
    };
}
