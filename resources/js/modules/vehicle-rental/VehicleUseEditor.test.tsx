import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, expect, it, vi } from 'vitest';
import { requestLookup } from '@/shared/api/lookupRequest';
import { VehicleUseEditor } from './VehicleUseEditor';
import { AGREEMENT_API, type Agreement } from './agreements';
import { planVehicleUse, replaceVehicleUse } from './vehicleUseApi';
import { timestampWithOffset, VehicleUseStatus, type VehicleUse } from './vehicleUse';
vi.mock('@/shared/api/lookupRequest', () => ({ requestLookup: vi.fn() }));
vi.mock('./vehicleUseApi', () => ({ planVehicleUse: vi.fn(), replaceVehicleUse: vi.fn() }));
const agreement = { id: 3, reference: 'CUSTOMER-A', row_version: 2 } as Agreement;
beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(requestLookup).mockReset();
    vi.mocked(requestLookup).mockImplementation((url) => Promise.resolve(
        url.includes('/lookup/active')
            ? { data: [{ id: 11, registration_number: 'CAR-123', vehicle_number: 'CAR' }] }
            : { data: [{ id: 21, name: 'OWNER-A · Example Owner' }] },
    ));
});
it('uses the planned period for owner lookup and clears its selection when dates change', async () => {
    render(<VehicleUseEditor agreement={agreement} onSaved={vi.fn()} onCancel={vi.fn()} />);
    fireEvent.change(screen.getByRole('combobox', { name: 'Vehicle' }), { target: { value: 'CAR' } });
    fireEvent.click(await screen.findByRole('option', { name: 'CAR-123' }));
    expect(screen.queryByRole('combobox', { name: 'Owner agreement' })).not.toBeInTheDocument();
    const start = '2026-09-07T09:00'; const end = '2026-09-08T09:00';
    fireEvent.change(screen.getByLabelText('Planned handover'), { target: { value: start } });
    expect(screen.getByLabelText('Planned return (optional)')).toHaveAttribute('min', start);
    fireEvent.change(screen.getByLabelText('Planned return (optional)'), { target: { value: end } });
    fireEvent.change(screen.getByRole('combobox', { name: 'Owner agreement' }), { target: { value: 'OWNER' } });
    fireEvent.click(await screen.findByRole('option', { name: 'OWNER-A · Example Owner' }));
    expect(requestLookup).toHaveBeenLastCalledWith(`${AGREEMENT_API}/vehicles/11/sources`, expect.any(Object), { starts_at: timestampWithOffset(start), ends_at: timestampWithOffset(end), customer_agreement_id: 3 });
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
    expect(requestLookup).toHaveBeenLastCalledWith(`${AGREEMENT_API}/vehicles/11/sources`, expect.any(Object), { starts_at: timestampWithOffset(start), ends_at: '', customer_agreement_id: 3 });
});

it('allows auto-selecting covering owner agreement when planned handover is set', async () => {
    render(<VehicleUseEditor agreement={agreement} onSaved={vi.fn()} onCancel={vi.fn()} />);
    fireEvent.change(screen.getByRole('combobox', { name: 'Vehicle' }), { target: { value: 'CAR' } });
    fireEvent.click(await screen.findByRole('option', { name: 'CAR-123' }));
    fireEvent.change(screen.getByLabelText('Planned handover'), { target: { value: '2026-09-07T09:00' } });
    const autoSelectBtn = await screen.findByRole('button', { name: 'Auto-select owner agreement' });
    fireEvent.click(autoSelectBtn);
    await waitFor(() => expect(screen.getByRole('combobox', { name: 'Owner agreement' })).toHaveValue('OWNER-A · Example Owner'));
    expect(screen.getByRole('button', { name: 'Save assignment' })).toBeEnabled();
});

it('allows saving assignment with owner agreement when planned return is left blank', async () => {
    render(<VehicleUseEditor agreement={agreement} onSaved={vi.fn()} onCancel={vi.fn()} />);
    fireEvent.change(screen.getByRole('combobox', { name: 'Vehicle' }), { target: { value: 'CAR' } });
    fireEvent.click(await screen.findByRole('option', { name: 'CAR-123' }));
    const start = '2026-09-07T09:00';
    fireEvent.change(screen.getByLabelText('Planned handover'), { target: { value: start } });
    fireEvent.change(screen.getByRole('combobox', { name: 'Owner agreement' }), { target: { value: 'OWNER' } });
    fireEvent.click(await screen.findByRole('option', { name: 'OWNER-A · Example Owner' }));
    expect(screen.getByRole('button', { name: 'Save assignment' })).toBeEnabled();
    fireEvent.click(screen.getByRole('button', { name: 'Save assignment' }));
    await waitFor(() => expect(planVehicleUse).toHaveBeenCalledWith(agreement, expect.objectContaining({
        vehicle_id: 11,
        owner_agreement_id: 21,
        starts_at: timestampWithOffset(start),
        ends_at: null,
    })));
});

it('keeps company-owned assignment disabled until a planned handover is entered', async () => {
    render(<VehicleUseEditor agreement={agreement} onSaved={vi.fn()} onCancel={vi.fn()} />);
    fireEvent.change(screen.getByRole('combobox', { name: 'Vehicle' }), { target: { value: 'CAR' } });
    fireEvent.click(await screen.findByRole('option', { name: 'CAR-123' }));
    fireEvent.click(screen.getByRole('checkbox', { name: 'Company-owned vehicle' }));
    expect(screen.getByRole('button', { name: 'Save assignment' })).toBeDisabled();
    fireEvent.change(screen.getByLabelText('Planned handover'), { target: { value: '2026-09-07T09:00' } });
    expect(screen.getByRole('button', { name: 'Save assignment' })).toBeEnabled();
});

it('requires a replacement reason before confirming an atomic replacement', async () => {
    const replacement = {
        id: 73,
        row_version: 3,
        status: VehicleUseStatus.InCustody,
        customer_agreement: { id: 3, reference: 'CUSTOMER-A', party_name: 'Customer', version: 2 },
        owner_agreement: null,
        vehicle: { id: 6, label: 'CAR-OLD' },
        starts_at: '2026-09-07T09:00:00+05:30',
        ends_at: null,
        handed_over_at: '2026-09-07T09:00:00+05:30',
        returned_at: null,
        handover_odometer: null,
        return_odometer: null,
        notes: null,
    } as VehicleUse;
    vi.mocked(replaceVehicleUse).mockResolvedValue({} as never);
    render(<VehicleUseEditor agreement={agreement} replacement={replacement} onSaved={vi.fn()} onCancel={vi.fn()} />);
    fireEvent.change(screen.getByRole('combobox', { name: 'Vehicle' }), { target: { value: 'CAR' } });
    fireEvent.click(await screen.findByRole('option', { name: 'CAR-123' }));
    fireEvent.click(screen.getByRole('checkbox', { name: 'Company-owned vehicle' }));
    fireEvent.change(screen.getByLabelText('Replacement time'), { target: { value: '2026-09-08T09:00' } });
    expect(screen.getByRole('button', { name: 'Confirm replacement' })).toBeDisabled();
    fireEvent.change(screen.getByLabelText('Replacement reason'), { target: { value: 'Workshop replacement' } });
    expect(screen.getByRole('button', { name: 'Confirm replacement' })).toBeEnabled();
});

it('pre-selects the vehicle when initialVehicleId is provided', async () => {
    render(<VehicleUseEditor agreement={agreement} initialVehicleId={11} onSaved={vi.fn()} onCancel={vi.fn()} />);
    await waitFor(() => {
        expect(screen.getByRole('combobox', { name: 'Vehicle' })).toHaveValue('CAR-123');
    });
});

it('prevents selecting the same vehicle during replacement', async () => {
    const replacement = {
        id: 73,
        row_version: 3,
        status: VehicleUseStatus.InCustody,
        customer_agreement: { id: 3, reference: 'CUSTOMER-A', party_name: 'Customer', version: 2 },
        owner_agreement: null,
        vehicle: { id: 11, label: 'CAR-123' },
        starts_at: '2026-09-07T09:00:00+05:30',
        ends_at: null,
        handed_over_at: '2026-09-07T09:00:00+05:30',
        returned_at: null,
        handover_odometer: null,
        return_odometer: null,
        notes: null,
    } as VehicleUse;
    render(<VehicleUseEditor agreement={agreement} replacement={replacement} onSaved={vi.fn()} onCancel={vi.fn()} />);
    fireEvent.change(screen.getByRole('combobox', { name: 'Vehicle' }), { target: { value: 'CAR' } });
    fireEvent.click(await screen.findByRole('option', { name: 'CAR-123' }));
    fireEvent.click(screen.getByRole('checkbox', { name: 'Company-owned vehicle' }));
    fireEvent.change(screen.getByLabelText('Replacement time'), { target: { value: '2026-09-08T09:00' } });
    fireEvent.change(screen.getByLabelText('Replacement reason'), { target: { value: 'Workshop replacement' } });
    expect(screen.getByText('Select a different physical vehicle.')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Confirm replacement' })).toBeDisabled();
});

it('provides return odometer guidance and min bound during replacement when old vehicle had handover odometer', async () => {
    const replacement = {
        id: 73,
        row_version: 3,
        status: VehicleUseStatus.InCustody,
        customer_agreement: { id: 3, reference: 'CUSTOMER-A', party_name: 'Customer', version: 2 },
        owner_agreement: null,
        vehicle: { id: 6, label: 'CAR-OLD' },
        starts_at: '2026-09-07T09:00:00+05:30',
        ends_at: null,
        handed_over_at: '2026-09-07T09:00:00+05:30',
        returned_at: null,
        handover_odometer: '1250.00',
        return_odometer: null,
        notes: null,
    } as VehicleUse;
    render(<VehicleUseEditor agreement={agreement} replacement={replacement} onSaved={vi.fn()} onCancel={vi.fn()} />);
    const timeInput = screen.getByLabelText('Replacement time');
    expect(timeInput).toHaveAttribute('min', '2026-09-07T09:00');
    expect(timeInput).toHaveAttribute('max', expect.stringContaining('T23:59:59'));
    expect(screen.getByText(/Replacement time must follow previous handover .* and cannot be in the future\./)).toBeInTheDocument();
    expect(screen.getByText(/Handover reading was 1250\.00\. Return reading cannot be lower\./)).toBeInTheDocument();
    const oldOdoInput = screen.getByLabelText('Old vehicle return odometer (optional)');
    expect(oldOdoInput).toHaveAttribute('min', '1250.00');
});

it('prevents submitting future replacement time with immediate validation feedback', async () => {
    const replacement = {
        id: 73,
        row_version: 3,
        status: VehicleUseStatus.InCustody,
        customer_agreement: { id: 3, reference: 'CUSTOMER-A', party_name: 'Customer', version: 2 },
        owner_agreement: null,
        vehicle: { id: 6, label: 'CAR-OLD' },
        starts_at: '2026-09-07T09:00:00+05:30',
        ends_at: null,
        handed_over_at: '2026-09-07T09:00:00+05:30',
        returned_at: null,
        handover_odometer: null,
        return_odometer: null,
        notes: null,
    } as VehicleUse;
    render(<VehicleUseEditor agreement={agreement} replacement={replacement} onSaved={vi.fn()} onCancel={vi.fn()} />);
    fireEvent.change(screen.getByRole('combobox', { name: 'Vehicle' }), { target: { value: 'CAR' } });
    fireEvent.click(await screen.findByRole('option', { name: 'CAR-123' }));
    fireEvent.click(screen.getByRole('checkbox', { name: 'Company-owned vehicle' }));
    fireEvent.change(screen.getByLabelText('Replacement time'), { target: { value: '2099-01-01T12:00' } });
    fireEvent.change(screen.getByLabelText('Replacement reason'), { target: { value: 'Workshop replacement' } });
    fireEvent.submit(screen.getByRole('form', { name: 'Replace vehicle' }));
    await waitFor(() => {
        expect(screen.getAllByText('Replacement time cannot be in the future.').length).toBeGreaterThan(0);
    });
    expect(replaceVehicleUse).not.toHaveBeenCalled();
});

it('prevents submitting replacement time at or before previous handover with immediate validation feedback', async () => {
    const replacement = {
        id: 73,
        row_version: 3,
        status: VehicleUseStatus.InCustody,
        customer_agreement: { id: 3, reference: 'CUSTOMER-A', party_name: 'Customer', version: 2 },
        owner_agreement: null,
        vehicle: { id: 6, label: 'CAR-OLD' },
        starts_at: '2026-09-07T09:00:00+05:30',
        ends_at: null,
        handed_over_at: '2026-09-07T09:00:00+05:30',
        returned_at: null,
        handover_odometer: null,
        return_odometer: null,
        notes: null,
    } as VehicleUse;
    render(<VehicleUseEditor agreement={agreement} replacement={replacement} onSaved={vi.fn()} onCancel={vi.fn()} />);
    fireEvent.change(screen.getByRole('combobox', { name: 'Vehicle' }), { target: { value: 'CAR' } });
    fireEvent.click(await screen.findByRole('option', { name: 'CAR-123' }));
    fireEvent.click(screen.getByRole('checkbox', { name: 'Company-owned vehicle' }));
    fireEvent.change(screen.getByLabelText('Replacement time'), { target: { value: '2026-09-07T09:00' } });
    fireEvent.change(screen.getByLabelText('Replacement reason'), { target: { value: 'Workshop replacement' } });
    fireEvent.submit(screen.getByRole('form', { name: 'Replace vehicle' }));
    await waitFor(() => {
        expect(screen.getAllByText("Replacement time must follow the previous vehicle's handover time.").length).toBeGreaterThan(0);
    });
    expect(replaceVehicleUse).not.toHaveBeenCalled();
});
