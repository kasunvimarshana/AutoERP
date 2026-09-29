import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, expect, it, vi } from 'vitest';
import { requestLookup } from '@/shared/api/lookupRequest';
import { VehicleUseEditor } from './VehicleUseEditor';
import { AGREEMENT_API, type Agreement } from './agreements';
import { planVehicleUse } from './vehicleUseApi';
import { timestampWithOffset } from './vehicleUse';
vi.mock('@/shared/api/lookupRequest', () => ({ requestLookup: vi.fn() }));
vi.mock('./vehicleUseApi', () => ({ planVehicleUse: vi.fn(), replaceVehicleUse: vi.fn() }));
const agreement = { id: 3, reference: 'CUSTOMER-A', row_version: 2 } as Agreement;
beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(requestLookup).mockResolvedValueOnce({ data: [{ id: 11, registration_number: 'CAR-123', vehicle_number: 'CAR' }] })
        .mockResolvedValueOnce({ data: [{ id: 21, name: 'OWNER-A · Example Owner' }] });
});
it('uses the planned period for owner lookup and clears its selection when dates change', async () => {
    render(<VehicleUseEditor agreement={agreement} onSaved={vi.fn()} onCancel={vi.fn()} />);
    fireEvent.change(screen.getByRole('combobox', { name: 'Vehicle' }), { target: { value: 'CAR' } });
    fireEvent.click(await screen.findByRole('option', { name: 'CAR-123' }));
    expect(screen.queryByRole('combobox', { name: 'Owner agreement' })).not.toBeInTheDocument();
    const start = '2026-09-07T09:00'; const end = '2026-09-08T09:00';
    fireEvent.change(screen.getByLabelText('Planned handover'), { target: { value: start } });
    fireEvent.change(screen.getByLabelText('Planned return (optional)'), { target: { value: end } });
    fireEvent.change(screen.getByRole('combobox', { name: 'Owner agreement' }), { target: { value: 'OWNER' } });
    fireEvent.click(await screen.findByRole('option', { name: 'OWNER-A · Example Owner' }));
    expect(requestLookup).toHaveBeenLastCalledWith(`${AGREEMENT_API}/vehicles/11/sources`, expect.any(Object), { starts_at: timestampWithOffset(start), ends_at: timestampWithOffset(end) });
    fireEvent.click(screen.getByRole('button', { name: 'Save assignment' }));
    await waitFor(() => expect(planVehicleUse).toHaveBeenCalledWith(agreement, expect.objectContaining({ owner_agreement_id: 21, starts_at: timestampWithOffset(start), ends_at: timestampWithOffset(end) })));
    await waitFor(() => expect(screen.getByRole('button', { name: 'Save assignment' })).toBeEnabled());
    fireEvent.change(screen.getByLabelText('Planned return (optional)'), { target: { value: '' } });
    expect(screen.getByRole('combobox', { name: 'Owner agreement' })).toHaveValue('');
    expect(screen.getByRole('button', { name: 'Save assignment' })).toBeDisabled();
});
it('makes open-ended coverage explicit in the source request', async () => {
    render(<VehicleUseEditor agreement={agreement} onSaved={vi.fn()} onCancel={vi.fn()} />);
    fireEvent.change(screen.getByRole('combobox', { name: 'Vehicle' }), { target: { value: 'CAR' } });
    fireEvent.click(await screen.findByRole('option', { name: 'CAR-123' }));
    const start = '2026-09-07T09:00';
    fireEvent.change(screen.getByLabelText('Planned handover'), { target: { value: start } });
    fireEvent.change(screen.getByRole('combobox', { name: 'Owner agreement' }), { target: { value: 'OWNER' } });
    await screen.findByRole('option', { name: 'OWNER-A · Example Owner' });
    expect(requestLookup).toHaveBeenLastCalledWith(`${AGREEMENT_API}/vehicles/11/sources`, expect.any(Object), { starts_at: timestampWithOffset(start), ends_at: '' });
});
