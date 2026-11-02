import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { MemoryRouter } from 'react-router-dom';
import RentalDashboardPage from './RentalDashboardPage';
import { type RentalDashboardSummary } from './rentalDashboardApi';
import * as rentalDashboardApi from './rentalDashboardApi';

vi.mock('./rentalDashboardApi', async (importOriginal) => {
    const actual = await importOriginal<typeof rentalDashboardApi>();
    return {
        ...actual,
        fetchRentalDashboard: vi.fn(),
    };
});

const mockSummary: RentalDashboardSummary = {
    metrics: {
        active_customer_agreements: 14,
        active_owner_agreements: 8,
        vehicles_in_custody: 12,
        planned_vehicle_uses: 7,
        pending_reservations: 5,
        confirmed_reservations: 2,
        due_finance_installments: 3,
        pending_expenses: 4,
        active_vehicle_incidents: 1,
        total_fleet: 25,
        available_fleet: 10,
    },
    recent_running_charts: [
        {
            id: 1,
            reference: 'RC-202611-0001',
            vehicle_label: 'WP-CAD-1234 (Toyota Prius)',
            customer_reference: 'AGR-CUST-100',
            starts_at: '2026-11-01T08:00:00Z',
            ends_at: '2026-11-01T17:00:00Z',
            total_km: '150.000000',
            status: 'submitted',
        },
    ],
};

beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(rentalDashboardApi.fetchRentalDashboard).mockResolvedValue(mockSummary);
});

describe('RentalDashboardPage', () => {
    it('renders rental operational KPIs and quick action links', async () => {
        render(
            <MemoryRouter>
                <RentalDashboardPage />
            </MemoryRouter>
        );

        expect(await screen.findByText('Vehicle Rental Operations Dashboard')).toBeInTheDocument();

        // Check KPI cards
        expect(screen.getByText('Active Customer Rentals')).toBeInTheDocument();
        expect(screen.getByText('14')).toBeInTheDocument();
        expect(screen.getByText('Pending Reservations')).toBeInTheDocument();
        expect(screen.getByText('5')).toBeInTheDocument();
        expect(screen.getByText('Vehicles In Custody')).toBeInTheDocument();
        expect(screen.getByText('12')).toBeInTheDocument();
        expect(screen.getByText('Due Finance Installments')).toBeInTheDocument();
        expect(screen.getByText('3')).toBeInTheDocument();
        expect(screen.getByText('Fleet Availability')).toBeInTheDocument();

        // Check recent running chart
        expect(screen.getByText('RC-202611-0001')).toBeInTheDocument();
        expect(screen.getByText('WP-CAD-1234 (Toyota Prius)')).toBeInTheDocument();
    });

    it('reloads operational metrics when Reload button is clicked', async () => {
        render(
            <MemoryRouter>
                <RentalDashboardPage />
            </MemoryRouter>
        );

        expect(await screen.findByText('Vehicle Rental Operations Dashboard')).toBeInTheDocument();
        fireEvent.click(screen.getByRole('button', { name: 'Reload' }));

        await waitFor(() => {
            expect(rentalDashboardApi.fetchRentalDashboard).toHaveBeenCalledTimes(2);
        });
    });
});
