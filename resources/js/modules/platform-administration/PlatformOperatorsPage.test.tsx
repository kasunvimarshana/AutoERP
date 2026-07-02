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
        mocks.listOperators.mockResolvedValue(operatorPage([activeOperator()]));
        mocks.updateOperatorPermissions.mockResolvedValue({ ...activeOperator(), permissions: ['platform.audit.view'] });
        mocks.createOperator.mockResolvedValue({ ...activeOperator(), first_name: 'New', last_name: 'Operator', display_name: 'New Operator', email: 'new.operator@example.test' });
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
            activeOperator(),
            ['platform.audit.view', 'platform.operators.manage'],
        ));
    });

    it('creates an active identity with an initial password', async () => {
        mocks.canManage = true;
        const user = userEvent.setup();
        render(<TestRouter initialEntries={['/administration/platform-operators']}><PlatformOperatorsPage /></TestRouter>);

        expect((await screen.findAllByText('Platform Manager')).length).toBeGreaterThan(0);
        await user.click(screen.getByRole('button', { name: 'Create operator' }));
        const dialog = screen.getByRole('dialog', { name: 'Create platform operator' });

        await user.type(within(dialog).getByLabelText('First name'), 'New');
        await user.type(within(dialog).getByLabelText('Last name'), 'Operator');
        await user.type(within(dialog).getByRole('textbox', { name: /Platform email/ }), 'NEW.OPERATOR@EXAMPLE.TEST');
        await user.type(within(dialog).getByLabelText(/^Password/), 'StrongPassword!123');
        await user.type(within(dialog).getByLabelText(/^Confirm password/), 'StrongPassword!123');
        await user.click(within(dialog).getByRole('checkbox', { name: /operators.*manage/i }));
        await user.click(within(dialog).getByRole('button', { name: 'Create operator' }));

        await waitFor(() => expect(mocks.createOperator).toHaveBeenCalledWith({
            first_name: 'New',
            last_name: 'Operator',
            email: 'new.operator@example.test',
            permissions: ['platform.operators.manage'],
            password: 'StrongPassword!123',
            password_confirmation: 'StrongPassword!123',
        }));
    });

});

function activeOperator() {
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

function operatorPage(data: ReturnType<typeof activeOperator>[]) {
    return {
        data,
        meta: { current_page: 1, from: 1, last_page: 1, per_page: 20, to: 1, total: 1 },
        available_permissions: ['platform.audit.view', 'platform.operators.manage'],
        password_policy: { minimum_length: 12, mixed_case: true, numbers: true, symbols: true },
    };
}
