import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ApiError } from '@/shared/api/apiError';
import VehicleUseRegisterPage from './VehicleUseRegisterPage';
import { listVehicleUseRegister, vehicleUseHistory } from './vehicleUseApi';
import { timestampWithOffset, VehicleUseStatus, type VehicleUse } from './vehicleUse';
vi.mock('./vehicleUseApi', () => ({ listVehicleUseRegister: vi.fn(), vehicleUseHistory: vi.fn() }));
const row: VehicleUse = {
    id: 12, row_version: 2, status: VehicleUseStatus.InCustody,
    vehicle: { id: 7, label: 'CAR-1234' }, customer_agreement: { id: 3, reference: 'CUSTOMER-A', party_name: 'Example Customer', version: 2 }, owner_agreement: null,
    starts_at: '2026-09-07T09:00:00+05:30', ends_at: null,
    handed_over_at: '2026-09-07T03:30:00+00:00', returned_at: null, handover_odometer: '0.000000', return_odometer: null, notes: null,
    replaces_use: { id: 11, vehicle_label: 'CAR-OLD' },
};
beforeEach(() => { vi.clearAllMocks(); vi.mocked(listVehicleUseRegister).mockResolvedValue({ data: [row] }); vi.mocked(vehicleUseHistory).mockResolvedValue({ data: [] }); });
describe('Vehicle Use register', () => {
    it('shows planned and actual dates separately, replacement context and expandable custody evidence', async () => {
        render(<VehicleUseRegisterPage />);
        expect(await screen.findByText('CAR-1234 · CUSTOMER-A')).toBeInTheDocument();
        expect(screen.getByText('Customer: Example Customer')).toBeInTheDocument();
        expect(screen.getByText(`Planned: ${row.starts_at} — Open-ended`)).toBeInTheDocument();
        expect(screen.getByText(`Handed over: ${row.handed_over_at}`)).toBeInTheDocument();
        expect(screen.getByText('Replaces vehicle CAR-OLD')).toBeInTheDocument();
        expect(vehicleUseHistory).not.toHaveBeenCalled();
        fireEvent.click(screen.getByRole('button', { name: 'Review CAR-1234' }));
        expect(screen.getByText('Handover odometer: 0.000000')).toBeInTheDocument();
        expect(screen.getByText('Return odometer: Not recorded')).toBeInTheDocument();
        await waitFor(() => expect(vehicleUseHistory).toHaveBeenCalledWith(row.id, 1, expect.any(AbortSignal)));
    });
    it('applies search, state and explicit-offset planned period filters', async () => {
        render(<VehicleUseRegisterPage />); await screen.findByText('CAR-1234 · CUSTOMER-A');
        vi.mocked(listVehicleUseRegister).mockResolvedValue({ data: [] });
        fireEvent.change(screen.getByLabelText('Vehicle, agreement or party'), { target: { value: ' SEARCH ' } });
        fireEvent.change(screen.getByLabelText('Use status'), { target: { value: VehicleUseStatus.Returned } });
        const start = '2026-09-07T10:00';
        fireEvent.change(screen.getByLabelText('Planned period start (optional)'), { target: { value: start } });
        fireEvent.click(screen.getByRole('button', { name: 'Apply filters' }));
        expect(await screen.findByText('No vehicle uses match these filters.')).toBeInTheDocument();
        expect(listVehicleUseRegister).toHaveBeenLastCalledWith({ search: 'SEARCH', use_status: VehicleUseStatus.Returned, from: timestampWithOffset(start), until: undefined }, 1, expect.any(AbortSignal));
    });
    it('hides old results after a failed filter request', async () => {
        render(<VehicleUseRegisterPage />); await screen.findByText('CAR-1234 · CUSTOMER-A');
        vi.mocked(listVehicleUseRegister).mockRejectedValue(new ApiError('Period end must follow its start.', 422));
        fireEvent.click(screen.getByRole('button', { name: 'Apply filters' }));
        expect(await screen.findByText('Period end must follow its start.')).toBeInTheDocument();
        expect(screen.queryByText('CAR-1234 · CUSTOMER-A')).not.toBeInTheDocument();
    });
});
