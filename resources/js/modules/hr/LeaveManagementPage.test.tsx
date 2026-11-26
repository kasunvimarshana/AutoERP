/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import type { ReactNode } from 'react';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ApiError } from '@/shared/api/apiError';
import { configureBusinessTimeZone } from '@/shared/utils/businessDate';
import LeaveManagementPage from './LeaveManagementPage';

const authState = vi.hoisted(() => ({
    canView: true,
    canApply: true,
    canManage: true,
}));

const hrApiMocks = vi.hoisted(() => ({
    actionCompOffClaim: vi.fn(),
    actionLeaveRequest: vi.fn(),
    applyLeave: vi.fn(),
    cancelLeaveRequest: vi.fn(),
    claimCompOff: vi.fn(),
    createLeavePolicy: vi.fn(),
    createLeaveType: vi.fn(),
    deleteLeavePolicy: vi.fn(),
    deleteLeaveType: vi.fn(),
    listCompOffClaims: vi.fn(),
    listLeaveBalances: vi.fn(),
    listLeavePolicies: vi.fn(),
    listLeaveRequests: vi.fn(),
    listLeaveTypes: vi.fn(),
    updateLeavePolicy: vi.fn(),
    updateLeaveType: vi.fn(),
}));

vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => ({ user: { id: 1 } }),
}));

vi.mock('@/modules/auth/accessControl', () => ({
    hasPermission: (_auth: unknown, permission: string) => {
        if (permission === 'hr.leaves.view') return authState.canView;
        if (permission === 'hr.leaves.apply') return authState.canApply;
        if (permission === 'hr.leaves.manage') return authState.canManage;
        return false;
    },
}));

vi.mock('@/app/errors/AccessDeniedPage', () => ({
    AccessDeniedPage: () => <div>Access Denied</div>,
}));

vi.mock('@/shared/components/ContentHeader', () => ({
    ContentHeader: ({ title, description }: { title: ReactNode; description?: ReactNode }) => (
        <div>
            <h1>{title}</h1>
            <p>{description}</p>
        </div>
    ),
}));

vi.mock('./components/EmployeeLookupSelect', () => ({
    EmployeeLookupSelect: ({ label }: { label?: string }) => <div>{label || 'EmployeeLookupSelect'}</div>,
}));

vi.mock('./components/HrLeaveTypeSelect', () => ({
    HrLeaveTypeSelect: ({ label, onChange }: { label?: string; onChange: (val: unknown) => void }) => (
        <button
            type="button"
            onClick={() => onChange({ id: 10, row_version: 1, code: 'ANNUAL', name: 'Annual Leave', color: '#10B981' })}
        >
            {label || 'HrLeaveTypeSelect'}
        </button>
    ),
}));

vi.mock('./components/HrDepartmentSelect', () => ({
    HrDepartmentSelect: ({ label }: { label?: string }) => <div>{label || 'HrDepartmentSelect'}</div>,
}));

vi.mock('./components/HrEmploymentTypeSelect', () => ({
    HrEmploymentTypeSelect: ({ label }: { label?: string }) => <div>{label || 'HrEmploymentTypeSelect'}</div>,
}));

vi.mock('./hrApi', () => hrApiMocks);

const emptyCollection = {
    data: [],
    meta: { current_page: 1, last_page: 1, per_page: 25, total: 0, from: null, to: null },
};

describe('LeaveManagementPage', () => {
    beforeEach(() => {
        configureBusinessTimeZone('UTC');
        authState.canView = true;
        authState.canApply = true;
        authState.canManage = true;

        vi.clearAllMocks();
        hrApiMocks.listLeaveBalances.mockResolvedValue(emptyCollection);
        hrApiMocks.listLeaveRequests.mockResolvedValue(emptyCollection);
        hrApiMocks.listCompOffClaims.mockResolvedValue(emptyCollection);
        hrApiMocks.listLeaveTypes.mockResolvedValue(emptyCollection);
        hrApiMocks.listLeavePolicies.mockResolvedValue(emptyCollection);
    });

    it('denies access when user lacks leaves.view permission', () => {
        authState.canView = false;
        render(<LeaveManagementPage />);
        expect(screen.getByText('Access Denied')).toBeInTheDocument();
    });

    it('renders all 4 tabs and gates management actions by permission', async () => {
        authState.canApply = false;
        authState.canManage = false;

        render(<LeaveManagementPage />);

        expect(screen.getByRole('tab', { name: 'Balances & Requests' })).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: 'Comp-Off Claims' })).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: 'Leave Types' })).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: 'Leave Policies' })).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: '+ Apply for Leave' })).not.toBeInTheDocument();

        fireEvent.click(screen.getByRole('tab', { name: 'Leave Types' }));
        await waitFor(() => {
            expect(hrApiMocks.listLeaveTypes).toHaveBeenCalled();
        });
        expect(screen.queryByRole('button', { name: '+ New Leave Type' })).not.toBeInTheDocument();
    });

    it('supports Leave Types update with expected_version OCC and renders modal error', async () => {
        hrApiMocks.listLeaveTypes.mockResolvedValue({
            data: [
                {
                    id: 10,
                    row_version: 3,
                    code: 'ANNUAL',
                    name: 'Annual Leave',
                    color: '#10B981',
                    is_paid: true,
                    requires_attachment: false,
                    attachment_mandatory_after_days: 2,
                    allow_half_day: true,
                    allow_hourly: false,
                    is_compensatory: false,
                    sandwich_rule_applied: false,
                    status: 'active',
                },
            ],
            meta: { current_page: 1, last_page: 1, per_page: 25, total: 1, from: 1, to: 1 },
        });
        hrApiMocks.updateLeaveType.mockRejectedValueOnce(new ApiError('Leave type was changed by someone else.', 409, 'CONFLICT'));

        render(<LeaveManagementPage />);

        fireEvent.click(screen.getByRole('tab', { name: 'Leave Types' }));
        await waitFor(() => {
            expect(screen.getByText('Annual Leave')).toBeInTheDocument();
        });

        fireEvent.click(screen.getByRole('button', { name: 'Edit' }));
        fireEvent.click(screen.getByRole('button', { name: 'Save Changes' }));

        await waitFor(() => {
            expect(hrApiMocks.updateLeaveType).toHaveBeenCalledWith(
                10,
                expect.objectContaining({
                    code: 'ANNUAL',
                    expected_version: 3,
                }),
            );
            expect(screen.getByText('Leave type was changed by someone else.')).toBeInTheDocument();
        });
    });

    it('supports Leave Policies update with expected_version OCC', async () => {
        hrApiMocks.listLeavePolicies.mockResolvedValue({
            data: [
                {
                    id: 20,
                    row_version: 2,
                    leave_type_id: 10,
                    name: 'Standard Annual Policy',
                    annual_allocation_days: '14.00',
                    accrual_frequency: 'annual_upfront',
                    max_carry_forward_days: '5.00',
                    carry_forward_expiry_months: 3,
                    max_consecutive_days: 10,
                    probation_restricted: false,
                    effective_from: '2026-01-01',
                    effective_to: null,
                    leave_type: { id: 10, row_version: 1, code: 'ANNUAL', name: 'Annual Leave', color: '#10B981', is_paid: true, requires_attachment: false, attachment_mandatory_after_days: 2, allow_half_day: true, allow_hourly: false, is_compensatory: false, sandwich_rule_applied: false, status: 'active' },
                },
            ],
            meta: { current_page: 1, last_page: 1, per_page: 25, total: 1, from: 1, to: 1 },
        });
        hrApiMocks.updateLeavePolicy.mockResolvedValue({});

        render(<LeaveManagementPage />);

        fireEvent.click(screen.getByRole('tab', { name: 'Leave Policies' }));
        await waitFor(() => {
            expect(screen.getByText('Standard Annual Policy')).toBeInTheDocument();
        });

        fireEvent.click(screen.getByRole('button', { name: 'Edit' }));
        fireEvent.click(screen.getByRole('button', { name: 'Save Changes' }));

        await waitFor(() => {
            expect(hrApiMocks.updateLeavePolicy).toHaveBeenCalledWith(
                20,
                expect.objectContaining({
                    name: 'Standard Annual Policy',
                    expected_version: 2,
                }),
            );
        });
    });

    it('forwards expected_version when approving Comp-Off Claims', async () => {
        hrApiMocks.listCompOffClaims.mockResolvedValue({
            data: [
                {
                    id: 30,
                    row_version: 4,
                    employee_id: 5,
                    worked_date: '2026-11-22',
                    claimed_days: '1.00',
                    reason: 'Holiday deployment',
                    status: 'pending',
                    employee: { id: 5, employee_number: 'EMP-5', display_name: 'Kasun Perera', status: 'active' },
                },
            ],
            meta: { current_page: 1, last_page: 1, per_page: 25, total: 1, from: 1, to: 1 },
        });
        hrApiMocks.actionCompOffClaim.mockResolvedValue({});

        render(<LeaveManagementPage />);

        fireEvent.click(screen.getByRole('tab', { name: 'Comp-Off Claims' }));
        await waitFor(() => {
            expect(screen.getByText('Holiday deployment')).toBeInTheDocument();
        });

        fireEvent.click(screen.getByRole('button', { name: 'Approve' }));

        await waitFor(() => {
            expect(hrApiMocks.actionCompOffClaim).toHaveBeenCalledWith(30, true, 4);
        });
    });
});
