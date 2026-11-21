import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import UpcomingServicesReportPage from './UpcomingServicesReportPage';
import type { UpcomingServicesResult } from '../upcomingServicesTypes';

const apiMocks = vi.hoisted(() => ({
    runUpcomingServicesReport: vi.fn(),
}));

vi.mock('../reportingApi', () => apiMocks);
vi.mock('../components/ExportActions', () => ({
    ExportActions: () => <div data-testid="export-actions">Export Actions</div>,
}));
vi.mock('@/modules/vehicle/components/VehicleLookupSelect', () => ({
    VehicleLookupSelect: () => <div>Vehicle Select Mock</div>,
}));
vi.mock('@/modules/customer/components/CustomerLookupSelect', () => ({
    CustomerLookupSelect: () => <div>Customer Select Mock</div>,
}));

const mockResult: UpcomingServicesResult = {
    data: [
        {
            id: 101,
            job_number: 'JOB-2026-0101',
            job_date: '2026-05-01',
            job_type: 'full_service',
            job_type_label: 'Full Service',
            job_status: 'completed',
            job_status_label: 'Completed',
            next_service_date: '2026-06-01',
            days_diff: -5,
            days_overdue: 5,
            days_until_due: 0,
            urgency_status: 'overdue',
            urgency_label: '5 days overdue',
            odometer_reading: '15000.000000',
            next_service_mileage: '20000.000000',
            vehicle: {
                id: 1,
                registration_number: 'WP CAB-1234',
                vehicle_number: 'V-001',
                display_name: 'WP CAB-1234 (Toyota Corolla)',
                make: 'Toyota',
                model: 'Corolla',
                year: 2020,
                current_odometer: '16200.000000',
                odometer_unit: 'km',
            },
            registration_number: 'WP CAB-1234',
            vehicle_label: 'WP CAB-1234 (Toyota Corolla)',
            customer: {
                id: 10,
                code: 'CUS-001',
                name: 'Alice Johnson',
                phone: '0771122334',
                email: 'alice@example.com',
            },
            customer_name: 'Alice Johnson',
            customer_phone: '0771122334',
        },
        {
            id: 102,
            job_number: 'JOB-2026-0102',
            job_date: '2026-05-15',
            job_type: 'oil_change',
            job_type_label: 'Oil Change',
            job_status: 'invoiced',
            job_status_label: 'Invoiced',
            next_service_date: '2026-06-15',
            days_diff: 3,
            days_overdue: 0,
            days_until_due: 3,
            urgency_status: 'due_this_week',
            urgency_label: 'Due in 3 days',
            odometer_reading: '28000.000000',
            next_service_mileage: null,
            vehicle: {
                id: 2,
                registration_number: 'WP CAD-5678',
                vehicle_number: 'V-002',
                display_name: 'WP CAD-5678 (Honda Civic)',
                make: 'Honda',
                model: 'Civic',
                year: 2022,
                current_odometer: '28000.000000',
                odometer_unit: 'km',
            },
            registration_number: 'WP CAD-5678',
            vehicle_label: 'WP CAD-5678 (Honda Civic)',
            customer: {
                id: 20,
                code: 'CUS-002',
                name: 'Bob Smith',
                phone: '0779988776',
                email: 'bob@example.com',
            },
            customer_name: 'Bob Smith',
            customer_phone: '0779988776',
        },
    ],
    summary: {
        total_upcoming: 8,
        total_overdue: 2,
        total_due_today: 1,
        total_due_this_week: 3,
        total_due_this_month: 2,
    },
    meta: {
        current_page: 1,
        from: 1,
        last_page: 1,
        per_page: 25,
        to: 2,
        total: 2,
    },
    links: {
        first: null,
        last: null,
        prev: null,
        next: null,
    },
    report: {
        key: 'vehicle-service/upcoming-services',
        title: 'Upcoming Vehicle Services',
        group: 'Vehicle Service',
        description: 'Vehicles due or overdue for scheduled service based on next service date.',
        columns: [],
    },
};

describe('UpcomingServicesReportPage', () => {
    it('renders KPI cards, table rows, and operational details accurately', async () => {
        apiMocks.runUpcomingServicesReport.mockResolvedValue(mockResult);

        render(
            <TestRouter>
                <UpcomingServicesReportPage />
            </TestRouter>
        );

        // Verify Header
        expect(await screen.findByRole('heading', { name: 'Upcoming Services Report' })).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'All reports' })).toHaveAttribute('href', '/reports');

        // Verify KPI Cards
        expect(screen.getByText('Total Scheduled')).toBeInTheDocument();
        expect(screen.getByText('8')).toBeInTheDocument();
        const overdueBtn = screen.getByRole('button', { name: /Overdue/i });
        expect(within(overdueBtn).getByText('2')).toBeInTheDocument();
        const dueTodayBtn = screen.getByRole('button', { name: /Due Today/i });
        expect(within(dueTodayBtn).getByText('1')).toBeInTheDocument();
        const next7Btn = screen.getByRole('button', { name: /Next 7 Days/i });
        expect(within(next7Btn).getByText('3')).toBeInTheDocument();
        const next30Btn = screen.getByRole('button', { name: /Next 30 Days/i });
        expect(within(next30Btn).getByText('2')).toBeInTheDocument();

        // Verify Table Rows
        expect(screen.getByText('5 days overdue')).toBeInTheDocument();
        expect(screen.getByText('WP CAB-1234')).toBeInTheDocument();
        expect(screen.getByText('Toyota Corolla (2020)')).toBeInTheDocument();
        expect(screen.getByText('Alice Johnson')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: '0771122334' })).toHaveAttribute('href', 'tel:0771122334');
        expect(screen.getByRole('link', { name: 'JOB-2026-0101' })).toHaveAttribute('href', '/vehicle-service/jobs/101');

        // Verify Second Row
        expect(screen.getByText('Due in 3 days')).toBeInTheDocument();
        expect(screen.getByText('WP CAD-5678')).toBeInTheDocument();
        expect(screen.getByText('Honda Civic (2022)')).toBeInTheDocument();
        expect(screen.getByText('Bob Smith')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'JOB-2026-0102' })).toHaveAttribute('href', '/vehicle-service/jobs/102');

        // Verify Quick Actions
        const newServiceLinks = screen.getAllByRole('link', { name: 'New Service' });
        expect(newServiceLinks[0]).toHaveAttribute(
            'href',
            '/vehicle-service/jobs/create?vehicle_id=1&customer_id=10'
        );
        expect(newServiceLinks[1]).toHaveAttribute(
            'href',
            '/vehicle-service/jobs/create?vehicle_id=2&customer_id=20'
        );
    });

    it('switches preset filters when clicking KPI card buttons', async () => {
        apiMocks.runUpcomingServicesReport.mockResolvedValue(mockResult);
        const user = userEvent.setup();

        render(
            <TestRouter>
                <UpcomingServicesReportPage />
            </TestRouter>
        );

        await screen.findByRole('heading', { name: 'Upcoming Services Report' });

        // Click Overdue card
        const overdueButton = screen.getByRole('button', { name: /Overdue/i });
        await user.click(overdueButton);

        expect(apiMocks.runUpcomingServicesReport).toHaveBeenCalledWith(
            expect.objectContaining({
                status_preset: 'overdue',
                page: 1,
            }),
            expect.any(AbortSignal)
        );
    });

    it('applies search and form filters on submit', async () => {
        apiMocks.runUpcomingServicesReport.mockResolvedValue(mockResult);
        const user = userEvent.setup();

        render(
            <TestRouter>
                <UpcomingServicesReportPage />
            </TestRouter>
        );

        await screen.findByRole('heading', { name: 'Upcoming Services Report' });

        const searchInput = screen.getByRole('textbox', { name: /Search/i });
        await user.clear(searchInput);
        await user.type(searchInput, 'CAB-1234');

        const applyButton = screen.getByRole('button', { name: 'Apply filters' });
        await user.click(applyButton);

        expect(apiMocks.runUpcomingServicesReport).toHaveBeenCalledWith(
            expect.objectContaining({
                search: 'CAB-1234',
                page: 1,
            }),
            expect.any(AbortSignal)
        );
    });

    it('displays empty state when no upcoming services match', async () => {
        apiMocks.runUpcomingServicesReport.mockResolvedValue({
            ...mockResult,
            data: [],
            meta: { ...mockResult.meta, total: 0 },
        });

        render(
            <TestRouter>
                <UpcomingServicesReportPage />
            </TestRouter>
        );

        expect(await screen.findByText('No upcoming services found')).toBeInTheDocument();
        expect(
            screen.getByText('Try selecting another status preset, clearing the vehicle or customer filter, or widening the date range.')
        ).toBeInTheDocument();
    });
});
