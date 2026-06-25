import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import PlatformOperatorsPage from './PlatformOperatorsPage';

const mocks = vi.hoisted(() => ({
    canManage: false,
    listOperators: vi.fn(),
    updateOperatorPermissions: vi.fn(),
    changeOperatorStatus: vi.fn(),
    createOperator: vi.fn(),
}));

vi.mock('@/modules/auth/AuthProvider', () => ({ useAuth: () => ({}) }));
vi.mock('@/modules/auth/accessControl', () => ({ hasPermission: () => mocks.canManage }));
vi.mock('./platformAdministrationApi', () => ({
    platformAdministrationApi: {
        listOperators: mocks.listOperators,
        updateOperatorPermissions: mocks.updateOperatorPermissions,
        changeOperatorStatus: mocks.changeOperatorStatus,
        createOperator: mocks.createOperator,
    },
}));

describe('PlatformOperatorsPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mocks.canManage = false;
        mocks.listOperators.mockResolvedValue(operatorPage());
        mocks.updateOperatorPermissions.mockResolvedValue({ ...operator(), permissions: ['platform.audit.view'] });
    });

    it('keeps lifecycle and permission controls hidden for view-only operators', async () => {
        render(<TestRouter initialEntries={['/administration/platform-operators']}><PlatformOperatorsPage /></TestRouter>);

        expect((await screen.findAllByText('Platform Manager')).length).toBeGreaterThan(0);
        expect(screen.queryByRole('button', { name: 'Create operator' })).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Permissions' })).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Deactivate' })).not.toBeInTheDocument();
    });

    it('sends the complete selected permission set for authoritative synchronization', async () => {
        mocks.canManage = true;
        const user = userEvent.setup();
        render(<TestRouter initialEntries={['/administration/platform-operators']}><PlatformOperatorsPage /></TestRouter>);

        const permissionButtons = await screen.findAllByRole('button', { name: 'Permissions' });
        await user.click(permissionButtons[0]);
        const dialog = await screen.findByRole('dialog', { name: 'Permissions for Platform Manager' });
        const auditPermission = within(dialog).getByRole('checkbox', { name: /audit.*view/i });
        expect(auditPermission).not.toBeChecked();
        await user.click(auditPermission);
        await user.click(within(dialog).getByRole('button', { name: 'Save permissions' }));

        await waitFor(() => expect(mocks.updateOperatorPermissions).toHaveBeenCalledWith(
            operator(),
            ['platform.audit.view', 'platform.operators.manage'],
        ));
    });
});

function operator() {
    return {
        id: 2,
        first_name: 'Platform',
        last_name: 'Manager',
        display_name: 'Platform Manager',
        email: 'manager@example.test',
        status: 'active' as const,
        permissions: ['platform.operators.manage'],
        row_version: 4,
        created_at: '2026-06-24T00:00:00Z',
        updated_at: '2026-06-25T00:00:00Z',
    };
}

function operatorPage() {
    return {
        data: [operator()],
        meta: { current_page: 1, from: 1, last_page: 1, per_page: 20, to: 1, total: 1 },
        available_permissions: ['platform.audit.view', 'platform.operators.manage'],
    };
}
