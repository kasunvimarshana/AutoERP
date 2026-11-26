/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import type { ReactNode } from 'react';
import { fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ApiError } from '@/shared/api/apiError';
import { configureBusinessTimeZone } from '@/shared/utils/businessDate';
import RosterSchedulerPage from './RosterSchedulerPage';

const authState = vi.hoisted(() => ({
    canView: true,
    canManage: true,
}));

const hrApiMocks = vi.hoisted(() => ({
    assignRotation: vi.fn(),
    assignShift: vi.fn(),
    cancelRotationAssignment: vi.fn(),
    cancelShiftAssignment: vi.fn(),
    cancelShiftReallocation: vi.fn(),
    cancelShiftSwap: vi.fn(),
    createHoliday: vi.fn(),
    createHolidayCalendar: vi.fn(),
    createRotationStep: vi.fn(),
    createShift: vi.fn(),
    createShiftBreak: vi.fn(),
    createShiftRotation: vi.fn(),
    deleteHoliday: vi.fn(),
    deleteHolidayCalendar: vi.fn(),
    deleteRotationStep: vi.fn(),
    deleteShift: vi.fn(),
    deleteShiftBreak: vi.fn(),
    deleteShiftRotation: vi.fn(),
    getRoster: vi.fn(),
    getShiftRotation: vi.fn(),
    listHolidayCalendars: vi.fn(),
    listHolidays: vi.fn(),
    listRotationAssignments: vi.fn(),
    listShiftAssignments: vi.fn(),
    listShiftBreaks: vi.fn(),
    listShiftReallocations: vi.fn(),
    listShiftRotations: vi.fn(),
    listShifts: vi.fn(),
    listShiftSwaps: vi.fn(),
    managerActionShiftSwap: vi.fn(),
    peerActionShiftSwap: vi.fn(),
    reallocateShiftCover: vi.fn(),
    requestShiftSwap: vi.fn(),
    updateHoliday: vi.fn(),
    updateHolidayCalendar: vi.fn(),
    updateRotationAssignment: vi.fn(),
    updateRotationStep: vi.fn(),
    updateShift: vi.fn(),
    updateShiftAssignment: vi.fn(),
    updateShiftBreak: vi.fn(),
    updateShiftReallocation: vi.fn(),
    updateShiftRotation: vi.fn(),
}));

vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => ({ user: { id: 1 } }),
}));

vi.mock('@/modules/auth/accessControl', () => ({
    hasPermission: (_auth: unknown, permission: string) => {
        if (permission === 'hr.shifts.view') return authState.canView;
        if (permission === 'hr.shifts.manage') return authState.canManage;
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

vi.mock('./components/HrShiftSelect', () => ({
    HrShiftSelect: ({ label }: { label?: string }) => <div>{label || 'HrShiftSelect'}</div>,
}));

vi.mock('./components/HrShiftRotationSelect', () => ({
    HrShiftRotationSelect: ({ label }: { label?: string }) => <div>{label || 'HrShiftRotationSelect'}</div>,
}));

vi.mock('./hrApi', () => hrApiMocks);

describe('RosterSchedulerPage (Phase 1B Shift Management)', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        authState.canView = true;
        authState.canManage = true;
        configureBusinessTimeZone('Asia/Colombo');

        hrApiMocks.getRoster.mockResolvedValue([]);
        hrApiMocks.listShifts.mockResolvedValue({
            data: [
                {
                    id: 10,
                    row_version: 3,
                    tenant_id: 1,
                    code: 'MORNING',
                    name: 'Morning Shift',
                    color: '#3B82F6',
                    start_time: '08:00:00',
                    end_time: '17:00:00',
                    is_overnight: false,
                    grace_in_minutes: 15,
                    grace_out_minutes: 15,
                    half_day_threshold_minutes: 240,
                    full_day_threshold_minutes: 480,
                    is_break_paid: false,
                    auto_deduct_break: false,
                    auto_deduct_break_minutes: 0,
                    auto_deduct_threshold_minutes: 300,
                    is_flexible: false,
                    status: 'active',
                    breaks: [],
                },
            ],
            meta: { current_page: 1, last_page: 1, per_page: 25, total: 1, from: 1, to: 1 },
        });
        hrApiMocks.updateShift.mockResolvedValue({});
        hrApiMocks.listShiftAssignments.mockResolvedValue({ data: [] });
        hrApiMocks.listShiftSwaps.mockResolvedValue({ data: [] });
        hrApiMocks.listShiftReallocations.mockResolvedValue({ data: [] });
        hrApiMocks.listShiftRotations.mockResolvedValue({ data: [] });
        hrApiMocks.listRotationAssignments.mockResolvedValue({ data: [] });
        hrApiMocks.listHolidayCalendars.mockResolvedValue({
            data: [
                {
                    id: 5,
                    row_version: 1,
                    code: 'LK-2026',
                    name: 'Sri Lanka 2026',
                    calendar_year: 2026,
                    is_default: true,
                    holidays_count: 1,
                },
            ],
        });
        hrApiMocks.listHolidays.mockResolvedValue({
            data: [
                {
                    id: 21,
                    row_version: 1,
                    calendar_id: 5,
                    name: 'New Year',
                    holiday_date: '2026-01-01',
                    is_mandatory: true,
                    is_comp_off_eligible: true,
                    overtime_multiplier: '2.00',
                },
            ],
        });
    });

    it('renders AccessDeniedPage when user lacks hr.shifts.view permission', () => {
        authState.canView = false;
        render(<RosterSchedulerPage />);
        expect(screen.getByText('Access Denied')).toBeInTheDocument();
    });

    it('renders all 7 shift management tabs and sends expected_version on shift update', async () => {
        render(<RosterSchedulerPage />);

        expect(screen.getByRole('tab', { name: 'Roster Grid' })).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: 'Shifts & Breaks' })).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: 'Shift Assignments' })).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: 'Shift Swaps' })).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: 'Shift Reallocations' })).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: 'Rotations & Assignments' })).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: 'Holiday Calendars' })).toBeInTheDocument();

        fireEvent.click(screen.getByRole('tab', { name: 'Shifts & Breaks' }));

        await waitFor(() => {
            expect(hrApiMocks.listShifts).toHaveBeenCalledWith({ page: 1, per_page: 25 });
            expect(screen.getByText('Morning Shift')).toBeInTheDocument();
        });

        fireEvent.click(screen.getByRole('button', { name: 'Edit' }));
        fireEvent.click(screen.getByRole('button', { name: 'Update Shift' }));

        await waitFor(() => {
            expect(hrApiMocks.updateShift).toHaveBeenCalledWith(
                10,
                expect.objectContaining({
                    code: 'MORNING',
                    expected_version: 3,
                }),
            );
        });
    });

    it('hides manage actions when user lacks hr.shifts.manage permission and loads holiday calendars', async () => {
        authState.canManage = false;
        render(<RosterSchedulerPage />);

        expect(screen.queryByRole('button', { name: '+ Assign Shift' })).not.toBeInTheDocument();

        fireEvent.click(screen.getByRole('tab', { name: 'Holiday Calendars' }));

        await waitFor(() => {
            expect(hrApiMocks.listHolidayCalendars).toHaveBeenCalledWith({
                calendar_year: undefined,
                page: 1,
                per_page: 25,
            });
            expect(screen.getByText('Sri Lanka 2026')).toBeInTheDocument();
        });

        await waitFor(() => {
            expect(hrApiMocks.listHolidays).toHaveBeenCalledWith(5, { page: 1, per_page: 50 });
            expect(screen.getByText('New Year')).toBeInTheDocument();
        });

        expect(screen.queryByRole('button', { name: '+ New Holiday Calendar' })).not.toBeInTheDocument();
    });

    it('displays 409/422 ApiError field messages inside the open modal', async () => {
        hrApiMocks.updateShift.mockRejectedValueOnce(
            new ApiError('The given data was invalid.', 422, null, null, {
                half_day_threshold_minutes: ['Half day threshold minutes must be less than or equal to full day threshold minutes.'],
            }),
        );

        render(<RosterSchedulerPage />);
        fireEvent.click(screen.getByRole('tab', { name: 'Shifts & Breaks' }));

        await waitFor(() => {
            expect(screen.getByText('Morning Shift')).toBeInTheDocument();
        });

        fireEvent.click(screen.getByRole('button', { name: 'Edit' }));
        const dialog = screen.getByRole('dialog');
        fireEvent.click(within(dialog).getByRole('button', { name: 'Update Shift' }));

        await waitFor(() => {
            expect(within(dialog).getByRole('alert')).toHaveTextContent(
                'Half day threshold minutes must be less than or equal to full day threshold minutes.',
            );
        });
    });

    it('loads shift breaks in modal and creates a break', async () => {
        hrApiMocks.listShiftBreaks.mockResolvedValueOnce([
            {
                id: 31,
                row_version: 1,
                shift_id: 10,
                name: 'Lunch',
                break_type: 'meal',
                start_time: '12:00:00',
                end_time: '12:30:00',
                duration_minutes: 30,
                is_paid: false,
                is_mandatory: true,
            },
        ]);
        hrApiMocks.createShiftBreak.mockResolvedValueOnce({});

        render(<RosterSchedulerPage />);
        fireEvent.click(screen.getByRole('tab', { name: 'Shifts & Breaks' }));

        await waitFor(() => {
            expect(screen.getByText('Morning Shift')).toBeInTheDocument();
        });

        fireEvent.click(screen.getByRole('button', { name: 'Breaks' }));

        const dialog = await screen.findByRole('dialog');
        await waitFor(() => {
            expect(hrApiMocks.listShiftBreaks).toHaveBeenCalledWith(10);
            expect(within(dialog).getByText('Lunch')).toBeInTheDocument();
        });
    });

    it('renders shift swaps queue and supports manager approval with expected_version', async () => {
        hrApiMocks.listShiftSwaps.mockResolvedValueOnce({
            data: [
                {
                    id: 77,
                    row_version: 4,
                    requester_employee_id: 1,
                    target_employee_id: 2,
                    swap_date: '2026-11-27',
                    target_swap_date: '2026-11-28',
                    reason: 'Family appointment',
                    status: 'peer_accepted',
                    requester_employee: { id: 1, employee_number: 'EMP-001', display_name: 'Alice' },
                    target_employee: { id: 2, employee_number: 'EMP-002', display_name: 'Bob' },
                },
            ],
            meta: { current_page: 1, last_page: 1, per_page: 25, total: 1, from: 1, to: 1 },
        });
        hrApiMocks.managerActionShiftSwap.mockResolvedValueOnce({});

        render(<RosterSchedulerPage />);
        fireEvent.click(screen.getByRole('tab', { name: 'Shift Swaps' }));

        await waitFor(() => {
            expect(screen.getByText('Alice')).toBeInTheDocument();
            expect(screen.getByText('Bob')).toBeInTheDocument();
        });

        fireEvent.click(screen.getByRole('button', { name: 'Approve' }));
        const dialog = screen.getByRole('dialog');
        fireEvent.click(within(dialog).getByRole('button', { name: 'Approve Swap' }));

        await waitFor(() => {
            expect(hrApiMocks.managerActionShiftSwap).toHaveBeenCalledWith(77, true, undefined, 4);
        });
    });
});
