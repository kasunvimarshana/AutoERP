vi.mock('@/modules/auth/AuthProvider', () => ({ useAuth: () => ({ roles: [], permissions: [], permissionsLoaded: true }) }));
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ApiError } from '@/shared/api/apiError';
import { AgreementKind, AgreementStatus, RentalBasis, DriverMode, type Agreement } from './agreements';
import { VehicleUsePanel } from './VehicleUsePanel';
import { listVehicleUses, transitionVehicleUse } from './vehicleUseApi';
import { VehicleUseStatus, VehicleUseAction, type VehicleUse } from './vehicleUse';
vi.mock('./vehicleUseApi', () => ({ listVehicleUses: vi.fn(), transitionVehicleUse: vi.fn(), vehicleUseHistory: vi.fn() }));
const agreement = { id: 20, row_version: 2, reference: 'CUSTOMER-AG', status: AgreementStatus.Active, basis: RentalBasis.Monthly, driver_mode: DriverMode.SelfDrive, party: { id: 1, name: 'Customer' }, currency: { id: 1, name: 'Rupee' }, terms: {}, kind: AgreementKind.Customer } as unknown as Agreement;
const row: VehicleUse = { id: 73, row_version: 3, status: VehicleUseStatus.Planned, customer_agreement: { id: 20, reference: 'CUSTOMER-AG', party_name: 'Customer', version: 2 }, owner_agreement: null, vehicle: { id: 6, label: 'CAR-1234' }, starts_at: '2026-09-07T09:00:00+05:30', ends_at: '2026-09-08T09:00:00+05:30', handed_over_at: null, returned_at: null, handover_odometer: null, return_odometer: null, notes: null };
beforeEach(() => { vi.clearAllMocks(); vi.mocked(listVehicleUses).mockResolvedValue({ data: [row] }); });
describe('Rental vehicle use', () => {
    it('shows readable vehicle and source context with no writes for view-only access', async () => {
        render(<VehicleUsePanel agreement={agreement} canManage={false} />);
        expect(await screen.findByText('CAR-1234 · Planned')).toBeInTheDocument();
        expect(screen.getByText('Company supply')).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Assign vehicle' })).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Hand over vehicle' })).not.toBeInTheDocument();
    });
    it('keeps stale cancellation visible and sends the reviewed version', async () => {
        vi.mocked(transitionVehicleUse).mockRejectedValue(new ApiError('This record changed. Reload before continuing.', 409));
        render(<VehicleUsePanel agreement={agreement} canManage />);
        fireEvent.click(await screen.findByRole('button', { name: 'Cancel plan' }));
        expect(screen.getByRole('button', { name: 'Confirm action' })).toBeDisabled();
        fireEvent.change(screen.getByLabelText('Action reason'), { target: { value: 'Booking withdrawn' } });
        fireEvent.click(screen.getByRole('button', { name: 'Confirm action' }));
        expect(await screen.findByText('This record changed. Reload before continuing.')).toBeInTheDocument();
        expect(transitionVehicleUse).toHaveBeenCalledWith(row, VehicleUseAction.Cancel, { reason: 'Booking withdrawn' });
        fireEvent.click(screen.getByRole('button', { name: 'Reload vehicles' }));
        await waitFor(() => expect(listVehicleUses).toHaveBeenCalledTimes(2));
    });
    it('offers return for custody and preserves unknown odometer', async () => {
        vi.mocked(listVehicleUses).mockResolvedValue({ data: [{ ...row, status: VehicleUseStatus.InCustody, handed_over_at: row.starts_at }] });
        render(<VehicleUsePanel agreement={agreement} canManage />);
        expect(await screen.findByRole('button', { name: 'Record return' })).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Cancel plan' })).not.toBeInTheDocument();
        expect(screen.getByText(/Odometer: Not recorded/)).toBeInTheDocument();
    });
});
