import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { requestLookup } from '@/shared/api/lookupRequest';
import { lookupApi } from '@/shared/api/lookupApi';
import { RentalExpenseEditor } from './RentalExpenseEditor';
import { createRentalExpense, ExpenseStatus, ExpenseType, type RentalExpense } from './rentalExpenseApi';

vi.mock('@/shared/api/lookupRequest', () => ({ requestLookup: vi.fn() }));
vi.mock('@/shared/api/lookupApi', () => ({
    lookupApi: {
        suppliers: vi.fn(),
    },
}));
vi.mock('./rentalExpenseApi', async (importOriginal) => {
    const actual = await importOriginal<typeof import('./rentalExpenseApi')>();
    return {
        ...actual,
        createRentalExpense: vi.fn(),
    };
});

const mockExpense: RentalExpense = {
    id: 1,
    row_version: 1,
    expense_number: 'EXP-202611-0001',
    vehicle: { id: 12, label: 'WP-PX-2020 (Toyota KDH)', make: 'Toyota', model: 'KDH' },
    expense_type: ExpenseType.Fuel,
    expense_type_label: 'Fuel',
    expense_date: '2026-11-02',
    currency: { id: 1, code: 'LKR' },
    net_amount: '15000.00',
    tax_amount: '0.00',
    gross_amount: '15000.00',
    supplier: { id: 5, name: 'Lanka IOC Fuel Station' },
    reference_number: 'PET-88992',
    description: 'Fuel refill during delivery',
    status: ExpenseStatus.Draft,
    allocations: [],
    created_at: '2026-11-02 09:00:00',
};

beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(lookupApi.suppliers).mockResolvedValue({
        data: [{ id: 5, name: 'Lanka IOC Fuel Station' }],
    });
    vi.mocked(requestLookup).mockImplementation((url: string) => Promise.resolve(
        url.includes('/lookup/active')
            ? { data: [{ id: 12, registration_number: 'WP-PX-2020', vehicle_number: 'CAR-2020' }] }
            : { data: [{ id: 1, name: 'Sri Lankan Rupee', code: 'LKR' }] },
    ));
});

describe('RentalExpenseEditor', () => {
    it('creates a fleet expense record using controlled lookups', async () => {
        const onSaved = vi.fn();
        const onCancel = vi.fn();
        vi.mocked(createRentalExpense).mockResolvedValue(mockExpense);

        render(<RentalExpenseEditor onSaved={onSaved} onCancel={onCancel} />);

        // Vehicle selection via LookupSelect
        fireEvent.change(screen.getByRole('combobox', { name: 'Vehicle' }), { target: { value: '2020' } });
        fireEvent.click(await screen.findByRole('option', { name: /WP-PX-2020/i }));

        // Currency selection via LookupSelect
        fireEvent.change(screen.getByRole('combobox', { name: 'Currency' }), { target: { value: 'LKR' } });
        fireEvent.click(await screen.findByRole('option', { name: 'LKR - Sri Lankan Rupee' }));

        // Supplier selection via LookupSelect
        fireEvent.change(screen.getByRole('combobox', { name: 'Supplier / Station (Optional)' }), { target: { value: 'IOC' } });
        fireEvent.click(await screen.findByRole('option', { name: 'Lanka IOC Fuel Station' }));

        // Enter amount
        fireEvent.change(screen.getByLabelText('Net Amount'), { target: { value: '15000.00' } });

        const submitBtn = screen.getByRole('button', { name: 'Save Expense Record' });
        expect(submitBtn).toBeEnabled();
        fireEvent.click(submitBtn);

        await waitFor(() => {
            expect(createRentalExpense).toHaveBeenCalledWith(expect.objectContaining({
                vehicle_id: 12,
                currency_id: 1,
                supplier_id: 5,
                net_amount: '15000.00',
            }));
            expect(onSaved).toHaveBeenCalled();
        });
    });
});
