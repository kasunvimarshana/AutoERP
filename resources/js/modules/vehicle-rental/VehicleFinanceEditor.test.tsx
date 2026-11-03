import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { requestLookup } from '@/shared/api/lookupRequest';
import { lookupApi } from '@/shared/api/lookupApi';
import { VehicleFinanceEditor } from './VehicleFinanceEditor';
import { createFinanceAgreement, FinanceStatus, type VehicleFinanceAgreement } from './vehicleFinanceApi';

vi.mock('@/shared/api/lookupRequest', () => ({ requestLookup: vi.fn() }));
vi.mock('@/shared/api/lookupApi', () => ({
    lookupApi: {
        suppliers: vi.fn(),
    },
}));
vi.mock('./vehicleFinanceApi', async (importOriginal) => {
    const actual = await importOriginal<typeof import('./vehicleFinanceApi')>();
    return {
        ...actual,
        createFinanceAgreement: vi.fn(),
    };
});

const mockAgreement: VehicleFinanceAgreement = {
    id: 1,
    row_version: 1,
    agreement_number: 'FIN-2026-0001',
    supplier: { id: 15, name: 'Commercial Leasing & Finance' },
    vehicle: { id: 30, label: 'WP-NC-5678 (Toyota HiAce)', make: 'Toyota', model: 'HiAce' },
    currency: { id: 1, code: 'LKR' },
    status: FinanceStatus.Draft,
    agreement_date: '2026-11-01',
    starts_at: '2026-11-01T00:00:00Z',
    matures_at: '2027-11-01T00:00:00Z',
    principal_amount: '1200000.00',
    initial_deposit_amount: '200000.00',
    residual_value: '0.00',
    interest_method: 'flat',
    annual_interest_rate: '12.00',
    installment_frequency: 'monthly',
    installment_count: 12,
    payment_term_days: 30,
    remarks: 'Fleet expansion HP lease',
    created_at: '2026-11-01 10:00:00',
    installments: [],
};

beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(lookupApi.suppliers).mockResolvedValue({
        data: [{ id: 15, name: 'Commercial Leasing & Finance' }],
    });
    vi.mocked(requestLookup).mockImplementation((url: string) => Promise.resolve(
        url.includes('/lookup/active')
            ? { data: [{ id: 30, registration_number: 'WP-NC-5678', vehicle_number: 'CAR-5678' }] }
            : { data: [{ id: 1, name: 'Sri Lankan Rupee', code: 'LKR' }] },
    ));
});

describe('VehicleFinanceEditor', () => {
    it('creates a vehicle finance agreement with controlled lookups', async () => {
        const onSaved = vi.fn();
        const onCancel = vi.fn();
        vi.mocked(createFinanceAgreement).mockResolvedValue(mockAgreement);

        render(<VehicleFinanceEditor onSaved={onSaved} onCancel={onCancel} />);

        // Supplier selection via LookupSelect
        fireEvent.change(screen.getByRole('combobox', { name: 'Financing Company / Supplier' }), { target: { value: 'Commercial' } });
        fireEvent.click(await screen.findByRole('option', { name: 'Commercial Leasing & Finance' }));

        // Vehicle selection via LookupSelect
        fireEvent.change(screen.getByRole('combobox', { name: 'Financed Vehicle' }), { target: { value: '5678' } });
        fireEvent.click(await screen.findByRole('option', { name: /WP-NC-5678/i }));

        // Currency selection via LookupSelect
        fireEvent.change(screen.getByRole('combobox', { name: 'Currency' }), { target: { value: 'LKR' } });
        fireEvent.click(await screen.findByRole('option', { name: 'LKR - Sri Lankan Rupee' }));

        // Submit form
        const submitBtn = screen.getByRole('button', { name: 'Generate Schedule & Create Agreement' });
        expect(submitBtn).toBeEnabled();
        fireEvent.click(submitBtn);

        await waitFor(() => {
            expect(createFinanceAgreement).toHaveBeenCalledWith(expect.objectContaining({
                supplier_id: 15,
                vehicle_id: 30,
                currency_id: 1,
                principal_amount: '1000000.00',
            }));
            expect(onSaved).toHaveBeenCalled();
        });
    });
});
