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
import AttendanceManagementPage from './AttendanceManagementPage';
import { EmployeeCredentialTab } from './components/EmployeeCredentialTab';

const authState = vi.hoisted(() => ({
    canView: true,
    canManage: true,
    canPunch: true,
    canRegularize: true,
}));

const hrApiMocks = vi.hoisted(() => ({
    actionAttendanceRegularization: vi.fn(),
    cancelAttendanceRegularization: vi.fn(),
    commitAttendanceImport: vi.fn(),
    createAttendanceGeofence: vi.fn(),
    createAttendanceKiosk: vi.fn(),
    deleteAttendanceGeofence: vi.fn(),
    deleteAttendanceKiosk: vi.fn(),
    deleteEmployeeCredential: vi.fn(),
    directAdjustAttendanceRecord: vi.fn(),
    getKioskQrToken: vi.fn(),
    listAttendanceAnomalies: vi.fn(),
    listAttendanceGeofences: vi.fn(),
    listAttendanceKiosks: vi.fn(),
    listAttendanceRecords: vi.fn(),
    listAttendanceRegularizations: vi.fn(),
    listEmployeeCredentials: vi.fn(),
    lockAttendancePeriod: vi.fn(),
    previewAttendanceImport: vi.fn(),
    punchAttendance: vi.fn(),
    quickResolveAttendanceAnomaly: vi.fn(),
    requestAttendanceRegularization: vi.fn(),
    scanAttendanceAnomalies: vi.fn(),
    storeEmployeeCredential: vi.fn(),
    updateAttendanceGeofence: vi.fn(),
    updateAttendanceKiosk: vi.fn(),
    updateEmployeeCredentialStatus: vi.fn(),
}));

vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => ({ user: { id: 1 } }),
}));

vi.mock('@/modules/auth/accessControl', () => ({
    hasPermission: (_auth: unknown, permission: string) => {
        if (permission === 'hr.attendance.view') return authState.canView;
        if (permission === 'hr.attendance.manage') return authState.canManage;
        if (permission === 'hr.attendance.punch') return authState.canPunch;
        if (permission === 'hr.attendance.regularize') return authState.canRegularize;
        return false;
    },
}));

vi.mock('@/app/errors/AccessDeniedPage', () => ({
    AccessDeniedPage: () => <div>Access Denied</div>,
}));

vi.mock('@/shared/components/ContentHeader', () => ({
    ContentHeader: ({ title, description, actions }: { title: ReactNode; description?: ReactNode; actions?: ReactNode }) => (
        <div>
            <h1>{title}</h1>
            <p>{description}</p>
            <div>{actions}</div>
        </div>
    ),
}));

vi.mock('./components/EmployeeLookupSelect', () => ({
    EmployeeLookupSelect: ({ label }: { label?: string }) => <div>{label || 'EmployeeLookupSelect'}</div>,
}));

vi.mock('./hrApi', () => hrApiMocks);

const emptyCollection = {
    data: [],
    meta: { current_page: 1, last_page: 1, per_page: 25, total: 0, from: null, to: null },
};

describe('AttendanceManagementPage & EmployeeCredentialTab (Phase 3)', () => {
    beforeEach(() => {
        configureBusinessTimeZone('UTC');
        authState.canView = true;
        authState.canManage = true;
        authState.canPunch = true;
        authState.canRegularize = true;

        vi.clearAllMocks();
        hrApiMocks.listAttendanceRecords.mockResolvedValue(emptyCollection);
        hrApiMocks.listAttendanceRegularizations.mockResolvedValue(emptyCollection);
        hrApiMocks.listAttendanceAnomalies.mockResolvedValue(emptyCollection);
        hrApiMocks.listAttendanceKiosks.mockResolvedValue(emptyCollection);
        hrApiMocks.listAttendanceGeofences.mockResolvedValue(emptyCollection);
        hrApiMocks.listEmployeeCredentials.mockResolvedValue(emptyCollection);
    });

    it('denies access when user lacks attendance.view permission', () => {
        authState.canView = false;
        render(<AttendanceManagementPage />);
        expect(screen.getByText('Access Denied')).toBeInTheDocument();
    });

    it('gates regularization, lock period, and geofence/kiosk management by hrPermissions', async () => {
        authState.canManage = false;
        authState.canRegularize = false;
        authState.canPunch = false;

        render(<AttendanceManagementPage />);

        await waitFor(() => {
            expect(hrApiMocks.listAttendanceRecords).toHaveBeenCalled();
        });

        expect(screen.queryByRole('button', { name: 'Request Regularization' })).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Lock Period' })).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Scan Missing Punches' })).not.toBeInTheDocument();

        fireEvent.click(screen.getByRole('button', { name: 'Geofences & Kiosks' }));

        await waitFor(() => {
            expect(hrApiMocks.listAttendanceGeofences).toHaveBeenCalled();
        });
        expect(screen.queryByRole('button', { name: 'New Geofence' })).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'New Kiosk' })).not.toBeInTheDocument();
    });

    it('supports locking an attendance period and displays errors inside the modal', async () => {
        hrApiMocks.lockAttendancePeriod.mockRejectedValueOnce(new ApiError('Period contains unapproved regularizations.', 422));

        render(<AttendanceManagementPage />);

        fireEvent.click(screen.getByRole('button', { name: 'Lock Period' }));
        fireEvent.click(screen.getByRole('button', { name: 'Finalize & Lock Period' }));

        await waitFor(() => {
            expect(screen.getByText('Period contains unapproved regularizations.')).toBeInTheDocument();
        });

        hrApiMocks.lockAttendancePeriod.mockResolvedValueOnce({ locked_count: 14 });
        fireEvent.click(screen.getByRole('button', { name: 'Finalize & Lock Period' }));

        await waitFor(() => {
            expect(screen.getByText(/Finalized 14 attendance record/)).toBeInTheDocument();
        });
    });

    it('passes row_version OCC on regularization approve and withdraw', async () => {
        hrApiMocks.listAttendanceRegularizations.mockResolvedValue({
            data: [
                {
                    id: 55,
                    row_version: 3,
                    employee_id: 9,
                    attendance_date: '2026-11-20',
                    regularization_type: 'forgot_to_punch',
                    reason_code: 'forgot_to_punch',
                    employee_notes: 'Forgot morning tap',
                    status: 'pending',
                    employee: { id: 9, display_name: 'Kasun Perera', employee_number: 'EMP-009' },
                },
            ],
            meta: { current_page: 1, last_page: 1, per_page: 25, total: 1, from: 1, to: 1 },
        });
        hrApiMocks.actionAttendanceRegularization.mockResolvedValue({});
        hrApiMocks.cancelAttendanceRegularization.mockResolvedValue({});

        render(<AttendanceManagementPage />);

        fireEvent.click(screen.getByRole('button', { name: /Regularization Requests/ }));

        await waitFor(() => {
            expect(screen.getByText('Kasun Perera')).toBeInTheDocument();
        });

        fireEvent.click(screen.getByRole('button', { name: 'Approve' }));
        await waitFor(() => {
            expect(hrApiMocks.actionAttendanceRegularization).toHaveBeenCalledWith(55, true, undefined, 3);
        });

        fireEvent.click(screen.getByRole('button', { name: 'Withdraw' }));
        const withdrawConfirm = await screen.findByRole('button', { name: 'Withdraw Request' });
        fireEvent.click(withdrawConfirm);

        await waitFor(() => {
            expect(hrApiMocks.cancelAttendanceRegularization).toHaveBeenCalledWith(55, 3);
        });
    });

    it('gates EmployeeCredentialTab actions by canManage and passes expected_version on status update and delete', async () => {
        hrApiMocks.listEmployeeCredentials.mockResolvedValue({
            data: [
                {
                    id: 12,
                    row_version: 4,
                    employee_id: 7,
                    credential_type: 'rfid_card',
                    credential_value: 'CARD-777',
                    label: 'Gate Badge',
                    status: 'active',
                },
            ],
            meta: { current_page: 1, last_page: 1, per_page: 25, total: 1, from: 1, to: 1 },
        });

        const { rerender } = render(<EmployeeCredentialTab employeeId={7} canManage={false} />);

        await waitFor(() => {
            expect(screen.getByText('CARD-777')).toBeInTheDocument();
        });
        expect(screen.queryByRole('button', { name: 'Issue Credential' })).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Change Status' })).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Delete' })).not.toBeInTheDocument();

        rerender(<EmployeeCredentialTab employeeId={7} canManage />);

        expect(screen.getByRole('button', { name: 'Issue Credential' })).toBeInTheDocument();
        fireEvent.click(screen.getByRole('button', { name: 'Change Status' }));
        hrApiMocks.updateEmployeeCredentialStatus.mockResolvedValue({});
        fireEvent.click(screen.getByRole('button', { name: 'Save Status' }));

        await waitFor(() => {
            expect(hrApiMocks.updateEmployeeCredentialStatus).toHaveBeenCalledWith(7, 12, {
                status: 'suspended',
                revocation_reason: undefined,
                expected_version: 4,
            });
        });

        hrApiMocks.deleteEmployeeCredential.mockResolvedValue({});
        fireEvent.click(screen.getByRole('button', { name: 'Delete' }));
        const deleteButtons = await screen.findAllByRole('button', { name: 'Delete' });
        fireEvent.click(deleteButtons[deleteButtons.length - 1]);

        await waitFor(() => {
            expect(hrApiMocks.deleteEmployeeCredential).toHaveBeenCalledWith(7, 12, 4);
        });
    });

    it('submits geofence edit with expected_version and skips kiosk QR polling when not manager', async () => {
        hrApiMocks.listAttendanceGeofences.mockResolvedValue({
            data: [
                {
                    id: 10,
                    row_version: 2,
                    code: 'HQ-GF',
                    name: 'HQ Gate',
                    latitude: 6.9271,
                    longitude: 79.8612,
                    radius_meters: 150,
                    is_active: true,
                },
            ],
            meta: { current_page: 1, last_page: 1, per_page: 100, total: 1, from: 1, to: 1 },
        });
        hrApiMocks.listAttendanceKiosks.mockResolvedValue({
            data: [
                {
                    id: 21,
                    row_version: 1,
                    kiosk_code: 'KIOSK-1',
                    name: 'Lobby',
                    rotation_seconds: 20,
                    geofence_id: 10,
                    is_active: true,
                },
            ],
            meta: { current_page: 1, last_page: 1, per_page: 100, total: 1, from: 1, to: 1 },
        });
        hrApiMocks.updateAttendanceGeofence.mockResolvedValue({});

        const { unmount } = render(<AttendanceManagementPage />);
        fireEvent.click(screen.getByRole('button', { name: 'Geofences & Kiosks' }));

        await waitFor(() => {
            expect(screen.getByText('HQ Gate')).toBeInTheDocument();
        });

        const editButtons = screen.getAllByRole('button', { name: 'Edit' });
        fireEvent.click(editButtons[0]);
        fireEvent.click(screen.getByRole('button', { name: 'Save Geofence' }));

        await waitFor(() => {
            expect(hrApiMocks.updateAttendanceGeofence).toHaveBeenCalledWith(10, expect.objectContaining({
                code: 'HQ-GF',
                name: 'HQ Gate',
                expected_version: 2,
            }));
        });

        unmount();

        authState.canManage = false;
        render(<AttendanceManagementPage />);
        fireEvent.click(screen.getByRole('button', { name: 'Dynamic QR Kiosk' }));
        await waitFor(() => {
            expect(screen.getByText(/Stationary Kiosk Rotating QR Terminal/)).toBeInTheDocument();
        });
        expect(hrApiMocks.getKioskQrToken).not.toHaveBeenCalled();
    });
});
