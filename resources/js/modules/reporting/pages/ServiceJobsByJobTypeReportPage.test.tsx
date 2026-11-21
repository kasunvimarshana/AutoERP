import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import ServiceJobsByJobTypeReportPage from './ServiceJobsByJobTypeReportPage';
import type { ServiceJobsByJobTypeResult } from '../types/serviceJobsByJobTypeReportTypes';

const apiMocks = vi.hoisted(() => ({
    runServiceJobsByJobTypeReport: vi.fn(),
}));

const jobTypeMocks = vi.hoisted(() => ({
    lookupVehicleServiceJobTypes: vi.fn(),
}));

vi.mock('../reportingApi', () => apiMocks);
vi.mock('@/modules/vehicle-service/api/jobTypes', () => jobTypeMocks);
vi.mock('../components/ExportActions', () => ({
    ExportActions: () => <div data-testid="export-actions">Export Actions Mock</div>,
}));
vi.mock('@/modules/vehicle/components/VehicleLookupSelect', () => ({
    VehicleLookupSelect: () => <div>Vehicle Select Mock</div>,
}));
vi.mock('@/modules/customer/components/CustomerLookupSelect', () => ({
    CustomerLookupSelect: () => <div>Customer Select Mock</div>,
}));

const mockResult: ServiceJobsByJobTypeResult = {
    data: [
        {
            id: 201,
            job_number: 'JOB-2026-0201',
            job_date: '2026-06-10',
            expected_delivery_date: '2026-06-11',
            next_service_date: '2026-07-10',
            job_type_id: 1,
            job_type_code: 'full_service',
            job_type_name: 'Full Service',
            tracks_mileage: true,
            status: 'completed',
            status_label: 'Completed',
            customer: {
                id: 11,
                code: 'CUS-011',
                name: 'Alice Johnson',
                phone: '0771234567',
                email: 'alice@example.com',
            },
            customer_name: 'Alice Johnson',
            customer_phone: '0771234567',
            vehicle: {
                id: 101,
                registration_number: 'WP CAB-1234',
                vehicle_number: 'V-101',
                display_name: 'WP CAB-1234 (Toyota Corolla)',
                make: 'Toyota',
                model: 'Corolla',
                year: 2021,
            },
            registration_number: 'WP CAB-1234',
            vehicle_label: 'WP CAB-1234 (Toyota Corolla)',
            supervisor: {
                id: 5,
                name: 'David Supervisor',
                code: 'EMP-005',
            },
            supervisor_name: 'David Supervisor',
            odometer_reading: '15000.000000',
            next_service_mileage: '20000.000000',
            subtotal: '500.000000',
            discount_total: '50.000000',
            tax_total: '40.000000',
            charge_total: '10.000000',
            grand_total: '500.000000',
            commission_cost_total: '50.000000',
            net_after_commission: '450.000000',
        },
        {
            id: 202,
            job_number: 'JOB-2026-0202',
            job_date: '2026-06-12',
            expected_delivery_date: null,
            next_service_date: null,
            job_type_id: 2,
            job_type_code: 'oil_change',
            job_type_name: 'Oil Change',
            tracks_mileage: true,
            status: 'invoiced',
            status_label: 'Invoiced',
            customer: {
                id: 12,
                code: 'CUS-012',
                name: 'Bob Smith',
                phone: '0779988776',
                email: 'bob@example.com',
            },
            customer_name: 'Bob Smith',
            customer_phone: '0779988776',
            vehicle: {
                id: 102,
                registration_number: 'WP CAD-5678',
                vehicle_number: 'V-102',
                display_name: 'WP CAD-5678 (Honda Civic)',
                make: 'Honda',
                model: 'Civic',
                year: 2022,
            },
            registration_number: 'WP CAD-5678',
            vehicle_label: 'WP CAD-5678 (Honda Civic)',
            supervisor: null,
            supervisor_name: null,
            odometer_reading: '28000.000000',
            next_service_mileage: null,
            subtotal: '300.000000',
            discount_total: '0.000000',
            tax_total: '20.000000',
            charge_total: '0.000000',
            grand_total: '320.000000',
            commission_cost_total: '0.000000',
            net_after_commission: '320.000000',
        },
    ],
    summary: {
        total_jobs: 2,
        total_subtotal: '800.000000',
        total_discount: '50.000000',
        total_tax: '60.000000',
        total_charges: '10.000000',
        total_revenue: '820.000000',
        total_commission: '50.000000',
        average_job_value: '410.000000',
        status_counts: {
            completed: 1,
            invoiced: 1,
        },
    },
    groups: [
        {
            job_type_id: 1,
            job_type_code: 'full_service',
            job_type_name: 'Full Service',
            tracks_mileage: true,
            total_jobs: 1,
            total_subtotal: '500.000000',
            total_discount: '50.000000',
            total_tax: '40.000000',
            total_charges: '10.000000',
            total_revenue: '500.000000',
            average_job_value: '500.000000',
            revenue_share_percentage: 61.0,
            jobs_share_percentage: 50.0,
            jobs: [
                {
                    id: 201,
                    job_number: 'JOB-2026-0201',
                    job_date: '2026-06-10',
                    expected_delivery_date: '2026-06-11',
                    next_service_date: '2026-07-10',
                    job_type_id: 1,
                    job_type_code: 'full_service',
                    job_type_name: 'Full Service',
                    tracks_mileage: true,
                    status: 'completed',
                    status_label: 'Completed',
                    customer: {
                        id: 11,
                        code: 'CUS-011',
                        name: 'Alice Johnson',
                        phone: '0771234567',
                        email: 'alice@example.com',
                    },
                    customer_name: 'Alice Johnson',
                    customer_phone: '0771234567',
                    vehicle: {
                        id: 101,
                        registration_number: 'WP CAB-1234',
                        vehicle_number: 'V-101',
                        display_name: 'WP CAB-1234 (Toyota Corolla)',
                        make: 'Toyota',
                        model: 'Corolla',
                        year: 2021,
                    },
                    registration_number: 'WP CAB-1234',
                    vehicle_label: 'WP CAB-1234 (Toyota Corolla)',
                    supervisor: {
                        id: 5,
                        name: 'David Supervisor',
                        code: 'EMP-005',
                    },
                    supervisor_name: 'David Supervisor',
                    odometer_reading: '15000.000000',
                    next_service_mileage: '20000.000000',
                    subtotal: '500.000000',
                    discount_total: '50.000000',
                    tax_total: '40.000000',
                    charge_total: '10.000000',
                    grand_total: '500.000000',
                    commission_cost_total: '50.000000',
                    net_after_commission: '450.000000',
                },
            ],
        },
        {
            job_type_id: 2,
            job_type_code: 'oil_change',
            job_type_name: 'Oil Change',
            tracks_mileage: true,
            total_jobs: 1,
            total_subtotal: '300.000000',
            total_discount: '0.000000',
            total_tax: '20.000000',
            total_charges: '0.000000',
            total_revenue: '320.000000',
            average_job_value: '320.000000',
            revenue_share_percentage: 39.0,
            jobs_share_percentage: 50.0,
            jobs: [
                {
                    id: 202,
                    job_number: 'JOB-2026-0202',
                    job_date: '2026-06-12',
                    expected_delivery_date: null,
                    next_service_date: null,
                    job_type_id: 2,
                    job_type_code: 'oil_change',
                    job_type_name: 'Oil Change',
                    tracks_mileage: true,
                    status: 'invoiced',
                    status_label: 'Invoiced',
                    customer: {
                        id: 12,
                        code: 'CUS-012',
                        name: 'Bob Smith',
                        phone: '0779988776',
                        email: 'bob@example.com',
                    },
                    customer_name: 'Bob Smith',
                    customer_phone: '0779988776',
                    vehicle: {
                        id: 102,
                        registration_number: 'WP CAD-5678',
                        vehicle_number: 'V-102',
                        display_name: 'WP CAD-5678 (Honda Civic)',
                        make: 'Honda',
                        model: 'Civic',
                        year: 2022,
                    },
                    registration_number: 'WP CAD-5678',
                    vehicle_label: 'WP CAD-5678 (Honda Civic)',
                    supervisor: null,
                    supervisor_name: null,
                    odometer_reading: '28000.000000',
                    next_service_mileage: null,
                    subtotal: '300.000000',
                    discount_total: '0.000000',
                    tax_total: '20.000000',
                    charge_total: '0.000000',
                    grand_total: '320.000000',
                    commission_cost_total: '0.000000',
                    net_after_commission: '320.000000',
                },
            ],
        },
    ],
    meta: {
        current_page: 1,
        from: 1,
        last_page: 1,
        per_page: 25,
        to: 2,
        total: 2,
    },
    links: {
        first: '?page=1',
        last: '?page=1',
        prev: null,
        next: null,
    },
    report: {
        key: 'vehicle-service/jobs-by-type',
        title: 'Service Jobs by Job Type',
        group: 'Vehicle Service',
        columns: [],
        filters: [],
        supports_date_range: true,
        default_sort: 'job_date',
        default_direction: 'desc',
    },
};

describe('ServiceJobsByJobTypeReportPage', () => {
    it('renders header, KPI metrics cards, comparison table, and grouped jobs', async () => {
        jobTypeMocks.lookupVehicleServiceJobTypes.mockResolvedValueOnce([
            { id: 1, name: 'Full Service', code: 'full_service', tracks_mileage: true },
            { id: 2, name: 'Oil Change', code: 'oil_change', tracks_mileage: true },
        ]);
        apiMocks.runServiceJobsByJobTypeReport.mockResolvedValueOnce(mockResult);

        render(
            <TestRouter>
                <ServiceJobsByJobTypeReportPage />
            </TestRouter>,
        );

        // Header
        expect(await screen.findByRole('heading', { level: 1, name: 'Service Jobs by Job Type' })).toBeInTheDocument();
        expect(screen.getByTestId('export-actions')).toBeInTheDocument();

        // Overall KPI cards
        const kpiSection = screen.getByRole('region', { name: 'Summary KPI Metrics' });
        expect(within(kpiSection).getByText('Total Jobs')).toBeInTheDocument();
        expect(within(kpiSection).getByText('2')).toBeInTheDocument();
        expect(within(kpiSection).getByText('Total Revenue')).toBeInTheDocument();
        expect(within(kpiSection).getByText('Average Job Value')).toBeInTheDocument();

        // Comparison Table
        expect(screen.getByText('Job Type Comparison & Performance')).toBeInTheDocument();
        const fullServiceOccurrences = screen.getAllByText('Full Service');
        expect(fullServiceOccurrences.length).toBeGreaterThanOrEqual(1);

        // Grouped detailed jobs
        const job1Links = screen.getAllByText('JOB-2026-0201');
        expect(job1Links.length).toBeGreaterThanOrEqual(1);
        expect(screen.getAllByText('Alice Johnson').length).toBeGreaterThanOrEqual(1);
        expect(screen.getAllByText('WP CAB-1234').length).toBeGreaterThanOrEqual(1);

        const job2Links = screen.getAllByText('JOB-2026-0202');
        expect(job2Links.length).toBeGreaterThanOrEqual(1);
        expect(screen.getAllByText('Bob Smith').length).toBeGreaterThanOrEqual(1);
        expect(screen.getAllByText('WP CAD-5678').length).toBeGreaterThanOrEqual(1);
    });

    it('toggles group collapse when header is clicked', async () => {
        jobTypeMocks.lookupVehicleServiceJobTypes.mockResolvedValueOnce([]);
        apiMocks.runServiceJobsByJobTypeReport.mockResolvedValueOnce(mockResult);

        const user = userEvent.setup();

        render(
            <TestRouter>
                <ServiceJobsByJobTypeReportPage />
            </TestRouter>,
        );

        await screen.findByRole('heading', { level: 1, name: 'Service Jobs by Job Type' });

        // Both jobs visible initially
        expect(screen.getAllByText('JOB-2026-0201').length).toBeGreaterThanOrEqual(1);

        // Find "Hide Jobs" button for Full Service group
        const hideButtons = screen.getAllByRole('button', { name: 'Hide Jobs' });
        await user.click(hideButtons[0]);

        // Button should switch to "Show Jobs"
        expect(screen.getByRole('button', { name: 'Show Jobs' })).toBeInTheDocument();
    });

    it('allows changing date preset and re-fetches report', async () => {
        jobTypeMocks.lookupVehicleServiceJobTypes.mockResolvedValueOnce([]);
        apiMocks.runServiceJobsByJobTypeReport.mockResolvedValue(mockResult);

        const user = userEvent.setup();

        render(
            <TestRouter>
                <ServiceJobsByJobTypeReportPage />
            </TestRouter>,
        );

        await screen.findByRole('heading', { level: 1, name: 'Service Jobs by Job Type' });

        // Click "This Week" preset
        const thisWeekButton = screen.getByRole('button', { name: 'This Week' });
        await user.click(thisWeekButton);

        expect(apiMocks.runServiceJobsByJobTypeReport).toHaveBeenCalledWith(
            expect.objectContaining({
                date_preset: 'this_week',
            }),
            expect.anything(),
        );
    });

    it('submits search filter keyword', async () => {
        jobTypeMocks.lookupVehicleServiceJobTypes.mockResolvedValueOnce([]);
        apiMocks.runServiceJobsByJobTypeReport.mockResolvedValue(mockResult);

        const user = userEvent.setup();

        render(
            <TestRouter>
                <ServiceJobsByJobTypeReportPage />
            </TestRouter>,
        );

        await screen.findByRole('heading', { level: 1, name: 'Service Jobs by Job Type' });

        const searchInput = screen.getByLabelText('Search Keyword');
        await user.clear(searchInput);
        await user.type(searchInput, 'Corolla');

        const applyButton = screen.getByRole('button', { name: 'Apply filters' });
        await user.click(applyButton);

        expect(apiMocks.runServiceJobsByJobTypeReport).toHaveBeenCalledWith(
            expect.objectContaining({
                search: 'Corolla',
            }),
            expect.anything(),
        );
    });

    it('renders empty message when no service jobs found', async () => {
        jobTypeMocks.lookupVehicleServiceJobTypes.mockResolvedValueOnce([]);
        apiMocks.runServiceJobsByJobTypeReport.mockResolvedValueOnce({
            ...mockResult,
            data: [],
            groups: [],
            summary: {
                total_jobs: 0,
                total_subtotal: '0.000000',
                total_discount: '0.000000',
                total_tax: '0.000000',
                total_charges: '0.000000',
                total_revenue: '0.000000',
                total_commission: '0.000000',
                average_job_value: '0.000000',
                status_counts: {},
            },
        });

        render(
            <TestRouter>
                <ServiceJobsByJobTypeReportPage />
            </TestRouter>,
        );

        expect(
            await screen.findByText('No service jobs found matching the selected filters.'),
        ).toBeInTheDocument();
    });
});
