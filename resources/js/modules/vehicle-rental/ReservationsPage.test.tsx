import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import ReservationsPage from './ReservationsPage';
import { ReservationStatus, type RentalReservation } from './reservationApi';
import * as reservationApi from './reservationApi';

const session = vi.hoisted(() => ({ roles: [] as string[], permissions: [] as string[], permissionsLoaded: true }));
vi.mock('@/modules/auth/AuthProvider', () => ({ useAuth: () => session }));
vi.mock('./reservationApi', async (importOriginal) => {
    const actual = await importOriginal<typeof reservationApi>();
    return {
        ...actual,
        listReservations: vi.fn(),
        transitionReservation: vi.fn(),
        convertReservation: vi.fn(),
    };
});

const mockReservation: RentalReservation = {
    id: 1,
    row_version: 1,
    reservation_number: 'RES-202611-0001',
    customer: { id: 10, name: 'Apex Logistics' },
    requested_vehicle: {
        id: 20,
        vehicle_number: 'CAR-1234',
        registration_number: 'WP-CAD-1234',
        label: 'WP-CAD-1234 (Toyota Prius)',
        make: 'Toyota',
        model: 'Prius',
    },
    requested_vehicle_category: null,
    rental_mode: 'self_drive',
    billing_cycle: 'daily',
    requested_start_at: '2026-11-10T10:00:00Z',
    requested_end_at: '2026-11-15T18:00:00Z',
    currency: { id: 1, code: 'LKR' },
    estimated_amount: '45000.00',
    estimated_deposit_amount: '10000.00',
    status: ReservationStatus.Draft,
    remarks: 'Airport transfer booking',
    converted_agreement: null,
    created_at: '2026-11-02 08:00:00',
};

beforeEach(() => {
    vi.clearAllMocks();
    session.permissions = ['vehicle-rental.reservations.view'];
    vi.mocked(reservationApi.listReservations).mockResolvedValue({
        data: [mockReservation],
        meta: { current_page: 1, last_page: 1, per_page: 15, total: 1, from: 1, to: 1 },
    });
});

describe('ReservationsPage', () => {
    it('renders reservation list in read-only mode for view-only users', async () => {
        render(<ReservationsPage />);
        expect(await screen.findByText('RES-202611-0001')).toBeInTheDocument();
        expect(screen.getByText('Apex Logistics')).toBeInTheDocument();
        expect(screen.getByText('WP-CAD-1234 (Toyota Prius)')).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'New Reservation' })).not.toBeInTheDocument();
    });

    it('allows managers to view actions, open details, and confirm a draft booking', async () => {
        session.permissions = ['vehicle-rental.reservations.view', 'vehicle-rental.reservations.manage'];
        vi.mocked(reservationApi.transitionReservation).mockResolvedValue({
            ...mockReservation,
            status: ReservationStatus.Confirmed,
            row_version: 2,
        });

        render(<ReservationsPage />);
        expect(await screen.findByRole('button', { name: 'New Reservation' })).toBeInTheDocument();

        // Open details
        const detailsBtn = screen.getByRole('button', { name: 'Details' });
        fireEvent.click(detailsBtn);

        expect(await screen.findByRole('button', { name: 'Confirm Booking' })).toBeInTheDocument();
        fireEvent.click(screen.getByRole('button', { name: 'Confirm Booking' }));

        await waitFor(() => {
            expect(reservationApi.transitionReservation).toHaveBeenCalledWith(1, 1, ReservationStatus.Confirmed);
        });
    });

    it('allows converting confirmed reservation to agreement', async () => {
        session.permissions = ['vehicle-rental.reservations.view', 'vehicle-rental.reservations.manage'];
        vi.mocked(reservationApi.listReservations).mockResolvedValue({
            data: [{ ...mockReservation, status: ReservationStatus.Confirmed }],
            meta: { current_page: 1, last_page: 1, per_page: 15, total: 1, from: 1, to: 1 },
        });
        vi.mocked(reservationApi.convertReservation).mockResolvedValue({
            id: 101,
            reference: 'AGR-CUST-101',
        });

        render(<ReservationsPage />);
        const detailsBtn = await screen.findByRole('button', { name: 'Details' });
        fireEvent.click(detailsBtn);

        const convertBtn = await screen.findByRole('button', { name: 'Convert to Agreement' });
        fireEvent.click(convertBtn);

        await waitFor(() => {
            expect(reservationApi.convertReservation).toHaveBeenCalledWith(1, 1, {});
        });
    });
});
