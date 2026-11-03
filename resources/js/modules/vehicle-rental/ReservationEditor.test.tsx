import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { requestLookup } from '@/shared/api/lookupRequest';
import { lookupApi } from '@/shared/api/lookupApi';
import { ReservationEditor } from './ReservationEditor';
import { createReservation, updateReservation, ReservationStatus, type RentalReservation } from './reservationApi';
import * as reservationApi from './reservationApi';

vi.mock('@/shared/api/lookupRequest', () => ({ requestLookup: vi.fn() }));
vi.mock('@/shared/api/lookupApi', () => ({
    lookupApi: {
        customers: vi.fn(),
    },
}));
vi.mock('./reservationApi', async (importOriginal) => {
    const actual = await importOriginal<typeof reservationApi>();
    return {
        ...actual,
        createReservation: vi.fn(),
        updateReservation: vi.fn(),
    };
});

const mockReservation: RentalReservation = {
    id: 5,
    row_version: 1,
    reservation_number: 'RR-0005',
    customer: { id: 10, name: 'Lanka Tours Ltd' },
    requested_vehicle: { id: 25, label: 'WP-CAD-5555', vehicle_number: 'CAR-5555' },
    requested_vehicle_category: null,
    rental_mode: 'self_drive',
    billing_cycle: 'daily',
    requested_start_at: '2026-11-10T10:00:00Z',
    requested_end_at: '2026-11-15T18:00:00Z',
    currency: { id: 1, code: 'LKR' },
    estimated_amount: '35000.00',
    estimated_deposit_amount: '15000.00',
    status: ReservationStatus.Draft,
    remarks: 'Tour booking',
    converted_agreement: null,
    created_at: '2026-11-03 10:00:00',
};

beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(lookupApi.customers).mockResolvedValue({
        data: [{ id: 10, name: 'Lanka Tours Ltd' }],
    });
    vi.mocked(requestLookup).mockImplementation((url: string) => Promise.resolve(
        url.includes('/lookup/active')
            ? { data: [{ id: 25, registration_number: 'WP-CAD-5555', vehicle_number: 'CAR-5555' }] }
            : { data: [{ id: 1, name: 'Sri Lankan Rupee', code: 'LKR' }] },
    ));
});

describe('ReservationEditor', () => {
    it('creates a new reservation using controlled lookup selectors', async () => {
        const onSaved = vi.fn();
        const onCancel = vi.fn();
        vi.mocked(createReservation).mockResolvedValue({ ...mockReservation, id: 10 });

        render(<ReservationEditor onSaved={onSaved} onCancel={onCancel} />);

        // Customer selection via LookupSelect
        fireEvent.change(screen.getByRole('combobox', { name: 'Customer' }), { target: { value: 'Lanka' } });
        fireEvent.click(await screen.findByRole('option', { name: 'Lanka Tours Ltd' }));

        // Vehicle selection via LookupSelect
        fireEvent.change(screen.getByRole('combobox', { name: 'Requested Vehicle (Optional)' }), { target: { value: '5555' } });
        fireEvent.click(await screen.findByRole('option', { name: /WP-CAD-5555/i }));

        // Currency selection via LookupSelect
        fireEvent.change(screen.getByRole('combobox', { name: 'Currency' }), { target: { value: 'LKR' } });
        fireEvent.click(await screen.findByRole('option', { name: 'LKR - Sri Lankan Rupee' }));

        // Fill dates and amounts
        fireEvent.change(screen.getByLabelText('Requested Start Date & Time'), { target: { value: '2026-11-10T10:00' } });
        fireEvent.change(screen.getByLabelText('Requested End Date & Time'), { target: { value: '2026-11-15T18:00' } });
        fireEvent.change(screen.getByLabelText('Estimated Rent Amount'), { target: { value: '35000.00' } });
        fireEvent.change(screen.getByLabelText('Estimated Deposit Amount'), { target: { value: '15000.00' } });

        const submitBtn = screen.getByRole('button', { name: 'Create Reservation' });
        expect(submitBtn).toBeEnabled();
        fireEvent.click(submitBtn);

        await waitFor(() => {
            expect(createReservation).toHaveBeenCalledWith(expect.objectContaining({
                customer_id: 10,
                requested_vehicle_id: 25,
                currency_id: 1,
                requested_start_at: '2026-11-10T10:00',
                requested_end_at: '2026-11-15T18:00',
                estimated_amount: '35000.00',
                estimated_deposit_amount: '15000.00',
            }));
            expect(onSaved).toHaveBeenCalled();
        });
    });

    it('updates an existing draft reservation', async () => {
        const onSaved = vi.fn();
        const onCancel = vi.fn();
        vi.mocked(updateReservation).mockResolvedValue({ ...mockReservation, estimated_amount: '40000.00', row_version: 2 });

        render(<ReservationEditor reservation={mockReservation} onSaved={onSaved} onCancel={onCancel} />);

        expect(screen.getByText('Edit Reservation RR-0005')).toBeInTheDocument();
        expect(screen.getByRole('combobox', { name: 'Customer' })).toHaveValue('Lanka Tours Ltd');

        fireEvent.change(screen.getByLabelText('Estimated Rent Amount'), { target: { value: '40000.00' } });

        const submitBtn = screen.getByRole('button', { name: 'Save Changes' });
        fireEvent.click(submitBtn);

        await waitFor(() => {
            expect(updateReservation).toHaveBeenCalledWith(5, 1, expect.objectContaining({
                customer_id: 10,
                estimated_amount: '40000.00',
            }));
            expect(onSaved).toHaveBeenCalled();
        });
    });
});
