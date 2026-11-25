/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import type { ReactNode } from 'react';
import { act, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { configureBusinessTimeZone } from '@/shared/utils/businessDate';
import AttendanceManagementPage from './AttendanceManagementPage';
import LeaveManagementPage from './LeaveManagementPage';
import RosterSchedulerPage from './RosterSchedulerPage';
import { PayrollLoanManagementPage } from '@/modules/payroll/PayrollLoanManagementPage';

const hrApiMocks = vi.hoisted(() => ({
    actionAttendanceRegularization: vi.fn(),
    actionLeaveRequest: vi.fn(),
    applyLeave: vi.fn(),
    assignShift: vi.fn(),
    cancelLeaveRequest: vi.fn(),
    claimCompOff: vi.fn(),
    commitAttendanceImport: vi.fn(),
    directAdjustAttendanceRecord: vi.fn(),
    getKioskQrToken: vi.fn(),
    getRoster: vi.fn(),
    listAttendanceAnomalies: vi.fn(),
    listAttendanceKiosks: vi.fn(),
    listAttendanceRecords: vi.fn(),
    listAttendanceRegularizations: vi.fn(),
    listLeaveBalances: vi.fn(),
    listLeaveRequests: vi.fn(),
    previewAttendanceImport: vi.fn(),
    punchAttendance: vi.fn(),
    quickResolveAttendanceAnomaly: vi.fn(),
    reallocateShiftCover: vi.fn(),
    requestAttendanceRegularization: vi.fn(),
    requestShiftSwap: vi.fn(),
    scanAttendanceAnomalies: vi.fn(),
}));

const payrollApiMocks = vi.hoisted(() => ({
    approveLoan: vi.fn(),
    cancelLoan: vi.fn(),
    createLoan: vi.fn(),
    disburseLoan: vi.fn(),
    getLoan: vi.fn(),
    listLoans: vi.fn(),
}));

vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => ({ user: { id: 1, permissions: ['*'] } }),
}));

vi.mock('@/modules/auth/accessControl', () => ({
    hasPermission: () => true,
}));

vi.mock('@/shared/components/ContentHeader', () => ({
    ContentHeader: ({ title, actions }: { title: ReactNode; actions?: ReactNode }) => (
        <div>
            <h1>{title}</h1>
            {actions}
        </div>
    ),
}));

vi.mock('./components/EmployeeLookupSelect', () => ({
    EmployeeLookupSelect: () => <div>EmployeeLookupSelect</div>,
}));

vi.mock('./components/HrLeaveTypeSelect', () => ({
    HrLeaveTypeSelect: () => <div>HrLeaveTypeSelect</div>,
}));

vi.mock('./components/HrShiftSelect', () => ({
    HrShiftSelect: () => <div>HrShiftSelect</div>,
}));

vi.mock('@/modules/payroll/components/PayPeriodSelect', () => ({
    PayPeriodSelect: () => <div>PayPeriodSelect</div>,
}));

vi.mock('./hrApi', () => hrApiMocks);
vi.mock('@/modules/payroll/payrollApi', () => payrollApiMocks);

describe('HR & Payroll business date defaults near UTC midnight', () => {
    beforeEach(() => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        // 2026-11-29T19:00:00Z is 2026-11-29 (Sunday) in UTC,
        // but 2026-11-30 00:30 (Monday) in Asia/Colombo (UTC+05:30).
        vi.setSystemTime(new Date('2026-11-29T19:00:00.000Z'));
        configureBusinessTimeZone('Asia/Colombo');

        hrApiMocks.listAttendanceRecords.mockResolvedValue({ data: [] });
        hrApiMocks.listAttendanceRegularizations.mockResolvedValue([]);
        hrApiMocks.listAttendanceAnomalies.mockResolvedValue({ data: [] });
        hrApiMocks.listAttendanceKiosks.mockResolvedValue([]);
        hrApiMocks.listLeaveRequests.mockResolvedValue({ data: [] });
        hrApiMocks.listLeaveBalances.mockResolvedValue([]);
        hrApiMocks.getRoster.mockResolvedValue([]);
        payrollApiMocks.listLoans.mockResolvedValue([
            {
                id: 42,
                loan_number: 'LN-042',
                employee: { id: 7, employee_number: 'EMP-007', display_name: 'Kasun' },
                loan_type: 'loan',
                principal_amount: '50000.00',
                interest_rate: '5.00',
                term_months: 12,
                installment_amount: '4280.00',
                remaining_balance: '50000.00',
                status: 'approved',
                disbursed_date: null,
                notes: null,
            },
        ]);
    });

    afterEach(() => {
        configureBusinessTimeZone(null);
        vi.useRealTimers();
        vi.clearAllMocks();
    });

    it('uses Asia/Colombo civil dates for AttendanceManagementPage filters and regularization form', async () => {
        render(<AttendanceManagementPage />);

        await waitFor(() => {
            expect(hrApiMocks.listAttendanceRecords).toHaveBeenCalledWith(
                expect.objectContaining({
                    start_date: '2026-11-23',
                    end_date: '2026-11-30',
                }),
            );
        });

        await act(async () => {
            fireEvent.click(screen.getByRole('button', { name: 'Request Regularization' }));
        });

        const dateInputs = document.querySelectorAll('input[type="date"]');
        const values = Array.from(dateInputs).map((el) => (el as HTMLInputElement).value);
        expect(values).toContain('2026-11-23');
        expect(values).toContain('2026-11-30');
    });

    it('uses Asia/Colombo civil dates for LeaveManagementPage apply leave and comp-off modals', async () => {
        render(<LeaveManagementPage />);

        await waitFor(() => {
            expect(hrApiMocks.listLeaveRequests).toHaveBeenCalled();
        });

        await act(async () => {
            fireEvent.click(screen.getByRole('button', { name: '+ Apply for Leave' }));
        });

        const leaveDateInputs = Array.from(document.querySelectorAll('input[type="date"]'))
            .map((el) => (el as HTMLInputElement).value);
        expect(leaveDateInputs.length).toBeGreaterThanOrEqual(2);
        expect(leaveDateInputs.every((v) => v === '2026-11-30')).toBe(true);

        await act(async () => {
            fireEvent.click(screen.getByRole('button', { name: 'Cancel' }));
            fireEvent.click(screen.getByRole('button', { name: 'Claim Comp-Off' }));
        });

        const compOffDateInputs = Array.from(document.querySelectorAll('input[type="date"]'))
            .map((el) => (el as HTMLInputElement).value);
        expect(compOffDateInputs).toContain('2026-11-30');
    });

    it('uses Asia/Colombo civil Monday-Sunday week for RosterSchedulerPage', async () => {
        render(<RosterSchedulerPage />);

        await waitFor(() => {
            expect(hrApiMocks.getRoster).toHaveBeenCalledWith({
                start_date: '2026-11-30',
                end_date: '2026-12-06',
            });
        });
    });

    it('uses Asia/Colombo civil date for PayrollLoanManagementPage disbursement default', async () => {
        render(<PayrollLoanManagementPage />);

        await waitFor(() => {
            expect(screen.getByRole('button', { name: 'Disburse' })).toBeInTheDocument();
        });

        await act(async () => {
            fireEvent.click(screen.getByRole('button', { name: 'Disburse' }));
        });

        const dateInputs = Array.from(document.querySelectorAll('input[type="date"]'))
            .map((el) => (el as HTMLInputElement).value);
        expect(dateInputs).toContain('2026-11-30');
    });
});
