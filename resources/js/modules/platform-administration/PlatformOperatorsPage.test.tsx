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
    resendOperatorInvitation: vi.fn(),
    revokeOperatorInvitation: vi.fn(),
}));

vi.mock('@/modules/auth/AuthProvider', () => ({ useAuth: () => ({}) }));
vi.mock('@/modules/auth/accessControl', () => ({ hasPermission: () => mocks.canManage }));
vi.mock('./platformAdministrationApi', () => ({
    platformAdministrationApi: {
        listOperators: mocks.listOperators,
        updateOperatorPermissions: mocks.updateOperatorPermissions,
        changeOperatorStatus: mocks.changeOperatorStatus,
        createOperator: mocks.createOperator,
        resendOperatorInvitation: mocks.resendOperatorInvitation,
        revokeOperatorInvitation: mocks.revokeOperatorInvitation,
    },
}));

describe('PlatformOperatorsPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mocks.canManage = false;
        mocks.listOperators.mockResolvedValue(operatorPage([activeOperator()]));
        mocks.updateOperatorPermissions.mockResolvedValue({ ...activeOperator(), permissions: ['platform.audit.view'] });
        mocks.createOperator.mockResolvedValue(invitedOperator());
        mocks.resendOperatorInvitation.mockResolvedValue(invitedOperator());
        mocks.revokeOperatorInvitation.mockResolvedValue({ ...invitedOperator(), status: 'inactive', invitation: null });
    });

    it('keeps lifecycle and permission controls hidden for view-only operators', async () => {
        render(<TestRouter initialEntries={['/administration/platform-operators']}><PlatformOperatorsPage /></TestRouter>);

        expect((await screen.findAllByText('Platform Manager')).length).toBeGreaterThan(0);
        expect(screen.queryByRole('button', { name: 'Invite operator' })).not.toBeInTheDocument();
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

    it('creates an invited identity without collecting an administrator-known password', async () => {
        mocks.canManage = true;
        const user = userEvent.setup();
        render(<TestRouter initialEntries={['/administration/platform-operators']}><PlatformOperatorsPage /></TestRouter>);

        expect((await screen.findAllByText('Platform Manager')).length).toBeGreaterThan(0);
        await user.click(screen.getByRole('button', { name: 'Invite operator' }));
        const dialog = screen.getByRole('dialog', { name: 'Invite platform operator' });
        expect(within(dialog).queryByLabelText(/password/i)).not.toBeInTheDocument();

        await user.type(within(dialog).getByLabelText('First name'), 'New');
        await user.type(within(dialog).getByLabelText('Last name'), 'Operator');
        await user.type(within(dialog).getByRole('textbox', { name: /Platform email/ }), 'NEW.OPERATOR@EXAMPLE.TEST');
        await user.click(within(dialog).getByRole('checkbox', { name: /operators.*manage/i }));
        await user.click(within(dialog).getByRole('button', { name: 'Send invitation' }));

        await waitFor(() => expect(mocks.createOperator).toHaveBeenCalledWith({
            first_name: 'New',
            last_name: 'Operator',
            email: 'new.operator@example.test',
            permissions: ['platform.operators.manage'],
        }));
    });

    it('requeues the active invitation without claiming that earlier copies are invalid', async () => {
        mocks.canManage = true;
        mocks.listOperators.mockResolvedValue(operatorPage([invitedOperator()]));
        const user = userEvent.setup();
        render(<TestRouter initialEntries={['/administration/platform-operators']}><PlatformOperatorsPage /></TestRouter>);

        expect((await screen.findAllByRole('button', { name: 'Resend invitation' })).length).toBeGreaterThan(0);
        expect(screen.getAllByRole('button', { name: 'Revoke invitation' }).length).toBeGreaterThan(0);
        expect(screen.queryByRole('button', { name: 'Activate' })).not.toBeInTheDocument();

        await user.click(screen.getAllByRole('button', { name: 'Resend invitation' })[0]);
        const dialog = screen.getByRole('dialog', { name: 'Resend invitation to Pending Operator' });
        expect(within(dialog).getByText(/existing copies remain valid/i)).toBeInTheDocument();
        expect(within(dialog).queryByText(/previous invitation link becomes invalid/i)).not.toBeInTheDocument();
        await user.click(within(dialog).getByRole('button', { name: 'Resend invitation' }));

        await waitFor(() => expect(mocks.resendOperatorInvitation).toHaveBeenCalledWith(invitedOperator()));
        expect(await screen.findByText(/active invitation was queued again/i)).toBeInTheDocument();
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
        invitation: null,
        permissions: ['platform.operators.manage'],
        row_version: 4,
        created_at: '2026-06-24T00:00:00Z',
        updated_at: '2026-06-25T00:00:00Z',
    };
}

function invitedOperator() {
    return {
        id: 3,
        first_name: 'Pending',
        last_name: 'Operator',
        display_name: 'Pending Operator',
        email: 'pending@example.test',
        status: 'invited' as const,
        invitation: {
            status: 'pending' as const,
            delivery_status: 'sent' as const,
            expires_at: '2026-06-26T00:00:00Z',
            sent_at: '2026-06-25T00:00:00Z',
            failed_at: null,
            error_message: null,
        },
        permissions: ['platform.operators.manage'],
        row_version: 1,
        created_at: '2026-06-25T00:00:00Z',
        updated_at: '2026-06-25T00:00:00Z',
    };
}

function operatorPage(data: Array<ReturnType<typeof activeOperator> | ReturnType<typeof invitedOperator>>) {
    return {
        data,
        meta: { current_page: 1, from: 1, last_page: 1, per_page: 20, to: 1, total: 1 },
        available_permissions: ['platform.audit.view', 'platform.operators.manage'],
    };
}
